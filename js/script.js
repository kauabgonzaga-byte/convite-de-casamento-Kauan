(() => {
  'use strict';

  const $ = (selector, scope = document) => scope.querySelector(selector);
  const $$ = (selector, scope = document) => [...scope.querySelectorAll(selector)];
  const storage = {
    get(key, fallback) {
      try { return JSON.parse(localStorage.getItem(key)) ?? fallback; } catch { return fallback; }
    },
    set(key, value) {
      try { localStorage.setItem(key, JSON.stringify(value)); } catch { /* storage can be disabled */ }
    }
  };

  const gifts = [
    { id: 'cesto', name: 'Cesto Dobrável', price: 42.90, icon: '⌁', visible: true },
    { id: 'tapete', name: 'Tapete para o banho', price: 66.50, icon: '▧', visible: true },
    { id: 'potes', name: 'Conjunto de potes', price: 75.80, icon: '◌', visible: true },
    { id: 'escorredor', name: 'Escorredor de louças', price: 82.40, icon: '⌇', visible: true },
    { id: 'passadeira', name: 'Passadeira para cozinha', price: 95.70, icon: '▤', visible: true },
    { id: 'facas', name: 'Jogo de facas', price: 130.20, icon: '†', visible: true },
    { id: 'pipoqueira', name: 'Pipoqueira elétrica', price: 144.80, icon: '◒', visible: true },
    { id: 'fondue', name: 'Aparelho para fondue', price: 160.90, icon: '♨', visible: true },
    { id: 'chaleira', name: 'Chaleira elétrica', price: 189.00, icon: '♨', visible: true },
    { id: 'ferro', name: 'Ferro a vapor', price: 190.10, icon: '⌁', visible: false },
    { id: 'tacas', name: 'Taças para brindar', price: 217.90, icon: '♕', visible: false },
    { id: 'frigideiras', name: 'Conjunto de frigideiras', price: 226.70, icon: '◉', visible: false }
  ];
  const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
  let cart = storage.get('kd-wedding-cart', []);
  let showAllGifts = false;
  let toastTimer;

  function showToast(message) {
    const toast = $('[data-toast]');
    toast.textContent = message;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 2900);
  }

  function updateCart() {
    storage.set('kd-wedding-cart', cart);
    const count = cart.length;
    $$('[data-cart-count]').forEach((node) => { node.textContent = String(count); });
    const container = $('[data-cart-items]');
    const summary = $('[data-cart-summary]');
    container.replaceChildren();

    if (!count) {
      const empty = document.createElement('div');
      empty.className = 'empty-cart';
      empty.innerHTML = '<span aria-hidden="true">♧</span><p>Seu carrinho ainda está esperando por um presente especial.</p>';
      container.append(empty);
      summary.hidden = true;
      return;
    }

    cart.forEach((id) => {
      const gift = gifts.find((item) => item.id === id);
      if (!gift) return;
      const line = document.createElement('article');
      line.className = 'cart-line';
      line.innerHTML = `<div class="cart-line-art" aria-hidden="true">${gift.icon}</div><div><h3>${gift.name}</h3><p>${money.format(gift.price)}</p></div><button class="remove-gift" type="button" data-remove-gift="${gift.id}">Remover</button>`;
      container.append(line);
    });
    const total = cart.reduce((sum, id) => sum + (gifts.find((gift) => gift.id === id)?.price || 0), 0);
    $('[data-cart-total]').textContent = money.format(total);
    summary.hidden = false;
  }

  function renderGifts() {
    const grid = $('[data-gift-grid]');
    const sort = $('[data-gift-sort]').value;
    const rendered = [...gifts].sort((a, b) => {
      if (sort === 'asc') return a.price - b.price;
      if (sort === 'desc') return b.price - a.price;
      if (sort === 'name') return a.name.localeCompare(b.name, 'pt-BR');
      return gifts.indexOf(a) - gifts.indexOf(b);
    });
    grid.replaceChildren();
    rendered.forEach((gift) => {
      const isHidden = !showAllGifts && !gift.visible;
      const isAdded = cart.includes(gift.id);
      const card = document.createElement('article');
      card.className = 'gift-card';
      card.hidden = isHidden;
      card.innerHTML = `<div class="gift-art" aria-hidden="true"><span>${gift.icon}</span></div><div class="gift-info"><h3>${gift.name}</h3><p class="gift-price">${money.format(gift.price)}</p><button class="outline-button" type="button" data-add-gift="${gift.id}" ${isAdded ? 'disabled' : ''}>${isAdded ? 'No carrinho' : 'Presentear'}</button></div>`;
      grid.append(card);
    });
    $('[data-gift-showing]').textContent = String(showAllGifts ? gifts.length : gifts.filter((gift) => gift.visible).length);
    const loadButton = $('[data-load-more]');
    loadButton.hidden = showAllGifts;
  }

  function addGift(id) {
    if (cart.includes(id)) { showToast('Este presente já está no carrinho.'); return; }
    if (cart.length >= 3) { showToast('Você pode escolher até três presentes por vez.'); return; }
    cart.push(id);
    updateCart();
    renderGifts();
    showToast('Presente adicionado ao carrinho.');
  }

  function setDrawer(open) {
    const drawer = $('[data-cart-drawer]');
    const backdrop = $('[data-backdrop]');
    drawer.classList.toggle('open', open);
    drawer.setAttribute('aria-hidden', String(!open));
    backdrop.classList.toggle('visible', open);
    document.body.classList.toggle('drawer-open', open);
    if (open) $('[data-close-cart]').focus();
  }

  function openModal(name) {
    const modal = $(`[data-modal="${name}"]`);
    if (modal && !modal.open) modal.showModal();
  }

  function closeModal(button) {
    const dialog = button.closest('dialog');
    if (dialog) dialog.close();
  }

  function formatCountdownValue(value) { return String(Math.max(0, value)).padStart(2, '0'); }
  function updateCountdown() {
    const target = new Date('2026-11-21T18:00:00-03:00').getTime();
    const distance = Math.max(0, target - Date.now());
    const values = {
      days: Math.floor(distance / 86400000),
      hours: Math.floor((distance % 86400000) / 3600000),
      minutes: Math.floor((distance % 3600000) / 60000),
      seconds: Math.floor((distance % 60000) / 1000)
    };
    Object.entries(values).forEach(([unit, value]) => {
      const targetNode = $(`[data-countdown="${unit}"]`);
      if (targetNode) targetNode.textContent = formatCountdownValue(value);
    });
  }

  function copyAddress(address) {
    if (navigator.clipboard?.writeText) {
      navigator.clipboard.writeText(address).then(() => showToast('Endereço copiado.')).catch(() => showToast(address));
    } else {
      showToast(address);
    }
  }

  function renderMessages() {
    const list = $('[data-message-list]');
    const defaults = [
      { name: 'Ana e Lucas', message: 'Que este novo capítulo seja leve, cheio de alegria e muitos brindes!' },
      { name: 'Família', message: 'Estamos muito felizes por celebrar esta data tão especial com vocês.' }
    ];
    const messages = [...storage.get('kd-wedding-messages', []), ...defaults].slice(0, 6);
    list.replaceChildren();
    messages.forEach((entry) => {
      const card = document.createElement('article');
      card.className = 'message-card';
      const quote = document.createElement('p');
      quote.textContent = `“${entry.message}”`;
      const name = document.createElement('strong');
      name.textContent = entry.name;
      card.append(quote, name);
      list.append(card);
    });
  }

  function initGallery() {
    const slides = $$('[data-gallery] .gallery-slide');
    const dots = $('[data-gallery] .gallery-dots');
    let active = 0;
    let galleryTimer;

    slides.forEach((slide, index) => {
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.setAttribute('aria-label', `Ver imagem ${index + 1}`);
      dot.addEventListener('click', () => setSlide(index));
      dots.append(dot);
    });

    function setSlide(index) {
      active = (index + slides.length) % slides.length;
      slides.forEach((slide, slideIndex) => slide.classList.toggle('active', slideIndex === active));
      $$('button', dots).forEach((dot, dotIndex) => dot.classList.toggle('active', dotIndex === active));
    }

    $('[data-gallery] .previous').addEventListener('click', () => setSlide(active - 1));
    $('[data-gallery] .next').addEventListener('click', () => setSlide(active + 1));
    slides.forEach((slide) => slide.addEventListener('click', () => {
      const image = $('img', slide);
      const lightbox = $('[data-lightbox-image]');
      lightbox.src = image.src;
      lightbox.alt = image.alt;
      openModal('gallery');
    }));
    const frame = $('[data-gallery]');
    const restart = () => { clearInterval(galleryTimer); galleryTimer = setInterval(() => setSlide(active + 1), 6000); };
    frame.addEventListener('mouseenter', () => clearInterval(galleryTimer));
    frame.addEventListener('mouseleave', restart);
    setSlide(0);
    restart();
  }

  function setFormMessage(selector, message, isError = false) {
    const node = $(selector);
    node.textContent = message;
    node.classList.toggle('error', isError);
  }

  function initForms() {
    const rsvp = $('[data-rsvp-form]');
    rsvp.addEventListener('submit', (event) => {
      event.preventDefault();
      if (!rsvp.checkValidity()) {
        setFormMessage('[data-rsvp-message]', 'Revise os campos marcados antes de enviar.', true);
        rsvp.reportValidity();
        return;
      }
      const data = Object.fromEntries(new FormData(rsvp));
      storage.set('kd-wedding-rsvp', data);
      rsvp.reset();
      setFormMessage('[data-rsvp-message]', 'Obrigada! Sua resposta foi registrada neste navegador.');
    });

    const messageForm = $('[data-message-form]');
    const messageInput = $('#message-text');
    messageInput.addEventListener('input', () => { $('[data-message-length]').textContent = String(messageInput.value.length); });
    messageForm.addEventListener('submit', (event) => {
      event.preventDefault();
      if (!messageForm.checkValidity()) {
        setFormMessage('[data-message-message]', 'Preencha os dados e marque a autorização.', true);
        messageForm.reportValidity();
        return;
      }
      const data = new FormData(messageForm);
      const saved = storage.get('kd-wedding-messages', []);
      saved.unshift({ name: data.get('name').trim(), message: data.get('message').trim() });
      storage.set('kd-wedding-messages', saved.slice(0, 4));
      messageForm.reset();
      $('[data-message-length]').textContent = '0';
      setFormMessage('[data-message-message]', 'Seu recado apareceu na lista. Obrigada pelo carinho!');
      renderMessages();
    });

    const checkout = $('[data-checkout-form]');
    checkout.addEventListener('submit', (event) => {
      event.preventDefault();
      if (!checkout.checkValidity()) {
        setFormMessage('[data-checkout-message]', 'Informe seu nome e um e-mail válido.', true);
        checkout.reportValidity();
        return;
      }
      storage.set('kd-wedding-last-gift', { ...Object.fromEntries(new FormData(checkout)), gifts: cart });
      cart = [];
      updateCart();
      renderGifts();
      checkout.reset();
      setFormMessage('[data-checkout-message]', 'Presente registrado. Muito obrigada por celebrar conosco!');
      setTimeout(() => {
        const dialog = $('[data-modal="checkout"]');
        if (dialog.open) dialog.close();
      }, 1500);
    });
  }

  function initReveal() {
    if (!('IntersectionObserver' in window)) { $$('.reveal').forEach((node) => node.classList.add('visible')); return; }
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) { entry.target.classList.add('visible'); observer.unobserve(entry.target); }
      });
    }, { threshold: .14 });
    $$('.reveal').forEach((node) => observer.observe(node));
  }

  function initEvents() {
    $('[data-open-cart]').addEventListener('click', () => setDrawer(true));
    $('[data-close-cart]').addEventListener('click', () => setDrawer(false));
    $('[data-backdrop]').addEventListener('click', () => setDrawer(false));
    $('[data-open-checkout]').addEventListener('click', () => { setDrawer(false); openModal('checkout'); });
    $('[data-gift-grid]').addEventListener('click', (event) => {
      const button = event.target.closest('[data-add-gift]');
      if (button) addGift(button.dataset.addGift);
    });
    $('[data-cart-items]').addEventListener('click', (event) => {
      const button = event.target.closest('[data-remove-gift]');
      if (!button) return;
      cart = cart.filter((id) => id !== button.dataset.removeGift);
      updateCart();
      renderGifts();
      showToast('Presente removido do carrinho.');
    });
    $('[data-load-more]').addEventListener('click', () => { showAllGifts = true; renderGifts(); });
    $('[data-gift-sort]').addEventListener('change', renderGifts);
    $$('[data-address]').forEach((button) => button.addEventListener('click', () => copyAddress(button.dataset.address)));
    $$('[data-close-modal]').forEach((button) => button.addEventListener('click', () => closeModal(button)));
    $$('dialog.modal').forEach((dialog) => dialog.addEventListener('click', (event) => {
      const rect = dialog.getBoundingClientRect();
      const outside = event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom;
      if (outside) dialog.close();
    }));
    $$('.person-card').forEach((button) => button.addEventListener('click', () => {
      $('[data-person-name]').textContent = button.dataset.person;
      openModal('person');
    }));
    const toggle = $('.menu-toggle');
    const nav = $('.site-nav');
    toggle.addEventListener('click', () => {
      const expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!expanded));
      nav.classList.toggle('open', !expanded);
    });
    $$('.site-nav a').forEach((link) => link.addEventListener('click', () => {
      nav.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
    }));
    addEventListener('scroll', () => $('.topbar').classList.toggle('scrolled', scrollY > 4), { passive: true });
  }

  updateCountdown();
  setInterval(updateCountdown, 1000);
  updateCart();
  renderGifts();
  renderMessages();
  initGallery();
  initForms();
  initReveal();
  initEvents();
})();
