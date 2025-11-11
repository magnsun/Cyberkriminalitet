<?php
session_start();
require_once "db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

try {
    // Hent alle incidents (eller de seneste 20)
    $stmt_incidents = $conn->query("SELECT title, description, severity, status, created_at FROM incidents ORDER BY created_at DESC LIMIT 20");
    $incidents = $stmt_incidents->fetchAll(PDO::FETCH_ASSOC);

    // Hent indikatorer
    $stmt_indicators = $conn->query("SELECT indicator_type, value, threat_score, last_seen FROM indicators ORDER BY last_seen DESC LIMIT 20");
    $indicators = $stmt_indicators->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("DB-fejl: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Overvågning | CyberMonitor</title>
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
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="monitoring.php">Overvågning</a>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <span class="me-3">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Log ud</a>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>🔍 Overvågningspanel</h2>
    <p>Her kan du se alle registrerede hændelser og indikatorer.</p>

    <div class="row g-4 mt-4">
        <div class="col-md-6">
            <div class="card p-3">
                <h5>📄 Alle hændelser</h5>
                <table class="table table-sm table-dark table-striped mt-3">
                    <thead>
                    <tr>
                        <th>Titel</th><th>Alvor</th><th>Status</th><th>Oprettet</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($incidents as $i): ?>
                        <tr>
                            <td><?= htmlspecialchars($i['title']) ?></td>
                            <td><span class="badge bg-<?= $i['severity']=='critical'?'danger':($i['severity']=='high'?'warning':'secondary') ?>">
                  <?= htmlspecialchars($i['severity']) ?></span></td>
                            <td><?= htmlspecialchars($i['status']) ?></td>
                            <td><?= htmlspecialchars(date("d.m.Y H:i", strtotime($i['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-3">
                <h5>🧠 Seneste indikatorer</h5>
                <table class="table table-sm table-dark table-striped mt-3">
                    <thead>
                    <tr>
                        <th>Type</th><th>Værdi</th><th>Score</th><th>Sidst set</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($indicators as $ind): ?>
                        <tr>
                            <td><?= htmlspecialchars($ind['indicator_type']) ?></td>
                            <td><?= htmlspecialchars($ind['value']) ?></td>
                            <td><span class="badge bg-<?= $ind['threat_score'] >= 80 ? 'danger' : ($ind['threat_score'] >= 50 ? 'warning' : 'success') ?>">
                  <?= htmlspecialchars($ind['threat_score']) ?></span></td>
                            <td><?= htmlspecialchars(date("d.m.Y H:i", strtotime($ind['last_seen']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="text-center mt-5">
        <a href="dashboard.php" class="btn btn-outline-light">⬅️ Tilbage til Dashboard</a>
    </div>
</div>
</body>
</html>
