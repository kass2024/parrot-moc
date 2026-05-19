<?php
require_once 'db.php';
require_once 'auth.php';

if (!isset($_GET['id'])) {
    header("Location: licenses.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM licenses WHERE id = ?");
$stmt->execute([$id]);
$license = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$license) {
    echo "License not found.";
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = $_POST['customer_name'];
    $license_key = $_POST['license_key'];
    $expiry_date = $_POST['expiry_date'];
    $status = $_POST['status'];
    $device_id = $_POST['device_id'];

    $update = $pdo->prepare("UPDATE licenses SET customer_name=?, license_key=?, expiry_date=?, status=?, device_id=? WHERE id=?");
    $update->execute([$customer_name, $license_key, $expiry_date, $status, $device_id, $id]);

    $message = "✅ License updated successfully.";
    // Refresh the updated values
    $stmt->execute([$id]);
    $license = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit License</title>
    <style>
        body { font-family: Arial; margin: 0; padding: 20px; background: #f5f5f5; }
        h1 { margin-bottom: 20px; }
        form { background: white; padding: 20px; border-radius: 8px; max-width: 600px; }
        input, select { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .topnav a { margin-right: 15px; text-decoration: none; color: #007bff; }
        .topnav { margin-bottom: 20px; }
        .msg { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="topnav">
    <a href="index.php">🏠 Dashboard</a>
    <a href="licenses.php">🔑 Manage Licenses</a>
    <a href="logout.php" style="color:red;">🚪 Logout</a>
</div>

<h1>Edit License</h1>

<?php if ($message): ?>
    <div class="msg"><?= $message ?></div>
<?php endif; ?>

<form method="post">
    <label>Customer Name:</label>
    <input type="text" name="customer_name" value="<?= htmlspecialchars($license['customer_name']) ?>" required>

    <label>License Key:</label>
    <input type="text" name="license_key" value="<?= htmlspecialchars($license['license_key']) ?>" required>

    <label>Expiry Date:</label>
    <input type="datetime-local" name="expiry_date" value="<?= date('Y-m-d\TH:i', strtotime($license['expiry_date'])) ?>" required>

    <label>Status:</label>
    <select name="status" required>
        <option value="active" <?= $license['status'] === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="expired" <?= $license['status'] === 'expired' ? 'selected' : '' ?>>Expired</option>
    </select>

    <label>Device ID (optional):</label>
    <input type="text" name="device_id" value="<?= htmlspecialchars($license['device_id']) ?>">

    <button type="submit">💾 Update License</button>
</form>

</body>
</html>
