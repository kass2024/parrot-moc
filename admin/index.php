<?php
require_once 'db.php';

// Quick stats
$total = $pdo->query("SELECT COUNT(*) FROM licenses")->fetchColumn();
$active = $pdo->query("SELECT COUNT(*) FROM licenses WHERE status = 'active'")->fetchColumn();
$expired = $pdo->query("SELECT COUNT(*) FROM licenses WHERE status = 'expired'")->fetchColumn();
$soon = $pdo->query("SELECT COUNT(*) FROM licenses WHERE expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// Recent licenses
$latest = $pdo->query("SELECT * FROM licenses ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Smart License Admin Panel</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; margin: 0; padding: 20px; }
        h1 { margin-bottom: 20px; }
        .card-group { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .card {
            background: white;
            padding: 20px;
            flex: 1;
            min-width: 200px;
            border-radius: 8px;
            box-shadow: 0 0 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .card h2 { margin: 0; font-size: 32px; }
        .card small { display: block; color: #666; margin-top: 5px; }

        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background: #007bff; color: white; }

        .topbar a {
            margin-right: 15px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }
        .topbar { margin-bottom: 25px; }
    </style>
</head>
<body>

<div class="topbar">
    <a href="index.php">🏠 Home</a>
    <a href="licenses.php">🔑 All Licenses</a>
    <a href="add_license.php">➕ Add License</a>
</div>

<h1>Smart License Admin Panel</h1>

<div class="card-group">
    <div class="card">
        <h2><?= $total ?></h2>
        <small>Total Licenses</small>
    </div>
    <div class="card">
        <h2 style="color:green;"><?= $active ?></h2>
        <small>Active</small>
    </div>
    <div class="card">
        <h2 style="color:red;"><?= $expired ?></h2>
        <small>Expired</small>
    </div>
    <div class="card">
        <h2 style="color:orange;"><?= $soon ?></h2>
        <small>Expiring Soon</small>
    </div>
</div>

<h2>🕒 Recently Added Licenses</h2>
<table>
    <tr>
        <th>Customer</th>
        <th>License Key</th>
        <th>Status</th>
        <th>Expiry</th>
        <th>Created At</th>
    </tr>
    <?php foreach ($latest as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['customer_name']) ?></td>
            <td><code><?= htmlspecialchars($row['license_key']) ?></code></td>
            <td style="color:<?= $row['status'] === 'expired' ? 'red' : 'green' ?>"><?= $row['status'] ?></td>
            <td><?= $row['expiry_date'] ?></td>
            <td><?= $row['created_at'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
