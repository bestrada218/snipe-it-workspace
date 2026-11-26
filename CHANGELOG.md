# Changelog

Versions of **Snipe-IT Workspace**. They are numbered on their own and are not
the same as the version of [Snipe-IT](https://github.com/grokability/snipe-it)
each one is based on, which is listed separately for each release.

The installed version is shown in **Settings → General** and at the bottom
of every page, and by `snipe-it-workspace status`.

## How versions are numbered

| Change | Example | Next version |
| --- | --- | --- |
| Small fixes and adjustments | a label, a copy, a bug | `0.1` → `0.1.1` → `0.1.2` |
| A new feature or a noticeable change in how something works | a new module | `0.1.x` → `0.2` |
| A formal, stable release | | `0.x` → `1.0` |

The current number is in the [`VERSION`](VERSION) file.

Spanish notes are in [the localized changelog](resources/lang/es-CO/changelog.md).
Use the version number alone for its release headings. Release order, dates and
publication status come from this file.

## How a version reaches the servers

- Day-to-day work goes to the `develop` branch. Servers never install from it.
- When a version is ready, `develop` is merged into `main`, `VERSION` and this
  file are updated, and the commit is tagged (`v0.2`, for example).
- Servers follow `main`: `snipe-it-workspace update` (or the nightly
  automatic update, if it is on) installs only released versions.

---

## Unreleased (next: 0.2.1)

### Maintenance

- Resolve the high-severity JavaScript dependency findings from 0.2 and keep
  the hot-reload development server restricted to the local machine.
- Make the test for the upcoming departures filter independent of randomly
  generated employment end dates.

## 0.2 — 2026-11-25

Based on **Snipe-IT v8.8.3**.

### Warehouses and inventory

- The Warehouses view includes stock charts, search, CSV export and filters
  for internal and external warehouses. Internal warehouses can also be
  filtered by state.
- Returned accessories keep a warehouse location for each available unit.
  The accessory page shows stock by warehouse, and checkout can select a
  source.
- Direct asset check-in asks which internal warehouse will receive the
  asset. Shipments to an external provider warehouse start in Returns.
- Asset statuses distinguish equipment that's ready to deploy, under review,
  awaiting setup, in repair, damaged or retired. Returns update readiness
  and physical location independently.

### Returns

- Each returned item goes to an internal warehouse or to a warehouse
  managed by an external provider. Settings supplies the provider name.
- Record each shipment step and its date, and undo eligible steps.
  The return ticket, owners, carrier and tracking number can be edited.
- The warehouse picker shows the location hierarchy and allows selection
  at the deepest eligible level.
- Checked-in assets stay unassigned. The asset page shows their physical
  warehouse and links to the location and return until the next checkout.
- Returns apply company access rules to lists, counts, direct links, edits
  and owner selection. Returns that include accessories also require
  accessory permissions.

### Backups and recovery

- Set up encrypted Amazon S3 or Google Drive copies from *Cloud copies*
  (Settings → Backups). Each service has a setup guide and field
  explanations. You can test the connection and check the last copy result.
- The daily backup check also monitors cloud destinations configured in the
  app and reports missing or outdated backups.
- Cloud copies verify AES-256 encryption and compare SHA-256 checksums after
  upload. Downloaded archives are validated before appearing in the local
  list.
- Restores validate attachments and the database dump. The web interface
  allows one restore at a time.

### Access and data protection

- *Cloud copies* and *Email* are restricted to superadmins and ask for a
  password and a two-factor code on each visit, including reloads.
  Confirmation applies only to the section opened.
- Web backup downloads require fresh two-factor confirmation. The legacy
  download API is disabled by default.
- New installations require superadmins to enroll in two-factor
  authentication and encrypt session data. The database encrypts both
  existing and new two-factor secrets.
- The Activity Report records completed web sign-ins.
- Cloud and SMTP credentials are excluded from validation session data and
  connection test diagnostics. SMTP configured in the app requires TLS with
  certificate validation.

### Email, alerts and navigation

- Set up outgoing mail in Settings → Email with guides for Google,
  Microsoft 365, SendGrid, Amazon SES and Mailgun, field explanations and
  a test email.
- A daily email at the chosen time reports pending shipments, upcoming
  replacements, stalled returns and backup problems. It sends only when
  there are pending items and includes counts and a link to Workspace.
- Stalled returns appear in *Needs attention* and in their own tab after
  a configurable period without progress (7 days by default).
- The bilingual *Help center* has 14 sections with indexes, full-text search
  and links to the relevant screens.
- Settings and the footer show both version numbers. The Workspace version
  opens *Changes by version*.
- Sidebar counters are cached per user and refresh when an asset, shipment,
  return or setting changes.
- Select2 dropdowns load the available translation for regional languages
  such as Spanish (Colombia).

### Installation and updates

- New installations bind to the loopback interface, generate a backup
  password and use secure cookies when configured for HTTPS. Setup emails
  omit login credentials.
- Production debug output is disabled. Docker builds exclude local secrets,
  backups, uploads and runtime data. Apache confines access to the web root,
  blocks executable uploads and omits URLs from access logs.
- The public storage proxy is opt-in, rejects overlapping S3 backup and
  private storage prefixes, and isolates active content in the browser.
- Updates pull the current base image and can rebuild with fresh packages.
  Incomplete updates retry until Workspace passes its health check.
- Ubuntu container builds require the November 2026 PHP security patch
  and skip the unused PEAR installer.
- CI runs the automated tests and audits PHP and JavaScript dependencies
  before release.
- CSV and Excel dependencies include the security fixes from League CSV
  9.28.1 and SheetJS 0.20.3.

## 0.1 — 2026-11-24

Based on **Snipe-IT v8.8.3**. First version of Snipe-IT Workspace.

### Asset tags
- `XX-KKK-PP[-TTT]` tags built from location, type and, for computers,
  operating system suffix; the tag field cannot be typed by hand.
- One global `KKK` sequence across the whole inventory; two people saving
  at the same time never get the same number.
- Tag types managed in Settings; renaming a code updates every tag that
  uses it, with an entry in each item's history.
- The asset tag comes first in titles, lists, checkouts, search and labels.

### Assignments
- One tagged unit per checkout.
- Duplicate assignments require a reason and a ticket, and for chosen
  categories an approver and evidence, in every checkout path.
- Duplicate assignments page.

### Shipments to remote people
- Shipments with carrier, tracking, estimated delivery and purchase approval.
- Progress line, due and late tracking, and a past shipments tab.

### Returns
- Returns for terminations and changes; the person's items load on their own.
- Each item goes back to the partner warehouse or is stored at a storage
  location; shipping and partner setup steps; notes copied to each item's
  history; ticket owner and return owner.
- Storage locations, kept apart from locations where assets are in use.

### Other features
- Mandatory replacement dates with alerts.
- Damage reports for damaged statuses.
- Labels with tag, model, company and serial, tag first.
- Inventory modules that can be switched off and renamed; permission groups
  limited to a location; configurable activity report.

### Workspace
- Redesigned interface, *Needs attention* list on the dashboard and global
  search. English and Spanish.

### Backups and installation
- Backups every 6 hours, also copied to a folder in Documents.
- Optional encrypted copies to Amazon S3, S3-compatible storage or Google
  Drive; restores check the whole backup before touching any data.
- One-command installer for Ubuntu and the `snipe-it-workspace` command.
