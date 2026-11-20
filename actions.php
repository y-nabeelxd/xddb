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

if (!isset($_SESSION['user'])) {
    redirect('index.php');
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSRF validation for state-modifying requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($token)) {
        flash_message('error', 'Security check failed (CSRF token invalid or expired). Please try again.');
        redirect('dashboard.php');
    }
}

$host = $_SESSION['host'];
$user = $_SESSION['user'];
$pass = $_SESSION['pass'];
$port = (int)($_SESSION['port'] ?? 3306);

$conn = new mysqli($host, $user, $pass, '', $port);
if ($conn->connect_error) {
    flash_message('error', 'Connection lost: ' . $conn->connect_error);
    redirect('index.php');
}
$conn->set_charset('utf8mb4');

$db = $_POST['db'] ?? '';
$table = $_POST['table'] ?? '';
$redirect_params = [];
if ($db) $redirect_params['db'] = $db;
if ($table) $redirect_params['table'] = $table;
if (isset($_POST['tab'])) $redirect_params['tab'] = $_POST['tab'];

function action_redirect($params) {
    redirect(base_url($params));
}

try {
switch ($action) {
    case 'check_update':
        unset($_SESSION['xddb_latest_version']);
        unset($_SESSION['xddb_update_last_check']);
        unset($_SESSION['xddb_update_zip']);
        flash_message('success', 'Checked for latest updates.');
        action_redirect($redirect_params);
        break;

    case 'update_app':
        $zip_url = $_SESSION['xddb_update_zip'] ?? '';
        $latest = $_SESSION['xddb_latest_version'] ?? '';
        if (!$zip_url) {
            flash_message('error', 'Update URL not found.');
            redirect('index.php');
        }
        
        $temp_zip = sys_get_temp_dir() . '/xddb_update.zip';
        $temp_extract = sys_get_temp_dir() . '/xddb_extract_' . time();
        
        // Download the zip
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $zip_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'XDDB-Updater');
        $zip_data = curl_exec($ch);
        curl_close($ch);
        
        if ($zip_data !== false) {
            file_put_contents($temp_zip, $zip_data);
            
            $zip = new ZipArchive;
            if ($zip->open($temp_zip) === TRUE) {
                mkdir($temp_extract);
                $zip->extractTo($temp_extract);
                $zip->close();
                
                // GitHub source zips contain a root folder (e.g. xddb-1.0.1)
                $dirs = glob($temp_extract . '/*', GLOB_ONLYDIR);
                $source_dir = !empty($dirs) ? $dirs[0] : $temp_extract;
                
                // Recursive copy function
                function recursive_copy($src, $dst) {
                    $dir = opendir($src);
                    @mkdir($dst);
                    while(( $file = readdir($dir)) ) {
                        if (( $file != '.' ) && ( $file != '..' )) {
                            if ( is_dir($src . '/' . $file) ) {
                                recursive_copy($src . '/' . $file, $dst . '/' . $file);
                            } else {
                                if (!@copy($src . '/' . $file, $dst . '/' . $file)) {
                                    throw new Exception("Failed to copy file: " . $file . ". Check directory permissions.");
                                }
                            }
                        }
                    }
                    closedir($dir);
                }
                
                try {
                    recursive_copy($source_dir, __DIR__);
                } catch (Exception $e) {
                    flash_message('error', $e->getMessage());
                    redirect('dashboard.php');
                }
                
                if (function_exists('opcache_reset')) {
                    opcache_reset();
                }
                if (function_exists('apcu_clear_cache')) {
                    apcu_clear_cache();
                }
                
                // Cleanup
                function recursive_delete($dir) {
                    if (is_dir($dir)) {
                        $objects = scandir($dir);
                        foreach ($objects as $object) {
                            if ($object != "." && $object != "..") {
                                if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
                                    recursive_delete($dir. DIRECTORY_SEPARATOR .$object);
                                else
                                    unlink($dir. DIRECTORY_SEPARATOR .$object);
                            }
                        }
                        rmdir($dir);
                    }
                }
                recursive_delete($temp_extract);
                unlink($temp_zip);
                
                unset($_SESSION['xddb_latest_version']);
                unset($_SESSION['xddb_update_zip']);
                unset($_SESSION['xddb_update_last_check']);
                
                if (function_exists('opcache_reset')) {
                    opcache_reset();
                }
                
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'text' => "XDDB has been successfully updated to v" . $latest . "!"
                ];
                
                // Show world-class animated updating UI
                echo '<!DOCTYPE html>
                <html lang="en" data-theme="dark">
                <head>
                  <meta charset="UTF-8">
                  <meta name="viewport" content="width=device-width, initial-scale=1.0">
                  <title>Updating XDDB...</title>
                  <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body {
                      font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", Roboto, sans-serif;
                      background: #000000;
                      color: #f5f5f7;
                      display: flex;
                      align-items: center;
                      justify-content: center;
                      min-height: 100vh;
                      overflow: hidden;
                      position: relative;
                      -webkit-font-smoothing: antialiased;
                    }
                    .ambient-glow {
                      position: absolute;
                      width: 500px;
                      height: 500px;
                      background: radial-gradient(circle, rgba(10, 132, 255, 0.15) 0%, rgba(0, 0, 0, 0) 70%);
                      top: 50%;
                      left: 50%;
                      transform: translate(-50%, -50%);
                      pointer-events: none;
                    }
                    .update-card {
                      position: relative;
                      width: 90%;
                      max-width: 440px;
                      background: rgba(28, 28, 30, 0.85);
                      border: 1px solid rgba(255, 255, 255, 0.1);
                      border-radius: 20px;
                      padding: 36px 32px;
                      box-shadow: 0 30px 80px rgba(0, 0, 0, 0.8), 0 0 1px rgba(255, 255, 255, 0.2);
                      backdrop-filter: blur(30px);
                      -webkit-backdrop-filter: blur(30px);
                      text-align: center;
                      animation: cardIn 0.5s cubic-bezier(0.25, 1, 0.5, 1);
                    }
                    @keyframes cardIn {
                      from { opacity: 0; transform: translateY(16px) scale(0.97); }
                      to { opacity: 1; transform: translateY(0) scale(1); }
                    }
                    .icon-wrap {
                      width: 64px;
                      height: 64px;
                      margin: 0 auto 20px;
                      border-radius: 18px;
                      background: rgba(10, 132, 255, 0.15);
                      border: 1px solid rgba(10, 132, 255, 0.3);
                      display: flex;
                      align-items: center;
                      justify-content: center;
                      color: #0a84ff;
                      position: relative;
                    }
                    .spinner-ring {
                      position: absolute;
                      inset: -4px;
                      border: 2px solid transparent;
                      border-top-color: #0a84ff;
                      border-right-color: rgba(10, 132, 255, 0.3);
                      border-radius: 22px;
                      animation: spin 1.2s cubic-bezier(0.5, 0.1, 0.5, 0.9) infinite;
                    }
                    @keyframes spin { 100% { transform: rotate(360deg); } }
                    h2 { font-size: 20px; font-weight: 600; letter-spacing: -0.02em; margin-bottom: 6px; }
                    p.sub { font-size: 13px; color: #98989d; margin-bottom: 24px; }
                    .progress-bar-wrap {
                      height: 6px;
                      background: rgba(255, 255, 255, 0.08);
                      border-radius: 3px;
                      overflow: hidden;
                      margin-bottom: 24px;
                    }
                    .progress-bar-fill {
                      height: 100%;
                      width: 0%;
                      background: linear-gradient(90deg, #0a84ff, #5ac8fa);
                      border-radius: 3px;
                      transition: width 0.4s ease;
                    }
                    .steps-list {
                      display: flex;
                      flex-direction: column;
                      gap: 10px;
                      text-align: left;
                      font-size: 13px;
                    }
                    .step-item {
                      display: flex;
                      align-items: center;
                      gap: 10px;
                      color: #636366;
                      transition: color 0.3s ease;
                    }
                    .step-item.active { color: #f5f5f7; font-weight: 500; }
                    .step-item.done { color: #30d158; }
                    .step-icon {
                      width: 18px;
                      height: 18px;
                      border-radius: 50%;
                      display: flex;
                      align-items: center;
                      justify-content: center;
                      background: rgba(255, 255, 255, 0.06);
                      flex-shrink: 0;
                    }
                    .step-item.active .step-icon { background: rgba(10, 132, 255, 0.2); color: #0a84ff; }
                    .step-item.done .step-icon { background: rgba(48, 209, 88, 0.15); color: #30d158; }
                  </style>
                </head>
                <body>
                  <div class="ambient-glow"></div>
                  <div class="update-card">
                    <div class="icon-wrap">
                      <div class="spinner-ring"></div>
                      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </div>
                    <h2>Updating to v' . esc($latest) . '</h2>
                    <p class="sub">Applying enhancements and restarting service</p>
                    <div class="progress-bar-wrap">
                      <div class="progress-bar-fill" id="pFill"></div>
                    </div>
                    <div class="steps-list">
                      <div class="step-item done" id="s1">
                        <span class="step-icon">✓</span>
                        <span>Package downloaded & verified</span>
                      </div>
                      <div class="step-item done" id="s2">
                        <span class="step-icon">✓</span>
                        <span>Files extracted & updated</span>
                      </div>
                      <div class="step-item done" id="s3">
                        <span class="step-icon">✓</span>
                        <span>Opcache & session reset</span>
                      </div>
                      <div class="step-item active" id="s4">
                        <span class="step-icon" id="s4icon">⋯</span>
                        <span>Restarting server & reconnecting...</span>
                      </div>
                    </div>
                  </div>
                  <script>
                    const fill = document.getElementById("pFill");
                    fill.style.width = "40%";
                    setTimeout(() => { fill.style.width = "75%"; }, 800);

                    let attempts = 0;
                    const maxAttempts = 30;
                    function poll() {
                      attempts++;
                      fetch("dashboard.php?t=" + Date.now(), { method: "HEAD", cache: "no-store" })
                        .then(res => {
                          if (res.ok || res.status === 200 || res.status === 302) {
                            fill.style.width = "100%";
                            const s4 = document.getElementById("s4");
                            s4.className = "step-item done";
                            document.getElementById("s4icon").textContent = "✓";
                            setTimeout(() => { window.location.href = "dashboard.php"; }, 400);
                          } else {
                            if (attempts < maxAttempts) setTimeout(poll, 600);
                            else window.location.href = "dashboard.php";
                          }
                        })
                        .catch(() => {
                          if (attempts < maxAttempts) setTimeout(poll, 600);
                          else window.location.href = "dashboard.php";
                        });
                    }
                    setTimeout(poll, 1500);
                  </script>
                </body>
                </html>';
                
                // Fire off the restart in the background, detached, so PHP can flush the output above
                flush();
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
                
                shell_exec('nohup sudo xddb restart > /dev/null 2>&1 &');
                exit;
            } else {
                flash_message('error', 'Failed to open the update package.');
                redirect('dashboard.php');
            }
        } else {
            flash_message('error', 'Failed to download the update package.');
            redirect('dashboard.php');
        }
        break;

    case 'create_database':
        $name = $conn->real_escape_string(trim($_POST['db_name'] ?? ''));
        $charset = $conn->real_escape_string($_POST['charset'] ?? 'utf8mb4');
        $collation = $conn->real_escape_string($_POST['collation'] ?? 'utf8mb4_unicode_ci');
        if ($name) {
            $sql = "CREATE DATABASE `$name` CHARACTER SET $charset COLLATE $collation";
            if ($conn->query($sql)) {
                add_query_history($sql);
                flash_message('success', "Database '$name' created.");
                $redirect_params['db'] = $name;
                $redirect_params['tab'] = 'structure';
            } else {
                flash_message('error', $conn->error);
            }
        }
        break;

    case 'drop_database':
        $name = $conn->real_escape_string($db);
        if ($name && $conn->query("DROP DATABASE `$name`")) {
            add_query_history("DROP DATABASE `$name`");
            flash_message('success', "Database '$name' dropped.");
            unset($redirect_params['db'], $redirect_params['table']);
            $redirect_params['tab'] = 'databases';
        } else {
            flash_message('error', $conn->error);
        }
        break;

    case 'create_table':
        $conn->select_db($db);
        $tname = $conn->real_escape_string(trim($_POST['table_name'] ?? ''));
        $cols = $_POST['col_name'] ?? [];
        $types = $_POST['col_type'] ?? [];
        $lengths = $_POST['col_length'] ?? [];
        $nulls = $_POST['col_null'] ?? [];
        $defaults = $_POST['col_default'] ?? [];
        $extras = $_POST['col_extra'] ?? [];
        $definitions = [];
        for ($i = 0; $i < count($cols); $i++) {
            if (empty($cols[$i])) continue;
            $col = '`' . $conn->real_escape_string($cols[$i]) . '`';
            $type = strtoupper($types[$i] ?? 'VARCHAR');
            $len = $lengths[$i] ?? '';
            if (in_array($type, ['VARCHAR', 'CHAR', 'INT', 'BIGINT', 'SMALLINT', 'TINYINT', 'DECIMAL', 'FLOAT', 'DOUBLE']) && $len) {
                $col .= " $type($len)";
            } else {
                $col .= " $type";
            }
            $col .= isset($nulls[$i]) ? ' NULL' : ' NOT NULL';
            if (!empty($defaults[$i])) {
                $col .= " DEFAULT '" . $conn->real_escape_string($defaults[$i]) . "'";
            }
            if (!empty($extras[$i])) {
                $col .= ' ' . $conn->real_escape_string($extras[$i]);
            }
            $definitions[] = $col;
        }
        if ($tname && count($definitions)) {
            $sql = "CREATE TABLE `$tname` (" . implode(', ', $definitions) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            if ($conn->query($sql)) {
                add_query_history($sql);
                flash_message('success', "Table '$tname' created.");
                $redirect_params['table'] = $tname;
                $redirect_params['tab'] = 'structure';
            } else {
                flash_message('error', $conn->error);
            }
        }
        break;

    case 'drop_table':
        $conn->select_db($db);
        if ($table && $conn->query("DROP TABLE `$table`")) {
            add_query_history("DROP TABLE `$table`");
            flash_message('success', "Table '$table' dropped.");
            unset($redirect_params['table']);
            $redirect_params['tab'] = 'structure';
        } else {
            flash_message('error', $conn->error);
        }
        break;

    case 'bulk_table_action':
        $conn->select_db($db);
        $bulk_action = $_POST['bulk_action'] ?? '';
        $selected_tables = $_POST['selected_tables'] ?? [];
        if (!empty($selected_tables) && $bulk_action) {
            $success_count = 0;
            foreach ($selected_tables as $t) {
                $sql = "";
                if ($bulk_action === 'drop') {
                    $sql = "DROP TABLE `$t`";
                } elseif ($bulk_action === 'empty') {
                    $sql = "TRUNCATE TABLE `$t`";
                }
                
                if ($sql) {
                    if ($conn->query($sql)) {
                        add_query_history($sql);
                        $success_count++;
                    } else {
                        flash_message('error', "Error on table `$t`: " . $conn->error);
                    }
                }
            }
            if ($success_count > 0) {
                $action_name = $bulk_action === 'drop' ? 'dropped' : 'emptied';
                flash_message('success', "$success_count table(s) $action_name successfully.");
            }
            if ($bulk_action === 'drop' && in_array($table, $selected_tables)) {
                unset($redirect_params['table']);
            }
        } else {
            flash_message('error', 'No action or tables selected.');
        }
        $redirect_params['tab'] = 'structure';
        break;

    case 'rename_table':
        $conn->select_db($db);
        $new_name = $conn->real_escape_string(trim($_POST['new_name'] ?? ''));
        if ($table && $new_name && $conn->query("RENAME TABLE `$table` TO `$new_name`")) {
            flash_message('success', "Table renamed to '$new_name'.");
            $redirect_params['table'] = $new_name;
        } else {
            flash_message('error', $conn->error);
        }
        break;

    case 'copy_table':
        $conn->select_db($db);
        $new_name = $conn->real_escape_string(trim($_POST['new_name'] ?? ''));
        $target_db = $conn->real_escape_string($_POST['target_db'] ?? $db);
        if ($table && $new_name) {
            $sql = "CREATE TABLE `$target_db`.`$new_name` LIKE `$db`.`$table`";
            if ($conn->query($sql)) {
                if (isset($_POST['copy_data'])) {
                    $conn->query("INSERT INTO `$target_db`.`$new_name` SELECT * FROM `$db`.`$table`");
                }
                flash_message('success', "Table copied to '$new_name'.");
            } else {
                flash_message('error', $conn->error);
            }
        }
        break;

    case 'add_column':
        $conn->select_db($db);
        $col_name = $conn->real_escape_string(trim($_POST['col_name'] ?? ''));
        $col_type = strtoupper($conn->real_escape_string($_POST['col_type'] ?? 'VARCHAR'));
        $col_length = $conn->real_escape_string($_POST['col_length'] ?? '255');
        $col_null = isset($_POST['col_null']) ? 'NULL' : 'NOT NULL';
        $col_default = $_POST['col_default'] ?? '';
        $after = $_POST['after_col'] ?? '';
        $def = "`$col_name` $col_type($col_length) $col_null";
        if ($col_default !== '') {
            $def .= " DEFAULT '" . $conn->real_escape_string($col_default) . "'";
        }
        if (!empty($_POST['col_extra'])) {
            $def .= ' ' . $conn->real_escape_string($_POST['col_extra']);
        }
        $sql = "ALTER TABLE `$table` ADD $def";
        if ($after) {
            $sql .= " AFTER `" . $conn->real_escape_string($after) . "`";
        }
        if ($conn->query($sql)) {
            add_query_history($sql);
            flash_message('success', "Column '$col_name' added.");
        } else {
            flash_message('error', $conn->error);
        }
        $redirect_params['tab'] = 'structure';
        break;

    case 'drop_column':
        $conn->select_db($db);
        $col = $conn->real_escape_string($_POST['col_name'] ?? '');
        if ($col && $conn->query("ALTER TABLE `$table` DROP COLUMN `$col`")) {
            flash_message('success', "Column '$col' dropped.");
        } else {
            flash_message('error', $conn->error);
        }
        $redirect_params['tab'] = 'structure';
        break;

    case 'create_routine':
        $conn->select_db($db);
        $name = $_POST['routine_name'] ?? '';
        $type = $_POST['routine_type'] ?? 'PROCEDURE';
        $params = $_POST['routine_params'] ?? '';
        $ret = $_POST['routine_return'] ?? '';
        $def = $_POST['routine_def'] ?? '';
        if ($name && $def) {
            $sql = "CREATE $type `$name`($params)";
            if ($type === 'FUNCTION' && $ret) {
                $sql .= " RETURNS $ret";
            }
            $sql .= " $def";
            $res = run_query($conn, $sql);
            if ($res['success']) {
                add_query_history($sql);
                flash_message('success', "Routine '$name' created.");
            } else {
                flash_message('error', $res['error'], true);
            }
        } else {
            flash_message('error', 'Routine name and definition are required.');
        }
        $redirect_params['tab'] = 'routines';
        break;

    case 'create_event':
        $conn->select_db($db);
        $name = $_POST['event_name'] ?? '';
        $schedule = $_POST['event_schedule'] ?? '';
        $def = $_POST['event_def'] ?? '';
        if ($name && $schedule && $def) {
            $sql = "CREATE EVENT `$name` ON SCHEDULE $schedule DO $def";
            $res = run_query($conn, $sql);
            if ($res['success']) {
                add_query_history($sql);
                flash_message('success', "Event '$name' created.");
            } else {
                flash_message('error', $res['error'], true);
            }
        } else {
            flash_message('error', 'Event name, schedule, and definition are required.');
        }
        $redirect_params['tab'] = 'events';
        break;

    case 'create_trigger':
        $conn->select_db($db);
        $name = $_POST['trigger_name'] ?? '';
        $time = $_POST['trigger_time'] ?? 'AFTER';
        $event = $_POST['trigger_event'] ?? 'INSERT';
        $trigger_table = $_POST['trigger_table'] ?? '';
        $def = $_POST['trigger_def'] ?? '';
        if ($name && $trigger_table && $def) {
            $sql = "CREATE TRIGGER `$name` $time $event ON `$trigger_table` FOR EACH ROW $def";
            $res = run_query($conn, $sql);
            if ($res['success']) {
                add_query_history($sql);
                flash_message('success', "Trigger '$name' created.");
            } else {
                flash_message('error', $res['error'], true);
            }
        } else {
            flash_message('error', 'Trigger name, table, and definition are required.');
        }
        $redirect_params['tab'] = 'triggers';
        break;

    case 'toggle_event_scheduler':
        $status = $_POST['status'] === 'ON' ? 'ON' : 'OFF';
        $res = run_query($conn, "SET GLOBAL event_scheduler = $status");
        if ($res['success']) {
            flash_message('success', "Event scheduler set to $status.");
        } else {
            flash_message('error', $res['error'], true);
        }
        $redirect_params['tab'] = 'events';
        break;

    case 'insert_row':
        $conn->select_db($db);
        $fields = $_POST['fields'] ?? [];
        $cols = [];
        $vals = [];
        foreach ($fields as $col => $val) {
            $cols[] = '`' . $conn->real_escape_string($col) . '`';
            if ($val === '' && isset($_POST['nulls'][$col])) {
                $vals[] = 'NULL';
            } else {
                $vals[] = "'" . $conn->real_escape_string($val) . "'";
            }
        }
        if (count($cols)) {
            $sql = "INSERT INTO `$table` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
            if ($conn->query($sql)) {
                add_query_history($sql);
                flash_message('success', 'Row inserted successfully.');
                $redirect_params['tab'] = 'browse';
            } else {
                flash_message('error', $conn->error);
            }
        }
        break;

    case 'update_row':
        $conn->select_db($db);
        $fields = $_POST['fields'] ?? [];
        $pk_col = $_POST['pk_col'] ?? '';
        $pk_val = $_POST['pk_val'] ?? '';
        $sets = [];
        foreach ($fields as $col => $val) {
            if ($col === $pk_col) continue;
            if ($val === '' && isset($_POST['nulls'][$col])) {
                $sets[] = '`' . $conn->real_escape_string($col) . '` = NULL';
            } else {
                $sets[] = '`' . $conn->real_escape_string($col) . '` = \'' . $conn->real_escape_string($val) . '\'';
            }
        }
        if (count($sets) && $pk_col) {
            $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE `" . $conn->real_escape_string($pk_col) . "` = '" . $conn->real_escape_string($pk_val) . "'";
            if ($conn->query($sql)) {
                add_query_history($sql);
                flash_message('success', 'Row updated successfully.');
            } else {
                flash_message('error', $conn->error);
            }
        }
        $redirect_params['tab'] = 'browse';
        break;

    case 'delete_row':
        $conn->select_db($db);
        $pk_col = $_POST['pk_col'] ?? '';
        $pk_val = $_POST['pk_val'] ?? '';
        if ($pk_col) {
            $sql = "DELETE FROM `$table` WHERE `" . $conn->real_escape_string($pk_col) . "` = '" . $conn->real_escape_string($pk_val) . "'";
            if ($conn->query($sql)) {
                add_query_history($sql);
                flash_message('success', 'Row deleted.');
            } else {
                flash_message('error', $conn->error);
            }
        }
        $redirect_params['tab'] = 'browse';
        break;

    case 'bulk_row_action':
        $conn->select_db($db);
        $bulk_action = $_POST['bulk_action'] ?? '';
        $selected_rows = $_POST['selected_rows'] ?? [];
        $pk_col = $_POST['pk_col'] ?? '';
        
        if (!empty($selected_rows) && $bulk_action && $pk_col) {
            if ($bulk_action === 'delete') {
                $escaped_rows = array_map(function($val) use ($conn) {
                    return "'" . $conn->real_escape_string($val) . "'";
                }, $selected_rows);
                
                $sql = "DELETE FROM `$table` WHERE `" . $conn->real_escape_string($pk_col) . "` IN (" . implode(',', $escaped_rows) . ")";
                if ($conn->query($sql)) {
                    add_query_history($sql);
                    flash_message('success', count($selected_rows) . ' row(s) deleted.');
                } else {
                    flash_message('error', $conn->error);
                }
            } elseif ($bulk_action === 'export') {
                $escaped_rows = array_map(function($val) use ($conn) {
                    return "'" . $conn->real_escape_string($val) . "'";
                }, $selected_rows);
                $_SESSION['export_query'] = "SELECT * FROM `$table` WHERE `" . $conn->real_escape_string($pk_col) . "` IN (" . implode(',', $escaped_rows) . ")";
                $redirect_params['tab'] = 'export';
            } elseif ($bulk_action === 'copy' || $bulk_action === 'edit') {
                flash_message('info', 'Bulk copy/edit is not fully implemented yet.');
            }
        } else {
            flash_message('error', 'No action or rows selected.');
        }
        if ($bulk_action !== 'export') {
            $redirect_params['tab'] = 'browse';
        }
        break;

    case 'create_view_from_table':
        $conn->select_db($db);
        $view_name = $_POST['view_name'] ?? '';
        if ($view_name) {
            $sql = "CREATE VIEW `" . $conn->real_escape_string($view_name) . "` AS SELECT * FROM `$table`";
            if ($conn->query($sql)) {
                add_query_history($sql);
                flash_message('success', "View '$view_name' created successfully.");
            } else {
                flash_message('error', $conn->error);
            }
        }
        $redirect_params['tab'] = 'browse';
        break;

    case 'copy_row':
        $conn->select_db($db);
        $pk_col = $_POST['pk_col'] ?? '';
        $pk_val = $_POST['pk_val'] ?? '';
        if ($pk_col) {
            $result = $conn->query("SELECT * FROM `$table` WHERE `" . $conn->real_escape_string($pk_col) . "` = '" . $conn->real_escape_string($pk_val) . "'");
            if ($result && $row = $result->fetch_assoc()) {
                unset($row[$pk_col]);
                $cols = [];
                $vals = [];
                foreach ($row as $k => $v) {
                    $cols[] = '`' . $conn->real_escape_string($k) . '`';
                    $vals[] = $v === null ? 'NULL' : "'" . $conn->real_escape_string($v) . "'";
                }
                $sql = "INSERT INTO `$table` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
                if ($conn->query($sql)) {
                    flash_message('success', 'Row copied.');
                } else {
                    flash_message('error', $conn->error);
                }
            }
        }
        $redirect_params['tab'] = 'browse';
        break;

    case 'run_sql':
        $sql = $_POST['sql_query'] ?? '';
        if ($db) {
            $conn->select_db($db);
        }
        if ($sql) {
            add_query_history($sql);
            $queries = array_filter(array_map('trim', explode(';', $sql)));
            $last_result = null;
            $messages = [];
            $use_detected = null;
            $error_encountered = false;
            foreach ($queries as $q) {
                if ($q === '') continue;
                $upper = strtoupper(ltrim($q));
                if (strpos($upper, 'USE ') === 0) {
                    $db_match = preg_match('/^USE\s+([^\s;]+)/i', $q, $m);
                    if ($db_match) {
                        $use_detected = trim($m[1], '`');
                        $conn->select_db($use_detected);
                        $messages[] = 'Database changed to ' . $use_detected . '.';
                    }
                    continue;
                }
                $res = run_query($conn, $q);
                if (!$res['success']) {
                    flash_message('error', $res['error'], true);
                    $_SESSION['last_sql'] = $sql;
                    if ($use_detected) $redirect_params['db'] = $use_detected;
                    action_redirect($redirect_params);
                }
                if ($res['result'] instanceof mysqli_result) {
                    $last_result = $res['result'];
                } else {
                    $messages[] = $res['affected'] . ' row(s) affected.';
                }
            }
            if ($use_detected) {
                $redirect_params['db'] = $use_detected;
            }
            if ($last_result) {
                $_SESSION['sql_result'] = [];
                while ($row = $last_result->fetch_assoc()) {
                    $_SESSION['sql_result'][] = $row;
                }
                $_SESSION['sql_columns'] = [];
                $last_result->data_seek(0);
                while ($field = $last_result->fetch_field()) {
                    $_SESSION['sql_columns'][] = $field->name;
                }
            }
            if ($messages) {
                flash_message('success', implode(' ', $messages));
            } else {
                flash_message('success', 'Query executed successfully.');
            }
            $_SESSION['last_sql'] = '';
        }
        break;

    case 'search_table':
        $conn->select_db($db);
        $search_col = $_POST['search_col'] ?? '';
        $search_val = $_POST['search_val'] ?? '';
        $search_type = $_POST['search_type'] ?? 'like';
        if ($search_col && $search_val !== '') {
            $col = '`' . $conn->real_escape_string($search_col) . '`';
            $val = $conn->real_escape_string($search_val);
            switch ($search_type) {
                case 'exact':
                    $where = "$col = '$val'";
                    break;
                case 'begin':
                    $where = "$col LIKE '$val%'";
                    break;
                default:
                    $where = "$col LIKE '%$val%'";
            }
            $result = $conn->query("SELECT * FROM `$table` WHERE $where LIMIT 100");
            if ($result) {
                $_SESSION['search_result'] = [];
                while ($row = $result->fetch_assoc()) {
                    $_SESSION['search_result'][] = $row;
                }
                $_SESSION['search_columns'] = [];
                while ($field = $result->fetch_field()) {
                    $_SESSION['search_columns'][] = $field->name;
                }
            }
        }
        $redirect_params['tab'] = 'search';
        break;

    case 'optimize_table':
        $conn->select_db($db);
        if ($conn->query("OPTIMIZE TABLE `$table`")) {
            flash_message('success', "Table '$table' optimized.");
        } else {
            flash_message('error', $conn->error);
        }
        $redirect_params['tab'] = 'operations';
        break;

    case 'db_search':
        $conn->select_db($db);
        $search_str = $_POST['search_str'] ?? '';
        $search_type = $_POST['search_type'] ?? 'like';
        $search_tables = $_POST['search_tables'] ?? [];
        
        $_SESSION['db_search'] = [
            'str' => $search_str,
            'type' => $search_type,
            'tables' => $search_tables
        ];
        $_SESSION['db_search_results'] = [];
        
        if ($search_str !== '' && !empty($search_tables)) {
            $search_val = $conn->real_escape_string($search_str);
            
            foreach ($search_tables as $t) {
                $cols = get_table_structure($conn, $db, $t);
                if (empty($cols)) continue;
                
                $where = [];
                foreach ($cols as $col) {
                    $cname = '`' . $conn->real_escape_string($col['Field']) . '`';
                    if ($search_type === 'exact') {
                        $where[] = "$cname = '$search_val'";
                    } elseif ($search_type === 'regex') {
                        $where[] = "$cname REGEXP '$search_val'";
                    } else {
                        // like - at least one of the words
                        // phpMyAdmin splits by space, but for simplicity we'll just do LIKE on the whole string unless we want to split
                        $where[] = "$cname LIKE '%$search_val%'";
                    }
                }
                
                if (!empty($where)) {
                    $sql = "SELECT COUNT(*) as cnt FROM `$t` WHERE " . implode(' OR ', $where);
                    $res = $conn->query($sql);
                    if ($res) {
                        $row = $res->fetch_assoc();
                        $_SESSION['db_search_results'][$t] = (int)$row['cnt'];
                    } else {
                        $_SESSION['db_search_results'][$t] = false;
                    }
                }
            }
        }
        $redirect_params['tab'] = 'search';
        break;
        
    case 'view_db_search_results':
        $conn->select_db($db);
        $search_str = $_SESSION['db_search']['str'] ?? '';
        $search_type = $_SESSION['db_search']['type'] ?? 'like';
        
        if ($search_str !== '') {
            $search_val = $conn->real_escape_string($search_str);
            $cols = get_table_structure($conn, $db, $table);
            $where = [];
            foreach ($cols as $col) {
                $cname = '`' . $conn->real_escape_string($col['Field']) . '`';
                if ($search_type === 'exact') {
                    $where[] = "$cname = '$search_val'";
                } elseif ($search_type === 'regex') {
                    $where[] = "$cname REGEXP '$search_val'";
                } else {
                    $where[] = "$cname LIKE '%$search_val%'";
                }
            }
            if (!empty($where)) {
                $sql = "SELECT * FROM `$table` WHERE " . implode(' OR ', $where);
                $_SESSION['last_sql'] = $sql;
                $res = run_query($conn, $sql);
                if ($res['success']) {
                    $_SESSION['sql_result'] = [];
                    while ($row = $res['result']->fetch_assoc()) {
                        $_SESSION['sql_result'][] = $row;
                    }
                    $_SESSION['sql_columns'] = [];
                    $res['result']->data_seek(0);
                    while ($field = $res['result']->fetch_field()) {
                        $_SESSION['sql_columns'][] = $field->name;
                    }
                }
            }
        }
        $redirect_params['tab'] = 'sql'; // Using the SQL tab to show results since it has the result grid
        break;

    case 'delete_db_search_results':
        $conn->select_db($db);
        $search_str = $_SESSION['db_search']['str'] ?? '';
        $search_type = $_SESSION['db_search']['type'] ?? 'like';
        
        if ($search_str !== '') {
            $search_val = $conn->real_escape_string($search_str);
            $cols = get_table_structure($conn, $db, $table);
            $where = [];
            foreach ($cols as $col) {
                $cname = '`' . $conn->real_escape_string($col['Field']) . '`';
                if ($search_type === 'exact') {
                    $where[] = "$cname = '$search_val'";
                } elseif ($search_type === 'regex') {
                    $where[] = "$cname REGEXP '$search_val'";
                } else {
                    $where[] = "$cname LIKE '%$search_val%'";
                }
            }
            if (!empty($where)) {
                $sql = "DELETE FROM `$table` WHERE " . implode(' OR ', $where);
                if ($conn->query($sql)) {
                    add_query_history($sql);
                    flash_message('success', "Deleted matches in table '$table'.");
                } else {
                    flash_message('error', "Error deleting from table '$table': " . $conn->error);
                }
            }
        }
        $redirect_params['tab'] = 'search';
        break;

    case 'repair_table':
        $conn->select_db($db);
        if ($conn->query("REPAIR TABLE `$table`")) {
            flash_message('success', "Table '$table' repaired.");
        } else {
            flash_message('error', $conn->error);
        }
        $redirect_params['tab'] = 'operations';
        break;
    case 'check_table':
        $conn->select_db($db);
        if ($conn->query("CHECK TABLE `$table`")) {
            flash_message('success', "Table '$table' checked.");
        } else {
            flash_message('error', $conn->error);
        }
        $redirect_params['tab'] = 'operations';
        break;

    case 'analyze_table':
        $conn->select_db($db);
        if ($conn->query("ANALYZE TABLE `$table`")) {
            flash_message('success', "Table '$table' analyzed.");
        } else {
            flash_message('error', $conn->error);
        }
        $redirect_params['tab'] = 'operations';
        break;

    case 'change_engine':
        $conn->select_db($db);
        $engine = $conn->real_escape_string($_POST['engine'] ?? 'InnoDB');
        if ($conn->query("ALTER TABLE `$table` ENGINE = $engine")) {
            flash_message('success', "Engine changed to $engine.");
        } else {
            flash_message('error', $conn->error);
        }
        $redirect_params['tab'] = 'operations';
        break;

    case 'create_user':
        $new_user = $conn->real_escape_string(trim($_POST['new_user'] ?? ''));
        $new_host = $conn->real_escape_string(trim($_POST['new_host'] ?? '%'));
        $new_pass = $conn->real_escape_string(trim($_POST['new_pass'] ?? ''));
        if ($new_user && $new_pass) {
            $sql = "CREATE USER '$new_user'@'$new_host' IDENTIFIED BY '$new_pass'";
            if ($conn->query($sql)) {
                flash_message('success', "User '$new_user'@'$new_host' created.");
            } else {
                flash_message('error', $conn->error);
            }
        }
        $redirect_params['tab'] = 'users';
        break;

    case 'drop_user':
        $drop_user = $conn->real_escape_string($_POST['drop_user'] ?? '');
        $drop_host = $conn->real_escape_string($_POST['drop_host'] ?? '');
        if ($drop_user) {
            if ($conn->query("DROP USER '$drop_user'@'$drop_host'")) {
                flash_message('success', "User dropped.");
            } else {
                flash_message('error', $conn->error);
            }
        }
        $redirect_params['tab'] = 'users';
        break;

    case 'grant_privileges':
        $grant_user = $conn->real_escape_string($_POST['grant_user'] ?? '');
        $grant_host = $conn->real_escape_string($_POST['grant_host'] ?? '');
        $privileges = $_POST['privileges'] ?? [];
        $grant_db = $conn->real_escape_string($_POST['grant_db'] ?? '*');
        if ($grant_user && count($privileges)) {
            $priv_str = implode(', ', array_map(function($p) use ($conn) {
                return $conn->real_escape_string(strtoupper($p));
            }, $privileges));
            $sql = "GRANT $priv_str ON `$grant_db`.* TO '$grant_user'@'$grant_host'";
            if ($conn->query($sql)) {
                $conn->query('FLUSH PRIVILEGES');
                flash_message('success', 'Privileges granted.');
            } else {
                flash_message('error', $conn->error);
            }
        }
        $redirect_params['tab'] = 'users';
        break;

    case 'toggle_favorite':
        $fav = $_POST['fav_db'] ?? '';
        if ($fav) {
            if (in_array($fav, $_SESSION['favorites'], true)) {
                $_SESSION['favorites'] = array_values(array_diff($_SESSION['favorites'], [$fav]));
            } else {
                $_SESSION['favorites'][] = $fav;
            }
            $redirect_params['db'] = $fav;
            $redirect_params['tab'] = 'structure';
        }
        break;

    case 'remove_recent':
        $recent_db = $_POST['recent_db'] ?? '';
        if ($recent_db && in_array($recent_db, $_SESSION['recent'], true)) {
            $_SESSION['recent'] = array_values(array_diff($_SESSION['recent'], [$recent_db]));
        }
        break;

    case 'import_sql':
        $conn->select_db($db);
        $sql_content = '';
        if (!empty($_FILES['import_file']['tmp_name'])) {
            $sql_content = file_get_contents($_FILES['import_file']['tmp_name']);
        } elseif (!empty($_POST['import_text'])) {
            $sql_content = $_POST['import_text'];
        }
        if ($sql_content) {
            $queries = array_filter(array_map('trim', explode(';', $sql_content)));
            $count = 0;
            foreach ($queries as $q) {
                if ($q === '') continue;
                if ($conn->query($q)) $count++;
            }
            add_query_history("-- Import: $count queries executed");
            flash_message('success', "$count queries imported successfully.");
        }
        $redirect_params['tab'] = 'import';
        break;

    case 'create_user':
        $new_user = $conn->real_escape_string($_POST['new_user'] ?? '');
        $new_host = $conn->real_escape_string($_POST['new_host'] ?? '%');
        $new_pass = $conn->real_escape_string($_POST['new_pass'] ?? '');
        $privs = $_POST['privs'] ?? ['ALL PRIVILEGES'];
        
        if ($new_user && $new_pass) {
            $sql = "CREATE USER '$new_user'@'$new_host' IDENTIFIED BY '$new_pass'";
            if ($conn->query($sql)) {
                $priv_str = in_array('ALL PRIVILEGES', $privs) ? 'ALL PRIVILEGES' : implode(', ', array_map([$conn, 'real_escape_string'], $privs));
                if ($priv_str) {
                    $conn->query("GRANT $priv_str ON *.* TO '$new_user'@'$new_host' WITH GRANT OPTION");
                }
                $conn->query("FLUSH PRIVILEGES");
                flash_message('success', "User '$new_user'@'$new_host' created and privileges granted.");
            } else {
                flash_message('error', $conn->error);
            }
        } else {
            flash_message('error', "Username and Password are required.");
        }
        $redirect_params['tab'] = 'users';
        break;

    case 'drop_user':
        $drop_user = $conn->real_escape_string($_POST['drop_user'] ?? '');
        $drop_host = $conn->real_escape_string($_POST['drop_host'] ?? '%');
        if ($drop_user) {
            if ($conn->query("DROP USER '$drop_user'@'$drop_host'")) {
                flash_message('success', "User '$drop_user'@'$drop_host' deleted.");
            } else {
                flash_message('error', $conn->error);
            }
        }
        $redirect_params['tab'] = 'users';
        break;

    case 'import_csv':
        $conn->select_db($db);
        if (!empty($_FILES['csv_file']['tmp_name'])) {
            $has_headers = isset($_POST['has_headers']);
            $truncate = isset($_POST['truncate_first']);
            
            if ($truncate) {
                $conn->query("TRUNCATE TABLE `$table`");
            }
            
            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $row_idx = 0;
            $success = 0;
            
            $columns = [];
            $res = $conn->query("SHOW COLUMNS FROM `$table`");
            while ($row = $res->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            $col_map = null;

            while (($data = fgetcsv($file)) !== false) {
                if ($has_headers && $row_idx === 0) {
                    $col_map = [];
                    foreach ($data as $i => $h) {
                        $h_trim = trim($h);
                        if (in_array($h_trim, $columns)) {
                            $col_map[$i] = $h_trim;
                        }
                    }
                    $row_idx++;
                    continue;
                }
                
                $cols_to_insert = [];
                $vals = [];
                
                if ($col_map) {
                    if (empty($col_map)) continue; // No matching headers
                    foreach ($col_map as $i => $col_name) {
                        $cols_to_insert[] = "`" . $conn->real_escape_string($col_name) . "`";
                        $vals[] = isset($data[$i]) ? "'" . $conn->real_escape_string($data[$i]) . "'" : "NULL";
                    }
                } else {
                    for ($i = 0; $i < count($columns); $i++) {
                        $cols_to_insert[] = "`" . $conn->real_escape_string($columns[$i]) . "`";
                        $vals[] = isset($data[$i]) ? "'" . $conn->real_escape_string($data[$i]) . "'" : "NULL";
                    }
                }
                
                $sql = "INSERT INTO `$table` (" . implode(',', $cols_to_insert) . ") VALUES (" . implode(',', $vals) . ")";
                if ($conn->query($sql)) {
                    $success++;
                }
                $row_idx++;
            }
            fclose($file);
            flash_message('success', "Imported $success rows from CSV.");
        }
        $redirect_params['tab'] = 'browse';
        break;

    case 'add_index':
        $conn->select_db($db);
        $idx_name = $conn->real_escape_string(trim($_POST['idx_name'] ?? ''));
        $idx_col = $conn->real_escape_string(trim($_POST['idx_col'] ?? ''));
        $idx_type = $_POST['idx_type'] ?? 'INDEX';
        if ($idx_col) {
            $type_map = ['PRIMARY' => 'ADD PRIMARY KEY', 'UNIQUE' => 'ADD UNIQUE', 'FULLTEXT' => 'ADD FULLTEXT', 'INDEX' => 'ADD INDEX'];
            $add = $type_map[$idx_type] ?? 'ADD INDEX';
            $name_part = $idx_type === 'PRIMARY' ? "(`$idx_col`)" : ($idx_name ? " `$idx_name` (`$idx_col`)" : " (`$idx_col`)");
            $sql = "ALTER TABLE `$table` $add$name_part";
            if ($conn->query($sql)) {
                flash_message('success', 'Index added.');
            } else {
                flash_message('error', $conn->error);
            }
        }
        $redirect_params['tab'] = 'indexes';
        break;
}
} catch (Throwable $e) {
    flash_message('error', $e->getMessage());
}

action_redirect($redirect_params);
