    <!-- Cards Section Template -->
    <script type="text/template" id="tpl-cards">
        <section data-editable class="py-16 px-6 bg-white">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-12">
                    <h2 contenteditable="true" class="text-3xl sm:text-4xl md:text-5xl font-bold mb-6 outline-none" style="font-family: 'Azo Sans Uber', sans-serif; font-weight: 400; text-transform: uppercase; letter-spacing: 0.02em; color: #FF4F4F; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">Feature Cards</h2>
                    <p contenteditable="true" class="text-base md:text-lg leading-relaxed max-w-3xl mx-auto outline-none" style="font-family: 'Azo Sans', sans-serif; color: #1F1F1F;">Discover the amazing features that make our service stand out from the rest.</p>
                </div>
                <div class="grid md:grid-cols-3 gap-8" id="feature-cards-container">
                    <!-- Initial card 1 -->
                    <div class="feature-card-item bg-white p-6 rounded-2xl shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300 relative group">
                        <div class="w-12 h-12 bg-pink-500 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 contenteditable="true" class="text-xl font-bold mb-2 outline-none" style="font-family: 'Azo Sans', sans-serif; color: #FF4F4F;">Feature One</h3>
                        <p contenteditable="true" class="outline-none" style="font-family: 'Azo Sans', sans-serif; color: #1F1F1F;">Description of the feature.</p>
                        
                        <!-- Remove button overlay -->
                        <button onclick="removeFeatureCard(this)" data-editor-only class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white w-8 h-8 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center shadow-lg z-10" title="Remove card">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Initial card 2 -->
                    <div class="feature-card-item bg-white p-6 rounded-2xl shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300 relative group">
                        <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <h3 contenteditable="true" class="text-xl font-bold mb-2 outline-none" style="font-family: 'Azo Sans', sans-serif; color: #FF4F4F;">Feature Two</h3>
                        <p contenteditable="true" class="outline-none" style="font-family: 'Azo Sans', sans-serif; color: #1F1F1F;">Description of the feature.</p>
                        
                        <!-- Remove button overlay -->
                        <button onclick="removeFeatureCard(this)" data-editor-only class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white w-8 h-8 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center shadow-lg z-10" title="Remove card">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Initial card 3 -->
                    <div class="feature-card-item bg-white p-6 rounded-2xl shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300 relative group">
                        <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <h3 contenteditable="true" class="text-xl font-bold mb-2 outline-none" style="font-family: 'Azo Sans', sans-serif; color: #FF4F4F;">Feature Three</h3>
                        <p contenteditable="true" class="outline-none" style="font-family: 'Azo Sans', sans-serif; color: #1F1F1F;">Description of the feature.</p>
                        
                        <!-- Remove button overlay -->
                        <button onclick="removeFeatureCard(this)" data-editor-only class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white w-8 h-8 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center shadow-lg z-10" title="Remove card">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Add Card Button -->
                <div class="mt-8 text-center" data-editor-only>
                    <button onclick="addFeatureCard(this)" class="inline-flex items-center gap-2 px-6 py-3 rounded-full border-2 border-dashed border-[#FF4F4F]/50 text-[#FF4F4F] hover:bg-[#FF4F4F]/10 hover:border-[#FF4F4F] transition-all duration-300">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Card</span>
                    </button>
                </div>
            </div>
        </section>
    </script>
