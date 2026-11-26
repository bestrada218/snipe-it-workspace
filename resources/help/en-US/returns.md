# Returns

How to record what a person hands back, when they leave or when equipment is exchanged, and follow each item until it is stored.

## When to use it

A return groups **everything a person hands back**, for one of two reasons:

- **Termination**: the person leaves the company.
- **Change**: one piece of equipment is exchanged for another.

Each returned item goes to one of two places:

- **external provider warehouse** (the hardware partner): shipped in boxes and received by the provider.
- **Internal warehouse** stores the item at an organization-managed location.


## Creating a return

1. Go to [Tracking → Returns](route:returns.index) → **New return**.
2. Choose the **person returning**. Their assigned equipment and accessories **load on their own**.
3. Choose the **reason** and, if you have them, the **ticket number**, the **ticket owner** and the **return owner** (you, by default).
4. In the items table, tick **Include** for what comes back and choose each one's **destination**:
   - **external provider warehouse**: answer **Was it with the provider before?** If "No", the provider has to receive it and set it up (two more steps).
   - **Internal warehouse**. Choose the warehouse location that will keep the item.
5. Add a **first note** if needed (in English) and press **Create return**.

### Choosing an internal warehouse

The picker shows the hierarchy configured in Settings. You can choose eligible locations from its deepest level. Higher levels are shown as headings. [Settings → Locations](route:locations.index)

## Recording progress by hand

**Record each step by hand** when it happens. The shipment to the provider has four steps:

1. **Address confirmed**: where to send the boxes was confirmed.
2. **Boxes sent to the person**.
3. **The person shipped the boxes** back.
4. **Arrived at the external provider warehouse**.

To record a step, below the progress bar:

1. In **What happened?** choose the step.
2. In **Date** keep today or pick the day it happened (future dates are not accepted).
3. Press **Record**.

**Steps can be skipped**: marking "The person shipped the boxes" gives the missing earlier steps the same date.

### Undoing a step

If something was marked by mistake, press **Undo "step"** next to the form: the last recorded step is cleared.

> The **arrival at the external provider warehouse cannot be undone**: at that moment the items are checked in and become available there.

### Provider steps per item

Items that **were not with the provider before** have two more steps on their row: **The provider received it** and **The provider set it up**. They are recorded the same way (step, date, Record) and can also be undone.

### Storing at an internal warehouse

When items reach the internal warehouse, pick the date and press **Mark as stored**.

## What happens to each item

When an item reaches its warehouse:

- **It is checked in**: no longer assigned to the person or to anything else.
- If it was ready to assign, storing it at an internal warehouse sets it to **Pending Review**. At the external warehouse, equipment that needs setup stays in **Pending Setup** until setup is recorded, then moves to **Ready to Assign**.
- Its **physical location** becomes the warehouse that received it.
- The asset page links to the warehouse and return below the status. The line disappears when the asset is checked out again.

**Accessories** go back to available stock; where each one ended up is shown on the return.

The return is marked **Completed** on its own when every item reached its destination (and, those that need it, were set up by the provider).

## Returns not moving

Since everything is recorded by hand, a return can sit still without anyone noticing. So when an open return goes several days **with no step, change or note**:

- it shows in the dashboard's **Needs attention**;
- it shows in the **Not moving** tab of the returns list, with the date of the last progress and how many days it has been still;

Adding a note counts as progress.

## Notes

Under **Notes** you can add comments at any time (in English). **Every note is also copied to the history of each item** in the return, with the return number, so the trail stays on the asset.

## Editing or cancelling

- **Edit return details**: change the reason, ticket, owners, and the boxes' **carrier** and **tracking number**.
- **Change destination** of an item: possible while that item has not been checked in.
- **Cancel return**: only while nothing has been checked in. The items stay assigned to the person.

<!-- admin-only -->

## Setting up returns

In **Settings → General → Returns** (superadmins):

Adding a note counts as progress.
- **External provider name**: the name of the third party that manages the warehouse and appears in the return steps.
- **Partner warehouse location**: where items that arrive there stay. It must be marked as storage in Locations.

<!-- end-admin-only -->
