const root = document.documentElement;
const sidebar = document.querySelector('[data-sidebar]');
const backdrop = document.querySelector('[data-sidebar-backdrop]');

const setTheme = (theme) => {
    root.classList.toggle('dark', theme === 'dark');
    root.dataset.theme = 'oryxops';
    localStorage.setItem('oryxpace-theme', theme);
};

setTheme(localStorage.getItem('oryxpace-theme') || 'light');

document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
    setTheme(root.classList.contains('dark') ? 'light' : 'dark');
});

document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', () => {
    sidebar?.classList.toggle('is-collapsed');
    localStorage.setItem('oryxpace-sidebar-collapsed', String(sidebar?.classList.contains('is-collapsed')));
});

if (localStorage.getItem('oryxpace-sidebar-collapsed') === 'true') sidebar?.classList.add('is-collapsed');

const closeMobileMenu = () => {
    sidebar?.classList.remove('is-mobile-open');
    backdrop?.classList.add('hidden');
};

document.querySelector('[data-mobile-menu]')?.addEventListener('click', () => {
    sidebar?.classList.add('is-mobile-open');
    backdrop?.classList.remove('hidden');
});
backdrop?.addEventListener('click', closeMobileMenu);
sidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMobileMenu));

const dismissToast = (toast) => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(8px)';
    toast.style.transition = 'opacity .2s ease, transform .2s ease';
    window.setTimeout(() => toast.remove(), 220);
};

const activateToasts = (container = document) => {
    container.querySelectorAll?.('[data-toast]').forEach((toast) => {
        if (toast.dataset.toastActive) return;
        toast.dataset.toastActive = 'true';
        toast.querySelector('[data-toast-close]')?.addEventListener('click', () => dismissToast(toast));
        const timeout = Number(toast.dataset.toastTimeout || 5000);
        if (timeout > 0) window.setTimeout(() => toast.isConnected && dismissToast(toast), timeout);
    });
};

activateToasts();
new MutationObserver(() => activateToasts()).observe(document.body, { childList: true, subtree: true });

document.addEventListener('click', (event) => {
    const openButton = event.target.closest('[data-modal-open]');
    const closeButton = event.target.closest('[data-modal-close]');

    if (openButton) {
        const modal = document.getElementById(openButton.dataset.modalOpen);
        if (modal && typeof modal.showModal === 'function') modal.showModal();
    }

    if (closeButton && !closeButton.hasAttribute('wire:click')) {
        closeButton.closest('dialog')?.close();
    }
});
