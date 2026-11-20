#!/usr/bin/env bash
# Snipe-IT Workspace: one-command installer for Ubuntu.
#
#   curl -fsSL https://raw.githubusercontent.com/bestrada218/snipe-it-workspace/main/scripts/workspace-setup.sh | bash
#
# Run it as your normal user (it asks for your password when it needs sudo).
# It installs Docker and Git, downloads Snipe-IT Workspace into
# /opt/snipe-it-workspace, starts it and adds the `snipe-it-workspace` command.
#
# Optional settings (environment variables):
#   WORKSPACE_REPO         repository to install from   (default: the public GitHub repo)
#   WORKSPACE_BRANCH       branch to follow              (default: main)
#   WORKSPACE_DIR          install folder                (default: /opt/snipe-it-workspace)
#   WORKSPACE_PORT         web port                      (default: 8000)
#   WORKSPACE_APP_URL      address people will use       (asked if not set)
#   WORKSPACE_AUTO_UPDATE  yes|no: nightly updates       (asked if not set)
#   WORKSPACE_KEEP_AWAKE   yes|no: disable sleep         (asked if not set)
set -euo pipefail

REPO="${WORKSPACE_REPO:-https://github.com/bestrada218/snipe-it-workspace.git}"
BRANCH="${WORKSPACE_BRANCH:-main}"
DIR="${WORKSPACE_DIR:-/opt/snipe-it-workspace}"

say() { printf '\n\033[1m==> %s\033[0m\n' "$*"; }
# Questions read the keyboard even when the script arrives through a pipe.
ask() {
    local reply=""
    if [ -r /dev/tty ]; then read -rp "$1" reply </dev/tty || true; fi
    printf '%s' "$reply"
}

if [ "$(id -u)" -eq 0 ]; then
    echo "Run this as your normal user, without sudo. It will ask for your password when needed." >&2
    exit 1
fi

say "1/5 Installing system packages (git, curl, openssl, cron)"
sudo apt-get update -qq
sudo apt-get install -y -qq ca-certificates curl git openssl cron >/dev/null

say "2/5 Installing Docker"
if docker info >/dev/null 2>&1 || sudo docker info >/dev/null 2>&1; then
    echo "Docker is already installed."
else
    curl -fsSL https://get.docker.com | sudo sh
fi
if ! docker info >/dev/null 2>&1 && ! id -nG "$USER" | grep -qw docker; then
    sudo usermod -aG docker "$USER"
fi

say "3/5 Downloading Snipe-IT Workspace into ${DIR}"
if [ -d "$DIR/.git" ]; then
    echo "Already downloaded: keeping ${DIR}."
else
    sudo mkdir -p "$DIR"
    sudo chown "$USER":"$(id -gn)" "$DIR"
    git clone --quiet --branch "$BRANCH" "$REPO" "$DIR"
fi

say "4/5 Installing and starting the application"
cd "$DIR"
WORKSPACE_BRANCH="$BRANCH" scripts/workspace-install.sh

say "5/5 Adding the snipe-it-workspace command"
sudo ln -sf "$DIR/scripts/snipe-it-workspace" /usr/local/bin/snipe-it-workspace
echo "Done: run 'snipe-it-workspace' to see what it can do."

port="$(grep '^APP_PORT=' .env | cut -d= -f2 | tr -d "'\"")"
if command -v ufw >/dev/null 2>&1 && sudo ufw status 2>/dev/null | grep -q "Status: active"; then
    sudo ufw allow "${port:-8000}/tcp" >/dev/null && echo "Firewall: port ${port:-8000} opened."
fi

auto_update="${WORKSPACE_AUTO_UPDATE:-}"
[ -n "$auto_update" ] || auto_update="$(ask 'Update automatically every night at 3:30 when there is a new version? [y/N] ')"
case "$auto_update" in
    y|Y|yes) "$DIR/scripts/snipe-it-workspace" auto-update on ;;
esac

keep_awake="${WORKSPACE_KEEP_AWAKE:-}"
[ -n "$keep_awake" ] || keep_awake="$(ask 'This machine is a server: stop it from sleeping or suspending? [Y/n] ')"
case "$keep_awake" in
    n|N|no) ;;
    *) sudo systemctl mask sleep.target suspend.target hibernate.target hybrid-sleep.target >/dev/null 2>&1 \
           && echo "Sleep and suspend disabled." ;;
esac

echo
echo "Snipe-IT Workspace is installed. Open $(grep '^APP_URL=' .env | cut -d= -f2-) to finish the setup."
echo "Keep a copy of ${DIR}/.env outside this machine: without its APP_KEY backups cannot be restored."
if ! docker info >/dev/null 2>&1; then
    echo "Log out and back in once so your user can run Docker without sudo."
fi
