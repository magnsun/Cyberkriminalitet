<?php

?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Adgang nægtet | CyberMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: radial-gradient(circle at top, #0b0f19, #06090f);
            color: #fff;
            font-family: 'Segoe UI', sans-serif;
            height: 100vh;
        }
        .denied-box {
            max-width: 520px;
            background-color: #101826;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 0 40px rgba(255, 0, 0, 0.2);
            text-align: center;
        }
        .icon {
            font-size: 64px;
            color: #dc3545;
        }
        .btn-dashboard {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            border: none;
            border-radius: 30px;
            padding: 12px 30px;
            font-weight: 600;
        }
    </style>
</head>
<body class="d-flex justify-content-center align-items-center">

<div class="denied-box">
    <div class="icon mb-3">⛔</div>
    <h1 class="mb-3">Adgang nægtet</h1>

    <p class="text-secondary fs-5">
        Du har ikke de nødvendige rettigheder til at få adgang til denne side.
    </p>

    <?php if (isset($_SESSION['username'])): ?>
        <p class="mt-3">
            Logget ind som: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong><br>
            Rolle: <span class="badge bg-danger"><?= htmlspecialchars($_SESSION['role'] ?? 'ukendt') ?></span>
        </p>
    <?php endif; ?>

    <div class="mt-4 d-flex justify-content-center gap-3">
        <a href="/backend/routes.php?page=dashboard" class="btn btn-dashboard">⬅ Til dashboard</a>
        <a href="/backend/routes.php?page=logout" class="btn btn-outline-light">Log ud</a>
    </div>
</div>

</body>
</html>
