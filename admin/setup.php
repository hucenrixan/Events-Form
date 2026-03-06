<?php
/**
 * One-time admin account creation script.
 *
 * USAGE:
 *   1. Open this page in your browser once to create the first admin user.
 *   2. DELETE this file from the server immediately after use.
 *
 * Access is protected by a hard-coded setup token below.
 */

define('SETUP_TOKEN', 'CHANGE_THIS_TOKEN_BEFORE_USE');

require_once '../config.php';

$message = '';
$success = false;

// Token check
$token = $_GET['token'] ?? $_POST['token'] ?? '';
if ($token !== SETUP_TOKEN) {
    http_response_code(403);
    die('<h2 style="font-family:sans-serif;color:#c0392b;padding:40px">403 Forbidden — Invalid setup token.</h2>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (strlen($username) < 3) {
        $message = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match.';
    } else {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
            $stmt->execute([$username, $hash]);

            $success = true;
            $message = "Admin user \"$username\" created successfully. Delete this file now!";
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup</title>
    <style>
        body { font-family: sans-serif; background: #f5f4f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; padding: 40px; border-radius: 12px; max-width: 420px; width: 100%; box-shadow: 0 4px 20px rgba(0,0,0,.1); }
        h1 { color: #5a6348; margin-bottom: 8px; }
        p.warn { color: #e65100; font-size: .875rem; margin-bottom: 20px; background: #fff3e0; padding: 10px; border-radius: 6px; }
        label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: 5px; color: #444; }
        input { width: 100%; padding: 9px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: .9rem; margin-bottom: 16px; }
        button { width: 100%; padding: 11px; background: #8b947a; color: #fff; border: none; border-radius: 8px; font-size: .95rem; cursor: pointer; }
        button:hover { background: #737d63; }
        .msg { padding: 12px; border-radius: 8px; margin-bottom: 18px; font-size: .875rem; }
        .msg.error   { background: #fde8e8; color: #c0392b; }
        .msg.success { background: #e8f5e9; color: #2e7d32; font-weight: 600; }
    </style>
</head>
<body>
<div class="card">
    <h1>Admin Setup</h1>
    <p class="warn">Warning: Delete this file immediately after creating your admin account.</p>

    <?php if ($message): ?>
        <div class="msg <?= $success ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <label>Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Confirm Password</label>
        <input type="password" name="confirm" required>

        <button type="submit">Create Admin Account</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
