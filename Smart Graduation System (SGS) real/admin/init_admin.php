<?php
require_once 'db.php';

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['setup'])) {
    $username = 'admin';
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            // Update
            $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
            $stmt->execute([$hashed_password, $username]);
            $message = "Password-ka 'admin' waa lagu guulaystay in la badalo!";
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hashed_password]);
            $message = "Admin account-ka waa la abuuray si guul leh!";
        }
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Khalad: " . $e->getMessage();
        $messageType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="so">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGS Admin Setup</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #0f172a;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .setup-card {
            background: #1e293b;
            padding: 3rem;
            border-radius: 24px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .form-control {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            border-radius: 12px;
            padding: 0.8rem;
        }
        .btn-primary {
            background: #4f46e5;
            border: none;
            border-radius: 12px;
            padding: 0.8rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="setup-card text-center">
        <h2 class="mb-4">Admin Reset/Setup</h2>
        <p class="text-secondary mb-4">Ku qor password-ka cusub ee aad rabto inaad u isticmaasho 'admin'.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <input type="password" name="password" class="form-control" placeholder="Password cusub" required>
            </div>
            <button type="submit" name="setup" class="btn btn-primary w-100">Duhur / Update Password</button>
        </form>
        
        <div class="mt-4">
            <a href="login.php" style="color: #94a3b8; text-decoration: none;">← Ku noqo Login</a>
        </div>
    </div>
</body>
</html>
