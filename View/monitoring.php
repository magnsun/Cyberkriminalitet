<?php

global $conn;

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/backend/db_connect.php';

$period = $_GET['period'] ?? '7d';

$periodMap = [
        '7d'  => '7 DAY',
        '1m'  => '1 MONTH',
        '3m'  => '3 MONTH',
        '6m'  => '6 MONTH',
        '1y'  => '1 YEAR'
];

$sqlInterval = $periodMap[$period] ?? '7 DAY';

// Statistik: antal incidents pr. dag (seneste 7 dage)
$stmt_chart = $conn->query("
  SELECT DATE(created_at) AS dato, COUNT(*) AS antal
  FROM incidents
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL $sqlInterval)
  GROUP BY DATE(created_at)
  ORDER BY dato ASC
");

$chartData = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

// Lav arrays til JavaScript
$labels = [];
$values = [];
foreach ($chartData as $row) {
    $labels[] = date("d.m", strtotime($row['dato']));
    $values[] = $row['antal'];
}

// Statistik: gennemsnitlig threat score pr. dag (seneste 7 dage)
$stmt_score = $conn->query("
  SELECT DATE(last_seen) AS dato, ROUND(AVG(threat_score), 1) AS avg_score
  FROM indicators
  WHERE last_seen >= DATE_SUB(CURDATE(), INTERVAL $sqlInterval)
  GROUP BY DATE(last_seen)
  ORDER BY dato ASC
");
$scoreData = $stmt_score->fetchAll(PDO::FETCH_ASSOC);

$scoreLabels = [];
$avgScores = [];
foreach ($scoreData as $row) {
    $scoreLabels[] = date("d.m", strtotime($row['dato']));
    $avgScores[] = $row['avg_score'];
}

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
        <a class="navbar-brand" href="/backend/routes.php?page=index">🛡️ CyberMonitor</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="/backend/routes.php?page=dashboard">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="/backend/routes.php?page=monitoring">Overvågning</a></li>
                <li class="nav-item"><a class="nav-link" href="/backend/routes.php?page=haendelser">Hændelser</a></li>
                <li class="nav-item"><a class="nav-link" href="/backend/routes.php?page=tiltag">Tiltag</a></li>
                <li class="nav-item"><a class="nav-link" href="/backend/routes.php?page=rapporter">Rapporter</a></li>
            </ul>
            <div class="d-flex align-items-center">
                <span class="me-3">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="/backend/routes.php?page=logout" class="btn btn-outline-light btn-sm">Log ud</a>
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
    <div class="card p-3 mb-4">
        <form method="get" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="page" value="monitoring">
            <strong>Vis periode:</strong>

            <select name="period" class="form-select w-auto" onchange="this.form.submit()">
                <option value="7d" <?= $period=='7d'?'selected':'' ?>>7 dage</option>
                <option value="1m" <?= $period=='1m'?'selected':'' ?>>1 måned</option>
                <option value="3m" <?= $period=='3m'?'selected':'' ?>>3 måneder</option>
                <option value="6m" <?= $period=='6m'?'selected':'' ?>>6 måneder</option>
                <option value="1y" <?= $period=='1y'?'selected':'' ?>>1 år</option>
            </select>
        </form>
    </div>
    <div class="card p-3 mb-4">
        <h5>📊 Incident-aktivitet (<?= htmlspecialchars($period) ?>)</h5>
        <canvas id="incidentChart" height="100"></canvas>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('incidentChart');

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Antal hændelser',
                    data: <?= json_encode($values) ?>,
                    borderColor: 'rgba(75,192,192,1)',
                    backgroundColor: 'rgba(75,192,192,0.2)',
                    tension: 0.3,
                    borderWidth: 2,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#0d6efd'
                }]
            },
            options: {
                plugins: {
                    legend: { labels: { color: '#fff' } }
                },
                scales: {
                    x: { ticks: { color: '#fff' }, grid: { color: '#333' } },
                    y: { ticks: { color: '#fff' }, grid: { color: '#333' } }
                }
            }
        });
    </script>
    <div class="card p-3 mb-4">
        <h5>🔥 Gennemsnitlig trussels-score (<?= htmlspecialchars($period) ?>)</h5>
        <canvas id="threatChart" height="100"></canvas>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // === Incident graf ===
        const ctx1 = document.getElementById('incidentChart');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Antal hændelser',
                    data: <?= json_encode($values) ?>,
                    borderColor: 'rgba(75,192,192,1)',
                    backgroundColor: 'rgba(75,192,192,0.2)',
                    tension: 0.3,
                    borderWidth: 2,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#0d6efd'
                }]
            },
            options: {
                plugins: { legend: { labels: { color: '#fff' } } },
                scales: {
                    x: { ticks: { color: '#fff' }, grid: { color: '#333' } },
                    y: { ticks: { color: '#fff' }, grid: { color: '#333' } }
                }
            }
        });

        // === Threat score graf ===
        const ctx2 = document.getElementById('threatChart');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: <?= json_encode($scoreLabels) ?>,
                datasets: [{
                    label: 'Gennemsnitlig threat score',
                    data: <?= json_encode($avgScores) ?>,
                    backgroundColor: 'rgba(255,99,132,0.5)',
                    borderColor: 'rgba(255,99,132,1)',
                    borderWidth: 2
                }]
            },
            options: {
                plugins: { legend: { labels: { color: '#fff' } } },
                scales: {
                    x: { ticks: { color: '#fff' }, grid: { color: '#333' } },
                    y: { ticks: { color: '#fff' }, grid: { color: '#333' }, beginAtZero: true, max: 100 }
                }
            }
        });
    </script>

    <div class="text-center mt-5 text-muted text-white">
        <small>Sidst opdateret: <?= date("d.m.Y H:i") ?></small>
    </div>
</div>
</body>
</html>
