<?php
global $conn;
session_start();
require_once "db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Tilføj nyt tiltag (log-post i audit_logs) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $incident_id = $_POST['incident_id'];
    $note = $_POST['note'];

    $stmt = $conn->prepare("
    INSERT INTO audit_logs (id, user_id, action, table_name, record_id, changed)
    VALUES (UUID(), ?, 'NOTE', 'incidents', ?, JSON_OBJECT('note', ?))
  ");
    $stmt->execute([$user_id, $incident_id, $note]);
}

// --- Hent åbne incidents ---
$stmt_incidents = $conn->query("
  SELECT id, title, severity, status, created_at
  FROM incidents
  WHERE status != 'closed'
  ORDER BY created_at DESC
");
$incidents = $stmt_incidents->fetchAll(PDO::FETCH_ASSOC);

// --- Hent seneste tiltag (audit_logs) ---
$stmt_logs = $conn->query("
  SELECT a.*, u.username, i.title AS incident_title
  FROM audit_logs a
  LEFT JOIN users u ON a.user_id = u.id
  LEFT JOIN incidents i ON a.record_id = i.id
  WHERE a.action = 'NOTE'
  ORDER BY a.created_at DESC
  LIMIT 20
");
$logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Tiltag | CyberMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0b0f19; color: #fff; font-family: 'Segoe UI'; }
        .navbar { background-color: #101826; }
        .card { background-color: #182235; border: none; color: #fff; }
        .table { color: #fff; }
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
                <li class="nav-item"><a class="nav-link" href="haendelser.php">Hændelser</a></li>
                <li class="nav-item"><a class="nav-link active" href="tiltag.php">Tiltag</a></li>
                <li class="nav-item"><a class="nav-link" href="rapporter.php">Rapporter</a></li>
                <li class="nav-item"><a class="nav-link" href="eksport.php">Rapporter</a></li>
            </ul>
            <div class="d-flex align-items-center">
                <span class="me-3">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Log ud</a>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>🧩 Tiltag</h2>
    <p>Her kan du tilføje og se manuelle tiltag relateret til hændelser.</p>

    <div class="card p-3 mb-4">
        <h5>➕ Registrér nyt tiltag</h5>
        <form method="POST" class="row g-3 mt-2">
            <div class="col-md-6">
                <label class="form-label">Vælg hændelse</label>
                <select name="incident_id" class="form-select" required>
                    <?php foreach ($incidents as $i): ?>
                        <option value="<?= $i['id'] ?>">
                            <?= htmlspecialchars($i['title']) ?> (<?= htmlspecialchars($i['severity']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Beskrivelse af tiltag</label>
                <textarea name="note" class="form-control" rows="3" required></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Gem tiltag</button>
            </div>
        </form>
    </div>

    <div class="card p-3">
        <h5>📋 Seneste tiltag</h5>
        <table class="table table-dark table-striped mt-3">
            <thead>
            <tr>
                <th>Hændelse</th>
                <th>Bruger</th>
                <th>Beskrivelse</th>
                <th>Tidspunkt</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $l): ?>
                <?php $note = json_decode($l['changed'], true)['note'] ?? ''; ?>
                <tr>
                    <td><?= htmlspecialchars($l['incident_title'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($l['username'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($note) ?></td>
                    <td><?= htmlspecialchars(date("d.m.Y H:i", strtotime($l['created_at']))) ?></td>
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
