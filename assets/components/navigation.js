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
                                    <a href="${pathPrefix}blog.html" class="block text-sm text-gray-700 hover:text-blue-600 transition py-1.5 hover:bg-blue-50 rounded-md px-3 -mx-3">
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
                <a href="${pathPrefix}contact-us.html" class="btn-primary text-white px-6 py-3 rounded-full font-semibold shadow-lg hover:shadow-xl transition-all duration-300">
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
                            <img src="${assetsPrefix}images/logo.svg" alt="MiHi Entertainment" class="h-10 w-auto" />
                            <button id="mobile-menu-close" class="text-gray-500 hover:text-gray-700 transition duration-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Mobile Menu Content -->
                        <div class="flex-1 overflow-y-auto p-6">
                            <!-- Mobile Products Section -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">Products</h3>
                                <div class="space-y-3">
                                    <div>
                                        <h4 class="font-bold text-base text-blue-600 uppercase tracking-wider mb-3 pb-2 border-b-2 border-blue-200">Photo Booths</h4>
                                        <div class="pl-4 space-y-2">
                                            <a href="${pathPrefix}product/ai-photo-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">AI Photo Booth</a>
                                            <a href="${pathPrefix}product/green-screen-photo-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Green Screen</a>
                                            <a href="${pathPrefix}product/rosie-the-robot-photo-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Rosie the Robot</a>
                                            <a href="${pathPrefix}product/graffiti-wall-photo-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Graffiti Wall Photo Booth</a>
                                            <a href="${pathPrefix}product/mosaic-photo-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Mosaic Photo Booth</a>
                                            <a href="${pathPrefix}product/roaming-photo-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Roaming Photo Booth</a>
                                            <a href="${pathPrefix}product/virtual-photo-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Virtual Photo Booth</a>
                                            <a href="${pathPrefix}product/professional-headshots.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Professional Headshots</a>
                                            <a href="${pathPrefix}photo-booth-sets.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Custom Photo Booth</a>
                                            <a href="${pathPrefix}index.html#photo-booths" class="block text-xs text-blue-600 hover:text-blue-700 transition font-semibold mt-3 pt-3 border-t border-gray-200">View All →</a>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-base text-purple-600 uppercase tracking-wider mb-3 pb-2 border-b-2 border-purple-200">Video Booths</h4>
                                        <div class="pl-4 space-y-2">
                                            <a href="${pathPrefix}product/360-photo-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">360 Video Booth</a>
                                            <a href="${pathPrefix}product/bullet-time-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Bullet-Time Array</a>
                                            <a href="${pathPrefix}product/glambot-photo-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">GlamBot Video</a>
                                            <a href="${pathPrefix}product/vogue-photo-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Vogue Video Booth</a>
                                            <a href="${pathPrefix}product/slow-motion-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Slow Motion Video Booth</a>
                                            <a href="${pathPrefix}product/video-testimonial-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Video Testimonial Booth</a>
                                            <a href="${pathPrefix}index.html#video-booths" class="block text-xs text-purple-600 hover:text-purple-700 transition font-semibold mt-3 pt-3 border-t border-gray-200">View All →</a>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-base text-green-600 uppercase tracking-wider mb-3 pb-2 border-b-2 border-green-200">Additional Experiences</h4>
                                        <div class="pl-4 space-y-2">
                                            <a href="${pathPrefix}event-photography.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Event Photography</a>
                                            <a href="${pathPrefix}products/sketchbot-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">SketchBot</a>
                                            <a href="${pathPrefix}products/cookie-printer.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Cookie Printer</a>
                                            <a href="${pathPrefix}pose-flashcards.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Pose Cards</a>
                                            <a href="${pathPrefix}lux-photography.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">Lux Photography</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Events Section -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">Events</h3>
                                <div class="space-y-2">
                                    <a href="${pathPrefix}wedding.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-medium">Weddings</span><br>
                                        <span class="text-xs text-gray-500">Make your special day unforgettable</span>
                                    </a>
                                    <a href="${pathPrefix}corporate-events.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-medium">Corporate Events</span><br>
                                        <span class="text-xs text-gray-500">Fully branded experiences for your brand</span>
                                    </a>
                                    <a href="${pathPrefix}socialevents.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-medium">Social Events</span><br>
                                        <span class="text-xs text-gray-500">Birthdays, Mitzvahs, and more</span>
                                    </a>
                                    <a href="${pathPrefix}tradeshow-booth.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-medium">Trade Shows</span><br>
                                        <span class="text-xs text-gray-500">Mosaic walls and high-impact activations</span>
                                    </a>
                                    <a href="${pathPrefix}holiday-party.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-medium">Holiday Parties</span><br>
                                        <span class="text-xs text-gray-500">Curated sets that come to life</span>
                                    </a>
                                    <a href="${pathPrefix}denver-casino-rentals.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-medium">Casino Parties</span><br>
                                        <span class="text-xs text-gray-500">Tables, dealers, and full experiences</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Mobile Rentals Section -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">Rentals</h3>
                                <div class="space-y-3">
                                    <div>
                                        <h4 class="font-bold text-base text-blue-600 uppercase tracking-wider mb-3 pb-2 border-b-2 border-blue-200">AV Services</h4>
                                        <div class="pl-4 space-y-2">
                                            <a href="${pathPrefix}av-services/audio-services.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                                <span class="font-semibold block mb-0.5">Audio</span>
                                                <span class="text-xs text-gray-500 leading-relaxed">Professional sound systems and audio mixing</span>
                                            </a>
                                            <a href="${pathPrefix}av-services/visual-services.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                                <span class="font-semibold block mb-0.5">Visual</span>
                                                <span class="text-xs text-gray-500 leading-relaxed">Screens, displays, and video projection</span>
                                            </a>
                                            <a href="${pathPrefix}av-services/event-lighting.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                                <span class="font-semibold block mb-0.5">Lighting</span>
                                                <span class="text-xs text-gray-500 leading-relaxed">Transform spaces with lighting</span>
                                            </a>
                                            <a href="${pathPrefix}av-services/event-stages.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                                <span class="font-semibold block mb-0.5">Event Stages</span>
                                                <span class="text-xs text-gray-500 leading-relaxed">Professional stage setups</span>
                                            </a>
                                            <a href="${pathPrefix}av-services/event-signage.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                                <span class="font-semibold block mb-0.5">Custom Signage</span>
                                                <span class="text-xs text-gray-500 leading-relaxed">Professional stage setups</span>
                                            </a>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-base text-purple-600 uppercase tracking-wider mb-3 pb-2 border-b-2 border-purple-200">Event Decor</h4>
                                        <div class="pl-4 space-y-2">
                                            <a href="${pathPrefix}event-decor/special-effects.html" class="block text-sm text-gray-600 hover:text-purple-600 transition">
                                                <span class="font-semibold block mb-0.5">Special Effects</span>
                                                <span class="text-xs text-gray-500 leading-relaxed">Sparks, snow, confetti, champagne walls</span>
                                            </a>
                                            <a href="${pathPrefix}event-decor/lighting-decor.html" class="block text-sm text-gray-600 hover:text-purple-600 transition">
                                                <span class="font-semibold block mb-0.5">Lighting Decor</span>
                                                <span class="text-xs text-gray-500 leading-relaxed">Chandeliers, market lights, marquee</span>
                                            </a>
                                            <a href="${pathPrefix}event-decor/event-decor.html" class="block text-sm text-gray-600 hover:text-purple-600 transition">
                                                <span class="font-semibold block mb-0.5">Event Decor</span>
                                                <span class="text-xs text-gray-500 leading-relaxed">Lounge sets, shimmer walls, drape</span>
                                            </a>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-base text-green-600 uppercase tracking-wider mb-3 pb-2 border-b-2 border-green-200">Games</h4>
                                        <div class="pl-4 space-y-2">
                                            <a href="${pathPrefix}game-rentals/claw-machine.html" class="block text-sm text-gray-600 hover:text-green-600 transition">
                                                <span class="font-semibold block mb-0.5">Claw Machine</span>
                                                <span class="text-xs text-gray-500 leading-relaxed">Walk up and win a prize</span>
                                            </a>
                                            <a href="${pathPrefix}virtual-reality-rental.html" class="block text-sm text-gray-600 hover:text-green-600 transition">
                                                <span class="font-semibold block mb-0.5">VR Headsets</span>
                                                <span class="text-xs text-gray-500 leading-relaxed">Immersive virtual experiences</span>
                                            </a>
                                            <a href="${pathPrefix}product/money-booth.html" class="block text-sm text-gray-600 hover:text-green-600 transition">
                                                <span class="font-semibold block mb-0.5">Money Booth</span>
                                                <span class="text-xs text-gray-500 leading-relaxed">Grab the floating cash</span>
                                            </a>
                                            <a href="${pathPrefix}game-rentals/stick-drop.html" class="block text-sm text-gray-600 hover:text-green-600 transition">
                                                <span class="font-semibold block mb-0.5">Stick Drop</span>
                                                <span class="text-xs text-gray-500 leading-relaxed">Grab the floating cash</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Gallery Section -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">Gallery</h3>
                                <div class="space-y-2">
                                    <a href="${pathPrefix}our-work.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-semibold block mb-0.5">Our Work</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">View a collection of our work</span>
                                    </a>
                                    <a href="${pathPrefix}our-services.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-semibold block mb-0.5">Our Services</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Check out all of the services we offer for events</span>
                                    </a>
                                    <a href="${pathPrefix}our-booths.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-semibold block mb-0.5">Our Booths</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">See all of our Photo Booths</span>
                                    </a>
                                    <a href="${pathPrefix}mihi-props.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-semibold block mb-0.5">Our Props</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Take a look at our prop collection</span>
                                    </a>
                                    <a href="${pathPrefix}booth-themes.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-semibold block mb-0.5">Our Themes</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">All events themes, curated for any event</span>
                                    </a>
                                    <a href="${pathPrefix}our-add-ons.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-semibold block mb-0.5">Our Add-Ons</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">See what we can add-on to your next rental</span>
                                    </a>
                                    <a href="${pathPrefix}our-design.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-semibold block mb-0.5">Our Designs</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Custom Branded Booth Wraps, Backdrops, and more</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Mobile About Us Section -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">About Us</h3>
                                <div class="space-y-2">
                                    <a href="${pathPrefix}blog.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-semibold block mb-0.5">Read Our Blogs</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Read about our events, activations, and more</span>
                                    </a>
                                    <a href="${pathPrefix}our-locations.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-semibold block mb-0.5">Our Locations</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">View all of the locations we service nationwide</span>
                                    </a>
                                    <a href="${pathPrefix}case-studies.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-semibold block mb-0.5">Case Studies</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Learn how our activations have helped create memorable events</span>
                                    </a>
                                    <a href="${pathPrefix}faq.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-semibold block mb-0.5">FAQ</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">View our most commonly asked questions</span>
                                    </a>
                                    <a href="${pathPrefix}about.html" class="block text-sm text-gray-600 hover:text-blue-600 transition">
                                        <span class="font-semibold block mb-0.5">About MiHi</span>
                                        <span class="text-xs text-gray-500 leading-relaxed">Learn about MiHi, see our hardworking team, and more</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Mobile Contact Button -->
                            <div class="space-y-4">
                                <a href="${pathPrefix}contact-us.html" class="btn-primary text-white px-6 py-3 rounded-full font-semibold shadow-lg hover:shadow-xl transition-all duration-300 text-center block">Contact Us</a>
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
