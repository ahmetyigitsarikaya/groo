<?php

declare(strict_types=1);

namespace Plugins\Crm\Services;

use App\Core\Database\Connection;
use App\Core\Database\QueryBuilder;
use App\Core\Exceptions\ValidationException;
use PDO;

final class FinanceService
{
    private PDO $pdo;

    private NotificationService $notificationService;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::getInstance();
        $this->notificationService = new NotificationService($this->pdo);
    }

    public function getAllInvoices(array $filters = []): array
    {
        $query = QueryBuilder::table('crm_invoices', $this->pdo);

        if (!empty($filters['type'])) {
            $query->where('type', '=', $filters['type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', '=', $filters['status']);
        }
        if (!empty($filters['contact_id'])) {
            $query->where('contact_id', '=', (int) $filters['contact_id']);
        }

        $invoices = $query->orderBy('id', 'DESC')->get();
        $contactService = new ContactService(null, $this->pdo);

        foreach ($invoices as &$inv) {
            $inv['items'] = is_string($inv['items']) ? (json_decode($inv['items'], true) ?: []) : ($inv['items'] ?? []);
            $contact = $contactService->getContactById((int) $inv['contact_id']);
            $inv['contact_name'] = $contact['name'] ?? '—';
        }

        return $invoices;
    }

    public function getInvoiceById(int $id): ?array
    {
        $inv = QueryBuilder::table('crm_invoices', $this->pdo)->find($id);
        if ($inv === null) {
            return null;
        }

        $inv['items'] = is_string($inv['items']) ? (json_decode($inv['items'], true) ?: []) : ($inv['items'] ?? []);
        $contact = (new ContactService(null, $this->pdo))->getContactById((int) $inv['contact_id']);
        $inv['contact_name'] = $contact['name'] ?? '—';
        $inv['contact_email'] = $contact['email'] ?? '—';
        $inv['contact_address'] = $contact['address'] ?? '—';

        return $inv;
    }

    public function createInvoice(array $data): int
    {
        $type = (string) ($data['type'] ?? 'income');

        $invoiceNum = !empty($data['invoice_number'])
            ? trim((string) $data['invoice_number'])
            : ($type === 'expense' ? 'EXP-' : 'INV-') . date('Ymd') . '-' . rand(100, 999);

        $items = $this->prepareItems($data['items'] ?? []);
        $totalAmount = 0.00;

        foreach ($items as $item) {
            $totalAmount += (float) ($item['total'] ?? 0);
        }

        if (isset($data['total_amount']) && (float) $data['total_amount'] > 0 && $totalAmount === 0.0) {
            $totalAmount = (float) $data['total_amount'];
        }

        $id = QueryBuilder::table('crm_invoices', $this->pdo)->insert([
            'invoice_number' => $invoiceNum,
            'type' => $type,
            'category' => !empty($data['category']) ? trim((string) $data['category']) : null,
            'contact_id' => (int) $data['contact_id'],
            'total_amount' => $totalAmount,
            'status' => (string) ($data['status'] ?? ($type === 'expense' ? 'paid' : 'unpaid')),
            'due_date' => !empty($data['due_date']) ? (string) $data['due_date'] : date('Y-m-d', strtotime('+14 days')),
            'items' => json_encode($items, JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $title = $type === 'expense' ? 'Yeni Gider/Harcama Kaydı' : 'Yeni Fatura Kesildi';
        $msg = sprintf('"%s" numaralı %s ₺ tutarındaki %s işlemi eklendi.', $invoiceNum, number_format($totalAmount, 2, ',', '.'), $type === 'expense' ? 'gider' : 'fatura');

        $this->notificationService->createNotification($title, $msg, '/admin/crm/finance/' . $id, 'invoice');

        return $id;
    }

    public function createExpense(array $data): int
    {
        $data['type'] = 'expense';
        if (empty($data['category'])) {
            $data['category'] = 'Genel Gider';
        }
        return $this->createInvoice($data);
    }

    public function updateInvoiceStatus(int $id, string $status): bool
    {
        $inv = $this->getInvoiceById($id);
        if ($inv === null) {
            return false;
        }

        $affected = QueryBuilder::table('crm_invoices', $this->pdo)
            ->where('id', '=', $id)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $affected > 0;
    }

    public function deleteInvoice(int $id): bool
    {
        return QueryBuilder::table('crm_invoices', $this->pdo)->where('id', '=', $id)->delete() > 0;
    }

    public function getMonthlyFinanceStats(): array
    {
        // Get stats for the last 6 months
        $stats = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $stats[$month] = ['income' => 0.0, 'expense' => 0.0, 'label' => date('M Y', strtotime("-$i months"))];
        }

        $invoices = QueryBuilder::table('crm_invoices', $this->pdo)
            ->where('created_at', '>=', date('Y-m-01', strtotime('-5 months')) . ' 00:00:00')
            ->get();

        foreach ($invoices as $inv) {
            if ($inv['status'] !== 'paid' && $inv['type'] !== 'expense') {
                // Sadece nakit akışına (ödenen gelir ve ödenen gider) bakalım
                continue;
            }
            
            $date = date('Y-m', strtotime($inv['created_at']));
            if (isset($stats[$date])) {
                $amt = (float) $inv['total_amount'];
                if ($inv['type'] === 'expense') {
                    $stats[$date]['expense'] += $amt;
                } else {
                    $stats[$date]['income'] += $amt;
                }
            }
        }

        return array_values($stats);
    }

    public function getFinancialSummary(): array
    {
        $invoices = QueryBuilder::table('crm_invoices', $this->pdo)->get();

        $totalIncome = 0.00;
        $totalPaidIncome = 0.00;
        $totalUnpaidIncome = 0.00;
        $totalOverdueIncome = 0.00;

        $totalExpense = 0.00;

        $today = date('Y-m-d');

        foreach ($invoices as $inv) {
            $amt = (float) $inv['total_amount'];
            $type = $inv['type'] ?? 'income';

            if ($type === 'expense') {
                $totalExpense += $amt;
            } else {
                $totalIncome += $amt;
                if ($inv['status'] === 'paid') {
                    $totalPaidIncome += $amt;
                } elseif ($inv['status'] === 'overdue' || ($inv['status'] === 'unpaid' && !empty($inv['due_date']) && $inv['due_date'] < $today)) {
                    $totalOverdueIncome += $amt;
                } else {
                    $totalUnpaidIncome += $amt;
                }
            }
        }

        $netProfit = $totalPaidIncome - $totalExpense;

        return [
            'total_income' => $totalIncome,
            'total_paid_income' => $totalPaidIncome,
            'total_unpaid_income' => $totalUnpaidIncome,
            'total_unpaid' => $totalUnpaidIncome,
            'total_overdue_income' => $totalOverdueIncome,
            'total_overdue' => $totalOverdueIncome,
            'total_expense' => $totalExpense,
            'net_profit' => $netProfit,
            'count_total' => count($invoices),
        ];
    }

    private function prepareItems($raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($raw)) {
            return [];
        }

        $items = [];
        foreach ($raw as $item) {
            if (!is_array($item) || empty($item['description'])) {
                continue;
            }

            $hours = isset($item['hours']) ? (float) $item['hours'] : 1.0;
            $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : 0.0;
            $total = isset($item['total']) ? (float) $item['total'] : ($hours * $unitPrice);

            $items[] = [
                'description' => trim((string) $item['description']),
                'hours' => $hours,
                'unit_price' => $unitPrice,
                'total' => $total,
            ];
        }

        return $items;
    }
}
