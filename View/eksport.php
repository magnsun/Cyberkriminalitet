<?php
global $conn;
session_start();
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/backend/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Modtag parametre
$type = $_GET['type'] ?? null;
$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

if (!$type) {
    die("No export type selected.");
}

// Dato filter
$where = "";
$params = [];

if ($from) {
    $where .= " AND created_at >= ? ";
    $params[] = $from . " 00:00:00";
}
if ($to) {
    $where .= " AND created_at <= ? ";
    $params[] = $to . " 23:59:59";
}

// Håndtering af hver eksporttype
switch ($type) {

    // ---------------------------
    //      INCIDENTS EXPORT
    // ---------------------------
    case "incidents":
        $stmt = $conn->prepare("
            SELECT id, title, severity, status, created_at
            FROM incidents
            WHERE 1=1 $where
            ORDER BY created_at DESC
        ");
        $filename = "incidents_export.csv";
        break;


    // ---------------------------
    //      ALERTS EXPORT
    // ---------------------------
    case "alerts":
        $stmt = $conn->prepare("
            SELECT a.id, a.rule_name, a.severity, a.message, a.created_at, i.title AS incident_title
            FROM alerts a
            LEFT JOIN incidents i ON a.incident_id = i.id
            WHERE 1=1 $where
            ORDER BY a.created_at DESC
        ");
        $filename = "alerts_export.csv";
        break;


    // ---------------------------
    //   INDICATORS EXPORT
    // ---------------------------
    case "indicators":
        $stmt = $conn->prepare("
            SELECT id, indicator_type, value, first_seen, last_seen, threat_score, created_at
            FROM indicators
            ORDER BY created_at DESC
        ");
        $filename = "indicators_export.csv";
        break;


    // ---------------------------
    //       TILTAG EXPORT
    // ---------------------------
    case "tiltag":
        $stmt = $conn->prepare("
            SELECT a.id, u.username, i.title AS incident_title, a.changed, a.created_at
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN incidents i ON a.record_id = i.id
            WHERE action = 'NOTE' $where
            ORDER BY a.created_at DESC
        ");
        $filename = "tiltag_export.csv";
        break;


    default:
        die("Invalid export type.");
}

$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$data) {  // Ingen data
    die("No data available for export.");
}


// --------------------------------------
//  Eksportér CSV
// --------------------------------------
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");

$output = fopen("php://output", "w");

// Skriv kolonnenavne
fputcsv($output, array_keys($data[0]));

// Skriv rækker
foreach ($data as $row) {
    // Hvis det er tiltag, konverter JSON til ren tekst
    if ($type === "tiltag") {
        $json = json_decode($row["changed"], true);
        $row["changed"] = $json["note"] ?? $row["changed"];
    }

    fputcsv($output, $row);
}

fclose($output);
exit;
?>

