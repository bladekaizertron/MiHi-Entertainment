<?php
require_once __DIR__ . '/../config/config.php';
$db = getDB();

try {
    // Helper function
    function addItem($db, $parent, $label, $url, $desc = '', $isHeader = 0, $sortOrder = 0) {
        $stmt = $db->prepare("INSERT INTO navigation_items (parent_id, label, url, description, is_header, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$parent, $label, $url, $desc, $isHeader, $sortOrder]);
        return $db->lastInsertId();
    }

    // Function to clear children
    function clearChildren($db, $parentId) {
        $db->exec("DELETE FROM navigation_items WHERE parent_id = $parentId");
    }
    
    // Find Header ID by label helper
    function getHeaderId($db, $label) {
        $stmt = $db->prepare("SELECT id FROM navigation_items WHERE label = ? AND parent_id IS NULL");
        $stmt->execute([$label]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row['id'];
        
        // If not found, create it
        $db->exec("INSERT INTO navigation_items (label, url, sort_order) VALUES ('$label', '#$label', 99)"); 
        return $db->lastInsertId();
    }

    // 0. PRODUCTS (From Reference Image)
    $productsId = getHeaderId($db, 'Products');
    clearChildren($db, $productsId);

    // --- COLUMN 1: PHOTO BOOTHS ---
    $prodCol1 = addItem($db, $productsId, 'Photo Booths', '', '', 1, 1);
    
    $photoBooths = [
        ['AI Photo Booth', 'product/ai-photo-booth.html', 'Custom AI-generated characters in seconds'],
        ['Green Screen', 'product/green-screen.html', 'Transport guests anywhere with magic backdrops'],
        ['Rosie the Robot', 'product/rosie-the-robot.html', 'Autonomous roaming robot photo booth'],
        ['Graffiti Wall Photo Booth', 'product/graffiti-wall.html', 'Paint and create digital art with your photos'],
        ['Mosaic Photo Booth', 'product/mosaic-photo-booth.html', 'Your event\'s story, built tile-by-tile with guest photos'],
        ['Roaming Photo Booth', 'product/roaming-photo-booth.html', 'The booth that comes to you, anywhere at your event'],
        ['Virtual Photo Booth', 'product/virtual-photo-booth.html', 'Snap, pose, and share—no app required, all online'],
        ['VW Bus Photo Booth', 'product/vw-bus-photo-booth.html', 'Snap, pose, and share—no app required, all online'], 
        ['Photo Booth Sets', 'product/photo-booth-sets.html', 'Fun and immersive photo booth sets designed to fit your event vision'],
        ['View All →', 'product/photo-booths.html', '']
    ];

    foreach ($photoBooths as $i => $item) {
        addItem($db, $prodCol1, $item[0], $item[1], $item[2], 0, $i+1);
    }

    // --- COLUMN 2: VIDEO BOOTHS ---
    $prodCol2 = addItem($db, $productsId, 'Video Booths', '', '', 1, 2);

    $videoBooths = [
        ['360 Video Booth', 'product/360-video-booth.html', 'Epic, shareable videos from every angle'],
        ['Bullet-Time Array', 'product/bullet-time.html', 'Matrix-style multi-camera effects'],
        ['GlamBot Video', 'product/glambot.html', 'Cinematic, automated slow pans'],
        ['Vogue Video Booth', 'product/vogue-booth.html', 'Studio-quality headshots, on-site and effortless'], 
        ['Slow Motion Video Booth', 'product/slow-motion.html', 'Cinematic slow-motion videos, on-site and effortless'],
        ['Video Testimonial Booth', 'product/video-testimonial.html', 'Authentic customer testimonials, captured on-site'],
        ['View All →', 'product/video-booths.html', '']
    ];

    foreach ($videoBooths as $i => $item) {
        addItem($db, $prodCol2, $item[0], $item[1], $item[2], 0, $i+1);
    }

    // --- COLUMN 3: ADDITIONAL EXPERIENCES ---
    $prodCol3 = addItem($db, $productsId, 'Additional Experiences', '', '', 1, 3);

    $experiences = [
        ['Event Photography', 'product/event-photography.html', 'Professional coverage of every moment'],
        ['Professional Headshots', 'product/headshots.html', 'Studio-quality headshots, on-site and effortless'],
        ['Brand Activation', 'product/brand-activation.html', 'Immersive brand experiences and activations'],
        ['SketchBot', 'product/sketchbot.html', 'Live robot-drawn portraits'],
        ['Cookie Printer', 'product/cookie-printer.html', 'Edible photo cookies on demand'],
        ['Lux Photography', 'product/lux-photography.html', 'An elevated photography booth that leaves you and your guests feeling luxurious']
    ];

    foreach ($experiences as $i => $item) {
        addItem($db, $prodCol3, $item[0], $item[1], $item[2], 0, $i+1);
    }
    
    // 1. EVENTS
    $eventsId = getHeaderId($db, 'Events');
    clearChildren($db, $eventsId);
    
    // Structure: Main Types, Themes, Locations? 
    // Let's go with columns: "Event Types", "Event Themes", "Guides"
    
    $evtCol1 = addItem($db, $eventsId, 'Event Types', '', '', 1, 1);
    addItem($db, $evtCol1, 'Corporate Events', 'corporate-events.html', 'Brand activations, trade shows, & parties', 0, 1);
    addItem($db, $evtCol1, 'Weddings', 'wedding.html', 'Receptions, engagements, & memories', 0, 2);
    addItem($db, $evtCol1, 'Social Parties', 'socialevents.html', 'Birthdays, graduations, & celebrations', 0, 3);
    addItem($db, $evtCol1, 'Mitzvahs', 'event-type/bar&bat-mitzvah.html', 'Bar & Bat Mitzvah celebrations', 0, 4);
    addItem($db, $evtCol1, 'Non-Profit', 'event-type/nonprofit-event.html', 'Fundraisers & charity galas', 0, 5);

    $evtCol2 = addItem($db, $eventsId, 'Popular Themes', '', '', 1, 2);
    addItem($db, $evtCol2, 'Roaring 20s / Gatsby', 'event-themes/great-gatsby-roarin-20s.html', '', 0, 1);
    addItem($db, $evtCol2, '80s Retro', 'event-themes/70s-80s-retro-party.html', '', 0, 2);
    addItem($db, $evtCol2, 'Holiday / Christmas', 'event-type/holiday-party.html', '', 0, 3);
    addItem($db, $evtCol2, 'Halloween', 'event-themes/spooky-halloween.html', '', 0, 4);
    addItem($db, $evtCol2, 'Neon / Futuristic', 'event-themes/futuristic.html', '', 0, 5);
    
    $evtCol3 = addItem($db, $eventsId, 'Planning Guides', '', '', 1, 3);
    addItem($db, $evtCol3, 'Wedding Guide', 'wedding-product-guide.html', '', 0, 1);
    addItem($db, $evtCol3, 'Holiday Guide', 'holiday-product-guide.html', '', 0, 2);
    addItem($db, $evtCol3, 'Trade Show Guide', 'trade-show-product-guide.html', '', 0, 3);


    // 2. RENTALS (Using sitemap to infer structure: Game Rentals, Custom Sets, AV, Decor)
    $rentalsId = getHeaderId($db, 'Rentals');
    clearChildren($db, $rentalsId);
    
    $rentCol1 = addItem($db, $rentalsId, 'Interactive Games', '', '', 1, 1);
    addItem($db, $rentCol1, 'Claw Machine', 'game-rentals/claw-machine.html', 'Custom branding & prizes', 0, 1);
    addItem($db, $rentCol1, 'Stick Drop', 'game-rentals/stick-drop.html', 'Reaction game for brand activations', 0, 2);
    addItem($db, $rentCol1, 'Casino Rentals', 'denver-casino-rentals.html', 'Poker, Blackjack, & more', 0, 3);
    addItem($db, $rentCol1, 'Virtual Reality', 'virtual-reality-rental.html', 'Immersive VR experiences', 0, 4);
    
    $rentCol2 = addItem($db, $rentalsId, 'AV & Decor', '', '', 1, 2);
    addItem($db, $rentCol2, 'Audio Services', 'av-services/audio-services.html', 'PA systems, DJs, & sound', 0, 1);
    addItem($db, $rentCol2, 'Event Lighting', 'av-services/event-lighting.html', 'Uplighting & atmospheric effects', 0, 2);
    addItem($db, $rentCol2, 'Visual Services', 'av-services/visual-services.html', 'Screens, projectors, & LED walls', 0, 3);
    addItem($db, $rentCol2, 'Dance Floors', 'av-services/dance-floors.html', 'Custom vinyl & LED floors', 0, 4);
    
    $rentCol3 = addItem($db, $rentalsId, 'Custom Sets', '', '', 1, 3);
    addItem($db, $rentCol3, 'Ski Lift Set', 'custom-sets/ski-lift-photo-set.html', 'Perfect for Apres Ski themes', 0, 1);
    addItem($db, $rentCol3, 'Saloon Doors', 'custom-sets/saloon-door-photo-set.html', 'Western theme essential', 0, 2);
    addItem($db, $rentCol3, 'Sleigh Set', 'custom-sets/sleigh-photo-set.html', 'Holiday photo ops', 0, 3);
    addItem($db, $rentCol3, 'View All Sets', 'photo-booth-sets.html', '', 0, 4);


    // 3. GALLERY (Based on "Our Work", "Case Studies", etc.)
    $galleryId = getHeaderId($db, 'Gallery');
    clearChildren($db, $galleryId);
    // Simple dropdown list, or single column
    addItem($db, $galleryId, 'Event Gallery', 'our-work.html', 'See our recent events', 0, 1);
    addItem($db, $galleryId, 'Case Studies', 'case-studies.html', 'Success stories & brand activations', 0, 2);
    addItem($db, $galleryId, 'Layout Lookbook', 'layout-lookbook.html', 'Photo print design templates', 0, 3);
    addItem($db, $galleryId, 'Backdrops', 'backdrops.html', 'View our backdrop collection', 0, 4);


    // 4. ABOUT US 
    $aboutId = getHeaderId($db, 'About Us');
    clearChildren($db, $aboutId);
    
    addItem($db, $aboutId, 'Our Story', 'about.html', 'Who we are', 0, 1);
    addItem($db, $aboutId, 'Locations', 'our-locations.html', 'Where we serve', 0, 2);
    addItem($db, $aboutId, 'Blog', 'blog.php', 'Latest news & tips', 0, 3);
    addItem($db, $aboutId, 'FAQ', 'faq.html', 'Frequently Asked Questions', 0, 4);
    
    // UPDATE SORT ORDER OF TOP LEVEL
    $db->exec("UPDATE navigation_items SET sort_order = 1 WHERE id = " . getHeaderId($db, 'Products'));
    $db->exec("UPDATE navigation_items SET sort_order = 2 WHERE id = $eventsId");
    $db->exec("UPDATE navigation_items SET sort_order = 3 WHERE id = $rentalsId");
    $db->exec("UPDATE navigation_items SET sort_order = 4 WHERE id = $galleryId");
    $db->exec("UPDATE navigation_items SET sort_order = 5 WHERE id = $aboutId");
    $db->exec("UPDATE navigation_items SET sort_order = 6 WHERE label = 'Contact Us'");

    $message = "Full menu seeded successfully.";

} catch (PDOException $e) {
    $message = "Error: " . $e->getMessage();
}
?>
