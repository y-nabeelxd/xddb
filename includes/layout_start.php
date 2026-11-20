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
  <meta name="csrf-token" content="<?= get_csrf_token() ?>">
  <link rel="stylesheet" href="assets/css/codemirror/codemirror.min.css">
  <link rel="stylesheet" href="assets/css/codemirror/monokai.min.css">
  <link rel="stylesheet" href="assets/css/codemirror/show-hint.min.css">
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
        <a href="actions.php?action=update_app" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:6px;font-weight:500;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Update v<?= esc($update_ver) ?>
        </a>
      <?php else: ?>
        <a href="actions.php?action=check_update" class="btn btn-secondary btn-sm" title="Check for updates" style="display:inline-flex;align-items:center;gap:6px;font-weight:500;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>
          Check Update
        </a>
      <?php endif; ?>
      <span class="server-badge"><?= esc($user) ?>@<?= esc($host) ?>:<?= esc($port) ?></span>
      <input type="color" id="theme-color-picker" title="Change accent color" value="<?= $accent_color ?: '#0a84ff' ?>" style="border:none; padding:0; width:22px; height:22px; border-radius:4px; background:none; cursor:pointer; vertical-align:middle;">
      <button type="button" class="btn btn-secondary btn-sm" id="openSnippetsBtn" title="Saved SQL Snippets" style="display:inline-flex;align-items:center;gap:6px;font-weight:500;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
        Snippets
      </button>
      <button type="button" class="btn btn-secondary btn-sm guide-btn" id="openGuideBtn" title="SQL Reference Guide" style="display:inline-flex;align-items:center;gap:6px;font-weight:500;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        Guide
      </button>
      <button class="btn-icon theme-toggle-btn" title="Switch theme" aria-label="Switch theme">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      </button>
      <a href="index.php?logout=1" class="btn btn-danger btn-sm" style="display:inline-flex;align-items:center;gap:6px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Disconnect
      </a>
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
          <?php 
          $fav_icon = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--warning);"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
          foreach ($_SESSION['favorites'] as $fav): ?>
            <?= render_sidebar_db_group($fav, $tables_by_db[$fav] ?? [], $selected_db, $selected_table, $fav_icon) ?>
          <?php endforeach; ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['recent'])): ?>
          <div class="sidebar-label">Recent</div>
          <?php 
          $rec_icon = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.75;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
          foreach ($_SESSION['recent'] as $rec): ?>
            <?= render_sidebar_db_group($rec, $tables_by_db[$rec] ?? [], $selected_db, $selected_table, $rec_icon, true, $active_tab) ?>
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
