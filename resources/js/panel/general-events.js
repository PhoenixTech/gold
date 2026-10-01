export function initGeneralEvents() {
    document.querySelectorAll('.delete-confirm').forEach((el) => {
        el.addEventListener('click', (e) => {
            const confirmMsg = window.TR?.deleteConfirm || 'Are you sure you want to delete this item?';
            if (!confirm(confirmMsg)) {
                e.preventDefault();
            }
        });
    });

    // Declarative confirmation for destructive forms. Keeping the message in a
    // data attribute instead of an inline onsubmit handler keeps the localized
    // text out of the markup and lets the browser block the submit properly.
    // Guarded so re-running init() cannot stack duplicate dialogs.
    if (!window.__zhonellaConfirmSubmitBound) {
        window.__zhonellaConfirmSubmitBound = true;
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            const message = form.getAttribute('data-confirm');
            if (message && !confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    }

    document.querySelectorAll('[data-open-file]').forEach((el) => {
        el.addEventListener('click', function () {
            const selector = this.getAttribute('data-open-file');
            if (selector) {
                document.querySelector(selector)?.click();
            }
        });
    });
}
