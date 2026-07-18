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
    const { toggle, panel, backdrop, label } = getNavEls();
    if (!panel || !toggle) return;

    panel.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('umu-nav-open', open);

    if (backdrop) {
        backdrop.hidden = !open;
        backdrop.classList.toggle('is-visible', open);
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
    if (target.closest('[data-umu-nav-toggle]') || target.closest('[data-umu-nav-backdrop]')) {
        setProfileOpen(false);
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

function bootUi() {
    onScroll();
    setNavOpen(false);
    setMegaOpen(false);
    setProfileOpen(false);
    initReveal();
    initSlider();
    initCheckoutShipping();
}

// Event delegation: works even after Turbo replaces the nav fragment
if (!window.__umuNavBound) {
    window.__umuNavBound = true;
    document.addEventListener('click', onDocumentClick, true);
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
