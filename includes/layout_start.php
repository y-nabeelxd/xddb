<?php
$flash = get_flash();
$server_info = get_server_info($conn);
$page = isset($edit_row) ? 'edit' : ($active_tab);
$accent_color = $_COOKIE['xddb_accent_color'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>XDDB<?= $selected_db ? ' · ' . esc($selected_db) : '' ?><?= $selected_table ? ' · ' . esc($selected_table) : '' ?></title>
  <!-- Inline theme restore — runs before CSS paints, prevents white flash -->
  <script>
    (function() {
      var t = localStorage.getItem('pma-theme');
      if (t === 'light' || t === 'dark') {
        document.documentElement.setAttribute('data-theme', t);
      }
    })();
  </script>
  <link rel="stylesheet" href="assets/css/theme.css?v=<?= XDDB_VERSION ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/monokai.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/show-hint.min.css">
  <?php if ($accent_color): ?>
  <style>:root { --accent: <?= esc($accent_color) ?>; }</style>
  <?php endif; ?>
</head>
<body>
<div class="app-shell">
  <header class="app-header">
    <div class="header-left">
      <div class="app-logo"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg></div>
      <div>
        <div class="app-title">SQL Management</div>
        <div class="breadcrumb">
          <a href="<?= esc(base_url(['tab' => 'databases'])) ?>">Server</a>
          <?php if ($selected_db): ?>
            <span>›</span><a href="<?= esc(base_url(['db' => $selected_db, 'tab' => 'structure'])) ?>"><?= esc($selected_db) ?></a>
          <?php endif; ?>
          <?php if ($selected_table): ?>
            <span>›</span><span><?= esc($selected_table) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="header-right">
      <?php if ($update_ver = check_for_updates()): ?>
        <a href="actions.php?action=update_app" class="btn btn-primary btn-sm" style="animation: pulse 2s infinite; background: var(--accent); color: white; border: none; font-weight: 600;">✨ Update Available (v<?= esc($update_ver) ?>)</a>
      <?php endif; ?>
      <span class="server-badge"><?= esc($user) ?>@<?= esc($host) ?>:<?= esc($port) ?></span>
      <input type="color" id="theme-color-picker" title="Change accent color" value="<?= $accent_color ?: '#0070f3' ?>" style="border:none; padding:0; width:22px; height:22px; border-radius:4px; background:none; cursor:pointer; vertical-align:middle;">
      <button type="button" class="btn btn-secondary btn-sm" id="openSnippetsBtn" title="Saved SQL Snippets">💾 Snippets</button>
      <button type="button" class="btn btn-secondary btn-sm guide-btn" id="openGuideBtn" title="SQL Reference Guide">📖 Guide</button>
      <button class="btn-icon theme-toggle-btn" title="Switch theme">☀️</button>
      <a href="logout.php" class="btn btn-danger btn-sm">Disconnect</a>
    </div>
  </header>
  <div class="app-body">
    <aside class="sidebar">
      <div class="sidebar-header">
        <input type="text" id="sidebarSearch" class="sidebar-search" placeholder="Filter databases...">
      </div>
      <div class="sidebar-section" id="sidebarSection">
        <?php if (!empty($_SESSION['favorites'])): ?>
          <div class="sidebar-label">Favorites</div>
          <?php foreach ($_SESSION['favorites'] as $fav): ?>
            <?= render_sidebar_db_group($fav, $tables_by_db[$fav] ?? [], $selected_db, $selected_table, '★') ?>
          <?php endforeach; ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['recent'])): ?>
          <div class="sidebar-label">Recent</div>
          <?php foreach ($_SESSION['recent'] as $rec): ?>
            <?= render_sidebar_db_group($rec, $tables_by_db[$rec] ?? [], $selected_db, $selected_table, '⏱', true, $active_tab) ?>
          <?php endforeach; ?>
        <?php endif; ?>
        <div class="sidebar-label">Databases (<?= count($databases) ?>)</div>
        <?php foreach ($databases as $dbitem): ?>
          <?= render_sidebar_db_group($dbitem, $tables_by_db[$dbitem] ?? [], $selected_db, $selected_table) ?>
        <?php endforeach; ?>
      </div>
      <div class="sidebar-actions">
        <a href="<?= esc(base_url(['tab' => 'databases'])) ?>" class="btn btn-secondary btn-sm" style="flex:1">+ New DB</a>
      </div>
    </aside>
    <main class="main-content">
      <?= nav_tabs($level, $selected_db, $selected_table, $active_tab) ?>
      <div class="content-area">
        <?php if ($flash): ?>
          <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>"><?= !empty($flash['is_html']) ? $flash['message'] : esc($flash['message']) ?></div>
        <?php endif; ?>
