export function initFastEdit() {
    const modal = document.querySelector('#iframe-modal');
    const iframe = modal?.querySelector('iframe');
    const editCatBtns = document.querySelectorAll('.edit-category-btn');
    const editGroupBtns = document.querySelectorAll('.edit-group-btn');
    const saveBtn = document.querySelector('#categories-save-btn');

    if (!modal && editCatBtns.length === 0 && editGroupBtns.length === 0 && !saveBtn) return;

    function openModalWithUrl(url) {
        if (!modal || !iframe || !url) return;
        iframe.setAttribute('src', url);
        modal.style.display = 'block';
    }

    editCatBtns.forEach((btn) => {
        btn.setAttribute('href', '#edit-category');
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.closest('tr')?.querySelector('input.chkbox')?.value;
            const baseUrl = document.querySelector('#category-edit-url')?.value;
            if (id && baseUrl) openModalWithUrl(baseUrl + id);
        });
    });

    editGroupBtns.forEach((btn) => {
        btn.setAttribute('href', '#group-category');
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.closest('tr')?.querySelector('input.chkbox')?.value;
            const baseUrl = document.querySelector('#group-edit-url')?.value;
            if (id && baseUrl) openModalWithUrl(baseUrl + id);
        });
    });

    modal?.addEventListener('click', function (e) {
        if (e.target === this) this.style.display = 'none';
    });

    saveBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        const syncForm = document.querySelector('#ajax-sync-form');
        const url = syncForm?.getAttribute('action');
        if (!url) return;

        const formData = new FormData();
        document.querySelectorAll('input.chkbox:checked').forEach((cb) => {
            formData.append('cat[]', cb.value);
        });

        axios.post(url, formData)
            .then((resp) => {
                if (resp.data.OK) {
                    window.$toast?.success(resp.data.message);
                } else {
                    window.$toast?.error(resp.data.error || 'Sync failed');
                }
            })
            .catch((err) => {
                window.$toast?.error(err.message || 'Error syncing categories');
            });
    });
}
