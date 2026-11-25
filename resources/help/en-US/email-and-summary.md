# Email and daily summary

How to set up the email account the app sends everything with, and how to get a summary of what is pending every morning.

## What it is for

The app sends email for many things: alerts, equipment acceptances, password resets, backup warnings and the **daily summary**. It needs an email account to send them from.

That used to be set up in a file on the server. Now it is done in **Settings → Email** ([open](route:settings.mail.index)), with a guide for each service.

## Who can set it up

- **Superadmins** only.
- **Every time it is opened** it asks for the password and a two-factor code, because the page holds the mail account's password (see [Security](help:security)).
- The password is stored **encrypted** and never shown again.

## Setting up the mail server

1. In **Email service**, pick the one you use: Google Workspace or Gmail, Microsoft 365 or Outlook, SendGrid, Amazon SES, Mailgun or another. **Server and port fill themselves.**
2. Open **Step by step: where each value comes from**. For that service it shows where to create the password or key and what goes in each field.
3. Fill in **Username**, **Password** and **Sender email**. Each field explains what to enter.
4. Tick **Send email with these settings** and save.
5. In **Test sending**, type your address and press **Send test**. It should arrive within a few minutes; otherwise check spam.

### What goes in each field

| Field | What to enter |
| --- | --- |
| **Server (SMTP)** | The sending server address, e.g. smtp.gmail.com. Filled when you pick the service. |
| **Port** | 587 almost always. 465 only when the provider asks for it. |
| **Username** | Usually the full email address. On SendGrid it is the word `apikey`. |
| **Password** | The **app password** or the provider's key, rarely the normal password. |
| **Sender email** | The address messages come from. It must be the account's own or a verified one; otherwise messages are rejected or land in spam. |
| **Sender name** | The name recipients see, e.g. IT Inventory. |
| **Reply to** | Optional: where replies go. |

### Which service to choose

- **Google Workspace or Gmail**: easiest if the company already uses Google. Needs an app password.
- **Microsoft 365**: works, but Microsoft is retiring this method; if it gives an authentication error, use another service.
- **SendGrid, Amazon SES or Mailgun**: services built for apps to send email. Better inbox delivery, and no dependence on a person's mailbox.

> Turning off **Send email with these settings** makes the app use the server `.env` settings again, if any.

## The daily summary

One email a day with what needs attention:

- **Shipments** arriving today or late that still need confirming.
- **Mandatory replacements** overdue or due within 15 days.
- **Returns not moving** (see [Returns](help:returns#returns-not-moving)).
- **Backup problems**: no recent backup on the server, or the last cloud copy failed.

Each topic shows how many there are and the first ones, with direct links. **It is only sent when something is pending**: when everything is up to date, nothing arrives.

### Turning it on

On the same Email page, **Daily summary** section:

1. Tick **Send the daily summary**.
2. In **Who receives it**, type the addresses, separated by commas or one per line.
3. Choose the **sending time** (server time).
4. Save. **Send now** sends today's summary right away, to see how it arrives.

The page shows when the last summary went out or, when nothing was pending, that it was not sent that day.

## Backup warnings by email

Every day at 08:00 the app checks that each backup destination (the server and the cloud copies) has a backup less than a day old. If one fails, it emails a warning to the address set for backup notifications.
