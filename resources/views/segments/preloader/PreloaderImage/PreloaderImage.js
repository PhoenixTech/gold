let isHidePreloader = false;
const hidePreloader= function (){
    if (!isHidePreloader){
        const el = document.querySelector('#website-preloader');
        if (el) {
            el.style.opacity = 0;
            setTimeout(()=>{
                if (el.parentNode) el.remove();
            },510);
        }
        isHidePreloader = true;
    }
};

window.addEventListener('load',function () {
    hidePreloader();
});

// if field and didn't load after 10s
setTimeout(()=>{
    hidePreloader();
},10000);



