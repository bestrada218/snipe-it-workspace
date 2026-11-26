# Locations and inventory

How locations are organized, what a storage location is, how inventory modules are turned on and renamed, and how a group is limited to a location.

## Locations as a tree

Locations can be organized in a hierarchy, such as **region → site → warehouse**. Each location can have a **parent location**. [Settings → Locations](route:locations.index)

The tree is used in several places:

- The **XX** part of the [asset tag](help:asset-tags) is the parent location, usually the state.
- For internal storage in [returns](help:returns), choose an eligible location at the deepest level.
- A group limited to a location also sees the locations under it.

## Storage locations

When editing a location, the **Storage / warehouse** box marks it as a warehouse or store room. What is in a storage location counts as **stored**, not **installed and in use**.

A fixed asset can be installed at a site or stored in a warehouse. Location-based modules show installed assets separately from warehouse stock.

The external provider warehouse location, for returns, must be marked as storage.

<!-- admin-only -->

## Inventory modules

In **Settings → General → Inventory modules** a superadmin can:

- **Turn each inventory type on or off** (accessories, consumables, components, licenses…). Turning a module off hides it from the menu; its records are kept.
- **Rename** a module to the name the team uses.

### Location-based modules

On the same page, **Location-based modules → Add a module** creates a list of its own in the Inventory menu, for example **Fixed assets**:

| Field | What it does |
| --- | --- |
| **Name** | How it appears in the menu. |
| **Location** | Limits the list to that location (and the ones under it). Blank: all. |
| **Categories** | Only assets of those categories (e.g. TVs and projectors). Blank: all. |

The module lists the assets **assigned to a location** that match, leaving out storage locations.

## Groups limited to a location

In the admin page (the gear at the top right) → **Groups**, when editing a group, the **Location scope** field limits what its members can see and do to one location and the ones under it.

- Leave it blank for no location limit.
- The group's permissions (view, create, check out…) are set below as usual.
- Choosing a location outside the scope is refused.

<!-- end-admin-only -->
