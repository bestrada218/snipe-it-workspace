# Asset tags

How each tag is made, how it is built when creating an item and how tag types are managed.

## The XX-KKK-PP-TTT format

Every inventory item carries a unique tag with four parts:

| Part | What it is | Example |
| --- | --- | --- |
| **XX** | Parent location, usually the state. | NY, NJ |
| **KKK** | Global three-digit sequence. | 042 |
| **PP** | Item type. | LP (laptop), HS (headset) |
| **TTT** | Optional suffix, computers only: the operating system. | W11, MAC, LNX |

Examples: `NY-042-LP-W11` (Windows 11 laptop in New York), `NJ-105-HS` (headset in New Jersey, no suffix).

The [asset tag guide](route:guide.asset-tags) has the full table of types and suffixes.

## The global sequence

- **A number identifies a single piece across the whole inventory**: assets, accessories, consumables, components and licenses share the same sequence. If `NY-042-LP-W11` exists, `NJ-042-HS` cannot.
- A number is never reused, even after the item is deleted or changes type, location or suffix.
- When two people save at the same time, **they never get the same number**: whoever saves second automatically gets the next free one and sees a notice with the final tag.
- The sequence goes up to 999.

## Building the tag when creating an item

Every module's create form has the **Build the tag** block:

1. Choose the **Location (XX)**.
2. Choose the **Type (PP)**.
3. For a laptop or desktop, choose the **Suffix (TTT)**; other types have no suffix.
4. The **Sequence (KKK)** fills itself with the next free number.

The tag is built above on its own. **The tag field cannot be typed by hand**, which prevents mistakes and repeated numbers.

### Creating several assets at once

When creating assets you can add rows to register several units of the same model in one step. Each row gets its own sequence number (the next free one, and so on).

<!-- admin-only -->

## Tag types (PP)

Types are managed in **Settings → Asset Tags → Tag types (PP)** ([open](route:settings.asset_tag_types.index)). There is also a **Manage types** link next to the tag builder.

- **Add** a type: a two-letter code and a name. Rows left blank are ignored when saving.
- **Choose which types take a suffix** (usually laptops and desktops).
- **Rename a code**, for example LP → LT: every tag that uses it is updated in every module, and each item keeps the change in its history.

> A type that tags already use cannot be removed. Change its code or name instead.

## Repeated tags from old data

If data with repeated numbers was imported, an administrator can list them with `php artisan snipeit:asset-tag-duplicates`. The first record of each number keeps it and the others must be corrected.

<!-- end-admin-only -->
