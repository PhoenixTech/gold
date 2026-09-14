export function initSettingSections() {
    const container = document.querySelector('#setting-sections');
    if (!container) return;

    const sectionGroupItems = document.querySelectorAll('.section-group-item');
    const sections = container.querySelectorAll('section');
    if (sectionGroupItems.length === 0 || sections.length === 0) return;

    // Hide all sections initially
    sections.forEach((s) => { s.style.display = 'none'; });

    function activateSection(targetId) {
        sections.forEach((sec) => {
            sec.style.display = sec.id === targetId ? 'block' : 'none';
        });
        sectionGroupItems.forEach((link) => {
            link.classList.toggle('active', link.getAttribute('href') === `#${targetId}`);
        });
    }

    sectionGroupItems.forEach((item) => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = (this.getAttribute('href') || '').replace(/^#/, '');
            activateSection(targetId);
        });
    });

    // Check URL hash or default to first section
    const hash = window.location.hash.replace(/^#/, '');
    if (hash && container.querySelector(`#${hash}`)) {
        activateSection(hash);
    } else if (sectionGroupItems[0]) {
        const firstTargetId = (sectionGroupItems[0].getAttribute('href') || '').replace(/^#/, '');
        activateSection(firstTargetId);
    }
}
