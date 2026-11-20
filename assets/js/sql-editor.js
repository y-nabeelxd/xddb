document.addEventListener('DOMContentLoaded', () => {
    const textareas = document.querySelectorAll('textarea[name="sql_query"]');
    
    textareas.forEach(textarea => {
        // Initialize CodeMirror
        const editor = CodeMirror.fromTextArea(textarea, {
            mode: 'text/x-sql',
            theme: 'monokai',
            lineNumbers: true,
            matchBrackets: true,
            autoCloseBrackets: true,
            extraKeys: {
                "Ctrl-Space": "autocomplete",
                "Ctrl-Enter": function(cm) {
                    const form = textarea.closest('form');
                    if (form) {
                        form.requestSubmit ? form.requestSubmit() : form.submit();
                    }
                },
                "Cmd-Enter": function(cm) {
                    const form = textarea.closest('form');
                    if (form) {
                        form.requestSubmit ? form.requestSubmit() : form.submit();
                    }
                }
            }
        });
        window.sqlEditor = editor;

        // Setup dimensions
        editor.setSize(null, 300);

        // Add Live Preview Container
        const previewContainer = document.createElement('div');
        previewContainer.className = 'sql-live-preview';
        previewContainer.style.marginTop = '15px';
        previewContainer.style.display = 'flex';
        previewContainer.style.flexDirection = 'column';
        previewContainer.style.gap = '10px';
        
        // Insert after the CodeMirror wrapper
        editor.getWrapperElement().parentNode.insertBefore(previewContainer, editor.getWrapperElement().nextSibling);

        // Autocomplete on typing
        editor.on("keyup", function (cm, event) {
            // Ignore navigational keys and space
            if (!cm.state.completionActive && 
                event.keyCode !== 13 && // Enter
                event.keyCode !== 27 && // Esc
                event.keyCode !== 32 && // Space
                event.keyCode !== 8 &&  // Backspace
                (event.keyCode >= 65 && event.keyCode <= 90)) { // A-Z
                CodeMirror.commands.autocomplete(cm, null, {completeSingle: false});
            }
        });

        // Parse and create live preview cards
        editor.on('change', () => {
            // Sync textarea
            editor.save();
            const sql = editor.getValue();
            updateLivePreview(sql, previewContainer, editor);
        });

        // Initial preview
        updateLivePreview(editor.getValue(), previewContainer, editor);
    });
});

function updateLivePreview(sql, container, editor) {
    container.innerHTML = ''; // Clear previous cards
    
    // Clear previous syntax markers
    editor.getAllMarks().forEach(mark => mark.clear());

    if (!sql.trim()) return;

    // Split queries (rudimentary split by semicolon)
    const queries = sql.split(';').map(q => q.trim()).filter(q => q);

    queries.forEach(query => {
        const card = parseQueryToCard(query, editor, sql);
        if (card) {
            container.appendChild(card);
        }
    });
}

const SQL_ICONS = {
    zap: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
    database: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>',
    table: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>',
    insert: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
    edit: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
    trash: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>',
    error: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
    check: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--success);vertical-align:middle;margin-right:4px;"><polyline points="20 6 9 17 4 12"/></svg>',
    tip: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--accent);vertical-align:middle;margin-right:4px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
};

function parseQueryToCard(query, editor, fullSql) {
    const card = document.createElement('div');
    card.style.border = '1px solid var(--border-color, #ccc)';
    card.style.borderRadius = '8px';
    card.style.padding = '12px';
    card.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
    card.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
    card.style.fontFamily = 'system-ui, -apple-system, sans-serif';

    let icon = SQL_ICONS.zap;
    let title = 'SQL Action';
    let details = '';
    let hasError = false;

    // Strip SQL comments for parsing
    let cleanQuery = query.replace(/--.*$/gm, '').replace(/\/\*[\s\S]*?\*\//g, '');
    const singleLineQuery = cleanQuery.replace(/\s+/g, ' ').trim();

    // 1. CREATE DATABASE
    const createDbMatch = singleLineQuery.match(/^CREATE\s+DATABASE\s+([a-zA-Z0-9_]+)/i);
    if (createDbMatch) {
        icon = SQL_ICONS.database;
        title = 'Database Action Card';
        details = `
            <strong>Command:</strong> <code>${query}</code><br>
            <strong>Database Name:</strong> ${createDbMatch[1]}<br>
            <strong>State:</strong> ${SQL_ICONS.check} Created database ${createDbMatch[1]}
        `;
    } 
    // CREATE DATABASE with error (e.g. spaces in name)
    else if (/^CREATE\s+DATABASE/i.test(singleLineQuery) && !/^CREATE\s+DATABASE\s+[a-zA-Z0-9_]+$/i.test(singleLineQuery)) {
        hasError = true;
        icon = SQL_ICONS.error;
        title = 'Syntax Error';
        details = `Invalid database name in: <code>${query}</code><br><br>
        ${SQL_ICONS.tip} <strong>Recommendation:</strong> Database names cannot contain spaces or special characters. Use underscores instead. Example: <code>CREATE DATABASE my_database;</code>`;
    }
    // 2. CREATE TABLE
    else if (/^CREATE\s+TABLE\s+([a-zA-Z0-9_]+)/i.test(singleLineQuery)) {
        const createTableMatch = singleLineQuery.match(/^CREATE\s+TABLE\s+([a-zA-Z0-9_]+)\s*\((.*)\)/i);
        if (createTableMatch) {
            icon = SQL_ICONS.table;
            title = 'Table Creation Card';
            const tableName = createTableMatch[1];
            // Extract column names roughly
            const columnsStr = createTableMatch[2];
            const cols = columnsStr.split(',')
                .map(c => c.trim().split(/\s+/)[0])
                .filter(c => c && !['CONSTRAINT', 'PRIMARY', 'FOREIGN', 'UNIQUE', 'CHECK'].includes(c.toUpperCase()))
                .map(c => c.replace(/`/g, ''));
            
            details = `
                <strong>Command:</strong> <code>CREATE TABLE ${tableName} (...)</code><br>
                <strong>Table Name:</strong> ${tableName}<br>
                <strong>Columns Added:</strong> ${cols.join(', ')}<br>
                <strong>State:</strong> ${SQL_ICONS.check} Created table ${tableName}
            `;
        } else {
            const tableNameMatch = singleLineQuery.match(/^CREATE\s+TABLE\s+([a-zA-Z0-9_]+)/i);
            const tName = tableNameMatch ? tableNameMatch[1] : 'my_table';
            hasError = true;
            icon = SQL_ICONS.error;
            title = 'Syntax Error';
            details = `Invalid CREATE TABLE syntax: <code>${query}</code><br><br>
            ${SQL_ICONS.tip} <strong>Recommendation:</strong> Ensure you have defined columns inside parentheses. Example: <code>CREATE TABLE ${tName} (id INT, name VARCHAR(50))</code>`;
        }
    }
    // 3. INSERT INTO
    else if (/^INSERT\s+INTO\s+([a-zA-Z0-9_]+)/i.test(singleLineQuery)) {
        const insertMatch = singleLineQuery.match(/^INSERT\s+INTO\s+([a-zA-Z0-9_]+)\s*(?:\((.*?)\))?\s*VALUES\s*(.*)/i);
        if (insertMatch) {
            icon = SQL_ICONS.insert;
            title = 'Data Insertion Card';
            const tableName = insertMatch[1];
            const valuesStr = insertMatch[3];
            // Roughly count rows by counting opening parentheses in VALUES part
            const rowCount = (valuesStr.match(/\(/g) || []).length;
            
            details = `
                <strong>Command:</strong> <code>INSERT INTO ${tableName} ...</code><br>
                <strong>Target Table:</strong> ${tableName}<br>
                <strong>Rows Inserted:</strong> ${rowCount}<br>
                <strong>State:</strong> ${SQL_ICONS.check} Data staged for insertion
            `;
        }
    }
    // 4. UPDATE
    else if (/^UPDATE\s+([a-zA-Z0-9_]+)\s+SET\s+(.*?)\s*(?:WHERE\s+(.*))?$/i.test(singleLineQuery)) {
        const updateMatch = singleLineQuery.match(/^UPDATE\s+([a-zA-Z0-9_]+)\s+SET\s+(.*?)\s*(?:WHERE\s+(.*))?$/i);
        icon = SQL_ICONS.edit;
        title = 'Record Update Card';
        details = `
            <strong>Command:</strong> <code>UPDATE ${updateMatch[1]} SET ...</code><br>
            <strong>Target:</strong> ${updateMatch[3] ? updateMatch[3] : 'ALL ROWS (Warning!)'}<br>
            <strong>Fields Modified:</strong> ${updateMatch[2]}<br>
            <strong>State:</strong> ${SQL_ICONS.check} Update action ready
        `;
    }
    // 5. DELETE
    else if (/^DELETE\s+FROM\s+([a-zA-Z0-9_]+)\s*(?:WHERE\s+(.*))?$/i.test(singleLineQuery)) {
        const deleteMatch = singleLineQuery.match(/^DELETE\s+FROM\s+([a-zA-Z0-9_]+)\s*(?:WHERE\s+(.*))?$/i);
        icon = SQL_ICONS.trash;
        title = 'Delete Action Card';
        details = `
            <strong>Command:</strong> <code>DELETE FROM ${deleteMatch[1]} ...</code><br>
            <strong>Target:</strong> ${deleteMatch[2] ? deleteMatch[2] : 'ALL ROWS (Warning!)'}<br>
            <strong>State:</strong> ${SQL_ICONS.error} Removal staged
        `;
    }
    else {
        // Unknown or unsupported command
        return null; 
    }

    if (hasError) {
        card.style.borderColor = 'rgba(255, 69, 58, 0.3)';
        card.style.backgroundColor = 'rgba(255, 69, 58, 0.12)';
        card.style.color = 'var(--danger)';
        
        // Highlight in CodeMirror
        const queryStartIdx = fullSql.indexOf(query);
        if (queryStartIdx !== -1) {
            const startPos = editor.posFromIndex(queryStartIdx);
            const endPos = editor.posFromIndex(queryStartIdx + query.length);
            editor.markText(startPos, endPos, {
                className: 'sql-syntax-error',
                css: 'text-decoration: underline wavy var(--danger, red); text-underline-offset: 3px;'
            });
        }
    }

    card.innerHTML = `
        <div style="font-weight: bold; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; color: ${hasError ? 'var(--danger)' : 'var(--text-primary)'}">
            <span>${icon}</span>
            <span>${title}</span>
        </div>
        <div style="font-size: 0.9em; line-height: 1.5; color: ${hasError ? 'var(--danger)' : 'var(--text-secondary)'};">
            ${details}
        </div>
    `;

    return card;
}
