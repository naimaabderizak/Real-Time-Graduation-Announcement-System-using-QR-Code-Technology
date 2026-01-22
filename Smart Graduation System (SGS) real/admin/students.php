<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// Handle Student Addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $student_id = $_POST['student_id'];
    $full_name = $_POST['full_name'];
    $phonetic_name = $_POST['phonetic_name'];
    $faculty = $_POST['faculty'];
    $department = $_POST['department'];
    $student_rank = !empty($_POST['student_rank']) ? intval($_POST['student_rank']) : null;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO students (student_id, full_name, phonetic_name, faculty, department, student_rank) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $full_name, $phonetic_name, $faculty, $department, $student_rank]);
        $message = "Student added successfully!";
    } catch (PDOException $e) {
        $message = "Error occurred: " . $e->getMessage();
    }
}

// Handle Student Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_student'])) {
    $id = $_POST['id'];
    $student_id = $_POST['student_id'];
    $full_name = $_POST['full_name'];
    $phonetic_name = $_POST['phonetic_name'];
    $faculty = $_POST['faculty'];
    $department = $_POST['department'];
    $student_rank = !empty($_POST['student_rank']) ? intval($_POST['student_rank']) : null;
    
    // ENFORCE UNIQUE RANK LOGIC
    if ($student_rank) {
        $clear_stmt = $pdo->prepare("UPDATE students SET student_rank = NULL WHERE faculty = ? AND student_rank = ? AND id != ?");
        $clear_stmt->execute([$faculty, $student_rank, $id]);
    }

    try {
        $stmt = $pdo->prepare("UPDATE students SET student_id=?, full_name=?, phonetic_name=?, faculty=?, department=?, student_rank=? WHERE id=?");
        $stmt->execute([$student_id, $full_name, $phonetic_name, $faculty, $department, $student_rank, $id]);
        $message = "Student data updated successfully!";
    } catch (PDOException $e) {
        $message = "Error occurred: " . $e->getMessage();
    }
}

// Handle Filtering & Search
$faculty_filter = isset($_GET['f']) ? $_GET['f'] : 'All';
$search_query = isset($_GET['s']) ? trim($_GET['s']) : '';

// Build Query
$query = "SELECT * FROM students WHERE 1=1";
$params = [];

if ($faculty_filter !== 'All') {
    $query .= " AND faculty = ?";
    $params[] = $faculty_filter;
}

if (!empty($search_query)) {
    $query .= " AND (full_name LIKE ? OR student_id LIKE ? OR department LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

$query .= " ORDER BY full_name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Fetch all faculties for dropdowns and tabs
$faculties_list = $pdo->query("SELECT faculty_name FROM faculties ORDER BY faculty_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - SGS</title>
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
        }

        .table {
            color: var(--text-main);
        }

        .table thead th {
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: var(--text-dim);
            font-weight: 600;
        }

        .table td {
            vertical-align: middle;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding: 1rem;
        }

        .student-img {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            object-fit: cover;
        }

        .form-control {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            border-radius: 10px;
        }

        .form-control:focus {
            background: rgba(255,255,255,0.1);
            border-color: var(--accent-color);
            color: #fff;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5) !important;
            opacity: 1;
        }

        .btn-add {
            background: linear-gradient(to right, #4f46e5, #7c3aed);
            border: none;
            border-radius: 10px;
            padding: 0.6rem 2rem;
            font-weight: 600;
        }

        /* Faculty Tabs Styling */
        .faculty-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 2rem;
            flex-wrap: wrap; /* Allow wrapping on small screens */
            justify-content: space-between; /* Distribute evenly */
        }

        .faculty-tab {
            padding: 0.6rem 1.5rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 30px;
            color: var(--text-dim);
            text-decoration: none;
            font-weight: 600;
            white-space: nowrap;
            transition: all 0.3s;
            flex: 1; /* Make all tabs equal width/fill space */
            text-align: center; /* Center text */
            min-width: 120px; /* Minimum width for readability */
        }

        .faculty-tab:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }

        .faculty-tab.active {
            background: var(--accent-color);
            color: #fff;
            border-color: var(--accent-color);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">
            <h2 class="mb-5" style="background: linear-gradient(to right, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 700;">SGS Admin</h2>
        </div>
        <nav>
            <a href="dashboard.php" class="nav-link">
                <i data-lucide="layout-grid"></i> Dashboard
            </a>
            <a href="students.php" class="nav-link active">
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
            <a href="../display/top3_reveal.php" class="nav-link" target="_blank"><i data-lucide="award"></i> Top 3 Awards Reveal</a>
            <a href="logout.php" class="nav-link mt-auto" style="color: #f87171;">
                <i data-lucide="log-out"></i> Logout
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h1 class="m-0">Student List</h1>
            
            <div class="d-flex align-items-center gap-2">
                <!-- Search Form -->
                <form action="" method="GET" class="d-flex gap-2 me-3">
                    <?php if($faculty_filter != 'All'): ?>
                        <input type="hidden" name="f" value="<?= htmlspecialchars($faculty_filter) ?>">
                    <?php endif; ?>
                    <div class="position-relative">
                        <input type="text" name="s" class="form-control ps-5" placeholder="Search name, ID or Dept..." value="<?= htmlspecialchars($search_query) ?>" style="width: 280px; height: 45px; border-radius: 12px;">
                        <i data-lucide="search" class="position-absolute top-50 start-0 translate-middle-y ms-3 text-dim" style="width: 18px; height: 18px;"></i>
                    </div>
                </form>

                <a href="import_students.php" class="btn btn-dark">
                    <span>📂</span> Import CSV
                </a>
                <a href="print_qrcodes.php?faculty=<?= urlencode($faculty_filter) ?>" class="btn btn-warning" target="_blank">
                    <i data-lucide="printer"></i> Print All QRs
                </a>
                <a href="print_all_a5_cards.php?faculty=<?= urlencode($faculty_filter) ?>" class="btn btn-info" target="_blank" style="color: white; font-weight: 600;">
                    <i data-lucide="id-card"></i> Print All A5 Cards
                </a>
                <a href="print_all_a5_cards_no_qr.php?faculty=<?= urlencode($faculty_filter) ?>" class="btn btn-secondary" target="_blank" style="color: white; font-weight: 600;">
                    <i data-lucide="printer"></i> Print 8-per-A4 (No QR)
                </a>
                <button class="btn btn-primary btn-add" data-bs-toggle="modal" data-bs-target="#addStudentModal" onclick="new bootstrap.Modal(document.getElementById('addStudentModal')).show()">
                    <span>➕</span> Add Student
                </button>
            </div>
        </div>

        <!-- Faculty Tabs -->
        <div class="faculty-tabs">
            <a href="?f=All<?= !empty($search_query) ? '&s='.urlencode($search_query) : '' ?>" class="faculty-tab <?php echo ($faculty_filter == 'All') ? 'active' : '' ?>">All</a>
            <?php foreach ($faculties_list as $f): ?>
                <a href="?f=<?= urlencode($f['faculty_name']) ?><?= !empty($search_query) ? '&s='.urlencode($search_query) : '' ?>" class="faculty-tab <?= ($faculty_filter == $f['faculty_name']) ? 'active' : '' ?>">
                    <?= $f['faculty_name'] ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show mb-4" role="alert" style="background: rgba(79, 70, 229, 0.1); border: 1px solid var(--accent-color); color: #fff;">
                <?php echo $message; ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card p-4">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>TTS Pronunciation</th>
                            <th>Faculty</th>
                            <th>Rank</th>
                            <th>Department</th>
                            <th>Scanned</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><code><?php echo $student['student_id']; ?></code></td>
                            <td><?php echo $student['full_name']; ?></td>
                            <td><small class="text-info"><?php echo $student['phonetic_name'] ?: '-'; ?></small></td>
                            <td><?php echo $student['faculty']; ?></td>
                            <td>
                                <?php if ($student['student_rank']): ?>
                                    <span class="badge bg-warning text-dark"><?php echo $student['student_rank']; ?></span>
                                <?php else: ?>
                                    <span class="text-dim">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $student['department']; ?></td>
                            <td>
                                <?php if ($student['is_scanned']): ?>
                                    <span class="badge bg-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="print_a5_card.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-outline-primary" title="Print A5 Card" target="_blank">
                                        <i data-lucide="printer"></i>
                                    </a>
                                    <a href="qr_generator.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-outline-info" title="Generate QR">
                                        <i data-lucide="qr-code"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-warning edit-btn" 
                                            data-id="<?php echo $student['id']; ?>"
                                            data-sid="<?php echo $student['student_id']; ?>"
                                            data-name="<?php echo $student['full_name']; ?>"
                                            data-phonetic="<?php echo $student['phonetic_name']; ?>"
                                            data-faculty="<?php echo $student['faculty']; ?>"
                                            data-rank="<?php echo $student['student_rank']; ?>"
                                            data-dept="<?php echo $student['department']; ?>">
                                        ✏️
                                    </button>
                                    <a href="delete_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this student?')">
                                        🗑️
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-dim">No students registered yet.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background-color: var(--card-bg); border: 1px solid rgba(255,255,255,0.1);">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title">Register New Student</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Student ID</label>
                            <input type="text" name="student_id" class="form-control" placeholder="e.g. 2024-001" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pronunciation (Spelling for Voice)</label>
                            <input type="text" name="phonetic_name" class="form-control" placeholder="e.g. Fa-haad (Optional)">
                            <small class="text-dim">Write how it sounds if the computer mispronounces the name.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Faculty</label>
                            <select name="faculty" class="form-control" required>
                                <option value="">Select Faculty...</option>
                                <?php foreach ($faculties_list as $f): ?>
                                    <option value="<?= $f['faculty_name'] ?>"><?= $f['faculty_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control" placeholder="e.g. Computer Science">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rank (Top Students)</label>
                            <select name="student_rank" class="form-control">
                                <option value="">No Rank</option>
                                <option value="1">1st Rank</option>
                                <option value="2">2nd Rank</option>
                                <option value="3">3rd Rank</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="submit" name="add_student" class="btn btn-primary btn-add w-100">Save Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background-color: var(--card-bg); border: 1px solid rgba(255,255,255,0.1);">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title">Edit Student</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Student ID</label>
                            <input type="text" name="student_id" id="edit_student_id" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pronunciation (Spelling for Voice)</label>
                            <input type="text" name="phonetic_name" id="edit_phonetic_name" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Faculty</label>
                            <select name="faculty" id="edit_faculty" class="form-control" required>
                                <?php foreach ($faculties_list as $f): ?>
                                    <option value="<?= $f['faculty_name'] ?>"><?= $f['faculty_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" id="edit_department" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rank (Top Students)</label>
                            <select name="student_rank" id="edit_student_rank" class="form-control">
                                <option value="">No Rank</option>
                                <option value="1">1st Rank</option>
                                <option value="2">2nd Rank</option>
                                <option value="3">3rd Rank</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="submit" name="edit_student" class="btn btn-primary btn-add w-100">Update Student Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fallback: If Lucide fails, icons are already emojis/text. If it works, great.
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Populate Edit Modal
        const editButtons = document.querySelectorAll('.edit-btn');
        editButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                // Populate fields
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_student_id').value = btn.dataset.sid;
                document.getElementById('edit_full_name').value = btn.dataset.name;
                document.getElementById('edit_phonetic_name').value = btn.dataset.phonetic;
                document.getElementById('edit_faculty').value = btn.dataset.faculty;
                document.getElementById('edit_student_rank').value = btn.dataset.rank;
                document.getElementById('edit_department').value = btn.dataset.dept;

                // Open Modal manually if Bootstrap didn't catch the click
                const modalEl = document.getElementById('editStudentModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            });
        });
    </script>
</body>
</html>
