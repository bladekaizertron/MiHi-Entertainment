    <!-- Video Section Template -->
    <script type="text/template" id="tpl-video">
<section data-editable class="relative overflow-hidden bg-gradient-to-r from-[#1F1F1F] via-[#1F1F1F] to-[#1F1F1F] text-white py-20 px-6" data-video-section>
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-10 left-1/2 -translate-x-1/2 w-[90%] h-full bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.15),transparent_60%)]"></div>
    </div>
    <div class="relative max-w-6xl mx-auto">
        <div class="text-center mb-16">
            <h2 contenteditable="true" class="text-3xl sm:text-4xl md:text-5xl font-bold mb-6 outline-none" style="font-family: 'Azo Sans Uber', sans-serif; font-weight: 400; color: #18F1E1; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">Video Showcase</h2>
            <p contenteditable="true" class="text-base md:text-lg text-white/85 leading-relaxed max-w-3xl mx-auto outline-none">Create share-worthy videos that capture the energy and emotion of your event.</p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6" id="video-cards-container">
            <!-- Initial video card -->
            <div class="video-card-item bg-white/10 border border-white/15 rounded-3xl overflow-hidden backdrop-blur transition-all duration-300 hover:-translate-y-1 relative group">
                <div class="aspect-video overflow-hidden bg-black/50 flex items-center justify-center relative video-player-container">
                    <span class="text-white/50 video-placeholder">Video Placeholder</span>
                    <video class="w-full h-full object-cover hidden video-element" controls></video>
                    <div class="w-full h-full hidden iframe-wrapper absolute inset-0"></div>
                    
                    <!-- Hover overlay for changing/removing video -->
                    <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center gap-3 z-10">
                        <button onclick="changeVideoInCard(this)" class="bg-[#18F1E1] hover:bg-[#15D9C9] text-black px-4 py-2 rounded-lg font-semibold text-sm flex items-center gap-2 transition-colors">
                            <i class="fas fa-video"></i> Change Video
                        </button>
                        <button onclick="removeVideoCard(this)" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold text-sm flex items-center gap-2 transition-colors">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <h4 contenteditable="true" class="text-xl font-semibold mb-2 outline-none" style="color: #18F1E1; font-family: 'Azo Sans', sans-serif;">Video Title</h4>
                    <p contenteditable="true" class="text-sm text-white/70 mb-4 leading-relaxed outline-none">Video description goes here.</p>
                    <a href="#" onclick="openVideoModal(event, this)" class="inline-flex items-center rounded-full bg-[#FF4F4F] px-5 py-2 text-white font-semibold hover:bg-[#FF3838] transition-colors">Watch Now</a>
                </div>
            </div>
        </div>
        
        <!-- Add Video Card Button -->
        <div class="mt-8 text-center">
            <button onclick="addVideoCard(this)" class="inline-flex items-center gap-2 px-6 py-3 rounded-full border-2 border-dashed border-[#18F1E1]/50 text-[#18F1E1] hover:bg-[#18F1E1]/10 hover:border-[#18F1E1] transition-all duration-300">
                <i class="fas fa-plus-circle"></i>
                <span>Add Video Card</span>
            </button>
        </div>
    </div>

</section>
    </script>
