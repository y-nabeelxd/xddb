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
        $status = get_server_status($conn);
        $keys = ['Uptime', 'Threads_connected', 'Threads_running', 'Queries', 'Slow_queries', 'Connections', 'Max_used_connections', 'Bytes_received', 'Bytes_sent', 'Open_tables', 'Opened_tables'];
        ?>
        <div class="panel">
          <div class="panel-header">Server Status</div>
          <div class="panel-body" style="padding:0">
            <div class="table-wrap" style="border:none"><table class="data-table">
              <thead><tr><th>Variable</th><th>Value</th></tr></thead>
              <tbody>
                <?php foreach ($keys as $k): if (isset($status[$k])): ?>
                  <tr><td><?= esc($k) ?></td><td><?= esc($status[$k]) ?></td></tr>
                <?php endif; endforeach; ?>
              </tbody>
            </table></div>
          </div>
        </div>
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
        ?>
        <div class="panel">
          <div class="panel-header">Query History</div>
          <div class="panel-body" style="padding:0">
            <?php if (empty($history)): ?>
              <p style="padding:16px;color:var(--text-muted)">No queries executed yet.</p>
            <?php else: ?>
              <div class="table-wrap" style="border:none"><table class="data-table">
                <thead><tr><th>Time</th><th>Query</th><th>Action</th></tr></thead>
                <tbody>
                  <?php foreach ($history as $h): ?>
                    <tr>
                      <td style="white-space:nowrap"><?= esc($h['time']) ?></td>
                      <td><code style="font-size:11px"><?= esc($h['sql']) ?></code></td>
                      <td><a href="<?= esc(base_url(['tab' => 'sql'])) ?>" onclick="sessionStorage.setItem('replay_sql','<?= esc(addslashes($h['sql'])) ?>')">Replay</a></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table></div>
            <?php endif; ?>
          </div>
        </div>
        <?php
        break;
}
