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
    port="${WORKSPACE_PORT:-8000}"
    if [[ ! "$port" =~ ^[1-9][0-9]{0,4}$ ]] || (( port > 65535 )); then
        echo "WORKSPACE_PORT must be between 1 and 65535." >&2
        exit 1
    fi
    default_url="http://localhost:${port}"
    app_url="${WORKSPACE_APP_URL:-}"
    if [ -z "$app_url" ] && [ -r /dev/tty ]; then
        read -rp "Browser address [${default_url}]. Use HTTPS with a reverse proxy for network access: " app_url </dev/tty || true
    fi
    app_url="${app_url:-$default_url}"
    secure_cookies=false
    if [[ "$app_url" =~ ^https://[a-zA-Z0-9.-]+(:[0-9]+)?(/[a-zA-Z0-9._/-]*)?$ ]]; then
        secure_cookies=true
    elif [ "$app_url" != "$default_url" ] && [ "$app_url" != "http://127.0.0.1:${port}" ]; then
        echo "Use ${default_url} or an HTTPS address served by a reverse proxy." >&2
        exit 1
    fi

    (umask 077; cp .env.docker .env)
    # Unique passwords for this server (never the example ones).
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$(openssl rand -hex 20)|" .env
    sed -i "s|^MYSQL_ROOT_PASSWORD=.*|MYSQL_ROOT_PASSWORD=$(openssl rand -hex 20)|" .env
    # Consistent database copy without locking people out during backups.
    sed -i "s|^DB_DUMP_SINGLE_TRANSACTION=.*|DB_DUMP_SINGLE_TRANSACTION=true|" .env

    timezone="$(timedatectl show -p Timezone --value 2>/dev/null || echo UTC)"
    sed -i "s|^APP_TIMEZONE=.*|APP_TIMEZONE='${timezone}'|" .env

    sed -i "s|^APP_PORT=.*|APP_PORT=${port}|" .env
    sed -i "s|^APP_URL=.*|APP_URL=${app_url}|" .env
    sed -i "s|^SECURE_COOKIES=.*|SECURE_COOKIES=${secure_cookies}|" .env
    sed -i 's|^ENCRYPT=.*|ENCRYPT=true|' .env
    sed -i "s|^ENABLE_CSP=.*|ENABLE_CSP=true|" .env
    sed -i "s|^ENABLE_HSTS=.*|ENABLE_HSTS=${secure_cookies}|" .env
    printf '\nAPP_BIND_ADDRESS=127.0.0.1\nBACKUP_ARCHIVE_PASSWORD=%s\n' "$(openssl rand -hex 32)" >> .env
fi

chmod 600 .env

echo "Building the Snipe-IT Workspace image (a few minutes the first time)..."
"${COMPOSE[@]}" build --pull app

# The example .env ships with a public key: replace it with a new one.
if grep -q '^APP_KEY=base64:3ilviXqB9u6DX1NRcyWGJ+sjySF+H18CPDGb3+IVwMQ=' .env || grep -q '^APP_KEY=$' .env; then
    key="$("${COMPOSE[@]}" run --rm --no-deps app php artisan key:generate --show | tail -n 1 | tr -d '\r')"
    sed -i "s|^APP_KEY=.*|APP_KEY=${key}|" .env
fi

"${COMPOSE[@]}" up -d

echo
echo "Snipe-IT Workspace is starting. Open $(grep '^APP_URL=' .env | cut -d= -f2-) in a minute to finish the setup."
echo "IMPORTANT: keep a copy of the .env file (APP_KEY and passwords) outside this machine."
