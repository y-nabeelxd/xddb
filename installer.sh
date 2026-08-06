#!/bin/bash

set -e

APP_DIR="/root/xddb"
ZIP_URL="https://github.com/y-nabeelxd/xddb/releases/latest/download/xddb.zip"
PORT="7070"
DB_USERNAME="nabeel"
DB_USER_PASSWORD="nabeelxd"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
BOLD='\033[1m'
NC='\033[0m'

if [ "$EUID" -ne 0 ]; then
    echo ""
    echo -e "${RED}╔════════════════════════════════════════╗${NC}"
    echo -e "${RED}║      ❌ Please run as root ❌          ║${NC}"
    echo -e "${RED}╚════════════════════════════════════════╝${NC}"
    echo ""
    exit 1
fi

clear

echo ""
echo -e "${CYAN}╔════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║${NC}                                        ${CYAN}║${NC}"
echo -e "${CYAN}║${NC}     ${GREEN}${BOLD}XDDB Installer${NC}                     ${CYAN}║${NC}"
echo -e "${CYAN}║${NC}     ${WHITE}${BOLD}NabeelXD${NC}                           ${CYAN}║${NC}"
echo -e "${CYAN}║${NC}                                        ${CYAN}║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════╝${NC}"
echo ""

echo -e "${YELLOW}[${CYAN}1${YELLOW}/${CYAN}7${YELLOW}] ${BLUE}Updating packages...${NC}"
sleep 2
apt update -y >/dev/null 2>&1
clear

echo -e "${YELLOW}[${CYAN}2${YELLOW}/${CYAN}7${YELLOW}] ${BLUE}Upgrading packages...${NC}"
sleep 2
apt upgrade -y >/dev/null 2>&1
clear

echo -e "${YELLOW}[${CYAN}3${YELLOW}/${CYAN}7${YELLOW}] ${BLUE}Installing required packages...${NC}"
sleep 2
echo -e "${PURPLE}→${NC} Installing PHP and dependencies..."
apt install -y wget curl unzip ufw lsof net-tools php php-cli php-common php-curl php-mysql php-mbstring php-xml php-zip php-json >/dev/null 2>&1
echo -e "${GREEN}✓${NC} All packages installed successfully"
sleep 2
clear

echo -e "${YELLOW}[${CYAN}4${YELLOW}/${CYAN}7${YELLOW}] ${BLUE}Checking database server...${NC}"
echo ""

MYSQL_INSTALLED=false
MARIADB_INSTALLED=false

if dpkg -l | grep -q mysql-server; then
    MYSQL_INSTALLED=true
fi

if dpkg -l | grep -q mariadb-server; then
    MARIADB_INSTALLED=true
fi

if [ "$MYSQL_INSTALLED" = false ] && [ "$MARIADB_INSTALLED" = false ]; then
    echo -e "${YELLOW}⚠${NC}  No MySQL or MariaDB found."
    echo -e "${PURPLE}→${NC} Installing MySQL Server..."
    sleep 2
    DEBIAN_FRONTEND=noninteractive apt install -y mysql-server >/dev/null 2>&1
    systemctl enable mysql >/dev/null 2>&1 || true
    systemctl start mysql >/dev/null 2>&1 || true
    clear
    echo -e "${GREEN}✓${NC} MySQL installed successfully"
    sleep 2
    
    echo ""
    echo -e "${PURPLE}→${NC} Creating database user ${WHITE}'${DB_USERNAME}'${NC}..."
    mysql -e "CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${DB_USER_PASSWORD}';" 2>/dev/null
    mysql -e "CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'127.0.0.1' IDENTIFIED BY '${DB_USER_PASSWORD}';" 2>/dev/null
    mysql -e "CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'%' IDENTIFIED BY '${DB_USER_PASSWORD}';" 2>/dev/null
    mysql -e "GRANT ALL PRIVILEGES ON *.* TO '${DB_USERNAME}'@'localhost' WITH GRANT OPTION;" 2>/dev/null
    mysql -e "GRANT ALL PRIVILEGES ON *.* TO '${DB_USERNAME}'@'127.0.0.1' WITH GRANT OPTION;" 2>/dev/null
    mysql -e "GRANT ALL PRIVILEGES ON *.* TO '${DB_USERNAME}'@'%' WITH GRANT OPTION;" 2>/dev/null
    mysql -e "FLUSH PRIVILEGES;" 2>/dev/null
    clear
    echo -e "${GREEN}✓${NC} Database user ${WHITE}'${DB_USERNAME}'${NC} created with full privileges"
    echo ""
    echo -e "${BLUE}╔════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║${NC}  ${YELLOW}📌 Database Credentials${NC}               ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}                                        ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}  ${WHITE}Username:${NC} ${PURPLE}${DB_USERNAME}${NC}                      ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}  ${WHITE}Password:${NC} ${PURPLE}${DB_USER_PASSWORD}${NC}                    ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}                                        ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}  ${RED}⚠ Keep these credentials secure!${NC}      ${BLUE}║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════╝${NC}"
    sleep 10
    
elif [ "$MYSQL_INSTALLED" = true ]; then
    echo -e "${GREEN}✓${NC} MySQL detected"
    systemctl enable mysql >/dev/null 2>&1 || true
    systemctl start mysql >/dev/null 2>&1 || true
    echo -e "${YELLOW}ℹ${NC}  MySQL already installed, skipping user creation"
    sleep 2
    clear
    
elif [ "$MARIADB_INSTALLED" = true ]; then
    echo -e "${GREEN}✓${NC} MariaDB detected"
    systemctl enable mariadb >/dev/null 2>&1 || true
    systemctl start mariadb >/dev/null 2>&1 || true
    echo -e "${YELLOW}ℹ${NC}  MariaDB already installed, skipping user creation"
    sleep 2
    clear
fi

mkdir -p "$APP_DIR"
rm -rf "$APP_DIR"/*

echo -e "${YELLOW}[${CYAN}5${YELLOW}/${CYAN}7${YELLOW}] ${BLUE}Downloading XDDB...${NC}"
sleep 2
wget -O /tmp/xddb.zip "$ZIP_URL" >/dev/null 2>&1
unzip -o /tmp/xddb.zip -d "$APP_DIR" >/dev/null 2>&1
rm -f /tmp/xddb.zip
echo -e "${GREEN}✓${NC} Download complete"
sleep 2
clear

echo -e "${YELLOW}[${CYAN}6${YELLOW}/${CYAN}7${YELLOW}] ${BLUE}Installing xddb...${NC}"

cat > /usr/local/bin/xddb << 'EOF'
#!/bin/bash

APP_DIR="/root/xddb"
PORT="7070"
PID_FILE="/var/run/xddb.pid"
LOG_FILE="/var/log/xddb.log"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
PURPLE='\033[0;35m'
WHITE='\033[1;37m'
NC='\033[0m'

check_php() {
    if ! command -v php >/dev/null 2>&1; then
        echo -e "${RED}✕${NC} PHP is not installed."
        echo ""
        echo -e "${CYAN}─ Developed By: NabeelXD${NC}"
        echo ""
        exit 1
    fi
}

open_port() {
    if command -v ufw >/dev/null 2>&1; then
        ufw allow ${PORT}/tcp >/dev/null 2>&1 || true
    fi
}

start_app() {
    check_php
    open_port

    if [ ! -d "$APP_DIR" ]; then
        echo -e "${RED}✕${NC} XDDB not installed."
        echo ""
        echo -e "${CYAN}─ Developed By: NabeelXD${NC}"
        echo ""
        exit 1
    fi

    if lsof -i:${PORT} >/dev/null 2>&1; then
        echo -e "${YELLOW}⚠${NC}  Port ${PORT} already in use."
        echo ""
        echo -e "${CYAN}─ Developed By: NabeelXD${NC}"
        echo ""
        exit 1
    fi

    cd "$APP_DIR"
    nohup php -S 0.0.0.0:${PORT} > "$LOG_FILE" 2>&1 &
    echo $! > "$PID_FILE"
    sleep 2

    if ps -p $(cat "$PID_FILE") > /dev/null 2>&1; then
        echo ""
        echo -e "${GREEN}╔════════════════════════════════════════╗${NC}"
        echo -e "${GREEN}║${NC}     ${WHITE}XDDB Started Successfully${NC}          ${GREEN}║${NC}"
        echo -e "${GREEN}╚════════════════════════════════════════╝${NC}"
        echo ""
        echo -e "${CYAN}🌐 Local URL:${NC}"
        echo -e "${WHITE}   http://127.0.0.1:${PORT}${NC}"
        echo ""
        echo -e "${CYAN}🌍 Server URL:${NC}"
        echo -e "${WHITE}   http://$(hostname -I | awk '{print $1}'):${PORT}${NC}"
        echo ""
        echo -e "${CYAN}─ Developed By: NabeelXD${NC}"
        echo ""
    else
        echo -e "${RED}✕${NC} Failed to start XDDB."
        echo ""
        echo -e "${CYAN}─ Developed By: NabeelXD${NC}"
        echo ""
        exit 1
    fi
}

stop_app() {
    if [ -f "$PID_FILE" ]; then
        PID=$(cat "$PID_FILE")
        kill "$PID" >/dev/null 2>&1 || true
        rm -f "$PID_FILE"
        echo -e "${GREEN}✓${NC} XDDB stopped."
        echo ""
        echo -e "${CYAN}─ Developed By: NabeelXD${NC}"
        echo ""
    else
        pkill -f "php -S 0.0.0.0:${PORT}" >/dev/null 2>&1 || true
        echo -e "${GREEN}✓${NC} XDDB stopped."
        echo ""
        echo -e "${CYAN}─ Developed By: NabeelXD${NC}"
        echo ""
    fi
}

status_app() {
    if lsof -i:${PORT} >/dev/null 2>&1; then
        echo -e "${GREEN}●${NC} XDDB is ${GREEN}running${NC} on port ${PORT}"
        echo ""
        echo -e "${CYAN}─ Developed By: NabeelXD${NC}"
        echo ""
    else
        echo -e "${RED}●${NC} XDDB is ${RED}stopped${NC}"
        echo ""
        echo -e "${CYAN}─ Developed By: NabeelXD${NC}"
        echo ""
    fi
}

restart_app() {
    stop_app
    sleep 1
    start_app
}

case "$1" in
    start)
        start_app
        ;;
    stop)
        stop_app
        ;;
    restart)
        restart_app
        ;;
    status)
        status_app
        ;;
    *)
        echo ""
        echo -e "${CYAN}╔════════════════════════════════════════╗${NC}"
        echo -e "${CYAN}║${NC}          ${WHITE}XDDB Commands${NC}                 ${CYAN}║${NC}"
        echo -e "${CYAN}╚════════════════════════════════════════╝${NC}"
        echo ""
        echo -e "${GREEN}sudo xddb start${NC}    - Start XDDB server"
        echo -e "${RED}sudo xddb stop${NC}     - Stop XDDB server"
        echo -e "${YELLOW}sudo xddb restart${NC}  - Restart XDDB server"
        echo -e "${BLUE}sudo xddb status${NC}   - Check XDDB status"
        echo ""
        echo -e "${CYAN}─ Developed By: NabeelXD${NC}"
        echo ""
        ;;
esac
EOF

chmod +x /usr/local/bin/xddb
echo -e "${GREEN}✓${NC} Command installed successfully"
sleep 2
clear

echo -e "${YELLOW}[${CYAN}7${YELLOW}/${CYAN}7${YELLOW}] ${BLUE}Opening firewall port ${PORT}...${NC}"
ufw allow ${PORT}/tcp >/dev/null 2>&1 || true
echo -e "${GREEN}✓${NC} Port ${PORT} opened"
sleep 2
clear

echo ""
echo -e "${GREEN}╔════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}                                        ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}   ${WHITE}XDDB Installed Successfully${NC}          ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}   ${CYAN}Developed By: NabeelXD${NC}               ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                        ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}📌 Note:${NC} xddb requires sudo privileges to run"
echo ""
echo -e "${CYAN}Commands:${NC}"
echo -e "${GREEN}  sudo xddb start${NC}    - Start XDDB server"
echo -e "${RED}  sudo xddb stop${NC}     - Stop XDDB server"
echo -e "${YELLOW}  sudo xddb restart${NC}  - Restart XDDB server"
echo -e "${BLUE}  sudo xddb status${NC}   - Check XDDB status"
echo ""