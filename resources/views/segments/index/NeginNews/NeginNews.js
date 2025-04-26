document.addEventListener('DOMContentLoaded',function () {
   document.querySelectorAll('.NeginNews li').forEach(function (el) {
       if (el.innerHTML.indexOf('--') !== -1){
           el.innerHTML = el.innerHTML.split('--').join('<span class="nsp"></span>')
       }
   })
});
