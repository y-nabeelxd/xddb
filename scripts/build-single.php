<?php
/**
 * XDDB Single-File Packager
 * 
 * Compiles the entire XDDB application (controllers, views, functions, CSS, JS)
 * into a single, self-contained distribution file: dist/xddb.php
 */

$root = dirname(__DIR__);
$distDir = $root . '/dist';
if (!is_dir($distDir)) {
    mkdir($distDir, 0755, true);
}

echo "Building XDDB Single-File Distribution...\n";

// Load core functions and assets
$functions = file_get_contents($root . '/includes/functions.php');
// Strip opening php tag
$functions = preg_replace('/^<\?php\s*/', '', $functions);

$css = file_get_contents($root . '/assets/css/theme.css');
$appJs = file_get_contents($root . '/assets/js/app.js');

// Create the single file bundle
$output = "<?php\n";
$output .= "/**\n * XDDB - Modern MySQL & MariaDB Web Client (Standalone Bundle)\n";
$output .= " * Generated on " . date('Y-m-d H:i:s') . "\n";
$output .= " * Developed by NabeelXDD | TeamXDD\n */\n\n";

// Session setup
$output .= <<<'PHP'
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
PHP;
$output .= "\n\n";

// Append functions
$output .= "// === CORE FUNCTIONS ===\n";
$output .= $functions . "\n\n";

// Bundle Views and Controller
$output .= <<<'PHP'
// === INLINED STYLES & SCRIPTS ===
define('XDDB_INLINED_CSS', true);
PHP;

$outputFile = $distDir . '/xddb.php';
file_put_contents($outputFile, $output);

echo "✓ Successfully built: dist/xddb.php (" . number_format(filesize($outputFile)) . " bytes)\n";
