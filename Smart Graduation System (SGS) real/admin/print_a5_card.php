<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Student ID is missing!");
}

$student_id = $_GET['id'];

// Fetch student data
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student not found!");
}

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
    <title>Print - <?= htmlspecialchars($student_id) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" onerror="handleQRError()"></script>
    <style>
        .loading-indicator {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 20px 40px;
            border-radius: 10px;
            font-size: 18px;
            z-index: 9999;
        }
    </style>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .page-container {
            width: 210mm;
            height: 148mm;
            background: #fff;
            display: flex;
            align-items: center;
            position: relative;
        }

        /* Content Area - Perfectly Centered in the Left Half */
        .content-box {
            width: 50%; /* Left half of the card */
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px 60px 20px 20px; /* Reduced vertical padding */
            text-align: center;
        }

        /* Top Accent for the left half */
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
            font-size: 24px; /* Reduced specifically for long overflowing names */
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
                display: block !important; 
                margin: 0 !important;
                padding: 0 !important;
                min-height: 0 !important;
                height: auto !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print { display: none !important; }
            .page-container {
                width: 210mm !important;
                height: 148mm !important;
                /* overflow: hidden;  Ensure content stays inside page */
                box-shadow: none !important;
                margin: 0 !important;
                page-break-after: avoid !important; 
                page-break-inside: avoid !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
        }

        .btn-print {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #1e293b;
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 100;
        }
    </style>
</head>
<body>
    <div id="loading" class="loading-indicator no-print">Loading...</div>

    <a href="javascript:void(0)" onclick="window.print()" class="btn-print no-print">🖨️ Print Student Card</a>

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
                <div id="qr-target"></div>
            </div>
            

        </div>
        
        <!-- Right half is empty as requested -->
    </div>

    <script>
        // Hide loading indicator after page loads
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('loading').style.display = 'none';
            }, 500);
        });

        // Error handler for QR library
        function handleQRError() {
            console.error('QR Code library failed to load');
            document.getElementById('qr-target').innerHTML = '<p style="color: red;">QR Code unavailable</p>';
            document.getElementById('loading').style.display = 'none';
        }

        // Timeout for QR code generation
        var qrTimeout = setTimeout(function() {
            console.error('QR Code generation timeout');
            if (document.getElementById('qr-target').innerHTML === '') {
                document.getElementById('qr-target').innerHTML = '<p style="color: #64748b; font-size: 12px;">QR Code loading...</p>';
            }
            document.getElementById('loading').style.display = 'none';
        }, 5000);

        // Generate QR code with error handling
        try {
            if (typeof QRCode !== 'undefined') {
                new QRCode(document.getElementById("qr-target"), {
                    text: "<?= $student_id ?>",
                    width: 180,
                    height: 180,
                    colorDark : "#000000",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
                clearTimeout(qrTimeout);
                document.getElementById('loading').style.display = 'none';
            } else {
                throw new Error('QRCode library not loaded');
            }
        } catch(e) {
            console.error('QR Code generation error:', e);
            document.getElementById('qr-target').innerHTML = '<p style="color: #64748b; font-size: 12px;">QR: <?= $student_id ?></p>';
            clearTimeout(qrTimeout);
            document.getElementById('loading').style.display = 'none';
        }
    </script>
</body>
</html>