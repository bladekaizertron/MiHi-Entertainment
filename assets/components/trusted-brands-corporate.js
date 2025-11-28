// Trusted Brands Component for Corporate Events with Infinite Carousel
console.log('Corporate trusted brands component loaded');

// Brand list matching the actual image files in trusted_by_brands directory
const corporateBrands = [
    { name: 'Adidas', image: 'adidas.png' },
    { name: 'Coca Cola', image: 'cocacola.png' },
    { name: 'Comcast', image: 'comcast.png' },
    { name: 'Dunkin', image: 'dunkin.png' },
    { name: 'Entravision', image: 'entravision.png' },
    { name: 'Express', image: 'express.png' },
    { name: 'Google', image: 'google.png' },
    { name: 'Ice Breakers', image: 'icebreakers.png' },
    { name: 'Justin\'s', image: 'justins.png' },
    { name: 'Lexus', image: 'lexus.png' },
    { name: 'Meow Wolf', image: 'meowwolf.png' },
    { name: 'Oracle', image: 'oracle.png' },
    { name: 'Phantogram', image: 'phantogram.png' },
    { name: 'Smirnoff', image: 'smirnoff.png' },
    { name: 'Sonic', image: 'sonic.png' },
    { name: 'Southwest', image: 'southwest.png' },
    { name: 'Tumblr', image: 'tumblr.png' },
    { name: 'Visa', image: 'visa.png' }
];

// Duplicate the brands array to create a seamless loop
const duplicatedBrands = [...corporateBrands, ...corporateBrands, ...corporateBrands];

// Add CSS for the animation
const style = document.createElement('style');
style.textContent = `
    @keyframes scroll {
        0% { transform: translateX(0); }
        100% { transform: translateX(calc(-300px * ${corporateBrands.length})); } /* Adjusted for larger logos */
    }
    .brands-carousel {
        position: relative;
        width: 100%;
        overflow: hidden;
        padding: 0.5rem 0 1rem;
    }
    .brands-label {
        text-align: center;
        color: #111827; /* gray-900 - darker for better contrast */
        font-size: 1.125rem; /* text-lg */
        font-weight: 600; /* font-semibold */
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-bottom: 1rem;
        position: relative;
        display: inline-block;
        width: 100%;
    }
    .brands-label:after {
        content: '';
        display: block;
        width: 50px;
        height: 3px;
        background: #3B82F6; /* blue-500 */
        margin: 0.5rem auto 0;
        border-radius: 3px;
    }
    .brands-track {
        display: flex;
        width: calc(280px * ${duplicatedBrands.length}); /* Adjusted for larger logos */
        animation: scroll 60s linear infinite;
    }
    .brand-logo {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 180px; /* Increased from 150px */
        padding: 0 20px; /* Increased padding for better spacing */
        opacity: 0.8;
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    .brand-logo:hover {
        opacity: 1;
        transform: scale(1.1);
    }
    .brand-logo img {
        max-width: 100%;
        height: 70px; /* Increased from 40px */
        width: auto;
        object-fit: contain;
        transition: transform 0.3s ease; /* Smooth hover effect */
    }
`;
document.head.appendChild(style);

// Render trusted brands section with carousel
function renderCorporateBrands() {
    const container = document.getElementById('trusted-brands-container');
    if (!container) {
        console.error('Trusted brands container not found');
        return;
    }

    // Create carousel container
    const carousel = document.createElement('div');
    carousel.className = 'brands-carousel';
    
    // Create track that will be animated
    const track = document.createElement('div');
    track.className = 'brands-track';
    
    // Add brand logos to the track
    duplicatedBrands.forEach((brand, index) => {
        const logoDiv = document.createElement('div');
        logoDiv.className = 'brand-logo';
        
        const img = document.createElement('img');
        const imgPath = `assets/images/trusted_by_brands/${brand.image}`;
        img.src = imgPath;
        img.alt = brand.name;
        img.loading = 'lazy';
        
        img.onerror = function() {
            console.error('Failed to load image:', this.src);
            this.src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMjQgMjQiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzljYTVmZiIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiPjxyZWN0IHg9IjMiIHk9IjMiIHdpZHRoPSIxOCIgaGVpZ2h0PSIxOCIgcng9IjIiIHJ5PSIyIj48L3JlY3Q+PGNpcmNsZSBjeD0iOC41IiBjeT0iOS41IiByPSIxLjUiPjwvY2lyY2xlPjxwb2x5bGluZSBwb2ludHM9IjIxIDE1IDE2IDEwIDUgMjEiPjwvcG9seWxpbmU+PC9zdmc+';
        };
        
        logoDiv.appendChild(img);
        track.appendChild(logoDiv);
    });
    
    // Create and add the label
    const label = document.createElement('div');
    label.className = 'brands-label';
    label.textContent = 'Brands We\'ve Worked With';
    
    // Add elements to container
    container.innerHTML = '';
    container.appendChild(label);
    container.appendChild(carousel);
    carousel.appendChild(track);
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderCorporateBrands);
} else {
    renderCorporateBrands();
}
