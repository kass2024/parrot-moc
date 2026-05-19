<?php
require_once dirname(__DIR__) . '/load_env.php';
load_env();

$host = env('DB_HOST', 'localhost');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$dbname = env('DB_NAME', 'iotxa_db');

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$conn->set_charset(env('DB_CHARSET', 'utf8mb4'));
$conn->query("SET time_zone = '+00:00'");
