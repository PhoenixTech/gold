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
    document.querySelector('#open-zar-1').addEventListener('click',openZarMenu);
    document.querySelector('#open-zar-2').addEventListener('click',openZarMenu);
});

