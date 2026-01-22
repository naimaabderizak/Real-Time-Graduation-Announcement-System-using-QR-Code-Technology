<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get filters
$faculty = isset($_GET['faculty']) ? $_GET['faculty'] : 'All';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Build Query
$query = "SELECT * FROM students WHERE 1=1";
$params = [];

if ($faculty && $faculty !== 'All') {
    $query .= " AND faculty = ?";
    $params[] = $faculty;
}


$query .= " ORDER BY full_name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Fetch settings for branding
$raw_settings = $pdo->query("SELECT * FROM settings")->fetchAll();
$settings = [];
foreach ($raw_settings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

?>
<!DOCTYPE html>
<html lang="so">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print 8-per-A4 (No QR) - <?= htmlspecialchars($faculty) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f1f5f9;
            padding: 20px;
        }

        /* A4 Page container for screen preview */
        .a4-page {
            width: 210mm;
            height: 297mm;
            background: #fff;
            margin: 0 auto 30px auto;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(4, 1fr);
            border: 1px solid #ddd;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        /* Each student card */
        .student-card {
            border: 0.5px dashed #e2e8f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 10px;
            position: relative;
        }

        /* Top Accent for each card */
        .student-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #6366f1, #a855f7);
        }


        .info-area {
            line-height: 1.2;
        }

        .st-name {
            font-size: 16px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 2px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .st-id {
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
        }

        .st-faculty {
            font-size: 11px;
            font-weight: 700;
            color: #6366f1;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1px;
        }

        .st-dept {
            font-size: 10px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Print Specifics */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }
            body { 
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print { display: none !important; }
            .a4-page {
                margin: 0 !important;
                box-shadow: none !important;
                border: none !important;
                page-break-after: always;
                page-break-inside: avoid;
            }
            .a4-page:last-child {
                page-break-after: auto;
            }
            .student-card {
                border-color: #ddd; /* Visible lines for cutting if wanted */
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
    </style>
</head>
<body>

    <div class="controls no-print">
        <div class="info-text" style="font-size: 14px; color: #64748b; margin-bottom: 15px; text-align: center;">
            <strong><?= count($students) ?> Students</strong><br>
            <em>8 Students per A4 Page</em><br>
            Faculty: <?= htmlspecialchars($faculty) ?>
        </div>
        <a href="javascript:void(0)" onclick="window.print()" class="btn-print">🖨️ Print to A4</a>
        <a href="students.php" class="btn-back">← Back</a>
    </div>

    <?php 
    // Chunk students into groups of 8
    foreach (array_chunk($students, 8) as $student_group): 
    ?>
    <div class="a4-page">
        <?php foreach ($student_group as $student): ?>
        <div class="student-card">

            <div class="info-area">
                <h1 class="st-name"><?= htmlspecialchars($student['full_name']) ?></h1>
                <div class="st-id">ID: <?= htmlspecialchars($student['student_id']) ?></div>
                <div class="st-faculty"><?= htmlspecialchars($student['faculty']) ?></div>
                <?php if (trim($student['faculty']) !== trim($student['department']) && !empty($student['department'])): ?>
                    <div class="st-dept"><?= htmlspecialchars($student['department']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php 
        // Fill empty slots if last page is not full
        $empty_slots = 8 - count($student_group);
        for($i=0; $i<$empty_slots; $i++):
        ?>
        <div class="student-card" style="border: none; background: transparent;"></div>
        <?php endfor; ?>
    </div>
    <?php endforeach; ?>

</body>
</html>
