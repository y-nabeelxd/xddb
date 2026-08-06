<?php
session_start();
define('XDDB_VERSION', '1.0.0');

require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['user']) && basename($_SERVER['PHP_SELF']) !== 'index.php') {
    redirect('index.php');
}

$host = $_SESSION['host'] ?? '';
$user = $_SESSION['user'] ?? '';
$pass = $_SESSION['pass'] ?? '';
$port = (int)($_SESSION['port'] ?? 3306);
$db_type = $_SESSION['db_type'] ?? 'MySQL';

$conn = null;
$conn_error = '';

if ($user) {
    $conn = @new mysqli($host, $user, $pass, '', $port);
    if ($conn->connect_error) {
        $conn_error = $conn->connect_error;
        $conn = null;
    } else {
        $conn->set_charset('utf8mb4');
    }
}

mysqli_report(MYSQLI_REPORT_OFF);

$selected_db = $_GET['db'] ?? '';
$selected_table = $_GET['table'] ?? '';
$active_tab = $_GET['tab'] ?? '';

if (!$active_tab) {
    if ($selected_table) {
        $active_tab = 'browse';
    } elseif ($selected_db) {
        $active_tab = 'structure';
    } else {
        $active_tab = 'databases';
    }
}

$level = 'server';
if ($selected_table) {
    $level = 'table';
} elseif ($selected_db) {
    $level = 'database';
}

$databases = $conn ? get_databases($conn) : [];
$tables_by_db = [];
if ($conn) {
    foreach ($databases as $db_name) {
        $tables_by_db[$db_name] = get_tables($conn, $db_name);
    }
}
$tables = ($selected_db && isset($tables_by_db[$selected_db])) ? $tables_by_db[$selected_db] : [];

if (!isset($_SESSION['favorites'])) {
    $_SESSION['favorites'] = [];
}
if (!isset($_SESSION['recent'])) {
    $_SESSION['recent'] = [];
}

if ($selected_db && !in_array($selected_db, $_SESSION['recent'], true)) {
    array_unshift($_SESSION['recent'], $selected_db);
    $_SESSION['recent'] = array_slice(array_unique($_SESSION['recent']), 0, 10);
}
