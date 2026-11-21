      </div>
    </main>
  </div>
  <?php require __DIR__ . '/sql_guide.php'; ?>

  <!-- Snippets Modal -->
  <div class="guide-overlay" id="snippetsModal" hidden>
    <div class="guide-modal" style="max-width:850px;">
      <div class="guide-modal-header">
        <h2 style="font-size:15px; font-weight:600; margin:0; display:inline-flex; align-items:center; gap:8px;">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
          Saved SQL Snippets
        </h2>
        <button class="guide-modal-close" id="closeSnippetsBtn">&times;</button>
      </div>
      <div class="guide-modal-body">
        <div style="margin-bottom:16px; display:flex; gap:12px;">
          <input type="text" id="snippet-name" class="form-input" placeholder="Snippet Name (e.g. Monthly Report)" style="flex:1;">
          <button type="button" class="btn btn-primary" id="saveSnippetBtn">Save Current SQL Editor Content</button>
        </div>
        <div class="table-wrap">
          <table class="data-table" id="snippets-table">
            <thead><tr><th style="width:200px; padding-left:16px;">Name</th><th>SQL Preview</th><th style="width:140px; text-align:right; padding-right:16px;">Actions</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- History Modal -->
  <div class="guide-overlay" id="historyModal" hidden>
    <div class="guide-modal" style="max-width:920px; width:92%; max-height:88vh; display:flex; flex-direction:column;">
      <div class="guide-modal-header" style="flex-shrink:0;">
        <h2 style="font-size:15px; font-weight:600; margin:0; display:inline-flex; align-items:center; gap:8px;">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
          Query History & Audit Log
          <span id="historyModalBadge" class="badge" style="background:var(--bg-tertiary); color:var(--text-muted); border:1px solid var(--border-color); font-size:11px; font-weight:500; padding:2px 7px; border-radius:10px;">0</span>
        </h2>
        <div style="display:flex; align-items:center; gap:8px;">
          <button type="button" class="btn btn-secondary btn-sm" id="historyModalRefreshBtn" title="Refresh history" style="padding:4px 8px; font-size:11px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          </button>
          <button type="button" class="btn btn-secondary btn-sm" id="historyModalExportBtn" title="Export as JSON" style="padding:4px 8px; font-size:11px;">
            Export JSON
          </button>
          <button type="button" class="btn btn-danger btn-sm" id="historyModalClearBtn" title="Clear history" style="padding:4px 8px; font-size:11px;">
            Clear
          </button>
          <button class="guide-modal-close" id="closeHistoryModalBtn">&times;</button>
        </div>
      </div>
      <div style="padding:10px 16px; background:var(--bg-tertiary); border-bottom:1px solid var(--border-color); display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; flex-shrink:0;">
        <div class="audit-pills" id="modalAuditPills">
          <button type="button" class="audit-pill active" data-filter="all">All</button>
          <button type="button" class="audit-pill" data-filter="update">Updates</button>
          <button type="button" class="audit-pill" data-filter="insert">Inserts</button>
          <button type="button" class="audit-pill" data-filter="delete">Deletes</button>
          <button type="button" class="audit-pill" data-filter="schema">Schema</button>
          <button type="button" class="audit-pill" data-filter="sql">SQL</button>
        </div>
        <div style="flex:1; max-width:280px; min-width:180px;">
          <input type="text" id="modalAuditSearch" class="form-input" placeholder="Search history..." style="width:100%; font-size:12px; padding:5px 8px;">
        </div>
      </div>
      <div class="guide-modal-body" id="historyModalBody" style="overflow-y:auto; padding:16px; flex:1;">
        <div id="historyModalList" style="display:flex; flex-direction:column; gap:12px;"></div>
      </div>
    </div>
  </div>

  <footer class="app-footer">Powered by <strong>TeamXDD</strong> · created by <strong>Nabeel XDD</strong> | XDDB @ v<?= XDDB_VERSION ?></footer>
</div>
<script src="assets/js/codemirror/codemirror.min.js"></script>
<script src="assets/js/codemirror/sql.min.js"></script>
<script src="assets/js/codemirror/show-hint.min.js"></script>
<script src="assets/js/codemirror/sql-hint.min.js"></script>
<script src="assets/js/chart.min.js?v=<?= XDDB_VERSION ?>"></script>
<script src="assets/js/app.js?v=<?= XDDB_VERSION ?>"></script>
<script src="assets/js/sql-editor.js?v=<?= XDDB_VERSION ?>"></script>
</body>
</html>
