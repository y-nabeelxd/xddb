<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

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

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['csrf_token'] ?? '';
    if (!empty($_SESSION['csrf_token']) && !verify_csrf_token($submitted_token)) {
        $error = 'Security validation failed. Please refresh the page and try again.';
    } else {
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
                session_regenerate_id(true);
                $_SESSION['host'] = $host;
                $_SESSION['user'] = $user;
                $_SESSION['pass'] = $pass;
                $_SESSION['port'] = $port;
                $_SESSION['db_type'] = $db_type;
                $_SESSION['favorites'] = $_SESSION['favorites'] ?? [];
                $_SESSION['recent'] = $_SESSION['recent'] ?? [];
                $_SESSION['query_history'] = $_SESSION['query_history'] ?? [];
                get_csrf_token(); // generate fresh token
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $error = 'Username is required.';
        }
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
  <meta name="csrf-token" content="<?= get_csrf_token() ?>">
</head>
<body>
  <button class="btn-icon theme-toggle-btn theme-toggle-login" title="Switch theme" aria-label="Switch theme"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg></button>
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
        <?= csrf_field() ?>
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
  <footer class="app-footer">Powered by <strong>TeamXDD</strong> · created by <strong>Nabeel XDD</strong> | XDDB @ v<?= XDDB_VERSION ?></footer>
  <script src="assets/js/app.js?v=<?= XDDB_VERSION ?>"></script>
</body>
</html>
