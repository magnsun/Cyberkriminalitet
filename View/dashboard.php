<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | CyberMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0b0f19; color: #fff;
            font-family: 'Segoe UI', serif; }
        .navbar { background-color: #101826; }
        .card { background-color: #182235; border: none; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">CyberMonitor Dashboard</a>
        <div class="d-flex">
            <span class="me-3">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Log ud</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h2>Velkommen, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h2>
    <p>Dette er dit kontrolpanel for overvågning af cyberkriminalitet.</p>

    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card p-3">
                <h5>Aktive hændelser</h5>
                <p>Viser seneste incidents fra databasen (kommer snart).</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3">
                <h5>Seneste indikatorer</h5>
                <p>Viser de nyeste IP’er/domæner fra overvågningen.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3">
                <h5>Systemstatus</h5>
                <p>Databaseforbindelse: <span class="text-success">OK</span></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>

