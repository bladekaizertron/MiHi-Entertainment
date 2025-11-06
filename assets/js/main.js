/**
 * Main JavaScript for MiHi Entertainment Website
 * Handles smooth scrolling, animations, lazy loading, and interactions
 */

(function() {
    'use strict';

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        initSmoothScrolling();
        initLazyLoading();
        initScrollAnimations();
        initKeyboardNavigation();
        initFormValidation();
        initScrollPerformance();
        initHorizontalScrolling();
    }

    /**
     * Smooth scrolling for anchor links
     */
    function initSmoothScrolling() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#' || href === '#contact') {
                    // Allow default behavior for contact links
                    return;
                }
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    /**
     * Lazy load images with Intersection Observer
     */
    function initLazyLoading() {
        const images = document.querySelectorAll('img[loading="lazy"]');
        if (!images.length) return;

        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });

        images.forEach(img => {
            imageObserver.observe(img);
        });
    }

    /**
     * Scroll-triggered animations using Intersection Observer
     */
    function initScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe all sections
        document.querySelectorAll('section').forEach(section => {
            observer.observe(section);
        });
    }

    /**
     * Keyboard navigation improvements
     */
    function initKeyboardNavigation() {
        document.addEventListener('keydown', function (e) {
            // Press 'Escape' to close modals or return to top
            if (e.key === 'Escape') {
                const modal = document.getElementById('brochureModal');
                if (modal && !modal.classList.contains('hidden')) {
                    if (typeof closeBrochureModal === 'function') {
                        closeBrochureModal();
                    }
                } else {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        });
    }

    /**
     * Form validation with visual feedback
     */
    function initFormValidation() {
        const forms = document.querySelectorAll('form');
        if (!forms.length) return;

        forms.forEach(form => {
            form.addEventListener('submit', function (e) {
                const inputs = form.querySelectorAll('input[required], textarea[required]');
                let isValid = true;

                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('border-red-500', 'shake');
                        setTimeout(() => input.classList.remove('shake'), 500);
                    } else {
                        input.classList.remove('border-red-500');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    }

    /**
     * Performance: Optimize scroll events with requestAnimationFrame
     */
    function initScrollPerformance() {
        let scrollTimeout;
        window.addEventListener('scroll', function () {
            if (scrollTimeout) {
                window.cancelAnimationFrame(scrollTimeout);
            }
            scrollTimeout = window.requestAnimationFrame(function () {
                // Scroll-based animations here
            });
        }, { passive: true });
    }

    /**
     * Horizontal scrolling for service cards
     */
    function initHorizontalScrolling() {
        const scrollContainer = document.querySelector('.scroll-container');
        if (!scrollContainer) return;

        // Horizontal mouse wheel scrolling
        scrollContainer.addEventListener('wheel', function(e) {
            if (Math.abs(e.deltaX) < Math.abs(e.deltaY)) {
                e.preventDefault();
                scrollContainer.scrollLeft += e.deltaY;
            }
        }, { passive: false });

        // Touch/drag scrolling
        let isDown = false;
        let startX;
        let scrollLeft;

        scrollContainer.addEventListener('mousedown', (e) => {
            isDown = true;
            scrollContainer.style.cursor = 'grabbing';
            startX = e.pageX - scrollContainer.offsetLeft;
            scrollLeft = scrollContainer.scrollLeft;
        });

        scrollContainer.addEventListener('mouseleave', () => {
            isDown = false;
            scrollContainer.style.cursor = 'grab';
        });

        scrollContainer.addEventListener('mouseup', () => {
            isDown = false;
            scrollContainer.style.cursor = 'grab';
        });

        scrollContainer.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - scrollContainer.offsetLeft;
            const walk = (x - startX) * 2;
            scrollContainer.scrollLeft = scrollLeft - walk;
        });

        scrollContainer.style.cursor = 'grab';

        // Arrow button functionality
        const scrollLeftBtn = document.getElementById('scrollLeftBtn');
        const scrollRightBtn = document.getElementById('scrollRightBtn');

        function updateArrowButtons() {
            if (!scrollContainer) return;
            const { scrollLeft, scrollWidth, clientWidth } = scrollContainer;
            const maxScroll = scrollWidth - clientWidth;

            if (scrollLeftBtn) {
                scrollLeftBtn.disabled = scrollLeft <= 0;
                scrollLeftBtn.setAttribute('aria-disabled', scrollLeft <= 0);
            }

            if (scrollRightBtn) {
                scrollRightBtn.disabled = scrollLeft >= maxScroll - 1;
                scrollRightBtn.setAttribute('aria-disabled', scrollLeft >= maxScroll - 1);
            }
        }

        scrollContainer.addEventListener('scroll', updateArrowButtons);
        updateArrowButtons();

        if (scrollLeftBtn) {
            scrollLeftBtn.addEventListener('click', () => {
                const cardWidth = 320 + 24;
                scrollContainer.scrollBy({
                    left: -cardWidth,
                    behavior: 'smooth'
                });
            });
        }

        if (scrollRightBtn) {
            scrollRightBtn.addEventListener('click', () => {
                const cardWidth = 320 + 24;
                scrollContainer.scrollBy({
                    left: cardWidth,
                    behavior: 'smooth'
                });
            });
        }
    }

    // Brochure Modal Functions (global for onclick handlers)
    window.openBrochureModal = function() {
        const modal = document.getElementById('brochureModal');
        const iframe = document.getElementById('brochureIframe');
        const loader = document.getElementById('brochureLoader');

        if (!modal || !iframe || !loader) return;

        modal.classList.remove('hidden');
        loader.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        const timestamp = new Date().getTime();
        iframe.src = 'flipbook/books/test.html?t=' + timestamp;

        setTimeout(() => iframe.focus(), 100);
    };

    window.closeBrochureModal = function() {
        const modal = document.getElementById('brochureModal');
        const iframe = document.getElementById('brochureIframe');
        const loader = document.getElementById('brochureLoader');

        if (!modal || !iframe || !loader) return;

        modal.classList.add('hidden');
        loader.classList.add('hidden');
        document.body.style.overflow = '';

        iframe.src = 'about:blank';
    };

    // Listen for flipbook loaded message from iframe
    window.addEventListener('message', function(event) {
        if (event.data === 'flipbookLoaded') {
            const loader = document.getElementById('brochureLoader');
            if (loader) {
                loader.classList.add('hidden');
            }
        }
    });

    // Prevent modal content clicks from closing modal
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('brochureModal');
        if (modal) {
            const modalContent = modal.querySelector('.relative.w-full');
            if (modalContent) {
                modalContent.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            }
        }
    });
})();
