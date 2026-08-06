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
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

$db = $_GET['db'] ?? '';
$is_dict = isset($_GET['dict']) && $_GET['dict'] == '1';
$table = $_GET['table'] ?? '';

if (!$db) die("No database specified");
$conn->select_db($db);

$tables = get_tables($conn, $db);

if ($table) {
    if (!in_array($table, $tables)) die("Table not found");
    $tables = [$table];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print View - <?= esc($db) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; margin: 20px; color: #333; }
        h1 { font-size: 20px; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 20px; }
        h2 { font-size: 16px; margin-top: 30px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; font-size: 12px; }
        th, td { border: 1px solid #aaa; padding: 6px 10px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        .footer { margin-top: 30px; font-size: 11px; color: #666; text-align: center; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom: 20px;">
    <button onclick="window.print()" style="padding: 8px 16px; font-size: 14px; cursor: pointer;">Print</button>
</div>

<h1>Database: <?= esc($db) ?> <?= $is_dict ? '- Data Dictionary' : '' ?></h1>

<?php foreach ($tables as $t): 
    $cols = get_table_structure($conn, $db, $t);
?>
    <h2>Table: <?= esc($t) ?></h2>
    <table>
        <thead>
            <tr>
                <th>Column</th>
                <th>Type</th>
                <th>Null</th>
                <th>Default</th>
                <?php if ($is_dict): ?>
                <th>Extra</th>
                <th>Key</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cols as $c): ?>
            <tr>
                <td><?= esc($c['Field']) ?></td>
                <td><?= esc($c['Type']) ?></td>
                <td><?= esc($c['Null']) ?></td>
                <td><?= esc($c['Default']) ?></td>
                <?php if ($is_dict): ?>
                <td><?= esc($c['Extra']) ?></td>
                <td><?= esc($c['Key']) ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if ($is_dict): 
        $indexes = get_table_indexes($conn, $db, $t);
        if (!empty($indexes)):
    ?>
    <h4>Indexes for <?= esc($t) ?></h4>
    <table>
        <thead><tr><th>Keyname</th><th>Type</th><th>Unique</th><th>Column</th></tr></thead>
        <tbody>
            <?php foreach ($indexes as $idx): ?>
            <tr>
                <td><?= esc($idx['Key_name']) ?></td>
                <td><?= esc($idx['Index_type']) ?></td>
                <td><?= $idx['Non_unique'] == '0' ? 'Yes' : 'No' ?></td>
                <td><?= esc($idx['Column_name']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; endif; ?>
    
<?php endforeach; ?>

<div class="footer">Printed on <?= date('Y-m-d H:i:s') ?></div>

</body>
</html>
