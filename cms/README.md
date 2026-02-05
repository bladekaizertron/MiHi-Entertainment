# GrapesJS Website Builder - CMS Integration

## 🎨 Overview

This is a complete visual website builder integration using GrapesJS. It allows you to create and edit pages with a drag-and-drop interface, similar to Webflow or Wix.

## ✨ Features

- **Visual Drag & Drop Editor** - Build pages visually without coding
- **Responsive Design** - Preview and edit for desktop, tablet, and mobile
- **Component Library** - Pre-built components (forms, countdowns, tabs, etc.)
- **Asset Manager** - Upload and manage images directly in the editor
- **Custom Code** - Add custom HTML/CSS/JS when needed
- **Auto-Save** - Automatically saves every 2 minutes
- **Live Preview** - Preview your page before publishing
- **Static HTML Generation** - Generates optimized static HTML files

## 📦 Included Plugins

1. **grapesjs-preset-webpage** - Complete webpage building preset
2. **grapesjs-blocks-basic** - Basic building blocks
3. **grapesjs-plugin-forms** - Form components
4. **grapesjs-component-countdown** - Countdown timers
5. **grapesjs-plugin-export** - Export functionality
6. **grapesjs-tabs** - Tab components
7. **grapesjs-custom-code** - Custom code blocks
8. **grapesjs-touch** - Touch device support
9. **grapesjs-tooltip** - Tooltip components
10. **grapesjs-tui-image-editor** - Image editing
11. **grapesjs-typed** - Typing animation effects
12. **grapesjs-style-bg** - Advanced background styling

## 🚀 Installation

### Step 1: Run Database Migration

Execute the SQL migration to create the `pages` table:

```bash
mysql -u your_username -p your_database < cms/migration.sql
```

Or manually run the SQL in phpMyAdmin/MySQL Workbench:

```sql
-- See cms/migration.sql for the full SQL
```

### Step 2: Create Upload Directory

The builder needs a directory for uploaded assets:

```bash
mkdir -p uploads/cms
chmod 777 uploads/cms
```

### Step 3: Access the Builder

Navigate to:
```
http://localhost/MiHi-Entertainment/cms/
```

Login with your admin credentials.

## 📖 Usage

### Creating a New Page

1. Go to `/cms/`
2. Click "Create New Page"
3. Design your page using the visual editor
4. Click "Save Page"
5. Enter a title and slug (URL)
6. Your page will be available at `/{slug}.html`

### Editing an Existing Page

1. Go to `/cms/`
2. Click "Edit" on any page card
3. Make your changes
4. Click "Save Page"

### Deleting a Page

1. Go to `/cms/`
2. Click "Delete" on any page card
3. Confirm deletion
4. Both the database entry and static HTML file will be removed

## 🎯 Key Features Explained

### Device Preview
- Click the device icons (💻 📱) to preview your design on different screen sizes
- Make responsive adjustments for each breakpoint

### Asset Manager
- Click the image icon to open the asset manager
- Upload images by dragging and dropping
- Uploaded images are stored in `/uploads/cms/`

### Style Manager
- Select any component
- Use the right panel to adjust styles
- Changes are applied in real-time

### Layer Manager
- View the component hierarchy
- Drag and drop to reorder elements
- Click to select components

### Custom Code
- Add custom HTML/CSS/JavaScript
- Use the "Custom Code" block from the blocks panel

## 🔧 Technical Details

### File Structure

```
cms/
├── index.php          # Main dashboard (list of pages)
├── builder.php        # GrapesJS visual editor
├── save_page.php      # API endpoint to save pages
├── delete_page.php    # Delete page handler
├── save_asset.php     # Asset upload handler
├── migration.sql      # Database schema
└── README.md          # This file
```

### Database Schema

The `pages` table stores:
- `id` - Unique identifier
- `title` - Page title
- `slug` - URL slug (unique)
- `html_content` - Generated HTML
- `css_content` - Generated CSS
- `components` - GrapesJS components (JSON)
- `styles` - GrapesJS styles (JSON)
- `meta_title`, `meta_description`, `meta_keywords` - SEO fields
- `created_at`, `updated_at` - Timestamps

### Static File Generation

When you save a page, the system:
1. Saves to the database
2. Generates a static HTML file at `/{slug}.html`
3. Includes Tailwind CSS and your custom styles
4. Automatically includes navigation and footer components

## 🎨 Customization

### Adding Custom Components

Edit `builder.php` and add custom blocks:

```javascript
editor.BlockManager.add('my-custom-block', {
    label: 'My Block',
    content: '<div class="my-block">Custom content</div>',
    category: 'Custom'
});
```

### Changing Default Template

Edit the default template in `builder.php`:

```javascript
editor.setComponents(`
    <div class="your-custom-template">
        <!-- Your HTML here -->
    </div>
`);
```

### Adding More Plugins

Add more GrapesJS plugins by:
1. Including the CDN link in `builder.php`
2. Adding the plugin name to the `plugins` array
3. Configuring in `pluginsOpts` if needed

## 🐛 Troubleshooting

### "Page not saving"
- Check that the `pages` table exists
- Verify database connection in `config/config.php`
- Check browser console for errors

### "Images not uploading"
- Ensure `/uploads/cms/` directory exists
- Check directory permissions (should be 777)
- Verify `upload_max_filesize` in php.ini

### "Static HTML file not generated"
- Check write permissions on the root directory
- Verify the slug doesn't contain invalid characters

## 📚 Resources

- [GrapesJS Documentation](https://grapesjs.com/docs/)
- [GrapesJS Plugins](https://grapesjs.com/docs/plugins/)
- [Tailwind CSS Docs](https://tailwindcss.com/docs)

## 🔐 Security Notes

- The builder requires admin authentication
- All file uploads are sanitized
- SQL injection protection via prepared statements
- XSS protection via htmlspecialchars

## 🚀 Next Steps

1. **SEO Enhancement**: Add meta title/description fields to the save form
2. **Version Control**: Implement page versioning/history
3. **Templates**: Create reusable page templates
4. **A/B Testing**: Add variant testing capabilities
5. **Analytics Integration**: Track page performance

## 💡 Tips

- Use Tailwind CSS classes for rapid styling
- Save frequently (or rely on auto-save)
- Test on all device sizes before publishing
- Use the layer manager for complex layouts
- Leverage custom code blocks for advanced features

---

**Created by:** MiHi Entertainment Development Team
**Last Updated:** 2026-02-06
