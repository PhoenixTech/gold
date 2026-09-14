export function initGeneralEvents() {
    document.querySelectorAll('.delete-confirm').forEach((el) => {
        el.addEventListener('click', (e) => {
            const confirmMsg = window.TR?.deleteConfirm || 'Are you sure you want to delete this item?';
            if (!confirm(confirmMsg)) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-open-file]').forEach((el) => {
        el.addEventListener('click', function () {
            const selector = this.getAttribute('data-open-file');
            if (selector) {
                document.querySelector(selector)?.click();
            }
        });
    });
}
