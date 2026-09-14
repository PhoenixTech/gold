export function initStepController() {
    const stepsContainer = document.querySelector('.steps');
    if (!stepsContainer) return;

    const steps = stepsContainer.querySelectorAll('li');
    const stepTabs = document.querySelectorAll('#step-tabs > div');
    const nextButtons = document.querySelectorAll('.step-next');
    const prevButtons = document.querySelectorAll('.step-prev');
    if (steps.length === 0) return;

    let currentStep = 0;

    function updateProgress(stepIndex) {
        const progress = ((stepIndex + 1) / steps.length) * 100;
        stepsContainer.style.setProperty('--progress', `${progress}%`);
    }

    function showStep(stepIndex) {
        const isLabeledTabs = stepsContainer.classList.contains('product-form-tabs');
        steps.forEach((step, index) => {
            const shouldActivate = isLabeledTabs ? index === stepIndex : index <= stepIndex;
            step.classList.toggle('active', shouldActivate);
        });

        stepTabs.forEach((tab, index) => {
            if (index === stepIndex) {
                tab.classList.add('active');
                setTimeout(() => { tab.style.opacity = '1'; }, 0);
            } else {
                tab.style.opacity = '0';
                setTimeout(() => { tab.classList.remove('active'); }, 0);
            }
        });

        updateProgress(stepIndex);
        currentStep = stepIndex;
    }

    steps.forEach((step, index) => {
        step.addEventListener('click', () => showStep(index));
    });

    nextButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (currentStep < steps.length - 1) showStep(currentStep + 1);
        });
    });

    prevButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (currentStep > 0) showStep(currentStep - 1);
        });
    });

    showStep(0);
}
