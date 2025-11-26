// Shared Footer Component
// This script will load the same footer on all pages

document.addEventListener('DOMContentLoaded', function() {
    // Determine the correct path prefix by finding the footer script's location
    const scripts = document.getElementsByTagName('script');
    let footerScriptSrc = '';

    // Find the footer script
    for (let script of scripts) {
        if (script.src && script.src.includes('footer.js')) {
            footerScriptSrc = script.src;
            break;
        }
    }

    // Calculate relative path from the script location to the root
    let pathPrefix = '';
    if (footerScriptSrc) {
        const scriptUrl = new URL(footerScriptSrc);
        const scriptPath = scriptUrl.pathname;

        // Remove 'assets/components/footer.js' from the path to get to root
        const rootPath = scriptPath.replace('/assets/components/footer.js', '');
        const rootSegments = rootPath.split('/').filter(segment => segment.length > 0);
        pathPrefix = '../'.repeat(rootSegments.length);
    }

    // Fallback: count directory levels in current path
    if (!pathPrefix) {
        const currentPath = window.location.pathname;
        const pathParts = currentPath.split('/').filter(part => part.length > 0 && !part.includes('.html'));
        const directoryDepth = pathParts.length;
        pathPrefix = directoryDepth > 0 ? '../'.repeat(directoryDepth) : '';
    }

    const footerHTML = `
    <footer class="bg-gradient-to-b from-gray-900 to-black text-gray-400">
        <!-- Main Footer Content -->
        <div class="container mx-auto px-4 sm:px-6 py-8 md:py-16">
            <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-5 gap-6 sm:gap-8 md:gap-12 lg:gap-8">
                <!-- Column 1: Logo & Social -->
                <div class="col-span-2 lg:col-span-2 mb-6 md:mb-0">
                    <img src="${pathPrefix}assets/images/logo_w.svg" alt="MiHi Entertainment" class="h-10 sm:h-12 md:h-14 mb-4 md:mb-8" />
                    <p class="mb-4 md:mb-8 text-gray-300 leading-relaxed max-w-md text-sm md:text-base">Unforgettable events, effortless experiences. Nationwide service with premium quality and exceptional customer care.</p>
                    
                    <!-- Premium Social Media Icons -->
                    <div class="flex gap-2 sm:gap-3">
                        <!-- Instagram -->
                        <a href="https://www.instagram.com/mihi_entertainment/" target="_blank" rel="noopener noreferrer" class="group relative" aria-label="Instagram">
                            <div class="w-10 h-10 md:w-11 md:h-11 bg-gradient-to-br from-purple-600 via-pink-600 to-orange-500 rounded-xl flex items-center justify-center transform transition-all duration-300 group-hover:scale-110 group-hover:rotate-6 shadow-lg group-hover:shadow-xl group-hover:shadow-pink-500/50">
                                <svg class="w-4 h-4 md:w-5 md:h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.148 3.227-1.669 4.771-4.919 4.919-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-3.252-.148-4.771-1.691-4.919-4.919-.058-1.265-.07-1.646-.07-4.85s.012-3.584.07-4.85c.148-3.227 1.669-4.771 4.919-4.919C8.416 2.175 8.796 2.163 12 2.163m0-2.163C8.74 0 8.333.012 7.053.072 2.695.272.273 2.69.073 7.052.012 8.333 0 8.74 0 12s.012 3.667.072 4.947c.2 4.358 2.618 6.78 6.98 6.98C8.333 23.988 8.74 24 12 24s3.667-.012 4.947-.072c4.354-.2 6.782-2.618 6.979-6.98.06-1.28.072-1.687.072-4.947s-.012-3.667-.072-4.947C23.728 2.69 21.306.27 16.947.072 15.667.012 15.26 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405a1.44 1.44 0 11-2.88 0 1.44 1.44 0 012.88 0z"/>
                                </svg>
                            </div>
                        </a>
                        
                        <!-- Facebook -->
                        <a href="https://www.facebook.com/mihientertainment" target="_blank" rel="noopener noreferrer" class="group relative" aria-label="Facebook">
                            <div class="w-10 h-10 md:w-11 md:h-11 bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl flex items-center justify-center transform transition-all duration-300 group-hover:scale-110 group-hover:rotate-6 shadow-lg group-hover:shadow-xl group-hover:shadow-blue-500/50">
                                <svg class="w-4 h-4 md:w-5 md:h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M22.675 0h-21.35C.59 0 0 .59 0 1.325v21.35C0 23.41.59 24 1.325 24H12.82v-9.29H9.69v-3.62h3.13V8.41c0-3.1 1.89-4.79 4.66-4.79 1.32 0 2.46.1 2.79.14v3.24h-1.9c-1.5 0-1.79.71-1.79 1.76v2.31h3.59l-.47 3.62h-3.12V24h5.7c.73 0 1.32-.59 1.32-1.32V1.32C24 .59 23.41 0 22.675 0z"/>
                                </svg>
                            </div>
                        </a>
                        
                        <!-- YouTube -->
                        <a href="https://www.youtube.com/@mihientertainment5959" target="_blank" rel="noopener noreferrer" class="group relative" aria-label="YouTube">
                            <div class="w-10 h-10 md:w-11 md:h-11 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center transform transition-all duration-300 group-hover:scale-110 group-hover:rotate-6 shadow-lg group-hover:shadow-xl group-hover:shadow-red-500/50">
                                <svg class="w-4 h-4 md:w-5 md:h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                            </div>
                        </a>
                        
                        <!-- LinkedIn -->
                        <a href="https://www.linkedin.com/company/mihientertainment/" target="_blank" rel="noopener noreferrer" class="group relative" aria-label="LinkedIn">
                            <div class="w-10 h-10 md:w-11 md:h-11 bg-gradient-to-br from-blue-700 to-blue-900 rounded-xl flex items-center justify-center transform transition-all duration-300 group-hover:scale-110 group-hover:rotate-6 shadow-lg group-hover:shadow-xl group-hover:shadow-blue-600/50">
                                <svg class="w-4 h-4 md:w-5 md:h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                            </div>
                        </a>
                    </div>
                </div>
                
                <!-- Column 2: Services -->
                <div class="col-span-1">
                    <h5 class="text-white font-bold text-base md:text-lg mb-3 md:mb-6">Services</h5>
                    <ul class="space-y-2 md:space-y-3">
                        <li><a href="${pathPrefix}index.html#photo-booths" class="hover:text-white transition-colors duration-300 text-sm md:text-base">Photo Booths</a></li>
                        <li><a href="${pathPrefix}index.html#video-booths" class="hover:text-white transition-colors duration-300 text-sm md:text-base">Video Booths</a></li>
                        <li><a href="${pathPrefix}index.html#additional-experiences" class="hover:text-white transition-colors duration-300 text-sm md:text-base">Additional Experiences</a></li>
                        <li><a href="${pathPrefix}index.html#rentals" class="hover:text-white transition-colors duration-300 text-sm md:text-base">Event Production</a></li>
                        <li><a href="${pathPrefix}index.html#rentals" class="hover:text-white transition-colors duration-300 text-sm md:text-base">AV & Decor</a></li>
                    </ul>
                </div>
                
                <!-- Column 3: Events -->
                <div class="col-span-1">
                    <h5 class="text-white font-bold text-base md:text-lg mb-3 md:mb-6">Events</h5>
                    <ul class="space-y-2 md:space-y-3">
                        <li><a href="${pathPrefix}events/weddings.html" class="hover:text-white transition-colors duration-300 text-sm md:text-base">Weddings</a></li>
                        <li><a href="${pathPrefix}events/corporate-events.html" class="hover:text-white transition-colors duration-300 text-sm md:text-base">Corporate</a></li>
                        <li><a href="${pathPrefix}events/social-events.html" class="hover:text-white transition-colors duration-300 text-sm md:text-base">Social Events</a></li>
                        <li><a href="${pathPrefix}events/trade-shows.html" class="hover:text-white transition-colors duration-300 text-sm md:text-base">Trade Shows</a></li>
                        <li><a href="${pathPrefix}events/holiday-parties.html" class="hover:text-white transition-colors duration-300 text-sm md:text-base">Holiday Parties</a></li>
                    </ul>
                </div>
                
                <!-- Column 4: Quick Links -->
                <div class="col-span-2 md:col-span-1 mt-6 md:mt-0">
                    <h5 class="text-white font-bold text-base md:text-lg mb-3 md:mb-6">Company</h5>
                    <ul class="space-y-2 md:space-y-3">
                        <li><a href="${pathPrefix}index.html#about" class="hover:text-white transition-colors duration-300 text-sm md:text-base">About Us</a></li>
                        <li><a href="${pathPrefix}index.html#galleries" class="hover:text-white transition-colors duration-300 text-sm md:text-base">Gallery</a></li>
                        <li><a href="${pathPrefix}index.html#contact" class="hover:text-white transition-colors duration-300 text-sm md:text-base">Get a Quote</a></li>
                        <li><a href="${pathPrefix}about/blog.html" class="hover:text-white transition-colors duration-300 text-sm md:text-base">Blog</a></li>
                        <li><a href="${pathPrefix}about/faq.html" class="hover:text-white transition-colors duration-300 text-sm md:text-base">FAQ</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Contact Bar -->
        <div class="border-t border-gray-800">
            <div class="container mx-auto px-4 sm:px-6 py-4 md:py-6">
                <div class="flex flex-col sm:flex-row flex-wrap justify-center gap-3 sm:gap-x-6 md:gap-x-8 gap-y-2 sm:gap-y-3 text-xs sm:text-sm">
                    <a href="https://www.mihiphotobooth.com" target="_blank" class="flex items-center gap-2 text-gray-300 hover:text-white transition-colors justify-center sm:justify-start">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                        <span class="break-all sm:break-normal">www.mihiphotobooth.com</span>
                    </a>
                    <button type="button" data-click-to-call data-call-text="Call Us" class="flex items-center gap-2 text-gray-300 hover:text-white transition-colors justify-center sm:justify-start bg-transparent border-none cursor-pointer" aria-label="Call us">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span>Call Us</span>
                    </button>
                    <a href="mailto:saras@mihientertainment.com" class="flex items-center gap-2 text-gray-300 hover:text-white transition-colors justify-center sm:justify-start">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 text-purple-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="break-all sm:break-normal">saras@mihientertainment.com</span>
                    </a>
                    <div class="flex items-start gap-2 text-gray-300 justify-center sm:justify-start">
                        <svg class="w-3 h-3 sm:w-4 sm:h-4 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-left">5135 W 58th Ave. Unit 5, Arvada, CO 80002</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bottom Bar -->
        <div class="border-t border-gray-800">
            <div class="container mx-auto px-4 sm:px-6 py-3 md:py-5">
                <div class="flex flex-col md:flex-row justify-between items-center gap-3 md:gap-4 text-xs sm:text-sm text-gray-400">
                    <p class="text-center md:text-left leading-relaxed">
                        ©2025 MiHi Entertainment | All Rights Reserved | Nationwide Photo Booth Rentals
                    </p>
                    <div class="flex gap-4 sm:gap-6 flex-wrap justify-center md:justify-end">
                        <a href="#" class="hover:text-white transition-colors whitespace-nowrap">Sitemap</a>
                        <a href="${pathPrefix}privacy-policy.html" class="hover:text-white transition-colors whitespace-nowrap">Privacy</a>
                        <a href="${pathPrefix}terms.html" class="hover:text-white transition-colors whitespace-nowrap">Terms</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    `;

    // Insert footer before closing body tag
    document.body.insertAdjacentHTML('beforeend', footerHTML);
    
    // Load phone protection script if not already loaded
    if (!document.querySelector('script[src*="phone-protection.js"]')) {
        const phoneScript = document.createElement('script');
        // Use the same path prefix as footer assets
        phoneScript.src = pathPrefix + 'assets/js/phone-protection.js';
        phoneScript.async = true;
        document.body.appendChild(phoneScript);
    }
});
