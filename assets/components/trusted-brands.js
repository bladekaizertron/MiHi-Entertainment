// Shared Trusted Brands Component
// Reusable component for displaying scrolling brand logos
// Usage: Load this script and call renderTrustedBrands() with optional config

console.log('Trusted brands component loaded');

// Default brand list
const defaultBrands = [
    { name: 'Smirnoff', image: 'smirnoff.png' },
    { name: 'Meow Wolf', image: 'meowwolf.png' },
    { name: 'Lexus', image: 'lexus.png' },
    { name: 'Justin\'s', image: 'justins.png' },
    { name: 'Ice Breakers', image: 'icebreakers.png' },
    { name: 'Google', image: 'google.png' },
    { name: 'Entravision', image: 'entravision.png' },
    { name: 'Dunkin\'', image: 'dunkin.png' },
    { name: 'Comcast', image: 'comcast.png' },
    { name: 'Coca Cola', image: 'cocacola.png' },
    { name: 'Adidas', image: 'adidas.png' },
    { name: 'Visa', image: 'visa.png' },
    { name: 'Sonic', image: 'sonic.png' },
    { name: 'Tumblr', image: 'tumblr.png' },
    { name: 'Southwest', image: 'southwest.png' },
    { name: 'Oracle', image: 'oracle.png' },
    { name: 'Express', image: 'express.png' },
    { name: 'Phantogram', image: 'phantogram.png' }
];

// Render trusted brands section
function renderTrustedBrands(options = {}) {
    // Determine the correct path prefix based on current page location
    const currentPath = window.location.pathname;
    let assetsPrefix = '';
    
    // Very simple path detection - always use 'assets/' for root, '../assets/' for everything else
    console.log('Determining assets prefix for path:', currentPath);
    
    // Since we're on the index.html page, we should use 'assets/'
    assetsPrefix = 'assets/';
    console.log('Using fixed path prefix: assets/');
    
    // Test a sample image path
    const testImagePath = `${assetsPrefix}images/trusted_by_brands/adidas.png`;
    console.log('Test image path:', testImagePath);
    
    // Debug logging to help troubleshoot path issues
    console.log('Current path:', currentPath);
    console.log('Assets prefix:', assetsPrefix);

    // Default configuration
    const config = {
        containerId: options.containerId || 'trusted-brands-container',
        badge: options.badge || 'Trusted By',
        title: options.title || {
            part1: 'Top Brands',
            part2: 'Planners'
        },
        description: options.description || 'Industry leaders trust MiHi Entertainment to deliver cutting-edge photo, video, and event experiences that captivate guests and elevate brand presence. From global corporations to top event planners, we create seamless, high-quality activations that leave a lasting impression.',
        brands: options.brands || defaultBrands,
        animationSpeed: options.animationSpeed || 30, // pixels per second
        sectionClass: options.sectionClass || 'bg-gradient-to-b from-white via-gray-50 to-white py-20',
        ...options
    };

    // Generate logo HTML
    function generateLogoHTML(brand, assetsPrefix) {
        const imagePath = `${assetsPrefix}images/trusted_by_brands/${brand.image}`;
        console.log('Generated image path:', imagePath);
        
        return `
            <div class="flex-shrink-0 h-20 w-40 flex items-center justify-center opacity-70 hover:opacity-100 transition-all duration-300 hover:scale-110">
                <img src="${imagePath}" alt="${brand.name}" class="max-h-full max-w-full object-contain" onerror="console.error('Failed to load image:', '${imagePath}'); 
                    // Hide the image but keep the container
                    this.style.display='none';
                    // Create a fallback text element
                    const fallback = document.createElement('div');
                    fallback.className = 'text-xs text-gray-500 text-center';
                    fallback.textContent = '${brand.name}';
                    this.parentElement.appendChild(fallback);
                ">
            </div>
        `;
    }

    // Generate logos set
    function generateLogosSet(brands, assetsPrefix) {
        return brands.map(brand => generateLogoHTML(brand, assetsPrefix)).join('\n');
    }

    const trustedBrandsHTML = `
        <!-- Trusted Brands Section -->
        <section class="${config.sectionClass}" aria-label="Trusted brands">
            <div class="container mx-auto px-6">
                <!-- Premium Header -->
                <div class="text-center mb-12 max-w-4xl mx-auto">
                    <div class="inline-block px-6 py-2 bg-gradient-to-r from-[#0050ff] to-[#0040d9] rounded-full mb-6">
                        <h3 class="text-sm font-bold text-white uppercase tracking-widest">
                            ${config.badge}
                        </h3>
                    </div>
                    <h2 class="text-4xl md:text-5xl font-bold mb-6">
                        <span class="bg-gradient-to-r from-gray-900 via-[#0050ff] to-gray-900 bg-clip-text text-transparent">${config.title.part1}</span> and <span class="bg-gradient-to-r from-[#0050ff] to-[#0040d9] bg-clip-text text-transparent">${config.title.part2}</span>
                    </h2>
                    <p class="text-lg text-gray-700 leading-relaxed">
                        ${config.description}
                    </p>
                </div>
                
                <!-- Debug info -->
                <div style="display: none;" id="debug-info">
                    <p>Assets prefix: ${assetsPrefix}</p>
                    <p>Current path: ${window.location.pathname}</p>
                </div>
                
                <!-- Scrolling Logos Container -->
                <div class="relative overflow-hidden py-8" id="trusted-brands-scroll-container">
                    <!-- Gradient Overlays -->
                    <div class="absolute left-0 top-0 bottom-0 w-32 bg-gradient-to-r from-white via-gray-50/80 to-transparent z-10 pointer-events-none"></div>
                    <div class="absolute right-0 top-0 bottom-0 w-32 bg-gradient-to-l from-white via-gray-50/80 to-transparent z-10 pointer-events-none"></div>
                    
                    <!-- Scrolling Track -->
                    <div class="flex" id="trusted-brands-track">
                        <!-- First Set of Logos -->
                        <div class="flex gap-10 items-center" id="trusted-brands-set1">
                            ${generateLogosSet(config.brands, assetsPrefix)}
                        </div>
                        
                        <!-- Duplicate Set for Seamless Loop -->
                        <div class="flex gap-10 items-center" id="trusted-brands-set2">
                            ${generateLogosSet(config.brands, assetsPrefix)}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    `;

    // Insert into page
    console.log('Looking for container with id:', config.containerId);
    const container = document.getElementById(config.containerId);
    console.log('Container found:', container);
    
    if (container) {
        console.log('Rendering trusted brands section');
        container.innerHTML = trustedBrandsHTML;
        console.log('Trusted brands HTML inserted');
        
        // Initialize the JavaScript-based scrolling
        setTimeout(() => {
            initializeScrolling(config.animationSpeed);
        }, 100);
    } else {
        console.error(`Container with id "${config.containerId}" not found. Please add <div id="${config.containerId}"></div> to your page.`);
        
        // Try to find all elements with this id
        const allElements = document.querySelectorAll(`[id="${config.containerId}"]`);
        console.log('All elements with this id:', allElements.length, allElements);
    }
}

// Pure JavaScript scrolling implementation
function initializeScrolling(speed) {
    const container = document.getElementById('trusted-brands-scroll-container');
    const track = document.getElementById('trusted-brands-track');
    const set1 = document.getElementById('trusted-brands-set1');
    const set2 = document.getElementById('trusted-brands-set2');
    
    if (!container || !track || !set1 || !set2) {
        console.error('Trusted brands elements not found');
        return;
    }
    
    // Set initial styles
    track.style.display = 'flex';
    track.style.transition = 'none';
    
    // Get the width of one set
    const setWidth = set1.offsetWidth;
    
    // Set initial position
    let position = 0;
    let animationId = null;
    let isPaused = false;
    
    // Calculate pixels per frame (assuming 60fps)
    const pixelsPerFrame = speed / 60;
    
    // Animation function
    function animate() {
        if (!isPaused) {
            position -= pixelsPerFrame;
            
            // Reset position when we've scrolled one full set
            if (position <= -setWidth) {
                position = 0;
            }
            
            track.style.transform = `translateX(${position}px)`;
        }
        
        animationId = requestAnimationFrame(animate);
    }
    
    // Start animation
    animationId = requestAnimationFrame(animate);
    
    // Pause on hover
    container.addEventListener('mouseenter', () => {
        isPaused = true;
    });
    
    container.addEventListener('mouseleave', () => {
        isPaused = false;
    });
    
    // Handle window resize
    window.addEventListener('resize', () => {
        // Restart animation on resize
        if (animationId) {
            cancelAnimationFrame(animationId);
        }
        setTimeout(() => {
            initializeScrolling(speed);
        }, 100);
    });
}

// Polyfill for requestAnimationFrame
window.requestAnimationFrame = window.requestAnimationFrame || 
                             window.webkitRequestAnimationFrame || 
                             window.mozRequestAnimationFrame || 
                             function(callback) { return setTimeout(callback, 1000 / 60); };

window.cancelAnimationFrame = window.cancelAnimationFrame || 
                              window.webkitCancelAnimationFrame || 
                              window.mozCancelAnimationFrame || 
                              function(id) { clearTimeout(id); };

// Auto-render on page load if container exists
function initTrustedBrands() {
    console.log('Initializing trusted brands component');
    
    // Try multiple ways to find the container
    let container = document.getElementById('trusted-brands-container');
    console.log('Container by ID:', container);
    
    if (!container) {
        // Try query selector
        container = document.querySelector('#trusted-brands-container');
        console.log('Container by query selector:', container);
    }
    
    if (!container) {
        // Try to find all elements with this ID
        const allContainers = document.querySelectorAll('[id="trusted-brands-container"]');
        console.log('All containers with this ID:', allContainers.length, allContainers);
        
        if (allContainers.length > 0) {
            container = allContainers[0];
            console.log('Using first container found');
        }
    }
    
    if (container) {
        console.log('Trusted brands container found');
        // Small delay to ensure all resources are loaded
        setTimeout(() => {
            renderTrustedBrands();
        }, 100);
    } else {
        console.error('Trusted brands container not found');
    }
}

// Try multiple event listeners for better compatibility
console.log('Document ready state:', document.readyState);
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTrustedBrands);
    console.log('Added DOMContentLoaded listener');
} else {
    // DOM is already loaded
    console.log('DOM already loaded, initializing trusted brands');
    initTrustedBrands();
}

// Also listen for window load event
window.addEventListener('load', function() {
    console.log('Window load event fired');
    initTrustedBrands();
});

// Additional check to ensure component loads even if other events fail
setTimeout(function() {
    const container = document.getElementById('trusted-brands-container');
    if (container && !container.hasChildNodes()) {
        console.log('Fallback initialization triggered');
        initTrustedBrands();
    }
}, 3000);

// Manual initialization function for testing
window.initTrustedBrandsManually = function() {
    console.log('Manual initialization called');
    renderTrustedBrands();
};

// Test function to verify the component is working
window.testTrustedBrands = function() {
    console.log('Testing trusted brands component');
    const container = document.getElementById('trusted-brands-container');
    console.log('Container:', container);
    
    if (container) {
        console.log('Container found, rendering brands');
        renderTrustedBrands();
    } else {
        console.error('Container not found');
    }
};