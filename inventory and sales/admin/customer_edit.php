<?php
/**
 * admin/customer_edit.php
 * 
 * Edit Customer Details
 * 
 * Allows modification of existing customer information (Name, Email, Phone).
 * Pre-fills the form with current data.
 * Restricted to Admin users only.
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

// Access Control: Admin only
if ($_SESSION['role'] !== 'admin') {
    header("Location: customers.php?error=Access Denied. Admins only.");
    exit;
}

// Get customer ID from URL query parameters
$id = $_GET['id'] ?? null;
// If ID is not present, redirect to customer list
if (!$id) {
    header("Location: customers.php");
    exit;
}

// Initialize error and success messages
$error = '';
$success = '';

// Fetch existing customer data to pre-fill the form
try {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch();
    // Validate if customer exists
    if (!$customer) {
        die("Customer not found.");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle Form Submission for Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Validate name
    if (empty($name)) {
        $error = "Customer name is required.";
    } else {
        try {
            // Update customer record
            $stmt = $pdo->prepare("UPDATE customers SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $id]);
            $success = "Customer updated successfully!";
            
            // Refresh customer data after update to show latest info
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$id]);
            $customer = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Error updating customer: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer - Inventory System</title>
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
                <h1 class="page-title">Edit Customer</h1>
                <a href="customers.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to List</a>
            </div>

            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <?php if ($error): ?>
                    <div class="alert alert-danger" style="background: #fee2e2; color: #b91c1c; padding: 1rem; margin-bottom: 1rem; border-radius: 6px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 1rem; margin-bottom: 1rem; border-radius: 6px;">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name" class="form-label">Customer Name *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($customer['email']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone']); ?>">
                    </div>

                    <div class="text-right mt-4">
                        <button type="submit" class="btn btn-primary">Update Customer</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
