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

// Check if student exists
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student not found!");
}

$qr_data = $student_id;
$size_mode = isset($_GET['size']) ? $_GET['size'] : 'big'; // default to big for individual cards

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
    <title>QR Code - <?php echo $student_id; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Kjua JS for Offline-friendly QR generation -->
    <script src="https://cdn.jsdelivr.net/npm/kjua@0.9.0/dist/kjua.min.js"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0f172a;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .qr-card {
            background: #1e293b;
            padding: 3rem;
            border-radius: 24px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            max-width: 400px;
            width: 90%;
        }
        #qr-container {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: inline-block;
            line-height: 0;
        }
        #qr-container canvas, #qr-container img {
            max-width: 100%;
            height: auto !important;
        }
        .student-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-transform: capitalize;
        }
        .student-id {
            color: #94a3b8;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        .btn-print {
            background: #4f46e5;
            color: #fff;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
        }
        @media print {
            body { background: #fff !important; color: #000 !important; }
            .qr-card { 
                display: block !important;
                max-width: 500px;
            }
            .btn-print, .back-link, .no-print { display: none !important; }
            .print-header { display: flex !important; justify-content: space-between; align-items: center; border-bottom: 2px solid #1e293b; padding-bottom: 15px; margin-bottom: 20px; width: 100%; }
            .print-header-left { display: flex; align-items: center; gap: 15px; text-align: left; }
            .print-header-logo { height: 60px; }
            .print-title { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
            .print-subtitle { font-size: 14px; color: #64748b; margin: 0; }

            /* Size Overrides */
            body[data-size="small"] .qr-card { 
                max-width: 300px; 
                padding: 1.5rem; 
                border-radius: 12px;
            }
            body[data-size="small"] .student-name { font-size: 1.1rem; }
            body[data-size="small"] #qr-container { padding: 10px; margin-bottom: 1rem; }
        }
        .print-header { display: none; }
    </style>
</head>
<body data-size="<?= htmlspecialchars($size_mode) ?>">


    <div class="qr-card">
        <!-- Print Only Header -->
        <div class="print-header">
            <div class="print-header-left">
                <img src="../<?= $settings['primary_logo'] ?? 'assets/images/benadir_logo.jpg' ?>" class="print-header-logo">
                <div>
                    <div class="print-title"><?= $settings['uni_name'] ?? 'Benadir University' ?></div>
                    <div class="print-subtitle"><?= $settings['uni_motto'] ?? 'Cultivating Human Talents' ?></div>
                </div>
            </div>
            <div class="text-end">
                <div class="fw-bold">STUDENT QR CARD</div>
                <div class="text-muted small"><?= date('F d, Y') ?></div>
            </div>
        </div>

        <h2 class="mb-4 no-print">QR Code Card</h2>
        
        <div id="qr-container"></div>
        
        <div class="student-name"><?php echo $student['full_name']; ?></div>
        <div class="student-id">ID: <?php echo $student['student_id']; ?></div>
        <div class="st-faculty mb-3"><?php echo $student['faculty']; ?></div>
        
        <div class="mb-3 d-flex align-items-center bg-dark p-1 rounded border border-secondary no-print">
            <span class="small fw-bold px-2 text-muted">Layout:</span>
            <a href="?id=<?= urlencode($student_id) ?>&size=small" class="btn btn-sm <?= $size_mode == 'small' ? 'btn-light' : 'btn-outline-light' ?> border-0">Small</a>
            <a href="?id=<?= urlencode($student_id) ?>&size=big" class="btn btn-sm <?= $size_mode == 'big' ? 'btn-light' : 'btn-outline-light' ?> border-0">Big</a>
        </div>

        <button onclick="window.print()" class="btn-print">Print QR Code</button>
        
        <div class="mt-3 back-link">
            <a href="students.php" style="color: #94a3b8; text-decoration: none;">← Ku noqo liiska</a>
        </div>
    </div>

    <script>
        const qrContent = "<?php echo $qr_data; ?>";
        const el = kjua({
            render: 'image',
            size: <?= $size_mode == 'small' ? '200' : '300' ?>,
            text: qrContent,
            fill: '#000',
            back: '#fff',
            rounded: 10,
            quiet: 1
        });
        document.getElementById('qr-container').appendChild(el);
    </script>
</body>
</html>
