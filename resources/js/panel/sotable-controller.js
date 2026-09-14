import Sortable from 'sortablejs';

export function initSortableController() {
    const sortControl = document.querySelector('#sort-control');
    if (!sortControl) return;

    const sortableRoot = sortControl.querySelector(':scope > .ol-sortable');
    const sortDataInput = document.querySelector('#sort-data');
    if (!sortableRoot || !sortDataInput) return;

    function serializeNode(ol, serialized, parentId = null) {
        Array.from(ol.children).forEach((li) => {
            const id = li.getAttribute('data-id');
            const item = { id: id, children: [] };
            if (parentId) item.parentId = parentId;
            serialized.push(item);

            const nestedOl = li.querySelector(':scope > ol');
            if (nestedOl) {
                serializeNode(nestedOl, serialized, id);
            }
        });
    }

    function serializeList() {
        const serialized = [];
        serializeNode(sortableRoot, serialized);
        sortDataInput.value = JSON.stringify(serialized);
    }

    // Initialize all nested sortable lists exactly once
    const allSortables = sortControl.querySelectorAll('.ol-sortable');
    allSortables.forEach((el) => {
        new Sortable(el, {
            group: 'nested',
            animation: 150,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            onEnd: () => serializeList(),
        });
    });

    serializeList();

    document.querySelector('#save-sort')?.addEventListener('click', async function () {
        const url = this.getAttribute('data-link');
        if (!url || !sortDataInput.value) return;

        try {
            const data = JSON.parse(sortDataInput.value);
            const resp = await axios.post(url, { items: data });
            if (resp.data.OK) {
                window.$toast?.info(resp.data.message);
            } else {
                window.$toast?.error(resp.data.error || 'Failed to save');
            }
        } catch (err) {
            window.$toast?.error(err.message || 'Error saving sort order');
        }
    });
}
