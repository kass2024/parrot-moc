<?php
require_once dirname(__DIR__) . '/load_env.php';
load_env();

$host = env('DB_HOST', 'localhost');
$db = env('DB_NAME', 'iotxa_db');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$charset = env('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'DB Connection failed: ' . $e->getMessage();
    exit;
}
