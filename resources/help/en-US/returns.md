# Returns

How to record what a person hands back, when they leave or when equipment is exchanged, and follow each item until it is stored.

## When to use it

A return groups **everything a person hands back**, for one of two reasons:

- **Termination**: the person leaves the company.
- **Change**: one piece of equipment is exchanged for another.

Each returned item goes to one of two places:

- **Rippling warehouse** (the hardware partner): shipped in boxes and received by Rippling.
- **A clinic's storage**: it stays stored at one of our clinics.

> The name "Rippling" and its warehouse are set in Settings → General → Returns. If another name shows there, read it instead of Rippling.

## Creating a return

1. Go to [Tracking → Returns](route:returns.index) → **New return**.
2. Choose the **person returning**. Their assigned equipment and accessories **load on their own**.
3. Choose the **reason** and, if you have them, the **ticket number**, the **ticket owner** and the **return owner** (you, by default).
4. In the items table, tick **Include** for what comes back and choose each one's **destination**:
   - **Rippling warehouse**: answer **Was it with Rippling before?** If "No", Rippling has to receive it and set it up (two more steps).
   - **A clinic's storage**: choose **which clinic** keeps it.
5. Add a **first note** if needed (in English) and press **Create return**.

### The clinic list

The list shows locations as a tree (country → state → clinic). **Only clinics can be chosen**, the last level; countries and states are greyed out. Clinics come from [Settings → Locations](route:locations.index).

## Recording progress by hand

The box shipment is handled on another platform, so **each step is recorded by hand** when it happens. The four steps of the shipment to Rippling are:

1. **Address confirmed**: where to send the boxes was confirmed.
2. **Boxes sent to the person**.
3. **The person shipped the boxes** back.
4. **Arrived at the Rippling warehouse**.

To record a step, below the progress bar:

1. In **What happened?** choose the step.
2. In **Date** keep today or pick the day it happened (future dates are not accepted).
3. Press **Record**.

**Steps can be skipped**: marking "The person shipped the boxes" gives the missing earlier steps the same date.

### Undoing a step

If something was marked by mistake, press **Undo "step"** next to the form: the last recorded step is cleared.

> The **arrival at the Rippling warehouse cannot be undone**: at that moment the items are checked in and become available there.

### Rippling steps per item

Items that **were not with Rippling before** have two more steps on their row: **Rippling received it** and **Rippling set it up**. They are recorded the same way (step, date, Record) and can also be undone.

### Storing at the clinic

For items going to a clinic, once they are there, pick the date and press **Mark as stored**.

## What happens to each item

When an item reaches its destination (arrival at Rippling or stored at the clinic):

- **It is checked in**: no longer assigned to the person or to anything else.
- It is **ready to deploy**, available to hand to someone else.
- Its **location** becomes the clinic or the Rippling warehouse.
- The asset page shows, under the status, **"In the Edgewater warehouse · Return #12"** (or "In the Rippling warehouse"), linking to the location and the return. The line disappears when the asset is checked out again.

**Accessories** go back to available stock; where each one ended up is shown on the return.

The return is marked **Completed** on its own when every item reached its destination (and, those that need it, were set up by Rippling).

## Returns not moving

Since everything is recorded by hand, a return can sit still without anyone noticing. So when an open return goes several days **with no step, change or note**:

- it shows in the dashboard's **Needs attention**;
- it shows in the **Not moving** tab of the returns list, with the date of the last progress and how many days it has been still;
- it is included in the [daily email summary](help:email-and-summary#the-daily-summary).

The default is **7 days**; change it in **Settings → General → Returns → Days without progress before an alert**. Adding a note (e.g. "Called the person, ships on Monday") counts as progress.

## Notes

Under **Notes** you can add comments at any time (in English). **Every note is also copied to the history of each item** in the return, with the return number, so the trail stays on the asset.

## Editing or cancelling

- **Edit return details**: change the reason, ticket, owners, and the boxes' **carrier** and **tracking number**.
- **Change destination** of an item: possible while that item has not been checked in.
- **Cancel return**: only while nothing has been checked in. The items stay assigned to the person.

## Setting up returns

In **Settings → General → Returns** (superadmins):

- **Days without progress before an alert**: how many still days before warning (7 by default).
- **Partner warehouse name**: the name shown in every step, e.g. Rippling.
- **Partner warehouse location**: where items that arrive there stay. It must be marked as storage in Locations.
