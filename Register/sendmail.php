<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/load_env.php';
load_env();
require_once 'db.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable error display in development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log function
function log_debug($message) {
    $path = __DIR__ . '/sendmail_log.txt';
    file_put_contents($path, "[" . date('Y-m-d H:i:s') . "] " . $message . "\n", FILE_APPEND);
}

try {
    $pdo->exec("SET NAMES 'utf8mb4'");

    $rawInput = file_get_contents("php://input");
    log_debug("RAW INPUT: " . var_export($rawInput, true));

    $data = json_decode($rawInput, true);
    log_debug("Parsed JSON: " . json_encode($data));

    $licenseKey = trim($data['license_key'] ?? '');

    if (!$licenseKey) {
        log_debug("❌ Missing license key.");
        echo json_encode(['status' => 'error', 'message' => 'No license key provided.']);
        exit;
    }

    // Fetch license info
    $stmt = $pdo->prepare("SELECT * FROM licenses WHERE license_key = ?");
    $stmt->execute([$licenseKey]);
    $license = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$license) {
        log_debug("❌ License not found: $licenseKey");
        echo json_encode(['status' => 'error', 'message' => 'License not found.']);
        exit;
    }

    // Prepare variables
    $fullName = htmlspecialchars($license['customer_name'] . ' ' . $license['last_name']);
    $email = $license['email'];
    $package = htmlspecialchars($license['package']);
    $expiry = htmlspecialchars($license['expiry_date']);
    $price = htmlspecialchars($license['price']);
    $status = htmlspecialchars($license['status']);
    $createdAt = htmlspecialchars($license['created_at']);
    $key = htmlspecialchars($license['license_key']);

    // === EMAIL TO CUSTOMER ===
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = env('SMTP_HOST', 'localhost');
    $mail->SMTPAuth = true;
    $mail->Username = env('SMTP_USER', '');
    $mail->Password = env('SMTP_PASSWORD', '');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = (int) env('SMTP_PORT', '465');

    $mail->setFrom(env('SMTP_FROM_EMAIL', env('SMTP_USER', '')), env('SMTP_FROM_NAME', 'Parrot License System'));
    $mail->addAddress($email, $fullName);
    $mail->isHTML(true);
    $mail->Subject = "✅ Your License Key + Payment Details";

    $mail->Body = <<<HTML
<h2>🎉 License Activated Successfully</h2>
<p>Dear <strong>$fullName</strong>,</p>
<p>Your license has been activated. Below are your license details and payment instructions:</p>
<hr>
<h3>🔑 License Details</h3>
<ul>
    <li><strong>License Key:</strong> $key</li>
    <li><strong>Package:</strong> $package</li>
    <li><strong>Price:</strong> \$$price</li>
    <li><strong>Status:</strong> $status</li>
    <li><strong>Expires On:</strong> $expiry</li>
    <li><strong>Issued On:</strong> $createdAt</li>
</ul>
<hr>
<h3>💰 Official Payment Instructions</h3>
<p><strong>📚 Start your study abroad journey with secure payment!</strong></p>
<p>🌍 Study in Canada, USA, UK, Germany, Korea, and more.</p>
<h4>🏦 Bank Transfer</h4>
<p>
    🏦 <strong>Bank:</strong> Equity Bank Rwanda Plc<br>
    🏢 <strong>Branch:</strong> Kigali<br>
    🔁 <strong>Swift Code:</strong> EQBLRWRW<br>
    👤 <strong>Beneficiary:</strong> PARROT CANADA VISA CONSULTANT CO. LTD
</p>
<p><strong>💵 RWF Account:</strong> 4030201236659</p>
<p><strong>💲 USD Account:</strong> 4030201236663</p>
<h4>📲 MTN MOMO</h4>
<p><strong>Code:</strong> *182*8*1*1680444# (Name: CANADA VISA CONSULTANT CO LTD)</p>
<p><em>Use the rate from <a href='https://www.bnr.rw/exchangeRate'>BNR Exchange Rate</a> if paying in RWF</em></p>
<h4>💳 Card Payment</h4>
<p><a href='https://visaconsultantcanada.com/Pay_now'>Pay via Visa Card</a></p>
<hr>
<p>📧 Email proof of payment to <strong>infos@visaconsultantcanada.com</strong></p>
<p>📞 Contact us: <strong>+1 (438) 290-6688</strong></p>
HTML;

    $mail->AltBody = "License Activated\nLicense Key: $key\nPackage: $package\nPrice: $price\nStatus: $status\nExpiry: $expiry\n\nPayment:\n- Equity Bank (RWF): 4030201236659\n- Equity Bank (USD): 4030201236663\n- MTN: *182*8*1*1680444#\n- Card: https://visaconsultantcanada.com/Pay_now\nProof: infos@visaconsultantcanada.com";

    $mail->send();
    log_debug("✅ Email sent to student: $email");

    // === EMAIL TO ADMIN ===
    $admin = new PHPMailer(true);
    $admin->isSMTP();
    $admin->Host = env('SMTP_HOST', 'localhost');
    $admin->SMTPAuth = true;
    $admin->Username = env('SMTP_USER', '');
    $admin->Password = env('SMTP_PASSWORD', '');
    $admin->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $admin->Port = (int) env('SMTP_PORT', '465');

    $admin->setFrom(env('SMTP_FROM_EMAIL', env('SMTP_USER', '')), 'License System');
    $admin->addAddress(env('SMTP_FROM_EMAIL', env('SMTP_USER', '')));
    $admin->isHTML(true);
    $admin->Subject = "🆕 License Activated by: $fullName";
    $admin->Body = <<<HTML
<h2>New License Activated</h2>
<ul>
    <li><strong>Name:</strong> $fullName</li>
    <li><strong>Email:</strong> $email</li>
    <li><strong>Package:</strong> $package</li>
    <li><strong>Price:</strong> $price</li>
    <li><strong>Status:</strong> $status</li>
    <li><strong>License Key:</strong> $key</li>
    <li><strong>Expires:</strong> $expiry</li>
</ul>
HTML;

    $admin->AltBody = "New license:\nName: $fullName\nEmail: $email\nPackage: $package\nPrice: $price\nKey: $key";

    $admin->send();
    log_debug("✅ Admin notification sent.");

    echo json_encode(['status' => 'success', 'message' => 'Emails sent to customer and admin.']);

} catch (Exception $e) {
    log_debug("❌ PHPMailer Exception: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Mailer error: ' . $e->getMessage()]);
} catch (PDOException $e) {
    log_debug("❌ Database Exception: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
