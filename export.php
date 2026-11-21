<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user'])) {
    redirect('index.php');
}

$host = $_SESSION['host'];
$user = $_SESSION['user'];
$pass = $_SESSION['pass'];
$port = (int)($_SESSION['port'] ?? 3306);

$conn = new mysqli($host, $user, $pass, '', $port);
if ($conn->connect_error) {
    session_destroy();
    redirect('index.php');
}
$conn->set_charset('utf8mb4');

$db = $_GET['db'] ?? '';
$table = $_GET['table'] ?? '';
$format = $_GET['format'] ?? 'sql';
$export_type = $_GET['type'] ?? 'quick';
$is_selected = !empty($_GET['selected']) && !empty($_SESSION['export_query']) && (($_SESSION['export_table'] ?? '') === $table);
$custom_query = $is_selected ? $_SESSION['export_query'] : null;

if ($table && $db) {
    $filename = $is_selected ? "{$table}_selected" : $table;
    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            echo export_csv($conn, $db, $table, $custom_query);
            break;
        case 'json':
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '.json"');
            echo export_json($conn, $db, $table, $custom_query);
            break;
        case 'xml':
            header('Content-Type: application/xml');
            header('Content-Disposition: attachment; filename="' . $filename . '.xml"');
            echo export_xml($conn, $db, $table, $custom_query);
            break;
        default:
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $filename . '.sql"');
            echo export_sql($conn, $db, [$table], $custom_query);
    }
} elseif ($db) {
    $tables = null;
    if ($export_type === 'custom' && !empty($_GET['tables'])) {
        $tables = explode(',', $_GET['tables']);
    }
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $db . '.sql"');
    echo export_sql($conn, $db, $tables);
} else {
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="server_export.sql"');
    $output = "-- Server Export\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    foreach (get_databases($conn) as $dbname) {
        if (in_array($dbname, ['information_schema', 'performance_schema', 'mysql', 'sys'], true)) continue;
        $output .= export_sql($conn, $dbname);
        $output .= "\n";
    }
    echo $output;
}
exit;
