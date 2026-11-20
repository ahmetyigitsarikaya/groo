<?php

declare(strict_types=1);

namespace Plugins\Crm\Support;

use Plugins\Crm\Services\ContactService;
use Plugins\Crm\Services\FinanceService;
use Plugins\Crm\Services\NotificationService;
use Plugins\Crm\Services\TaskService;

final class CrmWidgetHelper
{
    public static function getDashboardWidgetsData(): array
    {
        $taskService = new TaskService();
        $financeService = new FinanceService();
        $notificationService = new NotificationService();
        $contactService = new ContactService();

        // 1. Tasks Widget (Pending & Overdue)
        $tasks = $taskService->getAllTasks(['status' => 'pending']);
        $upcomingTasks = array_slice($tasks, 0, 5);

        // 2. Finance Widget Summary
        $financeSummary = $financeService->getFinancialSummary();
        $recentInvoices = array_slice($financeService->getAllInvoices(), 0, 5);

        // 3. Unread Notifications & Activities Widget
        $unreadNotifications = $notificationService->getUnreadNotifications(null, 5);
        $recentContacts = array_slice($contactService->getAllContacts(), 0, 5);

        return [
            'upcoming_tasks' => $upcomingTasks,
            'finance_summary' => $financeSummary,
            'recent_invoices' => $recentInvoices,
            'unread_notifications' => $unreadNotifications,
            'recent_contacts' => $recentContacts,
        ];
    }

    public static function renderNotificationBell(): string
    {
        $notificationService = new NotificationService();
        $count = $notificationService->getUnreadCount();
        $list = $notificationService->getUnreadNotifications(null, 6);

        $badgeHtml = $count > 0 ? '<span class="crm-notif-badge">' . $count . '</span>' : '';

        $itemsHtml = '';
        foreach ($list as $notif) {
            $link = !empty($notif['link']) ? $notif['link'] : '#';
            if ($link !== '#' && !str_starts_with($link, 'http')) {
                $basePath = rtrim((string) (\App\Core\Config::get('app.base_path') ?? ''), '/');
                $link = $basePath . '/' . ltrim($link, '/');
            }
            $itemsHtml .= sprintf(
                '<a href="%s" class="crm-notif-item" onclick="crmMarkNotifRead(%d, this)">' .
                '<div class="crm-notif-item-title">%s</div>' .
                '<div class="crm-notif-item-msg">%s</div>' .
                '<div class="crm-notif-item-time">%s</div>' .
                '</a>',
                htmlspecialchars($link, ENT_QUOTES, 'UTF-8'),
                (int) $notif['id'],
                htmlspecialchars((string) $notif['title'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) $notif['message'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) $notif['created_at'], ENT_QUOTES, 'UTF-8')
            );
        }

        if ($itemsHtml === '') {
            $itemsHtml = '<div class="crm-notif-empty">Yeni bildirim bulunmuyor.</div>';
        }

        return '
        <div class="crm-notif-dropdown" id="crm-notif-dropdown">
            <button type="button" class="crm-notif-bell-btn" onclick="crmToggleNotifMenu(event)">
                🔔 ' . $badgeHtml . '
            </button>
            <div class="crm-notif-menu" id="crm-notif-menu" style="display:none;">
                <div class="crm-notif-header">
                    <strong>Sistem Bildirimleri</strong>
                    <button type="button" class="btn btn-small btn-ghost" onclick="crmMarkAllNotifsRead(event)">Tümünü Oku</button>
                </div>
                <div class="crm-notif-body">
                    ' . $itemsHtml . '
                </div>
            </div>
        </div>
        ';
    }
}
