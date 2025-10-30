# MiHi Photo Booth Website - Project Structure

## 📁 Directory Structure

```
MiHi/
├── components/
│   └── navigation.js          # Shared navigation component (auto-loads on all pages)
├── includes/
│   └── image/
│       ├── logo.png
│       └── logo_black.png
├── js/
│   └── google-reviews.js
├── services/
│   ├── photobooth/
│   │   ├── ai-photo-booth.html
│   │   ├── green-screen-booth.html
│   │   └── rosie-robot.html
│   ├── videobooth/
│   │   ├── 360-video-booth.html
│   │   ├── bullet-time-array.html
│   │   └── glambot-video.html
│   └── experiences/
│       ├── event-photography.html
│       ├── sketchbot.html
│       ├── cookie-printer.html
│       └── pose-cards.html
└── index.html                 # Homepage
```

## 🎯 Key Features

### 1. **Centralized Navigation Component**
- Located in: `components/navigation.js`
- **How it works**: All pages automatically load this shared navigation via JavaScript
- **Benefits**: 
  - ✅ Update navigation in ONE place, automatically updates across ALL pages
  - ✅ Consistent navigation across the entire website
  - ✅ Smart path resolution based on page location
  - ✅ No code duplication

### 2. **Organized File Structure**
- All service pages organized into logical folders:
  - `services/photobooth/` - Photo booth services
  - `services/videobooth/` - Video booth services
  - `services/experiences/` - Additional experiences
- Clean, professional directory structure
- Easy to maintain and scale

### 3. **Clean URLs**
Examples:
- `https://yoursite.com/services/photobooth/ai-photo-booth.html`
- `https://yoursite.com/services/videobooth/360-video-booth.html`
- `https://yoursite.com/services/experiences/sketchbot.html`

## 🔧 How to Update Navigation

To update the navigation menu across ALL pages:

1. Open: `components/navigation.js`
2. Make your changes to the navigation HTML
3. Save the file
4. **That's it!** All pages will automatically use the updated navigation

### Example: Adding a New Service
```javascript
// In components/navigation.js, add to the appropriate section:

<a href="${pathPrefix}services/photobooth/new-service.html" class="block text-sm text-gray-700 hover:text-blue-600 transition">
    <span class="font-medium">New Service</span><br>
    <span class="text-xs text-gray-500">Service description</span>
</a>
```

## 📄 Page Template Structure

Each service page follows this structure:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta tags and styles -->
</head>
<body>
    <!-- Navigation loaded automatically -->
    <script src="../../components/navigation.js"></script>
    
    <main>
        <!-- Hero Section -->
        <!-- Features Section -->
        <!-- CTA Section -->
    </main>
    
    <!-- Footer -->
</body>
</html>
```

## 🎨 Path Resolution

The `navigation.js` component automatically detects the current page location and adjusts paths:

- **Root pages** (index.html): Uses `pathPrefix = ""`
- **Service pages** (services/photobooth/): Uses `pathPrefix = "../../"`

This ensures all links work correctly regardless of where the page is located in the directory structure.

## 🚀 Adding New Pages

1. Create your new HTML page in the appropriate folder under `services/`
2. Copy the structure from an existing service page
3. Update the content (title, headings, descriptions)
4. Add a link to the navigation in `components/navigation.js`
5. Done! The page will automatically have the correct navigation

## 💡 Benefits of This Structure

1. **Maintainability**: Update navigation once, affects all pages
2. **Scalability**: Easy to add new services without touching existing pages
3. **Organization**: Logical folder structure makes finding files easy
4. **Consistency**: All pages use the same navigation and styling
5. **SEO Friendly**: Clean URLs and organized structure
6. **Development Speed**: Quick to add new pages using templates

## 📝 Notes

- All service pages use **relative paths** (`../../`) to access root resources
- The homepage (`index.html`) uses **direct paths** to service pages
- Navigation component handles path resolution automatically
- All pages include Tailwind CSS via CDN for consistent styling

---

**Last Updated**: October 2025
**Version**: 2.0 - Reorganized Structure with Shared Navigation
