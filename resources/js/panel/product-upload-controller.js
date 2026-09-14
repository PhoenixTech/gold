// Scoped product form submission & media upload controller

export function initProductUpload() {
    const productForm = document.querySelector('.product-form');
    const uploadingImages = document.querySelector('#uploading-images');
    const uploadDragDrop = document.querySelector('#upload-drag-drop');
    const uploadImageSelect = document.querySelector('#upload-image-select');
    const indexImage = document.querySelector('#index-image');

    if (!productForm && !uploadingImages && !uploadDragDrop) return;

    let isSubmitting = false;
    const uploadFiles = [];

    function previewImage(file, index) {
        try {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = function (e) {
                if (!uploadingImages) return;
                const imgUrl = e.target.result;
                const newDiv = document.createElement('div');
                newDiv.dataset.id = index;
                newDiv.className = 'col-xl-3 col-md-4 col-sm-6 mb-3 image-index';
                newDiv.innerHTML = `
                    <div class="card h-100 shadow-sm border rounded-3 overflow-hidden position-relative product-media-card">
                        <span class="badge bg-success position-absolute top-0 start-0 m-2 shadow-sm" style="z-index: 2;">
                            <i class="ri-add-line me-1"></i>New
                        </span>
                        <button type="button" class="btn btn-danger upload-remove-image position-absolute top-0 end-0 m-2 shadow-sm rounded-circle d-flex align-items-center justify-content-center p-0" style="width: 30px; height: 30px; z-index: 2;" title="Remove">
                            <i class="ri-delete-bin-line fs-14"></i>
                        </button>
                        <div class="ratio ratio-1x1 bg-light">
                            <div class="img-preview w-100 h-100" style="background-image: url('${imgUrl}'); background-size: cover; background-position: center;"></div>
                        </div>
                        <div class="card-footer bg-white border-top py-2 px-2.5 text-center">
                            <small class="text-muted fs-11">${file.name ? (file.name.length > 20 ? file.name.substring(0, 18) + '...' : file.name) : ''}</small>
                        </div>
                    </div>
                `;
                uploadingImages.appendChild(newDiv);
            };
        } catch (err) {
            console.error('Error previewing product image:', err);
        }
    }

    // Form submit handler
    productForm?.addEventListener('submit', function (e) {
        e.preventDefault();
        if (isSubmitting || window.noSubmit) return false;

        const formData = new FormData(this);
        uploadFiles.forEach((file) => {
            if (file && file.size !== undefined) {
                formData.append('image[]', file);
            }
        });

        const submitButtons = document.querySelectorAll("[type='submit']");
        submitButtons.forEach((btn) => {
            btn.disabled = true;
            btn.classList.add('w8');
        });

        isSubmitting = true;
        const url = this.getAttribute('action');

        axios({
            method: 'post',
            url: url,
            data: formData,
            headers: { 'Content-Type': 'multipart/form-data' }
        }).then((res) => {
            submitButtons.forEach((btn) => {
                btn.disabled = false;
                btn.classList.remove('w8');
            });
            isSubmitting = false;

            if (res.data.OK) {
                if (res.data.url !== undefined) {
                    window.location.href = res.data.url;
                } else {
                    if (res.data.link !== undefined) {
                        this.setAttribute('action', res.data.link);
                    }
                    const editBase = window.currentEditLink || '';
                    const updateBase = window.currentUpdateLink || '';
                    if (res.data.data?.slug) {
                        window.redirect = editBase + res.data.data.slug;
                        this.setAttribute('action', updateBase + res.data.data.slug);
                    }
                    window.$toast?.info(res.data.message);
                    window.store?.dispatch('updateQuantities', res.data.data?.qidz);
                }
            }
        }).catch((error) => {
            document.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
            submitButtons.forEach((btn) => {
                btn.disabled = false;
                btn.classList.remove('w8');
            });
            isSubmitting = false;

            if (error.response?.data?.errors) {
                for (const field in error.response.data.errors) {
                    document.getElementById(field)?.classList.add('is-invalid');
                    for (const err of error.response.data.errors[field]) {
                        window.$toast?.error(err);
                    }
                }
            }
            window.$toast?.error('Error: ' + (error.response?.status || 'network error'));
        });
    });

    // Image double-click index selection
    uploadingImages?.addEventListener('dblclick', (e) => {
        const imageIndex = e.target.closest('.image-index');
        if (imageIndex && indexImage) {
            document.querySelectorAll('.indexed').forEach((el) => el.classList.remove('indexed'));
            imageIndex.classList.add('indexed');
            indexImage.value = imageIndex.dataset.key || '';
        }
    });

    // Drag-and-drop & file picker triggers
    uploadDragDrop?.addEventListener('click', () => {
        uploadImageSelect?.click();
    });

    uploadImageSelect?.addEventListener('change', () => {
        if (!uploadImageSelect.files) return;
        for (const file of uploadImageSelect.files) {
            uploadFiles.push(file);
            previewImage(file, uploadFiles.length);
        }
    });

    // Remove staged image
    document.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('.upload-remove-image');
        if (!removeBtn) return;
        const parentCol = removeBtn.closest('.image-index');
        if (!parentCol) return;
        const dataId = parseInt(parentCol.dataset.id, 10);
        if (!isNaN(dataId) && uploadFiles[dataId - 1]) {
            delete uploadFiles[dataId - 1];
        }
        parentCol.style.transition = 'opacity 300ms';
        parentCol.style.opacity = '0';
        setTimeout(() => parentCol.remove(), 300);
    });

    // Drag events
    if (uploadDragDrop) {
        ['dragenter', 'dragover'].forEach((ev) => {
            uploadDragDrop.addEventListener(ev, (e) => {
                e.preventDefault();
                e.stopPropagation();
                uploadDragDrop.classList.add('active');
            });
        });

        ['dragleave', 'dragend'].forEach((ev) => {
            uploadDragDrop.addEventListener(ev, (e) => {
                e.preventDefault();
                e.stopPropagation();
                uploadDragDrop.classList.remove('active');
            });
        });

        uploadDragDrop.addEventListener('drop', (e) => {
            uploadDragDrop.classList.remove('active');
            if (e.dataTransfer?.files?.length) {
                e.preventDefault();
                e.stopPropagation();
                for (const file of e.dataTransfer.files) {
                    uploadFiles.push(file);
                    previewImage(file, uploadFiles.length);
                }
            }
        });
    }
}
