// Shared Navigation Component
// This script will load the same navigation on all pages

document.addEventListener('DOMContentLoaded', function() {
    // Determine the correct path prefix based on current page location
    const currentPath = window.location.pathname;
    let pathPrefix = '../';
    let assetsPrefix = '../assets/';
    
    // If we're in the root directory
    if (currentPath.endsWith('index.html') || currentPath.endsWith('/')) {
        pathPrefix = '';
        assetsPrefix = 'assets/';
    }
    // If we're in a nested directory (services/, events/, rentals/ subdirectories)
    else if (currentPath.includes('services/') || currentPath.includes('events/') || currentPath.includes('rentals/')) {
        pathPrefix = '../../';
        assetsPrefix = '../../assets/';
    }

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
                    Get Quote
                </a>
            </div>
            
            <!-- Mobile Nav Button -->
            <div class="lg:hidden">
                <button id="mobile-menu-btn" class="text-gray-900 hover:text-blue-600 transition duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>
            </div>
        </nav>
    </header>
    `;

    // Insert navigation at the beginning of body
    document.body.insertAdjacentHTML('afterbegin', navigationHTML);
});
