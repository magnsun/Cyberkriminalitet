<?php
// db_connect.php
$host = "localhost";
$dbname = "cyberkriminalitet";
$username = "root";
$password = ""; // skriv evt. din MySQL adgangskode her

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Databaseforbindelse fejlede: " . $e->getMessage());
}
?>