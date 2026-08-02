document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('avisa-menu-btn');
    const closeBtn = document.getElementById('avisa-close-btn');
    const sidebar = document.getElementById('avisa-sidebar');
    const backdrop = document.getElementById('avisa-backdrop');
    if (!btn || !sidebar) {
        return;
    }
    const close = function () {
        sidebar.classList.remove('open');
        if (backdrop) {
            backdrop.classList.remove('open');
        }
    };
    btn.addEventListener('click', function () {
        sidebar.classList.toggle('open');
        if (backdrop) {
            backdrop.classList.toggle('open');
        }
    });
    if (backdrop) {
        backdrop.addEventListener('click', close);
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', close);
    }
    sidebar.querySelectorAll('#avisa-tabs a').forEach(function (a) {
        a.addEventListener('click', close);
    });
});
