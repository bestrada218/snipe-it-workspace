#!/usr/bin/env bash
# First install of Snipe-IT Workspace on Ubuntu with Docker.
# Run it from the repository folder:  scripts/workspace-install.sh
set -euo pipefail
cd "$(dirname "$0")/.."

# Plain docker when this user may use it, sudo otherwise (right after the
# first install the user is not in the docker group yet).
if docker info >/dev/null 2>&1; then
    COMPOSE=(docker compose -f compose.workspace.yml)
else
    COMPOSE=(sudo docker compose -f compose.workspace.yml)
fi

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker is not installed. Use scripts/workspace-setup.sh, which installs it." >&2
    exit 1
fi

if [ -f .env ]; then
    echo ".env already exists: keeping it."
else
    cp .env.docker .env
    # Unique passwords for this server (never the example ones).
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$(openssl rand -hex 20)|" .env
    sed -i "s|^MYSQL_ROOT_PASSWORD=.*|MYSQL_ROOT_PASSWORD=$(openssl rand -hex 20)|" .env
    # Consistent database copy without locking people out during backups.
    sed -i "s|^DB_DUMP_SINGLE_TRANSACTION=.*|DB_DUMP_SINGLE_TRANSACTION=true|" .env

    timezone="$(timedatectl show -p Timezone --value 2>/dev/null || echo UTC)"
    sed -i "s|^APP_TIMEZONE=.*|APP_TIMEZONE='${timezone}'|" .env

    port="${WORKSPACE_PORT:-8000}"
    sed -i "s|^APP_PORT=.*|APP_PORT=${port}|" .env
    default_url="http://$(hostname -I | awk '{print $1}'):${port}"
    app_url="${WORKSPACE_APP_URL:-}"
    if [ -z "$app_url" ]; then
        if [ -r /dev/tty ]; then
            read -rp "Address people will open in the browser [${default_url}]: " app_url </dev/tty || true
        fi
    fi
    sed -i "s|^APP_URL=.*|APP_URL=${app_url:-$default_url}|" .env
fi

echo "Building the Snipe-IT Workspace image (a few minutes the first time)..."
"${COMPOSE[@]}" build app

# The example .env ships with a public key: replace it with a new one.
if grep -q '^APP_KEY=base64:3ilviXqB9u6DX1NRcyWGJ+sjySF+H18CPDGb3+IVwMQ=' .env || grep -q '^APP_KEY=$' .env; then
    key="$("${COMPOSE[@]}" run --rm --no-deps app php artisan key:generate --show | tail -n 1 | tr -d '\r')"
    sed -i "s|^APP_KEY=.*|APP_KEY=${key}|" .env
fi

"${COMPOSE[@]}" up -d

echo
echo "Snipe-IT Workspace is starting. Open $(grep '^APP_URL=' .env | cut -d= -f2-) in a minute to finish the setup."
echo "IMPORTANT: keep a copy of the .env file (APP_KEY and passwords) outside this machine."
