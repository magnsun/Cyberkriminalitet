<?php
global $conn;
session_start();
require_once "db_connect.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Tjek bruger i DB
    $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = :username LIMIT 1");
    $stmt->bindParam(":username", $username);
    $stmt->execute();

    if ($stmt->rowCount() === 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $user["password_hash"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Forkert adgangskode.";
        }
    } else {
        $error = "Bruger findes ikke.";
    }
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Log ind | CyberMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #0b0f19;
            color: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-box {
            max-width: 400px;
            margin: 100px auto;
            background-color: #101826;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
        }
        .form-control {
            background-color: #1a2233;
            border: none;
            color: #fff;
        }
        .form-control:focus {
            background-color: #1f2a3d;
            color: #fff;
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
        }
    </style>
</head>
<body>
<div class="login-box">
    <h2 class="text-center mb-4">🔐 Log ind</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="mb-3">
            <label for="username" class="form-label">Brugernavn</label>
            <input type="text" name="username" id="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Adgangskode</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Log ind</button>
    </form>
    <div class="text-center mt-3">
        <a href="index.php" class="text-light">← Tilbage til forsiden</a>
    </div>
</div>
</body>
</html>

