<?php
session_start();
require_once dirname(__DIR__) . '/load_env.php';
load_env();

$valid_users = [];
$adminUsers = env('ADMIN_USERS', 'admin:parrot');
foreach (explode(',', $adminUsers) as $pair) {
    $pair = trim($pair);
    if ($pair === '' || strpos($pair, ':') === false) {
        continue;
    }
    [$user, $pass] = explode(':', $pair, 2);
    $valid_users[trim($user)] = trim($pass);
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (array_key_exists($username, $valid_users) && $valid_users[$username] === $password) {
        $_SESSION['admin'] = true;
        $_SESSION['username'] = $username;
        header("Location: index.php");
        exit;
    } else {
        $error = "❌ Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <style>
        body { font-family: Arial; background: #f0f0f0; margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 320px;
        }
        h2 { text-align: center; margin-bottom: 20px; }
        input[type="text"], input[type="password"] {
            width: 100%; padding: 10px; margin: 10px 0;
            border: 1px solid #ccc; border-radius: 5px;
        }
        button {
            width: 100%; padding: 10px;
            background: #007bff; color: white;
            border: none; border-radius: 5px;
            font-weight: bold; cursor: pointer;
        }
        .error { color: red; text-align: center; margin-top: 10px; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>License Admin Login</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required autofocus>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
</div>

</body>
</html>
