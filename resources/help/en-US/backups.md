# Backups

When backups are made, where they are kept, how to restore them and how to send an encrypted copy to the cloud.

## Automatic backups

Every **6 hours** (01:30, 07:30, 13:30 and 19:30) a full backup is made:

- the **database** with all inventory, people, history, shipments, returns and approvals;
- the **uploaded files**: evidence, photos, documents and images.

Making a backup neither stops the app nor corrupts anything: the database is copied as a "snapshot" of a single instant, and whatever is being saved at that second lands in that backup or the next one. People can keep working meanwhile.

### How long they are kept

**Every backup from the last 7 days** is kept; then one per day for 16 more days, after which the oldest are deleted.

## Viewing, downloading and making backups

In [Settings → Backups](route:settings.backups.index) (administrators):

- The list shows each backup with its date and size. You can **download** or **delete** it.
- **Generate Backup** makes one right now, for example before a big change.
- **Upload Backup** lets you restore one downloaded earlier or brought from another server.

## Restoring a backup

1. In Backups, choose the backup and press **Restore**.
2. Confirm. **Restoring replaces all current data** with the backup's.

Before deleting anything, the app **checks the whole backup**: that it has the database, that it is not damaged and that the encryption password is right. If something is wrong, the current data is left untouched. It also **makes a backup of the current data right before restoring**, in case you need to go back.

> After restoring, everyone is signed out. Sign in again with a user that exists in the restored backup.

## Cloud copies

Besides being kept on the server, each backup can be **uploaded encrypted** to **Amazon S3** (or compatible services such as Cloudflare R2, Backblaze B2, Wasabi or MinIO) or to **Google Drive**. If the server is damaged or lost, the data is still safe.

It is set up in **Backups → Cloud copies** ([open](route:settings.cloud_backups.index)):

- Only **superadmins** can open it, and it always asks for the **password and a two-factor code** (see [Security](help:security)).
- Each destination has a **step-by-step guide** on the page, and every field explains where it comes from.
- The **encryption password** locks each file before it is uploaded. Keep it outside the server: without it, cloud backups cannot be opened.
- **Test connection** confirms the keys and permissions work.
- The page shows **how the last copy went** for each destination.

## Backups in the Documents folder

On the Ubuntu computer where it is installed, backups are also copied to **Documents → Snipe-IT Workspace backups**, easy to find from the file manager:

- They are copied half an hour after each backup (02:00, 08:00, 14:00 and 20:00).
- Only **finished** backups are copied, never one being written.
- To copy them right away: `snipe-it-workspace sync-backups` in the terminal.
