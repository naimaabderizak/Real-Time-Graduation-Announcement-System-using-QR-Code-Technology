<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch students based on filter
$faculty = isset($_GET['faculty']) ? $_GET['faculty'] : '';
$size_mode = isset($_GET['size']) ? $_GET['size'] : 'small'; // default to small as requested earlier

if($faculty && $faculty !== 'All') {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE faculty = ? ORDER BY student_id ASC");
    $stmt->execute([$faculty]);
    $students = $stmt->fetchAll();
} else {
    $students = $pdo->query("SELECT * FROM students ORDER BY student_id ASC")->fetchAll();
}

// Fetch settings for branding
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
    <title>Print QR Codes - SGS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .controls {
            max-width: 1200px;
            margin: 0 auto 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .controls h2 {
            color: #1e293b;
            font-size: 24px;
            font-weight: 700;
        }
        
        .controls .filter-info {
            color: #64748b;
            font-size: 14px;
            margin-top: 4px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #4f46e5;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        
        .btn-secondary {
            background: #64748b;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #475569;
        }
        
        /* Grid Layout */
        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .student-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            page-break-inside: avoid;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .student-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4f46e5, #7c3aed);
        }
        
        .student-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
            border-color: #4f46e5;
        }


        .st-name {
            font-size: 18px;
            font-weight: 700;
            margin: 8px 0;
            color: #1e293b;
            line-height: 1.3;
        }

        .st-id {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 4px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 6px;
        }
        
        .st-faculty {
            font-size: 12px;
            color: #7c3aed;
            font-weight: 600;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .qr-code {
            margin-top: auto;
            padding: 12px;
            background: #fafafa;
            border-radius: 12px;
            border: 2px dashed #e2e8f0;
        }
        
        .qr-code canvas {
            border-radius: 8px;
        }
        
        .card-footer {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            width: 100%;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
        }

        /* Screen Layout Fixes */
        .print-header { 
            display: none; 
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }
        .print-header-logo { height: 60px; width: auto; }
        
        /* Print Styling */
        @media print {
            @page {
                size: A5;
                margin: 5mm;
            }

            body { 
                background: white !important; 
                padding: 0 !important; 
                margin: 0 !important;
                width: 100% !important;
            }
            .controls, .no-print { display: none !important; }
            
            .print-header { 
                display: flex !important; 
                justify-content: space-between; 
                align-items: center; 
                border-bottom: 2px solid #1e293b; 
                padding-bottom: 15px; 
                margin-bottom: 10px; 
                page-break-after: avoid;
            }
            .print-header-left { display: flex; align-items: center; gap: 10px; }
            .print-header-logo { height: 50px !important; }
            .print-title { font-size: 18px; font-weight: 700; color: #1e293b; margin: 0; }
            .print-subtitle { font-size: 11px; color: #64748b; margin: 0; }
            .text-right { text-align: right; }
            .fw-bold { font-weight: 700; }

            .qr-grid { 
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 5mm !important;
                width: 100% !important;
                margin-top: 0 !important;
                background: none !important;
                padding: 0 !important;
                justify-content: flex-start !important;
            }

            .student-card {
                /* A5 is 148mm wide. 2 columns is best. */
                width: calc(50% - 5mm) !important; 
                margin-bottom: 5mm !important;
                border: 1.5px solid #e2e8f0 !important;
                border-radius: 10px !important;
                padding: 10px !important;
                page-break-inside: avoid !important;
                box-shadow: none !important;
            }

            /* Size mode overrides - For A5, strict 2 cols is safest, maybe 3 for small */
            body[data-size="small"] .student-card { width: calc(33.33% - 5mm) !important; }
            body[data-size="big"] .student-card { width: calc(50% - 5mm) !important; }
        }
    </style>
</head>
<body data-size="<?= htmlspecialchars($size_mode) ?>">


    <div class="controls">
        <div>
            <h2>🎓 Student QR Codes</h2>
            <div class="filter-info">
                <?php if($faculty && $faculty !== 'All'): ?>
                    Showing: <strong><?= htmlspecialchars($faculty) ?></strong> (<?= count($students) ?> students)
                <?php else: ?>
                    Showing: <strong>All Faculties</strong> (<?= count($students) ?> students)
                <?php endif; ?>
            </div>
        </div>
        <div class="btn-group">
            <div class="me-3 d-flex align-items-center bg-light p-1 rounded border no-print">
                <span class="small fw-bold px-2 text-muted">Layout:</span>
                <a href="?faculty=<?= urlencode($faculty) ?>&size=small" class="btn btn-sm <?= $size_mode == 'small' ? 'btn-dark' : 'btn-outline-dark' ?> border-0">Small</a>
                <a href="?faculty=<?= urlencode($faculty) ?>&size=big" class="btn btn-sm <?= $size_mode == 'big' ? 'btn-dark' : 'btn-outline-dark' ?> border-0">Big</a>
            </div>
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Print QR Codes
            </button>
            <a href="students.php" class="btn btn-secondary">
                ← Back to Students
            </a>
        </div>
    </div>

    <!-- Professional Print Header -->
    <div class="print-header">
        <div class="print-header-left">
            <img src="../<?= $settings['primary_logo'] ?? 'assets/images/benadir_logo.jpg' ?>" class="print-header-logo">
            <div>
                <div class="print-title"><?= $settings['uni_name'] ?? 'Benadir University' ?></div>
                <div class="print-subtitle"><?= $settings['uni_motto'] ?? 'Cultivating Human Talents' ?></div>
            </div>
        </div>
        <div class="text-right">
            <div class="fw-bold" style="font-size: 18px; color: #1e293b;">GRADUATION QR CODES</div>
            <div class="text-muted small"><?= date('F d, Y') ?></div>
            <div style="color: #4f46e5; font-size: 12px; font-weight: 600;">Faculty: <?= htmlspecialchars($faculty ?: 'All') ?></div>
        </div>
    </div>

    <div class="qr-grid">
        <?php foreach ($students as $student): ?>
        <div class="student-card">
            
            <div class="st-name"><?= htmlspecialchars($student['full_name']) ?></div>
            <div class="st-id"><?= htmlspecialchars($student['student_id']) ?></div>
            <div class="st-faculty"><?= htmlspecialchars($student['faculty']) ?></div>
            
            <div id="qr-<?= $student['student_id'] ?>" class="qr-code"></div>
            
            <div class="card-footer">
                <?= $settings['uni_name'] ?? 'Benadir University' ?> • <?= $settings['class_year'] ?? '2025' ?>
            </div>

            <script>
                new QRCode(document.getElementById("qr-<?= $student['student_id'] ?>"), {
                    text: "<?= $student['student_id'] ?>",
                    width: <?= $size_mode == 'big' ? '180' : '120' ?>,
                    height: <?= $size_mode == 'big' ? '180' : '120' ?>,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            </script>
        </div>
        <?php endforeach; ?>
    </div>

</body>
</html>
