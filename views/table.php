<?php
$conn->select_db($selected_db);
$columns = get_table_structure($conn, $selected_db, $selected_table);
$pk = get_primary_key($conn, $selected_db, $selected_table);
$page_num = max(1, (int)($_GET['page'] ?? 1));
$per_page = 25;
$offset = ($page_num - 1) * $per_page;
$edit_mode = isset($_GET['edit']) && $_GET['edit'] === '1';
$edit_pk_val = $_GET['pk_val'] ?? '';

switch ($active_tab) {
    case 'browse':
        $total = 0;
        $count_res = $conn->query("SELECT COUNT(*) as cnt FROM `$selected_table`");
        if ($count_res) $total = (int)$count_res->fetch_assoc()['cnt'];
        $result = $conn->query("SELECT * FROM `$selected_table` LIMIT $per_page OFFSET $offset");
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) $rows[] = $row;
        }
        $col_names = !empty($rows) ? array_keys($rows[0]) : array_column($columns, 'Field');
        ?>
        <div class="panel">
          <div class="panel-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
              Browse — <?= esc($selected_table) ?>
              <?php if (!empty($is_view)): ?>
                <span style="display:inline-flex;align-items:center;padding:1px 6px;font-size:10px;font-weight:600;letter-spacing:0.5px;border-radius:4px;background:rgba(59,130,246,0.15);color:#60a5fa;margin-left:6px;vertical-align:middle;">VIEW</span>
              <?php endif; ?>
              <span style="font-weight:400;color:var(--text-muted)"><?= $total ?> row(s)</span>
            </div>
            <div>
              <?php if (empty($is_view)): ?>
                <button type="button" class="btn btn-secondary btn-sm" id="btnImportCsv">📥 Import CSV</button>
              <?php endif; ?>
            </div>
          </div>

          <!-- CSV Import Modal -->
          <div class="guide-overlay" id="csvImportModal" hidden>
            <div class="guide-modal" style="max-width:500px;">
              <div class="guide-modal-header">
                <h2 style="font-size:16px; font-weight:600; margin:0;">📥 Import CSV to <?= esc($selected_table) ?></h2>
                <button type="button" class="btn-icon guide-modal-close" id="closeCsvModal">&times;</button>
              </div>
              <div class="guide-modal-body">
                <form method="POST" action="actions.php" enctype="multipart/form-data">
                  <input type="hidden" name="action" value="import_csv">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
                  
                  <div class="form-group">
                    <label class="form-label">Select CSV File</label>
                    <input type="file" name="csv_file" accept=".csv" required style="display:block; width:100%; padding:8px; border:1px solid var(--border-color); background:var(--bg-tertiary); border-radius:4px;">
                  </div>
                  
                  <div class="form-group">
                    <label class="form-label" style="display:flex; align-items:center; gap:8px;">
                      <input type="checkbox" name="has_headers" value="1" checked> First row contains column headers
                    </label>
                  </div>

                  <div class="form-group">
                    <label class="form-label" style="display:flex; align-items:center; gap:8px;">
                      <input type="checkbox" name="truncate_first" value="1"> Empty table before importing
                    </label>
                  </div>

                  <div class="form-actions" style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary">Upload & Import</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <script>
            const csvModal = document.getElementById('csvImportModal');
            document.getElementById('btnImportCsv').addEventListener('click', () => {
              csvModal.removeAttribute('hidden');
              document.body.classList.add('guide-open');
            });
            document.getElementById('closeCsvModal').addEventListener('click', () => {
              csvModal.setAttribute('hidden', '');
              document.body.classList.remove('guide-open');
            });
            csvModal.addEventListener('click', (e) => {
              if (e.target === csvModal) {
                csvModal.setAttribute('hidden', '');
                document.body.classList.remove('guide-open');
              }
            });
          </script>

          <div class="panel-body" style="padding:0">
            <?php if (empty($rows)): ?>
              <p style="padding:16px;color:var(--text-muted)">No records found. <a href="<?= esc(base_url(['db' => $selected_db, 'table' => $selected_table, 'tab' => 'insert'])) ?>">Insert a row</a></p>
            <?php else: ?>
              <form id="bulkRowForm" method="POST" action="actions.php">
                <input type="hidden" name="action" value="bulk_row_action">
                <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
                <input type="hidden" name="pk_col" value="<?= esc($pk) ?>">
                <div class="table-search-bar" style="padding: 10px 16px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 12px; background: var(--bg-hover);">
                  <div style="display: flex; align-items: center; gap: 8px; flex: 1; max-width: 320px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.6;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="filterTableRows" class="form-input" placeholder="Filter rows in view..." style="padding: 4px 8px; font-size: 13px; width: 100%;">
                  </div>
                  <div style="display: flex; align-items: center; gap: 14px; font-size: 12px; color: var(--text-muted);">
                    <span id="filteredRowCount" style="font-variant-numeric: tabular-nums;"></span>
                    <span style="opacity: 0.8; display: inline-flex; align-items: center; gap: 4px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Double-click cell to edit</span>
                  </div>
                </div>
                <div class="table-wrap" style="border:none"><table class="data-table" id="tableBrowse">
                  <thead><tr>
                    <?php if ($pk): ?><th style="width:30px"><input type="checkbox" id="selectAllRows"></th><?php endif; ?>
                    <?php foreach ($col_names as $c): ?><th><?= esc($c) ?></th><?php endforeach; ?>
                    <th>Actions</th>
                  </tr></thead>
                  <tbody>
                    <?php foreach ($rows as $row): ?>
                      <tr>
                        <?php if ($pk): ?><td><input type="checkbox" name="selected_rows[]" class="row-checkbox" value="<?= esc($row[$pk]) ?>"></td><?php endif; ?>
                        <?php foreach ($row as $col_name => $v): ?>
                          <td class="editable-cell" data-col="<?= esc($col_name) ?>" data-pk="<?= esc($pk ? ($row[$pk] ?? '') : '') ?>" data-val="<?= esc($v ?? '') ?>" title="Double-click to edit"><?= $v === null ? '<span class="null-val">NULL</span>' : esc(strlen($v) > 50 ? substr($v,0,50).'...' : $v) ?></td>
                        <?php endforeach; ?>
                        <td class="row-actions">
                          <?php if ($pk): ?>
                            <a href="<?= esc(base_url(['db' => $selected_db, 'table' => $selected_table, 'tab' => 'insert', 'edit' => '1', 'pk_val' => $row[$pk]])) ?>" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:4px;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Edit</a>
                            <button type="submit" form="copy_<?= esc($row[$pk]) ?>" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:4px;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg> Copy</button>
                            <button type="submit" form="del_<?= esc($row[$pk]) ?>" class="btn btn-danger btn-sm" style="display:inline-flex;align-items:center;gap:4px;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg> Delete</button>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table></div>
                
                <?php if ($pk): ?>
                <div style="padding: 12px 16px; background: var(--bg-hover); border-top: 1px solid var(--border); display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <label style="cursor: pointer; display: flex; align-items: center; gap: 6px; font-weight: 500;">
                      <input type="checkbox" id="checkAllRowsText"> Check all
                    </label>
                  </div>
                  
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <span>With selected:</span>
                    <button type="submit" name="bulk_action" value="edit" class="btn btn-secondary btn-sm" style="display:flex;align-items:center;gap:4px;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Edit</button>
                    <button type="submit" name="bulk_action" value="copy" class="btn btn-secondary btn-sm" style="display:flex;align-items:center;gap:4px;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg> Copy</button>
                    <button type="submit" name="bulk_action" value="delete" class="btn btn-danger btn-sm" style="display:flex;align-items:center;gap:4px;" onclick="return confirm('Are you sure you want to delete these rows?');"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg> Delete</button>
                    <button type="submit" name="bulk_action" value="export" class="btn btn-secondary btn-sm" style="display:flex;align-items:center;gap:4px;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> Export</button>
                  </div>
                </div>
                <?php endif; ?>
              </form>

              <div style="padding: 12px 16px; border-top: 1px solid var(--border); display: flex; gap: 16px; flex-wrap: wrap;">
                <div style="padding:4px 8px; border:1px solid var(--border); border-radius:4px; font-size:12px; font-weight:bold; background:var(--bg-secondary);">Query results operations</div>
                <a href="print.php?db=<?= esc($selected_db) ?>&table=<?= esc($selected_table) ?>" target="_blank" class="btn btn-secondary btn-sm" style="display: flex; align-items: center; gap: 6px;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> Print
                </a>
                <button type="button" id="btnCopyTable" onclick="copyTableToClipboard(this)" class="btn btn-secondary btn-sm" style="display: flex; align-items: center; gap: 6px;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg> Copy to clipboard
                </button>
                <a href="<?= esc(base_url(['db' => $selected_db, 'table' => $selected_table, 'tab' => 'export'])) ?>" class="btn btn-secondary btn-sm" style="display: flex; align-items: center; gap: 6px;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> Export
                </a>
                <button type="button" id="btnDisplayChart" class="btn btn-secondary btn-sm" style="display: flex; align-items: center; gap: 6px;" onclick="openChartModal()">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg> Display chart
                </button>
                <form method="POST" action="actions.php" class="inline-form">
                    <input type="hidden" name="action" value="create_view_from_table">
                    <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                    <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
                    <button type="button" class="btn btn-secondary btn-sm" style="display: flex; align-items: center; gap: 6px;" onclick="let name = prompt('Enter a name for the new view:'); if(name && name.trim()) { this.form.insertAdjacentHTML('beforeend', `<input type=\'hidden\' name=\'view_name\' value=\'${name.trim()}\'>`); this.form.submit(); }">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg> Create view
                    </button>
                </form>
              </div>

              <!-- Chart Visualization Modal -->
              <div class="guide-overlay" id="chartModal" hidden>
                <div class="guide-modal" style="max-width: 960px; width: 95%;">
                  <div class="guide-modal-header" style="display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid var(--border);">
                    <h2 style="font-size: 15px; font-weight: 600; margin: 0; display: inline-flex; align-items: center; gap: 8px;">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                      Data Chart — <?= esc($selected_table) ?>
                    </h2>
                    <button type="button" class="guide-modal-close" id="closeChartModal" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted);">&times;</button>
                  </div>
                  <div class="guide-modal-body" style="padding: 20px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; align-items: flex-end; background: var(--bg-hover); padding: 14px; border-radius: 8px; border: 1px solid var(--border);">
                      <div style="flex: 1; min-width: 140px;">
                        <label style="font-size: 12px; font-weight: 500; color: var(--text-muted); display: block; margin-bottom: 4px;">Chart Type</label>
                        <select id="chartType" class="form-select" style="width: 100%; font-size: 13px;">
                          <option value="bar">Bar (Vertical)</option>
                          <option value="horizontalBar">Bar (Horizontal)</option>
                          <option value="line">Line</option>
                          <option value="pie">Pie</option>
                          <option value="doughnut">Doughnut</option>
                          <option value="polarArea">Polar Area</option>
                        </select>
                      </div>
                      <div style="flex: 1; min-width: 140px;">
                        <label style="font-size: 12px; font-weight: 500; color: var(--text-muted); display: block; margin-bottom: 4px;">Label (X-Axis)</label>
                        <select id="chartLabelCol" class="form-select" style="width: 100%; font-size: 13px;">
                          <?php foreach ($col_names as $col): ?>
                            <option value="<?= esc($col) ?>"><?= esc($col) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div style="flex: 1; min-width: 140px;">
                        <label style="font-size: 12px; font-weight: 500; color: var(--text-muted); display: block; margin-bottom: 4px;">Value (Y-Axis)</label>
                        <select id="chartValueCol" class="form-select" style="width: 100%; font-size: 13px;">
                          <option value="__count__">[Count of rows / records]</option>
                          <?php foreach ($col_names as $col): ?>
                            <option value="<?= esc($col) ?>"><?= esc($col) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div style="flex: 1; min-width: 130px;">
                        <label style="font-size: 12px; font-weight: 500; color: var(--text-muted); display: block; margin-bottom: 4px;">Aggregation</label>
                        <select id="chartAgg" class="form-select" style="width: 100%; font-size: 13px;">
                          <option value="auto">Auto (Smart)</option>
                          <option value="sum">Sum</option>
                          <option value="avg">Average</option>
                          <option value="raw">None (Row-by-Row)</option>
                        </select>
                      </div>
                      <div>
                        <button type="button" id="btnExportChartPng" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; height: 35px;">
                          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> Export PNG
                        </button>
                      </div>
                    </div>
                    <div style="position: relative; height: 420px; width: 100%; background: var(--bg-card); border-radius: 8px; border: 1px solid var(--border); padding: 16px; box-sizing: border-box;">
                      <canvas id="xddbChartCanvas"></canvas>
                    </div>
                  </div>
                </div>
              </div>

              <?php if ($pk): foreach ($rows as $row): ?>
                <form id="copy_<?= esc($row[$pk]) ?>" method="POST" action="actions.php" style="display:none;">
                  <input type="hidden" name="action" value="copy_row">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
                  <input type="hidden" name="pk_col" value="<?= esc($pk) ?>">
                  <input type="hidden" name="pk_val" value="<?= esc($row[$pk]) ?>">
                </form>
                <form id="del_<?= esc($row[$pk]) ?>" method="POST" action="actions.php" class="confirm-delete" style="display:none;">
                  <input type="hidden" name="action" value="delete_row">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
                  <input type="hidden" name="pk_col" value="<?= esc($pk) ?>">
                  <input type="hidden" name="pk_val" value="<?= esc($row[$pk]) ?>">
                </form>
              <?php endforeach; endif; ?>

              <script>
              document.addEventListener('DOMContentLoaded', function() {
                  const selectAll = document.getElementById('selectAllRows');
                  const checkAllText = document.getElementById('checkAllRowsText');
                  const checkboxes = document.querySelectorAll('.row-checkbox');
                  
                  function toggleAll(checked) {
                      checkboxes.forEach(cb => cb.checked = checked);
                      if(selectAll) selectAll.checked = checked;
                      if(checkAllText) checkAllText.checked = checked;
                  }
                  
                  if(selectAll) selectAll.addEventListener('change', e => toggleAll(e.target.checked));
                  if(checkAllText) checkAllText.addEventListener('change', e => toggleAll(e.target.checked));
                  
                  checkboxes.forEach(cb => {
                      cb.addEventListener('change', function() {
                          const allChecked = Array.from(checkboxes).every(c => c.checked);
                          if(selectAll) selectAll.checked = allChecked;
                          if(checkAllText) checkAllText.checked = allChecked;
                      });
                  });
              });

              function copyTableToClipboard(btn) {
                  let table = document.getElementById('tableBrowse');
                  if (!table) return;
                  let lines = [];
                  for (let row of table.rows) {
                      let rowText = [];
                      for (let i = 0; i < row.cells.length; i++) {
                          if (i === 0 && row.cells[0].querySelector('input[type=checkbox]')) continue;
                          if (i === row.cells.length - 1) continue;
                          let val = (row.cells[i].innerText || '').trim().replace(/[\r\n\t]+/g, ' ');
                          rowText.push(val);
                      }
                      if (rowText.length > 0) {
                          lines.push(rowText.join("\t"));
                      }
                  }
                  let text = lines.join("\n") + "\n";
                  
                  function onSuccess() {
                      if (btn) {
                          let origHtml = btn.innerHTML;
                          btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> <span style="color:#10b981;font-weight:600;">Copied!</span>';
                          setTimeout(() => { btn.innerHTML = origHtml; }, 2500);
                      }
                      let toast = document.createElement('div');
                      toast.className = 'alert alert-success';
                      toast.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;box-shadow:0 8px 24px rgba(0,0,0,0.3);';
                      toast.innerHTML = '<strong>✓ Copied!</strong> Table data copied to clipboard in tab-separated format.';
                      document.body.appendChild(toast);
                      setTimeout(() => { if (toast.parentNode) toast.remove(); }, 3000);
                  }

                  if (navigator.clipboard && navigator.clipboard.writeText) {
                      navigator.clipboard.writeText(text).then(onSuccess).catch(() => fallbackCopy(text, onSuccess));
                  } else {
                      fallbackCopy(text, onSuccess);
                  }
              }

              function fallbackCopy(text, onSuccess) {
                  let ta = document.createElement('textarea');
                  ta.value = text;
                  ta.style.position = 'fixed';
                  ta.style.left = '-9999px';
                  ta.style.top = '0';
                  document.body.appendChild(ta);
                  ta.focus();
                  ta.select();
                  try {
                      document.execCommand('copy');
                      if (onSuccess) onSuccess();
                  } catch (e) {
                      alert('Could not copy to clipboard: ' + e);
                  }
                  document.body.removeChild(ta);
              }

              // Chart Visualization Implementation
              const rawChartRows = <?= json_encode($rows) ?>;
              const chartColumns = <?= json_encode($col_names) ?>;
              let chartInstance = null;

              function openChartModal() {
                  if (typeof Chart === 'undefined') {
                      alert('Chart.js library is not loaded. Please ensure assets/js/chart.min.js is present.');
                      return;
                  }
                  const modal = document.getElementById('chartModal');
                  if (!modal) return;
                  modal.removeAttribute('hidden');
                  document.body.classList.add('guide-open');
                  initChartDefaults();
                  updateChart();
              }

              function closeChartModal() {
                  const modal = document.getElementById('chartModal');
                  if (!modal) return;
                  modal.setAttribute('hidden', '');
                  document.body.classList.remove('guide-open');
              }

              function initChartDefaults() {
                  if (!rawChartRows || rawChartRows.length === 0) return;
                  const numericCols = [];
                  const textCols = [];
                  chartColumns.forEach(col => {
                      let isNum = false;
                      let countChecked = 0;
                      for (let r of rawChartRows) {
                          let val = r[col];
                          if (val !== null && val !== undefined && String(val).trim() !== '') {
                              countChecked++;
                              if (!isNaN(Number(val))) {
                                  isNum = true;
                              } else {
                                  isNum = false;
                                  break;
                              }
                          }
                      }
                      if (isNum && countChecked > 0) {
                          numericCols.push(col);
                      } else {
                          textCols.push(col);
                      }
                  });

                  const labelSelect = document.getElementById('chartLabelCol');
                  const valueSelect = document.getElementById('chartValueCol');

                  let bestLabel = textCols.find(c => /name|title|course|job|role|type|category|status|dept|city|country|label/i.test(c))
                               || textCols[0]
                               || chartColumns[0];

                  let bestValue = numericCols.find(c => !/id$/i.test(c))
                               || numericCols[0]
                               || '__count__';

                  if (labelSelect && !labelSelect.dataset.userChanged) {
                      labelSelect.value = bestLabel;
                  }
                  if (valueSelect && !valueSelect.dataset.userChanged) {
                      valueSelect.value = bestValue;
                  }
              }

              function updateChart() {
                  if (typeof Chart === 'undefined') return;
                  const canvas = document.getElementById('xddbChartCanvas');
                  if (!canvas) return;

                  const chartType = document.getElementById('chartType').value;
                  const labelCol = document.getElementById('chartLabelCol').value;
                  const valueCol = document.getElementById('chartValueCol').value;
                  const aggMode = document.getElementById('chartAgg').value;

                  let labels = [];
                  let data = [];
                  let datasetLabel = valueCol;

                  if (valueCol === '__count__') {
                      datasetLabel = 'Count of ' + labelCol;
                      const counts = {};
                      rawChartRows.forEach(r => {
                          let k = (r[labelCol] !== null && r[labelCol] !== undefined && String(r[labelCol]).trim() !== '') ? String(r[labelCol]) : 'NULL';
                          counts[k] = (counts[k] || 0) + 1;
                      });
                      labels = Object.keys(counts);
                      data = Object.values(counts);
                  } else {
                      const shouldGroup = (aggMode === 'sum' || aggMode === 'avg') || (aggMode === 'auto' && rawChartRows.length > 25);
                      if (shouldGroup) {
                          const sums = {};
                          const counts = {};
                          rawChartRows.forEach(r => {
                              let k = (r[labelCol] !== null && r[labelCol] !== undefined && String(r[labelCol]).trim() !== '') ? String(r[labelCol]) : 'NULL';
                              let num = parseFloat(r[valueCol]);
                              if (!isNaN(num)) {
                                  sums[k] = (sums[k] || 0) + num;
                                  counts[k] = (counts[k] || 0) + 1;
                              }
                          });
                          labels = Object.keys(sums);
                          if (aggMode === 'avg') {
                              datasetLabel = 'Average of ' + valueCol;
                              data = labels.map(k => +(sums[k] / (counts[k] || 1)).toFixed(2));
                          } else {
                              datasetLabel = 'Total of ' + valueCol;
                              data = labels.map(k => +sums[k].toFixed(2));
                          }
                      } else {
                          datasetLabel = valueCol;
                          labels = rawChartRows.map((r, i) => {
                              return (r[labelCol] !== null && r[labelCol] !== undefined && String(r[labelCol]).trim() !== '') ? String(r[labelCol]) : `Row #${i+1}`;
                          });
                          data = rawChartRows.map(r => {
                              let num = parseFloat(r[valueCol]);
                              return isNaN(num) ? 0 : num;
                          });
                      }
                  }

                  const palette = [
                      '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                      '#ec4899', '#06b6d4', '#14b8a6', '#f97316', '#6366f1',
                      '#e11d48', '#84cc16', '#a855f7', '#0ea5e9', '#eab308'
                  ];
                  const colors = labels.map((_, i) => palette[i % palette.length]);

                  if (chartInstance) {
                      chartInstance.destroy();
                  }

                  const actualType = chartType === 'horizontalBar' ? 'bar' : chartType;
                  const isHorizontal = chartType === 'horizontalBar';
                  const isCartesian = ['bar', 'line'].includes(actualType);
                  const isLine = actualType === 'line';

                  const dataset = {
                      label: datasetLabel,
                      data: data,
                      backgroundColor: isLine ? 'rgba(59, 130, 246, 0.18)' : (['pie', 'doughnut', 'polarArea'].includes(actualType) ? colors : colors),
                      borderColor: isLine ? '#3b82f6' : (['pie', 'doughnut', 'polarArea'].includes(actualType) ? 'var(--bg-card)' : colors),
                      borderWidth: isLine ? 2 : 1,
                      fill: isLine,
                      tension: 0.3
                  };

                  const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
                  const textColor = isDark ? '#94a3b8' : '#475569';
                  const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.06)';

                  const options = {
                      responsive: true,
                      maintainAspectRatio: false,
                      indexAxis: isHorizontal ? 'y' : 'x',
                      animation: { duration: 350 },
                      plugins: {
                          legend: {
                              display: ['pie', 'doughnut', 'polarArea'].includes(actualType) || isLine,
                              labels: { color: textColor, boxWidth: 12, padding: 12 }
                          },
                          tooltip: {
                              backgroundColor: isDark ? '#161b22' : '#ffffff',
                              titleColor: isDark ? '#f0f6fc' : '#0f172a',
                              bodyColor: isDark ? '#8b949e' : '#334155',
                              borderColor: isDark ? '#30363d' : '#e2e8f0',
                              borderWidth: 1,
                              padding: 10,
                              cornerRadius: 6
                          }
                      }
                  };

                  if (isCartesian) {
                      options.scales = {
                          x: {
                              ticks: { color: textColor },
                              grid: { color: gridColor }
                          },
                          y: {
                              ticks: { color: textColor },
                              grid: { color: gridColor },
                              beginAtZero: true
                          }
                      };
                  }

                  chartInstance = new Chart(canvas, {
                      type: actualType,
                      data: {
                          labels: labels,
                          datasets: [dataset]
                      },
                      options: options
                  });
              }

              document.addEventListener('DOMContentLoaded', function() {
                  const chartModal = document.getElementById('chartModal');
                  if (chartModal) {
                      document.getElementById('closeChartModal')?.addEventListener('click', closeChartModal);
                      chartModal.addEventListener('click', (e) => {
                          if (e.target === chartModal) closeChartModal();
                      });
                      document.getElementById('btnExportChartPng')?.addEventListener('click', function() {
                          if (!chartInstance) return;
                          const a = document.createElement('a');
                          a.href = chartInstance.toBase64Image();
                          a.download = `xddb-chart-<?= esc($selected_table) ?>.png`;
                          a.click();
                      });
                      ['chartType', 'chartLabelCol', 'chartValueCol', 'chartAgg'].forEach(id => {
                          const el = document.getElementById(id);
                          if (el) {
                              el.addEventListener('change', () => {
                                  el.dataset.userChanged = 'true';
                                  updateChart();
                              });
                          }
                      });
                  }
              });
              </script>

              <div class="pagination" style="padding:12px 16px; border-top: 1px solid var(--border);">
                <?php if ($page_num > 1): ?><a href="<?= esc(base_url(['db' => $selected_db, 'table' => $selected_table, 'tab' => 'browse', 'page' => $page_num - 1])) ?>" class="btn btn-secondary btn-sm">← Previous</a><?php endif; ?>
                Page <?= $page_num ?> of <?= max(1, ceil($total / $per_page)) ?>
                <?php if ($offset + $per_page < $total): ?><a href="<?= esc(base_url(['db' => $selected_db, 'table' => $selected_table, 'tab' => 'browse', 'page' => $page_num + 1])) ?>" class="btn btn-secondary btn-sm">Next →</a><?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <?php
        break;

    case 'structure':
        if (!empty($is_view)):
            $cv_res = $conn->query("SHOW CREATE VIEW `$selected_table`");
            $create_view_sql = ($cv_res && $cv_row = $cv_res->fetch_row()) ? ($cv_row[1] ?? '') : '';
            ?>
            <div class="stats-grid">
              <div class="stat-card"><div class="stat-label">Type</div><div class="stat-value" style="font-size:14px;color:#60a5fa;">VIEW (Virtual)</div></div>
              <div class="stat-card"><div class="stat-label">Columns</div><div class="stat-value"><?= count($columns) ?></div></div>
              <div class="stat-card"><div class="stat-label">Storage</div><div class="stat-value" style="font-size:14px">Dynamic (0 B)</div></div>
              <div class="stat-card"><div class="stat-label">Status</div><div class="stat-value" style="font-size:14px;color:var(--success);">Active</div></div>
            </div>
            <?php if ($create_view_sql): ?>
            <div class="panel">
              <div class="panel-header">View Definition (SQL Query)</div>
              <div class="panel-body">
                <pre style="background:var(--bg-tertiary);padding:12px;border:1px solid var(--border-color);border-radius:6px;font-size:12px;white-space:pre-wrap;word-break:break-all;color:var(--text);margin:0;font-family:monospace;"><?= esc($create_view_sql) ?></pre>
              </div>
            </div>
            <?php endif; ?>
            <div class="panel">
              <div class="panel-header">Columns in View — <?= esc($selected_table) ?></div>
              <div class="panel-body" style="padding:0">
                <div class="table-wrap" style="border:none"><table class="data-table">
                  <thead><tr><th>#</th><th>Column</th><th>Type</th><th>Collation</th><th>Null</th><th>Default</th><th>Extra</th></tr></thead>
                  <tbody>
                    <?php foreach ($columns as $i => $col): ?>
                    <tr>
                      <td><?= $i + 1 ?></td>
                      <td><strong><?= esc($col['Field']) ?></strong></td>
                      <td><?= esc($col['Type']) ?></td>
                      <td><?= esc($col['Collation'] ?? '') ?></td>
                      <td><?= esc($col['Null']) ?></td>
                      <td><?= $col['Default'] === null ? '<span class="null-val">NULL</span>' : esc($col['Default']) ?></td>
                      <td><?= esc($col['Extra']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table></div>
              </div>
            </div>
            <?php
        else:
            $indexes = get_table_indexes($conn, $selected_db, $selected_table);
            $status = get_table_status($conn, $selected_db, $selected_table);
            ?>
            <div class="stats-grid">
              <div class="stat-card"><div class="stat-label">Rows</div><div class="stat-value"><?= esc($status['Rows'] ?? 0) ?></div></div>
              <div class="stat-card"><div class="stat-label">Engine</div><div class="stat-value" style="font-size:14px"><?= esc($status['Engine'] ?? '') ?></div></div>
              <div class="stat-card"><div class="stat-label">Collation</div><div class="stat-value" style="font-size:14px"><?= esc($status['Collation'] ?? '') ?></div></div>
              <div class="stat-card"><div class="stat-label">Size</div><div class="stat-value" style="font-size:14px"><?= format_bytes(($status['Data_length'] ?? 0) + ($status['Index_length'] ?? 0)) ?></div></div>
            </div>
            <div class="panel">
              <div class="panel-header">Table Structure — <?= esc($selected_table) ?></div>
              <div class="panel-body" style="padding:0">
                <div class="table-wrap" style="border:none"><table class="data-table">
                  <thead><tr><th>#</th><th>Column</th><th>Type</th><th>Collation</th><th>Null</th><th>Default</th><th>Extra</th><th>Actions</th></tr></thead>
                  <tbody>
                    <?php foreach ($columns as $i => $col): ?>
                    <tr>
                      <td><?= $i + 1 ?></td>
                      <td><strong><?= esc($col['Field']) ?></strong><?= $col['Key'] === 'PRI' ? ' 🔑' : '' ?></td>
                      <td><?= esc($col['Type']) ?></td>
                      <td><?= esc($col['Collation'] ?? '') ?></td>
                      <td><?= esc($col['Null']) ?></td>
                      <td><?= $col['Default'] === null ? '<span class="null-val">NULL</span>' : esc($col['Default']) ?></td>
                      <td><?= esc($col['Extra']) ?></td>
                      <td>
                        <form method="POST" action="actions.php" class="inline-form confirm-delete">
                          <input type="hidden" name="action" value="drop_column">
                          <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                          <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
                          <input type="hidden" name="col_name" value="<?= esc($col['Field']) ?>">
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
              <div class="panel-header">Add Column</div>
              <div class="panel-body">
                <form method="POST" action="actions.php">
                  <input type="hidden" name="action" value="add_column">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
                  <div class="form-row">
                    <div class="form-group"><label class="form-label">Name</label><input type="text" name="col_name" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Type</label><select name="col_type" class="form-select"><option>VARCHAR</option><option>INT</option><option>BIGINT</option><option>TEXT</option><option>DATE</option><option>DATETIME</option><option>FLOAT</option><option>DECIMAL</option><option>BOOLEAN</option></select></div>
                    <div class="form-group"><label class="form-label">Length</label><input type="text" name="col_length" class="form-input" value="255"></div>
                    <div class="form-group"><label class="form-label">Default</label><input type="text" name="col_default" class="form-input"></div>
                    <div class="form-group"><label class="form-label">After</label><select name="after_col" class="form-select"><option value="">First</option><?php foreach ($columns as $c): ?><option value="<?= esc($c['Field']) ?>"><?= esc($c['Field']) ?></option><?php endforeach; ?></select></div>
                  </div>
                  <label class="checkbox-label"><input type="checkbox" name="col_null" checked> Allow NULL</label>
                  <div class="form-group" style="margin-top:8px"><label class="form-label">Extra</label><input type="text" name="col_extra" class="form-input" placeholder="AUTO_INCREMENT"></div>
                  <div class="form-actions"><button type="submit" class="btn btn-primary">Add Column</button></div>
                </form>
              </div>
            </div>
            <?php
        endif;
        break;

    case 'sql':
        $last_sql = $_SESSION['last_sql'] ?? "SELECT * FROM `$selected_table` LIMIT 25;";
        ?>
        <div class="panel">
          <div class="panel-header">SQL — <?= esc($selected_table) ?></div>
          <div class="panel-body">
            <form method="POST" action="actions.php">
              <input type="hidden" name="action" value="run_sql">
              <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
              <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
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
          <div class="panel-header">Search — <?= esc($selected_table) ?></div>
          <div class="panel-body">
            <form method="POST" action="actions.php">
              <input type="hidden" name="action" value="search_table">
              <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
              <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
              <div class="form-row">
                <div class="form-group"><label class="form-label">Column</label><select name="search_col" class="form-select" required><?php foreach ($columns as $c): ?><option value="<?= esc($c['Field']) ?>"><?= esc($c['Field']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label class="form-label">Value</label><input type="text" name="search_val" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Type</label><select name="search_type" class="form-select"><option value="like">Contains</option><option value="exact">Exact</option><option value="begin">Starts with</option></select></div>
              </div>
              <button type="submit" class="btn btn-primary">Search</button>
            </form>
            <?php if (!empty($_SESSION['search_result'])): ?>
              <div style="margin-top:16px"><div class="table-wrap"><table class="data-table"><thead><tr>
                <?php foreach ($_SESSION['search_columns'] as $col): ?><th><?= esc($col) ?></th><?php endforeach; ?>
              </tr></thead><tbody>
                <?php foreach ($_SESSION['search_result'] as $row): ?><tr><?php foreach ($row as $v): ?><td><?= $v === null ? '<span class="null-val">NULL</span>' : esc($v) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
              </tbody></table></div></div>
              <?php unset($_SESSION['search_result'], $_SESSION['search_columns']); ?>
            <?php endif; ?>
          </div>
        </div>
        <?php
        break;

    case 'insert':
        $is_bulk_edit = !empty($_GET['bulk_edit']) && !empty($_SESSION['bulk_edit_pks']) && (($_SESSION['bulk_edit_table'] ?? '') === $selected_table);
        if ($is_bulk_edit) {
            $bulk_pks = $_SESSION['bulk_edit_pks'];
            $escaped_pks = array_map(function($val) use ($conn) {
                return "'" . $conn->real_escape_string($val) . "'";
            }, $bulk_pks);
            $bulk_res = $conn->query("SELECT * FROM `$selected_table` WHERE `" . $conn->real_escape_string($pk) . "` IN (" . implode(',', $escaped_pks) . ")");
            $bulk_rows = [];
            if ($bulk_res) {
                while ($r = $bulk_res->fetch_assoc()) {
                    $bulk_rows[] = $r;
                }
            }
            ?>
            <div class="panel">
              <div class="panel-header" style="display:flex;align-items:center;justify-content:space-between;">
                <div>Bulk Edit — <?= esc($selected_table) ?> <span style="font-size:12px;color:var(--text-muted);font-weight:normal;">(Editing <?= count($bulk_rows) ?> selected row<?= count($bulk_rows) === 1 ? '' : 's' ?>)</span></div>
                <a href="<?= esc(base_url(['db' => $selected_db, 'table' => $selected_table, 'tab' => 'browse'])) ?>" class="btn btn-secondary btn-sm">Cancel</a>
              </div>
              <div class="panel-body">
                <form method="POST" action="actions.php">
                  <input type="hidden" name="action" value="bulk_update_rows">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
                  <input type="hidden" name="pk_col" value="<?= esc($pk) ?>">

                  <?php foreach ($bulk_rows as $idx => $row): ?>
                    <div style="background:var(--bg-hover);border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:20px;">
                      <div style="font-weight:600;font-size:13px;margin-bottom:12px;display:flex;align-items:center;gap:8px;color:var(--text);">
                        <span style="background:var(--accent);color:#fff;width:22px;height:22px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:bold;"><?= $idx + 1 ?></span>
                        Row where <code><?= esc($pk) ?></code> = <strong><?= esc($row[$pk]) ?></strong>
                      </div>
                      <div class="table-wrap" style="background:var(--bg-card);border-radius:6px;">
                        <table class="data-table">
                          <thead><tr><th style="width:20%">Column</th><th style="width:15%">Type</th><th style="width:10%">Null</th><th>Value</th></tr></thead>
                          <tbody>
                            <?php foreach ($columns as $col):
                              $field = $col['Field'];
                              $is_pk_field = ($field === $pk);
                              $val = $row[$field] ?? '';
                              $is_ai = ($is_pk_field && stripos($col['Extra'] ?? '', 'auto_increment') !== false);
                            ?>
                            <tr>
                              <td><strong><?= esc($field) ?></strong></td>
                              <td><code style="font-size:11px;"><?= esc($col['Type']) ?></code></td>
                              <td>
                                <?php if ($col['Null'] === 'YES'): ?>
                                  <label class="checkbox-label" style="font-size:12px;"><input type="checkbox" name="rows[<?= esc($row[$pk]) ?>][nulls][<?= esc($field) ?>]" value="1" <?= $row[$field] === null ? 'checked' : '' ?>> NULL</label>
                                <?php else: ?>
                                  <span style="color:var(--text-muted);font-size:12px;">No</span>
                                <?php endif; ?>
                              </td>
                              <td>
                                <?php if (stripos($col['Type'], 'text') !== false): ?>
                                  <textarea name="rows[<?= esc($row[$pk]) ?>][fields][<?= esc($field) ?>]" class="form-textarea" rows="2"><?= esc($val) ?></textarea>
                                <?php else: ?>
                                  <input type="text" name="rows[<?= esc($row[$pk]) ?>][fields][<?= esc($field) ?>]" class="form-input" value="<?= esc($val) ?>" <?= $is_ai ? 'readonly style="opacity:0.6;"' : '' ?>>
                                <?php endif; ?>
                              </td>
                            </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  <?php endforeach; ?>

                  <div class="form-actions" style="margin-top:20px;display:flex;gap:12px;">
                    <button type="submit" class="btn btn-primary">Save All Changes (<?= count($bulk_rows) ?> rows)</button>
                    <a href="<?= esc(base_url(['db' => $selected_db, 'table' => $selected_table, 'tab' => 'browse'])) ?>" class="btn btn-secondary">Cancel</a>
                  </div>
                </form>
              </div>
            </div>
            <?php
        } else {
            $edit_row = null;
            if ($edit_mode && $pk && $edit_pk_val) {
                $res = $conn->query("SELECT * FROM `$selected_table` WHERE `" . $conn->real_escape_string($pk) . "` = '" . $conn->real_escape_string($edit_pk_val) . "'");
                if ($res) $edit_row = $res->fetch_assoc();
            }
            $is_edit = $edit_row !== null;
            ?>
            <div class="panel">
              <div class="panel-header"><?= $is_edit ? 'Edit Row' : 'Insert Row' ?> — <?= esc($selected_table) ?></div>
              <div class="panel-body">
                <?php if (!empty($is_view)): ?>
                  <div style="background:rgba(59,130,246,0.1);border-left:3px solid #3b82f6;padding:10px 14px;border-radius:4px;margin-bottom:16px;font-size:13px;color:var(--text);">
                    ℹ <strong>View Notice:</strong> This is a virtual table (View). Inserts are only allowed in MySQL if the view references a single base table without aggregate functions, DISTINCT, GROUP BY, or UNION.
                  </div>
                <?php endif; ?>
                <form method="POST" action="actions.php">
                  <input type="hidden" name="action" value="<?= $is_edit ? 'update_row' : 'insert_row' ?>">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
                  <?php if ($is_edit): ?>
                    <input type="hidden" name="pk_col" value="<?= esc($pk) ?>">
                    <input type="hidden" name="pk_val" value="<?= esc($edit_pk_val) ?>">
                  <?php endif; ?>
                  <div class="table-wrap"><table class="data-table">
                    <thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Value</th></tr></thead>
                    <tbody>
                      <?php foreach ($columns as $col):
                        $field = $col['Field'];
                        $is_pk = ($field === $pk);
                        if ($is_edit && $is_pk && strpos($col['Extra'], 'auto_increment') !== false) continue;
                        $val = $is_edit ? ($edit_row[$field] ?? '') : '';
                      ?>
                      <tr>
                        <td><strong><?= esc($field) ?></strong></td>
                        <td><?= esc($col['Type']) ?></td>
                        <td><?= esc($col['Null']) ?></td>
                        <td>
                          <?php if (stripos($col['Type'], 'text') !== false): ?>
                            <textarea name="fields[<?= esc($field) ?>]" class="form-textarea" rows="2"><?= esc($val) ?></textarea>
                          <?php else: ?>
                            <input type="text" name="fields[<?= esc($field) ?>]" class="form-input" value="<?= esc($val) ?>">
                          <?php endif; ?>
                          <?php if ($col['Null'] === 'YES'): ?>
                            <label class="checkbox-label" style="margin-top:4px"><input type="checkbox" name="nulls[<?= esc($field) ?>]"> NULL</label>
                          <?php endif; ?>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table></div>
                  <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= $is_edit ? 'Save' : 'Insert' ?></button>
                    <?php if ($is_edit): ?><a href="<?= esc(base_url(['db' => $selected_db, 'table' => $selected_table, 'tab' => 'browse'])) ?>" class="btn btn-secondary">Cancel</a><?php endif; ?>
                  </div>
                </form>
              </div>
            </div>
            <?php
        }
        break;

    case 'export':
        $is_selected_export = !empty($_GET['selected']) && !empty($_SESSION['export_query']) && (($_SESSION['export_table'] ?? '') === $selected_table);
        $selected_count = $is_selected_export ? intval($_SESSION['export_selected_count'] ?? 0) : 0;
        ?>
        <div class="panel">
          <div class="panel-header">Export — <?= esc($selected_table) ?></div>
          <div class="panel-body">
            <?php if ($is_selected_export): ?>
              <div style="background: rgba(59, 130, 246, 0.12); border-left: 3px solid #3b82f6; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                  <span style="font-size: 13px; font-weight: 500; color: var(--text);">Exporting <strong><?= $selected_count ?> selected row<?= $selected_count === 1 ? '' : 's' ?></strong></span>
                </div>
                <a href="<?= esc(base_url(['db' => $selected_db, 'table' => $selected_table, 'tab' => 'export'])) ?>" class="btn btn-secondary btn-sm" style="font-size: 12px;">Switch to Full Table Export</a>
              </div>
            <?php endif; ?>

            <p style="margin-bottom:12px;color:var(--text-secondary)">Choose export format <?= $is_selected_export ? "(for $selected_count selected rows)" : '(entire table)' ?>:</p>
            <div class="form-actions">
              <a href="export.php?db=<?= urlencode($selected_db) ?>&table=<?= urlencode($selected_table) ?>&format=sql<?= $is_selected_export ? '&selected=1' : '' ?>" class="btn btn-primary">SQL</a>
              <a href="export.php?db=<?= urlencode($selected_db) ?>&table=<?= urlencode($selected_table) ?>&format=csv<?= $is_selected_export ? '&selected=1' : '' ?>" class="btn btn-secondary">CSV</a>
              <a href="export.php?db=<?= urlencode($selected_db) ?>&table=<?= urlencode($selected_table) ?>&format=json<?= $is_selected_export ? '&selected=1' : '' ?>" class="btn btn-secondary">JSON</a>
              <a href="export.php?db=<?= urlencode($selected_db) ?>&table=<?= urlencode($selected_table) ?>&format=xml<?= $is_selected_export ? '&selected=1' : '' ?>" class="btn btn-secondary">XML</a>
            </div>
          </div>
        </div>
        <?php
        break;

    case 'import':
        ?>
        <div class="panel">
          <div class="panel-header">Import — <?= esc($selected_table) ?></div>
          <div class="panel-body">
            <form method="POST" action="actions.php" enctype="multipart/form-data">
              <input type="hidden" name="action" value="import_sql">
              <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
              <input type="hidden" name="tab" value="import">
              <div class="form-group"><label class="form-label">File</label><input type="file" name="import_file" class="form-input" accept=".sql,.csv"></div>
              <div class="form-group"><label class="form-label">SQL</label><textarea name="import_text" class="form-textarea" rows="6"></textarea></div>
              <button type="submit" class="btn btn-primary">Import</button>
            </form>
          </div>
        </div>
        <?php
        break;

    case 'operations':
        if (!empty($is_view)):
            $cv_res = $conn->query("SHOW CREATE VIEW `$selected_table`");
            $create_view_sql = ($cv_res && $cv_row = $cv_res->fetch_row()) ? ($cv_row[1] ?? '') : '';
            ?>
            <div class="panel">
              <div class="panel-header">View Operations — <?= esc($selected_table) ?> <span style="font-size:10px;font-weight:600;background:rgba(59,130,246,0.15);color:#60a5fa;padding:2px 6px;border-radius:4px;margin-left:6px;letter-spacing:0.5px;">VIEW</span></div>
              <div class="panel-body">
                <?php if ($create_view_sql): ?>
                <div class="form-group" style="margin-bottom:16px;">
                  <label class="form-label">View Definition (SQL Query)</label>
                  <pre style="background:var(--bg-tertiary);padding:12px;border:1px solid var(--border-color);border-radius:6px;font-size:12px;white-space:pre-wrap;word-break:break-all;color:var(--text);margin:0;font-family:monospace;"><?= esc($create_view_sql) ?></pre>
                </div>
                <?php endif; ?>

                <form method="POST" action="actions.php" style="margin-bottom:16px">
                  <input type="hidden" name="action" value="rename_table">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
                  <div class="form-group">
                    <label class="form-label">Rename View</label>
                    <div style="display:flex;gap:8px">
                      <input type="text" name="new_name" class="form-input" placeholder="New view name" required>
                      <button type="submit" class="btn btn-primary">Rename View</button>
                    </div>
                  </div>
                </form>

                <hr style="border:none;border-top:1px solid var(--border);margin:16px 0">
                <form method="POST" action="actions.php" class="confirm-delete">
                  <input type="hidden" name="action" value="drop_table">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
                  <button type="submit" class="btn btn-danger">Drop View</button>
                </form>
              </div>
            </div>
            <?php
        else:
            $status = get_table_status($conn, $selected_db, $selected_table);
            ?>
            <div class="panel">
              <div class="panel-header">Table Operations — <?= esc($selected_table) ?></div>
              <div class="panel-body">
                <form method="POST" action="actions.php" style="margin-bottom:16px">
                  <input type="hidden" name="action" value="rename_table">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
                  <div class="form-group"><label class="form-label">Rename Table</label><div style="display:flex;gap:8px"><input type="text" name="new_name" class="form-input" placeholder="New table name" required><button type="submit" class="btn btn-primary">Rename</button></div></div>
                </form>
                <form method="POST" action="actions.php" style="margin-bottom:16px">
                  <input type="hidden" name="action" value="copy_table">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
                  <div class="form-row">
                    <div class="form-group"><label class="form-label">Copy To</label><input type="text" name="new_name" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Target Database</label><select name="target_db" class="form-select"><?php foreach ($databases as $dbitem): ?><option value="<?= esc($dbitem) ?>" <?= $dbitem === $selected_db ? 'selected' : '' ?>><?= esc($dbitem) ?></option><?php endforeach; ?></select></div>
                  </div>
                  <label class="checkbox-label"><input type="checkbox" name="copy_data" checked> Copy data</label>
                  <div class="form-actions"><button type="submit" class="btn btn-secondary">Copy Table</button></div>
                </form>
                <form method="POST" action="actions.php" style="margin-bottom:16px">
                  <input type="hidden" name="action" value="change_engine">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
                  <div class="form-group"><label class="form-label">Storage Engine (current: <?= esc($status['Engine'] ?? '') ?>)</label>
                    <div style="display:flex;gap:8px"><select name="engine" class="form-select"><option value="InnoDB">InnoDB</option><option value="MyISAM">MyISAM</option><option value="MEMORY">MEMORY</option></select><button type="submit" class="btn btn-secondary">Change</button></div>
                  </div>
                </form>
                <div class="form-actions">
                  <form method="POST" action="actions.php" class="inline-form"><input type="hidden" name="action" value="check_table"><input type="hidden" name="db" value="<?= esc($selected_db) ?>"><input type="hidden" name="table" value="<?= esc($selected_table) ?>"><button type="submit" class="btn btn-secondary">Check</button></form>
                  <form method="POST" action="actions.php" class="inline-form"><input type="hidden" name="action" value="optimize_table"><input type="hidden" name="db" value="<?= esc($selected_db) ?>"><input type="hidden" name="table" value="<?= esc($selected_table) ?>"><button type="submit" class="btn btn-secondary">Optimize</button></form>
                  <form method="POST" action="actions.php" class="inline-form"><input type="hidden" name="action" value="analyze_table"><input type="hidden" name="db" value="<?= esc($selected_db) ?>"><input type="hidden" name="table" value="<?= esc($selected_table) ?>"><button type="submit" class="btn btn-secondary">Analyze</button></form>
                  <form method="POST" action="actions.php" class="inline-form"><input type="hidden" name="action" value="repair_table"><input type="hidden" name="db" value="<?= esc($selected_db) ?>"><input type="hidden" name="table" value="<?= esc($selected_table) ?>"><button type="submit" class="btn btn-secondary">Repair</button></form>
                </div>
                <hr style="border:none;border-top:1px solid var(--border);margin:16px 0">
                <form method="POST" action="actions.php" class="confirm-delete">
                  <input type="hidden" name="action" value="drop_table">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
                  <button type="submit" class="btn btn-danger">Drop Table</button>
                </form>
              </div>
            </div>
            <?php
        endif;
        break;

    case 'triggers':
        if (!empty($is_view)):
            ?>
            <div class="panel">
              <div class="panel-header">Triggers — <?= esc($selected_table) ?> <span style="font-size:10px;font-weight:600;background:rgba(59,130,246,0.15);color:#60a5fa;padding:2px 6px;border-radius:4px;margin-left:6px;letter-spacing:0.5px;">VIEW</span></div>
              <div class="panel-body">
                <p style="color:var(--text-muted);margin:0;">MySQL does not support triggers directly on views. Triggers should be created on the underlying base tables.</p>
              </div>
            </div>
            <?php
        else:
            $triggers = get_triggers($conn, $selected_db, $selected_table);
            ?>
            <div class="panel">
              <div class="panel-header">Triggers — <?= esc($selected_table) ?></div>
              <div class="panel-body" style="padding:0">
                <?php if (empty($triggers)): ?>
                  <p style="padding:16px;color:var(--text-muted)">No triggers on this table.</p>
                <?php else: ?>
                  <div class="table-wrap" style="border:none"><table class="data-table">
                    <thead><tr><th>Trigger</th><th>Event</th><th>Timing</th><th>Statement</th></tr></thead>
                    <tbody><?php foreach ($triggers as $t): ?>
                      <tr><td><?= esc($t['Trigger']) ?></td><td><?= esc($t['Event']) ?></td><td><?= esc($t['Timing']) ?></td><td style="font-size:11px"><?= esc($t['Statement'] ?? '') ?></td></tr>
                    <?php endforeach; ?></tbody>
                  </table></div>
                <?php endif; ?>
              </div>
            </div>
            <?php
        endif;
        break;

    case 'indexes':
        if (!empty($is_view)):
            ?>
            <div class="panel">
              <div class="panel-header">Indexes — <?= esc($selected_table) ?> <span style="font-size:10px;font-weight:600;background:rgba(59,130,246,0.15);color:#60a5fa;padding:2px 6px;border-radius:4px;margin-left:6px;letter-spacing:0.5px;">VIEW</span></div>
              <div class="panel-body">
                <p style="color:var(--text-muted);margin:0;">Views do not have independent physical indexes in MySQL. Indexes are managed on and utilized through the underlying base tables.</p>
              </div>
            </div>
            <?php
        else:
            $indexes = get_table_indexes($conn, $selected_db, $selected_table);
            $grouped = [];
            foreach ($indexes as $idx) {
                $grouped[$idx['Key_name']][] = $idx;
            }
            ?>
            <div class="panel">
              <div class="panel-header">Indexes — <?= esc($selected_table) ?></div>
              <div class="panel-body" style="padding:0">
                <div class="table-wrap" style="border:none"><table class="data-table">
                  <thead><tr><th>Key Name</th><th>Type</th><th>Unique</th><th>Column</th><th>Cardinality</th></tr></thead>
                  <tbody>
                    <?php foreach ($indexes as $idx): ?>
                    <tr>
                      <td><?= esc($idx['Key_name']) ?></td>
                      <td><?= $idx['Key_name'] === 'PRIMARY' ? 'PRIMARY' : ($idx['Index_type'] ?? 'INDEX') ?></td>
                      <td><?= $idx['Non_unique'] == 0 ? 'Yes' : 'No' ?></td>
                      <td><?= esc($idx['Column_name']) ?></td>
                      <td><?= esc($idx['Cardinality'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table></div>
              </div>
            </div>
            <div class="panel">
              <div class="panel-header">Add Index</div>
              <div class="panel-body">
                <form method="POST" action="actions.php">
                  <input type="hidden" name="action" value="add_index">
                  <input type="hidden" name="db" value="<?= esc($selected_db) ?>">
                  <input type="hidden" name="table" value="<?= esc($selected_table) ?>">
                  <div class="form-row">
                    <div class="form-group"><label class="form-label">Index Name</label><input type="text" name="idx_name" class="form-input"></div>
                    <div class="form-group"><label class="form-label">Column</label><select name="idx_col" class="form-select" required><?php foreach ($columns as $c): ?><option value="<?= esc($c['Field']) ?>"><?= esc($c['Field']) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label class="form-label">Type</label><select name="idx_type" class="form-select"><option value="INDEX">Index</option><option value="UNIQUE">Unique</option><option value="FULLTEXT">Fulltext</option><option value="PRIMARY">Primary</option></select></div>
                  </div>
                  <button type="submit" class="btn btn-primary">Create Index</button>
                </form>
              </div>
            </div>
            <?php
        endif;
        break;
}
