<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$error = "";
$success_count = 0;
$update_count = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    
    if ($_FILES['csv_file']['size'] > 0) {
        $handle = fopen($file, "r");
        
        // Skip Header row
        $headers = fgetcsv($handle, 1000, ",");
        
        // Prepare Statement
        $sql = "INSERT INTO students (student_id, full_name, phonetic_name, faculty, department) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                full_name = VALUES(full_name),
                phonetic_name = VALUES(phonetic_name),
                faculty = VALUES(faculty),
                department = VALUES(department)";
        
        $stmt = $pdo->prepare($sql);
        
        try {
            $pdo->beginTransaction();
            
            // Prepare faculty check/insert to keep master list in sync
            $fac_check = $pdo->prepare("SELECT id FROM faculties WHERE faculty_name = ?");
            $fac_insert = $pdo->prepare("INSERT IGNORE INTO faculties (faculty_name, faculty_color) VALUES (?, '#4f46e5')");

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) < 2) continue; // Skip empty rows
                
                $student_id = trim($data[0]);
                $full_name = trim($data[1]);
                $phonetic_name = isset($data[2]) ? trim($data[2]) : $full_name;
                $faculty = isset($data[3]) ? trim($data[3]) : 'General';
                $department = isset($data[4]) ? trim($data[4]) : '';
                
                if (empty($student_id) || empty($full_name)) continue;

                // Sync with faculties table
                $fac_insert->execute([$faculty]);
                
                $stmt->execute([$student_id, $full_name, $phonetic_name, $faculty, $department]);
                $success_count++;
            }
            $pdo->commit();
            $message = "Import complete! $success_count students processed successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error during import: " . $e->getMessage();
        }
        
        fclose($handle);
    } else {
        $error = "Please upload a valid CSV file.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Students - SGS</title>
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
        body { font-family: 'Outfit', sans-serif; background-color: var(--primary-bg); color: var(--text-main); margin: 0; display: flex; }
        .sidebar { width: 280px; height: 100vh; background-color: var(--sidebar-bg); padding: 2rem; position: fixed; border-right: 1px solid rgba(255, 255, 255, 0.05); }
        .main-content { margin-left: 280px; padding: 3rem; width: 100%; }
        .card { background-color: var(--card-bg); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; padding: 2.5rem; }
        .card h4, .card h5 { color: #ffffff !important; font-weight: 700; }
        .text-dim { color: #cbd5e1 !important; }
        .btn-primary { background-color: var(--accent-color); border: none; padding: 0.8rem 2rem; border-radius: 12px; font-weight: 600; }
        .nav-link { color: var(--text-dim); padding: 1rem; border-radius: 12px; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 12px; transition: all 0.3s; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background-color: var(--accent-color); color: #fff; }
        code { background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; color: #818cf8; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <h2 class="mb-5" style="background: linear-gradient(to right, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 700;">SGS Admin</h2>
        </div>
        <nav>
            <a href="dashboard.php" class="nav-link"><i data-lucide="layout-grid"></i> Dashboard</a>
            <a href="students.php" class="nav-link active"><i data-lucide="users"></i> Students</a>
            <a href="faculties.php" class="nav-link"><i data-lucide="layers"></i> Faculties</a>
            <a href="reports.php" class="nav-link"><i data-lucide="file-text"></i> Reports</a>
            <a href="settings.php" class="nav-link"><i data-lucide="settings"></i> Settings</a>
            <a href="../display/top3_reveal.php" class="nav-link" target="_blank"><i data-lucide="award"></i> Top 3 Awards Reveal</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h1 class="fw-bold">Bulk Import Students</h1>
                    <p class="text-dim">Upload students from an Excel/CSV file.</p>
                </div>
                <a href="students.php" class="btn btn-outline-light rounded-pill px-4">
                    <i data-lucide="arrow-left" class="me-2"></i> Back to List
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success border-0 rounded-4 p-3 mb-4">
                    <i data-lucide="check-circle" class="me-2"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 rounded-4 p-3 mb-4">
                    <i data-lucide="alert-circle" class="me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-7">
                    <div class="card shadow-sm">
                        <h4 class="mb-4">1. Download Template</h4>
                        <p class="text-dim">First, download the sample CSV file to see the required data format.</p>
                        <a href="students_sample.csv" class="btn btn-dark rounded-pill px-4 py-2 mb-4 d-inline-flex align-items-center">
                            <i data-lucide="download" class="me-2"></i> Download Sample CSV
                        </a>

                        <hr class="border-secondary opacity-25 my-4">

                        <h4 class="mb-4">2. Upload Student Data</h4>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label class="form-label text-dim">Select CSV File:</label>
                                <input type="file" name="csv_file" class="form-control bg-dark border-secondary text-white p-3" accept=".csv" required>
                                <small class="mt-2 d-block" style="color: #fbbf24; font-weight: 600;">Note: Only files ending in <code>.csv</code> are accepted.</small>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">
                                <i data-lucide="upload-cloud" class="me-2"></i> Start Importing Now
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card bg-opacity-10 border-indigo">
                        <h5 class="fw-bold mb-3"><i data-lucide="info" class="me-2 text-indigo"></i> Filling Guide:</h5>
                        <ul class="text-dim small ps-3">
                            <li class="mb-2"><strong>Student ID:</strong> Must be unique for each student.</li>
                            <li class="mb-2"><strong>Full Name:</strong> Complete name of the student.</li>
                            <li class="mb-2"><strong>Phonetic Name (Optional):</strong> How the name is pronounced. If left empty, Full Name is used.</li>
                            <li class="mb-2"><strong>Faculty:</strong> Student's faculty/college.</li>
                            <li class="mb-2"><strong>Department (Optional):</strong> Specific department within the faculty.</li>
                        </ul>
                        
                        <div class="mt-4">
                            <h6 class="text-white small fw-bold mb-2">Excel Example (Skipping Optional Fields):</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered border-secondary text-dim mb-0" style="font-size: 11px;">
                                    <thead class="bg-dark">
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Full Name</th>
                                            <th>Phonetic...</th>
                                            <th>Faculty</th>
                                            <th>Dept...</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>CS012001</td>
                                            <td>Ahmed Ali</td>
                                            <td>[Leave Empty]</td>
                                            <td>Computing</td>
                                            <td>[Leave Empty]</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="alert alert-warning bg-warning bg-opacity-10 border-warning border-opacity-25 text-warning small mt-4 rounded-4">
                            <strong>Duplicate Check:</strong> If a student ID already exists, the system will update the existing record with new data.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
