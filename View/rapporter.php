<?php
global $conn;
session_start();
require_once "db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Hent overordnede tal
$total_incidents = $conn->query("SELECT COUNT(*) FROM incidents")->fetchColumn();
$open_incidents = $conn->query("SELECT COUNT(*) FROM incidents WHERE status != 'closed'")->fetchColumn();
$total_alerts = $conn->query("SELECT COUNT(*) FROM alerts")->fetchColumn();
$total_indicators = $conn->query("SELECT COUNT(*) FROM indicators")->fetchColumn();

// Seneste 10 incidents
$incidents = $conn->query("
    SELECT id, title, severity, status, created_at
    FROM incidents
    ORDER BY created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Seneste 10 alerts
$alerts = $conn->query("
    SELECT a.*, i.title AS incident_title
    FROM alerts a
    LEFT JOIN incidents i ON a.incident_id = i.id
    ORDER BY a.created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Seneste 10 tiltag (audit logs)
$tiltag = $conn->query("
    SELECT a.*, u.username, i.title AS incident_title
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN incidents i ON a.record_id = i.id
    WHERE action = 'NOTE'
    ORDER BY a.created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

$where_inc = "";
$where_alerts = "";
$where_tiltag = "";

$params_inc = [];
$params_alerts = [];
$params_tiltag = [];

// INCIDENTS
if ($from) {
    $where_inc .= " AND incidents.created_at >= ? ";
    $params_inc[] = $from . " 00:00:00";
}
if ($to) {
    $where_inc .= " AND incidents.created_at <= ? ";
    $params_inc[] = $to . " 23:59:59";
}

// ALERTS
if ($from) {
    $where_alerts .= " AND a.created_at >= ? ";
    $params_alerts[] = $from . " 00:00:00";
}
if ($to) {
    $where_alerts .= " AND a.created_at <= ? ";
    $params_alerts[] = $to . " 23:59:59";
}

// TILTAG (audit_logs)
if ($from) {
    $where_tiltag .= " AND a.created_at >= ? ";
    $params_tiltag[] = $from . " 00:00:00";
}
if ($to) {
    $where_tiltag .= " AND a.created_at <= ? ";
    $params_tiltag[] = $to . " 23:59:59";
}


?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Rapporter | CyberMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { background-color: #0b0f19; color: #ffffff; }
        .navbar { background-color: #101826; }
        .card { background-color: #182235; border: none; color: #fff; }
        .table { color: #fff; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
        }
    </style>
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-dark no-print">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">🛡️ CyberMonitor</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="monitoring.php">Overvågning</a></li>
                <li class="nav-item"><a class="nav-link" href="haendelser.php">Hændelser</a></li>
                <li class="nav-item"><a class="nav-link" href="tiltag.php">Tiltag</a></li>
                <li class="nav-item"><a class="nav-link active" href="rapporter.php">Rapporter</a></li>
            </ul>
            <span class="me-3">👤 <?= $_SESSION['username'] ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Log ud</a>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>📊 Rapportcenter</h2>
        <button onclick="window.print()" class="btn btn-light no-print">🖨 Print / Gem som PDF</button>
    </div>

    <form method="GET" class="card p-3 mb-4 no-print">
        <h5>📅 Filtrer på dato</h5>
        <div class="row mt-2">
            <div class="col-md-4">
                <label class="form-label">Fra dato</label>
                <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control">
            </div>

            <div class="col-md-4">
                <label class="form-label">Til dato</label>
                <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control">
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary w-100">Anvend filter</button>
            </div>
        </div>
    </form>

    <!-- Statistik -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <h4><?= $total_incidents ?></h4>
                <p>Total Incidents</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <h4><?= $open_incidents ?></h4>
                <p>Åbne Incidents</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <h4><?= $total_alerts ?></h4>
                <p>Total Alerts</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <h4><?= $total_indicators ?></h4>
                <p>Registrerede Indicators</p>
            </div>
        </div>
    </div>

    <!-- Incident Liste -->
    <div class="card p-3 mb-4">
        <h5>📁 Seneste Incidents</h5>
        <table class="table table-dark table-striped mt-3">
            <thead>
            <tr>
                <th>Titel</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Oprettet</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($incidents as $i): ?>
                <tr>
                    <td><?= htmlspecialchars($i['title']) ?></td>
                    <td><?= htmlspecialchars($i['severity']) ?></td>
                    <td><?= htmlspecialchars($i['status']) ?></td>
                    <td><?= date("d.m.Y H:i", strtotime($i['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Alerts -->
    <div class="card p-3 mb-4">
        <h5>⚠️ Seneste Alerts</h5>
        <table class="table table-dark table-striped mt-3">
            <thead>
            <tr>
                <th>Alert</th>
                <th>Hændelse</th>
                <th>Severity</th>
                <th>Tidspunkt</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($alerts as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['rule_name']) ?></td>
                    <td><?= htmlspecialchars($a['incident_title'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($a['severity']) ?></td>
                    <td><?= date("d.m.Y H:i", strtotime($a['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Tiltag -->
    <div class="card p-3 mb-4">
        <h5>📝 Seneste Tiltag</h5>
        <table class="table table-dark table-striped mt-3">
            <thead>
            <tr>
                <th>Hændelse</th>
                <th>Bruger</th>
                <th>Tiltag</th>
                <th>Tidspunkt</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($tiltag as $t): ?>
                <?php $note = json_decode($t['changed'], true)['note'] ?? ''; ?>
                <tr>
                    <td><?= htmlspecialchars($t['incident_title'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($t['username'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($note) ?></td>
                    <td><?= date("d.m.Y H:i", strtotime($t['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="text-center mt-5 text-muted text-white">
        <small>Sidst opdateret: <?= date("d.m.Y H:i") ?></small>
    </div>
</div>
</body>
</html>
