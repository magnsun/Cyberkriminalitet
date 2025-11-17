<?php
global $conn;
session_start();
require_once "db_connect.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: dashboard.php?error=access_denied");
    exit;
}

function generate_uuid() {
    $data = random_bytes(16);

    // Version 4 UUID
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Hent alle brugere
$stmt = $conn->prepare("SELECT id, username, display_name, email, role, created_at 
                        FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tilføj bruger
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $id = generate_uuid();
    $username = $_POST['username'];
    $display_name = $_POST['display_name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (id, username, display_name, email, role, password_hash)
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, $username, $display_name, $email, $role, $password_hash]);

    header("Location: brugeradministration.php?msg=created");
    exit;
}

// Slet bruger
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    if ($delete_id !== $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
    }
    header("Location: brugeradministration.php?msg=deleted");
    exit;
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Brugeradministration | CyberMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0b0f19; color: #fff; font-family: 'Segoe UI'; }
        .navbar { background-color: #101826; }
        .card { background-color: #182235; border: none; color: #fff; border-radius: 10px; }
        .table-dark { background-color: #101826; }
        .badge { text-transform: capitalize; }
        .table thead th { border-bottom: 2px solid #2d394d; }
        .table tbody tr { border-bottom: 1px solid #2d394d; }
        .btn-primary { background-color: #3751ff; border: none; }
        .btn-danger { background-color: #d9534f; border: none; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark mb-4">
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
                <li class="nav-item"><a class="nav-link" href="tiltag.php">Tiltag</a></li>
                <li class="nav-item"><a class="nav-link" href="rapporter.php">Rapporter</a></li>
                <li class="nav-item"><a class="nav-link active" href="brugeradministration.php">Brugeradministration</a></li>
            </ul>

            <div class="d-flex align-items-center">
                <span class="me-3">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Log ud</a>
            </div>
        </div>
    </div>
</nav>

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold">Brugeradministration</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            ➕ Opret ny bruger
        </button>
    </div>

    <div class="card p-3">
        <h4 class="mb-3">Eksisterende brugere</h4>

        <table class="table table-dark table-hover align-middle">
            <thead>
            <tr>
                <th>Brugernavn</th>
                <th>Navn</th>
                <th>Email</th>
                <th>Rolle</th>
                <th>Oprettet</th>
                <th>Handling</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['display_name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="badge bg-danger">Admin</span>
                        <?php elseif ($u['role'] === 'analyst'): ?>
                            <span class="badge bg-warning text-dark">Analytiker</span>
                        <?php else: ?>
                            <span class="badge bg-info text-dark">Seer</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $u['created_at'] ?></td>
                    <td>
                        <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                            <a href="brugeradministration.php?delete=<?= $u['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Er du sikker på, at du vil slette denne bruger?');">
                                Slet
                            </a>
                        <?php else: ?>
                            <span class="text-muted">(dig selv)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- Modal: Tilføj bruger -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-dark">
            <div class="modal-header">
                <h5 class="modal-title">Opret ny bruger</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST">
                <div class="modal-body">

                    <label>Brugernavn</label>
                    <input type="text" name="username" class="form-control" required>

                    <label class="mt-2">Visningsnavn</label>
                    <input type="text" name="display_name" class="form-control">

                    <label class="mt-2">Email</label>
                    <input type="email" name="email" class="form-control">

                    <label class="mt-2">Rolle</label>
                    <select name="role" class="form-control">
                        <option value="admin">Admin</option>
                        <option value="analyst">Analytiker</option>
                        <option value="viewer">Seer</option>
                    </select>

                    <label class="mt-2">Password</label>
                    <input type="password" name="password" class="form-control" required>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Luk</button>
                    <button class="btn btn-primary" name="add_user">Opret bruger</button>
                </div>
            </form>

        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
