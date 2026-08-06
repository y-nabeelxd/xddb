<?php
require_once __DIR__ . '/includes/init.php';

if ($conn_error) {
    session_destroy();
    redirect('index.php');
}

$user = $_SESSION['user'];
$host = $_SESSION['host'];
$port = $_SESSION['port'];
$server_info = get_server_info($conn);

require __DIR__ . '/includes/layout_start.php';

if ($level === 'table') {
    require __DIR__ . '/views/table.php';
} elseif ($level === 'database') {
    require __DIR__ . '/views/database.php';
} else {
    require __DIR__ . '/views/server.php';
}

require __DIR__ . '/includes/layout_end.php';
