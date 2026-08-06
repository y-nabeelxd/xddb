document.addEventListener('DOMContentLoaded', () => {
    const textareas = document.querySelectorAll('textarea[name="sql_query"]');
    
    textareas.forEach(textarea => {
        // Initialize CodeMirror
        const editor = CodeMirror.fromTextArea(textarea, {
            mode: 'text/x-sql',
            theme: 'monokai', // Or any other preferred theme
            lineNumbers: true,
            matchBrackets: true,
            autoCloseBrackets: true,
            extraKeys: {"Ctrl-Space": "autocomplete"}
        });

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

function parseQueryToCard(query, editor, fullSql) {
    const card = document.createElement('div');
    card.style.border = '1px solid var(--border-color, #ccc)';
    card.style.borderRadius = '8px';
    card.style.padding = '12px';
    card.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
    card.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
    card.style.fontFamily = 'system-ui, -apple-system, sans-serif';

    let icon = '⚡';
    let title = 'SQL Action';
    let details = '';
    let hasError = false;

    // Strip SQL comments for parsing
    let cleanQuery = query.replace(/--.*$/gm, '').replace(/\/\*[\s\S]*?\*\//g, '');
    const singleLineQuery = cleanQuery.replace(/\s+/g, ' ').trim();

    // 1. CREATE DATABASE
    const createDbMatch = singleLineQuery.match(/^CREATE\s+DATABASE\s+([a-zA-Z0-9_]+)/i);
    if (createDbMatch) {
        icon = '🗄️';
        title = 'Database Action Card';
        details = `
            <strong>Command:</strong> <code>${query}</code><br>
            <strong>Database Name:</strong> ${createDbMatch[1]}<br>
            <strong>State:</strong> ✅ Created database ${createDbMatch[1]}
        `;
    } 
    // CREATE DATABASE with error (e.g. spaces in name)
    else if (/^CREATE\s+DATABASE/i.test(singleLineQuery) && !/^CREATE\s+DATABASE\s+[a-zA-Z0-9_]+$/i.test(singleLineQuery)) {
        hasError = true;
        icon = '❌';
        title = 'Syntax Error';
        details = `Invalid database name in: <code>${query}</code><br><br>
        💡 <strong>Recommendation:</strong> Database names cannot contain spaces or special characters. Use underscores instead. Example: <code>CREATE DATABASE my_database;</code>`;
    }
    // 2. CREATE TABLE
    else if (/^CREATE\s+TABLE\s+([a-zA-Z0-9_]+)/i.test(singleLineQuery)) {
        const createTableMatch = singleLineQuery.match(/^CREATE\s+TABLE\s+([a-zA-Z0-9_]+)\s*\((.*)\)/i);
        if (createTableMatch) {
            icon = '📋';
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
                <strong>State:</strong> ✅ Created table ${tableName}
            `;
        } else {
            const tableNameMatch = singleLineQuery.match(/^CREATE\s+TABLE\s+([a-zA-Z0-9_]+)/i);
            const tName = tableNameMatch ? tableNameMatch[1] : 'my_table';
            hasError = true;
            icon = '❌';
            title = 'Syntax Error';
            details = `Invalid CREATE TABLE syntax: <code>${query}</code><br><br>
            💡 <strong>Recommendation:</strong> Ensure you have defined columns inside parentheses. Example: <code>CREATE TABLE ${tName} (id INT, name VARCHAR(50))</code>`;
        }
    }
    // 3. INSERT INTO
    else if (/^INSERT\s+INTO\s+([a-zA-Z0-9_]+)/i.test(singleLineQuery)) {
        const insertMatch = singleLineQuery.match(/^INSERT\s+INTO\s+([a-zA-Z0-9_]+)\s*(?:\((.*?)\))?\s*VALUES\s*(.*)/i);
        if (insertMatch) {
            icon = '📥';
            title = 'Data Insertion Card';
            const tableName = insertMatch[1];
            const valuesStr = insertMatch[3];
            // Roughly count rows by counting opening parentheses in VALUES part
            const rowCount = (valuesStr.match(/\(/g) || []).length;
            
            details = `
                <strong>Command:</strong> <code>INSERT INTO ${tableName} ...</code><br>
                <strong>Target Table:</strong> ${tableName}<br>
                <strong>Rows Inserted:</strong> ${rowCount}<br>
                <strong>State:</strong> ✅ Data staged for insertion
            `;
        }
    }
    // 4. UPDATE
    else if (/^UPDATE\s+([a-zA-Z0-9_]+)\s+SET\s+(.*?)\s*(?:WHERE\s+(.*))?$/i.test(singleLineQuery)) {
        const updateMatch = singleLineQuery.match(/^UPDATE\s+([a-zA-Z0-9_]+)\s+SET\s+(.*?)\s*(?:WHERE\s+(.*))?$/i);
        icon = '✏️';
        title = 'Record Update Card';
        details = `
            <strong>Command:</strong> <code>UPDATE ${updateMatch[1]} SET ...</code><br>
            <strong>Target:</strong> ${updateMatch[3] ? updateMatch[3] : 'ALL ROWS (Warning!)'}<br>
            <strong>Fields Modified:</strong> ${updateMatch[2]}<br>
            <strong>State:</strong> ✅ Update action ready
        `;
    }
    // 5. DELETE
    else if (/^DELETE\s+FROM\s+([a-zA-Z0-9_]+)\s*(?:WHERE\s+(.*))?$/i.test(singleLineQuery)) {
        const deleteMatch = singleLineQuery.match(/^DELETE\s+FROM\s+([a-zA-Z0-9_]+)\s*(?:WHERE\s+(.*))?$/i);
        icon = '🗑️';
        title = 'Delete Action Card';
        details = `
            <strong>Command:</strong> <code>DELETE FROM ${deleteMatch[1]} ...</code><br>
            <strong>Target:</strong> ${deleteMatch[2] ? deleteMatch[2] : 'ALL ROWS (Warning!)'}<br>
            <strong>State:</strong> ❌ Removal staged
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
