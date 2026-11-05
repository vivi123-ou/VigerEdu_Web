// Trang quản trị: mở/đóng menu trên điện thoại, nút "?" hướng dẫn, đóng menu thả xuống khi bấm ra ngoài.
(function () {
    'use strict';

    var sidebar = document.getElementById('sidebar');
    var burger = document.querySelector('.topbar__burger');

    document.querySelectorAll('[data-toggle-sidebar]').forEach(function (el) {
        el.addEventListener('click', function () {
            if (!sidebar) return;
            var open = sidebar.classList.toggle('is-open');
            if (burger) burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && sidebar && sidebar.classList.contains('is-open')) {
            sidebar.classList.remove('is-open');
            if (burger) burger.setAttribute('aria-expanded', 'false');
        }
    });

    var helpButton = document.querySelector('[data-open-help]');
    var helpDialog = document.getElementById('help-dialog');
    if (helpButton && helpDialog && typeof helpDialog.showModal === 'function') {
        helpButton.addEventListener('click', function () { helpDialog.showModal(); });
    }

    document.addEventListener('click', function (event) {
        document.querySelectorAll('details.dropdown[open]').forEach(function (details) {
            if (!details.contains(event.target)) details.removeAttribute('open');
        });
    });
})();
