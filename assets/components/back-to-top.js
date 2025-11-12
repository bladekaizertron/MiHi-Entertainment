// Reusable Back to Top Button and Scroll Progress Bar Component
// Usage: Load this script and it will automatically initialize on page load

(function() {
    'use strict';

    // Check if elements already exist to avoid duplicates
    if (document.getElementById('scroll-progress') || document.getElementById('back-to-top')) {
        return;
    }

    // Create Scroll Progress Bar
    const scrollProgress = document.createElement('div');
    scrollProgress.id = 'scroll-progress';
    scrollProgress.className = 'fixed top-0 left-0 h-1 bg-gradient-to-r from-[#0050ff] to-[#0040d9] z-50 transition-all duration-300';
    scrollProgress.style.width = '0%';
    document.body.appendChild(scrollProgress);

    // Create Back to Top Button
    const backToTopButton = document.createElement('button');
    backToTopButton.id = 'back-to-top';
    backToTopButton.className = 'fixed bottom-8 right-8 bg-gradient-to-r from-[#0050ff] to-[#0040d9] text-white p-4 rounded-full shadow-2xl opacity-0 pointer-events-none transition-all duration-300 hover:scale-110 hover:shadow-3xl focus:ring-4 focus:ring-[#0050ff]/30 z-40';
    backToTopButton.setAttribute('aria-label', 'Back to top');
    backToTopButton.innerHTML = `
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
        </svg>
    `;
    document.body.appendChild(backToTopButton);

    // Initialize functionality
    function init() {
        // Scroll Progress Bar
        window.addEventListener('scroll', function () {
            const windowHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (window.scrollY / windowHeight) * 100;
            scrollProgress.style.width = scrolled + '%';

            // Add scroll effect to header (optional - only if header exists)
            const header = document.querySelector('header');
            if (header) {
                if (window.scrollY > 100) {
                    header.classList.add('bg-white/98', 'shadow-md');
                } else {
                    header.classList.remove('bg-white/98', 'shadow-md');
                }
            }
        });

        // Back to Top Button visibility
        window.addEventListener('scroll', function () {
            if (window.scrollY > 500) {
                backToTopButton.classList.remove('opacity-0', 'pointer-events-none');
                backToTopButton.classList.add('opacity-100');
            } else {
                backToTopButton.classList.add('opacity-0', 'pointer-events-none');
                backToTopButton.classList.remove('opacity-100');
            }
        });

        // Back to Top Button click handler
        backToTopButton.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
