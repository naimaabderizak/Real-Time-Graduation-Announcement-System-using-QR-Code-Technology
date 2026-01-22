<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// Add Faculty
if (isset($_POST['add_faculty'])) {
    $name = $_POST['faculty_name'];
    $color = $_POST['faculty_color'];
    $batch = $_POST['batch_name'];
    try {
        $stmt = $pdo->prepare("INSERT INTO faculties (faculty_name, faculty_color, batch_name) VALUES (?, ?, ?)");
        $stmt->execute([$name, $color, $batch]);
        $message = "Faculty added successfully!";
    } catch (PDOException $e) { $message = "Error: " . $e->getMessage(); }
}

// Update Faculty
if (isset($_POST['edit_faculty'])) {
    $id = $_POST['id'];
    $name = $_POST['faculty_name'];
    $color = $_POST['faculty_color'];
    $batch = $_POST['batch_name'];
    try {
        $stmt = $pdo->prepare("UPDATE faculties SET faculty_name=?, faculty_color=?, batch_name=? WHERE id=?");
        $stmt->execute([$name, $color, $batch, $id]);
        $message = "Faculty updated successfully!";
    } catch (PDOException $e) { $message = "Error: " . $e->getMessage(); }
}

// Delete Faculty
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM faculties WHERE id=?")->execute([$id]);
    $message = "Faculty deleted successfully!";
}

$faculties = $pdo->query("SELECT * FROM faculties ORDER BY faculty_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Management - SGS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --primary-bg: #0f172a; --sidebar-bg: #1e293b; --card-bg: #1e293b; --accent-color: #4f46e5; --text-main: #f8fafc; --text-dim: #94a3b8; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--primary-bg); color: var(--text-main); display: flex; }
        .sidebar { width: 280px; height: 100vh; background: var(--sidebar-bg); padding: 2rem; position: fixed; }
        .main-content { margin-left: 280px; padding: 3rem; width: 100%; }
        .nav-link { color: var(--text-dim); padding: 1rem; border-radius: 12px; display: flex; align-items: center; gap: 12px; text-decoration: none; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--accent-color); color: #fff; }
        .card { background: var(--card-bg); border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); }
        .table { color: #fff; }
        .color-preview { width: 30px; height: 30px; border-radius: 6px; border: 2px solid rgba(255,255,255,0.2); }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2 class="mb-5" style="color: var(--accent-color); font-weight: 700;">SGS Admin</h2>
        <nav>
            <a href="dashboard.php" class="nav-link"><i data-lucide="layout-grid"></i> Dashboard</a>
            <a href="students.php" class="nav-link"><i data-lucide="users"></i> Students</a>
            <a href="faculties.php" class="nav-link active"><i data-lucide="layers"></i> Faculties</a>
            <a href="settings.php" class="nav-link"><i data-lucide="settings"></i> Settings</a>
            <a href="../display/top3_reveal.php" class="nav-link" target="_blank"><i data-lucide="award"></i> Top 3 Awards Reveal</a>
            <a href="logout.php" class="nav-link mt-5" style="color: #f87171;"><i data-lucide="log-out"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h1>Faculty Management & Colors</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                <i data-lucide="plus"></i> Add Faculty
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>

        <div class="card p-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>Faculty Name</th>
                        <th>Batch</th>
                        <th>Color (Hex)</th>
                        <th>Preview</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($faculties as $f): ?>
                    <tr>
                        <td><?= $f['faculty_name'] ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($f['batch_name'] ?? 'N/A') ?></span></td>
                        <td><code><?= $f['faculty_color'] ?></code></td>
                        <td><div class="color-preview" style="background: <?= $f['faculty_color'] ?>"></div></td>
                        <td>
                            <button class="btn btn-sm btn-outline-warning edit-btn" 
                                    data-bs-toggle="modal" data-bs-target="#editFacultyModal"
                                    data-id="<?= $f['id'] ?>" data-name="<?= $f['faculty_name'] ?>" 
                                    data-color="<?= $f['faculty_color'] ?>"
                                    data-batch="<?= htmlspecialchars($f['batch_name'] ?? '') ?>">
                                <i data-lucide="edit"></i>
                            </button>
                            <a href="?delete=<?= $f['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                <i data-lucide="trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addFacultyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-white">
                <form method="POST">
                    <div class="modal-body">
                        <label>Faculty Name</label>
                        <input type="text" name="faculty_name" class="form-control mb-3" required>
                        <label>Batch Name (e.g. Batch 19)</label>
                        <input type="text" name="batch_name" class="form-control mb-3" placeholder="e.g. Batch 19">
                        <label>Color</label>
                        <input type="color" name="faculty_color" class="form-control form-control-color w-100" value="#4f46e5">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add_faculty" class="btn btn-primary w-100">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editFacultyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-white">
                <form method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <label>Faculty Name</label>
                        <input type="text" name="faculty_name" id="edit_name" class="form-control mb-3" required>
                        <label>Batch Name</label>
                        <input type="text" name="batch_name" id="edit_batch" class="form-control mb-3">
                        <label>Color</label>
                        <input type="color" name="faculty_color" id="edit_color" class="form-control form-control-color w-100">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="edit_faculty" class="btn btn-warning w-100">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_name').value = btn.dataset.name;
                document.getElementById('edit_color').value = btn.dataset.color;
                document.getElementById('edit_batch').value = btn.dataset.batch;
            });
        });
    </script>
</body>
</html>
