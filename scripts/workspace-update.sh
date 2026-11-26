#!/usr/bin/env bash
# Brings this server to the latest version on GitHub.
#   scripts/workspace-update.sh          update only when there is something new
#   scripts/workspace-update.sh --force  rebuild even if nothing changed
#
# 1. backup of the current data (kept on the server)
# 2. download the new code
# 3. rebuild the image and restart; database migrations run on start
# 4. check that the application answers
set -euo pipefail
cd "$(dirname "$0")/.."

# Plain docker when this user may use it, sudo otherwise (right after the
# first install the user is not in the docker group yet).
if docker info >/dev/null 2>&1; then
    COMPOSE=(docker compose -f compose.workspace.yml)
else
    COMPOSE=(sudo docker compose -f compose.workspace.yml)
fi
BRANCH="${WORKSPACE_BRANCH:-main}"
pending_update="$(git rev-parse --git-path workspace-update-pending)"

chmod 600 .env

git fetch --quiet origin "$BRANCH"
if [ "$(git rev-parse HEAD)" = "$(git rev-parse "origin/$BRANCH")" ] && [ "${1:-}" != "--force" ] && [ ! -f "$pending_update" ]; then
    echo "Already up to date ($(git rev-parse --short HEAD))."
    exit 0
fi

if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
    echo "There are local changes to the code on this server; not updating." >&2
    git status --short --untracked-files=no >&2
    exit 1
fi

echo "1/4 Backing up the current data..."
"${COMPOSE[@]}" exec -T --user docker app php artisan snipeit:backup --local-only --filename="pre-update-$(date +%Y-%m-%d-%H-%M-%S)"

echo "2/4 Downloading the new version..."
: >"$pending_update"
previous="$(git rev-parse --short HEAD)"
previous_version="$(cat VERSION 2>/dev/null || echo unknown)"
git merge --ff-only --quiet "origin/$BRANCH"
echo "    Snipe-IT Workspace ${previous_version} -> $(cat VERSION 2>/dev/null || echo unknown)  (${previous} -> $(git rev-parse --short HEAD))"
echo "    What changed: CHANGELOG.md"
git log --oneline "${previous}..HEAD" | sed 's/^/    /'

echo "3/4 Rebuilding and restarting..."
build_options=(--pull)
if [ "${1:-}" = "--force" ]; then
    build_options+=(--no-cache)
fi
"${COMPOSE[@]}" build "${build_options[@]}" app
"${COMPOSE[@]}" up -d

echo "4/4 Checking the application..."
port="$(grep '^APP_PORT=' .env | cut -d= -f2 | tr -d "'\"")"
for _ in $(seq 1 60); do
    if curl -fsS "http://localhost:${port:-8000}/health" 2>/dev/null | grep -Eq '"status"[[:space:]]*:[[:space:]]*"ok"'; then
        rm -f "$pending_update"
        echo "Updated and running."
        # Copy the backups (the pre-update one included) to Documents.
        scripts/snipe-it-workspace sync-backups || true
        exit 0
    fi
    sleep 5
done

echo "The application did not answer after 5 minutes. Check: docker compose -f compose.workspace.yml logs app" >&2
echo "To go back: git checkout ${previous} && docker compose -f compose.workspace.yml up -d --build" >&2
exit 1
