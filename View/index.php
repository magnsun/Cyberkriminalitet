<?php
// Database connection
try{
    $pdo = new PDO('mysql:host=localhost;dbname=cyberkriminalitet', 'root', '');
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit;
}

session_start();

// Hvis brugeren allerede er logget ind, send videre til dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberMonitor | Overvågning af cyberkriminalitet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #0b0f19;
            color: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }
        .navbar {
            background-color: #101826;
        }
        .hero {
            text-align: center;
            padding: 100px 20px;
        }
        .hero h1 {
            font-size: 3rem;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .hero p {
            font-size: 1.2rem;
            color: #adb5bd;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">🛡️ CyberMonitor</a>
        <div class="d-flex">
            <a href="login.php" class="btn btn-outline-light">Log ind</a>
        </div>
    </div>
</nav>

<!-- Hero sektion -->
<section class="hero">
    <h1>Velkommen til CyberMonitor</h1>
    <p>
        Et webbaseret system til overvågning og analyse af cyberkriminalitet.<br>
        Indsaml, korrelér og reagér på trusler i realtid.
    </p>
    <a href="login.php" class="btn btn-primary btn-lg mt-3">Gå til Dashboard</a>
</section>

<!-- Info sektion -->
<div class="container text-center mt-5 mb-5">
    <h3>Systemstatus</h3>
    <p>Databaseforbindelse: <span class="text-success">OK</span></p>
    <p>Seneste opdatering: <?= date("d.m.Y H:i") ?></p>
</div>

<footer class="footer">
    &copy; <?= date("Y") ?> CyberMonitor | Udviklet til cybersikkerhedsovervågning
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
