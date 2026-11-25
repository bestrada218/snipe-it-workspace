# Locations and inventory

How locations are organized, what a storage location is, how inventory modules are turned on and renamed, and how a group is limited to a location.

## Locations as a tree

Locations are organized as a tree: **country → state → clinic**. Each location can have a **parent location**. They are managed in [Settings → Locations](route:locations.index).

The tree is used in several places:

- The **XX** part of the [asset tag](help:asset-tags) is the parent location, usually the state.
- In [returns](help:returns) only clinics, the last level, can be chosen.
- A [group limited to a location](#groups-limited-to-a-location) also sees the locations under it.

## Storage locations

When editing a location, the **Storage / warehouse** box marks it as a warehouse or store room. What is in a storage location counts as **stored**, not **installed and in use**.

This matters because a fixed asset can be in use at a clinic (a TV on a wall) or stored because it is not being used. Items assigned to a storage location **do not appear** as in use in location-based modules.

The Rippling warehouse location, for returns, must be marked as storage.

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

In the admin page (the gear at the top right) → **Groups**, when editing a group, the **Location scope** field limits what its members can see and do to one location and the ones under it. For example, the Edgewater clinic team only works with Edgewater.

- Leave it blank for no location limit.
- The group's permissions (view, create, check out…) are set below as usual.
- Choosing a location outside the scope is refused.
