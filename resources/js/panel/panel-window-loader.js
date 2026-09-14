export function initPanelPreloader() {
    const preloader = document.querySelector('#panel-preloader');
    if (!preloader) return;

    let isDismissed = false;
    const dismissPreloader = () => {
        if (isDismissed) return;
        isDismissed = true;
        preloader.style.height = '0';
        setTimeout(() => {
            preloader.style.display = 'none';
        }, 500);
    };

    window.addEventListener('load', dismissPreloader);
    setTimeout(dismissPreloader, 5000);
}
