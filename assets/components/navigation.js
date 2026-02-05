// Shared Navigation Component
// This script loads navigation dynamically from the API

(function () {
    // Function to initialize navigation
    async function initNavigation() {
        // Determine the correct path prefix
        const scripts = document.getElementsByTagName('script');
        let navScriptSrc = '';

        for (let script of scripts) {
            if (script.src && script.src.includes('navigation.js')) {
                navScriptSrc = script.src;
                break;
            }
        }

        let pathPrefix = '';
        if (navScriptSrc) {
            const scriptUrl = new URL(navScriptSrc);
            const scriptPath = scriptUrl.pathname;
            const rootPath = scriptPath.replace('/assets/components/navigation.js', '');
            const rootSegments = rootPath.split('/').filter(segment => segment.length > 0);
            pathPrefix = '../'.repeat(rootSegments.length);
        }

        if (!pathPrefix) {
            const currentPath = window.location.pathname;
            const pathParts = currentPath.split('/').filter(part => part.length > 0 && !part.includes('.html'));
            const directoryDepth = pathParts.length;
            pathPrefix = directoryDepth > 0 ? '../'.repeat(directoryDepth) : '';
        }

        const assetsPrefix = pathPrefix + 'assets/';

        // Add protection styles
        const style = document.createElement('style');
        style.textContent = `
        /* Navigation Protection */
        header { z-index: 50 !important; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important; font-size: inherit !important; }
        header nav, header nav * { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important; font-style: normal !important; }
        body { padding-top: 80px !important; }
        @media (min-width: 1024px) { body { padding-top: 96px !important; } }
        #mobile-menu-overlay { z-index: 40 !important; }
        #mobile-menu { z-index: 41 !important; }
        header .btn-primary[style*="#FF4F4F"], nav .btn-primary[style*="#FF4F4F"], #mobile-menu .btn-primary[style*="#FF4F4F"] { background: #FF4F4F !important; color: #ffffff !important; }
        header .btn-primary[style*="#FF4F4F"]:hover, nav .btn-primary[style*="#FF4F4F"]:hover, #mobile-menu .btn-primary[style*="#FF4F4F"]:hover { background: #FF4F4F !important; opacity: 0.9 !important; }
        `;
        if (!document.getElementById('navigation-protection')) {
            style.id = 'navigation-protection';
            document.head.appendChild(style);
        }

        // Fetch Data
        try {
            const response = await fetch(pathPrefix + 'api/navigation.php?t=' + Date.now());
            if (!response.ok) throw new Error('API request failed');
            const menuData = await response.json();

            // Build Components
            const desktopNav = buildDesktopNav(menuData, pathPrefix);
            const mobileNav = buildMobileNav(menuData, pathPrefix);

            const navigationHTML = `
            <header class="bg-white shadow-lg fixed top-0 left-0 right-0 z-50">
                <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
                    <!-- Logo -->
                    <a href="${pathPrefix}index.html" class="flex items-center" aria-label="Go to home">
                        <img src="${assetsPrefix}images/logo.svg" alt="MiHi Entertainment" class="h-12 md:h-16 w-auto" />
                    </a>
                    
                    <!-- Desktop Nav -->
                    <div class="hidden lg:flex items-center space-x-8">
                        ${desktopNav}
                    </div>
                    
                    <!-- Mobile Nav Button -->
                    <div class="lg:hidden">
                        <button id="mobile-menu-btn" class="text-gray-900 hover:text-blue-600 transition duration-300 relative z-10 cursor-pointer p-2 -m-2">
                            <div class="w-6 h-6 flex flex-col justify-center items-center">
                                <span class="block w-5 h-0.5 bg-current transform transition-all duration-300 origin-center" id="hamburger-top"></span>
                                <span class="block w-5 h-0.5 bg-current transform transition-all duration-300 origin-center mt-1" id="hamburger-middle"></span>
                                <span class="block w-5 h-0.5 bg-current transform transition-all duration-300 origin-center mt-1" id="hamburger-bottom"></span>
                            </div>
                        </button>
                    </div>

                    <!-- Mobile Menu Overlay -->
                    <div id="mobile-menu-overlay" class="fixed inset-0 bg-black/70 backdrop-blur-md z-40 lg:hidden hidden transition-opacity duration-300">
                        <div class="fixed top-0 right-0 h-full w-[88vw] max-w-sm bg-white shadow-2xl transform transition-transform duration-300 ease-out translate-x-full" id="mobile-menu">
                            <div class="flex flex-col h-full overflow-hidden">
                                <div class="flex items-center justify-between px-6 py-5 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200/50 sticky top-0 z-10 backdrop-blur-sm">
                                    <img src="${assetsPrefix}images/logo.svg" alt="MiHi Entertainment" class="h-10 w-auto drop-shadow-sm" />
                                    <button id="mobile-menu-close" class="p-2.5 rounded-xl hover:bg-gray-100 transition-all duration-200 text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                <div class="flex-1 overflow-y-auto px-5 py-6 space-y-3">
                                    ${mobileNav}
                                </div>
                            </div>
                        </div>
                    </div>
                </nav>
            </header>
            `;

            document.body.insertAdjacentHTML('afterbegin', navigationHTML);
            initMobileMenu();
            initCollapsibleSections();

        } catch (e) {
            console.error('Failed to load navigation:', e);
            // Fallback could be injected here if needed
        }
    }

    // Helper: Build Desktop Nav
    function buildDesktopNav(items, pathPrefix) {
        return items.map(item => {
            const hasChildren = item.children && item.children.length > 0;
            const url = resolveUrl(item.url, pathPrefix);

            // Special case for Contact Us button
            if (item.label === 'Contact Us') {
                return `<a href="${url}" class="btn-primary text-white px-6 py-3 rounded-full font-semibold transition-all duration-300 transform hover:scale-105 hover:opacity-90" style="background: #FF4F4F;">${item.label}</a>`;
            }

            if (!hasChildren) {
                return `<a href="${url}" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">${item.label}</a>`;
            }

            // Dropdown Logic
            // Determine grid columns based on number of direct children
            let widthClass = 'w-[56rem]';
            let gridClass = 'grid-cols-3';
            const childCount = item.children.length;

            if (childCount === 1) { widthClass = 'w-64'; gridClass = 'grid-cols-1'; }
            if (childCount === 2) { widthClass = 'w-[40rem]'; gridClass = 'grid-cols-2'; }

            // Build Dropdown Content
            const dropdownContent = item.children.map(child => {
                const subHasChildren = child.children && child.children.length > 0;

                if (subHasChildren || child.is_header) {
                    // Column Style (Header + Links)
                    // THEME LOGIC based on Label
                    const labelLower = child.label.toLowerCase();
                    let themeColor = 'blue'; // Default
                    if (labelLower.includes('video')) themeColor = 'purple';
                    if (labelLower.includes('experience') || labelLower.includes('additional')) themeColor = 'green';

                    const headerHtml = `<div class="mb-4 pb-3 border-b-2 border-${themeColor}-200">
                        <h4 class="font-bold text-base text-${themeColor}-600 uppercase tracking-wider">${child.label}</h4>
                    </div>`;

                    const linksHtml = (child.children || []).map(sub => {
                        // Check if it's a "View All" link
                        const isViewAll = sub.label.toLowerCase().includes('view all');
                        const linkClass = isViewAll
                            ? `block text-sm font-bold text-${themeColor}-600 hover:text-${themeColor}-800 transition mt-4`
                            : `block text-sm text-gray-700 hover:text-${themeColor}-600 transition py-1.5 hover:bg-${themeColor}-50 rounded-md px-2 -mx-2`;

                        return `<a href="${resolveUrl(sub.url, pathPrefix)}" class="${linkClass}">
                            <span class="${isViewAll ? '' : 'font-semibold block mb-0.5'}">${sub.label}</span>
                            ${sub.description ? `<span class="text-xs text-gray-500 leading-relaxed">${sub.description}</span>` : ''}
                        </a>`;
                    }).join('');

                    return `<div class="space-y-2.5">${headerHtml}${linksHtml}</div>`;
                } else {
                    // Direct Link Style
                    return `<a href="${resolveUrl(child.url, pathPrefix)}" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                         <span class="font-medium">${child.label}</span><br>
                         ${child.description ? `<span class="text-xs text-gray-500">${child.description}</span>` : ''}
                    </a>`;
                }
            }).join('');

            return `
            <div class="relative group">
                <a href="${url}" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">${item.label}</a>
                <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 ${widthClass} max-h-[90vh] overflow-y-auto bg-white rounded-xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 border border-gray-200">
                    <div class="p-6">
                        <div class="grid ${gridClass} gap-5">
                            ${dropdownContent}
                        </div>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    // Helper: Build Mobile Nav
    function buildMobileNav(items, pathPrefix) {
        return items.map((item, index) => {
            const hasChildren = item.children && item.children.length > 0;
            const url = resolveUrl(item.url, pathPrefix);

            // Special case for Contact Us button
            if (item.label === 'Contact Us') {
                return `<div class="pb-4 pt-2">
                    <a href="${url}" class="btn-primary text-white px-6 py-4 rounded-2xl font-bold transition-all duration-300 text-center block shadow-xl hover:shadow-2xl transform hover:scale-[1.02] hover:opacity-90" style="background: #FF4F4F;">${item.label}</a>
                 </div>`;
            }

            if (!hasChildren) {
                // Simple Mobile Link
                // Using random colors or fixed colors for the 'sidebar' accent line?
                // Default to blue
                return `<div class="bg-white rounded-2xl shadow-lg border border-gray-200/50 overflow-hidden mb-3">
                         <a href="${url}" class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50/50 transition-colors">
                            <span class="text-base font-bold text-gray-900">${item.label}</span>
                         </a>
                        </div>`;
            }

            // Accordion for Submenu
            const targetId = `mobile-menu-${item.id || index}`;
            const colors = ['blue', 'purple', 'green', 'pink']; // Cycles
            const color = colors[index % colors.length];

            // Build Submenu Items
            const childrenHtml = item.children.map(child => {
                const subHasChildren = child.children && child.children.length > 0;

                if (subHasChildren || child.is_header) {
                    // Header Group
                    const groupLinks = (child.children || []).map(sub =>
                        `<a href="${resolveUrl(sub.url, pathPrefix)}" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">${sub.label}</a>`
                    ).join('');

                    return `<div>
                        <div class="flex items-center gap-2.5 mb-3 py-2.5 px-4 rounded-lg bg-${color}-50/70 border border-${color}-200/50 -mx-1">
                            <span class="w-2 h-2 rounded-full bg-${color}-500 shadow-sm"></span>
                            <span class="text-xs font-bold text-${color}-700 uppercase tracking-widest">${child.label}</span>
                        </div>
                        <div class="space-y-1 pl-1">${groupLinks}</div>
                    </div>`;
                } else {
                    // Direct Link
                    return `<a href="${resolveUrl(child.url, pathPrefix)}" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">${child.label}</a>`;
                }
            }).join('');

            return `
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200/50 overflow-hidden">
                <button class="mobile-menu-toggle w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50/50 transition-colors group" data-target="${targetId}">
                    <div class="flex items-center gap-3">
                        <span class="w-1.5 h-6 bg-gradient-to-b from-${color}-500 to-${color}-400 rounded-full shadow-sm"></span>
                        <span class="text-base font-bold text-gray-900 group-hover:text-gray-700">${item.label}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-300 transform rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="${targetId}" class="hidden overflow-hidden transition-all duration-300 ease-in-out" style="max-height: 0px;">
                    <div class="px-5 pb-4 space-y-4">
                        ${childrenHtml}
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    function resolveUrl(url, pathPrefix) {
        if (!url) return 'javascript:void(0)';
        if (url.startsWith('http') || url.startsWith('#')) return url;
        // If url is relative like 'product/foo.html' and we are at root -> 'product/foo.html'
        // If we are deep -> '../product/foo.html'
        return pathPrefix + url;
    }

    // Logic for Mobile Menu Interactivity (Hamburger, Collapsibles)
    function initMobileMenu() {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const mobileMenu = document.getElementById('mobile-menu');
        const hamburgerTop = document.getElementById('hamburger-top');
        const hamburgerMiddle = document.getElementById('hamburger-middle');
        const hamburgerBottom = document.getElementById('hamburger-bottom');

        if (!mobileMenuBtn || !mobileMenuOverlay || !mobileMenu) return;

        function animateHamburger(isOpen) {
            if (!hamburgerTop || !hamburgerMiddle || !hamburgerBottom) return;
            if (isOpen) {
                hamburgerTop.style.transform = 'rotate(45deg) translate(5px, 5px)';
                hamburgerMiddle.style.opacity = '0';
                hamburgerBottom.style.transform = 'rotate(-45deg) translate(7px, -6px)';
            } else {
                hamburgerTop.style.transform = 'rotate(0deg) translate(0px, 0px)';
                hamburgerMiddle.style.opacity = '1';
                hamburgerBottom.style.transform = 'rotate(0deg) translate(0px, 0px)';
            }
        }

        let menuOpen = false;

        function toggleMenu() {
            if (!menuOpen) {
                mobileMenuOverlay.classList.remove('hidden');
                requestAnimationFrame(() => {
                    mobileMenu.classList.remove('translate-x-full');
                    animateHamburger(true);
                });
                document.body.style.overflow = 'hidden';
                menuOpen = true;
            } else {
                mobileMenu.classList.add('translate-x-full');
                animateHamburger(false);
                setTimeout(() => {
                    mobileMenuOverlay.classList.add('hidden');
                }, 300);
                document.body.style.overflow = '';
                menuOpen = false;
            }
        }

        function closeMenu() {
            mobileMenu.classList.add('translate-x-full');
            animateHamburger(false);
            setTimeout(() => {
                mobileMenuOverlay.classList.add('hidden');
            }, 300);
            document.body.style.overflow = '';
            menuOpen = false;
        }

        mobileMenuBtn.onclick = (e) => { e.preventDefault(); e.stopPropagation(); toggleMenu(); };
        mobileMenuBtn.ontouchend = (e) => { e.preventDefault(); e.stopPropagation(); toggleMenu(); };
        if (mobileMenuClose) mobileMenuClose.onclick = (e) => { e.preventDefault(); closeMenu(); };
        mobileMenuOverlay.onclick = (e) => { if (e.target === mobileMenuOverlay) closeMenu(); };
    }

    function initCollapsibleSections() {
        const toggleButtons = document.querySelectorAll('.mobile-menu-toggle');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const targetId = this.getAttribute('data-target');
                const content = document.getElementById(targetId);
                const chevron = this.querySelector('svg');

                if (!content) return;

                const isHidden = content.classList.contains('hidden');

                if (isHidden) {
                    content.classList.remove('hidden');
                    const height = content.scrollHeight;
                    content.style.maxHeight = '0px';
                    content.style.opacity = '0';
                    requestAnimationFrame(() => {
                        content.style.transition = 'max-height 0.3s ease-in-out, opacity 0.3s ease-in-out';
                        content.style.maxHeight = height + 'px';
                        content.style.opacity = '1';
                    });
                    if (chevron) { chevron.style.transition = 'transform 0.3s ease-in-out'; chevron.style.transform = 'rotate(180deg)'; }
                } else {
                    content.style.maxHeight = '0px';
                    content.style.opacity = '0';
                    if (chevron) chevron.style.transform = 'rotate(0deg)';
                    setTimeout(() => {
                        content.classList.add('hidden');
                    }, 300);
                }
            });
        });
    }

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavigation);
    } else {
        initNavigation();
    }
})();