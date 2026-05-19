<?php
require_once 'db.php';
require_once 'auth.php';

$message = "";

// License Key Generator
function generateLicenseKey() {
    $segments = [];
    for ($i = 0; $i < 4; $i++) {
        $segments[] = strtoupper(bin2hex(random_bytes(3)));
    }
    return implode('-', $segments);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = $_POST['customer_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $package = $_POST['package'];
    $price = $_POST['price'];
    $license_key = generateLicenseKey();
    $expiry_date = $_POST['expiry_date'];
    $status = $_POST['status'];
    $device_id = $_POST['device_id'];

    $stmt = $pdo->prepare("INSERT INTO licenses 
        (customer_name, last_name, email, address, package, price, license_key, expiry_date, status, device_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
    $stmt->execute([
        $customer_name, $last_name, $email, $address, $package,
        $price, $license_key, $expiry_date, $status, $device_id
    ]);

    $message = "✅ License added successfully.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New License</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        h1 { margin-bottom: 20px; }
        form { background: white; padding: 20px; border-radius: 8px; max-width: 700px; margin: auto; }
        input, select, textarea {
            width: 100%; padding: 10px; margin-bottom: 15px;
            border: 1px solid #ccc; border-radius: 4px;
        }
        button { background: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .topnav a { margin-right: 15px; text-decoration: none; color: #007bff; }
        .topnav { margin-bottom: 20px; text-align: center; }
        .msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .price-display { font-weight: bold; color: #444; margin-top: -10px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="topnav">
    <a href="index.php">🏠 Dashboard</a>
    <a href="licenses.php">🔑 Manage Licenses</a>
    <a href="logout.php" style="color:red;">🚪 Logout</a>
</div>

<h1>Add New License</h1>

<?php if ($message): ?>
    <div class="msg"><?= $message ?></div>
<?php endif; ?>

<form method="post">
    <label>First Name:</label>
    <input type="text" name="customer_name" required>

    <label>Last Name:</label>
    <input type="text" name="last_name" required>

    <label>Email:</label>
    <input type="email" name="email" required>

    <label>Address:</label>
    <textarea name="address" rows="2" required></textarea>

    <label>Package:</label>
    <select name="package" id="package" onchange="updatePackagePrice()" required>
        <option value="">-- Select Package --</option>
        <option value="Basic Package" data-price="30">🔘 Basic Package – 7 days – $30</option>
        <option value="Value Plan" data-price="40">🔘 Value Plan – 2 weeks – $40</option>
        <option value="Standard Package" data-price="50">🔘 Standard Package – 3 weeks – $50</option>
        <option value="Premium Package" data-price="60">🔘 Premium Package – 4 weeks – $60</option>
    </select>

    <div class="price-display" id="priceDisplay">💰 Price: $0.00</div>
    <input type="hidden" name="price" id="price">

    <label>Expiry Date:</label>
    <input type="datetime-local" name="expiry_date" required>

    <label>Status:</label>
    <select name="status" required>
        <option value="active">Active</option>
        <option value="expired">Expired</option>
    </select>

    <label>Device ID (optional):</label>
    <input type="text" name="device_id">

    <button type="submit">➕ Add License</button>
</form>

<script>
function updatePackagePrice() {
    const select = document.getElementById('package');
    const selected = select.options[select.selectedIndex];
    const price = selected.getAttribute('data-price') || 0;

    document.getElementById('price').value = price;
    document.getElementById('priceDisplay').innerText = `💰 Price: $${parseFloat(price).toFixed(2)}`;
}
</script>

</body>
</html>
