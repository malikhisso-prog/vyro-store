
'use strict';

/* =====================================
   VYRO STORE - Main JavaScript
===================================== */

document.addEventListener('DOMContentLoaded', () => {

    /* MOBILE NAVIGATION */

    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');

    if (menuToggle && mainNav) {

        menuToggle.addEventListener('click', () => {

            const isOpen = mainNav.classList.toggle('open');

            menuToggle.classList.toggle('active', isOpen);

            menuToggle.setAttribute(
                'aria-expanded',
                String(isOpen)
            );

            document.body.classList.toggle('menu-open', isOpen);

        });

        mainNav.querySelectorAll('a').forEach(link => {

            link.addEventListener('click', () => {

                mainNav.classList.remove('open');
                menuToggle.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('menu-open');

            });

        });

    }


    /* STICKY HEADER */

    const siteHeader = document.getElementById('siteHeader');

    function handleScroll() {

        if (!siteHeader) return;

        siteHeader.classList.toggle(
            'scrolled',
            window.scrollY > 40
        );

    }

    window.addEventListener('scroll', handleScroll, {
        passive: true
    });

    handleScroll();


    /* SCROLL REVEAL ANIMATION */

    const revealElements = document.querySelectorAll('.reveal');

    if ('IntersectionObserver' in window) {

        const revealObserver = new IntersectionObserver(
            (entries, observer) => {

                entries.forEach(entry => {

                    if (entry.isIntersecting) {

                        entry.target.classList.add('visible');

                        observer.unobserve(entry.target);

                    }

                });

            },
            {
                threshold: 0.12,
                rootMargin: '0px 0px -40px 0px'
            }
        );

        revealElements.forEach(element => {
            revealObserver.observe(element);
        });

    } else {

        revealElements.forEach(element => {
            element.classList.add('visible');
        });

    }


    /* FAVORITES */

    const FAVORITES_KEY = 'vyro_favorites';

    function getFavorites() {

        try {

            const favorites = JSON.parse(
                localStorage.getItem(FAVORITES_KEY) || '[]'
            );

            return Array.isArray(favorites) ? favorites : [];

        } catch (error) {

            return [];

        }

    }

    function saveFavorites(favorites) {

        try {

            localStorage.setItem(
                FAVORITES_KEY,
                JSON.stringify(favorites)
            );

        } catch (error) {

            console.error('Unable to save favorites:', error);

        }

    }

    function updateFavoriteButtons() {

        const favorites = getFavorites();

        document.querySelectorAll('[data-favorite]').forEach(button => {

            const productId = button.dataset.favorite;

            const active = favorites.includes(productId);

            button.classList.toggle('is-favorite', active);

            button.innerHTML = active ? '♥' : '♡';

            button.setAttribute(
                'aria-pressed',
                String(active)
            );

        });

    }

    document.querySelectorAll('[data-favorite]').forEach(button => {

        button.addEventListener('click', () => {

            const productId = button.dataset.favorite;

            if (!productId) return;

            let favorites = getFavorites();

            if (favorites.includes(productId)) {

                favorites = favorites.filter(
                    id => id !== productId
                );

            } else {

                favorites.push(productId);

            }

            saveFavorites(favorites);

            updateFavoriteButtons();

        });

    });

    updateFavoriteButtons();


    /* IMAGE LOADING */

    document.querySelectorAll('img').forEach(image => {

        image.addEventListener('error', () => {

            image.classList.add('image-error');

        });

    });


    /* NEWSLETTER VALIDATION */

    document.querySelectorAll('.newsletter-form').forEach(form => {

        form.addEventListener('submit', event => {

            event.preventDefault();

            const input = form.querySelector('input[type="email"]');

            if (!input || !input.checkValidity()) {

                input?.reportValidity();

                return;

            }

            alert('شكراً لاهتمامك بعالم VYRO!');

            form.reset();

        });

    });


    /* SMOOTH SCROLL */

    document.querySelectorAll('a[href^="#"]').forEach(link => {

        link.addEventListener('click', event => {

            const targetId = link.getAttribute('href');

            if (!targetId || targetId === '#') return;

            const target = document.querySelector(targetId);

            if (target) {

                event.preventDefault();

                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

            }

        });

    });


    /* BUTTON FEEDBACK */

    document.querySelectorAll('.btn').forEach(button => {

        button.addEventListener('mouseenter', () => {
            button.classList.add('btn-hovered');
        });

        button.addEventListener('mouseleave', () => {
            button.classList.remove('btn-hovered');
        });

    });


    /* CONSOLE BRANDING */

    console.log(
        '%c VYRO® ',
        'background:#d5ff48;color:#111210;font-size:24px;font-weight:900;padding:10px 20px;'
    );

    console.log(
        'BUILT TO MOVE. — Welcome to VYRO.'
    );

});
