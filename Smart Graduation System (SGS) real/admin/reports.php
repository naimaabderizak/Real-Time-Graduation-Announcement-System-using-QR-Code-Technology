<?php
session_start();
require_once 'db.php';

// Handle Reset Request
if (isset($_POST['reset_scanned'])) {
    try {
        $stmt = $pdo->prepare("UPDATE students SET is_scanned = 0, scanned_at = NULL");
        $stmt->execute();
        $success_msg = "Ceremony has been reset. All students are now 'Remaining'.";
    } catch (PDOException $e) {
        $error_msg = "Error resetting ceremony: " . $e->getMessage();
    }
}

// Handle Individual Student Reset
if (isset($_POST['reset_student_id'])) {
    try {
        $student_id = $_POST['reset_student_id'];
        $stmt = $pdo->prepare("UPDATE students SET is_scanned = 0, scanned_at = NULL WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $success_msg = "Student " . htmlspecialchars($student_id) . " has been reset to 'Remaining'.";
    } catch (PDOException $e) {
        $error_msg = "Error resetting student: " . $e->getMessage();
    }
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get faculty filter
$faculty_filter = isset($_GET['faculty']) ? $_GET['faculty'] : 'All';

// Fetch Detailed Reports with optional faculty filter
if ($faculty_filter && $faculty_filter !== 'All' && trim($faculty_filter) !== '') {
    $scanned_students = $pdo->prepare("SELECT * FROM students WHERE is_scanned = 1 AND faculty = ? ORDER BY scanned_at DESC");
    $scanned_students->execute([$faculty_filter]);
    $scanned_students = $scanned_students->fetchAll();
    
    $pending_students = $pdo->prepare("SELECT * FROM students WHERE is_scanned = 0 AND faculty = ? ORDER BY created_at ASC");
    $pending_students->execute([$faculty_filter]);
    $pending_students = $pending_students->fetchAll();
} else {
    $scanned_students = $pdo->query("SELECT * FROM students WHERE is_scanned = 1 ORDER BY scanned_at DESC")->fetchAll();
    $pending_students = $pdo->query("SELECT * FROM students WHERE is_scanned = 0 ORDER BY created_at ASC")->fetchAll();
}

$scanned_count = count($scanned_students);
$pending_count = count($pending_students);
$total_count = $scanned_count + $pending_count;

// Fetch all faculties for filter
$faculties_list = $pdo->query("SELECT DISTINCT faculty FROM students ORDER BY faculty ASC")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SGS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary-bg: #0f172a;
            --sidebar-bg: #1e293b;
            --card-bg: #1e293b;
            --accent-color: #4f46e5;
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--primary-bg);
            color: var(--text-main);
            margin: 0;
            display: flex;
        }

        .sidebar {
            width: 280px;
            height: 100vh;
            background-color: var(--sidebar-bg);
            padding: 2rem;
            position: fixed;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        .main-content {
            margin-left: 280px;
            padding: 3rem;
            width: 100%;
        }

        .nav-link {
            color: var(--text-dim);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .nav-link:hover, .nav-link.active {
            background-color: var(--accent-color);
            color: #fff;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            color: #fff;
            margin-bottom: 2rem;
        }

        .table { color: #fff; }
        .table thead th { border-bottom: 1px solid rgba(255,255,255,0.1); color: var(--text-dim); }
        .table td { border-bottom: 1px solid rgba(255,255,255,0.05); padding: 1rem; }

        .nav-tabs .nav-link {
            border: none;
            color: var(--text-dim) !important;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            border-radius: 10px;
            margin-right: 0.5rem;
            transition: all 0.3s;
        }

        .nav-tabs .nav-link:hover {
            background: rgba(255,255,255,0.05);
            color: #fff !important;
        }

        .nav-tabs .nav-link.active {
            background-color: var(--accent-color) !important;
            color: #fff !important;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        
        .faculty-filter {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .faculty-filter a {
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            color: var(--text-dim);
            text-decoration: none;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 600;
        }
        
        .faculty-filter a:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        
        .faculty-filter a.active {
            background: var(--accent-color);
            color: #fff;
        }

        @media print {
            .sidebar, .btn-print, .nav-tabs, .faculty-filter, .no-print { display: none !important; }
            .main-content { margin-left: 0; padding: 0; width: 100%; border: none !important; }
            body { background-color: #fff !important; color: #000 !important; }
            .card { border: none !important; box-shadow: none !important; background: transparent !important; color: #000 !important; margin: 0 !important; padding: 0 !important; }
            .table { color: #000 !important; margin-top: 20px; }
            .table thead th { color: #000 !important; border-bottom: 2px solid #000 !important; }
            .table td { border-bottom: 1px solid #ddd !important; padding: 8px !important; }
            h1, h5 { color: #000 !important; }
            .print-header { display: flex !important; justify-content: space-between; align-items: center; border-bottom: 2px solid #1e293b; padding-bottom: 15px; margin-bottom: 20px; }
            .print-header-left { display: flex; align-items: center; gap: 15px; }
            .print-header-logo { height: 60px; }
            .print-title { font-size: 24px; font-weight: 700; color: #1e293b; }
            .print-subtitle { font-size: 14px; color: #64748b; }
            
            /* Filtering Logic for Printing */
            .tab-pane { display: none !important; }
            body.print-scanned-only #scanned { display: block !important; opacity: 1 !important; }
            body.print-pending-only #pending { display: block !important; opacity: 1 !important; }
            body.print-all #scanned, body.print-all #pending { display: block !important; opacity: 1 !important; }

            .nav-tabs, .no-print, .row.mb-4 { display: none !important; }
        }
        .print-header { display: none; }
    </style>
</head>
<body>
    <?php
    $raw_settings = $pdo->query("SELECT * FROM settings")->fetchAll();
    $settings = [];
    foreach ($raw_settings as $s) {
        $settings[$s['setting_key']] = $s['setting_value'];
    }
    ?>

    <div class="sidebar">
        <div class="sidebar-brand">
            <h2 class="mb-5" style="background: linear-gradient(to right, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 700;">SGS Admin</h2>
        </div>
        <nav>
            <a href="dashboard.php" class="nav-link">
                <i data-lucide="layout-grid"></i> Dashboard
            </a>
            <a href="students.php" class="nav-link">
                <i data-lucide="users"></i> Students
            </a>
            <a href="faculties.php" class="nav-link">
                <i data-lucide="layers"></i> Faculties
            </a>
            <a href="reports.php" class="nav-link active">
                <i data-lucide="file-text"></i> Reports
            </a>
            <a href="settings.php" class="nav-link">
                <i data-lucide="settings"></i> Settings
            </a>
            <a href="../scanner/index.php" class="nav-link" target="_blank">
                <i data-lucide="qr-code"></i> Scanner Interface
            </a>
            <a href="../display/top3_reveal.php" class="nav-link" target="_blank">
                <i data-lucide="award"></i> Top 3 Awards Reveal
            </a>
            <a href="logout.php" class="nav-link mt-auto" style="color: #f87171;">
                <i data-lucide="log-out"></i> Logout
            </a>
        </nav>
    </div>

    <div class="main-content">
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
                <div class="fw-bold">CEREMONY REPORT</div>
                <div class="text-muted small"><?= date('F d, Y - h:i A') ?></div>
                <div class="text-primary small"><?= htmlspecialchars($faculty_filter) ?> Faculty</div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <div>
                <h1>Ceremony Reports</h1>
                <p class="text-dim">Overall statistics and scanned students.</p>
                <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success py-2 mt-2"><?= htmlspecialchars($success_msg) ?></div>
                <?php endif; ?>
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger py-2 mt-2"><?= htmlspecialchars($error_msg) ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Faculty Filter -->
        <div class="faculty-filter no-print">
            <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                 <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                    <label class="form-check-label" for="autoRefresh">
                        <i data-lucide="zap" class="text-warning" style="width:16px;height:16px;"></i> Live Updates
                    </label>
                </div>
            </div>
            <a href="reports.php" class="<?= $faculty_filter == 'All' ? 'active' : '' ?>">All Faculties</a>
            <?php foreach ($faculties_list as $fac): ?>
                <a href="reports.php?faculty=<?= urlencode($fac) ?>" class="<?= $faculty_filter == $fac ? 'active' : '' ?>">
                    <?= htmlspecialchars($fac) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card p-4 text-center">
                    <h5 class="text-dim">Total Students</h5>
                    <h2 class="fw-bold"><?php echo $total_count; ?></h2>
                    <div class="d-flex justify-content-center gap-2 mt-2">
                        <button onclick="printFull()" class="btn btn-sm btn-outline-light no-print">
                            <i data-lucide="printer" style="width:14px;height:14px;"></i> Full Report
                        </button>
                        <form method="POST" onsubmit="return confirm('Are you sure? This will reset the status of ALL students to Unscanned (Remaining).');" class="no-print">
                            <button type="submit" name="reset_scanned" class="btn btn-sm btn-danger">
                                <i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i> Reset Ceremony
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 text-center border-success">
                    <h5 class="text-success">Scanned</h5>
                    <h2 class="fw-bold"><?php echo $scanned_count; ?></h2>
                    <button onclick="printScanned()" class="btn btn-sm btn-success mt-2 no-print">
                        <i data-lucide="printer" style="width:14px;height:14px;"></i> Print List
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 text-center border-warning">
                    <h5 class="text-warning">Remaining</h5>
                    <h2 class="fw-bold"><?php echo $pending_count; ?></h2>
                    <button onclick="printPending()" class="btn btn-sm btn-warning mt-2 no-print">
                        <i data-lucide="printer" style="width:14px;height:14px;"></i> Print List
                    </button>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4 border-0" id="reportTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active text-white" id="scanned-tab" data-bs-toggle="tab" data-bs-target="#scanned" type="button">Scanned</button>
            </li>
            <li class="nav-item">
                <button class="nav-link text-white" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button">Remaining</button>
            </li>
        </ul>

        <div class="tab-content" id="reportTabsContent">
            <div class="tab-pane fade show active" id="scanned" role="tabpanel">
                <div class="card p-4">
                    <h5 class="mb-4">Students Who Entered Stage</h5>
                    <div class="table-responsive">
                        <table class="table" id="scannedTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Faculty</th>
                                    <th>Time</th>
                                    <th class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($scanned_students as $s): ?>
                                <tr>
                                    <td><code><?php echo $s['student_id']; ?></code></td>
                                    <td><?php echo $s['full_name']; ?></td>
                                    <td><?php echo $s['faculty']; ?></td>
                                    <td><?php echo date('h:i A', strtotime($s['scanned_at'])); ?></td>
                                    <td class="no-print">
                                        <form method="POST" onsubmit="return confirm('Reset this student?');" style="display:inline;">
                                            <input type="hidden" name="reset_student_id" value="<?php echo $s['student_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0" title="Reset Student">
                                                <i data-lucide="rotate-ccw" style="width:12px;height:12px;"></i> Reset
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($scanned_students)): ?>
                                    <tr><td colspan="5" class="text-center text-muted">No scanned students found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="pending" role="tabpanel">
                <div class="card p-4">
                    <h5 class="mb-4">Awaiting Students</h5>
                    <div class="table-responsive">
                        <table class="table" id="pendingTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Faculty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_students as $p): ?>
                                <tr>
                                    <td><code><?php echo $p['student_id']; ?></code></td>
                                    <td><?php echo $p['full_name']; ?></td>
                                    <td><?php echo $p['faculty']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();
        
        function printScanned() {
            document.body.classList.add('print-scanned-only');
            window.print();
            document.body.classList.remove('print-scanned-only');
        }
        
        function printPending() {
            document.body.classList.add('print-pending-only');
            window.print();
            document.body.classList.remove('print-pending-only');
        }

        function printFull() {
            document.body.classList.add('print-all');
            window.print();
            document.body.classList.remove('print-all');
        }

        // Auto Refresh Logic
        let refreshInterval;
        const autoRefreshToggle = document.getElementById('autoRefresh');
        
        function startAutoRefresh() {
            sessionStorage.setItem('scrollPos', window.scrollY);
            const activeTab = document.querySelector('.nav-link.active') ? document.querySelector('.nav-link.active').id : 'scanned-tab';
            sessionStorage.setItem('activeTab', activeTab);
            
            refreshInterval = setInterval(() => {
                window.location.reload();
            }, 3000); 
        }

        function stopAutoRefresh() {
            clearInterval(refreshInterval);
        }

        if(autoRefreshToggle) {
            autoRefreshToggle.addEventListener('change', (e) => {
                if (e.target.checked) {
                    startAutoRefresh();
                    localStorage.setItem('autoRefresh', 'true');
                } else {
                    stopAutoRefresh();
                    localStorage.setItem('autoRefresh', 'false');
                }
            });
        }

        // Initialize state
        window.addEventListener('load', () => {
             const scrollPos = sessionStorage.getItem('scrollPos');
             if (scrollPos) window.scrollTo(0, scrollPos);

             const activeTab = sessionStorage.getItem('activeTab');
             if (activeTab) {
                 const tabEl = document.getElementById(activeTab);
                 if(tabEl) {
                     const tab = new bootstrap.Tab(tabEl);
                     tab.show();
                 }
             }

             const savedState = localStorage.getItem('autoRefresh');
             if (savedState === 'false') {
                 if(autoRefreshToggle) autoRefreshToggle.checked = false;
             } else {
                 if(autoRefreshToggle) autoRefreshToggle.checked = true;
                 startAutoRefresh();
             }
        });
    </script>

