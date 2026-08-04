function clearSelection() {
    if (window.getSelection) {
        window.getSelection().removeAllRanges();
    } else if (document.selection) {
        document.selection.empty();
    }
}


function handleCheckChange() {
    let table = document.querySelector('#main-form table');

    if (table == null) {
        return;
    }

    let count = table.querySelectorAll('.chkbox:checked').length;
    let countEls = document.querySelectorAll('[data-bulk-count]');
    let runBtns = document.querySelectorAll('[data-bulk-run]');

    countEls.forEach(function (countEl) {
        if (count > 0) {
            countEl.textContent = '(' + count + ')';
            countEl.classList.remove('d-none');
        } else {
            countEl.classList.add('d-none');
        }
    });

    runBtns.forEach(function (runBtn) {
        runBtn.disabled = count === 0;
    });
}

function syncBulkActions() {
    let selects = document.querySelectorAll('[data-bulk-action]');
    selects.forEach(function (sel) {
        sel.addEventListener('change', function () {
            selects.forEach(function (other) {
                if (other !== sel) {
                    other.value = sel.value;
                }
            });
        });
    });
}

window.addEventListener('load', function () {
    let chkall = document.querySelectorAll(".chkall");

    if (chkall.length == 0) {
        return false;
    }
    syncBulkActions();
    let toggle = document.querySelector('#toggle-select');
    if (toggle != null) {
        toggle?.addEventListener('click', function () {
            let checkboxes = document.querySelectorAll(".chkbox");
            checkboxes.forEach(function (checkbox) {
                if (!checkbox.checked) {
                    checkbox.checked = true;
                    checkbox.setAttribute("checked", "");
                } else {
                    checkbox.checked = false;
                    checkbox.removeAttribute("checked");
                }
            });
            handleCheckChange();

        });
    }
// Attach an event listener for "change" and "click" events
    chkall.forEach(function (chkall) {
        chkall.addEventListener("change", handleCheckboxChange);
        chkall.addEventListener("click", handleCheckboxChange);
    });

    function handleCheckboxChange() {
        let isChecked = this.checked;
        let table = this.closest("table");

        if (isChecked) {
            // Check all checkboxes in the table
            let checkboxes = table.querySelectorAll(".chkbox");
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = true;
                checkbox.setAttribute("checked", "");
            });
        } else {
            // Uncheck all checkboxes in the table
            let checkboxes = table.querySelectorAll(".chkbox");
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = false;
                checkbox.removeAttribute("checked");
            });
        }
        handleCheckChange();
    }


    // select with shift button
    const chkboxes = document.querySelectorAll('.chkbox');
    let lastChecked = null;

    chkboxes.forEach(chkbox => {
        chkbox.addEventListener('click', handleCheckboxClick);
        let label = chkbox.parentNode ? chkbox.parentNode.querySelector('label') : null;
        if (label) {
            label.addEventListener('click', handleCheckboxClick);
        }
        chkbox.addEventListener('change', handleCheckChange);
    });

    function handleCheckboxClick(e) {
        clearSelection();

        let self = this;
        if (e.target.tagName === 'LABEL') {
            self = e.target.parentNode.querySelector('input');
        }
        if (!lastChecked) {
            lastChecked = self;
            return;
        }

        if (e.shiftKey) {
            const start = Array.from(chkboxes).indexOf(self);
            const end = Array.from(chkboxes).indexOf(lastChecked);
            const range = Array.from(chkboxes).slice(Math.min(start, end) + 1, Math.max(start, end));

            range.forEach(chkbox => {
                chkbox.checked = lastChecked.checked;
            });

        }

        handleCheckChange();
        lastChecked = self;

    }

    handleCheckChange();
});

