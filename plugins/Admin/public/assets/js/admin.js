(function () {
    'use strict';

    var forms = document.querySelectorAll('form.js-confirm');

    Array.prototype.forEach.call(forms, function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm('Bu kaydı silmek istediğinize emin misiniz?')) {
                event.preventDefault();
            }
        });
    });
})();

function crmToggleNotifMenu(event) {
    event.stopPropagation();
    var menu = document.getElementById('crm-notif-menu');
    if (menu) {
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }
}

function crmMarkNotifRead(id, el) {
    fetch('/groo/admin/crm/notifications/mark-read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    }).catch(function(e) {});
}

function crmMarkAllNotifsRead(event) {
    if (event) event.stopPropagation();
    fetch('/groo/admin/crm/notifications/mark-all-read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    }).then(function() {
        var badge = document.querySelector('.crm-notif-badge');
        if (badge) badge.style.display = 'none';
        var body = document.querySelector('.crm-notif-body');
        if (body) body.innerHTML = '<div class="crm-notif-empty">Yeni bildirim bulunmuyor.</div>';
    }).catch(function(e) {});
}

document.addEventListener('click', function(event) {
    var dropdown = document.getElementById('crm-notif-dropdown');
    var menu = document.getElementById('crm-notif-menu');
    if (dropdown && menu && !dropdown.contains(event.target)) {
        menu.style.display = 'none';
    }
});

document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('mobile-menu-btn');
    var sidebar = document.querySelector('.admin-sidebar');
    var overlay = document.getElementById('sidebar-overlay');

    if (btn && sidebar && overlay) {
        btn.addEventListener('click', function () {
            sidebar.classList.add('open');
            overlay.classList.add('open');
        });

        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        });
    }
});
