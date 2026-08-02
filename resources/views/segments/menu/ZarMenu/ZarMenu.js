const openZarMenu = function () {
    document.querySelector('#zar-menu').style.display = 'block';
    setTimeout(function () {
        document.addEventListener('click', handleDocumentClickZar);
    },100);
};
const handleDocumentClickZar = function (e) {
    const respMenu = document.querySelector('#zar-menu ul');
    if (!respMenu.contains(e.target)) {
        document.querySelector('#zar-menu').style.display = 'none';
        document.removeEventListener('click', handleDocumentClickZar);
    }
}


document.addEventListener('DOMContentLoaded',function () {
    const openZar1 = document.querySelector('#open-zar-1');
    const openZar2 = document.querySelector('#open-zar-2');
    if (openZar1) openZar1.addEventListener('click',openZarMenu);
    if (openZar2) openZar2.addEventListener('click',openZarMenu);
});

