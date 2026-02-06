<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Header</h1>";

require_once __DIR__ . '/../db.php';
echo "<p>DB Connected. PDO is: " . (isset($pdo) ? "SET" : "NOT SET") . "</p>";

// Fetch settings manually to verify
$chk = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "<pre>Settings from DB: " . print_r($chk, true) . "</pre>";

echo "<hr>";
echo "<h2>Including Header...</h2>";
include __DIR__ . '/../views/header.php';
?>
