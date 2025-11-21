<?php
session_start();
require_once __DIR__ . '/db_connect.php';

$page = $_GET['page'] ?? 'dashboard';

// sti til dine views
$viewPath = __DIR__ . '/../view/';

// Router
switch ($page) {

    case 'dashboard':
        require $viewPath . 'dashboard.php';
        break;

    case 'monitoring':
        require $viewPath . 'monitoring.php';
        break;

    case 'haendelser':
        require $viewPath . 'haendelser.php';
        break;

    case 'tiltag':
        require $viewPath . 'tiltag.php';
        break;

    case 'rapporter':
        require $viewPath . 'rapporter.php';
        break;

    case 'brugeradministration':
        require $viewPath . 'brugeradministration.php';
        break;

    case 'logout':
        require $viewPath . 'logout.php';
        break;

    case 'login':
        require $viewPath . 'login.php';
        break;

    case 'index':
        require $viewPath . 'index.php';
        break;


    default:
        http_response_code(404);
        echo "<h1>404 - Siden findes ikke</h1>";
        break;
}

