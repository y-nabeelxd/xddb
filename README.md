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

### Easy Installer (Ubuntu/Debian/WSL)

To install XDDB on a Debian based OS, run the following command in your terminal. This will install all required dependancies (PHP, MySQL/MariaDB if not already installed), configure the firewall, and allow XDDB to be run in the background.

```bash
curl -sSL https://nabeelxd.vercel.app/xddb-ubuntu-installer | sudo bash
```

**Managing XDDB (Easy Installer):**  
Once installed, you can manage XDDB using the following commands:
- `sudo xddb start` - To start XDDB
- `sudo xddb stop` - To stop XDDB
- `sudo xddb restart` - To restart XDDB
- `sudo xddb status` - To check if XDDB is running

---

### Manual Installer (Windows/macOS/Other Linux)

XDDB is written in pure PHP, and can therefore be run on any platform! If you are running Windows, macOS, or a different Linux OS (such as CentOS/Fedora) then you can easily run XDDB:

1. Download the latest `xddb.zip` file from the [Releases page](https://github.com/y-nabeelxd/xddb/releases).
2. Extract the file.
3. Run it using your preferred PHP server (XAMPP, WAMP, MAMP or Laravel Valet).

*Alternatively*, you can run it using PHP's built-in server. In the extracted folder, run:
```bash
php -S 127.0.0.1:7070
```

## Usage

Once installed (either manually or using the easy installer), open up your web browser and go to: `http://127.0.0.1:7070/` (or your server's IP address).

## Contributing

We love contributors! As this is an Open Source project, any contributions you make will improve XDDB for everyone. Whether you're contributing to fix a bug, improve the UI, add new features or documentations, feel free to fork this repo, make a branch and make a Pull Request!

---

<div align="center">
  <i>Developed by NabeelXDD</i>
</div>
