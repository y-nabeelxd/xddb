<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
require_once __DIR__ . '/includes/functions.php';

mysqli_report(MYSQLI_REPORT_OFF);

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['user'])) {
    json_response(['error' => 'Not authenticated'], 401);
}

$host = $_SESSION['host'];
$user = $_SESSION['user'];
$pass = $_SESSION['pass'];
$port = (int)($_SESSION['port'] ?? 3306);

$conn = new mysqli($host, $user, $pass, '', $port);
if ($conn->connect_error) {
    json_response(['error' => 'Connection failed: ' . $conn->connect_error], 500);
}
$conn->set_charset('utf8mb4');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($token)) {
        json_response(['error' => 'CSRF verification failed'], 403);
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
switch ($action) {
    case 'server_metrics':
        $status = get_server_status($conn);
        $res = run_query($conn, "SHOW PROCESSLIST");
        $processlist = [];
        if ($res['success'] && $res['result'] instanceof mysqli_result) {
            while ($row = $res['result']->fetch_assoc()) {
                $processlist[] = $row;
            }
        }
        json_response(['status' => $status, 'processlist' => $processlist]);
        break;

    case 'get_context':
        $databases = get_databases($conn);
        $context = ['databases' => $databases, 'tables' => []];
        $db = $_GET['db'] ?? '';
        if ($db) {
            $context['tables'] = get_tables($conn, $db);
            $columns = [];
            foreach ($context['tables'] as $t) {
                $cols = get_table_structure($conn, $db, $t);
                $columns[$t] = array_column($cols, 'Field');
            }
            $context['columns'] = $columns;
        }
        json_response($context);
        break;

    case 'execute_sql':
        $sql = $_POST['sql'] ?? '';
        $db = $_POST['db'] ?? '';
        if ($db) $conn->select_db($db);
        if (!$sql) {
            json_response(['success' => false, 'error' => 'No SQL provided']);
        }
        add_query_history($sql);
        $queries = [];
        foreach (explode(';', $sql) as $q) {
            $q = trim($q);
            if ($q !== '') $queries[] = $q;
        }
        $results = [];
        $error = null;
        $use_db = $db;
        $messages = [];
        foreach ($queries as $q) {
            $upper = strtoupper(ltrim($q));
            if (preg_match('/^USE\s+`?([^\s;]+)`?/i', $q, $m)) {
                $use_db = trim($m[1], '`');
                $conn->select_db($use_db);
                $messages[] = ['type' => 'info', 'message' => 'Database changed to ' . $use_db . '.'];
                continue;
            }
            $res = run_query($conn, $q);
            if (!$res['success']) {
                $error = $res['error'];
                $results[] = ['sql' => $q, 'success' => false, 'error' => $error, 'affected' => 0, 'insert_id' => 0];
                break;
            }
            $row_result = null;
            $col_result = null;
            if ($res['result'] instanceof mysqli_result) {
                $row_result = [];
                while ($row = $res['result']->fetch_assoc()) {
                    $row_result[] = $row;
                }
                $col_result = [];
                $res['result']->data_seek(0);
                while ($field = $res['result']->fetch_field()) {
                    $col_result[] = $field->name;
                }
            }
            $results[] = [
                'sql' => $q,
                'success' => true,
                'error' => null,
                'affected' => $res['affected'],
                'insert_id' => $res['insert_id'],
                'rows' => $row_result,
                'columns' => $col_result
            ];
            if ($row_result === null) {
                $messages[] = ['type' => 'success', 'message' => $res['affected'] . ' row(s) affected.'];
            }
        }
        json_response([
            'success' => $error === null,
            'error' => $error,
            'results' => $results,
            'messages' => $messages,
            'use_db' => $use_db,
            'columns' => $col_result,
            'rows' => $row_result
        ]);
        break;

    case 'update_cell':
        $db = $_POST['db'] ?? '';
        $table = $_POST['table'] ?? '';
        $pk_col = $_POST['pk_col'] ?? '';
        $pk_val = $_POST['pk_val'] ?? '';
        $col = $_POST['col'] ?? '';
        $val = $_POST['val'] ?? null;
        $is_null = !empty($_POST['is_null']);

        if (!$db || !$table || !$pk_col || !$col) {
            json_response(['error' => 'Missing required parameters'], 400);
        }

        $conn->select_db($db);

        $escaped_table = esc_sql_id($table);
        $escaped_col = esc_sql_id($col);
        $escaped_pk = esc_sql_id($pk_col);

        if ($is_null) {
            $sql = "UPDATE `$escaped_table` SET `$escaped_col` = NULL WHERE `$escaped_pk` = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                json_response(['error' => $conn->error], 500);
            }
            $stmt->bind_param('s', $pk_val);
        } else {
            $sql = "UPDATE `$escaped_table` SET `$escaped_col` = ? WHERE `$escaped_pk` = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                json_response(['error' => $conn->error], 500);
            }
            $stmt->bind_param('ss', $val, $pk_val);
        }

        if ($stmt->execute()) {
            json_response(['success' => true, 'affected_rows' => $stmt->affected_rows]);
        } else {
            json_response(['error' => $stmt->error], 500);
        }
        break;

    default:
        json_response(['error' => 'Unknown action'], 400);
}
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
