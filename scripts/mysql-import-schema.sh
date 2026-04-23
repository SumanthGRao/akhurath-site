#!/usr/bin/env bash
set -euo pipefail
# Import sql/schema.sql, then (if PHP + config/database.local.php exist) apply idempotent
# patches (users.email, contact_enquiries.email, indexes) and merge legacy file customers
# into the users table. From project root.
#
# Hostinger (default DB/user names):
#   export MYSQL_PWD='your_mysql_password'
#   ./scripts/mysql-import-schema.sh
#
# Local XAMPP (empty root password — set MYSQL_PWD so mysql does not prompt):
#   export MYSQL_PWD=''
#   DB_HOST=127.0.0.1 DB_USER=root DB_NAME=akhurath_studio ./scripts/mysql-import-schema.sh
#
# MySQL prompts for password when MYSQL_PWD is unset:
#   unset MYSQL_PWD
#   ./scripts/mysql-import-schema.sh
#
# After import, if you use file-based customers only: add config/database.local.php, then either
# re-run this script (schema is safe to re-import) or:
#   php scripts/ensure-database.php --migrate-customers-from-files

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-u113439427_akhurath}"
DB_NAME="${DB_NAME:-u113439427_akhurath}"
SQL_FILE="${ROOT}/sql/schema.sql"
ENSURE_PHP="${ROOT}/scripts/ensure-database.php"

if [[ ! -f "$SQL_FILE" ]]; then
  echo "Missing ${SQL_FILE}" >&2
  exit 1
fi

if ! command -v mysql >/dev/null 2>&1; then
  echo "mysql client not found. On Hostinger use phpMyAdmin → Import → sql/schema.sql instead." >&2
  exit 1
fi

echo "Importing ${SQL_FILE} into ${DB_NAME} on ${DB_HOST} as ${DB_USER} ..."
if [[ "${MYSQL_PWD+x}" = "x" ]]; then
  # mysql reads password from MYSQL_PWD (works for empty password, e.g. XAMPP root).
  mysql -h"$DB_HOST" -u"$DB_USER" "$DB_NAME" <"$SQL_FILE"
else
  mysql -h"$DB_HOST" -u"$DB_USER" -p "$DB_NAME" <"$SQL_FILE"
fi
echo "Schema import finished."

if command -v php >/dev/null 2>&1 && [[ -f "${ROOT}/config/database.local.php" ]] && [[ -f "$ENSURE_PHP" ]]; then
  echo "Running PHP ensure step (patches + file customers → users) ..."
  php "$ENSURE_PHP" --migrate-customers-from-files
elif [[ ! -f "${ROOT}/config/database.local.php" ]]; then
  echo "Tip: add config/database.local.php (copy from config/database.local.example.php), then run:"
  echo "  php scripts/ensure-database.php --migrate-customers-from-files"
fi

echo "Done."
