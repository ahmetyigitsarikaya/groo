<?php

declare(strict_types=1);

namespace Plugins\Crm\Controllers;

use App\Core\Exceptions\ValidationException;
use Plugins\Admin\Controllers\BaseController;
use Plugins\Admin\Support\Flash;
use Plugins\Crm\Services\ContactService;
use Plugins\Crm\Services\FinanceService;
use Throwable;

final class CrmFinanceController extends BaseController
{
    private FinanceService $financeService;

    private ContactService $contactService;

    public function __construct(?FinanceService $financeService = null, ?ContactService $contactService = null)
    {
        parent::__construct();
        $this->financeService = $financeService ?? new FinanceService();
        $this->contactService = $contactService ?? new ContactService();
    }

    public function index(array $params = []): void
    {
        $status = $_GET['status'] ?? null;
        $type = $_GET['type'] ?? null;

        $filters = [];
        if (!empty($status)) {
            $filters['status'] = $status;
        }
        if (!empty($type)) {
            $filters['type'] = $type;
        }

        $invoices = $this->financeService->getAllInvoices($filters);
        $summary = $this->financeService->getFinancialSummary();
        $monthlyStats = $this->financeService->getMonthlyFinanceStats();

        $this->render('admin/crm/finance/index', [
            'invoices' => $invoices,
            'summary' => $summary,
            'monthly_stats' => $monthlyStats,
            'current_status' => $status,
            'current_type' => $type,
        ]);
    }

    public function create(array $params = []): void
    {
        $contacts = $this->contactService->getAllContacts();

        $this->render('admin/crm/finance/form', [
            'invoice' => null,
            'contacts' => $contacts,
            'is_edit' => false,
        ]);
    }

    public function createExpense(array $params = []): void
    {
        $contacts = $this->contactService->getAllContacts();

        $this->render('admin/crm/finance/expense_form', [
            'expense' => null,
            'contacts' => $contacts,
        ]);
    }

    public function store(array $params = []): void
    {
        $this->requireCsrf();

        $type = (string) ($_POST['type'] ?? 'income');

        $contactId = !empty($_POST['contact_id']) ? (int) $_POST['contact_id'] : 0;
        $manualContact = trim((string) ($_POST['manual_contact'] ?? ''));

        if ($contactId === 0 && $manualContact !== '') {
            try {
                $contactId = $this->contactService->createContact([
                    'name' => $manualContact,
                    'type' => 'customer',
                    'email' => '',
                    'phone' => '',
                ]);
            } catch (\Throwable $e) {}
        }

        $data = [
            'invoice_number' => trim((string) ($_POST['invoice_number'] ?? '')),
            'type' => $type,
            'category' => trim((string) ($_POST['category'] ?? '')),
            'contact_id' => $contactId,
            'due_date' => trim((string) ($_POST['due_date'] ?? date('Y-m-d'))),
            'status' => (string) ($_POST['status'] ?? ($type === 'expense' ? 'paid' : 'unpaid')),
            'total_amount' => (float) ($_POST['total_amount'] ?? 0),
            'items' => $_POST['items'] ?? [],
        ];

        try {
            $id = $this->financeService->createInvoice($data);
            $msg = $type === 'expense' ? 'Gider/Harcama kaydı başarıyla eklendi.' : 'Fatura ve hizmet kalemleri başarıyla oluşturuldu.';
            Flash::set('success', $msg);
            $this->redirect('/admin/crm/finance/' . $id);
        } catch (ValidationException $e) {
            Flash::set('error', 'Doğrulama hatası: ' . $e->getMessage());
            $this->render($type === 'expense' ? 'admin/crm/finance/expense_form' : 'admin/crm/finance/form', [
                'invoice' => $data,
                'expense' => $data,
                'contacts' => $this->contactService->getAllContacts(),
            ]);
        } catch (Throwable $e) {
            Flash::set('error', 'Hata oluştu: ' . $e->getMessage());
            $this->render($type === 'expense' ? 'admin/crm/finance/expense_form' : 'admin/crm/finance/form', [
                'invoice' => $data,
                'expense' => $data,
                'contacts' => $this->contactService->getAllContacts(),
            ]);
        }
    }

    public function show(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $invoice = $this->financeService->getInvoiceById($id);

        if ($invoice === null) {
            Flash::set('error', 'Fatura bulunamadı.');
            $this->redirect('/admin/crm/finance');
        }

        $this->render('admin/crm/finance/show', [
            'invoice' => $invoice,
        ]);
    }

    public function markPaid(array $params): void
    {
        $this->requireCsrf();

        $id = (int) ($params['id'] ?? 0);
        $this->financeService->updateInvoiceStatus($id, 'paid');

        Flash::set('success', 'Fatura ödendi olarak işaretlendi.');
        $this->redirect('/admin/crm/finance/' . $id);
    }

    public function destroy(array $params): void
    {
        $this->requireCsrf();

        $id = (int) ($params['id'] ?? 0);
        $this->financeService->deleteInvoice($id);

        Flash::set('success', 'Fatura / Gider kaydı silindi.');
        $this->redirect('/admin/crm/finance');
    }
}
