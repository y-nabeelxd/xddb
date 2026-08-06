<?php
$conn->select_db($selected_db);
switch ($active_tab) {
    case 'structure':
        $views = get_views($conn, $selected_db);
        ?>
        <div class="stats-grid">
          <div class="stat-card"><div class="stat-label">Database</div><div class="stat-value" style="font-size:14px"><?= esc($selected_db) ?></div></div>
          <div class="stat-card"><div class="stat-label">Tables</div><div class="stat-value"><?= count($tables) ?></div></div>
          <div class="stat-card"><div class="stat-label">Size</div><div class="stat-value" style="font-size:14px"><?= format_bytes(get_db_size($conn, $selected_db)) ?></div></div>
        </div>
        <div class="panel">
          <div class="panel-header">Create Table</div>
          <div class="panel-body">
            <form method="POST" action="actions.php">
              <input type="hidden" name="action" value="create_table">
              <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
              <div class="form-group"><label class="form-label">Table Name</label><input type="text" name="table_name" class="form-input" required></div>
              <div class="table-wrap"><table class="data-table">
                <thead><tr><th>Column</th><th>Type</th><th>Length</th><th>Null</th><th>Default</th><th>Extra</th></tr></thead>
                <tbody>
                  <?php for ($i = 0; $i < 3; $i++): ?>
                  <tr>
                    <td><input type="text" name="col_name[]" class="form-input" <?= $i === 0 ? 'value="id"' : '' ?>></td>
                    <td><select name="col_type[]" class="form-select"><option>VARCHAR</option><option>INT</option><option>BIGINT</option><option>TEXT</option><option>DATE</option><option>DATETIME</option><option>FLOAT</option><option>BOOLEAN</option><option>DECIMAL</option></select></td>
                    <td><input type="text" name="col_length[]" class="form-input" value="<?= $i === 0 ? '11' : '255' ?>"></td>
                    <td><input type="checkbox" name="col_null[<?= $i ?>]" <?= $i === 0 ? '' : 'checked' ?>></td>
                    <td><input type="text" name="col_default[]" class="form-input"></td>
                    <td><input type="text" name="col_extra[]" class="form-input" <?= $i === 0 ? 'value="AUTO_INCREMENT PRIMARY KEY"' : '' ?>></td>
                  </tr>
                  <?php endfor; ?>
                </tbody>
              </table></div>
              <div class="form-actions"><button type="submit" class="btn btn-primary">Create Table</button></div>
            </form>
          </div>
        </div>
        <div class="panel">
          <div class="panel-header">Tables</div>
          <div class="panel-body" style="padding:0">
            <form id="bulkTableForm" method="POST" action="actions.php">
              <input type="hidden" name="action" value="bulk_table_action">
              <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
              <div class="table-wrap" style="border:none"><table class="data-table" id="dbStructureTable">
                <thead><tr><th style="width:30px"><input type="checkbox" id="selectAllTables"></th><th>Table</th><th>Rows</th><th>Type</th><th>Size</th><th>Collation</th><th>Actions</th></tr></thead>
                <tbody>
                  <?php foreach ($tables as $titem):
                    $status = get_table_status($conn, $selected_db, $titem);
                  ?>
                  <tr>
                    <td><input type="checkbox" name="selected_tables[]" class="table-checkbox" value="<?= esc($titem) ?>"></td>
                    <td><a href="<?= esc(base_url(['db' => $selected_db, 'table' => $titem, 'tab' => 'browse'])) ?>"><?= esc($titem) ?></a></td>
                    <td><?= esc($status['Rows'] ?? '0') ?></td>
                    <td><?= esc($status['Engine'] ?? '') ?></td>
                    <td><?= format_bytes(($status['Data_length'] ?? 0) + ($status['Index_length'] ?? 0)) ?></td>
                    <td><?= esc($status['Collation'] ?? '') ?></td>
                    <td class="row-actions">
                      <a href="<?= esc(base_url(['db' => $selected_db, 'table' => $titem, 'tab' => 'browse'])) ?>" class="btn btn-secondary btn-sm">Browse</a>
                      <a href="<?= esc(base_url(['db' => $selected_db, 'table' => $titem, 'tab' => 'structure'])) ?>" class="btn btn-secondary btn-sm">Structure</a>
                      <button type="submit" form="drop_<?= esc($titem) ?>" class="btn btn-danger btn-sm">Drop</button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table></div>
              
              <div style="padding: 12px 16px; background: var(--bg-hover); border-top: 1px solid var(--border); display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 8px;">
                  <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNiIgaGVpZ2h0PSIxNiIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9ImN1cnJlbnRDb2xvciIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiPjxwYXRoIGQ9Ik0zIDlMMTIgMTVMMjEgOSIvPjxwYXRoIGQ9Ik0xMiAxNVYyMiIvPjwvc3ZnPg==" width="16" style="transform: rotate(-90deg) scaleY(-1); opacity: 0.6;" alt="arrow">
                  <label style="cursor: pointer; display: flex; align-items: center; gap: 4px;">
                    <input type="checkbox" id="checkAllText"> Check all
                  </label>
                </div>
                
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span>With selected:</span>
                  <select name="bulk_action" class="form-input" style="padding: 4px 8px; font-size: 13px;" required>
                    <option value="" disabled selected>Choose...</option>
                    <option value="empty">Empty (Truncate)</option>
                    <option value="drop">Drop</option>
                  </select>
                  <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Are you sure you want to execute this action on the selected tables?');">Go</button>
                </div>
              </div>
            </form>
            
            <div style="padding: 12px 16px; border-top: 1px solid var(--border); display: flex; gap: 16px;">
              <a href="print.php?db=<?= esc($selected_db) ?>" target="_blank" class="btn btn-secondary btn-sm" style="display: flex; align-items: center; gap: 6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> Print
              </a>
              <a href="print.php?db=<?= esc($selected_db) ?>&dict=1" target="_blank" class="btn btn-secondary btn-sm" style="display: flex; align-items: center; gap: 6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path></svg> Data dictionary
              </a>
            </div>
            
            <?php foreach ($tables as $titem): ?>
              <form id="drop_<?= esc($titem) ?>" method="POST" action="actions.php" class="confirm-delete" style="display:none;">
                <input type="hidden" name="action" value="drop_table">
                <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                <input type="hidden" name="table" value="<?= esc($titem) ?>">
              </form>
            <?php endforeach; ?>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selectAll = document.getElementById('selectAllTables');
                const checkAllText = document.getElementById('checkAllText');
                const checkboxes = document.querySelectorAll('.table-checkbox');
                
                function toggleAll(checked) {
                    checkboxes.forEach(cb => cb.checked = checked);
                    if(selectAll) selectAll.checked = checked;
                    if(checkAllText) checkAllText.checked = checked;
                }
                
                if(selectAll) {
                    selectAll.addEventListener('change', e => toggleAll(e.target.checked));
                }
                if(checkAllText) {
                    checkAllText.addEventListener('change', e => toggleAll(e.target.checked));
                }
                
                checkboxes.forEach(cb => {
                    cb.addEventListener('change', function() {
                        const allChecked = Array.from(checkboxes).every(c => c.checked);
                        if(selectAll) selectAll.checked = allChecked;
                        if(checkAllText) checkAllText.checked = allChecked;
                    });
                });
            });
            </script>
          </div>
        </div>
        <?php if (!empty($views)): ?>
        <div class="panel">
          <div class="panel-header">Views</div>
          <div class="panel-body" style="padding:0">
            <div class="table-wrap" style="border:none"><table class="data-table">
              <thead><tr><th>View</th></tr></thead>
              <tbody><?php foreach ($views as $v): ?><tr><td><?= esc($v) ?></td></tr><?php endforeach; ?></tbody>
            </table></div>
          </div>
        </div>
        <?php endif; ?>
        <div class="panel">
          <div class="panel-header">Database Operations</div>
          <div class="panel-body">
            <form method="POST" action="actions.php" class="confirm-delete inline-form">
              <input type="hidden" name="action" value="drop_database">
              <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
              <button type="submit" class="btn btn-danger">Drop Database</button>
            </form>
            <form method="POST" action="actions.php" class="inline-form" style="margin-left:8px">
              <input type="hidden" name="action" value="toggle_favorite">
              <input type="hidden" name="fav_db" value="<?= esc($selected_db) ?>">
              <button type="submit" class="btn btn-secondary"><?= in_array($selected_db, $_SESSION['favorites'], true) ? 'Remove from Favorites' : 'Add to Favorites' ?></button>
            </form>
          </div>
        </div>
        <?php
        break;

    case 'sql':
        $last_sql = $_SESSION['last_sql'] ?? "USE `$selected_db`;\n";
        ?>
        <div class="panel">
          <div class="panel-header">SQL — <?= esc($selected_db) ?></div>
          <div class="panel-body">
            <form method="POST" action="actions.php">
              <input type="hidden" name="action" value="run_sql">
              <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
              <input type="hidden" name="tab" value="sql">
              <textarea name="sql_query" class="form-textarea" rows="8"><?= esc($last_sql) ?></textarea>
              <div class="form-actions"><button type="submit" class="btn btn-primary">Go</button></div>
            </form>
            <?php if (!empty($_SESSION['sql_result'])): ?>
              <div style="margin-top:16px"><div class="table-wrap"><table class="data-table"><thead><tr>
                <?php foreach ($_SESSION['sql_columns'] as $col): ?><th><?= esc($col) ?></th><?php endforeach; ?>
              </tr></thead><tbody>
                <?php foreach ($_SESSION['sql_result'] as $row): ?><tr><?php foreach ($row as $v): ?><td><?= $v === null ? '<span class="null-val">NULL</span>' : esc($v) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
              </tbody></table></div></div>
              <?php unset($_SESSION['sql_result'], $_SESSION['sql_columns']); ?>
            <?php endif; ?>
          </div>
        </div>
        <?php
        break;

    case 'search':
        ?>
        <div class="panel">
          <div class="panel-header">Search in database</div>
          <div class="panel-body">
            <form method="POST" action="actions.php">
              <input type="hidden" name="action" value="db_search">
              <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
              
              <div class="form-group" style="margin-bottom: 20px;">
                  <label class="form-label">Words or values to search for (wildcard: "%"):</label>
                  <input type="text" name="search_str" class="form-input" style="max-width: 400px;" value="<?= esc($_SESSION['db_search']['str'] ?? '') ?>" required>
              </div>

              <div style="display: flex; gap: 40px; margin-bottom: 20px;">
                  <div>
                      <label class="form-label" style="font-weight: bold; margin-bottom: 8px;">Find:</label>
                      <div style="display: flex; flex-direction: column; gap: 6px;">
                          <label><input type="radio" name="search_type" value="like" <?= ($_SESSION['db_search']['type'] ?? 'like') === 'like' ? 'checked' : '' ?>> at least one of the words</label>
                          <label><input type="radio" name="search_type" value="exact" <?= ($_SESSION['db_search']['type'] ?? '') === 'exact' ? 'checked' : '' ?>> exact match</label>
                          <label><input type="radio" name="search_type" value="regex" <?= ($_SESSION['db_search']['type'] ?? '') === 'regex' ? 'checked' : '' ?>> regular expression</label>
                      </div>
                  </div>
                  
                  <div style="flex-grow: 1;">
                      <label class="form-label" style="font-weight: bold; margin-bottom: 8px;">Inside tables:</label>
                      <select name="search_tables[]" class="form-select" multiple style="height: 150px; width: 100%; max-width: 300px;">
                          <?php foreach ($tables as $t): ?>
                              <option value="<?= esc($t) ?>" <?= (in_array($t, $_SESSION['db_search']['tables'] ?? $tables)) ? 'selected' : '' ?>><?= esc($t) ?></option>
                          <?php endforeach; ?>
                      </select>
                      <div style="margin-top: 6px; font-size: 12px; color: var(--text-muted);">
                          <a href="#" onclick="Array.from(this.previousElementSibling.options).forEach(o => o.selected = true); return false;">Select All</a> / 
                          <a href="#" onclick="Array.from(this.previousElementSibling.previousElementSibling.options).forEach(o => o.selected = false); return false;">Unselect All</a>
                      </div>
                  </div>
              </div>

              <button type="submit" class="btn btn-primary">Go</button>
            </form>

            <?php if (isset($_SESSION['db_search_results'])): ?>
                <div style="margin-top: 30px;">
                    <h3 style="margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 5px;">Search results for "<?= esc($_SESSION['db_search']['str']) ?>"</h3>
                    <?php 
                    $total_matches = 0;
                    foreach ($_SESSION['db_search_results'] as $t => $matches): 
                        if ($matches === false) {
                            echo "<div style='color: var(--danger); margin-bottom: 8px;'>Error searching table <strong>" . esc($t) . "</strong></div>";
                            continue;
                        }
                        if ($matches === 0) continue;
                        $total_matches += $matches;
                    ?>
                        <div style="margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; padding: 8px; background: var(--bg-secondary); border-radius: 4px; border: 1px solid var(--border);">
                            <div>
                                <span style="font-weight: bold; margin-right: 15px;"><?= $matches ?> match(es) inside table</span>
                                <span><i><?= esc($t) ?></i></span>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <a href="actions.php?action=view_db_search_results&db=<?= urlencode($selected_db) ?>&table=<?= urlencode($t) ?>" class="btn btn-secondary btn-sm">Browse</a>
                                <form method="POST" action="actions.php" class="confirm-delete" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_db_search_results">
                                    <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                                    <input type="hidden" name="table" value="<?= esc($t) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($total_matches === 0): ?>
                        <div style="padding: 15px; background: var(--bg-hover); border: 1px solid var(--border); border-radius: 4px;">
                            No matches found.
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 15px; font-weight: bold;">
                            Total: <?= $total_matches ?> match(es)
                        </div>
                    <?php endif; ?>
                </div>
                <?php unset($_SESSION['db_search_results']); ?>
            <?php endif; ?>
          </div>
        </div>
        <?php
        break;

    case 'export':
        ?>
        <div class="panel">
          <div class="panel-header">Export Database — <?= esc($selected_db) ?></div>
          <div class="panel-body">
            <h4 style="margin-bottom:8px">Quick Export</h4>
            <a href="export.php?db=<?= urlencode($selected_db) ?>" class="btn btn-primary">Export as SQL</a>
            <h4 style="margin:16px 0 8px">Custom Export</h4>
            <form method="GET" action="export.php">
              <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
              <input type="hidden" name="type" value="custom">
              <div class="form-group"><label class="form-label">Select Tables (comma separated, empty = all)</label><input type="text" name="tables" class="form-input" placeholder="<?= esc(implode(',', $tables)) ?>"></div>
              <button type="submit" class="btn btn-secondary">Custom Export</button>
            </form>
          </div>
        </div>
        <?php
        break;

    case 'import':
        ?>
        <div class="panel">
          <div class="panel-header">Import — <?= esc($selected_db) ?></div>
          <div class="panel-body">
            <form method="POST" action="actions.php" enctype="multipart/form-data">
              <input type="hidden" name="action" value="import_sql">
              <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
              <input type="hidden" name="tab" value="import">
              <div class="form-group"><label class="form-label">File</label><input type="file" name="import_file" class="form-input" accept=".sql,.zip,.gz,.csv"></div>
              <div class="form-group"><label class="form-label">SQL Text</label><textarea name="import_text" class="form-textarea" rows="6"></textarea></div>
              <button type="submit" class="btn btn-primary">Import</button>
            </form>
          </div>
        </div>
        <?php
        break;

    case 'operations':
        ?>
        <div class="panel">
          <div class="panel-header">Database Operations</div>
          <div class="panel-body">
            <form method="POST" action="actions.php" class="confirm-delete">
              <input type="hidden" name="action" value="drop_database">
              <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
              <button type="submit" class="btn btn-danger">Drop Database</button>
            </form>
          </div>
        </div>
        <?php
        break;

    case 'routines':
        $routines = get_routines($conn, $selected_db);
        ?>
        <div class="panel">
          <div class="panel-header">Routines — Stored Procedures & Functions</div>
          <div class="panel-body" style="padding:0">
            <div style="padding: 16px; border-bottom: 1px solid var(--border);">
              <details>
                <summary class="btn btn-secondary">Create new routine</summary>
                <form method="POST" action="actions.php" style="margin-top:16px;">
                  <input type="hidden" name="action" value="create_routine">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <div class="form-group">
                    <label class="form-label">Routine Name</label>
                    <input type="text" name="routine_name" class="form-input" required>
                  </div>
                  <div class="form-group" style="display:flex;gap:16px;">
                    <div style="flex:1">
                      <label class="form-label">Type</label>
                      <select name="routine_type" class="form-input">
                        <option value="PROCEDURE">PROCEDURE</option>
                        <option value="FUNCTION">FUNCTION</option>
                      </select>
                    </div>
                    <div style="flex:1">
                      <label class="form-label">Return Type (Functions only)</label>
                      <input type="text" name="routine_return" class="form-input" placeholder="e.g. INT, VARCHAR(255)">
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Parameters</label>
                    <input type="text" name="routine_params" class="form-input" placeholder="IN param1 INT, OUT param2 VARCHAR">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Definition</label>
                    <textarea name="routine_def" class="form-textarea" rows="6" required placeholder="BEGIN\n  SELECT * FROM users;\nEND"></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary">Create Routine</button>
                </form>
              </details>
            </div>
            <?php if (empty($routines)): ?>
              <p style="padding:16px;color:var(--text-muted)">No routines found.</p>
            <?php else: ?>
              <div class="table-wrap" style="border:none"><table class="data-table">
                <thead><tr><th>Name</th><th>Type</th><th>Returns</th></tr></thead>
                <tbody><?php foreach ($routines as $r): ?>
                  <tr><td><?= esc($r['ROUTINE_NAME']) ?></td><td><?= esc($r['ROUTINE_TYPE']) ?></td><td><?= esc($r['DTD_IDENTIFIER'] ?? '') ?></td></tr>
                <?php endforeach; ?></tbody>
              </table></div>
            <?php endif; ?>
          </div>
        </div>
        <?php
        break;

    case 'events':
        $events = get_events($conn, $selected_db);
        $scheduler = get_event_scheduler_status($conn);
        ?>
        <div class="panel">
          <div class="panel-header">Events — Scheduled Tasks</div>
          <div class="panel-body" style="padding:0">
            <div style="padding: 16px; border-bottom: 1px solid var(--border); display: flex; gap: 16px; align-items: flex-start;">
              <details style="flex: 1;">
                <summary class="btn btn-secondary">Create new event</summary>
                <form method="POST" action="actions.php" style="margin-top:16px;">
                  <input type="hidden" name="action" value="create_event">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <div class="form-group">
                    <label class="form-label">Event Name</label>
                    <input type="text" name="event_name" class="form-input" required>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Schedule</label>
                    <input type="text" name="event_schedule" class="form-input" required placeholder="EVERY 1 DAY">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Definition</label>
                    <textarea name="event_def" class="form-textarea" rows="6" required placeholder="BEGIN\n  DELETE FROM sessions WHERE expires < NOW();\nEND"></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary">Create Event</button>
                </form>
              </details>
              
              <div style="border: 1px solid var(--border); padding: 12px; border-radius: var(--radius-sm); background: var(--bg-hover);">
                <div style="margin-bottom: 8px; font-weight: 600;">Event scheduler status</div>
                <form method="POST" action="actions.php" style="display:flex; gap: 8px;">
                  <input type="hidden" name="action" value="toggle_event_scheduler">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <input type="hidden" name="status" value="<?= $scheduler ? 'OFF' : 'ON' ?>">
                  <button type="submit" class="btn <?= $scheduler ? 'btn-danger' : 'btn-primary' ?> btn-sm">
                    Turn <?= $scheduler ? 'OFF' : 'ON' ?>
                  </button>
                  <span style="display:flex;align-items:center;margin-left:8px;" class="<?= $scheduler ? 'text-success' : 'text-danger' ?>">
                    Currently: <?= $scheduler ? 'ON' : 'OFF' ?>
                  </span>
                </form>
              </div>
            </div>
            <?php if (empty($events)): ?>
              <p style="padding:16px;color:var(--text-muted)">No events found.</p>
            <?php else: ?>
              <div class="table-wrap" style="border:none"><table class="data-table">
                <thead><tr><th>Name</th><th>Status</th><th>Type</th><th>Execute At</th><th>Interval</th></tr></thead>
                <tbody><?php foreach ($events as $e): ?>
                  <tr><td><?= esc($e['Name']) ?></td><td><?= esc($e['Status']) ?></td><td><?= esc($e['Event_type']) ?></td><td><?= esc($e['Execute_at'] ?? '') ?></td><td><?= esc($e['Interval_value'] ?? '') ?> <?= esc($e['Interval_field'] ?? '') ?></td></tr>
                <?php endforeach; ?></tbody>
              </table></div>
            <?php endif; ?>
          </div>
        </div>
        <?php
        break;

    case 'triggers':
        $triggers = get_triggers($conn, $selected_db);
        ?>
        <div class="panel">
          <div class="panel-header">Triggers</div>
          <div class="panel-body" style="padding:0">
            <div style="padding: 16px; border-bottom: 1px solid var(--border);">
              <details>
                <summary class="btn btn-secondary">Create new trigger</summary>
                <form method="POST" action="actions.php" style="margin-top:16px;">
                  <input type="hidden" name="action" value="create_trigger">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <div class="form-group">
                    <label class="form-label">Trigger Name</label>
                    <input type="text" name="trigger_name" class="form-input" required>
                  </div>
                  <div class="form-group" style="display:flex;gap:16px;">
                    <div style="flex:1">
                      <label class="form-label">Table</label>
                      <select name="trigger_table" class="form-input" required>
                        <?php foreach($tables as $t): ?><option value="<?= esc($t) ?>"><?= esc($t) ?></option><?php endforeach; ?>
                      </select>
                    </div>
                    <div style="flex:1">
                      <label class="form-label">Timing</label>
                      <select name="trigger_time" class="form-input">
                        <option value="BEFORE">BEFORE</option>
                        <option value="AFTER">AFTER</option>
                      </select>
                    </div>
                    <div style="flex:1">
                      <label class="form-label">Event</label>
                      <select name="trigger_event" class="form-input">
                        <option value="INSERT">INSERT</option>
                        <option value="UPDATE">UPDATE</option>
                        <option value="DELETE">DELETE</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Definition</label>
                    <textarea name="trigger_def" class="form-textarea" rows="6" required placeholder="BEGIN\n  SET NEW.updated_at = NOW();\nEND"></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary">Create Trigger</button>
                </form>
              </details>
            </div>
            <?php if (empty($triggers)): ?>
              <p style="padding:16px;color:var(--text-muted)">No triggers found.</p>
            <?php else: ?>
              <div class="table-wrap" style="border:none"><table class="data-table">
                <thead><tr><th>Trigger</th><th>Event</th><th>Table</th><th>Timing</th></tr></thead>
                <tbody><?php foreach ($triggers as $t): ?>
                  <tr><td><?= esc($t['Trigger']) ?></td><td><?= esc($t['Event']) ?></td><td><?= esc($t['Table']) ?></td><td><?= esc($t['Timing']) ?></td></tr>
                <?php endforeach; ?></tbody>
              </table></div>
            <?php endif; ?>
          </div>
        </div>
        <?php
        break;

    case 'designer':
        $tables_data = [];
        foreach ($tables as $t) {
            $cols = get_table_structure($conn, $selected_db, $t);
            $tables_data[$t] = $cols;
        }
        $foreign_keys = get_foreign_keys($conn, $selected_db);
        ?>
        <div class="panel designer-panel" style="display:flex; flex-direction:column; height:calc(100vh - 200px); overflow:hidden;">
          <div class="panel-header" style="flex-shrink:0;">Designer</div>
          <div class="panel-body designer-canvas" id="designerCanvas" style="position:relative; flex:1; overflow:auto; background:var(--bg-card); background-image:radial-gradient(var(--border) 1px, transparent 0); background-size:20px 20px;">
            <svg id="designerSvg" style="position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:1; min-width:3000px; min-height:3000px;"></svg>
            <?php 
            $i = 0;
            foreach ($tables_data as $tname => $cols): 
                $top = 40 + ($i % 3) * 220;
                $left = 40 + floor($i / 3) * 280;
            ?>
            <div class="designer-table" id="table_<?= esc($tname) ?>" style="position:absolute; top:<?= $top ?>px; left:<?= $left ?>px; width:220px; background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-md); box-shadow:var(--shadow-sm); z-index:2; cursor:move; user-select:none;">
              <div class="designer-table-header" style="padding:8px 12px; background:var(--bg-hover); border-bottom:1px solid var(--border); border-radius:calc(var(--radius-md) - 1px) calc(var(--radius-md) - 1px) 0 0; font-weight:600; font-size:13px; display:flex; justify-content:space-between; align-items:center;">
                <?= esc($tname) ?>
              </div>
              <div class="designer-table-body" style="padding:0; max-height:250px; overflow-y:auto; cursor:default;">
                <?php foreach($cols as $c): ?>
                  <div class="designer-column" id="col_<?= esc($tname) ?>_<?= esc($c['Field']) ?>" style="padding:6px 12px; font-size:12px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                    <span>
                      <?php if ($c['Key'] === 'PRI') echo '🔑 '; ?>
                      <?= esc($c['Field']) ?>
                    </span>
                    <span style="color:var(--text-muted);font-size:10px;"><?= esc(preg_replace('/\(.*\)/', '', $c['Type'])) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php $i++; endforeach; ?>
          </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('designerCanvas');
            const svg = document.getElementById('designerSvg');
            const foreignKeys = <?= json_encode($foreign_keys) ?>;
            
            function drawLines() {
                svg.innerHTML = '';
                const canvasRect = canvas.getBoundingClientRect();
                
                foreignKeys.forEach(fk => {
                    const fromCol = document.getElementById('col_' + fk.TABLE_NAME + '_' + fk.COLUMN_NAME);
                    const toCol = document.getElementById('col_' + fk.REFERENCED_TABLE_NAME + '_' + fk.REFERENCED_COLUMN_NAME);
                    
                    if (fromCol && toCol) {
                        const fromRect = fromCol.getBoundingClientRect();
                        const toRect = toCol.getBoundingClientRect();
                        
                        // Calculate relative positions taking into account scroll and bounding box
                        let x1, y1, x2, y2;
                        
                        // Determine which side to draw from based on relative positions
                        if (fromRect.left < toRect.left) {
                            x1 = fromRect.right - canvasRect.left + canvas.scrollLeft;
                            x2 = toRect.left - canvasRect.left + canvas.scrollLeft;
                        } else {
                            x1 = fromRect.left - canvasRect.left + canvas.scrollLeft;
                            x2 = toRect.right - canvasRect.left + canvas.scrollLeft;
                        }
                        
                        y1 = fromRect.top - canvasRect.top + canvas.scrollTop + (fromRect.height / 2);
                        y2 = toRect.top - canvasRect.top + canvas.scrollTop + (toRect.height / 2);
                        
                        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                        const cx = (x1 + x2) / 2;
                        path.setAttribute('d', `M ${x1} ${y1} C ${cx} ${y1}, ${cx} ${y2}, ${x2} ${y2}`);
                        path.setAttribute('fill', 'none');
                        path.setAttribute('stroke', '#2f81f7');
                        path.setAttribute('stroke-width', '1.5');
                        path.setAttribute('stroke-linecap', 'round');
                        svg.appendChild(path);
                        
                        // Dot on referenced table
                        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                        circle.setAttribute('cx', x2);
                        circle.setAttribute('cy', y2);
                        circle.setAttribute('r', '3');
                        circle.setAttribute('fill', '#2f81f7');
                        svg.appendChild(circle);
                    }
                });
            }
            
            let draggedEl = null;
            let startX = 0, startY = 0;
            let initialX = 0, initialY = 0;
            
            document.querySelectorAll('.designer-table').forEach(table => {
                table.addEventListener('mousedown', function(e) {
                    if (e.target.closest('.designer-table-body')) return;
                    draggedEl = table;
                    startX = e.clientX;
                    startY = e.clientY;
                    initialX = parseInt(table.style.left, 10) || 0;
                    initialY = parseInt(table.style.top, 10) || 0;
                    table.style.zIndex = 10;
                });
            });
            
            window.addEventListener('mousemove', function(e) {
                if (!draggedEl) return;
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                draggedEl.style.left = Math.max(0, initialX + dx) + 'px';
                draggedEl.style.top = Math.max(0, initialY + dy) + 'px';
                drawLines();
            });
            
            window.addEventListener('mouseup', function() {
                if (draggedEl) {
                    draggedEl.style.zIndex = 2;
                    draggedEl = null;
                }
            });
            
            setTimeout(drawLines, 50);
            window.addEventListener('resize', drawLines);
            // We don't need scroll listener because SVG scrolls with the content
        });
        </script>
        <?php
        break;


}
