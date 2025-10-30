// Shared Footer Component
// This script will load the same footer on all pages

document.addEventListener('DOMContentLoaded', function() {
    // Determine the correct path prefix based on current page location
    const currentPath = window.location.pathname;
    let pathPrefix = '../';
    
    // If we're in the root directory
    if (currentPath.endsWith('index.html') || currentPath.endsWith('/')) {
        pathPrefix = '';
    }
    // If we're in a nested directory (services/, events/, rentals/, about/ subdirectories)
    else if (currentPath.includes('services/') || currentPath.includes('events/') || currentPath.includes('rentals/') || currentPath.includes('about/')) {
        pathPrefix = '../../';
    }
    else if (currentPath.includes('/')) {
        pathPrefix = '../';
    }

    const footerHTML = `
    <footer class="bg-gray-900 text-gray-400 py-20">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-12">
                <!-- Column 1: About -->
                <div>
                    <img src="${pathPrefix}assets/images/logo.png" alt="MiHi Entertainment" class="h-14 mb-6" />
                    <p class="mb-6 text-gray-300">Unforgettable events, effortless experiences. Nationwide service with premium quality and exceptional customer care.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.148 3.227-1.669 4.771-4.919 4.919-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-3.252-.148-4.771-1.691-4.919-4.919-.058-1.265-.07-1.646-.07-4.85s.012-3.584.07-4.85c.148-3.227 1.669-4.771 4.919-4.919C8.416 2.175 8.796 2.163 12 2.163m0-2.163C8.74 0 8.333.012 7.053.072 2.695.272.273 2.69.073 7.052.012 8.333 0 8.74 0 12s.012 3.667.072 4.947c.2 4.358 2.618 6.78 6.98 6.98C8.333 23.988 8.74 24 12 24s3.667-.012 4.947-.072c4.354-.2 6.782-2.618 6.979-6.98.06-1.28.072-1.687.072-4.947s-.012-3.667-.072-4.947C23.728 2.69 21.306.27 16.947.072 15.667.012 15.26 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405a1.44 1.44 0 11-2.88 0 1.44 1.44 0 012.88 0z"></path>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M22.675 0h-21.35C.59 0 0 .59 0 1.325v21.35C0 23.41.59 24 1.325 24H12.82v-9.29H9.69v-3.62h3.13V8.41c0-3.1 1.89-4.79 4.66-4.79 1.32 0 2.46.1 2.79.14v3.24h-1.9c-1.5 0-1.79.71-1.79 1.76v2.31h3.59l-.47 3.62h-3.12V24h5.7c.73 0 1.32-.59 1.32-1.32V1.32C24 .59 23.41 0 22.675 0z"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                
                <!-- Column 2: Services -->
                <div>
                    <h5 class="text-white font-bold text-lg mb-6">Services</h5>
                    <ul class="space-y-3">
                        <li><a href="${pathPrefix}index.html#photo-booths" class="hover:text-white transition duration-300">Photo Booths</a></li>
                        <li><a href="${pathPrefix}index.html#video-booths" class="hover:text-white transition duration-300">Video Booths</a></li>
                        <li><a href="${pathPrefix}index.html#additional-experiences" class="hover:text-white transition duration-300">Additional Experiences</a></li>
                        <li><a href="${pathPrefix}index.html#rentals" class="hover:text-white transition duration-300">Event Production</a></li>
                        <li><a href="${pathPrefix}index.html#rentals" class="hover:text-white transition duration-300">AV & Decor</a></li>
                        <li><a href="${pathPrefix}index.html#rentals" class="hover:text-white transition duration-300">Game Rentals</a></li>
                    </ul>
                </div>
                
                <!-- Column 3: Event Types -->
                <div>
                    <h5 class="text-white font-bold text-lg mb-6">Event Types</h5>
                    <ul class="space-y-3">
                        <li><a href="${pathPrefix}events/weddings.html" class="hover:text-white transition duration-300">Wedding Events</a></li>
                        <li><a href="${pathPrefix}events/corporate-events.html" class="hover:text-white transition duration-300">Corporate Events</a></li>
                        <li><a href="${pathPrefix}events/social-events.html" class="hover:text-white transition duration-300">Social Events</a></li>
                        <li><a href="${pathPrefix}events/trade-shows.html" class="hover:text-white transition duration-300">Trade Show Services</a></li>
                        <li><a href="${pathPrefix}events/holiday-parties.html" class="hover:text-white transition duration-300">Holiday Parties</a></li>
                        <li><a href="${pathPrefix}events/casino-parties.html" class="hover:text-white transition duration-300">Casino Parties</a></li>
                    </ul>
                </div>
                
                <!-- Column 4: Quick Links -->
                <div>
                    <h5 class="text-white font-bold text-lg mb-6">Quick Links</h5>
                    <ul class="space-y-3">
                        <li><a href="${pathPrefix}index.html#about" class="hover:text-white transition duration-300">About Us</a></li>
                        <li><a href="${pathPrefix}index.html#galleries" class="hover:text-white transition duration-300">View Gallery</a></li>
                        <li><a href="${pathPrefix}index.html#contact" class="hover:text-white transition duration-300">Get a Quote</a></li>
                        <li><a href="${pathPrefix}index.html#contact" class="hover:text-white transition duration-300">Contact Us</a></li>
                        <li><a href="${pathPrefix}about/blog.html" class="hover:text-white transition duration-300">Blog</a></li>
                        <li><a href="${pathPrefix}about/faq.html" class="hover:text-white transition duration-300">FAQ</a></li>
                    </ul>
                </div>
            </div>
            
            <hr class="border-t border-gray-700 my-10">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-center md:text-left text-sm">&copy; 2025 MiHi Entertainment. All rights reserved.</p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-sm hover:text-white transition duration-300">Privacy Policy</a>
                    <a href="#" class="text-sm hover:text-white transition duration-300">Terms of Service</a>
                    <a href="#" class="text-sm hover:text-white transition duration-300">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>
    `;

    // Insert footer before closing body tag
    document.body.insertAdjacentHTML('beforeend', footerHTML);
});
