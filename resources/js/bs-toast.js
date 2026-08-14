import { Toast } from 'bootstrap';

class BootstrapToastManager {
    constructor() {
        this.containerId = 'bs-global-toast-container';
    }

    getContainer() {
        let container = document.getElementById(this.containerId);
        if (!container) {
            container = document.createElement('div');
            container.id = this.containerId;
            container.className = 'toast-container position-fixed top-0 start-0 p-3';
            container.style.zIndex = '10090';
            container.style.direction = 'rtl';
            document.body.appendChild(container);
        }
        return container;
    }

    show(message, type = 'success', duration = 3500) {
        if (!message) return;
        const container = this.getContainer();

        const typeConfigs = {
            success: {
                className: 'toast-success',
                icon: 'ri-check-line',
                badgeBg: 'var(--xshop-primary, #db9a00)',
                accentBg: 'linear-gradient(90deg, var(--xshop-primary, #db9a00), #f59e0b, #fbbf24)'
            },
            error: {
                className: 'toast-error',
                icon: 'ri-close-line',
                badgeBg: '#ef4444',
                accentBg: 'linear-gradient(90deg, #ef4444, #f87171)'
            },
            warning: {
                className: 'toast-warning',
                icon: 'ri-alert-line',
                badgeBg: '#f59e0b',
                accentBg: 'linear-gradient(90deg, #f59e0b, #fbbf24)'
            },
            info: {
                className: 'toast-info',
                icon: 'ri-information-line',
                badgeBg: '#3b82f6',
                accentBg: 'linear-gradient(90deg, #3b82f6, #60a5fa)'
            }
        };

        const config = typeConfigs[type] || typeConfigs.info;

        const toastEl = document.createElement('div');
        toastEl.className = `toast bs-modern-toast ${config.className} border-0 shadow-lg rounded-4 overflow-hidden mb-2.5`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.style.direction = 'rtl';
        toastEl.style.backgroundColor = 'rgba(255, 255, 255, 0.98)';
        toastEl.style.backdropFilter = 'blur(16px)';
        toastEl.style.minWidth = '300px';
        toastEl.style.maxWidth = '460px';

        toastEl.innerHTML = `
            <div class="toast-content d-flex align-items-center p-3">
                <div class="toast-icon-badge rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 text-white ms-2"
                     style="width: 36px; height: 36px; background-color: ${config.badgeBg}; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <i class="${config.icon} fs-18"></i>
                </div>
                <div class="toast-body flex-grow-1 fs-14 fw-semibold text-dark py-0 px-2">
                    ${message}
                </div>
                <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-bottom-accent" style="height: 3.5px; background: ${config.accentBg};"></div>
        `;

        container.appendChild(toastEl);

        const bsToast = new Toast(toastEl, {
            delay: duration,
            autohide: true
        });

        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });

        bsToast.show();
        return bsToast;
    }

    success(message, options = {}) {
        return this.show(message, 'success', options.duration || 3500);
    }

    error(message, options = {}) {
        return this.show(message, 'error', options.duration || 4000);
    }

    warning(message, options = {}) {
        return this.show(message, 'warning', options.duration || 3500);
    }

    info(message, options = {}) {
        return this.show(message, 'info', options.duration || 3500);
    }

    open(options = {}) {
        const msg = typeof options === 'string' ? options : (options.message || '');
        const type = options.type || 'info';
        const duration = options.duration || 3500;
        return this.show(msg, type, duration);
    }
}

export const bsToast = new BootstrapToastManager();
export const useToast = () => bsToast;
export const ToastPlugin = {
    install(app) {
        app.config.globalProperties.$toast = bsToast;
        app.provide('$toast', bsToast);
    }
};

export default bsToast;
