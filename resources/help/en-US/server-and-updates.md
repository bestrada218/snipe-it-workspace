# Server, versions and updates

For whoever runs the computer it is installed on: installing, updating, how versions are numbered and useful commands.

## Versions

Snipe-IT Workspace has **its own version number**, separate from Snipe-IT's, the original software it is based on:

| Change | Example |
| --- | --- |
| Small fixes and adjustments | 0.1 → 0.1.1 → 0.1.2 |
| A new feature or a noticeable change | 0.1.x → 0.2 |
| Formal, stable release | 0.x → 1.0 |

Where to see it:

- at the bottom of every page;
- in **Settings → General → System information**, next to the Snipe-IT version;
- in the terminal, with `snipe-it-workspace status`.

What each version brought is in [Changes by version](route:changelog).

## How versions reach the server

- Day-to-day work happens on a development branch. **The server never updates from it.**
- When a version is ready it is released: the changelog is updated, the number goes up and it gets its tag (e.g. `v0.2`).
- The server **only installs released versions**.

## Updating

In the server terminal:

```
snipe-it-workspace update
```

It only does something when there is a new version. Before updating it **makes a backup**; if the backup fails, it does not update. Then it downloads the version, restarts the app (one or two minutes offline) and checks that it answers. It also tells you which version you moved from and to.

To update on its own every night at 3:30: `snipe-it-workspace auto-update on` (and `off` to stop it).

## Useful commands

| Command | What it does |
| --- | --- |
| `snipe-it-workspace status` | Whether it is running and which versions are installed. |
| `snipe-it-workspace update` | Installs the latest released version (with a backup first). |
| `snipe-it-workspace backup` | Makes a backup now and copies it to Documents. |
| `snipe-it-workspace backups` | Lists the backups on the server. |
| `snipe-it-workspace sync-backups` | Copies finished backups to Documents now. |
| `snipe-it-workspace logs` | Follows the application log. |
| `snipe-it-workspace restart` / `start` / `stop` | Restarts, starts or stops the app. |
| `snipe-it-workspace auto-update on` / `off` | Nightly automatic updates. |

## Installing on a new computer

On Ubuntu 22.04 or newer, as your normal user (not with sudo):

```
curl -fsSL https://raw.githubusercontent.com/bestrada218/snipe-it-workspace/main/scripts/workspace-setup.sh | bash
```

The installer sets up everything, starts the app and asks whether you want automatic updates and whether the machine should never sleep. When it finishes, open the address it shows and create the first administrator.
