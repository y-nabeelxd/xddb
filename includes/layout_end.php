      </div>
    </main>
  </div>
  <?php require __DIR__ . '/sql_guide.php'; ?>

  <!-- Snippets Modal -->
  <div class="guide-overlay" id="snippetsModal" hidden>
    <div class="guide-modal" style="max-width:850px;">
      <div class="guide-modal-header">
        <h2 style="font-size:16px; font-weight:600; margin:0;">💾 Saved SQL Snippets</h2>
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

  <footer class="app-footer">Powered by <strong>TeamXD</strong> · created by <strong>Nabeel XD</strong></footer>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/sql/sql.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/show-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/sql-hint.min.js"></script>
<script src="assets/js/app.js?v=<?= XDDB_VERSION ?>"></script>
<script src="assets/js/sql-editor.js?v=<?= XDDB_VERSION ?>"></script>
</body>
</html>
