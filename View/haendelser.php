<?php
global $conn;
session_start();
require_once "db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Hent alle hændelser fra databasen
try {
    $stmt = $conn->query("SELECT id, title, description, severity, status, created_at FROM incidents ORDER BY created_at DESC");
    $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Databasefejl: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Hændelser | CyberMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0b0f19; color: #fff; font-family: 'Segoe UI'; }
        .navbar { background-color: #101826; }
        .card { background-color: #182235; border: none; color: #fff; }
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
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="monitoring.php">Overvågning</a></li>
                <li class="nav-item"><a class="nav-link active" href="haendelser.php">Hændelser</a></li>
                <li class="nav-item"><a class="nav-link" href="tiltag.php">Tiltag</a></li>
            </ul>
            <div class="d-flex align-items-center">
                <span class="me-3">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Log ud</a>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>🧾 Hændelsesoversigt</h2>
    <p>Her kan du se alle registrerede hændelser med detaljer.</p>

    <div class="card p-3 mt-4">
        <table class="table table-dark table-striped table-hover">
            <thead>
            <tr>
                <th>Titel</th>
                <th>Beskrivelse</th>
                <th>Alvor</th>
                <th>Status</th>
                <th>Oprettet</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($incidents) > 0): ?>
                <?php foreach ($incidents as $i): ?>
                    <tr>
                        <td><?= htmlspecialchars($i['title']) ?></td>
                        <td><?= htmlspecialchars($i['description']) ?></td>
                        <td><span class="badge bg-<?= $i['severity']=='critical'?'danger':($i['severity']=='high'?'warning':'secondary') ?>">
                <?= htmlspecialchars($i['severity']) ?></span></td>
                        <td><?= htmlspecialchars($i['status']) ?></td>
                        <td><?= htmlspecialchars(date("d.m.Y H:i", strtotime($i['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center text-muted">Ingen hændelser registreret.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="text-center mt-5 text-muted text-white">
        <small>Sidst opdateret: <?= date("d.m.Y H:i") ?></small>
    </div>
</div>

</body>
</html>

