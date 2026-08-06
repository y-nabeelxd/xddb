<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require_once __DIR__ . '/includes/functions.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['server_address'] ?? '127.0.0.1';
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $port = (int)($_POST['db_port'] ?? 3306);
    $db_type = $_POST['db_type'] ?? 'MySQL';

    if ($user) {
        $conn = @new mysqli($host, $user, $pass, '', $port);
        if ($conn->connect_error) {
            $msg = $conn->connect_error;
            if (stripos($msg, 'Access denied') !== false) {
                if ($pass !== '' && stripos($msg, 'using password: YES') !== false) {
                    $error = 'Password is incorrect.';
                } elseif ($pass === '') {
                    $error = 'Password is required for this account.';
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Connection failed: ' . $msg;
            }
        } else {
            $_SESSION['host'] = $host;
            $_SESSION['user'] = $user;
            $_SESSION['pass'] = $pass;
            $_SESSION['port'] = $port;
            $_SESSION['db_type'] = $db_type;
            $_SESSION['favorites'] = $_SESSION['favorites'] ?? [];
            $_SESSION['recent'] = $_SESSION['recent'] ?? [];
            $_SESSION['query_history'] = $_SESSION['query_history'] ?? [];
            header('Location: dashboard.php');
            exit;
        }
    } else {
        $error = 'Username is required.';
    }
}

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>XDDB · Login</title>
  <!-- Inline theme restore — runs before CSS paints, prevents white flash -->
  <script>
    (function() {
      var t = localStorage.getItem('pma-theme');
      if (t === 'light' || t === 'dark') {
        document.documentElement.setAttribute('data-theme', t);
      }
      // Always default to dark if no preference saved
    })();
  </script>
  <link rel="stylesheet" href="assets/css/theme.css?v=<?= XDDB_VERSION ?>">
</head>
<body>
  <button class="btn-icon theme-toggle-btn theme-toggle-login" title="Switch theme">☀️</button>
  <div class="login-page">
    <div class="login-card">
      <div class="login-logo">
        <div class="login-logo-icon"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg></div>
        <div class="login-title">XDDB</div>
        <div class="login-subtitle">Connect to your MySQL / MariaDB server</div>
      </div>
      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <div id="errorBox" class="alert alert-error" style="display:none"></div>
      <form id="loginForm" method="POST" action="index.php">
        <div class="form-group">
          <label class="form-label">Database Type</label>
          <div class="custom-select">
            <div class="select-display" id="dbSelectDisplay"><span id="dbSelectedText">MySQL</span></div>
            <span class="select-arrow">▼</span>
            <div class="select-dropdown" id="dbDropdown">
              <div class="select-option selected" data-value="MySQL" data-port="3306"><span>MySQL</span><span class="port-badge">3306</span></div>
              <div class="select-option" data-value="MariaDB" data-port="3306"><span>MariaDB</span><span class="port-badge">3306</span></div>
            </div>
          </div>
          <input type="hidden" id="dbType" name="db_type" value="MySQL">
          <input type="hidden" id="dbPort" name="db_port" value="3306">
        </div>
        <div class="form-group">
          <label class="form-label">Server</label>
          <div class="custom-select">
            <div class="select-display" id="serverSelectDisplay"><span id="serverSelectedText">127.0.0.1</span></div>
            <span class="select-arrow">▼</span>
            <div class="select-dropdown" id="serverDropdown">
              <div class="select-option selected" data-value="127.0.0.1">127.0.0.1</div>
              <div class="select-option" data-value="localhost">localhost</div>
              <div class="select-option" data-value="::1">::1</div>
              <div class="select-option" data-value="custom">Custom</div>
            </div>
          </div>
          <input type="hidden" id="serverAddress" name="server_address" value="127.0.0.1">
        </div>
        <div class="form-group custom-server-input" id="customServerGroup">
          <label class="form-label">Custom Server</label>
          <input type="text" id="customServerInput" name="custom_server" class="form-input" placeholder="192.168.1.100" autocomplete="off">
        </div>
        <div class="form-group">
          <label class="form-label">Username</label>
          <input type="text" id="username" name="username" class="form-input" placeholder="root" autocomplete="off" required>
        </div>
        <div class="form-group password-wrap">
          <label class="form-label">Password</label>
          <input type="password" id="password" name="password" class="form-input" placeholder="········" autocomplete="off">
          <button type="button" class="toggle-password">Show</button>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary" style="width:100%;padding:10px">Connect</button>
        </div>
      </form>
    </div>
  </div>
  <footer class="app-footer">Powered by <strong>TeamXD</strong> · created by <strong>Nabeel XD</strong> v<?= XDDB_VERSION ?></footer>
  <script src="assets/js/app.js?v=<?= XDDB_VERSION ?>"></script>
</body>
</html>
