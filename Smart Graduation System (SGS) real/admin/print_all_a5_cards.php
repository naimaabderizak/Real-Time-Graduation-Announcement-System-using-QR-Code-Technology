<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get faculty filter
$faculty = isset($_GET['faculty']) ? $_GET['faculty'] : 'All';

// Fetch students
if ($faculty && $faculty !== 'All') {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE faculty = ? ORDER BY full_name ASC");
    $stmt->execute([$faculty]);
    $students = $stmt->fetchAll();
} else {
    $students = $pdo->query("SELECT * FROM students ORDER BY faculty, full_name ASC")->fetchAll();
}

// Fetch settings for branding
$raw_settings = $pdo->query("SELECT * FROM settings")->fetchAll();
$settings = [];
foreach ($raw_settings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

$class_year = $settings['class_year'] ?? '2026';
if (stripos($class_year, 'Class of') !== false) {
    $footer_text = $class_year;
} else {
    $footer_text = "Class of " . $class_year;
}
?>
<!DOCTYPE html>
<html lang="so">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print All A5 Cards - <?= htmlspecialchars($faculty) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f1f5f9;
            margin: 0;
        }

        /* Each card is one page */
        .page-container {
            width: 210mm;
            height: 148mm;
            background: #fff;
            display: flex;
            align-items: center;
            position: relative;
            page-break-after: always; /* Each student gets their own page */
            margin-bottom: 20px; /* For screen preview */
        }

        .page-container:last-child {
            page-break-after: auto;
        }

        /* Content Area */
        .content-box {
            width: 50%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px 60px 20px 20px;
            text-align: center;
        }

        .content-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 50%;
            height: 6px;
            background: linear-gradient(90deg, #6366f1, #a855f7);
        }


        .info-area {
            margin-bottom: 15px;
        }

        .st-name {
            font-size: 24px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 5px;
            line-height: 1.1;
            word-wrap: break-word;
        }

        .st-id {
            font-size: 15px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 5px;
        }

        .st-faculty {
            font-size: 16px;
            font-weight: 700;
            color: #6366f1;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 2px;
        }

        .st-dept {
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .qr-wrapper {
            padding: 10px;
            border: 2px dashed #e2e8f0;
            border-radius: 15px;
            display: inline-block;
        }

        /* Print Specifics */
        @media print {
            @page {
                size: A5 landscape;
                margin: 0;
            }
            body { 
                background: white !important;
                margin: 0 !important;
            }
            .no-print { display: none !important; }
            .page-container {
                width: 210mm !important;
                height: 148mm !important;
                box-shadow: none !important;
                margin: 0 !important;
            }
        }

        /* Control Panel */
        .controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .btn-print {
            background: #1e293b;
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-bottom: 10px;
        }

        .btn-back {
            background: #64748b;
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .info-text {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="controls no-print">
        <div class="info-text">
            <strong><?= count($students) ?> Students</strong><br>
            Faculty: <?= htmlspecialchars($faculty) ?>
        </div>
        <a href="javascript:void(0)" onclick="window.print()" class="btn-print">🖨️ Print All Cards</a>
        <a href="students.php" class="btn-back">← Back</a>
    </div>

    <?php foreach ($students as $student): ?>
    <div class="page-container">
        <div class="content-box">

            <div class="info-area">
                <h1 class="st-name"><?= htmlspecialchars($student['full_name']) ?></h1>
                <div class="st-id">ID: <?= htmlspecialchars($student['student_id']) ?></div>
                <div class="st-faculty"><?= htmlspecialchars($student['faculty']) ?></div>
                <?php if (trim($student['faculty']) !== trim($student['department']) && !empty($student['department'])): ?>
                    <div class="st-dept"><?= htmlspecialchars($student['department']) ?></div>
                <?php endif; ?>
            </div>

            <div class="qr-wrapper">
                <div id="qr-<?= $student['id'] ?>" data-student-id="<?= htmlspecialchars($student['student_id']) ?>"></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script>
        // Generate QR codes in batches to prevent browser freeze
        document.addEventListener('DOMContentLoaded', function() {
            const qrElements = document.querySelectorAll('[id^="qr-"]');
            let index = 0;
            
            function generateNextBatch() {
                const batchSize = 5; // Generate 5 QR codes at a time
                const end = Math.min(index + batchSize, qrElements.length);
                
                for (let i = index; i < end; i++) {
                    const element = qrElements[i];
                    const studentId = element.getAttribute('data-student-id');
                    
                    new QRCode(element, {
                        text: studentId,
                        width: 180,
                        height: 180,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                }
                
                index = end;
                
                // Continue generating if there are more
                if (index < qrElements.length) {
                    setTimeout(generateNextBatch, 100); // Small delay between batches
                } else {
                    console.log('All QR codes generated successfully!');
                }
            }
            
            // Start generating
            generateNextBatch();
        });
    </script>

</body>
</html>
