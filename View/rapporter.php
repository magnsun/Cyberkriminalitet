<?php
global $conn;

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/backend/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Hent overordnede tal
$total_incidents = $conn->query("SELECT COUNT(*) FROM incidents")->fetchColumn();
$open_incidents = $conn->query("SELECT COUNT(*) FROM incidents WHERE status != 'closed'")->fetchColumn();
$total_alerts = $conn->query("SELECT COUNT(*) FROM alerts")->fetchColumn();
$total_indicators = $conn->query("SELECT COUNT(*) FROM indicators")->fetchColumn();

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

$where_inc = "";
$where_alerts = "";
$where_tiltag = "";

$params_inc = [];
$params_alerts = [];
$params_tiltag = [];

$search = $_GET['search'] ?? null;

if ($search) {
    $like = "%$search%";

    // Incidents søgning
    $where_inc .= " AND (incidents.title LIKE ? OR incidents.status LIKE ? OR incidents.severity LIKE ?) ";
    array_push($params_inc, $like, $like, $like);

    // Alerts søgning
    $where_alerts .= " AND (a.rule_name LIKE ? OR a.message LIKE ? OR a.severity LIKE ?) ";
    array_push($params_alerts, $like, $like, $like);

    // Tiltag søgning
    $where_tiltag .= " AND (a.changed LIKE ? OR u.username LIKE ? OR i.title LIKE ?) ";
    array_push($params_tiltag, $like, $like, $like);
}


// INCIDENTS
$sql_inc = "
    SELECT id, title, severity, status, created_at
    FROM incidents
    WHERE 1=1 $where_inc
    ORDER BY created_at DESC
    LIMIT 100
";
$stmt_inc = $conn->prepare($sql_inc);
$stmt_inc->execute($params_inc);
$incidents = $stmt_inc->fetchAll(PDO::FETCH_ASSOC);


// ALERTS
$sql_alerts = "
    SELECT a.*, i.title AS incident_title
    FROM alerts a
    LEFT JOIN incidents i ON a.incident_id = i.id
    WHERE 1=1 $where_alerts
    ORDER BY a.created_at DESC
    LIMIT 100
";
$stmt_alerts = $conn->prepare($sql_alerts);
$stmt_alerts->execute($params_alerts);
$alerts = $stmt_alerts->fetchAll(PDO::FETCH_ASSOC);


// TILTAG (audit_logs)
$sql_tiltag = "
    SELECT a.*, u.username, i.title AS incident_title
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN incidents i ON a.record_id = i.id
    WHERE action = 'NOTE' $where_tiltag
    ORDER BY a.created_at DESC
    LIMIT 100
";
$stmt_tiltag = $conn->prepare($sql_tiltag);
$stmt_tiltag->execute($params_tiltag);
$tiltag = $stmt_tiltag->fetchAll(PDO::FETCH_ASSOC);


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
        <a class="navbar-brand" href="/backend/routes.php?page=index">🛡️ CyberMonitor</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="/backend/routes.php?page=dashboard">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="/backend/routes.php?page=monitoring">Overvågning</a></li>
                <li class="nav-item"><a class="nav-link" href="/backend/routes.php?page=haendelser">Hændelser</a></li>
                <li class="nav-item"><a class="nav-link" href="/backend/routes.php?page=tiltag">Tiltag</a></li>
                <li class="nav-item"><a class="nav-link active" href="/backend/routes.php?page=rapporter">Rapporter</a></li>
            </ul>
            <span class="me-3">👤 <?= $_SESSION['username'] ?></span>
            <a href="/backend/routes.php?page=logout" class="btn btn-outline-light btn-sm">Log ud</a>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>📊 Rapportcenter</h2>
        <button onclick="window.print()" class="btn btn-light no-print">🖨 Print / Gem som PDF</button>
    </div>

    <form method="GET" action="/backend/routes.php" class="card p-3 mb-4 no-print">
        <input type="hidden" name="page" value="rapporter">
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
    <div class="card p-3 no-print mb-4">
        <h5>📤 Eksportér Data</h5>
        <form method="GET" action="/backend/routes.php" class="row g-3 mt-2">
            <input type="hidden" name="page" value="eksport">

            <div class="col-md-4">
                <label class="form-label">Fra dato</label>
                <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Til dato</label>
                <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Datatype</label>
                <select name="type" class="form-select" required>
                    <option value="" disabled selected>Vælg…</option>
                    <option value="incidents">Incidents</option>
                    <option value="alerts">Alerts</option>
                    <option value="indicators">Indicators</option>
                    <option value="tiltag">Tiltag</option>
                </select>
            </div>

            <div class="col-12">
                <button class="btn btn-success w-100">Eksportér som CSV</button>
            </div>

        </form>
    </div>

    <div class="text-center mt-5 text-muted text-white">
        <small>Sidst opdateret: <?= date("d.m.Y H:i") ?></small>
    </div>
</div>
</body>
</html>
