import './stimulus_bootstrap.js';
import './styles/app.css';

function isMobileNav() {
    return window.matchMedia('(max-width: 900px)').matches;
}

function getNavEls() {
    return {
        nav: document.querySelector('[data-umu-nav]'),
        toggle: document.querySelector('[data-umu-nav-toggle]'),
        panel: document.querySelector('[data-umu-nav-panel]'),
        backdrop: document.querySelector('[data-umu-nav-backdrop]'),
        label: document.querySelector('[data-umu-nav-toggle-label]'),
        megaRoot: document.querySelector('[data-umu-mega]'),
        megaBtn: document.querySelector('[data-umu-mega-btn]'),
        megaPanel: document.querySelector('[data-umu-mega-panel]'),
        profileRoot: document.querySelector('[data-umu-profile]'),
        profileBtn: document.querySelector('[data-umu-profile-btn]'),
        profilePanel: document.querySelector('[data-umu-profile-panel]'),
    };
}

function setNavOpen(open) {
    const { nav, toggle, panel, backdrop, label } = getNavEls();
    if (!panel || !toggle) return;

    if (nav) nav.classList.toggle('is-nav-open', open);
    panel.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
    toggle.classList.toggle('is-open', open);
    document.body.classList.toggle('umu-nav-open', open);

    if (backdrop) {
        backdrop.hidden = true;
        backdrop.classList.remove('is-visible');
    }

    if (label) {
        label.textContent = open ? 'Fermer' : 'Menu';
    }
}

function setMegaOpen(open) {
    const { megaRoot, megaBtn, megaPanel } = getNavEls();
    if (!megaRoot || !megaBtn || !megaPanel) return;

    megaRoot.classList.toggle('is-open', open);
    megaBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    megaPanel.hidden = !open;
}

function setProfileOpen(open) {
    const { profileRoot, profileBtn, profilePanel } = getNavEls();
    if (!profileRoot || !profileBtn || !profilePanel) return;

    profileRoot.classList.toggle('is-open', open);
    profileBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    profilePanel.hidden = !open;
}

function onDocumentClick(event) {
    const { panel, megaRoot, megaBtn, profileRoot, profileBtn } = getNavEls();
    const target = event.target;

    if (!(target instanceof Element)) return;

    // Menu open/close is handled by the inline script in base.html.twig
    if (
        target.closest('[data-umu-nav-toggle]')
        || target.closest('[data-umu-nav-backdrop]')
        || target.closest('[data-umu-nav-close]')
    ) {
        setProfileOpen(false);
        setMegaOpen(false);
        return;
    }

    // Profile circle menu
    if (target.closest('[data-umu-profile-btn]')) {
        event.preventDefault();
        event.stopPropagation();
        const open = profileBtn?.getAttribute('aria-expanded') !== 'true';
        setMegaOpen(false);
        setProfileOpen(open);
        return;
    }

    // Categories mega menu
    if (target.closest('[data-umu-mega-btn]')) {
        event.preventDefault();
        event.stopPropagation();
        const open = megaBtn?.getAttribute('aria-expanded') !== 'true';
        setProfileOpen(false);
        setMegaOpen(open);
        return;
    }

    if (megaRoot && !megaRoot.contains(target)) {
        setMegaOpen(false);
    }

    if (profileRoot && !profileRoot.contains(target)) {
        setProfileOpen(false);
    }

    if (isMobileNav() && panel?.classList.contains('is-open')) {
        const link = target.closest('.umu-nav__panel a[href]');
        if (link && !link.hasAttribute('data-bs-toggle')) {
            setNavOpen(false);
            setMegaOpen(false);
        }
    }
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        setMegaOpen(false);
        setProfileOpen(false);
        setNavOpen(false);
        closeProductModal();
    }
}

function onScroll() {
    const { nav } = getNavEls();
    if (!nav) return;
    nav.classList.toggle('is-scrolled', window.scrollY > 12);
}

function initReveal() {
    const cards = document.querySelectorAll('.umu-card:not(.is-visible)');
    if (!cards.length) return;

    if (!('IntersectionObserver' in window)) {
        cards.forEach((card) => card.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry, index) => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                el.style.transitionDelay = `${Math.min(index % 4, 3) * 70}ms`;
                el.classList.add('is-visible');
                observer.unobserve(el);
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
    );

    cards.forEach((card) => observer.observe(card));
}

function initSlider() {
    const slider = document.getElementById('productSlider');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    if (!slider || !prevBtn || !nextBtn || prevBtn.dataset.bound === '1') return;
    prevBtn.dataset.bound = '1';

    const scrollAmount = 280;
    prevBtn.addEventListener('click', () => slider.scrollBy({ left: -scrollAmount, behavior: 'smooth' }));
    nextBtn.addEventListener('click', () => slider.scrollBy({ left: scrollAmount, behavior: 'smooth' }));
}

function initCheckoutShipping() {
    const citySelector = document.getElementById('order_city');
    const shippingEl = document.getElementById('shippingConst');
    const totalEl = document.getElementById('totalToPay');

    if (!citySelector || !shippingEl || !totalEl || citySelector.dataset.bound === '1') return;
    citySelector.dataset.bound = '1';

    const baseTotal = Number(totalEl.dataset.baseTotal || 0);

    const fetchShipping = async (cityId) => {
        if (!cityId) {
            shippingEl.textContent = '—';
            totalEl.textContent = `${baseTotal} CFA`;
            return;
        }

        try {
            const template = totalEl.dataset.shippingUrlTemplate || `/city/${cityId}/shipping/const`;
            const url = template.includes('__CITY__')
                ? template.replace('__CITY__', encodeURIComponent(cityId))
                : `/city/${cityId}/shipping/const`;
            const response = await fetch(url);
            if (!response.ok) throw new Error('shipping');
            const data = await response.json();
            const shipping = Number(data.shippingConst || 0);
            shippingEl.textContent = `${shipping} CFA`;
            totalEl.textContent = `${baseTotal + shipping} CFA`;
        } catch (error) {
            shippingEl.textContent = 'Erreur';
        }
    };

    fetchShipping(citySelector.value);
    citySelector.addEventListener('change', () => fetchShipping(citySelector.value));
}

function initProductGallery(root = document) {
    root.querySelectorAll('[data-umu-gallery]').forEach((gallery) => {
        if (gallery.dataset.bound === '1') return;
        gallery.dataset.bound = '1';

        const main = gallery.querySelector('[data-umu-gallery-main]');
        const thumbs = gallery.querySelectorAll('[data-umu-gallery-thumb]');
        if (!main || !thumbs.length) return;

        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                const src = thumb.getAttribute('data-src');
                if (!src) return;
                main.src = src;
                thumbs.forEach((t) => t.classList.toggle('is-active', t === thumb));
            });
        });
    });
}

function getProductModal() {
    return document.querySelector('[data-umu-product-modal]');
}

function closeProductModal() {
    const modal = getProductModal();
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('umu-modal-open');
    const body = modal.querySelector('[data-umu-product-modal-body]');
    if (body) body.innerHTML = '';
}

function openProductModal(card) {
    const modal = getProductModal();
    const body = modal?.querySelector('[data-umu-product-modal-body]');
    const template = card?.querySelector('[data-umu-product-template]');
    if (!modal || !body || !template) return;

    body.innerHTML = '';
    body.appendChild(template.content.cloneNode(true));
    modal.hidden = false;
    document.body.classList.toggle('umu-modal-open', true);
    initProductGallery(body);

    const closeBtn = modal.querySelector('.umu-modal__close');
    closeBtn?.focus({ preventScroll: true });
}

function updateFavoriteUi(productId, liked, favoritesCount, likesCount) {
    const id = String(productId);
    document.querySelectorAll(`[data-umu-favorite][data-product-id="${id}"]`).forEach((btn) => {
        btn.classList.toggle('is-active', liked);
        btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
        const likesLabel = `${likesCount} j’aime`;
        btn.setAttribute(
            'aria-label',
            `${liked ? 'Retirer des favoris' : 'Ajouter aux favoris'} — ${likesLabel}`
        );
        btn.setAttribute('title', liked ? 'Retirer des favoris' : 'Ajouter aux favoris');
        btn.querySelectorAll('[data-umu-likes-count]').forEach((el) => {
            el.textContent = String(likesCount);
        });
    });

    document.querySelectorAll(`[data-umu-likes-count][data-product-id="${id}"]`).forEach((el) => {
        el.textContent = String(likesCount);
    });

    document.querySelectorAll('[data-umu-fav-count]').forEach((badge) => {
        badge.textContent = String(favoritesCount);
        badge.hidden = favoritesCount <= 0;
    });

    document.querySelectorAll('[data-umu-fav-nav]').forEach((link) => {
        link.setAttribute('aria-label', `Favoris (${favoritesCount})`);
        link.classList.toggle('is-active', favoritesCount > 0);
    });
}

async function toggleFavorite(btn) {
    const url = btn.getAttribute('data-favorite-url');
    if (!url || btn.disabled) return;

    btn.disabled = true;
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error('favorite toggle failed');
        const data = await res.json();
        if (!data.ok) throw new Error(data.message || 'favorite toggle failed');
        updateFavoriteUi(
            data.productId,
            Boolean(data.liked),
            Number(data.count) || 0,
            Number(data.likesCount) || 0
        );
    } catch (err) {
        console.error(err);
    } finally {
        btn.disabled = false;
    }
}

function onProductModalClick(event) {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const favBtn = target.closest('[data-umu-favorite]');
    if (favBtn) {
        event.preventDefault();
        event.stopPropagation();
        toggleFavorite(favBtn);
        return;
    }

    const openBtn = target.closest('[data-umu-product-open]');
    if (openBtn) {
        event.preventDefault();
        const card = openBtn.closest('[data-umu-product-card]');
        if (card) openProductModal(card);
        return;
    }

    if (target.closest('[data-umu-product-modal-close]')) {
        event.preventDefault();
        closeProductModal();
    }
}

function bootUi() {
    onScroll();
    setNavOpen(false);
    setMegaOpen(false);
    setProfileOpen(false);
    closeProductModal();
    initReveal();
    initSlider();
    initCheckoutShipping();
    initProductGallery();
}

// Event delegation: works even after Turbo replaces the nav fragment
if (!window.__umuNavBound) {
    window.__umuNavBound = true;
    document.addEventListener('click', onDocumentClick, true);
    document.addEventListener('click', onProductModalClick);
    document.addEventListener('keydown', onKeydown);
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', () => {
        if (!isMobileNav()) {
            setNavOpen(false);
            setMegaOpen(false);
        }
    });
}

document.addEventListener('DOMContentLoaded', bootUi);
document.addEventListener('turbo:load', bootUi);
document.addEventListener('turbo:render', bootUi);
