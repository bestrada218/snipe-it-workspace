# Labels

How to print equipment labels and what they show.

## What each label shows

The label is designed so the **tag** can be read at a glance:

1. **Asset tag**, first and in bold.
2. **Model**.
3. **Company** (the site name when the asset has no company).
4. **Serial**, last.

The tag's **barcode or QR code** sits beside it without covering the text.

## Printing

- **Several at once**: in the asset list, select the ones you want and choose **Generate labels** in the bulk actions.
- **Just one**: on the asset page, the label button (the barcode icon) in the action bar.

A print-ready view opens with the configured label format. Use the label printer and check in the preview that the size matches the roll.

<!-- admin-only -->

## Setting up the format

In **Settings → Labels** an administrator sets:

- **New label engine**: turn it on and save before changing the rest. It lets you pick a template for the label size you use.
- **Template**: size and layout.
- **Fields**: which data appear and in what order. The tag is always printed first; the other fields (model, company, serial…) are ordered in the list.
- The **code type** (QR or barcode) and whether the logo is printed.

> After changing the template, print a test label first.

<!-- end-admin-only -->
