// Delegated bulk actions and table checkbox controller

function syncMainFormAction(form) {
    const activeSelect = document.querySelector('[data-bulk-action]');
    if (!activeSelect) return;

    let hiddenInput = form.querySelector('input[name="action"]');
    if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'action';
        form.appendChild(hiddenInput);
    }
    hiddenInput.value = activeSelect.value || '';
}

function updateBulkState(form) {
    const checkedBoxes = form.querySelectorAll('.chkbox:checked');
    const count = checkedBoxes.length;
    const countEls = document.querySelectorAll('[data-bulk-count]');
    const runBtns = document.querySelectorAll('[data-bulk-run]');
    const activeSelect = document.querySelector('[data-bulk-action]');
    const hasAction = Boolean(activeSelect && activeSelect.value !== '');

    countEls.forEach((countEl) => {
        if (count > 0) {
            countEl.textContent = `(${count})`;
            countEl.classList.remove('d-none');
        } else {
            countEl.classList.add('d-none');
        }
    });

    runBtns.forEach((runBtn) => {
        runBtn.disabled = count === 0 || !hasAction;
    });

    syncMainFormAction(form);
}

export function initBulkActions(formSelector = '#main-form') {
    const form = document.querySelector(formSelector);
    if (!form) return;

    let lastChecked = null;

    // Single delegated change listener for select-all and item check
    form.addEventListener('change', (e) => {
        const target = e.target;

        // Select All switch
        if (target.matches('.chkall')) {
            const table = target.closest('table') || form;
            const checkboxes = table.querySelectorAll('.chkbox');
            checkboxes.forEach((cb) => {
                cb.checked = target.checked;
            });
            updateBulkState(form);
            return;
        }

        // Single checkbox item changed
        if (target.matches('.chkbox')) {
            // Keep master switch in sync if all/none checked
            const table = target.closest('table') || form;
            const chkall = table.querySelector('.chkall');
            if (chkall) {
                const allBoxes = table.querySelectorAll('.chkbox');
                const checkedBoxes = table.querySelectorAll('.chkbox:checked');
                chkall.checked = allBoxes.length > 0 && allBoxes.length === checkedBoxes.length;
            }
            updateBulkState(form);
        }
    });

    // Single delegated click listener for Shift+Click range selection
    form.addEventListener('click', (e) => {
        const cb = e.target.closest('.chkbox');
        if (!cb) return;

        if (e.shiftKey && lastChecked && lastChecked !== cb) {
            const allBoxes = Array.from(form.querySelectorAll('.chkbox'));
            const start = allBoxes.indexOf(cb);
            const end = allBoxes.indexOf(lastChecked);

            if (start !== -1 && end !== -1) {
                const [min, max] = start < end ? [start, end] : [end, start];
                for (let i = min; i <= max; i++) {
                    allBoxes[i].checked = lastChecked.checked;
                }
            }
        }

        lastChecked = cb;
        updateBulkState(form);
    });

    // Invert selection button support
    const toggleBtn = document.querySelector('#toggle-select');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const checkboxes = form.querySelectorAll('.chkbox');
            checkboxes.forEach((cb) => {
                cb.checked = !cb.checked;
            });
            updateBulkState(form);
        });
    }

    // Synchronize bulk action dropdowns
    const selects = document.querySelectorAll('[data-bulk-action]');
    selects.forEach((sel) => {
        sel.addEventListener('change', () => {
            selects.forEach((other) => {
                if (other !== sel) other.value = sel.value;
            });
            updateBulkState(form);
        });
    });

    form.addEventListener('submit', () => {
        syncMainFormAction(form);
    });

    // Initial state calculation
    updateBulkState(form);
}
