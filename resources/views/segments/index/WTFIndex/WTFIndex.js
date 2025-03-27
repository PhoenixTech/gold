document.addEventListener('DOMContentLoaded',function () {
   document.querySelectorAll('#wtf-main-btns .col')?.forEach(function (el) {
     el.addEventListener('click',function () {
         document.querySelectorAll('.wtf-section').forEach(function (el2) {
             el2.style.display = 'none';
         });
         document.querySelector(this.getAttribute('data-id')).style.display = 'block';
     });
   })
});
