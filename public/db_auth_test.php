<?php
echo "<h1>DB Auth Test v3</h1>";
ini_set('display_errors', 1);

// Credentials provided by user
$host = 'localhost';
$db   = 'uplfveim_hotelos';
$user = 'uplfveim';
$pass = 'jm@HS10$$'; 

echo "Attempting connection to <b>$db</b> as <b>$user</b>...<br>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h2 style='color:green'>SUCCESS! Password is Correct.</h2>";
    echo "<p>Please update your .env file with this password.</p>";
} catch (PDOException $e) {
    echo "<h2 style='color:red'>FAILURE</h2>";
    echo "Error: " . $e->getMessage();
}
