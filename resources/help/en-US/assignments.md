# Assignments

How an item is handed to a person, what happens when they already hold one of the same category and where those cases are reviewed.

## One unit per assignment

Each assignment hands over **a single tagged piece**. There are no quantity fields: a person receiving two headsets gets two assignments, each with its tag. That way you always know exactly which unit each person holds.

On every checkout screen **the tag comes first and stands out**, to confirm the right unit is handed over.

## Checking out an asset

1. Open the asset and press **Check out** (or use the row action in the list).
2. Choose **to whom**: a person, a location or another asset.
3. Check the date and, when needed, the expected check-in date.
4. Add a note if needed (in English) and save.

You can also check out several assets at once by selecting them in the list and using **Check out** in the bulk actions, or hand over a whole **kit**.

## Duplicate assignments

When the person **already holds an item of the same category**, the app warns before saving: "This person has 1 in "Headsets". With this assignment they will have 2." To go on you confirm and record:

- a **justification** (in English), e.g. "Backup headset for night shifts";
- the **ticket number** backing the assignment, e.g. INC-12345.

### Categories that need approval

In the categories the administrator chooses (usually laptops and desktops) you also need:

- **Approved by**: who authorized it, from the list of approvers.
- **Approval evidence**: a PDF or an image (JPG, PNG or WebP), such as the approval email.

Without them the assignment is not saved.

### Where it applies

The rule applies to **every** way of handing something over: single and bulk checkouts, kits, transfers between people, fulfilled requests, shipments and the API. In bulk checkouts, if someone would end up with a duplicate, the app asks to make that assignment individually, where the reason, ticket and approval are recorded.

## Duplicate assignments page

[Tracking → Duplicate assignments](route:duplicate-assignments.index) lists people who hold two or more units of one category. On each row you can:

- see **which units** they hold (with their tags);
- review the **recorded justifications**: reason, ticket, approver and evidence.

Rows marked "No justification recorded" are duplicates from before this rule or made outside the flow; they are worth reviewing.

## Setting up approvers and categories

In **Settings → General**, section **Duplicate assignment approval** (superadmins only):

- **Authorized approvers**: the people who can be recorded as approvers.
- **Categories that require approval**: where a duplicate needs an approver and evidence. Elsewhere the justification and ticket are enough.
