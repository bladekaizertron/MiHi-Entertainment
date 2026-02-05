# 🚀 Quick Start Guide - GrapesJS Website Builder

## Step 1: Run Database Migration

Visit this URL in your browser:
```
http://localhost/MiHi-Entertainment/cms/run_migration.php
```

This will create the `pages` table in your database.

## Step 2: Create Upload Directory

The system needs a directory for uploaded images. Run this command:

```bash
mkdir -p /Applications/XAMPP/xamppfiles/htdocs/MiHi-Entertainment/uploads/cms
chmod 777 /Applications/XAMPP/xamppfiles/htdocs/MiHi-Entertainment/uploads/cms
```

Or create it manually:
1. Navigate to `/Applications/XAMPP/xamppfiles/htdocs/MiHi-Entertainment/uploads/`
2. Create a folder named `cms`
3. Set permissions to 777 (read/write/execute for all)

## Step 3: Access the Builder

Navigate to:
```
http://localhost/MiHi-Entertainment/cms/
```

Login with your admin credentials.

## Step 4: Create Your First Page

1. Click "Create New Page"
2. Drag and drop components from the left panel
3. Customize styles using the right panel
4. Click "Save Page"
5. Enter a title (e.g., "About Us") and slug (e.g., "about-us")
6. Your page will be live at `http://localhost/MiHi-Entertainment/about-us.html`

## 🎨 Quick Tips

### Adding Text
- Drag the "Text" block from the left panel
- Double-click to edit
- Use the style panel on the right to customize

### Adding Images
- Drag the "Image" block
- Click the image icon in the asset manager
- Upload your image
- Select it to insert

### Responsive Design
- Click the device icons (💻 📱) at the top
- Adjust styles for each screen size
- Changes are device-specific

### Using Tailwind CSS
- Select any component
- Add Tailwind classes in the "Traits" panel
- Example: `bg-blue-500 text-white p-4 rounded-lg`

### Preview Before Publishing
- Click the "👁️ Preview" button
- Opens in a new window
- Shows exactly how it will look live

## 🔥 Advanced Features

### Custom Code Blocks
1. Find "Custom Code" in the blocks panel
2. Drag it to your page
3. Add HTML, CSS, or JavaScript
4. Perfect for embedding forms, videos, etc.

### Countdown Timers
1. Drag the "Countdown" block
2. Set the target date/time
3. Customize the appearance

### Forms
1. Use form components from the blocks panel
2. Add inputs, textareas, buttons
3. Configure form action and method

### Tabs & Accordions
1. Drag "Tabs" component
2. Add/remove tabs as needed
3. Customize content in each tab

## 📱 Keyboard Shortcuts

- `Ctrl/Cmd + S` - Save (coming soon)
- `Ctrl/Cmd + Z` - Undo
- `Ctrl/Cmd + Shift + Z` - Redo
- `Delete` - Delete selected component
- `Ctrl/Cmd + C` - Copy component
- `Ctrl/Cmd + V` - Paste component

## 🐛 Troubleshooting

### "Cannot save page"
- Check that you ran the migration (Step 1)
- Verify database connection in `config/config.php`

### "Cannot upload images"
- Ensure `/uploads/cms/` directory exists (Step 2)
- Check directory permissions (should be 777)

### "Page not showing navigation"
- The navigation is automatically injected
- Make sure `assets/components/navigation.js` exists

## 📚 Next Steps

1. Read the full README.md for advanced features
2. Explore all available plugins and components
3. Create page templates for reuse
4. Add SEO metadata to your pages

---

**Need Help?** Check the full documentation in `README.md`
