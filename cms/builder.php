<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$pageData = null;
$pageId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($pageId) {
    $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$pageId]);
    $pageData = $stmt->fetch();
    
    if (!$pageData) {
        die("Page not found");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageData ? 'Edit Page' : 'Create New Page'; ?> - Website Builder</title>
    
    <!-- GrapesJS Core (Local) -->
    <link rel="stylesheet" href="assets/lib/grapes.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body, html {
            height: 100%;
            overflow: hidden;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0f0f0f;
        }
        
        #gjs {
            border: none;
            height: calc(100vh - 64px);
            width: 100%;
        }
        
        /* Modern GrapesJS Panel Styling */
        .gjs-pn-panel {
            background: linear-gradient(180deg, #1a1a1a 0%, #0f0f0f 100%) !important;
            border-right: 1px solid rgba(255,255,255,0.05) !important;
        }
        
        .gjs-pn-btn {
            color: #a0a0a0 !important;
            transition: all 0.2s ease !important;
            border-radius: 8px !important;
            margin: 4px !important;
        }
        
        .gjs-pn-btn:hover {
            background: rgba(99, 102, 241, 0.1) !important;
            color: #6366f1 !important;
        }
        
        .gjs-pn-active {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3) !important;
        }
        
        /* Block Manager Styling */
        .gjs-block {
            background: rgba(255,255,255,0.03) !important;
            border: 1px solid rgba(255,255,255,0.08) !important;
            border-radius: 12px !important;
            transition: all 0.3s ease !important;
            backdrop-filter: blur(10px) !important;
        }
        
        .gjs-block:hover {
            background: rgba(99, 102, 241, 0.1) !important;
            border-color: #6366f1 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.2) !important;
        }
        
        .gjs-block-label {
            color: #e0e0e0 !important;
            font-weight: 500 !important;
            font-size: 12px !important;
        }
        
        /* Canvas Styling */
        .gjs-cv-canvas {
            background: #1a1a1a !important;
            background-image: 
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px) !important;
            background-size: 20px 20px !important;
        }
        
        /* Top Bar - Modern Design */
        .top-bar {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            color: white;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 24px rgba(0,0,0,0.4);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            position: relative;
            z-index: 1000;
        }
        
        .top-bar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.5), transparent);
        }
        
        .top-bar h1 {
            font-size: 16px;
            font-weight: 600;
            background: linear-gradient(135deg, #fff 0%, #a0a0a0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.02em;
        }
        
        .top-bar-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .btn:hover::before {
            opacity: 1;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.05);
            color: #e0e0e0;
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
        }
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.08);
            border-color: rgba(255,255,255,0.2);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
        }
        
        /* Loading Overlay - Modern */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            display: none;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .spinner {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(99, 102, 241, 0.1);
            border-top: 3px solid #6366f1;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            box-shadow: 0 0 30px rgba(99, 102, 241, 0.3);
        }
        
        .loading-overlay::after {
            content: 'Saving...';
            color: #a0a0a0;
            font-size: 14px;
            font-weight: 500;
            margin-top: 20px;
            letter-spacing: 0.05em;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Style Manager Enhancements */
        .gjs-sm-sector {
            background: rgba(255,255,255,0.02) !important;
            border: 1px solid rgba(255,255,255,0.05) !important;
            border-radius: 12px !important;
            margin-bottom: 12px !important;
        }
        
        .gjs-sm-sector-title {
            background: rgba(99, 102, 241, 0.05) !important;
            color: #e0e0e0 !important;
            font-weight: 600 !important;
            padding: 12px 16px !important;
            border-radius: 12px 12px 0 0 !important;
        }
        
        .gjs-sm-property {
            background: transparent !important;
            border-bottom: 1px solid rgba(255,255,255,0.03) !important;
        }
        
        .gjs-sm-label {
            color: #a0a0a0 !important;
            font-size: 12px !important;
            font-weight: 500 !important;
        }
        
        /* Input Fields */
        .gjs-field,
        .gjs-sm-input {
            background: rgba(255,255,255,0.05) !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            color: #e0e0e0 !important;
            border-radius: 8px !important;
            padding: 8px 12px !important;
            transition: all 0.2s !important;
        }
        
        .gjs-field:focus,
        .gjs-sm-input:focus {
            background: rgba(255,255,255,0.08) !important;
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
        }
        
        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.02);
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.3);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.5);
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <h1><?php echo $pageData ? 'Editing: ' . htmlspecialchars($pageData['title']) : 'Create New Page'; ?></h1>
        <div class="top-bar-actions">
            <button id="save-btn" class="btn btn-success">💾 Save Page</button>
            <button id="preview-btn" class="btn btn-primary">👁️ Preview</button>
            <a href="index.php" class="btn btn-secondary">← Back to Pages</a>
        </div>
    </div>
    
    <div id="gjs"></div>
    
    <div class="loading-overlay" id="loading">
        <div class="spinner"></div>
    </div>
    
    <!-- GrapesJS Core (Local) -->
    <script src="assets/lib/grapes.min.js"></script>
    
    <script>
        const pageId = <?php echo $pageId ? $pageId : 'null'; ?>;
        const pageData = <?php echo $pageData ? json_encode($pageData) : 'null'; ?>;
        
        // Initialize GrapesJS
        const editor = grapesjs.init({
            container: '#gjs',
            height: 'calc(100vh - 60px)',
            width: 'auto',
            fromElement: false,
            storageManager: false,
            canvas: {
                styles: [
                    'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css',
                    '../assets/css/style.css'
                ],
                scripts: [
                    'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js'
                ]
            },
            assetManager: {
                upload: 'save_asset.php',
                uploadName: 'files',
                multiUpload: true,
                assets: []
            },
            styleManager: {
                sectors: [{
                    name: 'General',
                    open: true,
                    properties: [
                        'display',
                        'position',
                        'top',
                        'right',
                        'left',
                        'bottom',
                    ],
                }, {
                    name: 'Dimension',
                    open: false,
                    properties: [
                        'width',
                        'height',
                        'max-width',
                        'min-height',
                        'margin',
                        'padding',
                    ],
                }, {
                    name: 'Typography',
                    open: false,
                    properties: [
                        'font-family',
                        'font-size',
                        'font-weight',
                        'letter-spacing',
                        'color',
                        'line-height',
                        'text-align',
                        'text-decoration',
                    ],
                }, {
                    name: 'Decorations',
                    open: false,
                    properties: [
                        'opacity',
                        'border-radius',
                        'border',
                        'box-shadow',
                        'background',
                    ],
                }],
            },
            deviceManager: {
                devices: [{
                    name: 'Desktop',
                    width: '',
                }, {
                    name: 'Tablet',
                    width: '768px',
                    widthMedia: '992px',
                }, {
                    name: 'Mobile',
                    width: '375px',
                    widthMedia: '480px',
                }]
            },
        });
        
        // Add custom blocks with modern styling
        const blockManager = editor.BlockManager;
        
        // Basic Components
        blockManager.add('section', {
            label: '📦 Section',
            category: 'Basic',
            content: '<section class="py-16 px-6"><div class="container mx-auto"><h2 class="text-3xl font-bold mb-4">New Section</h2><p class="text-gray-600">Add your content here</p></div></section>',
            attributes: { class: 'gjs-block-section' }
        });
        
        blockManager.add('text', {
            label: '📝 Text',
            category: 'Basic',
            content: '<p class="text-base text-gray-700 leading-relaxed">Insert your text here. Double-click to edit.</p>',
            attributes: { class: 'gjs-block-text' }
        });
        
        blockManager.add('heading', {
            label: '🔤 Heading',
            category: 'Basic',
            content: '<h2 class="text-4xl font-bold text-gray-900 mb-4">Your Heading</h2>',
            attributes: { class: 'gjs-block-heading' }
        });
        
        blockManager.add('image', {
            label: '🖼️ Image',
            category: 'Basic',
            content: '<div class="relative overflow-hidden rounded-xl"><img src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=800" alt="Image" class="w-full h-auto object-cover"/></div>',
            attributes: { class: 'gjs-block-image' }
        });
        
        blockManager.add('button', {
            label: '🔘 Button',
            category: 'Basic',
            content: '<a href="#" class="inline-block px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold rounded-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105">Click Me</a>',
            attributes: { class: 'gjs-block-button' }
        });
        
        blockManager.add('video', {
            label: '🎥 Video',
            category: 'Basic',
            content: '<div class="relative rounded-xl overflow-hidden shadow-2xl"><video class="w-full" controls><source src="" type="video/mp4">Your browser does not support the video tag.</video></div>',
            attributes: { class: 'gjs-block-video' }
        });
        
        // Layout Components
        blockManager.add('container', {
            label: '📐 Container',
            category: 'Layout',
            content: '<div class="container mx-auto px-6 py-12"><p class="text-gray-600">Container content</p></div>',
            attributes: { class: 'gjs-block-container' }
        });
        
        blockManager.add('columns-2', {
            label: '⚏ 2 Columns',
            category: 'Layout',
            content: '<div class="grid grid-cols-1 md:grid-cols-2 gap-8"><div class="bg-white p-8 rounded-xl shadow-lg"><h3 class="text-xl font-bold mb-2">Column 1</h3><p class="text-gray-600">Content here</p></div><div class="bg-white p-8 rounded-xl shadow-lg"><h3 class="text-xl font-bold mb-2">Column 2</h3><p class="text-gray-600">Content here</p></div></div>',
            attributes: { class: 'gjs-block-columns' }
        });
        
        blockManager.add('columns-3', {
            label: '⚌ 3 Columns',
            category: 'Layout',
            content: '<div class="grid grid-cols-1 md:grid-cols-3 gap-6"><div class="bg-white p-6 rounded-xl shadow-lg"><h3 class="text-lg font-bold mb-2">Column 1</h3><p class="text-gray-600 text-sm">Content</p></div><div class="bg-white p-6 rounded-xl shadow-lg"><h3 class="text-lg font-bold mb-2">Column 2</h3><p class="text-gray-600 text-sm">Content</p></div><div class="bg-white p-6 rounded-xl shadow-lg"><h3 class="text-lg font-bold mb-2">Column 3</h3><p class="text-gray-600 text-sm">Content</p></div></div>',
            attributes: { class: 'gjs-block-columns' }
        });
        
        blockManager.add('grid-4', {
            label: '⊞ 4 Grid',
            category: 'Layout',
            content: '<div class="grid grid-cols-2 md:grid-cols-4 gap-4"><div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg text-center"><p class="font-semibold">Item 1</p></div><div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg text-center"><p class="font-semibold">Item 2</p></div><div class="bg-gradient-to-br from-pink-50 to-pink-100 p-4 rounded-lg text-center"><p class="font-semibold">Item 3</p></div><div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg text-center"><p class="font-semibold">Item 4</p></div></div>',
            attributes: { class: 'gjs-block-grid' }
        });
        
        // Pre-built Sections
        blockManager.add('hero', {
            label: '🚀 Hero Section',
            category: 'Sections',
            content: `
                <section class="relative bg-gradient-to-br from-blue-600 via-purple-600 to-pink-500 text-white py-32 overflow-hidden">
                    <div class="absolute inset-0 bg-black opacity-20"></div>
                    <div class="container mx-auto px-6 relative z-10">
                        <div class="max-w-4xl mx-auto text-center">
                            <h1 class="text-6xl font-bold mb-6 leading-tight">Build Something Amazing</h1>
                            <p class="text-xl mb-10 text-blue-100">Create stunning websites with our modern drag-and-drop builder</p>
                            <div class="flex gap-4 justify-center">
                                <a href="#" class="px-10 py-5 bg-white text-blue-600 rounded-xl font-bold hover:shadow-2xl transition-all duration-300 transform hover:scale-105">Get Started</a>
                                <a href="#" class="px-10 py-5 bg-transparent border-2 border-white text-white rounded-xl font-bold hover:bg-white hover:text-blue-600 transition-all duration-300">Learn More</a>
                            </div>
                        </div>
                    </div>
                </section>
            `,
            attributes: { class: 'gjs-block-hero' }
        });
        
        blockManager.add('features', {
            label: '⭐ Features Grid',
            category: 'Sections',
            content: `
                <section class="py-20 bg-gray-50">
                    <div class="container mx-auto px-6">
                        <div class="text-center mb-16">
                            <h2 class="text-4xl font-bold mb-4">Amazing Features</h2>
                            <p class="text-xl text-gray-600">Everything you need to succeed</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl mb-6 flex items-center justify-center text-white text-2xl font-bold">1</div>
                                <h3 class="text-2xl font-bold mb-4">Fast & Easy</h3>
                                <p class="text-gray-600">Build pages in minutes with our intuitive interface</p>
                            </div>
                            <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl mb-6 flex items-center justify-center text-white text-2xl font-bold">2</div>
                                <h3 class="text-2xl font-bold mb-4">Responsive</h3>
                                <p class="text-gray-600">Works perfectly on all devices and screen sizes</p>
                            </div>
                            <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                                <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-red-600 rounded-xl mb-6 flex items-center justify-center text-white text-2xl font-bold">3</div>
                                <h3 class="text-2xl font-bold mb-4">Customizable</h3>
                                <p class="text-gray-600">Full control over every aspect of your design</p>
                            </div>
                        </div>
                    </div>
                </section>
            `,
            attributes: { class: 'gjs-block-features' }
        });
        
        blockManager.add('cta', {
            label: '📢 Call to Action',
            category: 'Sections',
            content: `
                <section class="py-20 bg-gradient-to-r from-blue-600 to-purple-600">
                    <div class="container mx-auto px-6">
                        <div class="max-w-3xl mx-auto text-center text-white">
                            <h2 class="text-5xl font-bold mb-6">Ready to Get Started?</h2>
                            <p class="text-xl mb-10 text-blue-100">Join thousands of users building amazing websites</p>
                            <a href="#" class="inline-block px-12 py-5 bg-white text-blue-600 rounded-xl font-bold text-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105">Start Free Trial</a>
                        </div>
                    </div>
                </section>
            `,
            attributes: { class: 'gjs-block-cta' }
        });
        
        // Components
        blockManager.add('card', {
            label: '🎴 Card',
            category: 'Components',
            content: `
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                    <img src="https://images.unsplash.com/photo-1557683316-973673baf926?w=600" alt="Card image" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="text-2xl font-bold mb-3">Card Title</h3>
                        <p class="text-gray-600 mb-4">This is a beautiful card component with an image, title, and description.</p>
                        <a href="#" class="inline-block text-blue-600 font-semibold hover:text-blue-700">Learn More →</a>
                    </div>
                </div>
            `,
            attributes: { class: 'gjs-block-card' }
        });
        
        blockManager.add('testimonial', {
            label: '💬 Testimonial',
            category: 'Components',
            content: `
                <div class="bg-white p-8 rounded-2xl shadow-lg">
                    <div class="flex items-center mb-6">
                        <img src="https://i.pravatar.cc/100?img=1" alt="Avatar" class="w-16 h-16 rounded-full mr-4">
                        <div>
                            <h4 class="font-bold text-lg">John Doe</h4>
                            <p class="text-gray-600 text-sm">CEO, Company Inc.</p>
                        </div>
                    </div>
                    <p class="text-gray-700 italic">"This is an amazing product! It has completely transformed how we work and increased our productivity by 10x."</p>
                    <div class="flex mt-4 text-yellow-400">★★★★★</div>
                </div>
            `,
            attributes: { class: 'gjs-block-testimonial' }
        });
        
        blockManager.add('pricing', {
            label: '💰 Pricing Card',
            category: 'Components',
            content: `
                <div class="bg-white rounded-2xl shadow-xl p-8 border-2 border-transparent hover:border-blue-500 transition-all duration-300">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold mb-2">Pro Plan</h3>
                        <div class="mb-6">
                            <span class="text-5xl font-bold">$29</span>
                            <span class="text-gray-600">/month</span>
                        </div>
                        <ul class="text-left mb-8 space-y-3">
                            <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Unlimited Projects</li>
                            <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Priority Support</li>
                            <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Advanced Analytics</li>
                            <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Custom Domain</li>
                        </ul>
                        <a href="#" class="block w-full py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold rounded-xl hover:shadow-xl transition-all duration-300">Choose Plan</a>
                    </div>
                </div>
            `,
            attributes: { class: 'gjs-block-pricing' }
        });
        
        // Forms
        blockManager.add('form', {
            label: '📋 Contact Form',
            category: 'Forms',
            content: `
                <form class="max-w-2xl mx-auto bg-white p-10 rounded-2xl shadow-xl">
                    <h3 class="text-3xl font-bold mb-8 text-center">Get In Touch</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">First Name</label>
                            <input type="text" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-colors" placeholder="John">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Last Name</label>
                            <input type="text" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-colors" placeholder="Doe">
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 font-semibold mb-2">Email</label>
                        <input type="email" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-colors" placeholder="john@example.com">
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 font-semibold mb-2">Message</label>
                        <textarea class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none transition-colors" rows="5" placeholder="Your message here..."></textarea>
                    </div>
                    <button type="submit" class="w-full px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold rounded-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105">Send Message</button>
                </form>
            `,
            attributes: { class: 'gjs-block-form' }
        });
        
        // Load existing page data if editing
        if (pageData) {
            editor.setComponents(pageData.html_content || '');
            editor.setStyle(pageData.css_content || '');
        } else {
            // Set default template for new pages
            editor.setComponents(`
                <div class="container mx-auto px-6 py-12">
                    <h1 class="text-4xl font-bold mb-6">Welcome to Your New Page</h1>
                    <p class="text-lg text-gray-700">Start building your page by dragging components from the left panel.</p>
                </div>
            `);
        }

        
        // Save functionality
        document.getElementById('save-btn').addEventListener('click', async () => {
            const loading = document.getElementById('loading');
            loading.classList.add('active');
            
            try {
                const html = editor.getHtml();
                const css = editor.getCss();
                const components = JSON.stringify(editor.getComponents());
                const styles = JSON.stringify(editor.getStyle());
                
                let title = pageData ? pageData.title : prompt('Enter page title:');
                let slug = pageData ? pageData.slug : prompt('Enter page slug (URL):');
                
                if (!title || !slug) {
                    alert('Title and slug are required!');
                    loading.classList.remove('active');
                    return;
                }
                
                const formData = new FormData();
                formData.append('id', pageId || '');
                formData.append('title', title);
                formData.append('slug', slug);
                formData.append('html_content', html);
                formData.append('css_content', css);
                formData.append('components', components);
                formData.append('styles', styles);
                
                const response = await fetch('save_page.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Page saved successfully!');
                    if (!pageId && result.id) {
                        window.location.href = 'builder.php?id=' + result.id;
                    }
                } else {
                    alert('Error saving page: ' + result.message);
                }
            } catch (error) {
                console.error('Save error:', error);
                alert('Error saving page. Check console for details.');
            } finally {
                loading.classList.remove('active');
            }
        });
        
        // Preview functionality
        document.getElementById('preview-btn').addEventListener('click', () => {
            const html = editor.getHtml();
            const css = editor.getCss();
            
            const previewWindow = window.open('', '_blank');
            previewWindow.document.write(`
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Preview</title>
                    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
                    <link rel="stylesheet" href="../assets/css/style.css">
                    <style>${css}<\/style>
                </head>
                <body>
                    ${html}
                    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"><\/script>
                </body>
                </html>
            `);
            previewWindow.document.close();
        });
        
        // Auto-save every 2 minutes
        setInterval(() => {
            if (pageId) {
                console.log('Auto-saving...');
                document.getElementById('save-btn').click();
            }
        }, 120000);
    </script>
</body>
</html>
