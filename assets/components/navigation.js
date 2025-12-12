// Shared Navigation Component
// This script will load the same navigation on all pages

(function() {
    // Function to initialize navigation
    function initNavigation() {
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

    // Add minimal global style to protect navigation from page-specific CSS
    const style = document.createElement('style');
    style.textContent = `
        /* Navigation Protection - Only essential positioning protection */
        header {
            z-index: 50 !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            /* Use standard font instead of inheriting page font */
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
            font-size: inherit !important;
        }
        
        header nav,
        header nav * {
            /* Use standard font instead of inheriting page font */
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
            font-style: normal !important;
        }
        
        /* Protect mobile menu z-index */
        #mobile-menu-overlay {
            z-index: 40 !important;
        }
        
        #mobile-menu {
            z-index: 41 !important;
        }
        
        /* Protect Contact Us button coral color from page overrides */
        header .btn-primary[style*="#FF4F4F"],
        nav .btn-primary[style*="#FF4F4F"],
        #mobile-menu .btn-primary[style*="#FF4F4F"] {
            background: #FF4F4F !important;
            color: #ffffff !important;
        }
        
        header .btn-primary[style*="#FF4F4F"]:hover,
        nav .btn-primary[style*="#FF4F4F"]:hover,
        #mobile-menu .btn-primary[style*="#FF4F4F"]:hover {
            background: #FF4F4F !important;
            opacity: 0.9 !important;
        }
    `;
    if (!document.getElementById('navigation-protection')) {
        style.id = 'navigation-protection';
        document.head.appendChild(style);
    }

    const navigationHTML = `
    <header class="bg-white shadow-lg fixed top-0 left-0 right-0 z-50">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <!-- Logo -->
            <a href="${pathPrefix}index.html" class="flex items-center" aria-label="Go to home">
                <img src="${assetsPrefix}images/logo.svg" alt="MiHi Entertainment" class="h-12 md:h-16 w-auto" />
            </a>
            
            <!-- Desktop Nav -->
            <div class="hidden lg:flex items-center space-x-8">
                <!-- Products Dropdown -->
                <div class="relative group">
                    <a href="${pathPrefix}index.html#products" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">
                        Products
                    </a>
                    <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-[56rem] max-h-[90vh] overflow-y-auto bg-white rounded-xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 border border-gray-200">
                        <div class="p-6">
                            <div class="grid grid-cols-3 gap-5">
                                <!-- Photo Booths Column -->
                                <div class="space-y-2.5">
                                    <div class="mb-4 pb-3 border-b-2 border-blue-200">
                                        <h4 class="font-bold text-base text-blue-600 uppercase tracking-wider">Photo Booths</h4>
                                    </div>
                                    <a href="${pathPrefix}product/ai-photo-booth.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">AI Photo Booth</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Custom AI-generated characters in seconds</span>
                                    </a>
                                    <a href="${pathPrefix}product/green-screen-photo-booth.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Green Screen</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Transport guests anywhere with magic backdrops</span>
                                    </a>
                                    <a href="${pathPrefix}product/rosie-the-robot-photo-booth.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Rosie the Robot</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Autonomous roaming robot photo booth</span>
                                    </a>
                                    <a href="${pathPrefix}product/graffiti-wall-photo-booth.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Graffiti Wall Photo Booth</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Paint and create digital art with your photos</span>
                                    </a>
                                    <a href="${pathPrefix}product/mosaic-photo-booth.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Mosaic Photo Booth</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Your event's story, built tile-by-tile with guest photos</span>
                                    </a>
                                    <a href="${pathPrefix}product/roaming-photo-booth.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Roaming Photo Booth</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">The booth that comes to you, anywhere at your event</span>
                                    </a>
                                    <a href="${pathPrefix}product/virtual-photo-booth.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Virtual Photo Booth</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Snap, pose, and share—no app required, all online</span>
                                    </a>
                                    <a href="${pathPrefix}product/professional-headshots.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Professional Headshots</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Studio-quality headshots, on-site and effortless</span>
                                    </a>
                                    <a href="${pathPrefix}photo-booth-sets.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Custom Photo Booth</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Fully branded, immersive photo set designs made just for you</span>
                                    </a>
                                    <a href="${pathPrefix}index.html#photo-booths" class="block text-xs text-blue-600 hover:text-blue-700 transition font-semibold mt-3 pt-3 border-t border-gray-200">View All →</a>
                                </div>
                                
                                <!-- Video Booths Column -->
                                <div class="space-y-2.5">
                                    <div class="mb-4 pb-3 border-b-2 border-purple-200">
                                        <h4 class="font-bold text-base text-purple-600 uppercase tracking-wider">Video Booths</h4>
                                    </div>
                                    <a href="${pathPrefix}product/360-photo-booth.html" class="block text-sm text-gray-700 hover:text-purple-600 transition py-1.5 hover:bg-purple-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">360 Video Booth</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Epic, shareable videos from every angle</span>
                                    </a>
                                    <a href="${pathPrefix}product/bullet-time-booth.html" class="block text-sm text-gray-700 hover:text-purple-600 transition py-1.5 hover:bg-purple-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Bullet-Time Array</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Matrix-style multi-camera effects</span>
                                    </a>
                                    <a href="${pathPrefix}product/glambot-photo-booth.html" class="block text-sm text-gray-700 hover:text-purple-600 transition py-1.5 hover:bg-purple-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">GlamBot Video</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Cinematic, automated slow pans</span>
                                    </a>
                                    <a href="${pathPrefix}product/vogue-photo-booth.html" class="block text-sm text-gray-700 hover:text-purple-600 transition py-1.5 hover:bg-purple-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Vogue Video Booth</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Studio-quality headshots, on-site and effortless</span>
                                    </a>
                                    <a href="${pathPrefix}product/slow-motion-booth.html" class="block text-sm text-gray-700 hover:text-purple-600 transition py-1.5 hover:bg-purple-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Slow Motion Video Booth</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Cinematic slow-motion videos, on-site and effortless</span>
                                    </a>
                                    <a href="${pathPrefix}product/video-testimonial-booth.html" class="block text-sm text-gray-700 hover:text-purple-600 transition py-1.5 hover:bg-purple-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Video Testimonial Booth</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Authentic customer testimonials, captured on-site</span>
                                    </a>
                                    <a href="${pathPrefix}index.html#video-booths" class="block text-xs text-purple-600 hover:text-purple-700 transition font-semibold mt-3 pt-3 border-t border-gray-200">View All →</a>
                                </div>
                                
                                <!-- Additional Experiences Column -->
                                <div class="space-y-2.5">
                                    <div class="mb-4 pb-3 border-b-2 border-green-200">
                                        <h4 class="font-bold text-base text-green-600 uppercase tracking-wider">Additional Experiences</h4>
                                    </div>
                                    <a href="${pathPrefix}event-photography.html" class="block text-sm text-gray-700 hover:text-green-600 transition py-1.5 hover:bg-green-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Event Photography</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Professional coverage of every moment</span>
                                    </a>
                                    <a href="${pathPrefix}product/brand-activation.html" class="block text-sm text-gray-700 hover:text-green-600 transition py-1.5 hover:bg-green-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Brand Activation</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Immersive brand experiences and activations</span>
                                    </a>
                                    <a href="${pathPrefix}products/sketchbot-booth.html" class="block text-sm text-gray-700 hover:text-green-600 transition py-1.5 hover:bg-green-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">SketchBot</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Live robot-drawn portraits</span>
                                    </a>
                                    <a href="${pathPrefix}products/cookie-printer.html" class="block text-sm text-gray-700 hover:text-green-600 transition py-1.5 hover:bg-green-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Cookie Printer</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Edible photo cookies on demand</span>
                                    </a>
                                    <a href="${pathPrefix}pose-flashcards.html" class="block text-sm text-gray-700 hover:text-green-600 transition py-1.5 hover:bg-green-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Pose Cards</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Signature pose flashcards for guests</span>
                                    </a>
                                    <a href="${pathPrefix}lux-photography.html" class="block text-sm text-gray-700 hover:text-green-600 transition py-1.5 hover:bg-green-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Lux Photography</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">An elevated photography booth that leaves you and your guests feeling luxurious</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Event Types -->
                <div class="relative group">
                    <a href="${pathPrefix}index.html#event-types" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">
                        Events
                    </a>
                    <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-[40rem] bg-white rounded-2xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 border border-gray-100">
                        <div class="p-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-3">
                                    <a href="${pathPrefix}wedding.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Weddings</span><br>
                                        <span class="text-xs text-gray-500">Make your special day unforgettable</span>
                                    </a>
                                    <a href="${pathPrefix}corporate-events.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Corporate Events</span><br>
                                        <span class="text-xs text-gray-500">Fully branded experiences for your brand</span>
                                    </a>
                                    <a href="${pathPrefix}socialevents.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Social Events</span><br>
                                        <span class="text-xs text-gray-500">Birthdays, Mitzvahs, and more</span>
                                    </a>
                                </div>
                                <div class="space-y-3">
                                    <a href="${pathPrefix}tradeshow-booth.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Trade Shows</span><br>
                                        <span class="text-xs text-gray-500">Mosaic walls and high-impact activations</span>
                                    </a>
                                    <a href="${pathPrefix}holiday-party.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
                                        <span class="font-medium">Holiday Parties</span><br>
                                        <span class="text-xs text-gray-500">Curated sets that come to life</span>
                                    </a>
                                    <a href="${pathPrefix}denver-casino-rentals.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
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
                    <a href="${pathPrefix}index.html#rentals" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">
                        Rentals
                    </a>
                    <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-[56rem] max-h-[90vh] overflow-y-auto bg-white rounded-xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 border border-gray-200">
                        <div class="p-6">
                            <div class="grid grid-cols-3 gap-5">
                                <div class="space-y-2.5">
                                    <div class="mb-4 pb-3 border-b-2 border-blue-200">
                                        <h4 class="font-bold text-base text-blue-600 uppercase tracking-wider">AV Services</h4>
                                    </div>
                                    <a href="${pathPrefix}av-services/audio-services.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Audio</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Professional sound systems and audio mixing</span>
                                    </a>
                                    <a href="${pathPrefix}av-services/visual-services.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Visual</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Screens, displays, and video projection</span>
                                    </a>
                                    <a href="${pathPrefix}av-services/event-lighting.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Lighting</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Transform spaces with lighting</span>
                                    </a>
                                    <a href="${pathPrefix}av-services/event-stages.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Event Stages</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Professional stage setups</span>
                                    </a>
                                    <a href="${pathPrefix}av-services/event-signage.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Custom Signage</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Professional stage setups</span>
                                    </a>
                                </div>
                                <div class="space-y-2.5">
                                    <div class="mb-4 pb-3 border-b-2 border-purple-200">
                                        <h4 class="font-bold text-base text-purple-600 uppercase tracking-wider">Event Decor</h4>
                                    </div>
                                    <a href="${pathPrefix}event-decor/special-effects.html" class="block text-sm text-gray-700 hover:text-purple-600 transition py-1.5 hover:bg-purple-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Special Effects</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Sparks, snow, confetti, champagne walls</span>
                                    </a>
                                    <a href="${pathPrefix}event-decor/lighting-decor.html" class="block text-sm text-gray-700 hover:text-purple-600 transition py-1.5 hover:bg-purple-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Lighting Decor</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Chandeliers, market lights, marquee</span>
                                    </a>
                                    <a href="${pathPrefix}event-decor/event-decor.html" class="block text-sm text-gray-700 hover:text-purple-600 transition py-1.5 hover:bg-purple-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Event Decor</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Lounge sets, shimmer walls, drape</span>
                                    </a>
                                </div>
                                <div class="space-y-2.5">
                                    <div class="mb-4 pb-3 border-b-2 border-green-200">
                                        <h4 class="font-bold text-base text-green-600 uppercase tracking-wider">Games</h4>
                                    </div>
                                    <a href="${pathPrefix}game-rentals/claw-machine.html" class="block text-sm text-gray-700 hover:text-green-600 transition py-1.5 hover:bg-green-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Claw Machine</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Walk up and win a prize</span>
                                    </a>
                                    <a href="${pathPrefix}virtual-reality-rental.html" class="block text-sm text-gray-700 hover:text-green-600 transition py-1.5 hover:bg-green-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">VR Headsets</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Immersive virtual experiences</span>
                                    </a>
                                    <a href="${pathPrefix}product/money-booth.html" class="block text-sm text-gray-700 hover:text-green-600 transition py-1.5 hover:bg-green-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Money Booth</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Grab the floating cash</span>
                                    </a>
                                    <a href="${pathPrefix}game-rentals/stick-drop.html" class="block text-sm text-gray-700 hover:text-green-600 transition py-1.5 hover:bg-green-50 rounded-md px-2 -mx-2">
                                        <span class="font-semibold block mb-0.5">Stick Drop</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Grab the floating cash</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gallery -->
                <div class="relative group">
                    <a href="${pathPrefix}index.html#gallery" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">
                        Gallery
                    </a>
                    <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-[30rem] max-h-[90vh] overflow-y-auto bg-white rounded-xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 border border-gray-200">
                        <div class="p-8">
                            <div class="grid grid-cols-3 gap-5">
                                <div class="space-y-2.5">
                                    <a href="${pathPrefix}our-work.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-3 -mx-3">
                                        <span class="font-semibold block mb-0.5 whitespace-nowrap">Our Work</span>
                                        <span class="text-xs text-gray-500 leading-relaxed block whitespace-nowrap">View a collection of our work</span>
                                    </a>
                                    <a href="${pathPrefix}our-services.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-3 -mx-3">
                                        <span class="font-semibold block mb-0.5 whitespace-nowrap">Our Services</span>
                                        <span class="text-xs text-gray-500 leading-relaxed block whitespace-nowrap">Check out all of the services we offer for events</span>
                                    </a>
                                    <a href="${pathPrefix}our-booths.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-3 -mx-3">
                                        <span class="font-semibold block mb-0.5 whitespace-nowrap">Our Booths</span>
                                        <span class="text-xs text-gray-500 leading-relaxed block whitespace-nowrap">See all of our Photo Booths</span>
                                    </a>
                                    <a href="${pathPrefix}mihi-props.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-3 -mx-3">
                                        <span class="font-semibold block mb-0.5 whitespace-nowrap">Our Props</span>
                                        <span class="text-xs text-gray-500 leading-relaxed block whitespace-nowrap">Take a look at our prop collection</span>
                                    </a>
                                    <a href="${pathPrefix}booth-themes.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-3 -mx-3">
                                        <span class="font-semibold block mb-0.5 whitespace-nowrap">Our Themes</span>
                                        <span class="text-xs text-gray-500 leading-relaxed block whitespace-nowrap">All events themes, curated for any event</span>
                                    </a>
                                    <a href="${pathPrefix}our-add-ons.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-3 -mx-3">
                                        <span class="font-semibold block mb-0.5 whitespace-nowrap">Our Add-Ons</span>
                                        <span class="text-xs text-gray-500 leading-relaxed block whitespace-nowrap">See what we can add-on to your next rental</span>
                                    </a>
                                    <a href="${pathPrefix}our-design.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-3 -mx-3">
                                        <span class="font-semibold block mb-0.5 whitespace-nowrap">Our Designs</span>
                                        <span class="text-xs text-gray-500 leading-relaxed block whitespace-nowrap">Custom Branded Booth Wraps, Backdrops, and more</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- About -->
                <div class="relative group">
                    <a href="${pathPrefix}index.html#about" class="text-gray-700 hover:text-blue-600 font-medium transition duration-300">
                        About Us
                    </a>
                    <div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-[30rem] max-h-[90vh] overflow-y-auto bg-white rounded-xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 border border-gray-200">
                        <div class="p-8">
                            <div class="grid grid-cols-3 gap-5">
                                <div class="space-y-2.5">
                                    <a href="${pathPrefix}blog.php" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-3 -mx-3">
                                        <span class="font-semibold block mb-0.5 whitespace-nowrap">Read Our Blogs</span>
                                        <span class="text-xs text-gray-500 leading-relaxed block whitespace-nowrap">Read about our events, activations, and more</span>
                                    </a>
                                    <a href="${pathPrefix}our-locations.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-3 -mx-3">
                                        <span class="font-semibold block mb-0.5 whitespace-nowrap">Our Locations</span>
                                        <span class="text-xs text-gray-500 leading-relaxed block whitespace-nowrap">View all of the locations we service nationwide</span>
                                    </a>
                                    <a href="${pathPrefix}case-studies.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-3 -mx-3">
                                        <span class="font-semibold block mb-0.5 whitespace-nowrap">Case Studies</span>
                                        <span class="text-xs text-gray-500 leading-relaxed block whitespace-nowrap">Learn how our activations have helped create memorable events</span>
                                    </a>
                                    <a href="${pathPrefix}faq.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-3 -mx-3">
                                        <span class="font-semibold block mb-0.5 whitespace-nowrap">FAQ</span>
                                        <span class="text-xs text-gray-500 leading-relaxed block whitespace-nowrap">View our most commonly asked questions</span>
                                    </a>
                                    <a href="${pathPrefix}about.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-3 -mx-3">
                                        <span class="font-semibold block mb-0.5 whitespace-nowrap">About MiHi</span>
                                        <span class="text-xs text-gray-500 leading-relaxed block whitespace-nowrap">Learn about MiHi, see our hardworking team, and more</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- CTA Button -->
                <a href="${pathPrefix}contact-us.html" class="btn-primary text-white px-6 py-3 rounded-full font-semibold transition-all duration-300 transform hover:scale-105 hover:opacity-90" style="background: #FF4F4F;">
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
            <div id="mobile-menu-overlay" class="fixed inset-0 bg-black/70 backdrop-blur-md z-40 lg:hidden hidden transition-opacity duration-300">
                <div class="fixed top-0 right-0 h-full w-[88vw] max-w-sm bg-white shadow-2xl transform transition-transform duration-300 ease-out translate-x-full" id="mobile-menu">
                    <div class="flex flex-col h-full overflow-hidden">
                        <!-- Mobile Menu Header -->
                        <div class="flex items-center justify-between px-6 py-5 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200/50 sticky top-0 z-10 backdrop-blur-sm">
                            <img src="${assetsPrefix}images/logo.svg" alt="MiHi Entertainment" class="h-10 w-auto drop-shadow-sm" />
                            <button id="mobile-menu-close" class="p-2.5 rounded-xl hover:bg-gray-100 active:bg-gray-200 transition-all duration-200 text-gray-600 hover:text-gray-900 shadow-sm hover:shadow-md">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Mobile Menu Content -->
                        <div class="flex-1 overflow-y-auto px-5 py-6 space-y-3">
                            <!-- Mobile Products Section -->
                            <div class="bg-white rounded-2xl shadow-lg border border-gray-200/50 overflow-hidden">
                                <button class="mobile-menu-toggle w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50/50 transition-colors group" data-target="products-content">
                                    <div class="flex items-center gap-3">
                                        <span class="w-1.5 h-6 bg-gradient-to-b from-blue-500 via-purple-500 to-pink-500 rounded-full shadow-sm"></span>
                                        <span class="text-base font-bold text-gray-900 group-hover:text-gray-700">Products</span>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-300 transform rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div id="products-content" class="hidden overflow-hidden transition-all duration-300 ease-in-out" style="max-height: 0px;">
                                    <div class="px-5 pb-4 space-y-4">
                                        <!-- Photo Booths Category -->
                                        <div>
                                            <div class="flex items-center gap-2.5 mb-3 py-2.5 px-4 rounded-lg bg-blue-50/70 border border-blue-200/50 -mx-1">
                                                <span class="w-2 h-2 rounded-full bg-blue-500 shadow-sm"></span>
                                                <span class="text-xs font-bold text-blue-700 uppercase tracking-widest">Photo Booths</span>
                                            </div>
                                            <div class="space-y-1 pl-1">
                                                <a href="${pathPrefix}product/ai-photo-booth.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">AI Photo Booth</a>
                                                <a href="${pathPrefix}product/green-screen-photo-booth.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Green Screen</a>
                                                <a href="${pathPrefix}product/rosie-the-robot-photo-booth.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Rosie the Robot</a>
                                                <a href="${pathPrefix}product/graffiti-wall-photo-booth.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Graffiti Wall Photo Booth</a>
                                                <a href="${pathPrefix}product/mosaic-photo-booth.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Mosaic Photo Booth</a>
                                                <a href="${pathPrefix}product/roaming-photo-booth.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Roaming Photo Booth</a>
                                                <a href="${pathPrefix}product/virtual-photo-booth.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Virtual Photo Booth</a>
                                                <a href="${pathPrefix}product/professional-headshots.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Professional Headshots</a>
                                                <a href="${pathPrefix}photo-booth-sets.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Custom Photo Booth</a>
                                            </div>
                                        </div>
                                        <!-- Video Booths Category -->
                                        <div>
                                            <div class="flex items-center gap-2.5 mb-3 py-2.5 px-4 rounded-lg bg-purple-50/70 border border-purple-200/50 -mx-1">
                                                <span class="w-2 h-2 rounded-full bg-purple-500 shadow-sm"></span>
                                                <span class="text-xs font-bold text-purple-700 uppercase tracking-widest">Video Booths</span>
                                            </div>
                                            <div class="space-y-1 pl-1">
                                                <a href="${pathPrefix}product/360-photo-booth.html" class="block text-sm text-gray-700 hover:text-purple-700 hover:bg-purple-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">360 Video Booth</a>
                                                <a href="${pathPrefix}product/bullet-time-booth.html" class="block text-sm text-gray-700 hover:text-purple-700 hover:bg-purple-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Bullet-Time Array</a>
                                                <a href="${pathPrefix}product/glambot-photo-booth.html" class="block text-sm text-gray-700 hover:text-purple-700 hover:bg-purple-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">GlamBot Video</a>
                                                <a href="${pathPrefix}product/vogue-photo-booth.html" class="block text-sm text-gray-700 hover:text-purple-700 hover:bg-purple-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Vogue Video Booth</a>
                                                <a href="${pathPrefix}product/slow-motion-booth.html" class="block text-sm text-gray-700 hover:text-purple-700 hover:bg-purple-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Slow Motion Video Booth</a>
                                                <a href="${pathPrefix}product/video-testimonial-booth.html" class="block text-sm text-gray-700 hover:text-purple-700 hover:bg-purple-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Video Testimonial Booth</a>
                                            </div>
                                        </div>
                                        <!-- Additional Experiences Category -->
                                        <div>
                                            <div class="flex items-center gap-2.5 mb-3 py-2.5 px-4 rounded-lg bg-green-50/70 border border-green-200/50 -mx-1">
                                                <span class="w-2 h-2 rounded-full bg-green-500 shadow-sm"></span>
                                                <span class="text-xs font-bold text-green-700 uppercase tracking-widest">Additional Experiences</span>
                                            </div>
                                            <div class="space-y-1 pl-1">
                                                <a href="${pathPrefix}event-photography.html" class="block text-sm text-gray-700 hover:text-green-700 hover:bg-green-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Event Photography</a>
                                                <a href="${pathPrefix}product/brand-activation.html" class="block text-sm text-gray-700 hover:text-green-700 hover:bg-green-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Brand Activation</a>
                                                <a href="${pathPrefix}products/sketchbot-booth.html" class="block text-sm text-gray-700 hover:text-green-700 hover:bg-green-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">SketchBot</a>
                                                <a href="${pathPrefix}products/cookie-printer.html" class="block text-sm text-gray-700 hover:text-green-700 hover:bg-green-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Cookie Printer</a>
                                                <a href="${pathPrefix}pose-flashcards.html" class="block text-sm text-gray-700 hover:text-green-700 hover:bg-green-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Pose Cards</a>
                                                <a href="${pathPrefix}lux-photography.html" class="block text-sm text-gray-700 hover:text-green-700 hover:bg-green-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Lux Photography</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Events Section -->
                            <div class="bg-white rounded-2xl shadow-lg border border-gray-200/50 overflow-hidden">
                                <button class="mobile-menu-toggle w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50/50 transition-colors group" data-target="events-content">
                                    <div class="flex items-center gap-3">
                                        <span class="w-1.5 h-6 bg-gradient-to-b from-pink-500 via-rose-500 to-blue-500 rounded-full shadow-sm"></span>
                                        <span class="text-base font-bold text-gray-900 group-hover:text-gray-700">Events</span>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-300 transform rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div id="events-content" class="hidden overflow-hidden transition-all duration-300 ease-in-out" style="max-height: 0px;">
                                    <div class="px-5 pb-4 space-y-1">
                                        <a href="${pathPrefix}wedding.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Weddings</a>
                                        <a href="${pathPrefix}corporate-events.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Corporate Events</a>
                                        <a href="${pathPrefix}socialevents.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Social Events</a>
                                        <a href="${pathPrefix}tradeshow-booth.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Trade Shows</a>
                                        <a href="${pathPrefix}holiday-party.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Holiday Parties</a>
                                        <a href="${pathPrefix}denver-casino-rentals.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Casino Parties</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Rentals Section -->
                            <div class="bg-white rounded-2xl shadow-lg border border-gray-200/50 overflow-hidden">
                                <button class="mobile-menu-toggle w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50/50 transition-colors group" data-target="rentals-content">
                                    <div class="flex items-center gap-3">
                                        <span class="w-1.5 h-6 bg-gradient-to-b from-indigo-500 via-purple-500 to-pink-500 rounded-full shadow-sm"></span>
                                        <span class="text-base font-bold text-gray-900 group-hover:text-gray-700">Rentals</span>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-300 transform rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div id="rentals-content" class="hidden overflow-hidden transition-all duration-300 ease-in-out" style="max-height: 0px;">
                                    <div class="px-5 pb-4 space-y-4">
                                        <!-- AV Services Category -->
                                        <div>
                                            <div class="flex items-center gap-2.5 mb-3 py-2.5 px-4 rounded-lg bg-blue-50/70 border border-blue-200/50 -mx-1">
                                                <span class="w-2 h-2 rounded-full bg-blue-500 shadow-sm"></span>
                                                <span class="text-xs font-bold text-blue-700 uppercase tracking-widest">AV Services</span>
                                            </div>
                                            <div class="space-y-1 pl-1">
                                                <a href="${pathPrefix}av-services/audio-services.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Audio</a>
                                                <a href="${pathPrefix}av-services/visual-services.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Visual</a>
                                                <a href="${pathPrefix}av-services/event-lighting.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Lighting</a>
                                                <a href="${pathPrefix}av-services/event-stages.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Event Stages</a>
                                                <a href="${pathPrefix}av-services/event-signage.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Custom Signage</a>
                                            </div>
                                        </div>
                                        <!-- Event Decor Category -->
                                        <div>
                                            <div class="flex items-center gap-2.5 mb-3 py-2.5 px-4 rounded-lg bg-purple-50/70 border border-purple-200/50 -mx-1">
                                                <span class="w-2 h-2 rounded-full bg-purple-500 shadow-sm"></span>
                                                <span class="text-xs font-bold text-purple-700 uppercase tracking-widest">Event Decor</span>
                                            </div>
                                            <div class="space-y-1 pl-1">
                                                <a href="${pathPrefix}event-decor/special-effects.html" class="block text-sm text-gray-700 hover:text-purple-700 hover:bg-purple-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Special Effects</a>
                                                <a href="${pathPrefix}event-decor/lighting-decor.html" class="block text-sm text-gray-700 hover:text-purple-700 hover:bg-purple-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Lighting Decor</a>
                                                <a href="${pathPrefix}event-decor/event-decor.html" class="block text-sm text-gray-700 hover:text-purple-700 hover:bg-purple-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Event Decor</a>
                                            </div>
                                        </div>
                                        <!-- Games Category -->
                                        <div>
                                            <div class="flex items-center gap-2.5 mb-3 py-2.5 px-4 rounded-lg bg-green-50/70 border border-green-200/50 -mx-1">
                                                <span class="w-2 h-2 rounded-full bg-green-500 shadow-sm"></span>
                                                <span class="text-xs font-bold text-green-700 uppercase tracking-widest">Games</span>
                                            </div>
                                            <div class="space-y-1 pl-1">
                                                <a href="${pathPrefix}game-rentals/claw-machine.html" class="block text-sm text-gray-700 hover:text-green-700 hover:bg-green-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Claw Machine</a>
                                                <a href="${pathPrefix}virtual-reality-rental.html" class="block text-sm text-gray-700 hover:text-green-700 hover:bg-green-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">VR Headsets</a>
                                                <a href="${pathPrefix}product/money-booth.html" class="block text-sm text-gray-700 hover:text-green-700 hover:bg-green-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Money Booth</a>
                                                <a href="${pathPrefix}game-rentals/stick-drop.html" class="block text-sm text-gray-700 hover:text-green-700 hover:bg-green-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Stick Drop</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Gallery Section -->
                            <div class="bg-white rounded-2xl shadow-lg border border-gray-200/50 overflow-hidden">
                                <button class="mobile-menu-toggle w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50/50 transition-colors group" data-target="gallery-content">
                                    <div class="flex items-center gap-3">
                                        <span class="w-1.5 h-6 bg-gradient-to-b from-blue-400 to-cyan-500 rounded-full shadow-sm"></span>
                                        <span class="text-base font-bold text-gray-900 group-hover:text-gray-700">Gallery</span>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-300 transform rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div id="gallery-content" class="hidden overflow-hidden transition-all duration-300 ease-in-out" style="max-height: 0px;">
                                    <div class="px-5 pb-4 space-y-1">
                                        <a href="${pathPrefix}our-work.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Our Work</a>
                                        <a href="${pathPrefix}our-services.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Our Services</a>
                                        <a href="${pathPrefix}our-booths.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Our Booths</a>
                                        <a href="${pathPrefix}mihi-props.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Our Props</a>
                                        <a href="${pathPrefix}booth-themes.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Our Themes</a>
                                        <a href="${pathPrefix}our-add-ons.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Our Add-Ons</a>
                                        <a href="${pathPrefix}our-design.html" class="block text-sm text-gray-700 hover:text-blue-700 hover:bg-blue-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Our Designs</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile About Us Section -->
                            <div class="bg-white rounded-2xl shadow-lg border border-gray-200/50 overflow-hidden">
                                <button class="mobile-menu-toggle w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50/50 transition-colors group" data-target="about-content">
                                    <div class="flex items-center gap-3">
                                        <span class="w-1.5 h-6 bg-gradient-to-b from-purple-400 to-pink-500 rounded-full shadow-sm"></span>
                                        <span class="text-base font-bold text-gray-900 group-hover:text-gray-700">About Us</span>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-300 transform rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div id="about-content" class="hidden overflow-hidden transition-all duration-300 ease-in-out" style="max-height: 0px;">
                                    <div class="px-5 pb-4 space-y-1">
                                        <a href="${pathPrefix}blog.php" class="block text-sm text-gray-700 hover:text-purple-700 hover:bg-purple-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Read Our Blogs</a>
                                        <a href="${pathPrefix}our-locations.html" class="block text-sm text-gray-700 hover:text-purple-700 hover:bg-purple-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Our Locations</a>
                                        <a href="${pathPrefix}case-studies.html" class="block text-sm text-gray-700 hover:text-purple-700 hover:bg-purple-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">Case Studies</a>
                                        <a href="${pathPrefix}faq.html" class="block text-sm text-gray-700 hover:text-purple-700 hover:bg-purple-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">FAQ</a>
                                        <a href="${pathPrefix}about.html" class="block text-sm text-gray-700 hover:text-purple-700 hover:bg-purple-50/50 transition-all rounded-lg py-2.5 px-3 font-medium">About MiHi</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Contact Button -->
                            <div class="pb-4 pt-2">
                                <a href="${pathPrefix}contact-us.html" class="btn-primary text-white px-6 py-4 rounded-2xl font-bold transition-all duration-300 text-center block shadow-xl hover:shadow-2xl transform hover:scale-[1.02] active:scale-[0.98] text-base hover:opacity-90" style="background: #FF4F4F;">Contact Us</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    `;

    // Mobile menu functionality - defined first so it can be called later
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
                // Open menu
                mobileMenuOverlay.classList.remove('hidden');
                requestAnimationFrame(() => {
                    mobileMenu.classList.remove('translate-x-full');
                    animateHamburger(true);
                });
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

        function closeMenu() {
            mobileMenu.classList.add('translate-x-full');
            animateHamburger(false);
            setTimeout(() => {
                mobileMenuOverlay.classList.add('hidden');
            }, 300);
            document.body.style.overflow = '';
            menuOpen = false;
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

        if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', function(e) {
                e.preventDefault();
                closeMenu();
            });
        }

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

        // Collapsible sections functionality
        function initCollapsibleSections() {
            const toggleButtons = document.querySelectorAll('.mobile-menu-toggle');
            toggleButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const targetId = this.getAttribute('data-target');
                    const content = document.getElementById(targetId);
                    const chevron = this.querySelector('svg');
                    
                    if (!content) return;
                    
                    const isHidden = content.classList.contains('hidden');
                    
                    if (isHidden) {
                        // Expand
                        content.classList.remove('hidden');
                        const height = content.scrollHeight;
                        content.style.maxHeight = '0px';
                        content.style.opacity = '0';
                        
                        requestAnimationFrame(() => {
                            content.style.transition = 'max-height 0.3s ease-in-out, opacity 0.3s ease-in-out';
                            content.style.maxHeight = height + 'px';
                            content.style.opacity = '1';
                        });
                        
                        if (chevron) {
                            chevron.style.transition = 'transform 0.3s ease-in-out';
                            chevron.style.transform = 'rotate(180deg)';
                        }
                    } else {
                        // Collapse
                        const height = content.scrollHeight;
                        content.style.maxHeight = height + 'px';
                        content.style.opacity = '1';
                        
                        requestAnimationFrame(() => {
                            content.style.transition = 'max-height 0.3s ease-in-out, opacity 0.3s ease-in-out';
                            content.style.maxHeight = '0px';
                            content.style.opacity = '0';
                            
                            setTimeout(() => {
                                content.classList.add('hidden');
                                content.style.maxHeight = '';
                                content.style.opacity = '';
                            }, 300);
                        });
                        
                        if (chevron) {
                            chevron.style.transition = 'transform 0.3s ease-in-out';
                            chevron.style.transform = 'rotate(0deg)';
                        }
                    }
                });
            });
        }
        
        // Initialize collapsible sections
        initCollapsibleSections();
    }

        // Insert navigation at the beginning of body and initialize mobile menu
        if (document.body) {
            document.body.insertAdjacentHTML('afterbegin', navigationHTML);
            initMobileMenu();
        }
    }

    // Initialize immediately if body exists, otherwise wait for DOMContentLoaded
    if (document.body) {
        initNavigation();
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavigation);
    } else {
        // DOM already loaded
        initNavigation();
    }
})();