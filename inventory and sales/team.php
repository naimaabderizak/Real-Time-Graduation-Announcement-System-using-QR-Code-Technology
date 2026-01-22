<?php
/**
 * team.php
 * 
 * Team Member Display Page
 * 
 * Displays the list of team members involved in the project. 
 * Highlights the team leader and lists other members in a grid layout.
 * 
 * Author: System
 * Date: 2026-01-05
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$team = [
    ['name' => 'Saleh Nasser Ahmed', 'role' => 'Team Leader & Full Stack Dev', 'img' => 'saleh.jpg', 'is_leader' => true],
    ['name' => 'Naima Abdirizak Ahmed', 'role' => 'Software Developer', 'img' => 'naima.jpg', 'is_leader' => false],
    ['name' => 'Abdihamid Abdi Nunow', 'role' => 'UI/UX Designer', 'img' => 'abdihamid.jpg', 'is_leader' => false],
    ['name' => 'Ibrahim Mumin Ali', 'role' => 'Backend Developer', 'img' => 'ibrahim.jpg', 'is_leader' => false],
    ['name' => 'Ayaan Mohamed', 'role' => 'Frontend Developer', 'img' => 'ayaan.jpg', 'is_leader' => false],
    ['name' => 'Abdulkadir Salah Ali', 'role' => 'Database Administrator', 'img' => 'abdulkadir.jpg', 'is_leader' => false],
    ['name' => 'Abdikafi Abdifitah Bare', 'role' => 'System Analyst', 'img' => 'abdikafi.jpg', 'is_leader' => false],
    ['name' => 'Maryam Abdifitah Bashiir', 'role' => 'QA Engineer', 'img' => 'maryam.jpg', 'is_leader' => false],
    ['name' => 'Salman Isse Adan', 'role' => 'DevOps Engineer', 'img' => 'salman.jpg', 'is_leader' => false],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Team - Inventory System</title>
    <link rel="stylesheet" href="css/style.css">
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
                <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="admin/products.php"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="admin/suppliers.php"><i class="fas fa-truck"></i> Suppliers</a></li>
                <li><a href="admin/customers.php"><i class="fas fa-users"></i> Customers</a></li>
                <li><a href="purchases/index.php"><i class="fas fa-shopping-cart"></i> Purchases</a></li>
                <li><a href="sales/index.php"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li><a href="reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="admin/users.php"><i class="fas fa-user-cog"></i> Users</a></li>
                <li><a href="team.php" class="active"><i class="fas fa-users-cog"></i> Our Team</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1 class="page-title">Meet Our Team</h1>
            </div>

            <!-- Leader Section -->
            <div class="team-leader-section">
                <?php foreach ($team as $member): ?>
                    <?php if ($member['is_leader']): ?>
                        <div class="team-frame leader-frame">
                            <div class="frame-inner">
                                <div class="member-img">
                                    <!-- Placeholder image if file doesn't exist -->
                                    <img src="images/team/<?php echo $member['img']; ?>" alt="<?php echo $member['name']; ?>" onerror="this.src='https://via.placeholder.com/150?text=User'">
                                </div>
                                <div class="member-info">
                                    <h3><?php echo $member['name']; ?></h3>
                                    <p class="member-role"><?php echo $member['role']; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Members Grid -->
            <div class="team-grid">
                <?php foreach ($team as $member): ?>
                    <?php if (!$member['is_leader']): ?>
                        <div class="team-frame">
                            <div class="frame-inner">
                                <div class="member-img">
                                    <img src="images/team/<?php echo $member['img']; ?>" alt="<?php echo $member['name']; ?>" onerror="this.src='https://via.placeholder.com/150?text=User'">
                                </div>
                                <div class="member-info">
                                    <h3><?php echo $member['name']; ?></h3>
                                    <p class="member-role"><?php echo $member['role']; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html>
