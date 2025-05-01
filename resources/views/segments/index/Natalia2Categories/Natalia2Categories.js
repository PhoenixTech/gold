document.addEventListener('DOMContentLoaded',function () {
    document.querySelectorAll('.Natalia2Categories ol li').forEach(function (el) {
        if (el.innerHTML.indexOf('--') !== -1){
            el.innerHTML = el.innerHTML.split('--').join('<span class="nsp"></span>')
        }
    })
});
