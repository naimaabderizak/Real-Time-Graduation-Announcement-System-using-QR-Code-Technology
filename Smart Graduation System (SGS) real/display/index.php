<?php
require_once '../admin/db.php';

// Fetch All Settings
$raw_settings = $pdo->query("SELECT * FROM settings")->fetchAll();
$settings = [];
foreach ($raw_settings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

// Fetch All Faculties and their colors
$faculties_data = $pdo->query("SELECT * FROM faculties")->fetchAll();
$faculty_colors_js = [];
foreach ($faculties_data as $f) {
    $faculty_colors_js[$f['faculty_name']] = $f['faculty_color'];
}

function hexToRgb($hex) {
    $hex = str_replace("#", "", $hex);
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }
    return "$r, $g, $b";
}
?>
<!DOCTYPE html>
<html lang="so">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benadir University • Convocation 2024 • Live Ceremony System</title>
    
    <!-- Premium Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Marcellus&family=Noto+Naskh+Arabic:wght@400;500;600;700&family=Playfair+Display:wght@400;700;900&family=Source+Sans+Pro:wght@300;400;600;700&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    
    <!-- GSAP with ScrollTrigger -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    
    <!-- Three.js for 3D effects -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    
    <!-- Canvas Confetti -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    
    <!-- Socket.io -->
    <script src="https://cdn.socket.io/4.6.0/socket.io.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Scanner Libraries (USB ONLY - Swalert for feedback) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            /* Primary Colors - Benadir University Identity */
            --bu-deep-blue: #002855;
            --bu-gold: #C5A047;
            --bu-gold-dark: #A6822D;
            --bu-white: #FFFFFF;
            --bu-cream: #FDFBF4;
            --bu-gray-light: #F5F7FA;
            
            /* Custom Dynamic Settings from DB */
            --custom-header-bg: <?= htmlspecialchars($settings['header_bg_color'] ?? '#ffffff') ?>;
            --custom-footer-bg: <?= htmlspecialchars($settings['footer_bg_color'] ?? '#002855') ?>;
            --custom-name-size: <?= htmlspecialchars($settings['name_font_size'] ?? '2.0rem') ?>;
            --custom-faculty-size: <?= htmlspecialchars($settings['faculty_font_size'] ?? '2.0rem') ?>;
            --custom-dept-size: <?= htmlspecialchars($settings['dept_font_size'] ?? '2.0rem') ?>;
            --custom-name-color: <?= htmlspecialchars($settings['name_text_color'] ?? '#ffffff') ?>;
            --custom-faculty-color: <?= htmlspecialchars($settings['faculty_text_color'] ?? '#ffffff') ?>;
            --custom-dept-color: <?= htmlspecialchars($settings['dept_text_color'] ?? '#ffffff') ?>;
            --custom-label-color: <?= htmlspecialchars($settings['label_text_color'] ?? '#C5A047') ?>;
            --custom-header-logo-h: <?= htmlspecialchars($settings['header_logo_height'] ?? '120px') ?>;
            --custom-footer-logo-h: <?= htmlspecialchars($settings['footer_logo_height'] ?? '60px') ?>;
            --custom-font-family: '<?= htmlspecialchars($settings['font_family'] ?? 'Inter') ?>', sans-serif;
            
            /* Card Scaling Logic */
            --scale-factor: <?= ($settings['card_scale'] ?? 100) / 100 ?>;
            --card-w: calc(500px * var(--scale-factor));
            --card-h: calc(700px * var(--scale-factor));
            --card-w-4: calc(420px * var(--scale-factor));
            
            /* Watermark Logic */
            <?php
        // Watermark Configuration
        $w_size = intval($settings['watermark_size'] ?? 100);
        $w_gap = intval($settings['watermark_gap'] ?? 60);
        $w_opacity = floatval($settings['watermark_opacity'] ?? 0.1);
        $w_url = $settings['watermark_logo'] ?? '';
        $total_cell = $w_size + $w_gap;
        ?>
            --watermark-size: <?= $w_size ?>px;
            --watermark-gap: <?= $w_gap ?>px;
            --watermark-opacity: <?= $w_opacity ?>;
            --watermark-total-size: <?= $total_cell ?>px;

            /* Faculty Colors */
            --faculty-cs: #C5A047;      /* Gold */
            --faculty-med: #D32F2F;     /* Red */
            --faculty-eng: #1976D2;     /* Blue */
            --faculty-biz: #388E3C;     /* Green */
            --faculty-law: #7B1FA2;     /* Purple */
            --faculty-edu: #00796B;     /* Teal */
            --faculty-default: #002855; /* Navy */
            
            /* Shadows & Effects */
            --shadow-premium: 0 10px 40px rgba(0,0,0,0.08);
            --shadow-card: 0 15px 50px rgba(0,0,0,0.12);
            --radius-lg: 16px;
            --radius-md: 12px;
            
            /* Typography */
            --font-primary: '<?= $settings['font_family'] ?? 'Inter' ?>', sans-serif;
            --font-secondary: 'Marcellus', serif;
            --font-arabic: 'Noto Naskh Arabic', serif;
            --font-display: 'Playfair Display', serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            font-family: var(--font-primary);
            color: var(--bu-deep-blue);
            background: transparent; /* Changed from var(--bu-cream) */
            -webkit-font-smoothing: antialiased;
        }

        /* Stage Backdrop Style */
        .stage-backdrop {
            position: fixed;
            top: 190px; /* Below stage-header (190px) */
            left: 0;
            width: 100%;
            height: calc(100vh - 190px - 160px); /* Fill space between header (190px) and footer (160px) */
            background: #fdfbf4; /* Fallback */
            background-image: url('../assets/images/no jiingad.jpg?v=<?= time() ?>');
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: center;
            z-index: -3;
            overflow: hidden;
        }

        /* Tiled Watermark Layer */
        .watermark-overlay {
            position: fixed;
            <?php if (!empty($settings['watermark_logo_1'])): ?>
            background-image: url('../<?= $settings['watermark_logo_1'] ?>?v=<?= time() ?>');
            background-size: <?= intval($settings['watermark_size'] ?? 600) ?>px;
            background-repeat: no-repeat;
            background-position: center;
            <?php endif; ?>
        }

        /* Golden Frame for the Center Slot */


        .sparkle-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://www.transparenttextures.com/patterns/stardust.png'); /* Subtle sparkles */
            opacity: 0.3;
            z-index: -1;
            pointer-events: none;
        }

        /* Top Branding Header */
        .stage-header {
            width: 100%;
            height: 190px;
            background: var(--custom-header-bg);
            <?php if (!empty($settings['header_bg_image'])): ?>
            background-image: url('../<?= $settings['header_bg_image'] ?>?v=<?= time() ?>');
            background-size: cover;
            background-position: center;
            <?php endif; ?>
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 4rem;
            border-bottom: 4px solid var(--bu-gold);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .header-logos {
            display: flex;
            align-items: center;
            gap: 2.5rem;
        }

        .header-logo {
            height: var(--custom-header-logo-h);
            object-fit: contain;
            /* filter: drop-shadow(0 5px 15px rgba(0,0,0,0.1)); REMOVED for sharpness */
            image-rendering: -webkit-optimize-contrast;
        }

        .header-branding {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .header-uni-name {
            font-family: var(--font-primary);
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--bu-deep-blue);
            line-height: 1;
            text-transform: uppercase;
            letter-spacing: -1px;
        }

        .header-uni-motto {
            font-family: 'Dancing Script', cursive;
            font-size: 1.8rem;
            color: #2ecc71;
            font-weight: 700;
            margin-top: 5px;
        }

        .header-center-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
        }

        .title-main {
            font-family: var(--font-secondary);
            font-size: 5.5rem; /* Increased from 4.5rem */
            font-weight: 900;
            color: var(--bu-deep-blue);
            line-height: 0.9;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .title-sub {
            font-family: var(--font-primary);
            font-size: 3rem; /* Increased from 2.2rem */
            font-weight: 800;
            color: var(--bu-gold);
            text-transform: uppercase;
            letter-spacing: 8px; /* Wider spacing */
            margin-top: 5px;
        }

        /* Dynamic Slot Visibility based on setting */
        .grid-container[data-card-count="1"] .student-slot:nth-child(n+2),
        .grid-container[data-card-count="2"] .student-slot:nth-child(n+3),
        .grid-container[data-card-count="3"] .student-slot:nth-child(n+4) {
            display: none !important;
        }

        /* Standardized Card Layout across all counts */
        /* 1 Card: Center stage */
        .grid-container[data-card-count="1"] { 
            justify-content: center; 
            gap: 0; 
        }

        /* 2 Cards: Wide spread for balanced look */
        .grid-container[data-card-count="2"] { 
            justify-content: center; 
            gap: 25rem; /* Wide gap to fill screen */
        }

        /* 3 Cards: Optimal distribution */
        .grid-container[data-card-count="3"] { 
            justify-content: center; 
            gap: 8rem; 
        }

        /* 4 Cards: Tighter packing to fit screen */
        .grid-container[data-card-count="4"] { 
            justify-content: center; 
            gap: 2rem; 
        }

        /* Resize cards specifically for 4-card layout to prevent overflow */
        .grid-container[data-card-count="4"] .student-slot {
            flex: 0 0 var(--card-w-4);
            width: var(--card-w-4);
        }
        
        /* Animations Toggle */
        body[data-animate="0"] .student-slot, 
        body[data-animate="0"] .empty-icon {
            animation: none !important;
            transition: none !important;
        }

        /* Standard Grid Layout */
        .grid-container {
            width: 100%;
            height: calc(100vh - 190px - 160px);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8rem; 
            padding: 0 2rem;
            perspective: 1500px;
        }

        /* Standardized Student Card - Framed Design */
        .student-slot {
            flex: 0 0 var(--card-w);
            width: var(--card-w);
            height: var(--card-h);
            position: relative;
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #ffffff; /* Solid white frame */
            border-radius: 20px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.25), 0 10px 30px rgba(0,0,0,0.1);
            border: 4px solid var(--faculty-default, #C5A047); /* Dynamic Faculty Color */
            padding: 15px; /* This creates the "Frame" gap inside */
            animation: cardFloat 6s ease-in-out infinite;
        }

        /* Info Overlay - Now the main card content */
        .slot-info {
            flex: 1; 
            width: 100%;
            padding: 2rem; 
            text-align: left;
            color: var(--bu-deep-blue);
            z-index: 3;
            display: flex;
            flex-direction: column;
            justify-content: center; 
            align-items: flex-start;
            position: relative;
            background: #f1f5f9; /* Light Gray Background */
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.05);
            min-height: 250px; /* Increased to fill card better */
        }

        /* Clean Rows - No Underlines, Better Spacing */
        .st-field {
            display: flex;
            align-items: center; 
            width: 100%;
            margin-bottom: 1.4rem; /* Increased vertical spacing */ 
            border-bottom: none; /* Removed underline */
            padding-bottom: 0; 
        }
        
        .st-field:last-child {
            margin-bottom: 0;
        }

        /* Labels */
        .st-label {
            display: inline-block;
            width: calc(95px * var(--scale-factor)); /* Reduced width to bring value closer */
            flex-shrink: 0; 
            font-size: calc(1.1rem * var(--scale-factor)); 
            color: #A6822D !important; /* Force Dark Gold */
            letter-spacing: 0.5px;
            font-weight: 800; /* BOLDER */
            text-transform: uppercase;
            font-family: 'Inter', sans-serif; 
        }    

        /* Values - Improved Typography */
        .st-name, .st-faculty, .st-department {
            font-weight: 900 !important; /* Force Extra Bold */
            line-height: 1.2; 
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: var(--bu-deep-blue) !important;
            text-shadow: none;
            flex: 1;
            padding-left: 4px;
            margin-bottom: 4px;
            white-space: normal; 
            display: -webkit-box;
            -webkit-line-clamp: 2; 
            -webkit-box-orient: vertical;
            overflow: hidden;
            overflow: hidden;
            margin: 0;
            font-family: 'Inter', sans-serif; /* Ensure bold-compatible font */
        }

        /* Special Override: Allow Faculty Name to take its own color */
        .st-faculty {
            color: var(--faculty-default, var(--bu-deep-blue)) !important;
        }
        
        .st-name {
            font-size: calc(var(--custom-name-size) * var(--scale-factor) * 0.9);
        }
        .st-field-faculty { font-size: calc(var(--custom-faculty-size) * var(--scale-factor)); }
        .st-field-dept { font-size: calc(var(--custom-dept-size) * var(--scale-factor)); }

        /* Icon Scaling */
        .empty-icon {
            font-size: calc(8rem * var(--scale-factor));
            color: rgba(79, 70, 229, 0.3);
            animation: pulse-icon 2s ease-in-out infinite;
        }
        
        .empty-photo-placeholder {
             height: calc(480px * var(--scale-factor));
        }

        /* Remove old photo placeholder styles if conflicting */
        .photo-placeholder {
            width: 100%; height: 100%;
            background: #f0f0f0;
            display: flex; align-items: center; justify-content: center;
        }

        .st-field-name { 
            font-size: var(--custom-name-size); 
            color: var(--custom-name-color);
        }
        .st-field-faculty { 
            font-size: var(--custom-faculty-size); 
            color: var(--custom-faculty-color);
        }
        .st-field-dept { 
            font-size: var(--custom-dept-size); 
            color: var(--custom-dept-color);
        }

            margin: 0;
            padding-left: 4px; 
        }

        .st-field-name { font-size: var(--custom-name-size); }
        .st-field-faculty { font-size: var(--custom-faculty-size); }
        .st-field-dept { font-size: var(--custom-dept-size); }

        /* Dynamic Faculty Color Classes Generated from DB */
        <?php foreach ($faculties_data as $f): 
            $safe_name = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $f['faculty_name']));
        ?>
        .f-<?= $safe_name ?> { 
            --faculty-default: <?= $f['faculty_color'] ?>; 
            --faculty-default-rgb: <?= hexToRgb($f['faculty_color']) ?>;
            box-shadow: 0 40px 100px rgba(var(--faculty-default-rgb), 0.25), 
                        0 10px 30px rgba(0,0,0,0.15); /* Dynamic glow */
        }
        <?php endforeach; ?>

        /* Shimmer Effect for Names */
        /* Solid Bold Name (Shimmer Removed for Better Visibility) */
        .st-name {
            position: relative;
            color: var(--custom-name-color);
            background: none;
            -webkit-text-fill-color: initial;
            text-shadow: none; /* Shadow removed as requested */
            letter-spacing: 1px;
        }

        /* Improved Frame Shadow */
        .slot-photo::after {
            box-shadow: inset 0 0 20px rgba(0,0,0,0.05);
        }

        /* Footer Bar Styling */
        .stage-footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            height: 160px; /* Further increased for impact */
            background: var(--custom-footer-bg);
            <?php if (!empty($settings['footer_bg_image'])): ?>
            background-image: url('../<?= $settings['footer_bg_image'] ?>?v=<?= time() ?>');
            background-size: cover;
            background-position: center;
            <?php endif; ?>
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5rem;
            z-index: 105;
            box-shadow: 0 -15px 50px rgba(0,0,0,0.4);
            border-top: 3px solid var(--bu-gold);
        }

        .footer-left {
            display: flex;
            align-items: center;
            gap: 1.5rem; /* Reduced gap to fit 4 sponsors */
        }

        .footer-branding {
            margin-left: 3rem;
            padding-left: 3rem;
            border-left: 1px solid rgba(255,255,255,0.1);
            color: white;
        }

        .footer-motto {
            font-family: 'Dancing Script', cursive;
            font-size: 2.22rem; /* Much larger */
            color: var(--bu-gold);
            margin: 0;
            line-height: 1;
        }

        .footer-tagline {
            font-size: 1rem; /* Clearer footer text */
            opacity: 0.8;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .sponsor-item {
            height: var(--custom-footer-logo-h); /* Dynamic sponsor logo height */
            width: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .sponsor-item:hover {
            transform: translateY(-8px) scale(1.1);
        }

        .sponsor-item img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            /* filter: drop-shadow(0 6px 15px rgba(0,0,0,0.4)); REMOVED for sharpness */
            image-rendering: -webkit-optimize-contrast;
        }

        .sponsor-divider {
            width: 1px;
            height: 30px;
            background: rgba(255,255,255,0.1);
            margin: 0 0.5rem;
        }

        .powered-by {
            display: flex;
            flex-direction: column;
            line-height: 1;
        }

        .powered-by span {
            font-size: 0.75rem;
            color: var(--bu-gold);
            letter-spacing: 3px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .powered-by strong {
            font-size: 1.1rem;
            color: white;
            letter-spacing: 2px;
            margin-top: 5px;
        }

        .footer-right {
            display: flex;
            align-items: center;
        }

        .anniversary-badge {
            background: transparent;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            border-left: 1px solid rgba(255,255,255,0.1);
            padding-left: 4rem; /* More space */
        }

        /* Header version with darker text */
        .anniversary-badge.header-version {
            border-left: 1px solid rgba(0,0,0,0.1);
            padding-left: 2rem;
            margin-right: 2rem;
        }

        .year-text {
            font-family: var(--font-secondary);
            font-size: 3.5rem; /* Massive impact */
            font-weight: 900;
            color: white;
            line-height: 0.85;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .header-version .year-text {
            color: var(--bu-deep-blue);
            font-size: 2.4rem;
        }

        .conv-text {
            font-family: var(--font-primary);
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--bu-gold);
            text-transform: uppercase;
            letter-spacing: 4px;
            margin-top: 2px;
        }

        .header-version .conv-text {
            font-size: 0.8rem;
            letter-spacing: 3px;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #EF4444;
        }
        .status-dot.active { background: #10B981; animation: pulse 2s infinite; }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Control Panel */
        .control-panel {
            position: fixed;
            bottom: 180px; /* Above the 160px footer */
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 1.5rem;
            background: white;
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            z-index: 9999;
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .ctrl-btn {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
            color: #64748b;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .ctrl-btn:hover {
            background: var(--bu-deep-blue);
            color: white;
            transform: translateY(-3px);
        }

        .ctrl-btn.active {
            background: #EF4444;
            color: white;
        }
        #voice-select {
            width: 100%;
            padding: 1rem;
            background: rgba(0, 40, 85, 0.5);
            border: 1px solid rgba(197, 160, 71, 0.2);
            border-radius: var(--radius-sm);
            color: var(--bu-cream);
            font-family: var(--font-primary);
            margin-bottom: 1rem;
            outline: none;
        }

        .voice-test-group {
            display: flex;
            gap: 1rem;
        }

        #test-voice-btn {
            flex: 1;
            padding: 1rem;
            background: var(--gradient-blue);
            color: var(--bu-cream);
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition-smooth);
        }

        #test-voice-btn:hover {
            background: linear-gradient(135deg, var(--bu-accent-blue) 0%, #00172D 100%);
        }

        /* Voice Settings Tray CSS */
        #voice-settings {
            position: fixed;
            bottom: 250px; /* Above the control panel */
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            width: 400px;
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            z-index: 10000;
            display: none; /* Controlled by JS toggle */
            border: 1px solid rgba(0,0,0,0.05);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        #voice-settings.active {
            display: block;
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .settings-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 1rem;
        }

        .settings-header h3 {
            margin: 0;
            font-size: 1.2rem;
            color: var(--bu-deep-blue);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Animation Classes */
        .animate-in {
            animation: slideUp 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        .animate-out {
            animation: slideDown 0.6s cubic-bezier(0.4, 0, 1, 1) forwards;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(50px);
            }
        }

        @keyframes photoRotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Status & Controls - Restored and Polished */
        .status-header {
            position: fixed;
            bottom: 180px; /* Above the footer */
            right: 4rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            background: white;
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 1000;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .sparkle-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://www.transparenttextures.com/patterns/stardust.png');
            opacity: 0.2;
            z-index: -1;
            pointer-events: none;
            animation: focusFloat 20s linear infinite;
        }

        @keyframes focusFloat {
            0% { background-position: 0 0; }
            100% { background-position: 500px 500px; }
        }

        /* Start Overlay Removed */
        /* Control Panel Enhancements */
        .control-panel {
            position: fixed;
            bottom: 2rem;
            left: 2rem; /* Moved from center to left for cleaner look */
            transform: none;
            display: flex;
            gap: 0.8rem;
            background: rgba(255,255,255,0.9);
            padding: 0.6rem;
            border-radius: 50px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            z-index: 10000;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.5);
        }

        .ctrl-btn {
            width: 42px;
            height: 42px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--bu-deep-blue);
            font-size: 1.1rem;
        }

        .ctrl-btn:hover {
            transform: scale(1.1) translateY(-2px);
            background: var(--bu-gold);
            color: white;
        }
        
        .ctrl-btn.special {
            background: var(--bu-deep-blue);
            color: white;
            width: auto;
            padding: 0 1.2rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        
        .ctrl-btn.active {
            background: #ef4444;
            color: white;
        }
    </style>
</head>
<body data-animate="<?= $settings['animation_enabled'] ?? '1' ?>">
    <div id="scannerIndicator" style="position: fixed; top: 10px; left: 10px; width: 12px; height: 12px; background: #2ecc71; border-radius: 50%; z-index: 10000; box-shadow: 0 0 10px #2ecc71; opacity: 0.8; transition: opacity 0.3s;" title="Scanner Ready (Page Focused)"></div>
    <!-- Stage Backdrop -->
    <div class="stage-backdrop">
        <div class="watermark-overlay"></div>
        <div class="sparkle-overlay"></div>
    </div>

    <div id="main-stage">
        <!-- Branded Header -->
        <header class="stage-header">
            <div class="header-logos">
                <img src="../<?= $settings['primary_logo'] ?? 'assets/images/benadir_logo.jpg' ?>?v=<?= time() ?>" class="header-logo" alt="University Logo">
                <div class="header-branding">
                    <span class="header-uni-name"><?= $settings['uni_name'] ?? 'Benadir University' ?></span>
                    <span class="header-uni-motto"><?= $settings['uni_motto'] ?? 'Cultivating Human Talents' ?></span>
                </div>
            </div>

            <div class="header-center-title">
                <div class="title-main"><?= $settings['anniversary_text'] ?? '19TH' ?></div>
                <div class="title-sub"><?= $settings['anniversary_subtext'] ?? 'CONVOCATION' ?></div>
            </div>

            <div class="header-logos">
                <img src="../<?= $settings['secondary_logo'] ?? 'assets/images/secondary_logo.png' ?>?v=<?= time() ?>" class="header-logo" alt="Convocation Logo">
            </div>
        </header>

        <!-- Graduate Grid Area -->
        <main class="grid-container" data-card-count="<?= $settings['layout_mode'] ?? '3' ?>">
            <div id="slot-1" class="student-slot empty">
                <div class="slot-info" style="opacity: 0;">
                    <div class="st-field st-field-name">
                        <span class="st-label">NAME:</span>
                        <h3 class="st-name"></h3>
                    </div>
                    <div class="st-field st-field-faculty">
                        <span class="st-label">FACULTY:</span>
                        <p class="st-faculty"></p>
                    </div>
                    <div class="st-field st-field-dept">
                        <span class="st-label">DEPT:</span>
                        <p class="st-department"></p>
                    </div>
                </div>
            </div>
            <div id="slot-2" class="student-slot empty">
                <div class="slot-info" style="opacity: 0;">
                    <div class="st-field st-field-name">
                        <span class="st-label">NAME:</span>
                        <h3 class="st-name"></h3>
                    </div>
                    <div class="st-field st-field-faculty">
                        <span class="st-label">FACULTY:</span>
                        <p class="st-faculty"></p>
                    </div>
                    <div class="st-field st-field-dept">
                        <span class="st-label">DEPT:</span>
                        <p class="st-department"></p>
                    </div>
                </div>
            </div>
            <div id="slot-3" class="student-slot empty">
                <div class="slot-info" style="opacity: 0;">
                    <div class="st-field st-field-name">
                        <span class="st-label">NAME:</span>
                        <h3 class="st-name"></h3>
                    </div>
                    <div class="st-field st-field-faculty">
                        <span class="st-label">FACULTY:</span>
                        <p class="st-faculty"></p>
                    </div>
                    <div class="st-field st-field-dept">
                        <span class="st-label">DEPT:</span>
                        <p class="st-department"></p>
                    </div>
                </div>
            </div>
            <div id="slot-4" class="student-slot empty">
                <div class="slot-info" style="opacity: 0;">
                    <div class="st-field st-field-name">
                        <span class="st-label"><?= $settings['label_name_text'] ?? 'NAME:' ?></span>
                        <h3 class="st-name">Name</h3>
                    </div>
                    <div class="st-field st-field-faculty">
                        <span class="st-label"><?= $settings['label_faculty_text'] ?? 'FACULTY:' ?></span>
                        <p class="st-faculty">Faculty</p>
                    </div>
                    <div class="st-field st-field-dept">
                        <span class="st-label"><?= $settings['label_dept_text'] ?? 'DEPT:' ?></span>
                        <p class="st-department">Dept</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Branded Footer -->
        <footer class="stage-footer">
            <div class="footer-left">
                <div class="sponsor-item">
                    <img src="../<?= $settings['sponsor_1_logo'] ?? 'assets/images/sponsor_1.png' ?>?v=<?= time() ?>" alt="Sponsor 1">
                </div>
                <div class="sponsor-item">
                    <img src="../<?= $settings['sponsor_2_logo'] ?? 'assets/images/sponsor_2.png' ?>?v=<?= time() ?>" alt="Sponsor 2">
                </div>
                <div class="sponsor-item">
                    <img src="../<?= $settings['sponsor_3_logo'] ?? 'assets/images/sponsor_3.png' ?>?v=<?= time() ?>" alt="Sponsor 3">
                </div>
                <div class="sponsor-item">
                    <img src="../<?= $settings['sponsor_4_logo'] ?? 'assets/images/sponsor_4.png' ?>?v=<?= time() ?>" alt="Sponsor 4">
                </div>
                
                <div class="footer-branding">
                    <p class="footer-motto"><?= $settings['uni_motto'] ?? 'Cultivating Human Talents' ?></p>
                    <p class="footer-tagline"><?= $settings['footer_text'] ?? 'Benadir University • Graduation Ceremony 2025' ?></p>
                </div>

                <div class="sponsor-divider"></div>
                <div class="powered-by">
                    <span>POWERED BY</span>
                    <strong><?= $settings['powered_by'] ?? 'SGS TECHNOLOGY' ?></strong>
                </div>
            </div>

            <div class="footer-right">
                <div class="anniversary-badge">
                    <span class="year-text"><?= $settings['anniversary_text'] ?? '10th' ?></span>
                    <span class="conv-text"><?= $settings['anniversary_subtext'] ?? 'ANNIVERSARY' ?></span>
                </div>
            </div>
        </footer>
    </div>
    <!-- Start Overlay (Required for Audio) -->
    

    <!-- Voice Settings Tray -->
    <div id="voice-settings">
        <div class="settings-header">
            <i class="fas fa-cog" style="color: var(--bu-gold);"></i>
            <h3 style="font-family: var(--font-secondary);">Voice Configuration</h3>
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-size: 0.8rem; font-weight: 700; color: #64748b;">ANNOUNCER VOICE</label>
            <select id="voice-select" onchange="handleVoiceChange(event)"></select>
        </div>

        <div class="voice-test-group" style="margin-bottom: 1.5rem;">
            <button id="test-voice-btn" onclick="testVoice()">
                <i class="fas fa-play" style="margin-right: 8px;"></i> TEST VOICE
            </button>
        </div>
                <button class="rate-btn" id="rate-down"><i class="fas fa-minus"></i></button>
                <span id="rate-value" style="font-weight: 700; min-width: 30px; text-align: center;">1.0</span>
                <button class="rate-btn" id="rate-up"><i class="fas fa-plus"></i></button>
            </div>
        </div>

        <button id="test-voice-btn" onclick="testVoice()" style="width: 100%; padding: 1rem; background: var(--bu-deep-blue); color: white; border: none; border-radius: var(--radius-md); font-weight: 700; cursor: pointer;">
            TEST ANNOUNCEMENT
        </button>
    </div>

    <!-- Control Panel (Moved outside main-stage) -->
    <div class="status-header">
        <div id="status-indicator" class="status-dot"></div>
        <span id="status-text" style="font-size: 0.9rem; font-weight: 600; color: #64748b;">DISCONNECTED</span>
    </div>

    <div class="control-panel">
        <div class="ctrl-btn" id="sound-btn" onclick="toggleSound()" title="Master Sound">
            <i class="fas fa-volume-up"></i>
        </div>
        <div class="ctrl-btn" onclick="toggleVoiceSettings()" title="Voice Settings">
            <i class="fas fa-microphone"></i>
        </div>
        <div class="ctrl-btn special" onclick="testVoice()" title="Re-announce Last Student">
            <i class="fas fa-redo-alt" style="margin-right: 8px;"></i> REPEAT NAME
        </div>

        <div class="ctrl-btn" onclick="system.ceremonyActive = !system.ceremonyActive; this.classList.toggle('active');" title="Pause Feed">
            <i class="fas fa-power-off"></i>
        </div>
    </div>

    <!-- Audio Elements -->



    <script>
        // ==================== SYSTEM STATE ====================
        const CARD_COUNT = parseInt(document.querySelector('.grid-container').dataset.cardCount) || 3;
        let system = {
            socket: null,
            activeGraduates: new Array(CARD_COUNT).fill(null),
            nextSlotIndex: 0,
            lastGraduate: null,
            voices: [],
            selectedVoice: null,
            currentUtterance: null, // Anti-GC
            speechRate: 1.0,
            soundEnabled: true,
            ceremonyActive: true, // Auto-start
            classYear: '<?= $settings['class_year'] ?? 'CLASS OF 2025' ?>',
            facultyColors: <?= json_encode($faculty_colors_js) ?>
        };

        // ==================== INITIALIZATION ====================
        // Auto-Start on Load
        document.addEventListener('DOMContentLoaded', () => {
            initSystem();
            initWebSocket();
            initVoices();
            // Enter fullscreen suggestion not enforced automatically to avoid browser block
            console.log("System Auto-Started");
            
            // Controls
            document.getElementById('rate-up').addEventListener('click', () => adjustSpeechRate(0.1));
            document.getElementById('rate-down').addEventListener('click', () => adjustSpeechRate(-0.1));
        });

        function initSystem() {
            console.log("BU Ceremony System Initialized");
            // Set initial volumes etc.
        }

        function initWebSocket() {
            const serverIp = window.location.hostname;
            system.socket = io(`http://${serverIp}:5001`, {
                transports: ['websocket', 'polling']
            });

            const indicator = document.getElementById('status-indicator');
            const statusText = document.getElementById('status-text');

            system.socket.on("connect", () => {
                indicator.classList.add('active');
                statusText.innerText = "LIVE CONNECTED";
            });

            system.socket.on("disconnect", () => {
                indicator.classList.remove('active');
                statusText.innerText = "DISCONNECTED";
            });

            system.socket.on("new_scan", (data) => {
                if (system.ceremonyActive) {
                    processNewGraduate(data);
                }
            });
        }

        // ==================== GRADUATE PROCESSING ====================
        function processNewGraduate(graduate) {
            // 1. Immediate Audio Logic (Zero latency start)
            announceGraduate(graduate);

            // 2. Update the next sequential slot
            system.activeGraduates[system.nextSlotIndex] = graduate;
            system.lastGraduate = graduate; 
            const changedSlot = system.nextSlotIndex + 1;
            
            // Increment Index (0 -> 1 -> 2 -> ... -> CARD_COUNT-1 -> 0)
            system.nextSlotIndex = (system.nextSlotIndex + 1) % CARD_COUNT;

            // 3. Refresh UI and animate the changed slot (Deferred to prioritize audio)
            setTimeout(() => {
                renderGrid(changedSlot);
                launchPremiumConfetti(changedSlot);
                
            }, 10);
        }


        function renderGrid(activeId = null) {
            for (let i = 1; i <= CARD_COUNT; i++) {
                const slotId = `slot-${i}`;
                const slotElement = document.getElementById(slotId);
                const data = system.activeGraduates[i - 1];

                if (data) {
                    const facultyClass = getFacultyClass(data.faculty);
                    slotElement.className = `student-slot ${facultyClass}`;
                    
                    const toTitleCase = (str) => {
                        return str.replace(
                            /\w\S*/g,
                            function(txt) {
                                return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
                            }
                        );
                    };

                    const displayName = data.full_name ? toTitleCase(data.full_name) : 'Name';
                    const displayFaculty = data.faculty ? toTitleCase(data.faculty) : 'Faculty';
                    const displayDept = data.department ? toTitleCase(data.department) : 'Dept';
                    const hideDept = !data.department || data.department.trim() === '' || data.department.trim() === '-' || displayFaculty.trim().toLowerCase() === displayDept.trim().toLowerCase();

                    slotElement.innerHTML = `
                        <div class="slot-info">
                            <div class="st-field st-field-name">
                                <span class="st-label"><?= $settings['label_name_text'] ?? 'NAME:' ?></span>
                                <h3 class="st-name">${displayName}</h3>
                            </div>
                            <div class="st-field st-field-faculty">
                                <span class="st-label"><?= $settings['label_faculty_text'] ?? 'FACULTY:' ?></span>
                                <h3 class="st-faculty">${displayFaculty}</h3>
                            </div>
                            <div class="st-field st-field-dept" style="${hideDept ? 'display:none;' : ''}">
                                <span class="st-label"><?= $settings['label_dept_text'] ?? 'DEPT:' ?></span>
                                <p class="st-department">${displayDept}</p>
                            </div>
                        </div>
                    `;

                    if (activeId === i) {
                        gsap.from(slotElement, {
                            scale: 0.8,
                            opacity: 0,
                            duration: 0.6,
                            ease: "power2.out"
                        });
                    }
                } else {
                    // Empty Slot
                    slotElement.className = 'student-slot empty';
                    slotElement.innerHTML = `
                        <div class="slot-info" style="opacity: 0;">
                            <div class="st-field st-field-name">
                                <span class="st-label"><?= $settings['label_name_text'] ?? 'NAME:' ?></span>
                                <h3 class="st-name">Name</h3>
                            </div>
                            <div class="st-field st-field-faculty">
                                <span class="st-label"><?= $settings['label_faculty_text'] ?? 'FACULTY:' ?></span>
                                <p class="st-faculty">Faculty</p>
                            </div>
                            <div class="st-field st-field-dept">
                                <span class="st-label"><?= $settings['label_dept_text'] ?? 'DEPT:' ?></span>
                                <p class="st-department">Dept</p>
                            </div>
                        </div>
                    `;
                }
            }
        }

        function getFacultyClass(facultyName) {
            if (!facultyName) return 'f-medicine';
            // Clean name to match CSS class format: f-computerscience
            const safeName = facultyName.toLowerCase().replace(/[^a-z0-9]/g, '');
            return `f-${safeName}`;
        }

        // ==================== ANNOUNCEMENTS ====================
        function initVoices() {
            system.voices = speechSynthesis.getVoices();
            const select = document.getElementById('voice-select');
            select.innerHTML = '';
            
            // Wait for voices if empty (Chrome)
            if (system.voices.length === 0) {
                speechSynthesis.onvoiceschanged = () => initVoices();
                return;
            }

            const savedVoiceURI = localStorage.getItem('sgs_selected_voice_uri');
            let foundSaved = false;

            system.voices.forEach((voice, index) => {
                // Modified: Show ALL voices as requested by user
                // const isMature = ['Ana', 'Aria', 'Jenny', 'Natasha', 'Natural', 'Brian', 'Google US English'].some(name => voice.name.includes(name));
                // const isEnglish = voice.lang.includes('en');
                
                // Show all voices so user can choose
                const option = document.createElement('option');
                option.value = index; 
                option.textContent = voice.name + ' (' + voice.lang + ')';
                
                if (voice.voiceURI === savedVoiceURI) {
                    option.selected = true;
                    system.selectedVoice = voice;
                    foundSaved = true;
                }
                select.appendChild(option);
            });

            // If no saved voice found, default logic
            if (!foundSaved) {
                const brianVoice = system.voices.find(v => v.name.includes('Brian'));
                const preferred = brianVoice || system.voices.find(v => 
                    (v.name.includes('Aria') || v.name.includes('Samantha') || v.lang === 'en-US') && 
                    v.lang.includes('en')
                );

                if (preferred) {
                    system.selectedVoice = preferred;
                    // Start selection in dropdown
                    const options = Array.from(select.options);
                    const match = options.find(opt => system.voices[opt.value].voiceURI === preferred.voiceURI);
                    if (match) match.selected = true;
                }
            }

            // Persistence on change is handled below
            speechSynthesis.onvoiceschanged = () => {
                const newVoices = speechSynthesis.getVoices();
                if (system.voices.length !== newVoices.length && newVoices.length > 0) {
                    initVoices(); 
                }
            };
        }

        function handleVoiceChange(event) {
            const index = event.target.value;
            const chosenVoice = system.voices[index];
            
            if (chosenVoice) {
                system.selectedVoice = chosenVoice;
                localStorage.setItem('sgs_selected_voice_uri', chosenVoice.voiceURI);
                console.log("Voice Saved:", chosenVoice.name);
                
                // Re-announce or test
                if (system.lastGraduate) {
                    announceGraduate(system.lastGraduate);
                } else {
                    testVoice();
                }
            }
        }

        function testVoice() {
            if (system.lastGraduate) {
                announceGraduate(system.lastGraduate);
                return;
            }

            const utterance = new SpeechSynthesisUtterance();
            if (system.selectedVoice) utterance.voice = system.selectedVoice;
            utterance.rate = 1.0; 
            utterance.pitch = 1.0; // Professional adult tone
            utterance.text = "Congratulations! Welcome to the graduation ceremony.";
            speechSynthesis.cancel();
            speechSynthesis.speak(utterance);
        }

        function announceGraduate(graduate) {
            // Debounce: Prevent double-speaking within 2 seconds for the same student
            const now = Date.now();
            if (system.lastSpokenId === graduate.student_id && (now - system.lastSpokenTime) < 2000) {
                console.log("Skipping duplicate announcement");
                return;
            }
            system.lastSpokenId = graduate.student_id;
            system.lastSpokenTime = now;

            // Robustness: Cancel and Resume to unblock engine
            // speechSynthesis.cancel(); // REMOVED: Allow queuing so descriptions aren't cut off
            speechSynthesis.resume();

            system.currentUtterance = new SpeechSynthesisUtterance();
            if (system.selectedVoice) system.currentUtterance.voice = system.selectedVoice;
            system.currentUtterance.rate = 1.05; 
            system.currentUtterance.pitch = 1.0; 
            
            const name = graduate.full_name;
            const faculty = graduate.faculty || '';
            const dept = graduate.department || '';

            // Custom Speech Template Logic
            const template = "<?= $settings['speech_template'] ?? '{name}. Faculty of {faculty}. Department of {dept}.' ?>";
            
            // Clean values
            const safeName = name;
            // Smart Faculty Check: If user types "Faculty of", remove it from data to avoid dupes IF template has it, 
            // BUT for flexibility, we assume template handles it.
            // If template says "Faculty of {faculty}" and data is "Faculty of Science", we get "Faculty of Faculty of Science".
            // To be safe, we rely on the USER to configure the template matching their data.
            // OR we strip "Faculty of" from data if present.
            
            const safeFaculty = faculty; 
            const safeDept = dept;

            let speechText = template
                .replace('{name}', safeName)
                .replace('{faculty}', safeFaculty)
                .replace('{dept}', safeDept);
            
            // Cleanup empty placeholders or double dots..
            speechText = speechText.replace('Faculty of .', '').replace('Department of .', '').replace('..', '.');

            system.currentUtterance.text = speechText;
            
            // Keep alive event
            system.currentUtterance.onend = () => { system.currentUtterance = null; };
            
            speechSynthesis.speak(system.currentUtterance);
        }

        function adjustSpeechRate(delta) {
            system.speechRate = Math.max(0.5, Math.min(2.0, system.speechRate + delta));
            document.getElementById('rate-value').innerText = system.speechRate.toFixed(1);
        }

        function toggleVoiceSettings() {
            document.getElementById('voice-settings').classList.toggle('active');
        }

        function toggleSound() {
            system.soundEnabled = !system.soundEnabled;
            const btn = document.getElementById('sound-btn');
            btn.innerHTML = system.soundEnabled ? '<i class="fas fa-volume-up"></i>' : '<i class="fas fa-volume-mute"></i>';
        }

        // ==================== VISUAL EFFECTS ====================
        function launchPremiumConfetti(slotIndex) {
            let origin = { y: 0.6, x: 0.5 };
            if (slotIndex) {
                const slot = document.getElementById(`slot-${slotIndex}`);
                if (slot) {
                    const rect = slot.getBoundingClientRect();
                    origin = {
                        x: (rect.left + rect.width / 2) / window.innerWidth,
                        y: (rect.top + rect.height / 2) / window.innerHeight
                    };
                }
            }
            confetti({
                particleCount: 150,
                spread: 70,
                origin: origin,
                colors: ['#C5A047', '#002855', '#FFFFFF']
            });
        }



        function playEffect(type) {
            if (!system.soundEnabled && type !== 'ambient') return;
            
            let elId = '';
            let vol = 0.3;
            
            switch(type) {

                case 'ambient': elId = 'ambient-sound'; vol = 0.1; break;
            }
            
            const el = document.getElementById(elId);
            if (el) {
                el.volume = vol;
                if (type === 'ambient') {
                    el.play().catch(e => console.warn('Ambient blocked:', e));
                } else {
                    el.currentTime = 0;
                    el.play().catch(e => console.error(`${type} play failed:`, e));
                }
            }
        }
    </script>
    <script>
        // --- SCANNER INTEGRATION (USB ONLY) ---
        const pythonServerUrl = `http://${window.location.hostname}:5001`;
        let scanBuffer = "";
        let submitTimer = null;
        const SUBMIT_DELAY = 300; 
        
        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

            const char = e.key;
            
            // Ignore control keys but allow characters
            if (char.length > 1 && char !== 'Enter') return; 

            clearTimeout(submitTimer);

            if (char === 'Enter') {
                if (scanBuffer.length > 0) {
                    processScan(scanBuffer);
                    scanBuffer = "";
                }
                return;
            } 
            
            if (char.length === 1) {
                scanBuffer += char;
            }

            // Auto-submit if silence
            submitTimer = setTimeout(() => {
                if (scanBuffer.length > 0) {
                    processScan(scanBuffer);
                    scanBuffer = "";
                }
            }, SUBMIT_DELAY);
        });

        // Visual feedback on focus
        window.onblur = () => { document.getElementById('scannerIndicator').style.opacity = '0.2'; document.getElementById('scannerIndicator').style.background = '#e74c3c'; };
        window.onfocus = () => { document.getElementById('scannerIndicator').style.opacity = '0.8'; document.getElementById('scannerIndicator').style.background = '#2ecc71'; };

        async function processScan(studentId) {
            // Pulse indicator on scan
            document.getElementById('scannerIndicator').style.transform = 'scale(2)';
            setTimeout(() => document.getElementById('scannerIndicator').style.transform = 'scale(1)', 200);
            const Toast = Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false, timer: 2000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            })

            // Toast.fire({ icon: 'info', title: 'Processing Scan...', text: studentId })
            
            try {
                const response = await fetch(`${pythonServerUrl}/scan/${studentId}`, { method: 'POST' });
                const data = await response.json();
                
                if (data.status === 'success') {
                     // Toast.fire({ icon: 'success', title: 'Displayed' });
                } else if (data.status === 'warning') {
                     // Toast.fire({ icon: 'warning', title: 'Wait', text: data.message });
                } else {
                     // Toast.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            } catch (error) {
                console.error("Scan Error:", error);
                // Toast.fire({ icon: 'error', title: 'Connection Error', text: 'Real-time Server offline?' });
            }
        }
    </script>
</body>
</html>