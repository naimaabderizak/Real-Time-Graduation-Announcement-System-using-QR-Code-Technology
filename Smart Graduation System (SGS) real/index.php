<?php
// Redirect to Admin Login by default
header("Location: admin/login.php");
exit();
?>
<!DOCTYPE html>
<html lang="so">
<head>
    <meta charset="UTF-8">
    <title>Smart Graduation System (SGS)</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; background: #0f172a; color: white; margin: 0; }
        .container { text-align: center; background: #1e293b; padding: 2rem; border-radius: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        .btn { display: inline-block; padding: 1rem 2rem; margin: 0.5rem; background: #4f46e5; color: white; text-decoration: none; border-radius: 0.5rem; font-weight: bold; }
        .btn:hover { background: #6366f1; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Smart Graduation System</h1>
        <p>Doorasho samay si aad u gasho qaybaha system-ka:</p>
        <a href="admin/login.php" class="btn">ADMIN DASHBOARD</a>
        <a href="display/index.php" class="btn">GRAND SCREEN</a>
        <a href="scanner/index.php" class="btn">SCANNER PANEL</a>
    </div>
</body>
</html>
