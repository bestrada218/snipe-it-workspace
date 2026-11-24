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

## How a version reaches the servers

- Day-to-day work goes to the `develop` branch. Servers never install from it.
- When a version is ready, `develop` is merged into `main`, `VERSION` and this
  file are updated, and the commit is tagged (`v0.2`, for example).
- Servers follow `main`: `snipe-it-workspace update` (or the nightly
  automatic update, if it is on) installs only released versions.

---

## Unreleased (next: 0.1.1)

- The Snipe-IT Workspace version is shown next to the Snipe-IT version in
  Settings, and at the bottom of every page.
- Returns: the destinations read "<partner> warehouse" (the partner name set
  in Settings, for example Rippling) and "A clinic's storage"; choosing a
  clinic asks which one, from all the locations.
- *Changes by version* page: the version number in Settings and in the
  footer opens the list of changes of every version.

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
