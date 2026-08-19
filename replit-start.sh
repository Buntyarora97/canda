#!/usr/bin/env bash
set -euo pipefail

# Replit preview helper only. Shared hosting continues to use the normal
# Apache/PHP + phpMyAdmin setup documented in README.md.
DATA_DIR="${REPLIT_DB_DIR:-/tmp/gio-mysql}"
SOCKET="${REPLIT_DB_SOCKET:-/tmp/gio-mysql.sock}"
PID_FILE="${REPLIT_DB_PID:-/tmp/gio-mysql.pid}"
PORT="${REPLIT_DB_PORT:-3307}"
LOG_FILE="${REPLIT_DB_LOG:-/tmp/gio-mysql.err}"

if [ ! -d "$DATA_DIR/mysql" ]; then
  mkdir -p "$DATA_DIR"
  mariadb-install-db --datadir="$DATA_DIR" --auth-root-authentication-method=normal --skip-test-db >/tmp/gio-mysql-init.log 2>&1
fi

if ! mariadb-admin --socket="$SOCKET" -uroot ping >/dev/null 2>&1; then
  mariadbd --datadir="$DATA_DIR" --socket="$SOCKET" --port="$PORT" \
    --bind-address=127.0.0.1 --pid-file="$PID_FILE" --log-error="$LOG_FILE" \
    --skip-name-resolve >/tmp/gio-mysql.out 2>&1 &
  for _ in $(seq 1 30); do
    mariadb-admin --socket="$SOCKET" -uroot ping >/dev/null 2>&1 && break
    sleep 1
  done
fi

mariadb --socket="$SOCKET" -uroot -e \
  "CREATE DATABASE IF NOT EXISTS gio_mobility CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER IF NOT EXISTS 'gio'@'127.0.0.1' IDENTIFIED BY 'gio_test_pass';
   GRANT ALL PRIVILEGES ON gio_mobility.* TO 'gio'@'127.0.0.1';
   FLUSH PRIVILEGES;"

if ! mariadb --socket="$SOCKET" -ugio -pgio_test_pass -h127.0.0.1 -P"$PORT" gio_mobility -N -e "SELECT 1 FROM settings LIMIT 1" >/dev/null 2>&1; then
  mariadb --socket="$SOCKET" -ugio -pgio_test_pass -h127.0.0.1 -P"$PORT" gio_mobility < database.sql
fi

exec php -S 0.0.0.0:5000 replit-router.php