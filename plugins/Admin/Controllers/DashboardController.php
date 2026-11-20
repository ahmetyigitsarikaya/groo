<?php

declare(strict_types=1);

namespace Plugins\Admin\Controllers;

use App\Core\Database\DynamicEntityRepository;
use App\Core\Database\QueryBuilder;
use App\Core\View\View;
use Throwable;

final class DashboardController extends BaseController
{
    public function index(array $params): void
    {
        $plugins = View::shared('plugins');
        $activePlugins = $plugins !== null ? $plugins->getPlugins() : [];
        $templateMeta = View::shared('template');

        $entities = [];
        $totalRecords = 0;

        try {
            $entities = QueryBuilder::table(DynamicEntityRepository::TABLE_ENTITIES)
                ->orderBy('id')
                ->get();

            foreach ($entities as &$entity) {
                $count = QueryBuilder::table(DynamicEntityRepository::TABLE_RECORDS)
                    ->where('entity_id', '=', $entity['id'])
                    ->count();
                $entity['record_count'] = $count;
                $totalRecords += $count;
            }
            unset($entity);
        } catch (Throwable $e) {
            $entities = [];
        }

        $currentMonthProfit = 0.0;
        if (class_exists(\Plugins\Crm\Services\FinanceService::class)) {
            try {
                $stats = (new \Plugins\Crm\Services\FinanceService())->getMonthlyFinanceStats();
                if (!empty($stats)) {
                    $last = end($stats);
                    $currentMonthProfit = (float) ($last['income'] - $last['expense']);
                }
            } catch (Throwable $e) {}
        }

        $this->render('pages/admin/dashboard', [
            'active_plugin_count' => count($activePlugins),
            'active_plugins' => $activePlugins,
            'active_template' => is_array($templateMeta) ? $templateMeta : null,
            'entities' => $entities,
            'total_records' => $totalRecords,
            'current_month_profit' => $currentMonthProfit,
        ]);
    }
}
