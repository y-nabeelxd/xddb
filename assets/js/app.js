(function() {
  const saved = localStorage.getItem('pma-theme') || 'dark';
  document.documentElement.setAttribute('data-theme', saved === 'light' ? 'light' : 'dark');

  const sunIcon = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
  const moonIcon = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';

  function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('pma-theme', next);
    updateToggleIcons(next);
  }

  function updateToggleIcons(theme) {
    document.querySelectorAll('.theme-toggle-btn').forEach(function(btn) {
      btn.innerHTML = theme === 'dark' ? sunIcon : moonIcon;
      btn.title = theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme';
    });
  }

  document.querySelectorAll('.theme-toggle-btn').forEach(function(btn) {
    btn.addEventListener('click', toggleTheme);
  });

  updateToggleIcons(saved === 'light' ? 'light' : 'dark');

  // Auto-inject CSRF token into all forms
  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
  if (csrfToken) {
    document.querySelectorAll('form').forEach(function(form) {
      const method = (form.getAttribute('method') || 'GET').toUpperCase();
      if (method === 'POST' && !form.querySelector('input[name="csrf_token"]')) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = csrfToken;
        form.appendChild(input);
      }
    });

    document.addEventListener('submit', function(e) {
      const form = e.target;
      if (form && form.tagName === 'FORM') {
        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        if (method === 'POST' && !form.querySelector('input[name="csrf_token"]')) {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'csrf_token';
          input.value = csrfToken;
          form.appendChild(input);
        }
      }
    }, true);
  }

  const sidebarSearch = document.getElementById('sidebarSearch');
  if (sidebarSearch) {
    sidebarSearch.addEventListener('input', function() {
      const q = this.value.toLowerCase();
      document.querySelectorAll('.sidebar-db-group').forEach(function(group) {
        const dbName = (group.getAttribute('data-db') || '').toLowerCase();
        const dbMatch = dbName.includes(q);
        let tableMatch = false;
        group.querySelectorAll('.sidebar-item.nested[data-name]').forEach(function(item) {
          const name = (item.getAttribute('data-name') || '').toLowerCase();
          const match = name.includes(q);
          item.style.display = match || dbMatch || q === '' ? '' : 'none';
          if (match) tableMatch = true;
        });
        group.style.display = dbMatch || tableMatch || q === '' ? '' : 'none';
        if ((dbMatch || tableMatch) && q !== '') {
          expandDbGroup(group);
        }
      });
    });
  }

  function expandDbGroup(group) {
    if (!group) return;
    group.classList.add('expanded');
    const list = group.querySelector('.sidebar-table-list');
    const toggle = group.querySelector('.sidebar-toggle');
    if (list) list.classList.remove('collapsed');
    if (toggle) {
      toggle.setAttribute('aria-expanded', 'true');
      const icon = toggle.querySelector('.toggle-icon');
      if (icon) icon.textContent = '▼';
    }
  }

  function collapseDbGroup(group) {
    if (!group) return;
    group.classList.remove('expanded');
    const list = group.querySelector('.sidebar-table-list');
    const toggle = group.querySelector('.sidebar-toggle');
    if (list) list.classList.add('collapsed');
    if (toggle) {
      toggle.setAttribute('aria-expanded', 'false');
      const icon = toggle.querySelector('.toggle-icon');
      if (icon) icon.textContent = '▶';
    }
  }

  document.querySelectorAll('.sidebar-toggle').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const group = btn.closest('.sidebar-db-group');
      if (!group) return;
      if (group.classList.contains('expanded')) {
        collapseDbGroup(group);
      } else {
        expandDbGroup(group);
      }
    });
  });

  document.querySelectorAll('.sidebar-scroll-up').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const list = btn.closest('.sidebar-db-group').querySelector('.sidebar-table-list');
      if (list) list.scrollBy({ top: -80, behavior: 'smooth' });
    });
  });

  document.querySelectorAll('.sidebar-scroll-down').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const list = btn.closest('.sidebar-db-group').querySelector('.sidebar-table-list');
      if (list) list.scrollBy({ top: 80, behavior: 'smooth' });
    });
  });

  document.querySelectorAll('.sidebar-remove-recent').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
    });
  });

  const openGuideBtn = document.getElementById('openGuideBtn');
  const closeGuideBtn = document.getElementById('closeGuideBtn');
  const guideOverlay = document.getElementById('guideOverlay');

  function openGuide() {
    if (!guideOverlay) return;
    guideOverlay.hidden = false;
    document.body.classList.add('guide-open');
  }

  function closeGuide() {
    if (!guideOverlay) return;
    guideOverlay.hidden = true;
    document.body.classList.remove('guide-open');
  }

  if (openGuideBtn) openGuideBtn.addEventListener('click', openGuide);
  if (closeGuideBtn) closeGuideBtn.addEventListener('click', closeGuide);
  if (guideOverlay) {
    guideOverlay.addEventListener('click', function(e) {
      if (e.target === guideOverlay) closeGuide();
    });
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && guideOverlay && !guideOverlay.hidden) {
      closeGuide();
    }
  });

  document.querySelectorAll('.toggle-password').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const input = document.getElementById('password');
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.textContent = show ? 'Hide' : 'Show';
    });
  });

  document.querySelectorAll('.confirm-delete').forEach(function(form) {
    form.addEventListener('submit', function(e) {
      if (!confirm('Are you sure you want to delete this? This action cannot be undone.')) {
        e.preventDefault();
      }
    });
  });

  const dbSelect = document.getElementById('dbSelectDisplay');
  const dbDropdown = document.getElementById('dbDropdown');
  const serverSelect = document.getElementById('serverSelectDisplay');
  const serverDropdown = document.getElementById('serverDropdown');
  const customServerGroup = document.getElementById('customServerGroup');
  const customServerInput = document.getElementById('customServerInput');

  function setupDropdown(display, dropdown, onSelect) {
    if (!display || !dropdown) return;
    display.addEventListener('click', function(e) {
      e.stopPropagation();
      document.querySelectorAll('.select-dropdown.active').forEach(function(d) {
        if (d !== dropdown) d.classList.remove('active');
      });
      dropdown.classList.toggle('active');
    });
    dropdown.querySelectorAll('.select-option').forEach(function(opt) {
      opt.addEventListener('click', function(e) {
        e.stopPropagation();
        onSelect(opt);
        dropdown.classList.remove('active');
        dropdown.querySelectorAll('.select-option').forEach(function(o) {
          o.classList.remove('selected');
        });
        opt.classList.add('selected');
      });
    });
  }

  if (dbSelect && dbDropdown) {
    setupDropdown(dbSelect, dbDropdown, function(opt) {
      document.getElementById('dbSelectedText').textContent = opt.dataset.value;
      document.getElementById('dbType').value = opt.dataset.value;
      document.getElementById('dbPort').value = opt.dataset.port;
    });
  }

  if (serverSelect && serverDropdown) {
    setupDropdown(serverSelect, serverDropdown, function(opt) {
      document.getElementById('serverSelectedText').textContent = opt.textContent.trim();
      document.getElementById('serverAddress').value = opt.dataset.value;
      if (opt.dataset.value === 'custom') {
        customServerGroup.classList.add('visible');
      } else {
        customServerGroup.classList.remove('visible');
        if (customServerInput) customServerInput.value = '';
      }
    });
  }

  document.addEventListener('click', function() {
    document.querySelectorAll('.select-dropdown.active').forEach(function(d) {
      d.classList.remove('active');
    });
  });

  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
      let server = document.getElementById('serverAddress').value;
      const username = document.getElementById('username').value.trim();
      const errorBox = document.getElementById('errorBox');
      if (server === 'custom') {
        server = customServerInput ? customServerInput.value.trim() : '';
        if (!server) {
          e.preventDefault();
          errorBox.textContent = 'Please enter a custom server address.';
          errorBox.style.display = 'block';
          return;
        }
        document.getElementById('serverAddress').value = server;
      }
      if (!username) {
        e.preventDefault();
        errorBox.textContent = 'Username is required.';
        errorBox.style.display = 'block';
        return;
      }
      errorBox.style.display = 'none';
    });
  }

  // --- Color Picker Logic ---
  const colorPicker = document.getElementById('theme-color-picker');
  if (colorPicker) {
    colorPicker.addEventListener('change', function(e) {
      const color = e.target.value;
      document.documentElement.style.setProperty('--accent', color);
      document.cookie = `xddb_accent_color=${color}; path=/; max-age=31536000`;
    });
  }

  // --- History Modal Logic ---
  const historyModalBtn = document.getElementById('openHistoryModalBtn');
  const historyModal = document.getElementById('historyModal');
  const closeHistoryModalBtn = document.getElementById('closeHistoryModalBtn');
  const historyModalList = document.getElementById('historyModalList');
  const historyModalBadge = document.getElementById('historyModalBadge');
  const globalHistoryDot = document.getElementById('globalHistoryDot');
  const tabHistoryDot = document.getElementById('tabHistoryDot');
  const historyModalRefreshBtn = document.getElementById('historyModalRefreshBtn');
  const historyModalExportBtn = document.getElementById('historyModalExportBtn');
  const historyModalClearBtn = document.getElementById('historyModalClearBtn');
  const modalAuditSearch = document.getElementById('modalAuditSearch');
  const modalAuditPills = document.querySelectorAll('#modalAuditPills .audit-pill');

  function clearHistoryDots() {
    if (globalHistoryDot) globalHistoryDot.style.display = 'none';
    if (tabHistoryDot) tabHistoryDot.style.display = 'none';
  }

  // Clear dots if user is currently visiting the history tab
  const currentTabParam = new URLSearchParams(window.location.search).get('tab');
  if (currentTabParam === 'history') {
    clearHistoryDots();
  }

  let cachedHistory = [];
  let modalFilter = 'all';

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function renderHistoryList() {
    if (!historyModalList) return;
    const q = (modalAuditSearch ? modalAuditSearch.value.toLowerCase().trim() : '');

    const filtered = cachedHistory.filter(h => {
      const rawType = (h.type || 'SQL').toUpperCase();
      let category = 'sql';
      if (['UPDATE', 'BULK_UPDATE'].includes(rawType)) category = 'update';
      else if (['INSERT', 'COPY'].includes(rawType)) category = 'insert';
      else if (['DELETE', 'BULK_DELETE', 'TRUNCATE'].includes(rawType)) category = 'delete';
      else if (['CREATE_TABLE', 'DROP_TABLE', 'ALTER_TABLE', 'CREATE_DB', 'DROP_DB', 'CREATE_VIEW', 'DROP_VIEW', 'CREATE', 'DROP', 'ALTER'].includes(rawType)) category = 'schema';

      if (modalFilter !== 'all' && category !== modalFilter) return false;

      if (q) {
        let hay = [rawType, h.summary || '', h.db || '', h.table || '', h.sql || ''].join(' ');
        if (h.diff && Array.isArray(h.diff)) {
          h.diff.forEach(d => {
            hay += ' ' + (d.field || '') + ' ' + (d.old || '') + ' ' + (d.new || '');
          });
        }
        if (!hay.toLowerCase().includes(q)) return false;
      }
      return true;
    });

    if (historyModalBadge) {
      historyModalBadge.textContent = cachedHistory.length + (cachedHistory.length === 1 ? ' Record' : ' Records');
    }

    if (filtered.length === 0) {
      historyModalList.innerHTML = `
        <div style="text-align:center; padding:32px; color:var(--text-muted);">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 8px; display:block; opacity:0.5;"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          No matching history records found.
        </div>`;
      return;
    }

    historyModalList.innerHTML = filtered.map((h, i) => {
      const rawType = (h.type || 'SQL').toUpperCase();
      let badgeClass = 'badge-sql';
      if (['UPDATE', 'BULK_UPDATE'].includes(rawType)) badgeClass = 'badge-update';
      else if (['INSERT', 'COPY'].includes(rawType)) badgeClass = 'badge-insert';
      else if (['DELETE', 'BULK_DELETE', 'TRUNCATE'].includes(rawType)) badgeClass = 'badge-delete';
      else if (['CREATE_TABLE', 'DROP_TABLE', 'ALTER_TABLE', 'CREATE_DB', 'DROP_DB', 'CREATE_VIEW', 'DROP_VIEW', 'CREATE', 'DROP', 'ALTER'].includes(rawType)) badgeClass = 'badge-schema';

      const diff = h.diff || [];
      const hasDiff = Array.isArray(diff) && diff.length > 0;
      const beforeState = h.before || null;
      const afterState = h.after || null;
      const hasSnap = (beforeState || afterState);
      const itemId = 'm_snap_' + (h.id || i);

      let diffHtml = '';
      if (hasDiff) {
        diffHtml = `
          <div class="diff-container" style="margin-top:8px;">
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
                ${diff.map(d => `
                  <tr>
                    <td class="diff-col">${escapeHtml(d.field)}</td>
                    <td class="diff-old">${d.old === null ? '<span class="diff-null">NULL</span>' : `<del class="diff-val">${escapeHtml(d.old)}</del>`}</td>
                    <td style="text-align:center; color:var(--text-muted); font-weight:bold;">→</td>
                    <td class="diff-new">${d.new === null ? '<span class="diff-null">NULL</span>' : `<ins class="diff-val">${escapeHtml(d.new)}</ins>`}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>`;
      } else if (rawType === 'DELETE' && beforeState) {
        diffHtml = `
          <div style="background:rgba(239, 68, 68, 0.08); border:1px solid rgba(239, 68, 68, 0.2); border-radius:6px; padding:8px 12px; font-size:12px; color:#f87171; margin-top:8px;">
            <strong>Deleted Record State:</strong>
            <pre style="margin:4px 0 0; font-family:monospace; font-size:11px; white-space:pre-wrap; word-break:break-all;">${escapeHtml(JSON.stringify(beforeState, null, 2))}</pre>
          </div>`;
      } else if (['INSERT', 'COPY'].includes(rawType) && afterState) {
        diffHtml = `
          <div style="background:rgba(16, 185, 129, 0.08); border:1px solid rgba(16, 185, 129, 0.2); border-radius:6px; padding:8px 12px; font-size:12px; color:#34d399; margin-top:8px;">
            <strong>Inserted Record State:</strong>
            <pre style="margin:4px 0 0; font-family:monospace; font-size:11px; white-space:pre-wrap; word-break:break-all;">${escapeHtml(JSON.stringify(afterState, null, 2))}</pre>
          </div>`;
      }

      let snapHtml = '';
      if (hasSnap && hasDiff) {
        snapHtml = `
          <div style="margin-top:6px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.toggleModalSnap('${itemId}')" style="font-size:11px; padding:2px 7px;">
              Toggle Full Record Snapshots (Before & After)
            </button>
            <div id="${itemId}" class="snapshot-drawer" style="display:none; margin-top:6px;">
              <div class="snapshot-grid">
                <div class="snapshot-panel" style="border-top:3px solid #ef4444;">
                  <div class="snapshot-panel-title" style="color:#ef4444;">Before Mutation</div>
                  <pre class="snapshot-json">${escapeHtml(JSON.stringify(beforeState, null, 2))}</pre>
                </div>
                <div class="snapshot-panel" style="border-top:3px solid #10b981;">
                  <div class="snapshot-panel-title" style="color:#10b981;">After Mutation</div>
                  <pre class="snapshot-json">${escapeHtml(JSON.stringify(afterState, null, 2))}</pre>
                </div>
              </div>
            </div>
          </div>`;
      }

      return `
        <div class="audit-card">
          <div class="audit-card-header" style="padding:8px 12px;">
            <div class="audit-card-meta">
              <span class="audit-badge ${badgeClass}">${escapeHtml(rawType)}</span>
              ${(h.db || h.table) ? `
                <span class="audit-target">
                  ${escapeHtml(h.db || '')}${(h.db && h.table) ? '.' : ''}${escapeHtml(h.table || '')}
                </span>` : ''}
              ${h.duration !== null && h.duration !== undefined ? `<span style="font-size:11px; color:var(--text-muted);">⚡ ${Number(h.duration).toFixed(2)} ms</span>` : ''}
              ${h.affected_rows !== null && h.affected_rows !== undefined ? `<span style="font-size:11px; color:var(--text-muted);">${h.affected_rows} row(s)</span>` : ''}
            </div>
            <span style="font-size:11px; color:var(--text-muted);">${escapeHtml(h.time || '')}</span>
          </div>
          <div class="audit-card-body" style="padding:12px;">
            ${h.summary ? `<div class="audit-summary" style="font-size:13px;">${escapeHtml(h.summary)}</div>` : ''}
            ${diffHtml}
            ${snapHtml}
            <div class="sql-box" style="margin-top:8px; padding:6px 10px;">
              <pre class="sql-code" style="font-size:11px;"><code>${escapeHtml(h.sql || '')}</code></pre>
              <div style="display:flex; gap:4px; flex-shrink:0;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="window.copyModalSql(this, '${escapeHtml(encodeURIComponent(h.sql || ''))}')" style="padding:2px 6px; font-size:11px;">Copy</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="window.replayModalSql('${escapeHtml(encodeURIComponent(h.sql || ''))}', '${escapeHtml(h.db || '')}')" style="padding:2px 6px; font-size:11px;">Replay</button>
              </div>
            </div>
          </div>
        </div>
      `;
    }).join('');
  }

  window.toggleModalSnap = function(id) {
    const el = document.getElementById(id);
    if (el) {
      el.style.display = (el.style.display === 'block') ? 'none' : 'block';
    }
  };

  window.copyModalSql = function(btn, encodedSql) {
    const sql = decodeURIComponent(encodedSql);
    if (navigator.clipboard) {
      navigator.clipboard.writeText(sql).then(() => {
        const orig = btn.innerText;
        btn.innerText = 'Copied!';
        btn.style.color = '#34d399';
        setTimeout(() => { btn.innerText = orig; btn.style.color = ''; }, 1500);
      });
    }
  };

  window.replayModalSql = function(encodedSql, db) {
    const sql = decodeURIComponent(encodedSql);
    sessionStorage.setItem('replay_sql', sql);
    let target = 'dashboard.php?tab=sql';
    if (db) target += '&db=' + encodeURIComponent(db);
    window.location.href = target;
  };

  function fetchHistory() {
    fetch('api.php?action=get_history')
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          cachedHistory = res.history || [];
          renderHistoryList();
        }
      })
      .catch(err => console.error('Failed to load history:', err));
  }

  if (historyModalBtn && historyModal) {
    historyModalBtn.addEventListener('click', function() {
      clearHistoryDots();
      historyModal.removeAttribute('hidden');
      document.body.classList.add('guide-open');
      fetchHistory();
    });

    if (closeHistoryModalBtn) {
      closeHistoryModalBtn.addEventListener('click', function() {
        historyModal.setAttribute('hidden', '');
        document.body.classList.remove('guide-open');
      });
    }

    historyModal.addEventListener('click', function(e) {
      if (e.target === historyModal) {
        historyModal.setAttribute('hidden', '');
        document.body.classList.remove('guide-open');
      }
    });

    if (historyModalRefreshBtn) {
      historyModalRefreshBtn.addEventListener('click', fetchHistory);
    }

    if (historyModalExportBtn) {
      historyModalExportBtn.addEventListener('click', function() {
        const blob = new Blob([JSON.stringify(cachedHistory, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'xddb-audit-log-' + (new Date().toISOString().slice(0, 10)) + '.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      });
    }

    if (historyModalClearBtn) {
      historyModalClearBtn.addEventListener('click', function() {
        if (!confirm('Are you sure you want to clear all history?')) return;
        fetch('api.php?action=clear_history', { method: 'POST' })
          .then(r => r.json())
          .then(res => {
            if (res.success) {
              cachedHistory = [];
              renderHistoryList();
            }
          });
      });
    }

    modalAuditPills.forEach(pill => {
      pill.addEventListener('click', function() {
        modalAuditPills.forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        modalFilter = pill.getAttribute('data-filter') || 'all';
        renderHistoryList();
      });
    });

    if (modalAuditSearch) {
      modalAuditSearch.addEventListener('input', renderHistoryList);
    }
  }

  // --- Snippets Logic ---
  const snippetsBtn = document.getElementById('openSnippetsBtn');
  const snippetsModal = document.getElementById('snippetsModal');
  const closeSnippetsBtn = document.getElementById('closeSnippetsBtn');
  const saveSnippetBtn = document.getElementById('saveSnippetBtn');
  const snippetsTable = document.querySelector('#snippets-table tbody');

  function loadSnippets() {
    if (!snippetsTable) return;
    const snippets = JSON.parse(localStorage.getItem('xddb_snippets') || '[]');
    if (snippets.length === 0) {
      snippetsTable.innerHTML = '<tr><td colspan="3" style="text-align:center">No snippets saved</td></tr>';
      return;
    }
    snippetsTable.innerHTML = snippets.map((s, i) => `
      <tr>
        <td style="padding-left:16px;"><strong>${s.name.replace(/</g, '&lt;')}</strong></td>
        <td><code style="background:var(--bg-tertiary);padding:4px 8px;border-radius:4px;display:block;max-width:450px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;border:1px solid var(--border);">${s.sql.replace(/</g, '&lt;')}</code></td>
        <td style="text-align:right; padding-right:16px;">
          <button class="btn btn-secondary btn-sm" onclick="window.useSnippet(${i})" title="Use Snippet">Use</button>
          <button class="btn btn-danger btn-sm" onclick="window.deleteSnippet(${i})" title="Delete Snippet">&times;</button>
        </td>
      </tr>
    `).join('');
  }

  window.useSnippet = function(index) {
    const snippets = JSON.parse(localStorage.getItem('xddb_snippets') || '[]');
    const s = snippets[index];
    if (s && window.sqlEditor) {
      window.sqlEditor.setValue(s.sql);
    } else if (s) {
      const ta = document.querySelector('textarea[name="sql_query"]');
      if (ta) ta.value = s.sql;
    }
    if (snippetsModal) {
      snippetsModal.setAttribute('hidden', '');
      document.body.classList.remove('guide-open');
    }
  };

  window.deleteSnippet = function(index) {
    if (!confirm('Delete this snippet?')) return;
    const snippets = JSON.parse(localStorage.getItem('xddb_snippets') || '[]');
    snippets.splice(index, 1);
    localStorage.setItem('xddb_snippets', JSON.stringify(snippets));
    loadSnippets();
  };

  if (snippetsBtn && snippetsModal) {
    snippetsBtn.addEventListener('click', function() {
      snippetsModal.removeAttribute('hidden');
      document.body.classList.add('guide-open');
      loadSnippets();
    });
    if (closeSnippetsBtn) {
      closeSnippetsBtn.addEventListener('click', function() {
        snippetsModal.setAttribute('hidden', '');
        document.body.classList.remove('guide-open');
      });
    }
    snippetsModal.addEventListener('click', function(e) {
      if (e.target === snippetsModal) {
        snippetsModal.setAttribute('hidden', '');
        document.body.classList.remove('guide-open');
      }
    });
    if (saveSnippetBtn) {
      saveSnippetBtn.addEventListener('click', function() {
        const nameInput = document.getElementById('snippet-name');
        const name = nameInput.value.trim();
        let sql = '';
        if (window.sqlEditor) {
          sql = window.sqlEditor.getValue().trim();
        } else {
          const ta = document.querySelector('textarea[name="sql_query"]');
          if (ta) sql = ta.value.trim();
        }
        if (!name || !sql) {
          alert('Please enter a snippet name and ensure the SQL editor is not empty.');
          return;
        }
        const snippets = JSON.parse(localStorage.getItem('xddb_snippets') || '[]');
        snippets.push({ name, sql });
        localStorage.setItem('xddb_snippets', JSON.stringify(snippets));
        nameInput.value = '';
        loadSnippets();
      });
    }
  }

  // Auto-dismiss floating alerts after 4.5 seconds
  const alerts = document.querySelectorAll('.alert');
  if (alerts.length > 0) {
    setTimeout(function() {
      alerts.forEach(function(alert) {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-8px) scale(0.98)';
        setTimeout(function() {
          if (alert.parentNode) {
            alert.style.display = 'none';
          }
        }, 400);
      });
    }, 4500);
  }

  // Interactive Table Grid: Live Quick Filter & Double-Click Inline Cell Editing
  const browseTable = document.getElementById('tableBrowse');
  if (browseTable) {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    const dbName = (document.querySelector('input[name="db"]') || {}).value || '';
    const tableName = (document.querySelector('input[name="table"]') || {}).value || '';
    const pkCol = (document.querySelector('input[name="pk_col"]') || {}).value || '';

    // 1. Live row filter
    const filterInput = document.getElementById('filterTableRows');
    const countBadge = document.getElementById('filteredRowCount');
    if (filterInput) {
      const rows = browseTable.querySelectorAll('tbody tr');
      const totalCount = rows.length;

      filterInput.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        let visibleCount = 0;

        rows.forEach(function(row) {
          const text = row.textContent.toLowerCase();
          const match = q === '' || text.includes(q);
          row.style.display = match ? '' : 'none';
          if (match) visibleCount++;
        });

        if (countBadge) {
          countBadge.textContent = q === '' ? '' : `${visibleCount} of ${totalCount} rows`;
        }
      });
    }

    // 2. Double-click inline cell editing
    browseTable.querySelectorAll('.editable-cell').forEach(function(cell) {
      cell.addEventListener('dblclick', function() {
        if (cell.classList.contains('is-editing')) return;
        const currentVal = cell.getAttribute('data-val') !== null ? cell.getAttribute('data-val') : cell.textContent.trim();
        const colName = cell.getAttribute('data-col');
        const pkVal = cell.getAttribute('data-pk');

        if (!colName || !pkVal) return;

        cell.classList.add('is-editing');
        const originalHtml = cell.innerHTML;

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'cell-inline-input';
        input.value = currentVal;
        cell.innerHTML = '';
        cell.appendChild(input);
        input.focus();
        input.select();

        let isSaving = false;

        function saveEdit() {
          if (isSaving) return;
          const newVal = input.value;
          if (newVal === currentVal) {
            cancelEdit();
            return;
          }
          isSaving = true;
          input.disabled = true;

          const formData = new URLSearchParams();
          formData.append('csrf_token', csrfToken);
          formData.append('action', 'update_cell');
          formData.append('db', dbName);
          formData.append('table', tableName);
          formData.append('pk_col', pkCol);
          formData.append('pk_val', pkVal);
          formData.append('col', colName);
          formData.append('val', newVal);

          fetch('api.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
              'X-CSRF-TOKEN': csrfToken
            },
            body: formData.toString()
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              cell.classList.remove('is-editing');
              cell.setAttribute('data-val', newVal);
              cell.textContent = newVal.length > 50 ? newVal.substring(0, 50) + '...' : newVal;
              cell.classList.add('cell-saved-flash');
              setTimeout(() => cell.classList.remove('cell-saved-flash'), 600);

              const gDot = document.getElementById('globalHistoryDot');
              if (gDot) gDot.style.display = 'inline-block';
              const tDot = document.getElementById('tabHistoryDot');
              if (tDot) tDot.style.display = 'inline-block';
            } else {
              alert('Error updating cell: ' + (data.error || 'Unknown error'));
              cancelEdit();
            }
          })
          .catch(err => {
            alert('Request failed: ' + err.message);
            cancelEdit();
          });
        }

        function cancelEdit() {
          cell.classList.remove('is-editing');
          cell.innerHTML = originalHtml;
        }

        input.addEventListener('keydown', function(e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            saveEdit();
          } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelEdit();
          }
        });

        input.addEventListener('blur', function() {
          setTimeout(function() {
            if (cell.classList.contains('is-editing') && !isSaving) {
              saveEdit();
            }
          }, 150);
        });
      });
    });
  }

})();
