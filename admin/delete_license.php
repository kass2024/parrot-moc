<?php
require_once 'db.php';
require_once 'auth.php';

if (!isset($_GET['id'])) {
    header("Location: licenses.php");
    exit;
}

$id = $_GET['id'];

// Confirm license exists before deleting
$stmt = $pdo->prepare("SELECT * FROM licenses WHERE id = ?");
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    echo "License not found.";
    exit;
}

// Delete the license
$delete = $pdo->prepare("DELETE FROM licenses WHERE id = ?");
$delete->execute([$id]);

header("Location: licenses.php");
exit;
