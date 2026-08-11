import bootstrap from 'bootstrap/dist/js/bootstrap.bundle.min.js';

function getReceiptModal() {
    return document.getElementById('avisa-receipt-modal');
}

function getReceiptModalInstance(modal) {
    return bootstrap.Modal.getOrCreateInstance(modal, {
        backdrop: true,
        keyboard: true,
        focus: true,
    });
}

function closeReceiptModal() {
    const modal = getReceiptModal();
    if (!modal) {
        return;
    }

    const instance = bootstrap.Modal.getInstance(modal);
    if (instance) {
        instance.hide();
        return;
    }

    modal.classList.remove('show');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
    document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-receipt-uploader]').forEach((form) => {
        const input = form.querySelector('[data-receipt-input]');
        const dropzone = form.querySelector('[data-receipt-dropzone]');
        const list = form.querySelector('[data-receipt-file-list]');
        const submit = form.querySelector('[data-receipt-submit]');
        if (!input || !dropzone || !list || !submit) {
            return;
        }

        const renderFiles = () => {
            const files = Array.from(input.files || []);
            list.innerHTML = '';
            if (!files.length) {
                list.hidden = true;
                submit.disabled = true;
                return;
            }

            list.hidden = false;
            submit.disabled = false;

            files.forEach((file, index) => {
                const item = document.createElement('li');
                item.className = 'receipt-file-list__item';

                const isImage = /^image\//.test(file.type);
                const thumb = document.createElement(isImage ? 'img' : 'span');
                thumb.className = 'receipt-file-list__thumb';
                if (isImage) {
                    thumb.src = URL.createObjectURL(file);
                    thumb.alt = file.name;
                } else {
                    thumb.innerHTML = '<i class="ri-file-pdf-2-line"></i>';
                }

                const meta = document.createElement('div');
                meta.className = 'receipt-file-list__meta';
                meta.innerHTML = `<strong>${file.name}</strong><small>${(file.size / 1024).toFixed(1)} KB</small>`;

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'receipt-file-list__remove';
                remove.setAttribute('aria-label', 'Remove');
                remove.innerHTML = '<i class="ri-close-line"></i>';
                remove.addEventListener('click', () => {
                    const dt = new DataTransfer();
                    files.forEach((f, i) => {
                        if (i !== index) {
                            dt.items.add(f);
                        }
                    });
                    input.files = dt.files;
                    renderFiles();
                });

                item.append(thumb, meta, remove);
                list.appendChild(item);
            });
        };

        input.addEventListener('change', renderFiles);

        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', (event) => {
            const files = event.dataTransfer?.files;
            if (!files?.length) {
                return;
            }
            const dt = new DataTransfer();
            Array.from(input.files || []).forEach((file) => dt.items.add(file));
            Array.from(files).forEach((file) => dt.items.add(file));
            input.files = dt.files;
            renderFiles();
        });
    });

    const modal = getReceiptModal();
    if (modal) {
        modal.querySelectorAll('[data-bs-dismiss="modal"], [data-receipt-modal-close]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                closeReceiptModal();
            });
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeReceiptModal();
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        const openModal = getReceiptModal();
        if (openModal?.classList.contains('show')) {
            closeReceiptModal();
        }
    });

    document.querySelectorAll('[data-receipt-modal-open]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            const receiptModal = getReceiptModal();
            const form = receiptModal?.querySelector('[data-receipt-uploader]');
            if (!receiptModal || !form) {
                return;
            }
            form.action = button.getAttribute('data-upload-url') || form.action;
            const title = receiptModal.querySelector('[data-receipt-modal-title]');
            if (title) {
                title.textContent = button.getAttribute('data-invoice-label') || '';
            }
            getReceiptModalInstance(receiptModal).show();
        });
    });
});
