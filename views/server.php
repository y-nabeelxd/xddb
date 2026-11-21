<?php
switch ($active_tab) {
    case 'databases':
        $server_status = get_server_status($conn);
        ?>
        <div class="stats-grid">
          <div class="stat-card"><div class="stat-label">Server Version</div><div class="stat-value" style="font-size:14px"><?= esc($server_info['version']) ?></div></div>
          <div class="stat-card"><div class="stat-label">Uptime</div><div class="stat-value" style="font-size:14px"><?= esc($server_status['Uptime'] ?? 'N/A') ?>s</div></div>
          <div class="stat-card"><div class="stat-label">Connections</div><div class="stat-value"><?= esc($server_status['Threads_connected'] ?? '0') ?></div></div>
          <div class="stat-card"><div class="stat-label">Databases</div><div class="stat-value"><?= count($databases) ?></div></div>
        </div>
        <div class="panel">
          <div class="panel-header">Create Database</div>
          <div class="panel-body">
            <form method="POST" action="actions.php">
              <input type="hidden" name="action" value="create_database">
              <div class="form-row">
                <div class="form-group"><label class="form-label">Database Name</label><input type="text" name="db_name" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Charset</label><select name="charset" class="form-select"><option value="utf8mb4">utf8mb4</option><option value="utf8">utf8</option><option value="latin1">latin1</option></select></div>
                <div class="form-group"><label class="form-label">Collation</label><select name="collation" class="form-select"><option value="utf8mb4_unicode_ci">utf8mb4_unicode_ci</option><option value="utf8mb4_general_ci">utf8mb4_general_ci</option><option value="utf8_general_ci">utf8_general_ci</option></select></div>
              </div>
              <div class="form-actions"><button type="submit" class="btn btn-primary">Create</button></div>
            </form>
          </div>
        </div>
        <div class="panel">
          <div class="panel-header">Databases</div>
          <div class="panel-body" style="padding:0">
            <div class="table-wrap" style="border:none">
              <table class="data-table">
                <thead><tr><th>Database</th><th>Size</th><th>Actions</th></tr></thead>
                <tbody>
                  <?php foreach ($databases as $dbitem): ?>
                  <tr>
                    <td><a href="<?= esc(base_url(['db' => $dbitem, 'tab' => 'structure'])) ?>"><?= esc($dbitem) ?></a></td>
                    <td><?= format_bytes(get_db_size($conn, $dbitem)) ?></td>
                    <td class="row-actions">
                      <form method="POST" action="actions.php" class="inline-form">
                        <input type="hidden" name="action" value="toggle_favorite">
                        <input type="hidden" name="fav_db" value="<?= esc($dbitem) ?>">
                        <button type="submit" class="btn btn-secondary btn-sm"><?= in_array($dbitem, $_SESSION['favorites'], true) ? '★' : '☆' ?></button>
                      </form>
                      <form method="POST" action="actions.php" class="inline-form confirm-delete">
                        <input type="hidden" name="action" value="drop_database">
                        <input type="hidden" name="db" value="<?= esc($dbitem) ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Drop</button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <?php
        break;

    case 'sql':
        $last_sql = $_SESSION['last_sql'] ?? '';
        ?>
        <div class="panel">
          <div class="panel-header">Run SQL Query</div>
          <div class="panel-body">
            <form method="POST" action="actions.php">
              <input type="hidden" name="action" value="run_sql">
              <input type="hidden" name="tab" value="sql">
              <div class="form-group"><textarea name="sql_query" class="form-textarea" rows="8" placeholder="SHOW DATABASES;"><?= esc($last_sql) ?></textarea></div>
              <div class="form-actions"><button type="submit" class="btn btn-primary">Go</button></div>
            </form>
            <?php if (!empty($_SESSION['sql_result'])): ?>
              <div style="margin-top:16px">
                <div class="table-wrap"><table class="data-table"><thead><tr>
                  <?php foreach ($_SESSION['sql_columns'] as $col): ?><th><?= esc($col) ?></th><?php endforeach; ?>
                </tr></thead><tbody>
                  <?php foreach ($_SESSION['sql_result'] as $row): ?>
                    <tr><?php foreach ($row as $v): ?><td><?= $v === null ? '<span class="null-val">NULL</span>' : esc($v) ?></td><?php endforeach; ?></tr>
                  <?php endforeach; ?>
                </tbody></table></div>
              </div>
              <?php unset($_SESSION['sql_result'], $_SESSION['sql_columns']); ?>
            <?php endif; ?>
          </div>
        </div>
        <?php
        break;

    case 'status':
        ?>
        <div class="panel">
          <div class="panel-header" style="display:flex; justify-content:space-between; align-items:center;">
            <span>Real-Time Server Dashboard</span>
            <div style="font-size:12px; color:var(--text-muted);">
              Auto-refresh: <input type="checkbox" id="auto-refresh-toggle" checked style="vertical-align:middle;">
            </div>
          </div>
          <div class="panel-body">
            <div class="stats-grid" id="real-time-metrics">
              <div class="stat-card"><div class="stat-label">Uptime</div><div class="stat-value" id="rt-uptime">-</div></div>
              <div class="stat-card"><div class="stat-label">Active Connections</div><div class="stat-value" id="rt-connections">-</div></div>
              <div class="stat-card"><div class="stat-label">Queries/sec</div><div class="stat-value" id="rt-qps">-</div></div>
              <div class="stat-card"><div class="stat-label">Slow Queries</div><div class="stat-value" id="rt-slow">-</div></div>
            </div>
          </div>
        </div>
        
        <div class="panel">
          <div class="panel-header">Active Processes (Processlist)</div>
          <div class="panel-body" style="padding:0">
            <div class="table-wrap" style="border:none">
              <table class="data-table" id="processlist-table">
                <thead><tr><th>ID</th><th>User</th><th>Host</th><th>DB</th><th>Command</th><th>Time</th><th>State</th><th>Info</th></tr></thead>
                <tbody><tr><td colspan="8" style="text-align:center;">Loading processes...</td></tr></tbody>
              </table>
            </div>
          </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            let refreshInterval;
            let lastQueries = 0;
            let lastTime = 0;

            function fetchMetrics() {
                fetch('api.php?action=server_metrics')
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) return;
                        const status = data.status;
                        document.getElementById('rt-uptime').innerText = status.Uptime + 's';
                        document.getElementById('rt-connections').innerText = status.Threads_connected;
                        document.getElementById('rt-slow').innerText = status.Slow_queries;

                        // Calculate QPS
                        const now = Date.now();
                        const currentQueries = parseInt(status.Queries) || 0;
                        if (lastTime > 0) {
                            const diffSecs = (now - lastTime) / 1000;
                            const diffQ = currentQueries - lastQueries;
                            const qps = Math.max(0, Math.round(diffQ / diffSecs));
                            document.getElementById('rt-qps').innerText = qps;
                        } else {
                            document.getElementById('rt-qps').innerText = '0';
                        }
                        lastQueries = currentQueries;
                        lastTime = now;

                        // Render processlist
                        const tbody = document.querySelector('#processlist-table tbody');
                        if (data.processlist && data.processlist.length > 0) {
                            tbody.innerHTML = data.processlist.map(p => `
                                <tr>
                                    <td>${p.Id}</td>
                                    <td>${p.User}</td>
                                    <td>${p.Host}</td>
                                    <td>${p.db || '-'}</td>
                                    <td>${p.Command}</td>
                                    <td>${p.Time}s</td>
                                    <td>${p.State || '-'}</td>
                                    <td><code style="background:var(--bg-tertiary);padding:2px 4px;border-radius:3px;">${p.Info ? p.Info.substring(0, 50) + (p.Info.length > 50 ? '...' : '') : '-'}</code></td>
                                </tr>
                            `).join('');
                        } else {
                            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;">No active processes</td></tr>';
                        }
                    });
            }

            fetchMetrics();
            refreshInterval = setInterval(fetchMetrics, 2000);

            document.getElementById('auto-refresh-toggle').addEventListener('change', function(e) {
                if (e.target.checked) {
                    refreshInterval = setInterval(fetchMetrics, 2000);
                } else {
                    clearInterval(refreshInterval);
                }
            });
        });
        </script>
        <?php
        break;

    case 'variables':
        $variables = get_server_variables($conn);
        $filter = $_GET['var_filter'] ?? '';
        ?>
        <div class="panel">
          <div class="panel-header">Server Variables</div>
          <div class="panel-body">
            <form method="GET" style="margin-bottom:12px"><input type="hidden" name="tab" value="variables"><input type="text" name="var_filter" class="form-input" placeholder="Filter variables..." value="<?= esc($filter) ?>"></form>
            <div class="table-wrap"><table class="data-table">
              <thead><tr><th>Variable</th><th>Value</th></tr></thead>
              <tbody>
                <?php foreach ($variables as $k => $v): if ($filter && stripos($k, $filter) === false) continue; ?>
                  <tr><td><?= esc($k) ?></td><td><?= esc($v) ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table></div>
          </div>
        </div>
        <?php
        break;

    case 'users':
        $users = get_users($conn);
        ?>
        <div class="panel">
          <div class="panel-header">Create User</div>
          <div class="panel-body">
            <form method="POST" action="actions.php">
              <input type="hidden" name="action" value="create_user">
              <input type="hidden" name="tab" value="users">
              <div class="form-row">
                <div class="form-group"><label class="form-label">Username</label><input type="text" name="new_user" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Host</label><input type="text" name="new_host" class="form-input" value="%" required></div>
                <div class="form-group"><label class="form-label">Password</label><input type="password" name="new_pass" class="form-input" required></div>
              </div>
              <div class="form-group">
                <label class="form-label">Privileges (Global)</label>
                <div style="display:flex; gap:16px;">
                  <label><input type="checkbox" name="privs[]" value="ALL PRIVILEGES" checked> ALL PRIVILEGES</label>
                  <label><input type="checkbox" name="privs[]" value="SELECT"> SELECT</label>
                  <label><input type="checkbox" name="privs[]" value="INSERT"> INSERT</label>
                  <label><input type="checkbox" name="privs[]" value="UPDATE"> UPDATE</label>
                  <label><input type="checkbox" name="privs[]" value="DELETE"> DELETE</label>
                </div>
              </div>
              <div class="form-actions"><button type="submit" class="btn btn-primary">Create User</button></div>
            </form>
          </div>
        </div>
        <div class="panel">
          <div class="panel-header">User Accounts</div>
          <div class="panel-body" style="padding:0">
            <div class="table-wrap" style="border:none"><table class="data-table">
              <thead><tr><th>User</th><th>Host</th><th>Grants</th><th>Actions</th></tr></thead>
              <tbody>
                <?php foreach ($users as $u):
                  $grants = get_user_grants($conn, $u['User'], $u['Host']);
                ?>
                <tr>
                  <td><?= esc($u['User']) ?></td>
                  <td><?= esc($u['Host']) ?></td>
                  <td style="font-size:11px;max-width:400px"><?= esc(implode('; ', array_slice($grants, 0, 2))) ?></td>
                  <td class="row-actions">
                    <form method="POST" action="actions.php" class="inline-form confirm-delete">
                      <input type="hidden" name="action" value="drop_user">
                      <input type="hidden" name="drop_user" value="<?= esc($u['User']) ?>">
                      <input type="hidden" name="drop_host" value="<?= esc($u['Host']) ?>">
                      <input type="hidden" name="tab" value="users">
                      <button type="submit" class="btn btn-danger btn-sm">Drop</button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table></div>
          </div>
        </div>
        <div class="panel">
          <div class="panel-header">Grant Privileges</div>
          <div class="panel-body">
            <form method="POST" action="actions.php">
              <input type="hidden" name="action" value="grant_privileges">
              <input type="hidden" name="tab" value="users">
              <div class="form-row">
                <div class="form-group"><label class="form-label">User</label><input type="text" name="grant_user" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Host</label><input type="text" name="grant_host" class="form-input" value="%" required></div>
                <div class="form-group"><label class="form-label">Database</label><input type="text" name="grant_db" class="form-input" value="*" required></div>
              </div>
              <div class="form-group">
                <label class="form-label">Privileges</label>
                <div class="form-row">
                  <?php foreach (['SELECT','INSERT','UPDATE','DELETE','CREATE','DROP','ALTER','INDEX','ALL PRIVILEGES'] as $p): ?>
                    <label class="checkbox-label"><input type="checkbox" name="privileges[]" value="<?= $p ?>"> <?= $p ?></label>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="form-actions"><button type="submit" class="btn btn-primary">Grant</button></div>
            </form>
          </div>
        </div>
        <?php
        break;

    case 'export':
        ?>
        <div class="panel">
          <div class="panel-header">Export Server</div>
          <div class="panel-body">
            <p style="margin-bottom:12px;color:var(--text-secondary)">Quick export of all user databases as SQL.</p>
            <a href="export.php" class="btn btn-primary">Quick Export (SQL)</a>
          </div>
        </div>
        <?php
        break;

    case 'import':
        ?>
        <div class="panel">
          <div class="panel-header">Import SQL</div>
          <div class="panel-body">
            <form method="POST" action="actions.php" enctype="multipart/form-data">
              <input type="hidden" name="action" value="import_sql">
              <input type="hidden" name="tab" value="import">
              <div class="form-group"><label class="form-label">Target Database</label><select name="db" class="form-select" required><?php foreach ($databases as $dbitem): ?><option value="<?= esc($dbitem) ?>"><?= esc($dbitem) ?></option><?php endforeach; ?></select></div>
              <div class="form-group"><label class="form-label">SQL File (.sql, .zip, .gz, .csv)</label><input type="file" name="import_file" class="form-input" accept=".sql,.zip,.gz,.csv"></div>
              <div class="form-group"><label class="form-label">Or paste SQL</label><textarea name="import_text" class="form-textarea" rows="6"></textarea></div>
              <div class="form-actions"><button type="submit" class="btn btn-primary">Import</button></div>
            </form>
          </div>
        </div>
        <?php
        break;

    case 'history':
        $history = $_SESSION['query_history'] ?? [];
        $total_count = count($history);
        $count_updates = 0;
        $count_inserts = 0;
        $count_deletes = 0;
        $count_schema = 0;
        $count_sql = 0;

        foreach ($history as $h) {
            $t = strtoupper($h['type'] ?? 'SQL');
            if (in_array($t, ['UPDATE', 'BULK_UPDATE'])) $count_updates++;
            elseif (in_array($t, ['INSERT', 'COPY'])) $count_inserts++;
            elseif (in_array($t, ['DELETE', 'BULK_DELETE', 'TRUNCATE'])) $count_deletes++;
            elseif (in_array($t, ['CREATE_TABLE', 'DROP_TABLE', 'ALTER_TABLE', 'CREATE_DB', 'DROP_DB', 'CREATE_VIEW', 'DROP_VIEW', 'CREATE', 'DROP', 'ALTER'])) $count_schema++;
            else $count_sql++;
        }
        ?>
        <style>
          .audit-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
          }
          .audit-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
          }
          .audit-stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            box-shadow: var(--shadow-sm);
          }
          .audit-stat-num {
            font-size: 22px;
            font-weight: 700;
            line-height: 1.2;
            color: var(--text);
          }
          .audit-stat-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 500;
          }
          .audit-filter-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
          }
          .audit-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
          }
          .audit-pill {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
          }
          .audit-pill:hover, .audit-pill.active {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
          }
          .audit-timeline {
            display: flex;
            flex-direction: column;
            gap: 16px;
          }
          .audit-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: border-color 0.2s ease;
          }
          .audit-card:hover {
            border-color: var(--border-hover, #444);
          }
          .audit-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            padding: 12px 16px;
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
          }
          .audit-card-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
          }
          .audit-card-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
          }
          .audit-summary {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
          }
          .audit-badge {
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            display: inline-flex;
            align-items: center;
            gap: 4px;
          }
          .badge-update { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); }
          .badge-insert { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
          .badge-delete { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
          .badge-schema { background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); }
          .badge-sql { background: rgba(139, 92, 246, 0.15); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.3); }

          .audit-target {
            font-size: 12px;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
            color: var(--text);
          }
          .diff-container {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            overflow: hidden;
            margin-top: 4px;
          }
          .diff-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
          }
          .diff-table th {
            background: var(--bg-tertiary);
            padding: 8px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            border-bottom: 1px solid var(--border-color);
          }
          .diff-table td {
            padding: 8px 12px;
            border-bottom: 1px solid var(--border-color);
          }
          .diff-table tr:last-child td {
            border-bottom: none;
          }
          .diff-col {
            font-family: monospace;
            font-weight: 600;
            color: var(--accent);
            width: 22%;
          }
          .diff-old {
            width: 36%;
            background: rgba(239, 68, 68, 0.05);
          }
          .diff-new {
            width: 36%;
            background: rgba(16, 185, 129, 0.05);
          }
          del.diff-val {
            background: rgba(239, 68, 68, 0.18);
            color: #f87171;
            padding: 2px 6px;
            border-radius: 4px;
            text-decoration: line-through;
            font-family: monospace;
            word-break: break-all;
          }
          ins.diff-val {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            padding: 2px 6px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            font-family: monospace;
            word-break: break-all;
          }
          .diff-null {
            color: var(--text-muted);
            font-style: italic;
            font-size: 12px;
          }
          .sql-box {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
          }
          .sql-code {
            font-family: monospace;
            font-size: 12px;
            color: var(--text);
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
            flex: 1;
            margin: 0;
          }
          .snapshot-drawer {
            display: none;
            margin-top: 8px;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 12px;
          }
          .snapshot-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 12px;
          }
          .snapshot-panel {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 10px;
          }
          .snapshot-panel-title {
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            display: flex;
            align-items: center;
            gap: 6px;
          }
          .snapshot-json {
            font-family: monospace;
            font-size: 11px;
            line-height: 1.5;
            color: var(--text);
            margin: 0;
            white-space: pre-wrap;
            word-break: break-all;
          }
        </style>

        <div class="audit-page-header">
          <div>
            <h2 style="font-size:18px; font-weight:700; margin:0 0 4px; display:inline-flex; align-items:center; gap:8px;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
              Query History & Audit Log
              <span class="badge" style="background:var(--accent); color:#fff; font-size:11px;"><?= $total_count ?> Operations</span>
            </h2>
            <p style="margin:0; font-size:13px; color:var(--text-muted);">
              Comprehensive audit trail tracking every single database and table modification, before/after values, and executed queries.
            </p>
          </div>
          <div style="display:flex; gap:8px; align-items:center;">
            <?php if (!empty($history)): ?>
              <button type="button" class="btn btn-secondary btn-sm" onclick="exportAuditLogJson()" title="Download full history as JSON" style="display:inline-flex;align-items:center;gap:6px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export JSON
              </button>
              <form action="actions.php" method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to clear all query and audit history?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clear_query_history">
                <button type="submit" class="btn btn-danger btn-sm" style="display:inline-flex;align-items:center;gap:6px;">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                  Clear History
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <div class="audit-stats-grid">
          <div class="audit-stat-card">
            <span class="audit-stat-num"><?= $total_count ?></span>
            <span class="audit-stat-label">Total Actions</span>
          </div>
          <div class="audit-stat-card" style="border-left:3px solid #f59e0b;">
            <span class="audit-stat-num" style="color:#f59e0b;"><?= $count_updates ?></span>
            <span class="audit-stat-label">Updates & Edits</span>
          </div>
          <div class="audit-stat-card" style="border-left:3px solid #10b981;">
            <span class="audit-stat-num" style="color:#10b981;"><?= $count_inserts ?></span>
            <span class="audit-stat-label">New Inserts</span>
          </div>
          <div class="audit-stat-card" style="border-left:3px solid #ef4444;">
            <span class="audit-stat-num" style="color:#ef4444;"><?= $count_deletes ?></span>
            <span class="audit-stat-label">Deletions</span>
          </div>
          <div class="audit-stat-card" style="border-left:3px solid #3b82f6;">
            <span class="audit-stat-num" style="color:#3b82f6;"><?= $count_schema ?></span>
            <span class="audit-stat-label">Schema Changes</span>
          </div>
        </div>

        <div class="audit-filter-bar">
          <div class="audit-pills">
            <button type="button" class="audit-pill active" data-filter="all">All (<?= $total_count ?>)</button>
            <button type="button" class="audit-pill" data-filter="update">Updates (<?= $count_updates ?>)</button>
            <button type="button" class="audit-pill" data-filter="insert">Inserts (<?= $count_inserts ?>)</button>
            <button type="button" class="audit-pill" data-filter="delete">Deletes (<?= $count_deletes ?>)</button>
            <button type="button" class="audit-pill" data-filter="schema">Schema / DDL (<?= $count_schema ?>)</button>
            <button type="button" class="audit-pill" data-filter="sql">Raw SQL (<?= $count_sql ?>)</button>
          </div>
          <div style="position:relative; flex:1; max-width:320px; min-width:200px;">
            <input type="text" id="auditSearchInput" class="form-input" placeholder="Filter by table, column, value, or SQL..." style="width:100%; font-size:12px; padding:6px 10px;">
          </div>
        </div>

        <?php if (empty($history)): ?>
          <div class="panel">
            <div class="panel-body" style="padding:40px; text-align:center; color:var(--text-muted);">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 12px; display:block; opacity:0.5;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
              <h3 style="font-size:15px; margin:0 0 6px; color:var(--text);">No History Recorded Yet</h3>
              <p style="font-size:13px; margin:0;">Any cell edit, row insert, update, deletion, or SQL query executed will be automatically recorded here with before and after state diffs.</p>
            </div>
          </div>
        <?php else: ?>
          <div class="audit-timeline" id="auditTimelineList">
            <?php foreach ($history as $idx => $h):
              $raw_type = strtoupper($h['type'] ?? 'SQL');
              $category = 'sql';
              $badge_class = 'badge-sql';

              if (in_array($raw_type, ['UPDATE', 'BULK_UPDATE'])) {
                  $category = 'update';
                  $badge_class = 'badge-update';
              } elseif (in_array($raw_type, ['INSERT', 'COPY'])) {
                  $category = 'insert';
                  $badge_class = 'badge-insert';
              } elseif (in_array($raw_type, ['DELETE', 'BULK_DELETE', 'TRUNCATE'])) {
                  $category = 'delete';
                  $badge_class = 'badge-delete';
              } elseif (in_array($raw_type, ['CREATE_TABLE', 'DROP_TABLE', 'ALTER_TABLE', 'CREATE_DB', 'DROP_DB', 'CREATE_VIEW', 'DROP_VIEW', 'CREATE', 'DROP', 'ALTER'])) {
                  $category = 'schema';
                  $badge_class = 'badge-schema';
              }

              $diff = $h['diff'] ?? [];
              $has_diff = !empty($diff);
              $before_state = $h['before'] ?? null;
              $after_state = $h['after'] ?? null;
              $has_snapshot = !empty($before_state) || !empty($after_state);
              $item_id = $h['id'] ?? ('qh_' . $idx);

              $search_tokens = [
                  $raw_type,
                  $h['summary'] ?? '',
                  $h['db'] ?? '',
                  $h['table'] ?? '',
                  $h['sql'] ?? ''
              ];
              if ($has_diff) {
                  foreach ($diff as $df) {
                      $search_tokens[] = $df['field'] ?? '';
                      $search_tokens[] = (string)($df['old'] ?? '');
                      $search_tokens[] = (string)($df['new'] ?? '');
                  }
              }
              $search_blob = strtolower(implode(' ', array_filter($search_tokens)));
            ?>
              <div class="audit-card" data-category="<?= esc($category) ?>" data-search="<?= esc($search_blob) ?>">
                <div class="audit-card-header">
                  <div class="audit-card-meta">
                    <span class="audit-badge <?= esc($badge_class) ?>"><?= esc($raw_type) ?></span>
                    <?php if (!empty($h['db']) || !empty($h['table'])): ?>
                      <span class="audit-target" title="Target Database & Table">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><path d="M12 2C6.48 2 2 4.02 2 6.5s4.48 4.5 10 4.5 10-2.02 10-4.5S17.52 2 12 2z"/><path d="M2 12c0 2.48 4.48 4.5 10 4.5s10-2.02 10-4.5"/><path d="M2 17.5C2 19.98 4.48 22 12 22s10-2.02 10-4.5"/></svg>
                        <?= esc($h['db'] ?? '') ?><?= (!empty($h['db']) && !empty($h['table'])) ? '.' : '' ?><?= esc($h['table'] ?? '') ?>
                      </span>
                    <?php endif; ?>
                    <?php if (isset($h['duration']) && $h['duration'] !== null): ?>
                      <span style="font-size:11px; color:var(--text-muted); display:inline-flex; align-items:center; gap:2px;">
                        ⚡ <?= number_format((float)$h['duration'], 2) ?> ms
                      </span>
                    <?php endif; ?>
                    <?php if (isset($h['affected_rows']) && $h['affected_rows'] !== null): ?>
                      <span style="font-size:11px; color:var(--text-muted);">
                        <?= (int)$h['affected_rows'] ?> row(s) affected
                      </span>
                    <?php endif; ?>
                  </div>
                  <div style="font-size:12px; color:var(--text-muted); display:inline-flex; align-items:center; gap:4px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                    <?= esc($h['time'] ?? '') ?>
                  </div>
                </div>

                <div class="audit-card-body">
                  <?php if (!empty($h['summary'])): ?>
                    <div class="audit-summary">
                      <span><?= esc($h['summary']) ?></span>
                    </div>
                  <?php endif; ?>

                  <?php if ($has_diff): ?>
                    <div class="diff-container">
                      <table class="diff-table">
                        <thead>
                          <tr>
                            <th>Modified Column</th>
                            <th>Before (Old Value)</th>
                            <th style="width:24px; text-align:center;"></th>
                            <th>After (New Value)</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($diff as $d): ?>
                            <tr>
                              <td class="diff-col"><?= esc($d['field'] ?? '') ?></td>
                              <td class="diff-old">
                                <?php if (($d['old'] ?? null) === null): ?>
                                  <span class="diff-null">NULL</span>
                                <?php else: ?>
                                  <del class="diff-val"><?= esc((string)$d['old']) ?></del>
                                <?php endif; ?>
                              </td>
                              <td style="text-align:center; color:var(--text-muted); font-weight:bold;">→</td>
                              <td class="diff-new">
                                <?php if (($d['new'] ?? null) === null): ?>
                                  <span class="diff-null">NULL</span>
                                <?php else: ?>
                                  <ins class="diff-val"><?= esc((string)$d['new']) ?></ins>
                                <?php endif; ?>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php elseif ($raw_type === 'DELETE' && !empty($before_state)): ?>
                    <div style="background:rgba(239, 68, 68, 0.08); border:1px solid rgba(239, 68, 68, 0.2); border-radius:6px; padding:10px 14px; font-size:13px; color:#f87171;">
                      <strong>Deleted Record State:</strong>
                      <div style="margin-top:6px; font-family:monospace; font-size:11px; white-space:pre-wrap; word-break:break-all;"><?= esc(json_encode($before_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></div>
                    </div>
                  <?php elseif (($raw_type === 'INSERT' || $raw_type === 'COPY') && !empty($after_state)): ?>
                    <div style="background:rgba(16, 185, 129, 0.08); border:1px solid rgba(16, 185, 129, 0.2); border-radius:6px; padding:10px 14px; font-size:13px; color:#34d399;">
                      <strong>Inserted Record State:</strong>
                      <div style="margin-top:6px; font-family:monospace; font-size:11px; white-space:pre-wrap; word-break:break-all;"><?= esc(json_encode($after_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></div>
                    </div>
                  <?php endif; ?>

                  <?php if ($has_snapshot && $has_diff): ?>
                    <div>
                      <button type="button" class="btn btn-secondary btn-sm" onclick="toggleSnapshot('<?= esc($item_id) ?>')" style="font-size:11px; padding:3px 8px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Toggle Full Record Snapshots (Before & After)
                      </button>
                      <div id="snap_<?= esc($item_id) ?>" class="snapshot-drawer">
                        <div class="snapshot-grid">
                          <div class="snapshot-panel" style="border-top:3px solid #ef4444;">
                            <div class="snapshot-panel-title" style="color:#ef4444;">
                              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                              Before Mutation
                            </div>
                            <pre class="snapshot-json"><?= esc(json_encode($before_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
                          </div>
                          <div class="snapshot-panel" style="border-top:3px solid #10b981;">
                            <div class="snapshot-panel-title" style="color:#10b981;">
                              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                              After Mutation
                            </div>
                            <pre class="snapshot-json"><?= esc(json_encode($after_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>

                  <div class="sql-box">
                    <pre class="sql-code"><code><?= esc($h['sql'] ?? '') ?></code></pre>
                    <div style="display:flex; gap:6px; flex-shrink:0;">
                      <button type="button" class="btn btn-secondary btn-sm" onclick="copySqlText(this, '<?= esc(addslashes($h['sql'] ?? '')) ?>')" title="Copy SQL statement" style="padding:4px 8px; font-size:11px;">
                        Copy
                      </button>
                      <a href="<?= esc(base_url(['tab' => 'sql', 'db' => $h['db'] ?? ''])) ?>" onclick="sessionStorage.setItem('replay_sql','<?= esc(addslashes($h['sql'] ?? '')) ?>')" class="btn btn-primary btn-sm" style="padding:4px 8px; font-size:11px;">
                        Replay
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <script>
          function toggleSnapshot(id) {
            const el = document.getElementById('snap_' + id);
            if (el) {
              el.style.display = (el.style.display === 'block') ? 'none' : 'block';
            }
          }

          function copySqlText(btn, sql) {
            if (navigator.clipboard) {
              navigator.clipboard.writeText(sql).then(() => {
                const orig = btn.innerText;
                btn.innerText = 'Copied!';
                btn.style.color = '#34d399';
                setTimeout(() => { btn.innerText = orig; btn.style.color = ''; }, 1500);
              });
            }
          }

          function exportAuditLogJson() {
            fetch('api.php?action=get_history')
              .then(r => r.json())
              .then(data => {
                const blob = new Blob([JSON.stringify(data.history || [], null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'xddb-audit-log-' + (new Date().toISOString().slice(0, 10)) + '.json';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
              })
              .catch(e => alert('Failed to export audit log: ' + e));
          }

          // Live Filtering by search input and pills
          document.addEventListener('DOMContentLoaded', () => {
            const pills = document.querySelectorAll('.audit-pill');
            const searchInput = document.getElementById('auditSearchInput');
            const cards = document.querySelectorAll('.audit-card');
            let currentFilter = 'all';

            function applyFilters() {
              const query = (searchInput ? searchInput.value.toLowerCase().trim() : '');
              cards.forEach(card => {
                const category = card.getAttribute('data-category');
                const searchBlob = card.getAttribute('data-search') || '';

                const matchesFilter = (currentFilter === 'all' || category === currentFilter);
                const matchesSearch = (!query || searchBlob.includes(query));

                card.style.display = (matchesFilter && matchesSearch) ? 'block' : 'none';
              });
            }

            pills.forEach(pill => {
              pill.addEventListener('click', () => {
                pills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                currentFilter = pill.getAttribute('data-filter');
                applyFilters();
              });
            });

            if (searchInput) {
              searchInput.addEventListener('input', applyFilters);
            }
          });
        </script>
        <?php
        break;
}
