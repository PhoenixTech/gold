import { Modal } from 'bootstrap';

function getReceiptModal() {
    return document.getElementById('avisa-receipt-modal');
}

function openReceiptModal(button) {
    const modal = getReceiptModal();
    if (!modal) {
        return;
    }

    const form = modal.querySelector('[data-receipt-uploader]');
    if (form && button.getAttribute('data-upload-url')) {
        form.action = button.getAttribute('data-upload-url');
    }
    const title = modal.querySelector('[data-receipt-modal-title]');
    if (title && button.getAttribute('data-invoice-label')) {
        title.textContent = button.getAttribute('data-invoice-label');
    }

    Modal.getOrCreateInstance(modal, {
        backdrop: true,
        keyboard: true,
        focus: true,
    }).show();
}

function closeReceiptModal() {
    const modal = getReceiptModal();
    if (!modal) {
        return;
    }

    Modal.getInstance(modal)?.hide();
}

function initReceiptUploaderForms() {
    document.querySelectorAll('[data-receipt-uploader]').forEach((form) => {
        if (form.dataset.receiptInitialized) {
            return;
        }
        form.dataset.receiptInitialized = 'true';

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
                remove.addEventListener('click', (e) => {
                    e.stopPropagation();
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
}

document.addEventListener('click', (event) => {
    const openButton = event.target.closest('[data-receipt-modal-open]');
    if (openButton) {
        event.preventDefault();
        openReceiptModal(openButton);
        initReceiptUploaderForms();
        return;
    }

    const closeButton = event.target.closest('[data-bs-dismiss="modal"], [data-receipt-modal-close]');
    if (closeButton && event.target.closest('#avisa-receipt-modal')) {
        event.preventDefault();
        closeReceiptModal();
        return;
    }

    if (event.target && event.target.id === 'avisa-receipt-modal') {
        closeReceiptModal();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        const openModal = getReceiptModal();
        if (openModal?.classList.contains('show')) {
            closeReceiptModal();
        }
    }
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReceiptUploaderForms);
} else {
    initReceiptUploaderForms();
}

export { openReceiptModal, closeReceiptModal, initReceiptUploaderForms };
