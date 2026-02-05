    <!-- Gallery Image Grid Template (LookBook Style) -->
    <script type="text/template" id="tpl-gallery_grid">
<section data-editable class="relative overflow-hidden section-padding" style="background-image: radial-gradient(#021027, #000000);">
    <!-- Particle Effect Background -->
    <div class="particle-container" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none;"></div>
    
    <style>
        /* Lookbook Gallery Grid Styles - Modern Layout */
        .lookbook-gallery {
            width: 90vw;
            max-width: 1400px;
            margin: 4em auto 1em;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            grid-auto-rows: 60px;
            grid-auto-flow: dense;
            align-items: end;
            align-content: start;
            gap: 1.5rem;
            padding: 0 1rem 0.5rem;
            min-height: fit-content;
            position: relative;
            z-index: 1;
        }

        /* Ensure bottom row items align to bottom */
        .lookbook-gallery::after {
            content: '';
            grid-column: 1 / -1;
            height: 0;
            display: block;
        }

        /* Force all items to align to bottom of their grid cells */
        .lookbook-gallery .gallery-item {
            align-self: end;
        }

        .lookbook-gallery .large,
        .lookbook-gallery .medium,
        .lookbook-gallery .small {
            display: flex;
            height: 100%;
            width: 100%;
            grid-column: auto / span 1;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            border-radius: 16px;
            background: #000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 1;
            align-self: end;
        }

        .lookbook-gallery .gallery-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(255, 79, 79, 0.25), 0 8px 16px rgba(0, 0, 0, 0.2);
            border: 2px solid rgba(255, 79, 79, 0.3);
        }

        .gallery-item-title {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.7) 60%, rgba(0, 0, 0, 0.3) 80%, transparent 100%);
            color: white;
            padding: 1.25rem 1rem 1rem;
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            z-index: 1;
            opacity: 1;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(4px);
        }

        .gallery-item:hover .gallery-item-title {
            background: linear-gradient(to top, rgba(255, 79, 79, 0.95) 0%, rgba(255, 79, 79, 0.75) 60%, rgba(255, 79, 79, 0.4) 80%, transparent 100%);
            padding-bottom: 1.25rem;
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .lookbook-gallery {
                width: 95vw;
                gap: 1rem;
                margin: 2em auto;
            }

            .gallery-item-title {
                font-size: 0.75rem;
                padding: 1rem 0.75rem 0.75rem;
            }
        }

        .lookbook-gallery .large {
            grid-row: span 5;
            border-radius: 20px;
        }

        .lookbook-gallery .medium {
            grid-row: span 4;
        }

        .lookbook-gallery .small {
            grid-row: span 3;
        }

        .lookbook-gallery img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1), filter 0.4s ease;
        }

        .lookbook-gallery .gallery-item:hover img {
            transform: scale(1.08);
            filter: brightness(0.8) contrast(1.1);
        }

        /* Gallery item controls */
        .gallery-item-controls {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            gap: 0.5rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 10;
        }

        .gallery-item:hover .gallery-item-controls {
            opacity: 1;
        }

        .gallery-item-controls button {
            background: white;
            color: #1F1F1F;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .gallery-item-controls button:hover {
            transform: scale(1.05);
        }

        .gallery-item-controls button.remove-btn {
            background: #FF4F4F;
            color: white;
        }

        /* Particle Effect */
        .circle-container {
            position: absolute;
            transform: translateY(-10vh);
            animation-iteration-count: infinite;
            animation-timing-function: linear;
        }

        .circle-container .circle {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            mix-blend-mode: screen;
            background-image: radial-gradient(hsl(180, 100%, 80%), hsl(180, 100%, 80%) 10%, hsla(180, 100%, 80%, 0) 56%);
            animation: fadein-frames 200ms infinite, scale-frames 2s infinite;
        }

        @keyframes fadein-frames {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        @keyframes scale-frames {
            0% { transform: scale3d(0.4, 0.4, 1); }
            50% { transform: scale3d(2.2, 2.2, 1); }
            100% { transform: scale3d(0.4, 0.4, 1); }
        }

        @media (max-width: 1024px) {
            .lookbook-gallery {
                width: 92vw;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1.25rem;
            }
        }

        @media (max-width: 768px) {
            .lookbook-gallery {
                width: 95vw;
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                gap: 1rem;
                margin: 2em auto;
            }

            .lookbook-gallery .large,
            .lookbook-gallery .medium,
            .lookbook-gallery .small {
                border-radius: 12px;
            }
        }

        @media (max-width: 480px) {
            .lookbook-gallery {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 0.75rem;
            }
        }
    </style>
    
    <div class="container mx-auto px-4" style="position: relative; z-index: 1;">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Main Heading -->
            <h2 contenteditable="true" class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-light leading-tight mb-6 outline-none" style="text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5), 0 0 20px rgba(0, 80, 255, 0.3); font-family: 'Azo Sans Uber', sans-serif;">
                <span style="color: white;">A </span>
                <span style="color: #FF4F4F; text-shadow: 0 0 20px rgba(0, 0, 0, 0.8), 0 0 40px rgba(255, 79, 79, 0.4);">LOOKBOOK</span>
                <span style="color: white;"> THAT'S NEVER</span>
                <br class="hidden sm:block">
                <span style="color: white;">LOOKED </span>
                <span style="color: #18F1E1; text-shadow: 0 0 20px rgba(2, 2, 2, 0.8), 0 0 40px rgba(77, 219, 255, 0.4);">QUITE THIS GOOD</span>
            </h2>

            <!-- Descriptive Paragraph -->
            <p contenteditable="true" class="text-base md:text-lg max-w-2xl mx-auto mb-12 leading-relaxed outline-none" style="color: #e5e7eb; text-shadow: 0 2px 8px rgba(0, 0, 0, 0.7);">
                If any booth catches your eye, just click on its image to explore its dedicated web page and learn more about the product.
            </p>

            <!-- Secondary Heading with Underline -->
            <div>
                <h3 contenteditable="true" class="text-xl sm:text-2xl md:text-3xl font-light mb-3 outline-none" style="color: #18F1E1; text-shadow: 0 0 15px rgba(77, 166, 255, 0.6), 0 2px 10px rgba(0, 0, 0, 0.5); font-family: 'Azo Sans Uber', sans-serif;">
                    ALL ACTIVATIONS
                </h3>
                <div class="h-1 w-24 mx-auto" style="background: #18F1E1; box-shadow: 0 0 10px rgba(77, 166, 255, 0.6);"></div>
            </div>
        </div>

        <!-- Lookbook Gallery Grid -->
        <div class="lookbook-gallery" id="gallery-grid-container">
            <!-- Initial gallery items -->
            <div class="large gallery-item" data-size="large">
                <img src="" alt="Gallery image" style="display: none;">
                <div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 1.5rem;">Image Placeholder</div>
                <div contenteditable="true" class="gallery-item-title outline-none">GALLERY TITLE</div>
            </div>
            
            <div class="medium gallery-item" data-size="medium">
                <img src="" alt="Gallery image" style="display: none;">
                <div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; font-size: 1.5rem;">Image Placeholder</div>
                <div contenteditable="true" class="gallery-item-title outline-none">GALLERY TITLE</div>
            </div>
            
            <div class="small gallery-item" data-size="small">
                <img src="" alt="Gallery image" style="display: none;">
                <div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; font-size: 1.5rem;">Image Placeholder</div>
                <div contenteditable="true" class="gallery-item-title outline-none">GALLERY TITLE</div>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize particle effect
        (function() {
            const particleContainer = document.currentScript.closest('section').querySelector('.particle-container');
            if (!particleContainer) return;
            
            function createParticle() {
                const particle = document.createElement('div');
                particle.className = 'circle-container';
                const circle = document.createElement('div');
                circle.className = 'circle';
                particle.appendChild(circle);
                
                const size = Math.random() * 60 + 20;
                const left = Math.random() * 100;
                const duration = Math.random() * 20 + 15;
                const delay = Math.random() * 5;
                
                particle.style.cssText = `
                    width: ${size}px;
                    height: ${size}px;
                    left: ${left}%;
                    animation: float-up ${duration}s ${delay}s infinite linear;
                `;
                
                return particle;
            }
            
            // Create particles
            for (let i = 0; i < 15; i++) {
                particleContainer.appendChild(createParticle());
            }
            
            // Add float animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes float-up {
                    0% { transform: translateY(110vh); opacity: 0; }
                    10% { opacity: 1; }
                    90% { opacity: 1; }
                    100% { transform: translateY(-10vh); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        })();
    </script>
</section>
    </script>
