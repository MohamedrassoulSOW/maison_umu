/**
 * UI critique — chargé en script classique (hors importmap).
 * Garantit catégories, profil, favoris, thème, modale produit même si Asset Mapper échoue en prod.
 */
(function () {
    'use strict';

    if (window.__umuUiBound) {
        return;
    }
    window.__umuUiBound = true;

    function isMobileNav() {
        return window.matchMedia('(max-width: 1024px)').matches;
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
        var els = getNavEls();
        if (!els.panel || !els.toggle) return;

        if (els.nav) els.nav.classList.toggle('is-nav-open', open);
        els.panel.classList.toggle('is-open', open);
        els.toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        els.toggle.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
        els.toggle.classList.toggle('is-open', open);
        document.body.classList.toggle('umu-nav-open', open);

        if (els.backdrop) {
            els.backdrop.hidden = true;
            els.backdrop.classList.remove('is-visible');
        }
        if (els.label) {
            els.label.textContent = open ? 'Fermer' : 'Menu';
        }
    }

    function setMegaOpen(open) {
        var els = getNavEls();
        if (!els.megaRoot || !els.megaBtn || !els.megaPanel) return;
        els.megaRoot.classList.toggle('is-open', open);
        els.megaBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        els.megaPanel.hidden = !open;
    }

    function setProfileOpen(open) {
        var els = getNavEls();
        if (!els.profileRoot || !els.profileBtn || !els.profilePanel) return;
        els.profileRoot.classList.toggle('is-open', open);
        els.profileBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        els.profilePanel.hidden = !open;
    }

    function getTheme() {
        return document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        var next = theme === 'dark' ? 'dark' : 'light';
        document.documentElement.dataset.theme = next;
        document.documentElement.style.colorScheme = next;
        try {
            localStorage.setItem('umu-theme', next);
        } catch (e) { /* ignore */ }

        document.querySelectorAll('[data-umu-theme-toggle]').forEach(function (btn) {
            var dark = next === 'dark';
            btn.setAttribute('aria-label', dark ? 'Activer le mode clair' : 'Activer le mode sombre');
            btn.setAttribute('title', dark ? 'Mode clair' : 'Mode sombre');
            btn.classList.toggle('is-dark', dark);
        });
    }

    var FAV_WELCOME_KEY = 'umu-fav-welcome-seen';

    function getProductModal() {
        return document.querySelector('[data-umu-product-modal]');
    }

    function getFavWelcome() {
        return document.querySelector('[data-umu-fav-welcome]');
    }

    function dismissFavWelcome() {
        var modal = getFavWelcome();
        if (!modal || modal.hidden) return;
        modal.hidden = true;
        document.body.classList.remove('umu-modal-open');
        try {
            localStorage.setItem(FAV_WELCOME_KEY, '1');
        } catch (e) { /* ignore */ }
    }

    function shouldShowFavWelcome() {
        try {
            if (localStorage.getItem(FAV_WELCOME_KEY) === '1') {
                return false;
            }
        } catch (e) { /* ignore */ }

        var path = window.location.pathname || '/';
        if (path !== '/' && path !== '') {
            return false;
        }

        var badge = document.querySelector('[data-umu-fav-count]');
        if (badge && !badge.hidden) {
            var n = parseInt(badge.textContent, 10);
            if (!isNaN(n) && n > 0) {
                return false;
            }
        }

        return !!getFavWelcome();
    }

    function openFavWelcome() {
        var modal = getFavWelcome();
        if (!modal || !shouldShowFavWelcome()) return;
        modal.hidden = false;
        document.body.classList.add('umu-modal-open');
        var closeBtn = modal.querySelector('.umu-modal__close');
        if (closeBtn && closeBtn.focus) {
            try { closeBtn.focus({ preventScroll: true }); } catch (e) { closeBtn.focus(); }
        }
    }

    function scheduleFavWelcome() {
        if (window.__umuWelcomeScheduled || !shouldShowFavWelcome()) return;
        window.__umuWelcomeScheduled = true;
        window.setTimeout(openFavWelcome, 900);
    }

    function closeProductModal() {
        var modal = getProductModal();
        if (!modal) return;
        modal.hidden = true;
        document.body.classList.remove('umu-modal-open');
        var body = modal.querySelector('[data-umu-product-modal-body]');
        if (body) body.innerHTML = '';
    }

    function initProductGallery(root) {
        (root || document).querySelectorAll('[data-umu-gallery]').forEach(function (gallery) {
            if (gallery.dataset.bound === '1') return;
            gallery.dataset.bound = '1';
            var main = gallery.querySelector('[data-umu-gallery-main]');
            var thumbs = gallery.querySelectorAll('[data-umu-gallery-thumb]');
            if (!main || !thumbs.length) return;
            thumbs.forEach(function (thumb) {
                thumb.addEventListener('click', function () {
                    var src = thumb.getAttribute('data-src');
                    if (!src) return;
                    main.src = src;
                    thumbs.forEach(function (t) {
                        t.classList.toggle('is-active', t === thumb);
                    });
                });
            });
        });
    }

    function rememberPreference(card) {
        var url = card ? card.getAttribute('data-preference-url') : null;
        if (!url || !window.fetch) return;
        fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).catch(function () {});
    }

    function openProductModal(card) {
        var modal = getProductModal();
        var body = modal ? modal.querySelector('[data-umu-product-modal-body]') : null;
        var template = card ? card.querySelector('[data-umu-product-template]') : null;
        if (!modal || !body || !template) return;
        body.innerHTML = '';
        body.appendChild(template.content.cloneNode(true));
        modal.hidden = false;
        document.body.classList.add('umu-modal-open');
        initProductGallery(body);
        rememberPreference(card);
        var closeBtn = modal.querySelector('.umu-modal__close');
        if (closeBtn && closeBtn.focus) {
            try { closeBtn.focus({ preventScroll: true }); } catch (e) { closeBtn.focus(); }
        }
    }

    function updateFavoriteUi(productId, liked, favoritesCount, likesCount) {
        var id = String(productId);
        document.querySelectorAll('[data-umu-favorite][data-product-id="' + id + '"]').forEach(function (btn) {
            btn.classList.toggle('is-active', liked);
            btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
            var likesLabel = likesCount + ' j’aime';
            btn.setAttribute('aria-label', (liked ? 'Retirer des favoris' : 'Ajouter aux favoris') + ' — ' + likesLabel);
            btn.setAttribute('title', liked ? 'Retirer des favoris' : 'Ajouter aux favoris');
            btn.querySelectorAll('[data-umu-likes-count]').forEach(function (el) {
                el.textContent = String(likesCount);
            });
        });
        document.querySelectorAll('[data-umu-likes-count][data-product-id="' + id + '"]').forEach(function (el) {
            el.textContent = String(likesCount);
        });
        document.querySelectorAll('[data-umu-fav-count]').forEach(function (badge) {
            badge.textContent = String(favoritesCount);
            badge.hidden = favoritesCount <= 0;
        });
        document.querySelectorAll('[data-umu-fav-nav]').forEach(function (link) {
            link.setAttribute('aria-label', 'Favoris (' + favoritesCount + ')');
            link.classList.toggle('is-active', favoritesCount > 0);
        });
    }

    function toggleFavorite(btn) {
        var url = btn.getAttribute('data-favorite-url');
        if (!url || btn.disabled) return;
        btn.disabled = true;
        fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { res: res, data: data };
                }).catch(function () {
                    return { res: res, data: null };
                });
            })
            .then(function (payload) {
                var res = payload.res;
                var data = payload.data || {};
                if (res.status === 401 || data.loginRequired) {
                    window.location.href = data.loginUrl || '/login';
                    return;
                }
                if (!res.ok || !data.ok) throw new Error(data.message || 'favorite toggle failed');
                updateFavoriteUi(
                    data.productId,
                    Boolean(data.liked),
                    Number(data.count) || 0,
                    Number(data.likesCount) || 0
                );
            })
            .catch(function (err) {
                console.error(err);
            })
            .finally(function () {
                btn.disabled = false;
            });
    }

    function initReveal() {
        var cards = document.querySelectorAll('.umu-card:not(.is-visible)');
        if (!cards.length) return;
        cards.forEach(function (card) {
            card.classList.add('js-reveal');
        });
        if (!('IntersectionObserver' in window)) {
            cards.forEach(function (card) {
                card.classList.add('is-visible');
            });
            return;
        }
        var observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry, index) {
                    if (!entry.isIntersecting) return;
                    var el = entry.target;
                    el.style.transitionDelay = Math.min(index % 4, 3) * 70 + 'ms';
                    el.classList.add('is-visible');
                    observer.unobserve(el);
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
        );
        cards.forEach(function (card) {
            observer.observe(card);
        });
    }

    function initSlider() {
        var slider = document.getElementById('productSlider');
        var prevBtn = document.getElementById('prevBtn');
        var nextBtn = document.getElementById('nextBtn');
        if (!slider || !prevBtn || !nextBtn || prevBtn.dataset.bound === '1') return;
        prevBtn.dataset.bound = '1';
        var scrollAmount = 280;
        prevBtn.addEventListener('click', function () {
            slider.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        });
        nextBtn.addEventListener('click', function () {
            slider.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        });
    }

    function initCheckoutShipping() {
        var citySelector = document.getElementById('order_city');
        var shippingEl = document.getElementById('shippingConst');
        var totalEl = document.getElementById('totalToPay');
        if (!citySelector || !shippingEl || !totalEl || citySelector.dataset.bound === '1') return;
        citySelector.dataset.bound = '1';
        var baseTotal = Number(totalEl.dataset.baseTotal || 0);

        function fetchShipping(cityId) {
            if (!cityId) {
                shippingEl.textContent = '—';
                totalEl.textContent = baseTotal + ' CFA';
                return;
            }
            var template = totalEl.dataset.shippingUrlTemplate || ('/city/' + cityId + '/shipping/const');
            var url = template.indexOf('__CITY__') !== -1
                ? template.replace('__CITY__', encodeURIComponent(cityId))
                : ('/city/' + cityId + '/shipping/const');
            fetch(url)
                .then(function (response) {
                    if (!response.ok) throw new Error('shipping');
                    return response.json();
                })
                .then(function (data) {
                    var shipping = Number(data.shippingConst || 0);
                    shippingEl.textContent = shipping + ' CFA';
                    totalEl.textContent = (baseTotal + shipping) + ' CFA';
                })
                .catch(function () {
                    shippingEl.textContent = 'Erreur';
                });
        }

        fetchShipping(citySelector.value);
        citySelector.addEventListener('change', function () {
            fetchShipping(citySelector.value);
        });
    }

    function bootUi() {
        setNavOpen(false);
        setMegaOpen(false);
        setProfileOpen(false);
        closeProductModal();
        initReveal();
        initSlider();
        initCheckoutShipping();
        initProductGallery(document);
        applyTheme(getTheme());
        scheduleFavWelcome();
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!(target instanceof Element)) return;
        var els = getNavEls();

        var themeBtn = target.closest('[data-umu-theme-toggle]');
        if (themeBtn) {
            event.preventDefault();
            applyTheme(getTheme() === 'dark' ? 'light' : 'dark');
            return;
        }

        if (target.closest('[data-umu-nav-toggle]')) {
            event.preventDefault();
            setProfileOpen(false);
            setMegaOpen(false);
            var panel = els.panel;
            setNavOpen(!(panel && panel.classList.contains('is-open')));
            return;
        }

        if (target.closest('[data-umu-nav-backdrop]') || target.closest('[data-umu-nav-close]')) {
            event.preventDefault();
            setProfileOpen(false);
            setMegaOpen(false);
            setNavOpen(false);
            return;
        }

        var togglePwd = target.closest('[data-password-toggle-btn]');
        if (togglePwd) {
            event.preventDefault();
            var wrap = togglePwd.closest('.umu-password');
            var input = wrap ? wrap.querySelector('input') : null;
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            var iconShow = togglePwd.querySelector('.umu-password__icon--show');
            var iconHide = togglePwd.querySelector('.umu-password__icon--hide');
            if (iconShow && iconHide) {
                iconShow.hidden = show;
                iconHide.hidden = !show;
            }
            togglePwd.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            return;
        }

        var dashToggle = target.closest('[data-dash-menu-toggle]');
        if (dashToggle) {
            event.preventDefault();
            var dashMenu = document.querySelector('[data-dash-nav]');
            if (!dashMenu) return;
            var dashOpen = !dashMenu.classList.contains('is-open');
            dashMenu.classList.toggle('is-open', dashOpen);
            dashToggle.setAttribute('aria-expanded', dashOpen ? 'true' : 'false');
            dashToggle.setAttribute('aria-label', dashOpen ? 'Fermer le menu' : 'Ouvrir le menu');
            dashToggle.classList.toggle('is-open', dashOpen);
            document.body.classList.toggle('dash-menu-open', dashOpen);
            return;
        }

        if (target.closest('[data-dash-nav] a.dash__link')) {
            var openMenu = document.querySelector('[data-dash-nav].is-open');
            var openToggle = document.querySelector('[data-dash-menu-toggle]');
            if (openMenu) {
                openMenu.classList.remove('is-open');
                document.body.classList.remove('dash-menu-open');
                if (openToggle) {
                    openToggle.setAttribute('aria-expanded', 'false');
                    openToggle.setAttribute('aria-label', 'Ouvrir le menu');
                    openToggle.classList.remove('is-open');
                }
            }
        }

        if (target.closest('[data-umu-profile-btn]')) {
            event.preventDefault();
            event.stopPropagation();
            var profileOpen = els.profileBtn && els.profileBtn.getAttribute('aria-expanded') !== 'true';
            setMegaOpen(false);
            setProfileOpen(profileOpen);
            return;
        }

        if (target.closest('[data-umu-mega-btn]')) {
            event.preventDefault();
            event.stopPropagation();
            var megaOpen = els.megaBtn && els.megaBtn.getAttribute('aria-expanded') !== 'true';
            setProfileOpen(false);
            setMegaOpen(megaOpen);
            return;
        }

        var favBtn = target.closest('[data-umu-favorite]');
        if (favBtn) {
            event.preventDefault();
            event.stopPropagation();
            toggleFavorite(favBtn);
            return;
        }

        var openBtn = target.closest('[data-umu-product-open]');
        if (openBtn) {
            event.preventDefault();
            var card = openBtn.closest('[data-umu-product-card]');
            if (card) openProductModal(card);
            return;
        }

        if (target.closest('[data-umu-product-modal-close]')) {
            event.preventDefault();
            closeProductModal();
            return;
        }

        if (target.closest('[data-umu-fav-welcome-close]')) {
            dismissFavWelcome();
            return;
        }

        if (els.megaRoot && !els.megaRoot.contains(target)) {
            setMegaOpen(false);
        }
        if (els.profileRoot && !els.profileRoot.contains(target)) {
            setProfileOpen(false);
        }

        if (isMobileNav() && els.panel && els.panel.classList.contains('is-open')) {
            var link = target.closest('.umu-nav__panel a[href]');
            if (link && !link.hasAttribute('data-bs-toggle')) {
                setNavOpen(false);
                setMegaOpen(false);
            }
        }
    }, true);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setMegaOpen(false);
            setProfileOpen(false);
            setNavOpen(false);
            closeProductModal();
            dismissFavWelcome();
        }
    });

    window.addEventListener('scroll', function () {
        var nav = document.querySelector('[data-umu-nav]');
        if (!nav) return;
        nav.classList.toggle('is-scrolled', window.scrollY > 12);
    }, { passive: true });

    window.addEventListener('resize', function () {
        if (!isMobileNav()) {
            setNavOpen(false);
            setMegaOpen(false);
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootUi);
    } else {
        bootUi();
    }

    document.addEventListener('turbo:load', bootUi);
    document.addEventListener('turbo:render', bootUi);
})();
