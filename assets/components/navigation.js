// Shared Navigation Component
// This script will load the same navigation on all pages

document.addEventListener('DOMContentLoaded', function() {
    // Determine the correct path prefix by finding the navigation script's location
    const scripts = document.getElementsByTagName('script');
    let navScriptSrc = '';

    // Find the navigation script
    for (let script of scripts) {
        if (script.src && script.src.includes('navigation.js')) {
            navScriptSrc = script.src;
            break;
        }
    }

    // Calculate relative path from the script location to the root
    let pathPrefix = '';
    if (navScriptSrc) {
        const scriptUrl = new URL(navScriptSrc);
        const scriptPath = scriptUrl.pathname;

        // Remove 'assets/components/navigation.js' from the path to get to root
        const rootPath = scriptPath.replace('/assets/components/navigation.js', '');
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

    const assetsPrefix = pathPrefix + 'assets/';

    const navigationHTML = `
    <header class="bg-white shadow-lg sticky top-0 z-50">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <!-- Logo -->
            <a href="${pathPrefix}index.html" class="flex items-center" aria-label="Go to home">
                <img src="${assetsPrefix}images/logo_black.png" alt="MiHi Entertainment" class="h-12 md:h-16 w-auto" />
            </a>
            
            <!-- Desktop Nav -->
            <div class="hidden lg:flex items-center space-x-8">
                <!-- Services Dropdown -->
                <div class="relative group">
                    <a href="${pathPrefix}index.html#products" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300 flex items-center">
                        Services
                        <svg class="w-4 h-4 ml-1 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </a>
                    <div class="absolute top-full left-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 border border-gray-100">
                        <div class="p-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-3">
                                    <h4 class="font-bold text-gray-900 text-sm mb-3 text-blue-600">Photo Booths</h4>
                                    <a href="${pathPrefix}services/photobooth/ai-photo-booth.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">AI Photo Booth</span><br>
                                        <span class="text-xs text-gray-500">Custom AI-generated characters in seconds</span>
                                    </a>
                                    <a href="${pathPrefix}services/photobooth/green-screen-booth.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Green Screen</span><br>
                                        <span class="text-xs text-gray-500">Transport guests anywhere with magic backdrops</span>
                                    </a>
                                    <a href="${pathPrefix}services/photobooth/rosie-robot.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Rosie the Robot</span><br>
                                        <span class="text-xs text-gray-500">Autonomous roaming robot photo booth</span>
                                    </a>
                                    <a href="${pathPrefix}index.html#photo-booths" class="block text-sm text-gray-600 hover:text-blue-600 transition font-semibold">View All →</a>
                                </div>
                                <div class="space-y-3">
                                    <h4 class="font-bold text-gray-900 text-sm mb-3 text-purple-600">Video Booths</h4>
                                    <a href="${pathPrefix}services/videobooth/360-video-booth.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">360 Video Booth</span><br>
                                        <span class="text-xs text-gray-500">Epic, shareable videos from every angle</span>
                                    </a>
                                    <a href="${pathPrefix}services/videobooth/bullet-time-array.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Bullet-Time Array</span><br>
                                        <span class="text-xs text-gray-500">Matrix-style multi-camera effects</span>
                                    </a>
                                    <a href="${pathPrefix}services/videobooth/glambot-video.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">GlamBot Video</span><br>
                                        <span class="text-xs text-gray-500">Cinematic, automated slow pans</span>
                                    </a>
                                    <a href="${pathPrefix}index.html#video-booths" class="block text-sm text-gray-600 hover:text-blue-600 transition font-semibold">View All →</a>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <h4 class="font-bold text-gray-900 text-sm mb-3 text-green-600">Additional Experiences</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <a href="${pathPrefix}services/experiences/event-photography.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Event Photography</span><br>
                                        <span class="text-xs text-gray-500">Professional coverage of every moment</span>
                                    </a>
                                    <a href="${pathPrefix}services/experiences/sketchbot.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">SketchBot</span><br>
                                        <span class="text-xs text-gray-500">Live robot-drawn portraits</span>
                                    </a>
                                    <a href="${pathPrefix}services/experiences/cookie-printer.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Cookie Printer</span><br>
                                        <span class="text-xs text-gray-500">Edible photo cookies on demand</span>
                                    </a>
                                    <a href="${pathPrefix}services/experiences/pose-cards.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Pose Cards</span><br>
                                        <span class="text-xs text-gray-500">Signature pose flashcards for guests</span>
                                    </a>
                                    <a href="${pathPrefix}services/experiences/lux-photography.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Lux Photography</span><br>
                                        <span class="text-xs text-gray-500">An elevated photography booth that leaves you and your guests feeling luxurious</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Event Types -->
                <div class="relative group">
                    <a href="${pathPrefix}index.html#event-types" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300 flex items-center">
                        Events
                        <svg class="w-4 h-4 ml-1 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </a>
                    <div class="absolute top-full left-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 border border-gray-100">
                        <div class="p-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-3">
                                    <a href="${pathPrefix}events/weddings.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Weddings</span><br>
                                        <span class="text-xs text-gray-500">Make your special day unforgettable</span>
                                    </a>
                                    <a href="${pathPrefix}events/corporate-events.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Corporate Events</span><br>
                                        <span class="text-xs text-gray-500">Fully branded experiences for your brand</span>
                                    </a>
                                    <a href="${pathPrefix}events/social-events.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Social Events</span><br>
                                        <span class="text-xs text-gray-500">Birthdays, Mitzvahs, and more</span>
                                    </a>
                                </div>
                                <div class="space-y-3">
                                    <a href="${pathPrefix}events/trade-shows.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Trade Shows</span><br>
                                        <span class="text-xs text-gray-500">Mosaic walls and high-impact activations</span>
                                    </a>
                                    <a href="${pathPrefix}events/holiday-parties.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Holiday Parties</span><br>
                                        <span class="text-xs text-gray-500">Curated sets that come to life</span>
                                    </a>
                                    <a href="${pathPrefix}events/casino-parties.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Casino Parties</span><br>
                                        <span class="text-xs text-gray-500">Tables, dealers, and full experiences</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rentals -->
                <div class="relative group">
                    <a href="${pathPrefix}index.html#rentals" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300 flex items-center">
                        Rentals
                        <svg class="w-4 h-4 ml-1 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </a>
                    <div class="absolute top-full left-0 mt-2 w-72 bg-white rounded-2xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 border border-gray-100">
                        <div class="p-6">
                            <div class="space-y-4">
                                <div>
                                    <h4 class="font-bold text-gray-900 text-sm mb-2 text-blue-600">AV Services</h4>
                                    <div class="space-y-2">
                                        <a href="${pathPrefix}rentals/av-services/audio-visual.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                            <span class="font-medium">Audio & Visual</span><br>
                                            <span class="text-xs text-gray-500">Pro sound, screens, and mixing</span>
                                        </a>
                                        <a href="${pathPrefix}rentals/av-services/lighting.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                            <span class="font-medium">Lighting</span><br>
                                            <span class="text-xs text-gray-500">Transform spaces with lighting</span>
                                        </a>
                                        <a href="${pathPrefix}rentals/av-services/stages.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                            <span class="font-medium">Stages</span><br>
                                            <span class="text-xs text-gray-500">Professional stage setups</span>
                                        </a>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900 text-sm mb-2 text-purple-600">Event Decor</h4>
                                    <div class="space-y-2">
                                        <a href="${pathPrefix}rentals/event-decor/special-effects.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                            <span class="font-medium">Special Effects</span><br>
                                            <span class="text-xs text-gray-500">Sparks, snow, confetti, champagne walls</span>
                                        </a>
                                        <a href="${pathPrefix}rentals/event-decor/lighting-decor.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                            <span class="font-medium">Lighting Decor</span><br>
                                            <span class="text-xs text-gray-500">Chandeliers, market lights, marquee</span>
                                        </a>
                                        <a href="${pathPrefix}rentals/event-decor/furniture.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                            <span class="font-medium">Furniture</span><br>
                                            <span class="text-xs text-gray-500">Lounge sets, shimmer walls, drape</span>
                                        </a>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900 text-sm mb-2 text-green-600">Games</h4>
                                    <div class="space-y-2">
                                        <a href="${pathPrefix}rentals/games/claw-machine.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                            <span class="font-medium">Claw Machine</span><br>
                                            <span class="text-xs text-gray-500">Walk up and win a prize</span>
                                        </a>
                                        <a href="${pathPrefix}rentals/games/vr-headsets.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                            <span class="font-medium">VR Headsets</span><br>
                                            <span class="text-xs text-gray-500">Immersive virtual experiences</span>
                                        </a>
                                        <a href="${pathPrefix}rentals/games/money-booth.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                            <span class="font-medium">Money Booth</span><br>
                                            <span class="text-xs text-gray-500">Grab the floating cash</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Simple Links -->
                <a href="${pathPrefix}index.html#galleries" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">Gallery</a>
                <a href="${pathPrefix}index.html#about" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">About</a>

                <!-- CTA Button -->
                <a href="${pathPrefix}index.html#contact" class="btn-primary text-white px-6 py-3 rounded-full font-semibold shadow-lg hover:shadow-xl transition-all duration-300">
                    Contact Us
                </a>
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
            <div id="mobile-menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden">
                <div class="fixed top-0 right-0 h-full w-80 max-w-[90vw] bg-white shadow-2xl transform transition-transform duration-300 ease-in-out translate-x-full" id="mobile-menu">
                    <div class="flex flex-col h-full">
                        <!-- Mobile Menu Header -->
                        <div class="flex items-center justify-between p-6 border-b border-gray-200">
                            <img src="${assetsPrefix}images/logo_black.png" alt="MiHi Entertainment" class="h-10 w-auto" />
                            <button id="mobile-menu-close" class="text-gray-500 hover:text-gray-700 transition duration-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Mobile Menu Content -->
                        <div class="flex-1 overflow-y-auto p-6">
                            <!-- Mobile Services Section -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">Services</h3>
                                <div class="space-y-3">
                                    <div>
                                        <h4 class="font-semibold text-gray-800 mb-2">Photo Booths</h4>
                                        <div class="pl-4 space-y-2">
                                            <a href="${pathPrefix}services/photobooth/ai-photo-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">AI Photo Booth</a>
                                            <a href="${pathPrefix}services/photobooth/green-screen-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Green Screen</a>
                                            <a href="${pathPrefix}services/photobooth/rosie-robot.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Rosie the Robot</a>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 mb-2">Video Booths</h4>
                                        <div class="pl-4 space-y-2">
                                            <a href="${pathPrefix}services/videobooth/360-video-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">360 Video Booth</a>
                                            <a href="${pathPrefix}services/videobooth/bullet-time-array.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Bullet-Time Array</a>
                                            <a href="${pathPrefix}services/videobooth/glambot-video.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">GlamBot Video</a>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 mb-2">Additional Experiences</h4>
                                        <div class="pl-4 space-y-2">
                                            <a href="${pathPrefix}services/experiences/event-photography.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Event Photography</a>
                                            <a href="${pathPrefix}services/experiences/sketchbot.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">SketchBot</a>
                                            <a href="${pathPrefix}services/experiences/cookie-printer.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Cookie Printer</a>
                                            <a href="${pathPrefix}services/experiences/pose-cards.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Pose Cards</a>
                                            <a href="${pathPrefix}services/experiences/lux-photography.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Lux Photography</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Events Section -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">Events</h3>
                                <div class="space-y-2">
                                    <a href="${pathPrefix}events/weddings.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Weddings</a>
                                    <a href="${pathPrefix}events/corporate-events.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Corporate Events</a>
                                    <a href="${pathPrefix}events/social-events.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Social Events</a>
                                    <a href="${pathPrefix}events/trade-shows.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Trade Shows</a>
                                    <a href="${pathPrefix}events/holiday-parties.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Holiday Parties</a>
                                    <a href="${pathPrefix}events/casino-parties.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Casino Parties</a>
                                </div>
                            </div>

                            <!-- Mobile Rentals Section -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">Rentals</h3>
                                <div class="space-y-3">
                                    <div>
                                        <h4 class="font-semibold text-gray-800 mb-2">AV Services</h4>
                                        <div class="pl-4 space-y-2">
                                            <a href="${pathPrefix}rentals/av-services/audio-visual.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Audio & Visual</a>
                                            <a href="${pathPrefix}rentals/av-services/lighting.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Lighting</a>
                                            <a href="${pathPrefix}rentals/av-services/stages.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Stages</a>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 mb-2">Event Decor</h4>
                                        <div class="pl-4 space-y-2">
                                            <a href="${pathPrefix}rentals/event-decor/special-effects.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Special Effects</a>
                                            <a href="${pathPrefix}rentals/event-decor/lighting-decor.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Lighting Decor</a>
                                            <a href="${pathPrefix}rentals/event-decor/furniture.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Furniture</a>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 mb-2">Games</h4>
                                        <div class="pl-4 space-y-2">
                                            <a href="${pathPrefix}rentals/games/claw-machine.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Claw Machine</a>
                                            <a href="${pathPrefix}rentals/games/vr-headsets.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">VR Headsets</a>
                                            <a href="${pathPrefix}rentals/games/money-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Money Booth</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Simple Links -->
                            <div class="space-y-4">
                                <a href="${pathPrefix}index.html#galleries" class="block text-gray-700 hover:text-blue-600 font-medium transition duration-300">Gallery</a>
                                <a href="${pathPrefix}index.html#about" class="block text-gray-700 hover:text-blue-600 font-medium transition duration-300">About</a>
                                <a href="${pathPrefix}index.html#contact" class="btn-primary text-white px-6 py-3 rounded-full font-semibold shadow-lg hover:shadow-xl transition-all duration-300 text-center block">Contact Us</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    `;

    // Insert navigation at the beginning of body
    document.body.insertAdjacentHTML('afterbegin', navigationHTML);

    // Mobile menu functionality - use setTimeout to ensure DOM is updated
    setTimeout(() => {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const mobileMenu = document.getElementById('mobile-menu');
        const hamburgerTop = document.getElementById('hamburger-top');
        const hamburgerMiddle = document.getElementById('hamburger-middle');
        const hamburgerBottom = document.getElementById('hamburger-bottom');

    function animateHamburger(isOpen) {
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

    if (mobileMenuBtn && mobileMenuOverlay && mobileMenu) {
        let menuOpen = false;

        function toggleMenu() {
            if (!menuOpen) {
                // Open menu
                mobileMenuOverlay.classList.remove('hidden');
                setTimeout(() => {
                    mobileMenu.classList.remove('translate-x-full');
                    animateHamburger(true);
                }, 10);
                document.body.style.overflow = 'hidden';
                menuOpen = true;
            } else {
                // Close menu
                mobileMenu.classList.add('translate-x-full');
                animateHamburger(false);
                setTimeout(() => {
                    mobileMenuOverlay.classList.add('hidden');
                }, 300);
                document.body.style.overflow = '';
                menuOpen = false;
            }
        }

        // Add click listener
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleMenu();
        });

        // Add touch listener for mobile
        mobileMenuBtn.addEventListener('touchend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleMenu();
        });

        // Close mobile menu
        function closeMenu() {
            mobileMenu.classList.add('translate-x-full');
            animateHamburger(false);
            setTimeout(() => {
                mobileMenuOverlay.classList.add('hidden');
            }, 300);
            document.body.style.overflow = '';
            menuOpen = false;
        }

        mobileMenuClose.addEventListener('click', function(e) {
            e.preventDefault();
            closeMenu();
        });

        // Close mobile menu when clicking overlay
        mobileMenuOverlay.addEventListener('click', function(e) {
            if (e.target === mobileMenuOverlay) {
                closeMenu();
            }
        });

        // Close mobile menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && menuOpen) {
                closeMenu();
            }
        });
    }
    }, 10); // Small delay to ensure DOM is updated
});
