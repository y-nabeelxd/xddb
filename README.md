<div align="center">
  <h1>XDDB</h1>
  <p>A fast, modern web-based database manager for MySQL and MariaDB.</p>
  <p><strong>100% Free and Open Source</strong></p>
</div>

---

## Overview

XDDB is a lightweight database manager for MySQL and MariaDB databases. Built with pure PHP and vanilla JavaScript, it gives developers and system administrators a clean interface to inspect tables, run queries, edit records, and track database changes without requiring heavy frameworks or external dependencies.

## Key Features

- **Interactive Data Charts:** Generate bar, line, pie, doughnut, and polar area charts directly from query results or browsed tables. Choose label and value columns, preview the chart in real time, and export directly to PNG images.
- **Audit Log & Change Tracking:** Every update, insert, delete, and schema modification is recorded with column-level before and after values, row snapshots, and query execution times.
- **Inline Cell Editing:** Double-click any cell in browse view to edit values in place, saving changes instantly by pressing Enter or clicking outside.
- **Multi-Row Bulk Operations:** Select multiple records to bulk copy, bulk edit in a side-by-side view, or bulk delete in one pass.
- **Targeted Exports:** Export entire databases, full tables, or only the specific rows you select into SQL, CSV, JSON, or XML formats.
- **Offline SQL Editor:** Full syntax highlighting, SQL auto-completion, query formatting, and reusable code snippets that function completely offline.
- **Schema & Relationship Inspection:** View table structures, column types, storage engines, foreign keys, indexes, and table sizes.
- **Zero Configuration Service:** Control the background server with a single `xddb` CLI command (`start`, `stop`, `restart`, `status`) on Linux and Windows.

---

## Installation

### 1. Universal Linux Installer (Recommended for Linux)

Works automatically on **any** Linux distribution (Ubuntu, Debian, AlmaLinux, Rocky Linux, Fedora, RHEL, CentOS, Arch Linux, Manjaro, Alpine Linux, openSUSE). It automatically detects your operating system, package manager, and service manager:

```bash
curl -sSL https://nabeelxd.vercel.app/xddb-installer | sudo bash
```

---

### 2. Dedicated Linux Installers

If you prefer running an installer tailored directly to your specific Linux distribution:

- **Ubuntu / Debian / WSL / Linux Mint / Kali:**
  ```bash
  curl -sSL https://nabeelxd.vercel.app/xddb-ubuntu-installer | sudo bash
  ```

- **RHEL / CentOS / Fedora / AlmaLinux / Rocky Linux / Amazon Linux:**
  ```bash
  curl -sSL https://nabeelxd.vercel.app/xddb-rhel-installer | sudo bash
  ```

- **Arch Linux / Manjaro / EndeavourOS:**
  ```bash
  curl -sSL https://nabeelxd.vercel.app/xddb-arch-installer | sudo bash
  ```

- **Alpine Linux / Docker Containers:**
  ```sh
  curl -sSL https://nabeelxd.vercel.app/xddb-alpine-installer | sh
  ```

- **openSUSE (Leap / Tumbleweed) / SLES:**
  ```bash
  curl -sSL https://nabeelxd.vercel.app/xddb-opensuse-installer | sudo bash
  ```

---

### 3. Windows (1-Line PowerShell Installer)

Install and run XDDB on Windows with a single PowerShell command. This configures a portable background server and places a shortcut on your Desktop:

```powershell
iwr -useb https://nabeelxd.vercel.app/xddb-windows-installer.ps1 | iex
```

---

### 4. Manual Installation (Any OS)

XDDB is written in pure PHP and runs on any platform (Windows, macOS, or Linux):

1. Download the latest `xddb.zip` from the [Releases page](https://github.com/y-nabeelxd/xddb/releases).
2. Extract the archive into your desired folder.
3. Start it using any PHP server environment (XAMPP, WAMP, Laragon, MAMP, Herd) or PHP's built-in web server:
   ```bash
   php -S 127.0.0.1:7070
   ```

---

## Managing XDDB

Once installed via an automated installer, you can control the XDDB service using the `xddb` CLI command:

| Command | Action |
| :--- | :--- |
| `sudo xddb start` | Start the XDDB server in the background |
| `sudo xddb stop` | Stop the XDDB server |
| `sudo xddb restart` | Restart the XDDB server |
| `sudo xddb status` | Check whether XDDB is running and view active port |

*(On Windows, run `xddb start`, `xddb stop`, `xddb restart`, or `xddb status` directly from PowerShell or Command Prompt).*

---

## Usage

Once started, open your web browser and navigate to:
```
http://127.0.0.1:7070/
```
*(or your remote server's IP address: `http://<SERVER_IP>:7070/`)*

---

## Contributing

Contributions are welcome. If you find a bug, want to improve the interface, or have a feature idea, feel free to open an issue or submit a pull request.

---

<div align="center">
  <i>Developed by Nabeel XDD · TeamXDD</i>
</div>
