<div class="guide-overlay" id="guideOverlay" hidden>
  <div class="guide-modal" role="dialog" aria-labelledby="guideTitle" aria-modal="true" style="max-width: 900px;">
    <div class="guide-modal-header">
      <h2 id="guideTitle">Ultimate MySQL / MariaDB SQL Reference Guide</h2>
      <button type="button" class="btn-icon guide-close-btn" id="closeGuideBtn" title="Close guide">✕</button>
    </div>
    <div class="guide-modal-body">
      <p class="guide-intro">The most comprehensive SQL reference from beginner to expert — covers everything from basic queries to advanced administration and optimization.</p>

      <section class="guide-section" style="background: var(--bg-hover); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border); margin-bottom: 24px;">
        <h3><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: text-bottom; margin-right: 6px; color: var(--accent);"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> XDDB vs phpMyAdmin Feature Map</h3>
        <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 12px; line-height: 1.6;">
          You might wonder how XDDB compares to the complete phpMyAdmin feature set. XDDB is designed to be a lightweight, modern, and lightning-fast alternative that focuses on the most critical features a developer needs daily.
        </p>
        <ul style="font-size: 13px; color: var(--text-primary); margin-left: 20px; line-height: 1.6; list-style-type: disc;">
          <li><strong>Navigation & Dashboard (Modules 1-5):</strong> XDDB offers a highly polished dashboard, sidebar schema navigation, and cross-database search without the clutter of phpMyAdmin's older UI.</li>
          <li><strong>Database & Tables (Modules 6-9):</strong> Create, drop, truncate, optimize, and repair tables. Edit charset/collation, build tables visually, and leverage all MySQL data types.</li>
          <li><strong>SQL & Data Browsing (Modules 10-12):</strong> A dedicated SQL tab with syntax highlighting. The Browse tab features inline editing, bulk row actions (delete, copy, export), index viewing, and a printable Data Dictionary.</li>
          <li><strong>Import & Export (Modules 14-15):</strong> Export databases/tables directly to SQL, and import full SQL dump files.</li>
          <li><strong>Visual Designer (Module 18):</strong> Drag-and-drop visual schema builder with live relationship (foreign key) mapping.</li>
          <li><strong>Automation (Modules 25-28):</strong> Native visual editors for Triggers, Events, Stored Procedures, and Views.</li>
        </ul>
        <p style="font-size: 13px; color: var(--text-secondary); margin-top: 12px; line-height: 1.6;">
          <em>Note: To keep XDDB fast and secure, advanced server administration (User Accounts, Global Privileges, Server Status Variables, Replication) is intentionally omitted and best handled via CLI or dedicated server management tools.</em>
        </p>
      </section>

      <section class="guide-section">
        <h3>1. Database Management</h3>
        <div class="guide-block"><h4>Create a Database</h4><pre><code>CREATE DATABASE IF NOT EXISTS my_database;
CREATE DATABASE my_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</code></pre></div>
        <div class="guide-block"><h4>Delete a Database</h4><pre><code>DROP DATABASE IF EXISTS my_database;</code></pre></div>
        <div class="guide-block"><h4>Select/View Databases</h4><pre><code>SHOW DATABASES;
USE my_database;
SELECT DATABASE(); -- See currently active database</code></pre></div>
      </section>

      <section class="guide-section">
        <h3>2. Table Management (DDL)</h3>
        <div class="guide-block"><h4>Create Table</h4><pre><code>CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    age INT CHECK (age >= 18),
    role ENUM('admin', 'user', 'guest') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);</code></pre></div>
        <div class="guide-block"><h4>Create Table with Foreign Key</h4><pre><code>CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total DECIMAL(10, 2),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);</code></pre></div>
        <div class="guide-block"><h4>Modify Columns (ALTER TABLE)</h4><pre><code>ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER email;
ALTER TABLE users MODIFY COLUMN age TINYINT;
ALTER TABLE users CHANGE COLUMN phone mobile_number VARCHAR(20);
ALTER TABLE users DROP COLUMN age;</code></pre></div>
        <div class="guide-block"><h4>Rename / Delete Tables</h4><pre><code>RENAME TABLE users TO members;
DROP TABLE IF EXISTS members, orders;
TRUNCATE TABLE members; -- Deletes all data, resets AUTO_INCREMENT</code></pre></div>
      </section>

      <section class="guide-section">
        <h3>3. Data Manipulation (DML)</h3>
        <div class="guide-block"><h4>Insert Data</h4><pre><code>INSERT INTO users (username, email) VALUES ('nabeel', 'nabeel@example.com');
INSERT INTO users (username, email) VALUES 
('alice', 'alice@test.com'), 
('bob', 'bob@test.com');</code></pre></div>
        <div class="guide-block"><h4>Insert or Update (UPSERT)</h4><pre><code>INSERT INTO users (id, username, email) VALUES (1, 'nabeel', 'new@email.com')
ON DUPLICATE KEY UPDATE email = 'new@email.com';</code></pre></div>
        <div class="guide-block"><h4>Update Data</h4><pre><code>UPDATE users SET role = 'admin', age = 25 WHERE username = 'nabeel';</code></pre></div>
        <div class="guide-block"><h4>Delete Data</h4><pre><code>DELETE FROM users WHERE age < 18;</code></pre></div>
      </section>

      <section class="guide-section">
        <h3>4. Querying Data (DQL)</h3>
        <div class="guide-block"><h4>Basic Select</h4><pre><code>SELECT * FROM users;
SELECT username, email FROM users WHERE role = 'admin';</code></pre></div>
        <div class="guide-block"><h4>Filtering (WHERE clauses)</h4><pre><code>SELECT * FROM users WHERE age >= 18 AND status = 'active';
SELECT * FROM users WHERE city IN ('New York', 'London', 'Tokyo');
SELECT * FROM users WHERE email LIKE '%@gmail.com';
SELECT * FROM users WHERE age BETWEEN 20 AND 30;
SELECT * FROM users WHERE phone IS NULL;</code></pre></div>
        <div class="guide-block"><h4>Sorting & Pagination</h4><pre><code>SELECT * FROM users ORDER BY created_at DESC;
SELECT * FROM users ORDER BY role ASC, age DESC;
SELECT * FROM users LIMIT 10 OFFSET 20; -- Pagination (Rows 21-30)</code></pre></div>
      </section>

      <section class="guide-section">
        <h3>5. Aggregation & Grouping</h3>
        <div class="guide-block"><h4>Aggregate Functions</h4><pre><code>SELECT COUNT(*) AS total_users FROM users;
SELECT AVG(age), MAX(age), MIN(age), SUM(balance) FROM users;</code></pre></div>
        <div class="guide-block"><h4>GROUP BY and HAVING</h4><pre><code>SELECT role, COUNT(*) AS count FROM users GROUP BY role;
SELECT department, AVG(salary) FROM employees GROUP BY department HAVING AVG(salary) > 50000;</code></pre></div>
      </section>

      <section class="guide-section">
        <h3>6. Joins (Combining Tables)</h3>
        <div class="guide-block"><h4>INNER JOIN (Matching records in both)</h4><pre><code>SELECT users.username, orders.total 
FROM users 
INNER JOIN orders ON users.id = orders.user_id;</code></pre></div>
        <div class="guide-block"><h4>LEFT JOIN (All users, matching orders if any)</h4><pre><code>SELECT users.username, orders.total 
FROM users 
LEFT JOIN orders ON users.id = orders.user_id;</code></pre></div>
        <div class="guide-block"><h4>RIGHT JOIN</h4><pre><code>SELECT users.username, orders.total 
FROM users 
RIGHT JOIN orders ON users.id = orders.user_id;</code></pre></div>
      </section>

      <section class="guide-section">
        <h3>7. Indexes & Performance</h3>
        <div class="guide-block"><h4>Create / Drop Index</h4><pre><code>CREATE INDEX idx_email ON users(email);
CREATE UNIQUE INDEX idx_username ON users(username);
CREATE FULLTEXT INDEX idx_content ON articles(content);
DROP INDEX idx_email ON users;</code></pre></div>
        <div class="guide-block"><h4>Analyze Queries</h4><pre><code>EXPLAIN SELECT * FROM users WHERE email = 'test@test.com';</code></pre></div>
      </section>

      <section class="guide-section">
        <h3>8. Views, Triggers, & Stored Procedures</h3>
        <div class="guide-block"><h4>Views (Virtual Tables)</h4><pre><code>CREATE VIEW active_admins AS SELECT * FROM users WHERE role = 'admin' AND status = 'active';
SELECT * FROM active_admins;
DROP VIEW active_admins;</code></pre></div>
        <div class="guide-block"><h4>Triggers</h4><pre><code>DELIMITER //
CREATE TRIGGER before_user_update 
BEFORE UPDATE ON users FOR EACH ROW 
BEGIN
    SET NEW.updated_at = NOW();
END//
DELIMITER ;</code></pre></div>
        <div class="guide-block"><h4>Stored Procedures</h4><pre><code>DELIMITER //
CREATE PROCEDURE GetUserOrders(IN target_user_id INT)
BEGIN
    SELECT * FROM orders WHERE user_id = target_user_id;
END//
DELIMITER ;
CALL GetUserOrders(1);</code></pre></div>
      </section>

      <section class="guide-section">
        <h3>9. Transactions (ACID)</h3>
        <div class="guide-block"><h4>Commit / Rollback</h4><pre><code>START TRANSACTION;
UPDATE accounts SET balance = balance - 100 WHERE id = 1;
UPDATE accounts SET balance = balance + 100 WHERE id = 2;
COMMIT; -- Save changes
-- OR ROLLBACK; (Undo changes if an error occurred)</code></pre></div>
      </section>

      <section class="guide-section">
        <h3>10. User Administration & Security (DCL)</h3>
        <div class="guide-block"><h4>Create / Delete User</h4><pre><code>CREATE USER 'developer'@'localhost' IDENTIFIED BY 'SecurePass123!';
CREATE USER 'remote_user'@'%' IDENTIFIED BY 'Password!';
DROP USER 'developer'@'localhost';</code></pre></div>
        <div class="guide-block"><h4>Manage Privileges</h4><pre><code>GRANT ALL PRIVILEGES ON my_database.* TO 'developer'@'localhost';
GRANT SELECT, INSERT ON my_database.users TO 'developer'@'localhost';
REVOKE INSERT ON my_database.users FROM 'developer'@'localhost';
FLUSH PRIVILEGES;
SHOW GRANTS FOR 'developer'@'localhost';</code></pre></div>
        <div class="guide-block"><h4>Change Passwords</h4><pre><code>ALTER USER 'developer'@'localhost' IDENTIFIED BY 'NewPassword456!';</code></pre></div>
      </section>

      <section class="guide-section">
        <h3>11. Backup, Restore, and Maintenance</h3>
        <div class="guide-block"><h4>MySQL Dump (Run in Terminal)</h4><pre><code>mysqldump -u root -p my_database > backup.sql
mysqldump -u root -p --all-databases > full_backup.sql</code></pre></div>
        <div class="guide-block"><h4>Restore (Run in Terminal)</h4><pre><code>mysql -u root -p my_database < backup.sql</code></pre></div>
        <div class="guide-block"><h4>Table Maintenance</h4><pre><code>OPTIMIZE TABLE users;
REPAIR TABLE users;
ANALYZE TABLE users;
CHECK TABLE users;</code></pre></div>
      </section>

      <section class="guide-section">
        <h3>12. Common Functions & Expressions</h3>
        <div class="guide-block"><h4>String Functions</h4><pre><code>SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM users;
SELECT UPPER(username), LOWER(email), LENGTH(password) FROM users;
SELECT SUBSTRING(description, 1, 50) FROM posts;</code></pre></div>
        <div class="guide-block"><h4>Date/Time Functions</h4><pre><code>SELECT NOW(), CURRENT_DATE(), CURRENT_TIME();
SELECT DATE_ADD(NOW(), INTERVAL 7 DAY);
SELECT DATEDIFF('2026-12-31', NOW());
SELECT YEAR(created_at), MONTH(created_at) FROM users;</code></pre></div>
        <div class="guide-block"><h4>Control Flow</h4><pre><code>SELECT username, 
  IF(age >= 18, 'Adult', 'Minor') AS status 
FROM users;

SELECT username,
  CASE 
    WHEN role = 'admin' THEN 'Full Access'
    WHEN role = 'user' THEN 'Limited Access'
    ELSE 'No Access'
  END AS access_level
FROM users;</code></pre></div>
      </section>

      <section class="guide-section">
        <h3>13. Advanced Data Types</h3>
        <div class="guide-block"><h4>JSON Fields</h4><pre><code>CREATE TABLE events (id INT, data JSON);
INSERT INTO events (id, data) VALUES (1, '{"name": "login", "browser": "Chrome"}');
SELECT data->>'$.name' AS event_name FROM events;</code></pre></div>
      </section>

    </div>
  </div>
</div>
