# Security Policy

## Supported versions

Only the latest version on the `main` branch is supported. Keep installations current with `snipe-it-workspace update` (or `snipe-it-workspace auto-update on`).

## Reporting a vulnerability

**Do not open a public issue for security problems.**

Report it privately through GitHub: open the [Security tab of this repository](https://github.com/bestrada218/snipe-it-workspace/security) and choose **Report a vulnerability**. Include what is affected, how to reproduce it and its possible impact. You will get an answer as soon as possible, and the fix will be published before the details are made public.

If the problem is also present in the original Snipe-IT (code this project did not change), please report it to the Snipe-IT team as well, following [their security policy](https://github.com/grokability/snipe-it/security/policy).

## Good practices for installations

- Keep a copy of the `.env` file outside the server, and never share it: it holds the encryption key and database passwords.
- Set `BACKUP_ARCHIVE_PASSWORD` before sending backups off the server; without it, cloud copies are refused.
- Put the application behind HTTPS if it is reachable from outside the local network.
