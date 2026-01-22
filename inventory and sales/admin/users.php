<?php
/**
 * admin/users.php
 * 
 * User Management Dashboard
 * 
 * Lists all system users (Staff and Admins).
 * Allows Admins to add new users or delete existing ones.
 * Users cannot delete their own account.
 * 
 * Author: System
 * Date: 2026-01-05
 */

session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=Access Denied. You need Admin privileges.");
    exit;
}

// Handle Delete
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    if ($id != $_SESSION['user_id']) { // Prevent self-delete
        try {
            $pdo->beginTransaction();

            // Set created_by to NULL for related records instead of deleting them
            // This preserves the history but removes the link to the deleted user
            
            // 1. Purchases
            $stmt = $pdo->prepare("UPDATE purchases SET created_by = NULL WHERE created_by = ?");
            $stmt->execute([$id]);

            // 2. Sales
            $stmt = $pdo->prepare("UPDATE sales SET created_by = NULL WHERE created_by = ?");
            $stmt->execute([$id]);

            // 3. Stock Movements
            $stmt = $pdo->prepare("UPDATE stock_movements SET created_by = NULL WHERE created_by = ?");
            $stmt->execute([$id]);

            // 4. Delete User
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);

            $pdo->commit();
            $success = "User deleted successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error deleting user: " . $e->getMessage();
        }
    } else {
        $error = "You cannot delete yourself.";
    }
}

// Fetch users
$stmt = $pdo->query("SELECT * FROM users ORDER BY username ASC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Inventory System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                Inventory Sys
            </div>
            <ul class="sidebar-menu">
                <li><a href="../index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a></li>
                <li><a href="customers.php"><i class="fas fa-users"></i> Customers</a></li>
                <li><a href="../purchases/index.php"><i class="fas fa-shopping-cart"></i> Purchases</a></li>
                <li><a href="../sales/index.php"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li><a href="../reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="users.php" class="active"><i class="fas fa-user-cog"></i> Users</a></li>
                <li><a href="../team.php"><i class="fas fa-users-cog"></i> Our Team</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1 class="page-title">User Management</h1>
                <a href="user_add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add User</a>
            </div>

            <div class="card">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 1rem; margin-bottom: 1rem; border-radius: 6px;">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" style="background: #fee2e2; color: #b91c1c; padding: 1rem; margin-bottom: 1rem; border-radius: 6px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td>
                                        <span style="padding: 4px 8px; border-radius: 4px; background: <?php echo $user['role'] == 'admin' ? '#e0e7ff' : '#f3f4f6'; ?>; color: <?php echo $user['role'] == 'admin' ? '#3730a3' : '#374151'; ?>;">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                                                <input type="hidden" name="delete_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;"><i class="fas fa-trash"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
