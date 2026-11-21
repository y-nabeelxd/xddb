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

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

if (!isset($_SESSION['user'])) {
    redirect('index.php');
}

if (!extension_loaded('mysqli') || !class_exists('mysqli')) {
    flash_message('error', 'PHP MySQLi extension is not enabled in php.ini.');
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
            $start_time = microtime(true);
            if ($conn->query($sql)) {
                $dur = (microtime(true) - $start_time) * 1000;
                add_query_history($sql, $dur, [
                    'type' => 'CREATE_DB',
                    'db' => $name,
                    'summary' => "Created database `$name` with charset $charset / $collation",
                    'after' => ['database' => $name, 'charset' => $charset, 'collation' => $collation],
                    'status' => 'success'
                ]);
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
        if ($name) {
            $sql = "DROP DATABASE `$name`";
            $start_time = microtime(true);
            if ($conn->query($sql)) {
                $dur = (microtime(true) - $start_time) * 1000;
                add_query_history($sql, $dur, [
                    'type' => 'DROP_DB',
                    'db' => $name,
                    'summary' => "Dropped database `$name` and all contained tables",
                    'before' => ['database' => $name],
                    'status' => 'success'
                ]);
                flash_message('success', "Database '$name' dropped.");
                unset($redirect_params['db'], $redirect_params['table']);
                $redirect_params['tab'] = 'databases';
            } else {
                flash_message('error', $conn->error);
            }
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
            $start_time = microtime(true);
            if ($conn->query($sql)) {
                $dur = (microtime(true) - $start_time) * 1000;
                add_query_history($sql, $dur, [
                    'type' => 'CREATE_TABLE',
                    'db' => $db,
                    'table' => $tname,
                    'summary' => "Created table `$tname` with " . count($definitions) . " column(s)",
                    'after' => ['columns' => $definitions],
                    'status' => 'success'
                ]);
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
        if ($table) {
            $is_view_entity = is_view($conn, $db, $table);
            $drop_sql = $is_view_entity ? "DROP VIEW `$table`" : "DROP TABLE `$table`";
            $start_time = microtime(true);
            if ($conn->query($drop_sql)) {
                $dur = (microtime(true) - $start_time) * 1000;
                $entity_label = $is_view_entity ? "View" : "Table";
                add_query_history($drop_sql, $dur, [
                    'type' => $is_view_entity ? 'DROP_VIEW' : 'DROP_TABLE',
                    'db' => $db,
                    'table' => $table,
                    'summary' => "Dropped $entity_label `$table`",
                    'before' => ['entity' => $table, 'type' => $entity_label],
                    'status' => 'success'
                ]);
                flash_message('success', "$entity_label '$table' dropped.");
                unset($redirect_params['table']);
                $redirect_params['tab'] = 'structure';
            } else {
                // If DROP TABLE failed because the entity is actually a VIEW (MySQL errno 1051)
                if ($conn->errno == 1051 && !$is_view_entity) {
                    $start_time = microtime(true);
                    if ($conn->query("DROP VIEW `$table`")) {
                        $dur = (microtime(true) - $start_time) * 1000;
                        add_query_history("DROP VIEW `$table`", $dur, [
                            'type' => 'DROP_VIEW',
                            'db' => $db,
                            'table' => $table,
                            'summary' => "Dropped View `$table`",
                            'before' => ['entity' => $table, 'type' => 'View'],
                            'status' => 'success'
                        ]);
                        flash_message('success', "View '$table' dropped.");
                        unset($redirect_params['table']);
                        $redirect_params['tab'] = 'structure';
                        break;
                    }
                }
                flash_message('error', $conn->error);
            }
        }
        break;

    case 'bulk_table_action':
        $conn->select_db($db);
        $bulk_action = $_POST['bulk_action'] ?? '';
        $selected_tables = $_POST['selected_tables'] ?? [];
        if (!empty($selected_tables) && $bulk_action) {
            $success_count = 0;
            $views_in_db = get_views($conn, $db);
            foreach ($selected_tables as $t) {
                $is_v = in_array($t, $views_in_db, true);
                $sql = "";
                if ($bulk_action === 'drop') {
                    $sql = $is_v ? "DROP VIEW `$t`" : "DROP TABLE `$t`";
                } elseif ($bulk_action === 'empty') {
                    if ($is_v) {
                        continue; // Views cannot be truncated in MySQL
                    }
                    $sql = "TRUNCATE TABLE `$t`";
                }
                
                if ($sql) {
                    $start_time = microtime(true);
                    if ($conn->query($sql)) {
                        $dur = (microtime(true) - $start_time) * 1000;
                        add_query_history($sql, $dur, [
                            'type' => $bulk_action === 'drop' ? ($is_v ? 'DROP_VIEW' : 'DROP_TABLE') : 'TRUNCATE',
                            'db' => $db,
                            'table' => $t,
                            'summary' => ($bulk_action === 'drop' ? "Dropped " . ($is_v ? "view" : "table") : "Truncated (emptied) table") . " `$t`",
                            'status' => 'success'
                        ]);
                        $success_count++;
                    } else {
                        // Fallback attempt for views on drop
                        if ($bulk_action === 'drop' && $conn->errno == 1051) {
                            $start_time = microtime(true);
                            if ($conn->query("DROP VIEW `$t`")) {
                                $dur = (microtime(true) - $start_time) * 1000;
                                add_query_history("DROP VIEW `$t`", $dur, [
                                    'type' => 'DROP_VIEW',
                                    'db' => $db,
                                    'table' => $t,
                                    'summary' => "Dropped view `$t`",
                                    'status' => 'success'
                                ]);
                                $success_count++;
                                continue;
                            }
                        }
                        flash_message('error', "Error on table `$t`: " . $conn->error);
                    }
                }
            }
            if ($success_count > 0) {
                $action_name = $bulk_action === 'drop' ? 'dropped' : 'emptied';
                flash_message('success', "$success_count item(s) $action_name successfully.");
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
        if ($table && $new_name) {
            $sql = "RENAME TABLE `$table` TO `$new_name`";
            $start_time = microtime(true);
            if ($conn->query($sql)) {
                $dur = (microtime(true) - $start_time) * 1000;
                add_query_history($sql, $dur, [
                    'type' => 'ALTER_TABLE',
                    'db' => $db,
                    'table' => $new_name,
                    'summary' => "Renamed table `$table` to `$new_name`",
                    'diff' => [['field' => 'table_name', 'old' => $table, 'new' => $new_name]],
                    'before' => ['table' => $table],
                    'after' => ['table' => $new_name],
                    'status' => 'success'
                ]);
                flash_message('success', "Table renamed to '$new_name'.");
                $redirect_params['table'] = $new_name;
            } else {
                flash_message('error', $conn->error);
            }
        }
        break;

    case 'copy_table':
        $conn->select_db($db);
        $new_name = $conn->real_escape_string(trim($_POST['new_name'] ?? ''));
        $target_db = $conn->real_escape_string($_POST['target_db'] ?? $db);
        if ($table && $new_name) {
            $sql = "CREATE TABLE `$target_db`.`$new_name` LIKE `$db`.`$table`";
            $start_time = microtime(true);
            if ($conn->query($sql)) {
                $copied_data = false;
                if (isset($_POST['copy_data'])) {
                    $conn->query("INSERT INTO `$target_db`.`$new_name` SELECT * FROM `$db`.`$table`");
                    $copied_data = true;
                }
                $dur = (microtime(true) - $start_time) * 1000;
                add_query_history($sql, $dur, [
                    'type' => 'COPY_TABLE',
                    'db' => $target_db,
                    'table' => $new_name,
                    'summary' => "Cloned table `$db`.`$table` to `$target_db`.`$new_name`" . ($copied_data ? " (structure and data)" : " (structure only)"),
                    'before' => ['source_db' => $db, 'source_table' => $table],
                    'after' => ['target_db' => $target_db, 'target_table' => $new_name, 'with_data' => $copied_data],
                    'status' => 'success'
                ]);
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
        $start_time = microtime(true);
        if ($conn->query($sql)) {
            $dur = (microtime(true) - $start_time) * 1000;
            add_query_history($sql, $dur, [
                'type' => 'ALTER_TABLE',
                'db' => $db,
                'table' => $table,
                'summary' => "Added column `$col_name` ($col_type" . ($col_length ? "($col_length)" : "") . ") to `$table`",
                'after' => ['column' => $col_name, 'type' => $col_type, 'length' => $col_length, 'null' => $col_null, 'default' => $col_default],
                'status' => 'success'
            ]);
            flash_message('success', "Column '$col_name' added.");
        } else {
            flash_message('error', $conn->error);
        }
        $redirect_params['tab'] = 'structure';
        break;

    case 'drop_column':
        $conn->select_db($db);
        $col = $conn->real_escape_string($_POST['col_name'] ?? '');
        if ($col) {
            $sql = "ALTER TABLE `$table` DROP COLUMN `$col`";
            $start_time = microtime(true);
            if ($conn->query($sql)) {
                $dur = (microtime(true) - $start_time) * 1000;
                add_query_history($sql, $dur, [
                    'type' => 'ALTER_TABLE',
                    'db' => $db,
                    'table' => $table,
                    'summary' => "Dropped column `$col` from table `$table`",
                    'before' => ['column' => $col],
                    'status' => 'success'
                ]);
                flash_message('success', "Column '$col' dropped.");
            } else {
                flash_message('error', $conn->error);
            }
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
        $after_row = [];
        foreach ($fields as $col => $val) {
            $cols[] = '`' . str_replace('`', '``', $col) . '`';
            if ($val === '' && isset($_POST['nulls'][$col])) {
                $vals[] = 'NULL';
                $after_row[$col] = null;
            } else {
                $vals[] = "'" . $conn->real_escape_string($val) . "'";
                $after_row[$col] = $val;
            }
        }
        if (count($cols)) {
            $sql = "INSERT INTO `" . str_replace('`', '``', $table) . "` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
            $start_time = microtime(true);
            if ($conn->query($sql)) {
                $dur = (microtime(true) - $start_time) * 1000;
                $ins_id = $conn->insert_id;
                if ($ins_id && !empty($pk_col) && empty($after_row[$pk_col])) {
                    $after_row[$pk_col] = $ins_id;
                }
                add_query_history($sql, $dur, [
                    'type' => 'INSERT',
                    'db' => $db,
                    'table' => $table,
                    'summary' => "Inserted new row into `$table`" . ($ins_id ? " (ID: $ins_id)" : ""),
                    'before' => null,
                    'after' => $after_row,
                    'diff' => array_map(function($k, $v) { return ['field' => $k, 'old' => null, 'new' => $v]; }, array_keys($after_row), array_values($after_row)),
                    'affected_rows' => 1,
                    'status' => 'success'
                ]);
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

        $before_row = null;
        if ($pk_col && $pk_val) {
            $b_res = $conn->query("SELECT * FROM `" . str_replace('`', '``', $table) . "` WHERE `" . str_replace('`', '``', $pk_col) . "` = '" . $conn->real_escape_string($pk_val) . "'");
            if ($b_res) $before_row = $b_res->fetch_assoc();
        }

        $sets = [];
        $after_row = $before_row ?: [];
        $diff = [];
        foreach ($fields as $col => $val) {
            if ($col === $pk_col) continue;
            $new_val = ($val === '' && isset($_POST['nulls'][$col])) ? null : $val;
            $old_val = $before_row[$col] ?? null;
            $after_row[$col] = $new_val;
            if ($new_val === null) {
                $sets[] = '`' . str_replace('`', '``', $col) . '` = NULL';
            } else {
                $sets[] = '`' . str_replace('`', '``', $col) . '` = \'' . $conn->real_escape_string($val) . '\'';
            }
            if ($old_val != $new_val) {
                $diff[] = [
                    'field' => $col,
                    'old' => $old_val,
                    'new' => $new_val
                ];
            }
        }
        if (count($sets) && $pk_col) {
            $sql = "UPDATE `" . str_replace('`', '``', $table) . "` SET " . implode(', ', $sets) . " WHERE `" . str_replace('`', '``', $pk_col) . "` = '" . $conn->real_escape_string($pk_val) . "'";
            $start_time = microtime(true);
            if ($conn->query($sql)) {
                $dur = (microtime(true) - $start_time) * 1000;
                add_query_history($sql, $dur, [
                    'type' => 'UPDATE',
                    'db' => $db,
                    'table' => $table,
                    'summary' => "Updated row in `$table` (`$pk_col` = '$pk_val')" . (count($diff) ? " — " . count($diff) . " field(s) modified" : ""),
                    'diff' => $diff,
                    'before' => $before_row,
                    'after' => $after_row,
                    'affected_rows' => 1,
                    'status' => 'success'
                ]);
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
            $before_row = null;
            $b_res = $conn->query("SELECT * FROM `" . str_replace('`', '``', $table) . "` WHERE `" . str_replace('`', '``', $pk_col) . "` = '" . $conn->real_escape_string($pk_val) . "'");
            if ($b_res) $before_row = $b_res->fetch_assoc();

            $sql = "DELETE FROM `" . str_replace('`', '``', $table) . "` WHERE `" . str_replace('`', '``', $pk_col) . "` = '" . $conn->real_escape_string($pk_val) . "'";
            $start_time = microtime(true);
            if ($conn->query($sql)) {
                $dur = (microtime(true) - $start_time) * 1000;
                add_query_history($sql, $dur, [
                    'type' => 'DELETE',
                    'db' => $db,
                    'table' => $table,
                    'summary' => "Deleted row from `$table` (`$pk_col` = '$pk_val')",
                    'before' => $before_row,
                    'after' => null,
                    'diff' => $before_row ? array_map(function($k, $v) { return ['field' => $k, 'old' => $v, 'new' => null]; }, array_keys($before_row), array_values($before_row)) : [],
                    'affected_rows' => 1,
                    'status' => 'success'
                ]);
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

                $deleted_rows_list = [];
                $d_res = $conn->query("SELECT * FROM `" . str_replace('`', '``', $table) . "` WHERE `" . str_replace('`', '``', $pk_col) . "` IN (" . implode(',', $escaped_rows) . ")");
                if ($d_res) {
                    while ($r = $d_res->fetch_assoc()) $deleted_rows_list[] = $r;
                }
                
                $sql = "DELETE FROM `" . str_replace('`', '``', $table) . "` WHERE `" . str_replace('`', '``', $pk_col) . "` IN (" . implode(',', $escaped_rows) . ")";
                $start_time = microtime(true);
                if ($conn->query($sql)) {
                    $dur = (microtime(true) - $start_time) * 1000;
                    add_query_history($sql, $dur, [
                        'type' => 'DELETE',
                        'db' => $db,
                        'table' => $table,
                        'summary' => "Bulk deleted " . count($deleted_rows_list) . " row(s) from `$table`",
                        'before' => $deleted_rows_list,
                        'after' => null,
                        'affected_rows' => count($deleted_rows_list),
                        'status' => 'success'
                    ]);
                    flash_message('success', count($selected_rows) . ' row(s) deleted.');
                } else {
                    flash_message('error', $conn->error);
                }
            } elseif ($bulk_action === 'export') {
                $escaped_rows = array_map(function($val) use ($conn) {
                    return "'" . $conn->real_escape_string($val) . "'";
                }, $selected_rows);
                $_SESSION['export_query'] = "SELECT * FROM `$table` WHERE `" . $conn->real_escape_string($pk_col) . "` IN (" . implode(',', $escaped_rows) . ")";
                $_SESSION['export_selected_count'] = count($selected_rows);
                $_SESSION['export_table'] = $table;
                $redirect_params['tab'] = 'export';
                $redirect_params['selected'] = '1';
            } elseif ($bulk_action === 'copy') {
                $escaped_rows = array_map(function($val) use ($conn) {
                    return "'" . $conn->real_escape_string($val) . "'";
                }, $selected_rows);

                $pk_is_ai = false;
                $col_check = $conn->query("SHOW COLUMNS FROM `" . str_replace('`', '``', $table) . "` LIKE '" . $conn->real_escape_string($pk_col) . "'");
                if ($col_check && $c_info = $col_check->fetch_assoc()) {
                    $pk_is_ai = stripos($c_info['Extra'] ?? '', 'auto_increment') !== false;
                }

                $res = $conn->query("SELECT * FROM `" . str_replace('`', '``', $table) . "` WHERE `" . str_replace('`', '``', $pk_col) . "` IN (" . implode(',', $escaped_rows) . ")");
                $copied_count = 0;
                $error_msg = null;
                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        $source_row = $row;
                        $copy_row_data = $row;
                        if ($pk_is_ai) {
                            unset($copy_row_data[$pk_col]);
                        } else {
                            $copy_row_data[$pk_col] = is_numeric($copy_row_data[$pk_col]) ? ($copy_row_data[$pk_col] + 1000) : ($copy_row_data[$pk_col] . '_copy');
                        }
                        $cols = [];
                        $vals = [];
                        foreach ($copy_row_data as $k => $v) {
                            $cols[] = '`' . str_replace('`', '``', $k) . '`';
                            $vals[] = $v === null ? 'NULL' : "'" . $conn->real_escape_string($v) . "'";
                        }
                        $sql = "INSERT INTO `" . str_replace('`', '``', $table) . "` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
                        $start_time = microtime(true);
                        if ($conn->query($sql)) {
                            $dur = (microtime(true) - $start_time) * 1000;
                            $copied_count++;
                            $ins_id = $conn->insert_id;
                            if ($ins_id && $pk_is_ai) $copy_row_data[$pk_col] = $ins_id;
                            add_query_history($sql, $dur, [
                                'type' => 'COPY',
                                'db' => $db,
                                'table' => $table,
                                'summary' => "Duplicated row in `$table` (Source `$pk_col`: {$source_row[$pk_col]})" . ($ins_id ? " -> New ID: $ins_id" : ""),
                                'before' => $source_row,
                                'after' => $copy_row_data,
                                'affected_rows' => 1,
                                'status' => 'success'
                            ]);
                        } else {
                            $error_msg = $conn->error;
                        }
                    }
                }
                if ($copied_count > 0) {
                    flash_message('success', "$copied_count row(s) copied successfully.");
                } else {
                    flash_message('error', 'Failed to copy rows' . ($error_msg ? ': ' . $error_msg : '.'));
                }
                $redirect_params['tab'] = 'browse';
            } elseif ($bulk_action === 'edit') {
                $_SESSION['bulk_edit_pks'] = $selected_rows;
                $_SESSION['bulk_edit_pk_col'] = $pk_col;
                $_SESSION['bulk_edit_table'] = $table;
                $redirect_params['tab'] = 'insert';
                $redirect_params['bulk_edit'] = '1';
            }
        } else {
            flash_message('error', 'No action or rows selected.');
        }
        if (!in_array($bulk_action, ['export', 'edit'], true)) {
            $redirect_params['tab'] = 'browse';
        }
        break;

    case 'bulk_update_rows':
        $conn->select_db($db);
        $pk_col = $_POST['pk_col'] ?? '';
        $rows_data = $_POST['rows'] ?? [];
        if (!$pk_col || empty($rows_data)) {
            flash_message('error', 'No rows provided for bulk update.');
            $redirect_params['tab'] = 'browse';
            break;
        }

        // Fetch before state for all affected rows
        $pks = array_keys($rows_data);
        $escaped_pks = array_map(function($v) use ($conn) { return "'" . $conn->real_escape_string($v) . "'"; }, $pks);
        $b_res = $conn->query("SELECT * FROM `" . str_replace('`', '``', $table) . "` WHERE `" . str_replace('`', '``', $pk_col) . "` IN (" . implode(',', $escaped_pks) . ")");
        $before_map = [];
        if ($b_res) {
            while ($r = $b_res->fetch_assoc()) {
                $before_map[$r[$pk_col]] = $r;
            }
        }

        $updated_count = 0;
        $error_msg = null;

        foreach ($rows_data as $pk_val => $row_info) {
            $fields = $row_info['fields'] ?? [];
            $nulls = $row_info['nulls'] ?? [];
            $before_row = $before_map[$pk_val] ?? null;
            $after_row = $before_row ?: [];
            $diff = [];

            $set_clauses = [];
            foreach ($fields as $field_name => $field_val) {
                if ($field_name === $pk_col) continue;

                $new_val = !empty($nulls[$field_name]) ? null : $field_val;
                $old_val = $before_row[$field_name] ?? null;
                $after_row[$field_name] = $new_val;

                $escaped_field = '`' . str_replace('`', '``', $field_name) . '`';
                if ($new_val === null) {
                    $set_clauses[] = "$escaped_field = NULL";
                } else {
                    $set_clauses[] = "$escaped_field = '" . $conn->real_escape_string($field_val) . "'";
                }

                if ($old_val != $new_val) {
                    $diff[] = [
                        'field' => $field_name,
                        'old' => $old_val,
                        'new' => $new_val
                    ];
                }
            }

            if (!empty($set_clauses)) {
                $sql = "UPDATE `" . str_replace('`', '``', $table) . "` SET " . implode(', ', $set_clauses) . " WHERE `" . str_replace('`', '``', $pk_col) . "` = '" . $conn->real_escape_string($pk_val) . "'";
                $start_time = microtime(true);
                if ($conn->query($sql)) {
                    $dur = (microtime(true) - $start_time) * 1000;
                    $updated_count++;
                    add_query_history($sql, $dur, [
                        'type' => 'UPDATE',
                        'db' => $db,
                        'table' => $table,
                        'summary' => "Bulk updated row in `$table` (`$pk_col` = '$pk_val')" . (count($diff) ? " — " . count($diff) . " field(s) modified" : ""),
                        'diff' => $diff,
                        'before' => $before_row,
                        'after' => $after_row,
                        'affected_rows' => 1,
                        'status' => 'success'
                    ]);
                } else {
                    $error_msg = $conn->error;
                }
            }
        }

        unset($_SESSION['bulk_edit_pks'], $_SESSION['bulk_edit_pk_col'], $_SESSION['bulk_edit_table']);

        if ($updated_count > 0) {
            flash_message('success', "$updated_count row(s) updated successfully.");
        } else {
            flash_message('error', 'Failed to update rows' . ($error_msg ? ': ' . $error_msg : '.'));
        }
        $redirect_params['tab'] = 'browse';
        break;

    case 'create_view_from_table':
        $conn->select_db($db);
        $view_name = trim($_POST['view_name'] ?? '');
        if ($view_name) {
            $view_esc = $conn->real_escape_string($view_name);
            $sql = "CREATE VIEW `$view_esc` AS SELECT * FROM `$table`";
            $start_time = microtime(true);
            if ($conn->query($sql)) {
                $dur = (microtime(true) - $start_time) * 1000;
                add_query_history($sql, $dur, [
                    'type' => 'CREATE_VIEW',
                    'db' => $db,
                    'table' => $view_name,
                    'summary' => "Created view `$view_name` based on SELECT * FROM `$table`",
                    'before' => ['source_table' => $table],
                    'after' => ['view_name' => $view_name],
                    'status' => 'success'
                ]);
                flash_message('success', "View '$view_name' created successfully.");
                $redirect_params['table'] = $view_name;
                $redirect_params['tab'] = 'browse';
            } else {
                flash_message('error', $conn->error);
            }
        }
        break;

    case 'copy_row':
        $conn->select_db($db);
        $pk_col = $_POST['pk_col'] ?? '';
        $pk_val = $_POST['pk_val'] ?? '';
        if ($pk_col) {
            $result = $conn->query("SELECT * FROM `" . str_replace('`', '``', $table) . "` WHERE `" . str_replace('`', '``', $pk_col) . "` = '" . $conn->real_escape_string($pk_val) . "'");
            if ($result && $row = $result->fetch_assoc()) {
                $source_row = $row;
                unset($row[$pk_col]);
                $cols = [];
                $vals = [];
                foreach ($row as $k => $v) {
                    $cols[] = '`' . str_replace('`', '``', $k) . '`';
                    $vals[] = $v === null ? 'NULL' : "'" . $conn->real_escape_string($v) . "'";
                }
                $sql = "INSERT INTO `" . str_replace('`', '``', $table) . "` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
                $start_time = microtime(true);
                if ($conn->query($sql)) {
                    $dur = (microtime(true) - $start_time) * 1000;
                    $ins_id = $conn->insert_id;
                    $after_row = $row;
                    if ($ins_id) $after_row[$pk_col] = $ins_id;
                    add_query_history($sql, $dur, [
                        'type' => 'COPY',
                        'db' => $db,
                        'table' => $table,
                        'summary' => "Duplicated row in `$table` (Source `$pk_col`: $pk_val)" . ($ins_id ? " -> New ID: $ins_id" : ""),
                        'before' => $source_row,
                        'after' => $after_row,
                        'affected_rows' => 1,
                        'status' => 'success'
                    ]);
                    flash_message('success', 'Row copied.');
                } else {
                    flash_message('error', $conn->error);
                }
            }
        }
        $redirect_params['tab'] = 'browse';
        break;

    case 'clear_query_history':
        $_SESSION['query_history'] = [];
        flash_message('success', 'Query and modification history cleared.');
        $redirect_params['tab'] = 'history';
        break;

    case 'run_sql':
        $sql = $_POST['sql_query'] ?? '';
        if ($db) {
            $conn->select_db($db);
        }
        if ($sql) {
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
                        add_query_history($q, 0.1, [
                            'type' => 'SQL',
                            'db' => $use_detected,
                            'summary' => "Switched active database to `$use_detected`",
                            'status' => 'success'
                        ]);
                    }
                    continue;
                }
                $res = run_query($conn, $q);
                $q_dur = isset($res['duration']) ? ($res['duration'] * 1000) : null;
                if (!$res['success']) {
                    add_query_history($q, $q_dur, [
                        'type' => 'SQL',
                        'db' => $db ?: $use_detected,
                        'summary' => "Query execution failed",
                        'status' => 'error',
                        'error' => $res['error']
                    ]);
                    flash_message('error', $res['error'], true);
                    $_SESSION['last_sql'] = $sql;
                    if ($use_detected) $redirect_params['db'] = $use_detected;
                    action_redirect($redirect_params);
                }
                if ($res['result'] instanceof mysqli_result) {
                    $last_result = $res['result'];
                    $num_r = $last_result->num_rows;
                    add_query_history($q, $q_dur, [
                        'type' => 'SELECT',
                        'db' => $db ?: $use_detected,
                        'summary' => "Executed query returning $num_r row(s)",
                        'affected_rows' => $num_r,
                        'status' => 'success'
                    ]);
                } else {
                    $messages[] = $res['affected'] . ' row(s) affected.';
                    add_query_history($q, $q_dur, [
                        'type' => 'SQL',
                        'db' => $db ?: $use_detected,
                        'summary' => "Executed SQL statement affecting {$res['affected']} row(s)",
                        'affected_rows' => $res['affected'],
                        'status' => 'success'
                    ]);
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
            $start_time = microtime(true);
            if ($conn->query($sql)) {
                $dur = (microtime(true) - $start_time) * 1000;
                add_query_history($sql, $dur, [
                    'type' => 'ALTER_TABLE',
                    'db' => $db,
                    'table' => $table,
                    'summary' => "Added $idx_type index on column `$idx_col` in table `$table`",
                    'after' => ['index_name' => $idx_name, 'column' => $idx_col, 'type' => $idx_type],
                    'status' => 'success'
                ]);
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
