<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch stats
$total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$scanned_students = $pdo->query("SELECT COUNT(*) FROM students WHERE is_scanned = 1")->fetchColumn();
$pending_students = $total_students - $scanned_students;

$scanned_percentage = ($total_students > 0) ? round(($scanned_students / $total_students) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGS Dashboard</title>
    <!-- Google Fonts: Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Lucide Icons -->
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

        /* Sidebar */
        .sidebar {
            width: 280px;
            height: 100vh;
            background-color: var(--sidebar-bg);
            padding: 2rem;
            position: fixed;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-brand h2 {
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(to right, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 3rem;
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

        .nav-link i {
            width: 20px;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 3rem;
            width: 100%;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }

        .welcome-text h1 {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .welcome-text p {
            color: var(--text-dim);
        }

        /* Stats Cards */
        .stat-card {
            background-color: var(--card-bg);
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-title {
            color: var(--text-dim);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            display: block;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .stat-icon {
            float: right;
            background: rgba(79, 70, 229, 0.1);
            color: var(--accent-color);
            padding: 1rem;
            border-radius: 16px;
        }

        /* System Health */
        .health-card {
            background: rgba(16, 185, 129, 0.05);
            border: 1px solid rgba(16, 185, 129, 0.1);
            padding: 1.5rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .health-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pulse {
            width: 12px;
            height: 12px;
            background-color: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">
            <h2>SGS Admin</h2>
        </div>
        <nav>
            <a href="dashboard.php" class="nav-link active">
                <i data-lucide="layout-grid"></i> Dashboard
            </a>
            <a href="students.php" class="nav-link">
                <i data-lucide="users"></i> Students
            </a>
            <a href="faculties.php" class="nav-link">
                <i data-lucide="layers"></i> Faculties
            </a>
            <a href="reports.php" class="nav-link">
                <i data-lucide="file-text"></i> Reports
            </a>
            <a href="settings.php" class="nav-link">
                <i data-lucide="settings"></i> Settings
            </a>
            <a href="../scanner/index.php" class="nav-link" target="_blank">
                <i data-lucide="qr-code"></i> Scanner Interface
            </a>
            <a href="../display/index.php" class="nav-link" target="_blank">
                <i data-lucide="monitor"></i> Grand Screen
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
        <div class="header">
            <div class="welcome-text">
                <h1>Welcome, <?php echo $_SESSION['username']; ?>!</h1>
                <p>The Smart Graduation System is ready.</p>
            </div>
            <div class="user-profile">
                <span class="badge bg-primary p-2 px-3">Admin Panel</span>
            </div>
        </div>

        <div class="health-card">
            <div class="health-indicator">
                <div class="pulse" id="heartbeat-pulse"></div>
                <div>
                    <h6 class="mb-0">Real-time Server</h6>
                    <small class="text-dim" id="server-status-text">Status: Checking...</small>
                </div>
            </div>
            <div class="server-ip">
                <small class="text-dim">Server IP: <?php echo $_SERVER['SERVER_ADDR']; ?></small>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stat-card">
                    <i data-lucide="users" class="stat-icon"></i>
                    <span class="stat-title">Total Students</span>
                    <div class="stat-value"><?php echo $total_students; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <i data-lucide="check-circle" class="stat-icon" style="color: #10b981; background: rgba(16, 185, 129, 0.1);"></i>
                    <span class="stat-title">Scanned</span>
                    <div class="stat-value"><?php echo $scanned_students; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <i data-lucide="clock" class="stat-icon" style="color: #f59e0b; background: rgba(245, 158, 11, 0.1);"></i>
                    <span class="stat-title">Remaining</span>
                    <div class="stat-value"><?php echo $pending_students; ?></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="stat-card">
                    <h5 class="mb-4">Live Progress</h5>
                    <div class="progress" style="height: 30px; border-radius: 15px; background: rgba(255,255,255,0.05);">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                             style="width: <?php echo $scanned_percentage; ?>%; background: linear-gradient(to right, #4f46e5, #7c3aed);" 
                             aria-valuenow="<?php echo $scanned_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                             <?php echo $scanned_percentage; ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Heartbeat Check for Python Server
        async function checkHeartbeat() {
            const pulse = document.getElementById('heartbeat-pulse');
            const statusText = document.getElementById('server-status-text');
            const serverIp = window.location.hostname;
            const pythonServerUrl = `http://${serverIp}:5001`;

            try {
                const res = await fetch(`${pythonServerUrl}/`);
                if (res.ok) {
                    statusText.innerHTML = "Status: Online";
                    statusText.className = "text-dim";
                    pulse.style.backgroundColor = "#10b981";
                    pulse.style.boxShadow = "0 0 0 0 rgba(16, 185, 129, 0.7)";
                } else {
                    throw new Error();
                }
            } catch (e) {
                statusText.innerHTML = "Status: Offline";
                statusText.className = "text-danger";
                pulse.style.backgroundColor = "#ef4444";
                pulse.style.boxShadow = "none";
            }
        }

        setInterval(checkHeartbeat, 5000);
        checkHeartbeat();
    </script>
</body>
</html>
