<?php
require_once 'db.php';

$message = "";

// License Key Generator
function generateLicenseKey() {
    $segments = [];
    for ($i = 0; $i < 4; $i++) {
        $segments[] = strtoupper(bin2hex(random_bytes(3)));
    }
    return implode('-', $segments);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = $_POST['customer_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $package = $_POST['package'];
    $price = $_POST['price'];

    // Determine expiry days based on selected package
    switch ($package) {
        case 'Basic Package':
            $expiry_days = 7;
            break;
        case 'Value Plan':
            $expiry_days = 14;
            break;
        case 'Standard Package':
            $expiry_days = 21;
            break;
        case 'Premium Package':
            $expiry_days = 28;
            break;
        default:
            $expiry_days = 365; // fallback
    }

    // Calculate expiry date
    $expiry_date = date('Y-m-d H:i:s', strtotime("+$expiry_days days"));

    // Prevent duplicate emails
    $check = $pdo->prepare("SELECT COUNT(*) FROM licenses WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetchColumn() > 0) {
        $message = "❌ This email is already registered. Please use another.";
    } else {
        $license_key = generateLicenseKey();
        $status = 'active';
        $device_id = null;

        $stmt = $pdo->prepare("INSERT INTO licenses 
            (customer_name, last_name, email, address, package, price, license_key, expiry_date, status, device_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $customer_name, $last_name, $email, $address, $package, $price,
            $license_key, $expiry_date, $status, $device_id
        ]);

        $message = "✅ License registered: $package – $$price. Expires in $expiry_days days.";

        // Call sendmail.php to send email to admin and student
        $postData = [
            'customer_name' => $customer_name,
            'last_name' => $last_name,
            'email' => $email,
            'address' => $address,
            'package' => $package,
            'price' => $price,
            'license_key' => $license_key,
            'expiry_date' => $expiry_date
        ];

        $ch = curl_init('sendmail.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOC Interview License</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            padding: 20px;
            margin: 0;
            background: #eef1f5;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 500px;
            margin: auto;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        label {
            font-weight: 500;
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        input, select, textarea, button {
            width: 100%;
            padding: 12px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }
        button {
            background: #007bff;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #0056b3;
        }
        .msg {
            background: #d1e7dd;
            color: #0f5132;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .msg.error {
            background: #f8d7da;
            color: #842029;
        }
        .price-display {
            font-weight: bold;
            color: #444;
            margin-top: -10px;
            margin-bottom: 15px;
        }
        @media (max-width: 600px) {
            form {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

<!-- Logo section -->
<div style="text-align:center; margin-bottom: 10px;">
    <img src="logo.png" alt="Logo" style="max-width: 300px; height: 140px;">
</div>

<h1>Register to MOC Interview License</h1>

<?php if ($message): ?>
    <div class="msg <?= strpos($message, '❌') === 0 ? 'error' : '' ?>"><?= $message ?></div>
<?php endif; ?>

<form method="post">
    <label>First Name:</label>
    <input type="text" name="customer_name" required>

    <label>Last Name:</label>
    <input type="text" name="last_name" required>

    <label>Email:</label>
    <input type="email" name="email" required>

    <label>Address:</label>
    <textarea name="address" rows="3" required></textarea>

    <label>Package:</label>
    <select name="package" id="package" required onchange="updatePackagePrice()">
        <option value="">-- Select Package --</option>
        <option value="Basic Package" data-price="30">🔘 Basic Package – 7 days – $30</option>
        <option value="Value Plan" data-price="40">🔘 Value Plan – 2 weeks – $40</option>
        <option value="Standard Package" data-price="50">🔘 Standard Package – 3 weeks – $50</option>
        <option value="Premium Package" data-price="60">🔘 Premium Package – 4 weeks – $60</option>
    </select>

    <div class="price-display" id="priceDisplay">💰 Price: $0.00</div>
    <input type="hidden" name="price" id="price">

    <button type="submit">➕ Register Now</button>
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
