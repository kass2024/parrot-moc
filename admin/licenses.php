<?php
require_once 'db.php';
require_once 'auth.php';

// Fetch all licenses
$stmt = $pdo->query("SELECT * FROM licenses ORDER BY created_at DESC");
$licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Licenses</title>
    <style>
        body { font-family: Arial; margin: 0; padding: 20px; background: #f9f9f9; }
        h1 { margin-bottom: 20px; }
        input[type="text"] {
            padding: 10px;
            width: 300px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background: #007bff; color: white; }
        a.button {
            background: #007bff; color: white; padding: 6px 10px;
            border-radius: 4px; text-decoration: none; margin-right: 4px;
            display: inline-block;
        }
        .button.red { background: #dc3545; }
        .button.green { background: #28a745; }
        .topnav a { margin-right: 15px; text-decoration: none; color: #007bff; }
        .topnav { margin-bottom: 20px; }
        td code { word-break: break-all; }
    </style>
</head>
<body>
<div class="topnav">
    <a href="index.php">🏠 Dashboard</a>
    <a href="licenses.php">🔑 Manage Licenses</a>
    <a href="add_license.php">➕ Add License</a>
    <a href="logout.php" style="color:red;">🚪 Logout</a>
</div>

<h1>All Licenses</h1>

<input type="text" id="searchInput" placeholder="🔍 Search licenses...">

<table id="licenseTable">
    <thead>
        <tr>
            <th>ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>Address</th>
            <th>Package</th>
            <th>License Key</th>
            <th>Status</th>
            <th>Expiry</th>
            <th>Device ID</th>
            <th>Last Used</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($licenses as $row): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['customer_name']) ?></td>
            <td><?= htmlspecialchars($row['last_name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['address']) ?></td>
            <td><?= htmlspecialchars($row['package']) ?></td>
            <td><code><?= htmlspecialchars($row['license_key']) ?></code></td>
            <td style="color:<?= $row['status'] === 'expired' ? 'red' : 'green' ?>"><?= $row['status'] ?></td>
            <td><?= $row['expiry_date'] ?></td>
            <td><?= $row['device_id'] ?: '-' ?></td>
            <td><?= $row['last_used_at'] ?></td>
            <td>
                <a href="edit_license.php?id=<?= $row['id'] ?>" class="button">✏️ Edit</a>
                <a href="delete_license.php?id=<?= $row['id'] ?>" class="button red" onclick="return confirm('Delete this license?')">🗑 Delete</a>
                <a href="#" class="button green" onclick="issueLicense('<?= $row['license_key'] ?>', <?= $row['id'] ?>)">🔗 Issue</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
// Smart search
document.getElementById('searchInput').addEventListener('keyup', function () {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#licenseTable tbody tr');

    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

// Copy license link and mark as issued
function issueLicense(licenseKey, licenseId) {
    const url = `https://www.applyparrot.com/index.html?license=${licenseKey}`;
    navigator.clipboard.writeText(url).then(() => {
        alert("✅ License link copied:\n" + url);
    });

    // Optional: mark as issued
    fetch(`mark_issued.php?id=${licenseId}`, { method: 'POST' });
}
</script>

</body>
</html>
