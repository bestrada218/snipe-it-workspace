# Snipe-IT Workspace

**Snipe-IT Workspace** is a ready-to-run edition of [Snipe-IT](https://github.com/grokability/snipe-it), the open-source IT asset management system, with a redesigned workspace and the workflows an IT team needs day to day: a structured asset-tag system, shipments to remote people, approvals for duplicate assignments, damage reports, mandatory replacement dates and encrypted off-site backups.

> **Install it from this repository.** The official Snipe-IT downloads, Docker images and installation guides install the original Snipe-IT, without any of the features below.

- [Install](#install)
- [Update](#update)
- [Everyday commands](#everyday-commands)
- [What Snipe-IT Workspace adds](#what-snipe-it-workspace-adds)
- [Backups](#backups)
- [Moving to a new server](#moving-to-a-new-server)
- [Development](#development)
- [Relationship with Snipe-IT and license](#relationship-with-snipe-it-and-license)

---

## Install

**You need:** a machine with Ubuntu 22.04 or newer (Desktop or Server), 2 CPU cores, 4 GB of RAM, 20 GB of free disk and internet access. Docker is installed for you.

Open a terminal on that machine and run, **as your normal user (not with sudo)**:

```bash
curl -fsSL https://raw.githubusercontent.com/bestrada218/snipe-it-workspace/main/scripts/workspace-setup.sh | bash
```

The installer asks for your password when it needs it and then:

1. installs Git and Docker;
2. downloads Snipe-IT Workspace into `/opt/snipe-it-workspace`;
3. creates a configuration with its own passwords and encryption key for this server;
4. builds and starts the application (the first time takes a few minutes);
5. adds the `snipe-it-workspace` command;
6. asks whether to update automatically every night and whether to keep the machine from sleeping.

When it finishes, open the address it shows (by default `http://<this-machine's-IP>:8000`) and complete the setup wizard to create the first administrator.

> **Keep a copy of `/opt/snipe-it-workspace/.env` somewhere safe, outside the server** (a password manager, for example). It holds the encryption key: without it, backups cannot be restored on another machine.

The application starts again on its own after a reboot.

<details>
<summary>Installer options</summary>

Set any of these before `bash` to skip the questions or change the defaults:

| Variable | Default | Meaning |
| --- | --- | --- |
| `WORKSPACE_PORT` | `8000` | Web port |
| `WORKSPACE_APP_URL` | `http://<IP>:8000` | Address people will use |
| `WORKSPACE_AUTO_UPDATE` | asked | `yes` / `no`: nightly automatic updates |
| `WORKSPACE_KEEP_AWAKE` | asked | `yes` / `no`: disable sleep and suspend |
| `WORKSPACE_DIR` | `/opt/snipe-it-workspace` | Install folder |
| `WORKSPACE_BRANCH` | `main` | Branch to follow |

```bash
curl -fsSL https://raw.githubusercontent.com/bestrada218/snipe-it-workspace/main/scripts/workspace-setup.sh | WORKSPACE_PORT=80 WORKSPACE_AUTO_UPDATE=no bash
```
</details>

## Update

```bash
snipe-it-workspace update
```

It only does something when there is a new version on GitHub. It then:

1. backs up the current data (it does not update if the backup fails);
2. downloads the new version and lists what changed;
3. rebuilds and restarts; database changes are applied on start;
4. checks that the application answers, and tells you how to roll back if it does not.

The application is unavailable for one or two minutes. To update automatically every night at 3:30 instead:

```bash
snipe-it-workspace auto-update on
```

## Everyday commands

| Command | What it does |
| --- | --- |
| `snipe-it-workspace status` | Whether it is running and which version is installed |
| `snipe-it-workspace update` | Install the latest version (backs up first) |
| `snipe-it-workspace backup` | Make a backup now |
| `snipe-it-workspace backups` | List the backups kept on this server |
| `snipe-it-workspace logs` | Follow the application log |
| `snipe-it-workspace restart` / `start` / `stop` | Control the application |
| `snipe-it-workspace auto-update on` / `off` | Nightly automatic updates |

---

## What Snipe-IT Workspace adds

Everything in Snipe-IT is still there. On top of it:

### Asset tags: `XX-KKK-PP[-TTT]`

- **Tag builder** in every create form (assets, accessories, consumables, components, licenses): pick the location (`XX`), the type (`PP`) and, for computers, the operating system suffix (`TTT`). The tag field cannot be typed by hand.
- **One global sequence (`KKK`)** across the whole inventory: a number identifies a single item, even after it is deleted. The record and its number are saved together, so two people saving at the same time never get the same number; whoever saves second gets the next free one automatically.
- **Tag types managed in Settings** (Settings → Asset Tags → Tag types): add types, rename them, choose which ones take a suffix. Changing a code (for example `LP` → `LT`) updates every tag that uses it, in every module, with an entry in each item's history.
- The **asset tag always comes first**: in titles, lists, checkouts and search results.
- A tag guide inside the application, and `php artisan snipeit:asset-tag-duplicates` to report legacy duplicates.

### Assignments

- **One tagged unit per checkout**: no quantity fields.
- **Duplicate assignments**: giving a person a second item of a category they already hold always requires a reason and a ticket number. For the categories you choose (for example laptops and desktops) it also requires an approver and evidence (PDF or image). The rule applies everywhere: individual and bulk checkouts, kits, transfers between people, request fulfilment and the API.
- A **Duplicate assignments** page lists people holding more than one unit of a category.

### Shipments to remote people

- Register a shipment instead of a direct checkout: recipient, purchase platform, carrier, tracking number, estimated delivery and purchase approval with evidence.
- Progress line (registered → in transit → received → assigned), due and late tracking, rescheduling and cancellation.
- The asset is assigned when delivery is confirmed.

### Mandatory replacement

- Replacement deadline per asset, with alerts by window (overdue, today or tomorrow, 15 days, 1 month, 3 months) on the dashboard and on their own page.

### Damage reports

- Moving an asset to a "damaged" status (chosen in Settings) requires documenting what is damaged, whether it can be repaired and whether a repair was attempted. Those statuses cannot be assigned. The report is kept in the asset history.

### Workspace

- Redesigned interface: grouped side menu with counters, status chips above the asset list, one row action plus a "more" menu, save button always in reach on long forms, clearer page titles.
- **Needs attention** on the dashboard: overdue replacements, shipments to confirm, overdue check-ins and audits, overdue maintenance, people leaving with items, expiring licenses, pending requests and acceptances, all in one list with an action for each.
- **Global search** by tag, serial, person or model, limited to the modules each person can open.
- English and Spanish interface. Data people type is kept in English.

### Inventory and permissions

- Inventory modules can be switched off and renamed; location-based modules (for example *Fixed assets*) get their own menu entry.
- Permission groups can be **limited to a location**.
- Configurable activity report columns and readable change history.

### Backups

- Automatic backups every 6 hours, with clear reporting of where each copy landed.
- Optional **encrypted copies off the server**: Amazon S3 and S3-compatible storage (Cloudflare R2, Backblaze B2, Wasabi, MinIO, Google Cloud Storage) or **Google Drive**.
- Restores check the whole backup (database dump, integrity and password) **before** touching the current data.

---

## Backups

Backups run every 6 hours (01:30, 07:30, 13:30 and 19:30) and are kept on the server; half an hour later they are also copied to a *Snipe-IT Workspace backups* folder in your Documents. `snipe-it-workspace backups` lists them, and they can be downloaded or restored from **Settings → Backups**.

To also send an **encrypted copy off the server**, add these lines to `/opt/snipe-it-workspace/.env` and run `snipe-it-workspace restart`. All the options are documented in [`.env.example`](.env.example).

```ini
# Required for any off-site copy. Keep this password outside the server too.
BACKUP_ARCHIVE_PASSWORD=a-long-random-password

# Amazon S3 or S3-compatible storage
BACKUP_CLOUD_DISKS=backup_s3
BACKUP_S3_KEY=...
BACKUP_S3_SECRET=...
BACKUP_S3_REGION=us-east-1
BACKUP_S3_BUCKET=my-backups
#BACKUP_S3_ENDPOINT=https://...   # only for R2, B2, Wasabi, MinIO, GCS

# or Google Drive (both can be used: BACKUP_CLOUD_DISKS=backup_s3,backup_google)
#BACKUP_CLOUD_DISKS=backup_google
#BACKUP_GOOGLE_SERVICE_ACCOUNT_JSON=/path/to/service-account.json
#BACKUP_GOOGLE_FOLDER_ID=...
#BACKUP_GOOGLE_SHARED_DRIVE=true
```

Without `BACKUP_ARCHIVE_PASSWORD`, nothing leaves the server: the local copy is still made and the backup reports why the cloud copy was skipped. To be told by email when a backup fails or is more than a day old, set `MAIL_BACKUP_NOTIFICATION_DRIVER=mail` and `MAIL_BACKUP_NOTIFICATION_ADDRESS`.

## Moving to a new server

1. Install Snipe-IT Workspace on the new machine with the command in [Install](#install).
2. Copy the old `.env` over the new `/opt/snipe-it-workspace/.env` (it must have the same `APP_KEY` and `BACKUP_ARCHIVE_PASSWORD`) and run `snipe-it-workspace restart`.
3. Bring a backup back from the cloud, if that is where it is:
   ```bash
   cd /opt/snipe-it-workspace
   docker compose -f compose.workspace.yml exec --user docker app php artisan snipeit:backup-pull backup_s3 --latest
   ```
   or copy the backup file to the new server and upload it in **Settings → Backups**.
4. Restore it from **Settings → Backups**.

---

## Development

- The application is Laravel 12 on PHP 8.3 with MariaDB; front-end assets are built with Laravel Mix (`npm run dev`, `npm run prod`). The compiled CSS is committed.
- Tests: `php vendor/bin/phpunit` (run each `tests/` folder separately on machines with little memory).
- Format PHP with `vendor/bin/pint --dirty`.
- `compose.workspace.yml` builds the image from this repository; the stock `docker-compose.yml` uses the official Snipe-IT image and is kept only to ease merging with upstream.

## Relationship with Snipe-IT and license

Snipe-IT Workspace is a modified version of [Snipe-IT](https://github.com/grokability/snipe-it), created by [Grokability, Inc.](https://grokability.com) It is not affiliated with or endorsed by Grokability. For anything that works the same as in Snipe-IT, the [Snipe-IT documentation](https://snipe-it.readme.io/docs) applies.

Like Snipe-IT, this project is licensed under the [GNU Affero General Public License v3.0](LICENSE). If you run a modified version for other people over a network, the license requires you to offer them its source code.
