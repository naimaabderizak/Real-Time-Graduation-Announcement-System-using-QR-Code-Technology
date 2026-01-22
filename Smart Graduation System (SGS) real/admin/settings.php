<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// Handle Settings Update
if (isset($_POST['update_settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        // Check if setting exists
        $check = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
        $check->execute([$key]);
        
        if ($check->fetchColumn() > 0) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }
    }

    // Handle All Other Image Uploads
    $image_settings = [
        'primary_logo' => 'benadir_logo.jpg',
        'secondary_logo' => 'secondary_logo.png',
        'sponsor_1_logo' => 'sponsor_1.png',
        'sponsor_2_logo' => 'sponsor_2.png',
        'sponsor_3_logo' => 'sponsor_3.png',
        'sponsor_4_logo' => 'sponsor_4.png',
        'header_bg_image' => 'header_bg.jpg',
        'footer_bg_image' => 'footer_bg.jpg',
        'stage_bg_image' => 'stage_bg.jpg',
        'watermark_logo_1' => 'watermark_logo_1.png',
        'watermark_logo_2' => 'watermark_logo_2.png',
        'watermark_logo_3' => 'watermark_logo_3.png',
    ];

    foreach ($image_settings as $key => $filename) {
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] == 0) {
            $target_dir = "../assets/images/";
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($_FILES[$key]["tmp_name"], $target_file)) {
                $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?")
                    ->execute(['assets/images/' . $filename, $key]);
            }
        }
    }

    $message = "Settings updated successfully!";
}

// Handle Image Removal
if (isset($_POST['remove_image'])) {
    $image_key = $_POST['remove_image'];
    $pdo->prepare("UPDATE settings SET setting_value = '' WHERE setting_key = ?")
        ->execute([$image_key]);
    header("Location: settings.php?msg=removed");
    exit();
}

if (isset($_GET['msg']) && $_GET['msg'] == 'removed') {
    $message = "Image removed successfully!";
}

// Handle Settings Reset
if (isset($_POST['reset_settings']) && $_POST['reset_settings'] == '1') {
    $defaults = [
        'uni_name' => 'Benadir University',
        'uni_motto' => 'Cultivating Human Talents',
        'class_year' => 'Class of 2025',
        'footer_text' => 'BENADIR UNIVERSITY • GRADUATION CEREMONY 2025',
        'anniversary_text' => '21TH',
        'anniversary_subtext' => 'ANNIVERSARY',
        'powered_by' => 'SGS SYSTEM',
        'header_bg_color' => '#ffffff',
        'footer_bg_color' => '#002855',
        'header_logo_height' => '120px',
        'footer_logo_height' => '60px',
        'name_font_size' => '2.5rem',
        'faculty_font_size' => '1.8rem',
        'header_bg_image' => '',
        'footer_bg_image' => '',
        'stage_bg_image' => '',
        'watermark_logo_1' => '',
        'watermark_logo_2' => '',
        'watermark_logo_3' => '',
        'watermark_size' => '100',
        'watermark_opacity' => '0.1',
        'watermark_gap' => '60',
        'primary_logo' => 'assets/images/benadir_logo.jpg',
        'secondary_logo' => 'assets/images/secondary_logo.png',
        'card_scale' => '100'
    ];

    foreach ($defaults as $key => $value) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    $message = "Settings reset to defaults!";
}

// Fetch all settings
$raw_settings = $pdo->query("SELECT * FROM settings")->fetchAll();
$settings = [];
foreach ($raw_settings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - SGS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { 
            --primary-bg: #0f172a; 
            --sidebar-bg: #1e293b; 
            --card-bg: #1e293b; 
            --accent-color: #4f46e5; 
            --bu-gold: #C5A047; /* Added missing variable */
            --text-main: #f8fafc; 
            --text-dim: #94a3b8; 
        }
        body { font-family: 'Outfit', sans-serif; background-color: var(--primary-bg); color: var(--text-main); display: flex; }
        .sidebar { width: 280px; height: 100vh; background: var(--sidebar-bg); padding: 2rem; position: fixed; }
        .main-content { margin-left: 280px; padding: 3rem; width: 100%; }
        .nav-link { color: var(--text-dim); padding: 1rem; border-radius: 12px; display: flex; align-items: center; gap: 12px; text-decoration: none; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--accent-color); color: #fff; }
        .card { background: var(--card-bg); border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); }
        .form-label { color: var(--text-dim); font-weight: 600; font-size: 0.9rem; }
        .form-control { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 10px; }
        .form-control:focus { background: rgba(255,255,255,0.1); border-color: var(--accent-color); color: #fff; }
        .form-control option { background: #1e293b; color: #fff; padding: 8px; }
        .form-control select option:hover { background: var(--accent-color); }
        .text-dim { color: var(--text-dim) !important; }
        .text-gold { color: var(--bu-gold) !important; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2 class="mb-5" style="color: var(--accent-color); font-weight: 700;">SGS Admin</h2>
        <nav>
            <a href="dashboard.php" class="nav-link"><i data-lucide="layout-grid"></i> Dashboard</a>
            <a href="students.php" class="nav-link"><i data-lucide="users"></i> Students</a>
            <a href="faculties.php" class="nav-link"><i data-lucide="layers"></i> Faculties</a>
            <a href="settings.php" class="nav-link active"><i data-lucide="settings"></i> Settings</a>
            <a href="../display/top3_reveal.php" class="nav-link" target="_blank"><i data-lucide="award"></i> Top 3 Awards Reveal</a>
            <a href="logout.php" class="nav-link mt-5" style="color: #f87171;"><i data-lucide="log-out"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h1>System Configuration</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success mb-4"><?= $message ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 overflow-hidden">
            <form method="POST" enctype="multipart/form-data" id="settings-form">
                <!-- Tab Navigation Header with Save Button -->
                <div class="d-flex justify-content-between align-items-center bg-dark bg-opacity-25 p-2 pr-4">
                    <ul class="nav nav-tabs border-0 gap-2" id="settingsTabs" role="tablist" style="border: none !important;">
                        <li class="nav-item">
                            <button class="nav-link active border-0 rounded-3 text-white px-4 py-2" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" style="background: var(--accent-color);"><i data-lucide="info" class="mb-1 me-1" style="width: 16px;"></i> General</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link border-0 rounded-3 text-white px-4 py-2" id="branding-tab" data-bs-toggle="tab" data-bs-target="#branding" type="button" style="background: transparent;"><i data-lucide="image" class="mb-1 me-1" style="width: 16px;"></i> Appearance</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link border-0 rounded-3 text-white px-4 py-2" id="colors-tab" data-bs-toggle="tab" data-bs-target="#colors" type="button" style="background: transparent;"><i data-lucide="palette" class="mb-1 me-1" style="width: 16px;"></i> Colors</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link border-0 rounded-3 text-white px-4 py-2" id="typography-tab" data-bs-toggle="tab" data-bs-target="#typography" type="button" style="background: transparent;"><i data-lucide="type" class="mb-1 me-1" style="width: 16px;"></i> Font & Size</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link border-0 rounded-3 text-white px-4 py-2" id="backgrounds-tab" data-bs-toggle="tab" data-bs-target="#backgrounds" type="button" style="background: transparent;"><i data-lucide="monitor" class="mb-1 me-1" style="width: 16px;"></i> Backgrounds</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link border-0 rounded-3 text-white px-4 py-2" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" style="background: transparent;"><i data-lucide="shield-alert" class="mb-1 me-1" style="width: 16px;"></i> System</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link border-0 rounded-3 text-white px-4 py-2" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced" type="button" style="background: transparent;"><i data-lucide="sliders" class="mb-1 me-1" style="width: 16px;"></i> Advanced</button>
                        </li>
                    </ul>
                    <button type="submit" name="update_settings" class="btn btn-primary px-4 py-2 mr-3" style="margin-right: 15px;">
                        <i data-lucide="save"></i> Save All Settings
                    </button>
                </div>

            <style>
                .nav-tabs .nav-link.active {
                    background: var(--accent-color) !important;
                    color: white !important;
                    box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
                }
                .nav-tabs .nav-link:not(.active):hover {
                    background: rgba(255,255,255,0.1) !important;
                }
            </style>

            <div class="p-5">
                <div class="tab-content" id="settingsTabsContent">
                    
                    <!-- TAB 1: GENERAL -->
                    <div class="tab-pane fade show active" id="general">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">University Name</label>
                                <input type="text" name="settings[uni_name]" class="form-control" value="<?= $settings['uni_name'] ?? '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">University Motto</label>
                                <input type="text" name="settings[uni_motto]" class="form-control" value="<?= $settings['uni_motto'] ?? '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Graduation Class (e.g. Class of 2025)</label>
                                <input type="text" name="settings[class_year]" class="form-control" value="<?= $settings['class_year'] ?? '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Footer Slogan</label>
                                <input type="text" name="settings[footer_text]" class="form-control" value="<?= $settings['footer_text'] ?? '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Anniversary Number</label>
                                <input type="text" name="settings[anniversary_text]" class="form-control" value="<?= $settings['anniversary_text'] ?? '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Anniversary Label</label>
                                <input type="text" name="settings[anniversary_subtext]" class="form-control" value="<?= $settings['anniversary_subtext'] ?? '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Powered By Branding</label>
                                <input type="text" name="settings[powered_by]" class="form-control" value="<?= $settings['powered_by'] ?? '' ?>">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: BRANDING -->
                    <div class="tab-pane fade" id="branding">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Primary Logo (Header Left)</label>
                                    <?php if (!empty($settings['primary_logo'])): ?>
                                        <button type="submit" name="remove_image" value="primary_logo" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.75rem;">Remove</button>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3 bg-white bg-opacity-5 rounded-4 border border-white border-opacity-10">
                                    <img src="../<?= $settings['primary_logo'] ?>?v=<?= time() ?>" class="mb-3 d-block" style="height: 50px;">
                                    <input type="file" name="primary_logo" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Secondary Logo (Header Right)</label>
                                    <?php if (!empty($settings['secondary_logo'])): ?>
                                        <button type="submit" name="remove_image" value="secondary_logo" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.75rem;">Remove</button>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3 bg-white bg-opacity-5 rounded-4 border border-white border-opacity-10">
                                    <img src="../<?= $settings['secondary_logo'] ?>?v=<?= time() ?>" class="mb-3 d-block" style="height: 50px;">
                                    <input type="file" name="secondary_logo" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3 mt-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Sponsor 1</label>
                                    <?php if (!empty($settings['sponsor_1_logo'])): ?>
        <button type="submit" name="remove_image" value="sponsor_1_logo" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.75rem;">Remove</button>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3 bg-white bg-opacity-5 rounded-4 border border-white border-opacity-10">
                                    <img src="../<?= $settings['sponsor_1_logo'] ?? 'assets/images/sponsor_1.png' ?>?v=<?= time() ?>" class="mb-3 d-block w-100" style="height: 80px; object-fit: contain;">
                                    <input type="file" name="sponsor_1_logo" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3 mt-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Sponsor 2</label>
                                    <?php if (!empty($settings['sponsor_2_logo'])): ?>
                                        <button type="submit" name="remove_image" value="sponsor_2_logo" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.75rem;">Remove</button>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3 bg-white bg-opacity-5 rounded-4 border border-white border-opacity-10">
                                    <img src="../<?= $settings['sponsor_2_logo'] ?? 'assets/images/sponsor_2.png' ?>?v=<?= time() ?>" class="mb-3 d-block w-100" style="height: 80px; object-fit: contain;">
                                    <input type="file" name="sponsor_2_logo" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3 mt-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Sponsor 3</label>
                                    <?php if (!empty($settings['sponsor_3_logo'])): ?>
                                        <button type="submit" name="remove_image" value="sponsor_3_logo" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.75rem;">Remove</button>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3 bg-white bg-opacity-5 rounded-4 border border-white border-opacity-10">
                                    <img src="../<?= $settings['sponsor_3_logo'] ?? 'assets/images/sponsor_3.png' ?>?v=<?= time() ?>" class="mb-3 d-block w-100" style="height: 80px; object-fit: contain;">
                                    <input type="file" name="sponsor_3_logo" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3 mt-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Sponsor 4</label>
                                    <?php if (!empty($settings['sponsor_4_logo'])): ?>
                                        <button type="submit" name="remove_image" value="sponsor_4_logo" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.75rem;">Remove</button>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3 bg-white bg-opacity-5 rounded-4 border border-white border-opacity-10">
                                    <img src="../<?= $settings['sponsor_4_logo'] ?? 'assets/images/sponsor_4.png' ?>?v=<?= time() ?>" class="mb-3 d-block w-100" style="height: 80px; object-fit: contain;">
                                    <input type="file" name="sponsor_4_logo" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: COLORS -->
                    <div class="tab-pane fade" id="colors">
                        <div class="row g-4">
                             <div class="col-md-6">
                                <label class="form-label d-block">Header Background Color</label>
                                <div class="d-flex gap-3 align-items-center">
                                    <input type="color" name="settings[header_bg_color]" class="form-control form-control-color border-0 p-0 overflow-hidden rounded-circle" value="<?= $settings['header_bg_color'] ?>" style="width: 50px; height: 50px;">
                                    <input type="text" class="form-control" value="<?= $settings['header_bg_color'] ?>" readonly style="width: 100px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label d-block">Footer Background Color</label>
                                <div class="d-flex gap-3 align-items-center">
                                    <input type="color" name="settings[footer_bg_color]" class="form-control form-control-color border-0 p-0 overflow-hidden rounded-circle" value="<?= $settings['footer_bg_color'] ?>" style="width: 50px; height: 50px;">
                                    <input type="text" class="form-control" value="<?= $settings['footer_bg_color'] ?>" readonly style="width: 100px;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block">Student Name Color</label>
                                <div class="d-flex gap-3 align-items-center">
                                    <input type="color" name="settings[name_text_color]" class="form-control form-control-color border-0 p-0 overflow-hidden rounded-circle" value="<?= $settings['name_text_color'] ?? '#ffffff' ?>" style="width: 50px; height: 50px;">
                                    <input type="text" class="form-control" value="<?= $settings['name_text_color'] ?? '#ffffff' ?>" readonly style="width: 100px;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block">Faculty Text Color</label>
                                <div class="d-flex gap-3 align-items-center">
                                    <input type="color" name="settings[faculty_text_color]" class="form-control form-control-color border-0 p-0 overflow-hidden rounded-circle" value="<?= $settings['faculty_text_color'] ?? '#ffffff' ?>" style="width: 50px; height: 50px;">
                                    <input type="text" class="form-control" value="<?= $settings['faculty_text_color'] ?? '#ffffff' ?>" readonly style="width: 100px;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block">Department Text Color</label>
                                <div class="d-flex gap-3 align-items-center">
                                    <input type="color" name="settings[dept_text_color]" class="form-control form-control-color border-0 p-0 overflow-hidden rounded-circle" value="<?= $settings['dept_text_color'] ?? '#ffffff' ?>" style="width: 50px; height: 50px;">
                                    <input type="text" class="form-control" value="<?= $settings['dept_text_color'] ?? '#ffffff' ?>" readonly style="width: 100px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: TYPOGRAPHY & SIZES -->
                    <div class="tab-pane fade" id="typography">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label">Name Font Size (e.g. 2.5rem)</label>
                                <input type="text" name="settings[name_font_size]" class="form-control" value="<?= $settings['name_font_size'] ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Faculty Font Size (e.g. 1.8rem)</label>
                                <input type="text" name="settings[faculty_font_size]" class="form-control" value="<?= $settings['faculty_font_size'] ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Dept Font Size (e.g. 1.0rem)</label>
                                <input type="text" name="settings[dept_font_size]" class="form-control" value="<?= $settings['dept_font_size'] ?? '1.0rem' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Header Logo Height (e.g. 120px)</label>
                                <input type="text" name="settings[header_logo_height]" class="form-control" value="<?= $settings['header_logo_height'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Footer Logo Height (e.g. 60px)</label>
                                <input type="text" name="settings[footer_logo_height]" class="form-control" value="<?= $settings['footer_logo_height'] ?>">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: BACKGROUNDS & WATERMARK -->
                    <div class="tab-pane fade" id="backgrounds">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Header BG Image</label>
                                    <?php if (!empty($settings['header_bg_image'])): ?>
                                        <button type="submit" name="remove_image" value="header_bg_image" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.75rem;">Remove</button>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="header_bg_image" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Footer BG Image</label>
                                    <?php if (!empty($settings['footer_bg_image'])): ?>
                                        <button type="submit" name="remove_image" value="footer_bg_image" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.75rem;">Remove</button>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="footer_bg_image" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Main Stage BG Image</label>
                                    <?php if (!empty($settings['stage_bg_image'])): ?>
                                        <button type="submit" name="remove_image" value="stage_bg_image" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.75rem;">Remove</button>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="stage_bg_image" class="form-control">
                            </div>
                        </div>

                        <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
                        
                        <h5 class="text-warning mb-3">Tiled Logo Background (Watermark Effect)</h5>
                        <div class="row g-4">
                            <?php $key = "watermark_logo_1"; ?>
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Watermark Logo</label>
                                    <?php if (!empty($settings[$key])): ?>
                                        <button type="submit" name="remove_image" value="<?= $key ?>" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.75rem;">Remove</button>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3 bg-white bg-opacity-5 rounded-4 border border-white border-opacity-10">
                                    <?php if (!empty($settings[$key])): ?>
                                        <img src="../<?= $settings[$key] ?>?v=<?= time() ?>" class="mb-3 d-block w-100" style="height: 60px; object-fit: contain;">
                                    <?php else: ?>
                                        <div class="text-center py-4 text-dim" style="border: 2px dashed rgba(255,255,255,0.05); border-radius: 10px;">
                                            <i data-lucide="image-plus" class="mb-2" style="width: 20px; opacity: 0.3;"></i>
                                            <div style="font-size: 0.8rem;">No image</div>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="<?= $key ?>" class="form-control mt-2">
                                </div>
                            </div>
                            
                            <div class="col-md-4 mt-4">
                                <label class="form-label">Logo Size (px)</label>
                                <input type="number" name="settings[watermark_size]" class="form-control" value="<?= $settings['watermark_size'] ?? '100' ?>" placeholder="100">
                                <small class="text-muted">Default: 100px</small>
                            </div>
                            <div class="col-md-4 mt-4">
                                <label class="form-label">Opacity (0-1)</label>
                                <input type="number" step="0.1" min="0" max="1" name="settings[watermark_opacity]" class="form-control" value="<?= $settings['watermark_opacity'] ?? '0.1' ?>" placeholder="0.1">
                                <small class="text-muted">Default: 0.1</small>
                            </div>
                            <div class="col-md-4 mt-4">
                                <label class="form-label">Gap (px)</label>
                                <input type="number" name="settings[watermark_gap]" class="form-control" value="<?= $settings['watermark_gap'] ?? '60' ?>" placeholder="60">
                                <small class="text-muted">Space between logos</small>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: ADVANCED -->
                    <div class="tab-pane fade" id="advanced">
                        <h5 class="text-warning mb-3">Custom Labels</h5>
                        <div class="row g-4 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Name Label</label>
                                <input type="text" name="settings[label_name_text]" class="form-control" value="<?= $settings['label_name_text'] ?? 'NAME:' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Faculty Label</label>
                                <input type="text" name="settings[label_faculty_text]" class="form-control" value="<?= $settings['label_faculty_text'] ?? 'FACULTY:' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Dept Label</label>
                                <input type="text" name="settings[label_dept_text]" class="form-control" value="<?= $settings['label_dept_text'] ?? 'DEPT:' ?>">
                            </div>
                        </div>

                        <h5 class="text-warning mb-3">Visual & Audio</h5>
                        <div class="row g-4 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Font Family</label>
                                <select name="settings[font_family]" class="form-control">
                                    <option value="Inter" <?= ($settings['font_family'] ?? '') == 'Inter' ? 'selected' : '' ?>>Inter (Modern)</option>
                                    <option value="Playfair Display" <?= ($settings['font_family'] ?? '') == 'Playfair Display' ? 'selected' : '' ?>>Playfair Display (Elegant)</option>
                                    <option value="Roboto" <?= ($settings['font_family'] ?? '') == 'Roboto' ? 'selected' : '' ?>>Roboto (Clean)</option>
                                    <option value="Oswald" <?= ($settings['font_family'] ?? '') == 'Oswald' ? 'selected' : '' ?>>Oswald (Bold)</option>
                                    <option value="Marcellus" <?= ($settings['font_family'] ?? '') == 'Marcellus' ? 'selected' : '' ?>>Marcellus (Classic)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Animations</label>
                                <select name="settings[animation_enabled]" class="form-control">
                                    <option value="1" <?= ($settings['animation_enabled'] ?? '1') == '1' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= ($settings['animation_enabled'] ?? '1') == '0' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Number of Cards</label>
                                <select name="settings[layout_mode]" class="form-control">
                                    <option value="1" <?= ($settings['layout_mode'] ?? '3') == '1' ? 'selected' : '' ?>>1 Card</option>
                                    <option value="2" <?= ($settings['layout_mode'] ?? '3') == '2' ? 'selected' : '' ?>>2 Cards</option>
                                    <option value="3" <?= ($settings['layout_mode'] ?? '3') == '3' ? 'selected' : '' ?>>3 Cards</option>
                                    <option value="4" <?= ($settings['layout_mode'] ?? '3') == '4' ? 'selected' : '' ?>>4 Cards</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Card Size Scale (%)</label>
                                <input type="number" name="settings[card_scale]" class="form-control" value="<?= $settings['card_scale'] ?? '100' ?>" min="50" max="150" step="5">
                                <small class="text-muted">Default: 100%. Adjust to fit screen.</small>
                            </div>
                        </div>

                        <h5 class="text-warning mb-3">Announcement Format</h5>
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label">Speech Pattern</label>
                                <input type="text" name="settings[speech_template]" class="form-control" value="<?= $settings['speech_template'] ?? '{name}. Faculty of {faculty}. Department of {dept}.' ?>">
                                <small class="text-muted">Use variables: <code>{name}</code>, <code>{faculty}</code>, <code>{dept}</code></small>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 6: SYSTEM ACTIONS -->
                    <div class="tab-pane fade" id="system">
                        <div class="p-4 bg-danger bg-opacity-10 rounded-4 border border-danger border-opacity-20 text-center">
                            <i data-lucide="alert-triangle" class="text-danger mb-3" style="width: 48px; height: 48px;"></i>
                            <h4 class="text-danger mb-3">Reset to Defaults</h4>
                            <p class="text-dim mb-4" style="color: #94a3b8 !important;">Are you sure you want to reset all settings to their original defaults?<br>This will remove all your customizations (Colors, Fonts, Logos, etc.).</p>
                            <button type="button" onclick="confirmReset()" class="btn btn-danger px-5 py-3 rounded-pill fw-bold">
                                <i data-lucide="rotate-ccw" class="me-2"></i> Confirm Reset Settings
                            </button>
                            <!-- Hidden form for reset -->
                            <input type="hidden" name="reset_settings" id="reset-input" value="0">
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        lucide.createIcons();

        function confirmReset() {
            Swal.fire({
                title: 'Are you sure?',
                text: "ALL settings will be restored to their original values!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Reset Now!',
                cancelButtonText: 'Cancel',
                background: '#1e293b',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('reset-input').value = '1';
                    document.getElementById('settings-form').submit();
                }
            })
        }
    </script>
</body>
</html>
