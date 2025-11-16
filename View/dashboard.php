<?php
global $conn;
session_start();
require_once "db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// --- Hent incidents, indicators, alerts fra databasen ---
try {
    // Seneste incidents
    $stmt_incidents = $conn->query("SELECT title, severity, status, created_at FROM incidents ORDER BY created_at DESC LIMIT 5");
    $incidents = $stmt_incidents->fetchAll(PDO::FETCH_ASSOC);

    // Seneste indicators
    $stmt_indicators = $conn->query("SELECT indicator_type, value, threat_score, last_seen FROM indicators ORDER BY last_seen DESC LIMIT 5");
    $indicators = $stmt_indicators->fetchAll(PDO::FETCH_ASSOC);

    // Seneste alerts
    $stmt_alerts = $conn->query("SELECT rule_name, severity, message, created_at FROM alerts ORDER BY created_at DESC LIMIT 5");
    $alerts = $stmt_alerts->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Databasefejl: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | CyberMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0b0f19; color: #fff; font-family: 'Segoe UI'; }
        .navbar { background-color: #101826; }
        .card { background-color: #182235; border: none; color: #fff; }
        .card h5 { color: #0d6efd; }
        .table { color: #fff; }
        .badge { text-transform: capitalize; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">🛡️ CyberMonitor</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="monitoring.php">Overvågning</a></li>
                <li class="nav-item"><a class="nav-link" href="haendelser.php">Hændelser</a>
                <li class="nav-item"><a class="nav-link" href="tiltag.php">Tiltag</a></li>
                <li class="nav-item"><a class="nav-link" href="rapporter.php">Rapporter</a></li>
                <li class="nav-item"><a class="nav-link" href="brugeradministration.php">brugeradministration</a> </li>
            </ul>
            <div class="d-flex align-items-center">
                <span class="me-3">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Log ud</a>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="mb-4">Velkommen, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h2>

    <div class="row g-4">
        <!-- INCIDENTS -->
        <div class="col-md-4">
            <div class="card p-3 h-100">
                <h5>Seneste hændelser</h5>
                <?php if ($incidents): ?>
                    <table class="table table-sm table-dark table-striped mt-3">
                        <thead>
                        <tr><th>Titel</th><th>Alvor</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($incidents as $i): ?>
                            <tr>
                                <td><?= htmlspecialchars($i['title']) ?></td>
                                <td><span class="badge bg-<?= $i['severity']=='critical'?'danger':($i['severity']=='high'?'warning':'secondary') ?>">
                                <?= htmlspecialchars($i['severity']) ?></span></td>
                                <td><?= htmlspecialchars($i['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted mt-3">Ingen hændelser registreret.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- INDICATORS -->
        <div class="col-md-4">
            <div class="card p-3 h-100">
                <h5>Seneste indikatorer</h5>
                <?php if ($indicators): ?>
                    <table class="table table-sm table-dark table-striped mt-3">
                        <thead>
                        <tr><th>Type</th><th>Værdi</th><th>Score</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($indicators as $ind): ?>
                            <tr>
                                <td><?= htmlspecialchars($ind['indicator_type']) ?></td>
                                <td><?= htmlspecialchars($ind['value']) ?></td>
                                <td><span class="badge bg-<?= $ind['threat_score'] >= 80 ? 'danger' : ($ind['threat_score'] >= 50 ? 'warning' : 'success') ?>">
                                <?= htmlspecialchars($ind['threat_score']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted mt-3">Ingen indikatorer registreret.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ALERTS -->
        <div class="col-md-4">
            <div class="card p-3 h-100">
                <h5>Seneste alarmer</h5>
                <?php if ($alerts): ?>
                    <table class="table table-sm table-dark table-striped mt-3">
                        <thead>
                        <tr><th>Regel</th><th>Alvor</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($alerts as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['rule_name']) ?></td>
                                <td><span class="badge bg-<?= $a['severity']=='critical'?'danger':($a['severity']=='high'?'warning':'secondary') ?>">
                                <?= htmlspecialchars($a['severity']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted mt-3">Ingen alarmer registreret.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="text-center mt-5 text-muted text-white">
        <small>Sidst opdateret: <?= date("d.m.Y H:i") ?></small>
    </div>
</div>
</body>
</html>
