document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#wtf-main-btns .main-dir')?.forEach(function (el) {
        el.addEventListener('click', function () {
            document.querySelectorAll('#wtf-main-btns .main-dir').forEach(function(btn) {
                btn.classList.remove('active');
            });
            this.classList.add('active');

            document.querySelectorAll('.wtf-section').forEach(function (el2) {
                el2.style.display = 'none';
            });
            var target = document.querySelector(this.getAttribute('data-id'));
            if (target) {
                target.style.display = 'block';
            }
        });
    });
});
