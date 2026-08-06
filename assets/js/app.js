(function() {
  const saved = localStorage.getItem('pma-theme') || 'dark';
  document.documentElement.setAttribute('data-theme', saved === 'light' ? 'light' : 'dark');

  function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('pma-theme', next);
    updateToggleIcons(next);
  }

  function updateToggleIcons(theme) {
    document.querySelectorAll('.theme-toggle-btn').forEach(function(btn) {
      btn.textContent = theme === 'dark' ? '☀️' : '🌙';
      btn.title = theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme';
    });
  }

  document.querySelectorAll('.theme-toggle-btn').forEach(function(btn) {
    btn.addEventListener('click', toggleTheme);
  });

  updateToggleIcons(saved === 'light' ? 'light' : 'dark');

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
})();
