export function initFastAttachment() {
    const attachForm = document.querySelector('#attaching-form');
    if (!attachForm) return;

    document.querySelector('#attach-down')?.addEventListener('click', function () {
        attachForm.style.bottom = `${window.innerHeight * -0.5}px`;
    });
    document.querySelector('#show-attach-form')?.addEventListener('click', function (e) {
        e.preventDefault();
        attachForm.style.bottom = '0px';
    });
}
