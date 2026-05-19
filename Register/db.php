<?php
require_once dirname(__DIR__) . '/load_env.php';
load_env();

$host = env('REGISTER_DB_HOST', 'localhost');
$db = env('REGISTER_DB_NAME', 'interview');
$user = env('REGISTER_DB_USER', 'root');
$pass = env('REGISTER_DB_PASS', '');
$charset = env('REGISTER_DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'DB Connection failed: ' . $e->getMessage();
    exit;
}
