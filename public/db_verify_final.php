<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "<h1>Execution Started</h1>";
// Credentials from User's .env
$host = 'localhost';
$db   = 'uplfveim_hotelos';
$user = 'uplfveim_deploy'; // Corrected User
$pass = 'jm@HS10$$'; 

echo "Attempting connection to <b>$db</b> as <b>$user</b>...<br>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h2 style='color:green'>SUCCESS! Credential Match.</h2>";
    echo "<p>Checking Table Count: " . $pdo->query("SHOW TABLES")->rowCount() . "</p>";
} catch (PDOException $e) {
    echo "<h2 style='color:red'>FAILURE</h2>";
    echo "Error: " . $e->getMessage();
}
