<div align="center">
  <h1>XDDB</h1>
  <p>An elegant, fast and modern MySQL/MariaDB database manager.</p>
  <p><strong>100% Free and Open Source</strong></p>
</div>

---

## Overview

XDDB is an elegant, lightweight and incredibly fast database manager for MySQL/MariaDB databases. It allows you to manage your databases in a very sleek and powerful manner. Since this project is 100% free and Open Source, it is transparent and community focused.

## Why use XDDB?

Many database managers can be slow and outdated. However, using XDDB allows you to manage your databases in a much more efficient way!

- **Fast:** Very fast, with near instant query speeds, data navigation and support for large data sets.
- **Beautiful UI:** Modern interface with both dark/light modes, giving a high end enterprise feel.
- **Schema Visualiser:** Easily map out relationships between tables in your database.
- **Powerful Query Editor:** An advanced editor with syntax highlighting, auto-complete and query history.
- **No Configuration:** Simply run the command line script and XDDB will run instantly in the background. There is no complicated configuration required on servers!

## Who is this for?

XDDB is perfect for developers, database admins and sysadmins who want a modern way to manage their databases. Whether you're creating a prototype quickly, managing a large production database or simply running a quick query, XDDB is the perfect tool for you.

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

XDDB is written in pure PHP and can run on any platform (Windows, macOS, or Linux):

1. Download the latest `xddb.zip` from the [Releases page](https://github.com/y-nabeelxd/xddb/releases).
2. Extract the file into your desired directory.
3. Run it using any PHP server environment (XAMPP, WAMP, Laragon, MAMP, Herd) or PHP's built-in web server:
   ```bash
   php -S 127.0.0.1:7070
   ```

---

## Managing XDDB

Once installed via any automated installer, you can control the XDDB service using the `xddb` CLI command:

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

## Contributing

We love contributors! As this is an Open Source project, any contributions you make will improve XDDB for everyone. Whether you're contributing to fix a bug, improve the UI, add new features or documentations, feel free to fork this repo, make a branch and make a Pull Request!

---

<div align="center">
  <i>Developed by NabeelXDD</i>
</div>
