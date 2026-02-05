    <!-- Split Screen Template -->
    <script type="text/template" id="tpl-split">
<section data-editable class="py-20 px-6 bg-white">
    <div class="max-w-6xl mx-auto grid lg:grid-cols-2 gap-12 items-center" id="split-screen-grid">
        <div id="split-screen-text-content">
            <h2 contenteditable="true" class="text-3xl sm:text-4xl md:text-5xl font-bold mb-6 outline-none" style="font-family: 'Azo Sans Uber', sans-serif; font-weight: 400; text-transform: uppercase; letter-spacing: 0.02em; color: #FF4F4F; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">Split Screen Section</h2>
            <p contenteditable="true" class="text-base md:text-lg leading-relaxed mb-8 outline-none" style="color: #1F1F1F;">This is a split screen layout with content on one side and an image placeholder on the other.</p>
            <div class="space-y-4 mb-8" id="split-screen-feature-points">
                <div class="flex gap-3 split-screen-feature-item group" data-feature-index="1">
                    <div class="w-10 h-10 rounded-full bg-pink-500/10 flex items-center justify-center text-pink-600 font-bold flex-shrink-0 border border-pink-500/20 split-screen-feature-number">1</div>
                    <div class="flex-1">
                        <p contenteditable="true" class="font-semibold outline-none" style="color: #1F1F1F;">Feature Point One</p>
                        <p contenteditable="true" class="text-sm outline-none" style="color: #1F1F1F;">Description of feature</p>
                    </div>
                    <button data-action="remove-feature" class="text-red-600 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity p-1" title="Remove feature point">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="flex gap-3 split-screen-feature-item group" data-feature-index="2">
                    <div class="w-10 h-10 rounded-full bg-purple-500/10 flex items-center justify-center text-purple-600 font-bold flex-shrink-0 border border-purple-500/20 split-screen-feature-number">2</div>
                    <div class="flex-1">
                        <p contenteditable="true" class="font-semibold outline-none" style="color: #1F1F1F;">Feature Point Two</p>
                        <p contenteditable="true" class="text-sm outline-none" style="color: #1F1F1F;">Description of feature</p>
                    </div>
                    <button data-action="remove-feature" class="text-red-600 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity p-1" title="Remove feature point">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="flex gap-3 split-screen-feature-item group" data-feature-index="3">
                    <div class="w-10 h-10 rounded-full bg-red-500/10 flex items-center justify-center text-red-600 font-bold flex-shrink-0 border border-red-500/20 split-screen-feature-number">3</div>
                    <div class="flex-1">
                        <p contenteditable="true" class="font-semibold outline-none" style="color: #1F1F1F;">Feature Point Three</p>
                        <p contenteditable="true" class="text-sm outline-none" style="color: #1F1F1F;">Description of feature</p>
                    </div>
                    <button data-action="remove-feature" class="text-red-600 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity p-1" title="Remove feature point">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="#" class="inline-flex items-center justify-center px-8 py-4 rounded-full font-semibold text-lg bg-[#FF4F4F] hover:bg-[#FF3838] text-white transition-all duration-300 hover:-translate-y-1 hover:scale-105">Get Your Quote</a>
                <a href="#" class="inline-flex items-center justify-center px-7 py-3 rounded-full font-semibold border-2 border-[#18F1E1] text-[#18F1E1] bg-white hover:bg-[#18F1E1] hover:text-black transition-all duration-300">Call Us</a>
            </div>
        </div>
        <div class="relative" data-editable="true" id="split-screen-media-content">
            <div class="relative bg-white border border-gray-200/50 rounded-[28px] overflow-hidden shadow-[0_24px_60px_-18px_rgba(0,0,0,0.12)] group">
                <div class="w-full h-[500px] bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center relative" id="split-screen-media-container">
                    <img src="" alt="" class="w-full h-full object-cover hidden" id="split-screen-image">
                    <video src="" controls class="w-full h-full object-cover hidden" id="split-screen-video"></video>
                    <span class="text-gray-400" id="split-screen-placeholder">Media Placeholder</span>
                    
                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center z-10" id="split-screen-media-overlay">
                        <div class="flex flex-col gap-3 px-4">
                            <button data-action="change-media" data-type="photo" class="bg-white hover:bg-gray-100 text-gray-900 px-6 py-3 rounded-lg font-semibold text-sm flex items-center gap-2 transition-colors shadow-lg">
                                <i class="fas fa-image"></i>
                                <span>Change Photo</span>
                            </button>
                            <button data-action="change-media" data-type="video" class="bg-white hover:bg-gray-100 text-gray-900 px-6 py-3 rounded-lg font-semibold text-sm flex items-center gap-2 transition-colors shadow-lg">
                                <i class="fas fa-video"></i>
                                <span>Change Video</span>
                            </button>
                            <button data-action="remove-media" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold text-sm flex items-center gap-2 transition-colors shadow-lg" id="split-screen-remove-btn" style="display: none;">
                                <i class="fas fa-trash"></i>
                                <span>Remove Media</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
    </script>
