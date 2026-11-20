<?php

if (!defined('XDDB_VERSION')) {
    define('XDDB_VERSION', '1.3.3');
}

function esc_sql_id($identifier) {
    return '`' . str_replace('`', '``', (string)$identifier) . '`';
}

function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . esc(get_csrf_token()) . '">';
}

function check_for_updates() {
    if (!defined('XDDB_VERSION')) return false;
    
    $now = time();
    $cache_time = 600; // 10 minutes cache
    
    if (isset($_SESSION['xddb_latest_version']) && isset($_SESSION['xddb_update_last_check']) && ($now - $_SESSION['xddb_update_last_check']) < $cache_time) {
        $latest = $_SESSION['xddb_latest_version'];
    } else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/y-nabeelxd/xddb/releases/latest');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'XDDB-Updater');
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['tag_name'])) {
                $latest = ltrim($data['tag_name'], 'vV');
                $_SESSION['xddb_latest_version'] = $latest;
                $_SESSION['xddb_update_zip'] = $data['zipball_url'] ?? '';
                $_SESSION['xddb_update_last_check'] = $now;
            } else {
                if (isset($_SESSION['xddb_latest_version'])) {
                    return version_compare(XDDB_VERSION, $_SESSION['xddb_latest_version'], '<') ? $_SESSION['xddb_latest_version'] : false;
                }
                return false;
            }
        } else {
            if (isset($_SESSION['xddb_latest_version'])) {
                return version_compare(XDDB_VERSION, $_SESSION['xddb_latest_version'], '<') ? $_SESSION['xddb_latest_version'] : false;
            }
            return false;
        }
    }
    
    if (version_compare(XDDB_VERSION, $latest, '<')) {
        return $latest;
    }
    return false;
}
mysqli_report(MYSQLI_REPORT_OFF);

function esc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function base_url($params = []) {
    $query = http_build_query($params);
    return 'dashboard.php' . ($query ? '?' . $query : '');
}

function get_databases($conn) {
    $list = [];
    $result = $conn->query('SHOW DATABASES');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $list[] = $row['Database'];
        }
    }
    return $list;
}

function get_tables($conn, $db) {
    $list = [];
    $conn->select_db($db);
    $result = $conn->query('SHOW TABLES');
    if ($result) {
        while ($row = $result->fetch_row()) {
            $list[] = $row[0];
        }
    }
    return $list;
}

function get_table_structure($conn, $db, $table) {
    $conn->select_db($db);
    $columns = [];
    $result = $conn->query("SHOW FULL COLUMNS FROM `$table`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row;
        }
    }
    return $columns;
}

function get_table_indexes($conn, $db, $table) {
    $conn->select_db($db);
    $indexes = [];
    $result = $conn->query("SHOW INDEX FROM `$table`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $indexes[] = $row;
        }
    }
    return $indexes;
}

function get_table_status($conn, $db, $table) {
    $conn->select_db($db);
    $result = $conn->query("SHOW TABLE STATUS LIKE '" . $conn->real_escape_string($table) . "'");
    if ($result) {
        return $result->fetch_assoc();
    }
    return null;
}

function get_db_size($conn, $db) {
    $conn->select_db($db);
    $result = $conn->query("SELECT SUM(data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = '" . $conn->real_escape_string($db) . "'");
    if ($result) {
        $row = $result->fetch_assoc();
        return (int)($row['size'] ?? 0);
    }
    return 0;
}

function format_bytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GiB';
    }
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MiB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KiB';
    }
    return $bytes . ' B';
}

function analyze_sql_error($error) {
    if (!$error) return '';
    $recommendation = '';
    
    if (stripos($error, 'doesn\'t exist') !== false && stripos($error, 'Table') !== false) {
        $recommendation = 'Check if the table name is spelled correctly, and ensure you are working in the correct database (use the sidebar or type USE db_name).';
    } elseif (stripos($error, 'Unknown column') !== false) {
        $recommendation = 'Check the column name spelling. It might not exist in the selected table.';
    } elseif (stripos($error, 'You have an error in your SQL syntax') !== false) {
        $recommendation = 'Check for missing commas, unclosed quotes, or misspelled keywords near the indicated section.';
    } elseif (stripos($error, 'Duplicate entry') !== false) {
        $recommendation = 'You are trying to insert a value that already exists in a UNIQUE or PRIMARY KEY column.';
    } elseif (stripos($error, 'foreign key constraint fails') !== false) {
        $recommendation = 'You are referencing an ID that does not exist in the parent table, or trying to delete a parent row that still has children.';
    } elseif (stripos($error, 'No database selected') !== false) {
        $recommendation = 'Select a database first from the left sidebar, or run "USE your_database_name;" before your query.';
    } elseif (stripos($error, 'Access denied') !== false) {
        $recommendation = 'Your database user does not have permission to perform this action. Check user privileges.';
    }

    if ($recommendation) {
        return esc($error) . '<br><br>💡 <strong>Recommendation:</strong> ' . $recommendation;
    }
    return esc($error);
}

function run_query($conn, $sql) {
    $start_time = microtime(true);
    $result = false;
    $error = '';
    try {
        $result = $conn->query($sql);
        $error = $conn->error;
    } catch (mysqli_sql_exception $e) {
        $error = $e->getMessage();
    }
    $duration = round(microtime(true) - $start_time, 4);
    return [
        'success' => $result !== false,
        'result' => $result instanceof mysqli_result ? $result : null,
        'error' => analyze_sql_error($error),
        'affected' => $conn->affected_rows,
        'insert_id' => $conn->insert_id,
        'duration' => $duration
    ];
}

function add_query_history($sql, $duration = null) {
    if (!isset($_SESSION['query_history'])) {
        $_SESSION['query_history'] = [];
    }
    array_unshift($_SESSION['query_history'], [
        'sql' => $sql,
        'time' => date('Y-m-d H:i:s'),
        'duration' => $duration
    ]);
    $_SESSION['query_history'] = array_slice($_SESSION['query_history'], 0, 50);
}

function get_primary_key($conn, $db, $table) {
    $indexes = get_table_indexes($conn, $db, $table);
    foreach ($indexes as $idx) {
        if ($idx['Key_name'] === 'PRIMARY') {
            return $idx['Column_name'];
        }
    }
    $columns = get_table_structure($conn, $db, $table);
    return $columns[0]['Field'] ?? null;
}

function build_where_from_pk($conn, $db, $table, $pk_values) {
    $pk = get_primary_key($conn, $db, $table);
    if (!$pk || !isset($pk_values[$pk])) {
        return null;
    }
    $conn->select_db($db);
    $val = $conn->real_escape_string($pk_values[$pk]);
    return "`$pk` = '$val'";
}

function export_sql($conn, $db, $tables = null) {
    $conn->select_db($db);
    if ($tables === null) {
        $tables = get_tables($conn, $db);
    }
    $output = "-- SQL Export\n-- Database: $db\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $output .= "CREATE DATABASE IF NOT EXISTS `$db`;\nUSE `$db`;\n\n";
    foreach ($tables as $table) {
        $create = $conn->query("SHOW CREATE TABLE `$table`");
        if ($create) {
            $row = $create->fetch_assoc();
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $output .= $row['Create Table'] . ";\n\n";
        }
        $data = $conn->query("SELECT * FROM `$table`");
        if ($data && $data->num_rows > 0) {
            $columns = [];
            while ($field = $data->fetch_field()) {
                $columns[] = "`" . $field->name . "`";
            }
            $data->data_seek(0);
            while ($row = $data->fetch_assoc()) {
                $values = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . $conn->real_escape_string($val) . "'";
                    }
                }
                $output .= "INSERT INTO `$table` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
            }
            $output .= "\n";
        }
    }
    return $output;
}

function export_csv($conn, $db, $table) {
    $conn->select_db($db);
    $result = $conn->query("SELECT * FROM `$table`");
    $output = fopen('php://temp', 'r+');
    if ($result && $result->num_rows > 0) {
        $headers = [];
        while ($field = $result->fetch_field()) {
            $headers[] = $field->name;
        }
        fputcsv($output, $headers);
        $result->data_seek(0);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    return $csv;
}

function export_json($conn, $db, $table) {
    $conn->select_db($db);
    $result = $conn->query("SELECT * FROM `$table`");
    $rows = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function export_xml($conn, $db, $table) {
    $conn->select_db($db);
    $result = $conn->query("SELECT * FROM `$table`");
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= "<database name=\"" . esc($db) . "\">\n";
    $xml .= "  <table name=\"" . esc($table) . "\">\n";
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $xml .= "    <row>\n";
            foreach ($row as $key => $val) {
                $xml .= "      <" . esc($key) . ">" . esc($val ?? '') . "</" . esc($key) . ">\n";
            }
            $xml .= "    </row>\n";
        }
    }
    $xml .= "  </table>\n</database>";
    return $xml;
}

function get_server_info($conn) {
    return [
        'version' => $conn->server_info,
        'host' => $conn->host_info,
        'protocol' => $conn->protocol_version,
        'charset' => $conn->character_set_name()
    ];
}

function get_server_status($conn) {
    $status = [];
    $result = $conn->query('SHOW GLOBAL STATUS');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $status[$row['Variable_name']] = $row['Value'];
        }
    }
    return $status;
}

function get_server_variables($conn) {
    $vars = [];
    $res = run_query($conn, "SHOW VARIABLES");
    if ($res['success'] && $res['result'] instanceof mysqli_result) {
        while ($row = $res['result']->fetch_assoc()) {
            $vars[$row['Variable_name']] = $row['Value'];
        }
    }
    return $vars;
}

function get_users($conn) {
    $users = [];
    $res = run_query($conn, "SELECT User, Host FROM mysql.user");
    if ($res['success'] && $res['result'] instanceof mysqli_result) {
        while ($row = $res['result']->fetch_assoc()) {
            $users[] = $row;
        }
    }
    return $users;
}

function get_user_grants($conn, $user, $host) {
    $grants = [];
    $user_esc = $conn->real_escape_string($user);
    $host_esc = $conn->real_escape_string($host);
    $result = $conn->query("SHOW GRANTS FOR '$user_esc'@'$host_esc'");
    if ($result) {
        while ($row = $result->fetch_row()) {
            $grants[] = $row[0];
        }
    }
    return $grants;
}

function get_triggers($conn, $db, $table = null) {
    $conn->select_db($db);
    $triggers = [];
    if ($table) {
        $result = $conn->query("SHOW TRIGGERS LIKE '" . $conn->real_escape_string($table) . "'");
    } else {
        $result = $conn->query('SHOW TRIGGERS');
    }
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $triggers[] = $row;
        }
    }
    return $triggers;
}

function get_events($conn, $db) {
    $conn->select_db($db);
    $events = [];
    $result = $conn->query('SHOW EVENTS');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    }
    return $events;
}

function get_routines($conn, $db) {
    $conn->select_db($db);
    $routines = [];
    $result = $conn->query("SELECT ROUTINE_NAME, ROUTINE_TYPE, DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = '" . $conn->real_escape_string($db) . "'");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $routines[] = $row;
        }
    }
    return $routines;
}

function get_views($conn, $db) {
    $conn->select_db($db);
    $views = [];
    $result = $conn->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    if ($result) {
        while ($row = $result->fetch_row()) {
            $views[] = $row[0];
        }
    }
    return $views;
}

function get_foreign_keys($conn, $db) {
    $keys = [];
    $query = "SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
              FROM information_schema.KEY_COLUMN_USAGE 
              WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($db) . "' 
              AND REFERENCED_TABLE_NAME IS NOT NULL";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $keys[] = $row;
        }
    }
    return $keys;
}

function get_event_scheduler_status($conn) {
    $result = $conn->query("SHOW GLOBAL VARIABLES LIKE 'event_scheduler'");
    if ($result && $row = $result->fetch_assoc()) {
        return strtoupper($row['Value']) === 'ON';
    }
    return false;
}

function render_result_table($result) {
    if (!$result instanceof mysqli_result) {
        return '';
    }
    $html = '<div class="table-wrap"><table class="data-table"><thead><tr>';
    $fields = $result->fetch_fields();
    foreach ($fields as $field) {
        $html .= '<th>' . esc($field->name) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>';
        foreach ($row as $value) {
            $html .= '<td>' . ($value === null ? '<span class="null-val">NULL</span>' : esc($value)) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div>';
    return $html;
}

function flash_message($type, $message, $is_html = false) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message, 'is_html' => $is_html];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function render_sidebar_db_group($dbitem, $tables, $selected_db, $selected_table, $icon = null, $removable = false, $active_tab = '') {
    if ($icon === null) {
        $icon = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.75;"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/></svg>';
    }
    $is_active = $selected_db === $dbitem;
    $is_expanded = $is_active;
    $table_count = count($tables);
    $html = '<div class="sidebar-db-group' . ($is_expanded ? ' expanded' : '') . '" data-db="' . esc($dbitem) . '">';
    $html .= '<div class="sidebar-db-row">';
    $html .= '<button type="button" class="sidebar-toggle" title="Expand / collapse tables" aria-expanded="' . ($is_expanded ? 'true' : 'false') . '">';
    $html .= '<span class="toggle-icon">' . ($is_expanded ? '▼' : '▶') . '</span></button>';
    $html .= '<a href="' . esc(base_url(['db' => $dbitem, 'tab' => 'structure'])) . '" class="sidebar-item db-link' . ($is_active ? ' active' : '') . '" data-name="' . esc($dbitem) . '">';
    $html .= '<span class="icon">' . $icon . '</span><span class="db-name">' . esc($dbitem) . '</span>';
    if ($table_count) {
        $html .= '<span class="table-count">' . $table_count . '</span>';
    }
    $html .= '</a>';
    if ($removable) {
        $html .= '<form method="POST" action="actions.php" class="sidebar-remove-form">';
        $html .= csrf_field();
        $html .= '<input type="hidden" name="action" value="remove_recent">';
        $html .= '<input type="hidden" name="recent_db" value="' . esc($dbitem) . '">';
        if ($selected_db) {
            $html .= '<input type="hidden" name="db" value="' . esc($selected_db) . '">';
        }
        if ($selected_table) {
            $html .= '<input type="hidden" name="table" value="' . esc($selected_table) . '">';
        }
        if ($active_tab) {
            $html .= '<input type="hidden" name="tab" value="' . esc($active_tab) . '">';
        }
        $html .= '<button type="submit" class="sidebar-remove-recent" title="Remove from recent" aria-label="Remove from recent"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>';
        $html .= '</form>';
    }
    $html .= '</div>';
    $html .= '<div class="sidebar-table-list' . ($is_expanded ? '' : ' collapsed') . '">';
    foreach ($tables as $titem) {
        $html .= '<a href="' . esc(base_url(['db' => $dbitem, 'table' => $titem, 'tab' => 'browse'])) . '" class="sidebar-item nested' . ($selected_table === $titem && $is_active ? ' active' : '') . '" data-name="' . esc($titem) . '">';
        $html .= '<span class="icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.75;"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18M3 15h18M9 9v12"/></svg></span>' . esc($titem) . '</a>';
    }
    $html .= '</div></div>';
    return $html;
}

function nav_tabs($level, $db, $table, $active) {
    $tabs = [];
    if ($level === 'server') {
        $tabs = [
            'databases' => 'Databases',
            'sql' => 'SQL',
            'status' => 'Status',
            'variables' => 'Variables',
            'users' => 'User accounts',
            'export' => 'Export',
            'import' => 'Import',
            'history' => 'Query history'
        ];
    } elseif ($level === 'database') {
        $tabs = [
            'structure' => 'Structure',
            'sql' => 'SQL',
            'search' => 'Search',
            'export' => 'Export',
            'import' => 'Import',
            'operations' => 'Operations',
            'routines' => 'Routines',
            'events' => 'Events',
            'triggers' => 'Triggers',
            'designer' => 'Designer'
        ];
    } elseif ($level === 'table') {
        $tabs = [
            'browse' => 'Browse',
            'structure' => 'Structure',
            'sql' => 'SQL',
            'search' => 'Search',
            'insert' => 'Insert',
            'export' => 'Export',
            'import' => 'Import',
            'operations' => 'Operations',
            'triggers' => 'Triggers',
            'indexes' => 'Indexes'
        ];
    }
    $html = '<nav class="tab-nav">';
    foreach ($tabs as $key => $label) {
        $params = ['tab' => $key];
        if ($db) $params['db'] = $db;
        if ($table) $params['table'] = $table;
        $class = $active === $key ? 'tab-link active' : 'tab-link';
        $html .= '<a href="' . esc(base_url($params)) . '" class="' . $class . '">' . esc($label) . '</a>';
    }
    $html .= '</nav>';
    return $html;
}
