(() => {
  'use strict';

  const $ = (selector, scope = document) => scope.querySelector(selector);
  const $$ = (selector, scope = document) => [...scope.querySelectorAll(selector)];

  function updateCountdown() {
    const root = $('[data-wedding-date]');
    if (!root) return;
    const distance = Math.max(0, new Date(root.dataset.weddingDate).getTime() - Date.now());
    const values = {
      days: Math.floor(distance / 86400000),
      hours: Math.floor((distance % 86400000) / 3600000),
      minutes: Math.floor((distance % 3600000) / 60000),
      seconds: Math.floor((distance % 60000) / 1000)
    };
    Object.entries(values).forEach(([name, value]) => {
      const target = $(`[data-countdown="${name}"]`);
      if (target) target.textContent = String(value).padStart(2, '0');
    });
  }

  const menuButton = $('[data-menu-toggle]');
  const menu = $('[data-site-nav]');
  if (menuButton && menu) {
    menuButton.addEventListener('click', () => {
      const open = menuButton.getAttribute('aria-expanded') !== 'true';
      menuButton.setAttribute('aria-expanded', String(open));
      menu.classList.toggle('open', open);
    });
    $$('a', menu).forEach((link) => link.addEventListener('click', () => {
      menuButton.setAttribute('aria-expanded', 'false');
      menu.classList.remove('open');
    }));
  }

  const topbar = $('[data-topbar]');
  if (topbar) addEventListener('scroll', () => topbar.classList.toggle('scrolled', scrollY > 4), { passive: true });

  $$('[data-copy-address]').forEach((button) => button.addEventListener('click', async () => {
    const original = button.textContent;
    try {
      await navigator.clipboard.writeText(button.dataset.copyAddress || '');
      button.textContent = 'Endereço copiado';
    } catch {
      button.textContent = button.dataset.copyAddress || original;
    }
    setTimeout(() => { button.textContent = original; }, 2500);
  }));

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    }), { threshold: 0.12 });
    $$('.reveal').forEach((node) => observer.observe(node));
  } else {
    $$('.reveal').forEach((node) => node.classList.add('visible'));
  }

  updateCountdown();
  setInterval(updateCountdown, 1000);
})();
