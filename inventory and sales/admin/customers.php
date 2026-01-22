<?php
/**
 * admin/customers.php
 * 
 * Customer Management Dashboard
 * 
 * Lists all registered customers in the system. 
 * Allows admins to view details and navigate to add/edit/delete functions.
 * Staff users can only view the list.
 * 
 * Author: System
 * Date: 2026-01-05
 */

// Start the session
session_start();
// Include database connection
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch all customers from the database sorted by name
$stmt = $pdo->query("SELECT * FROM customers ORDER BY name ASC");
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Inventory System</title>
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
                <li><a href="customers.php" class="active"><i class="fas fa-users"></i> Customers</a></li>
                <li><a href="../purchases/index.php"><i class="fas fa-shopping-cart"></i> Purchases</a></li>
                <li><a href="../sales/index.php"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li><a href="../reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="users.php"><i class="fas fa-user-cog"></i> Users</a></li>
                <li><a href="../team.php"><i class="fas fa-users-cog"></i> Our Team</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1 class="page-title">Customer Management</h1>
                <div style="display: flex; gap: 10px;">
                    <button onclick="window.print()" class="btn btn-secondary btn-print"><i class="fas fa-print"></i> Print</button>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="customer_add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Customer</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="no-print" style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($customers) > 0): ?>
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td class="no-print"><input type="checkbox" class="row-checkbox"></td>
                                        <td><?php echo $customer['id']; ?></td>
                                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($customer['created_at'])); ?></td>
                                        <td>
                                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                                <a href="customer_edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem;"><i class="fas fa-edit"></i></a>
                                                
                                                <form method="POST" action="customer_delete.php" style="display:inline;" onsubmit="return confirm('Are you sure? This will unlink all related sales.');">
                                                    <input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
                                                    <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;"><i class="fas fa-trash"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">No customers found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
