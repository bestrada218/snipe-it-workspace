# Shipments to remote people

How to record equipment shipped to someone who works remotely and how it gets assigned once it arrives.

## What it is for

When equipment is **not handed over in person** but sent by carrier, it should not be assigned right away: the person does not have it yet. The shipment keeps it **reserved** for the recipient while it travels, and the assignment is completed **when delivery is confirmed**.

## Recording a shipment

There are three ways:

- **[Tracking → Equipment in transit](route:shipments.index) → Register shipment**, choosing the asset.
- From the page of an available asset.
- When **creating a new asset**, ticking **Register a shipment for this asset**: the asset and its shipment are created in one step.

The asset must be in a status that allows assigning it (ready to deploy). Each shipment carries **one asset**.

### Shipment details

| Field | What to enter |
| --- | --- |
| **Recipient** | The person who will receive the equipment. |
| **Purchase platform** | Where it was bought, for a new purchase (e.g. Amazon, CDW). |
| **Carrier** | Who delivers it (e.g. FedEx, UPS). |
| **Tracking number** | The tracking the carrier gives. |
| **Estimated delivery date** | When it should arrive. Alerts are based on it. |
| **Purchase approved by** | Who authorized the purchase, from the list of purchase approvers. |
| **Purchase approval** and **Evidence** | The approval reference and a PDF or image backing it. |

## Shipment statuses

The progress line shows where it is: **recorded → in transit → received → assigned**. Lists show:

| Status | Meaning |
| --- | --- |
| **In transit** | On its way and the estimated date has not come yet. |
| **Due today** | Today is the estimated date. |
| **Delivery pending** | The estimated date has passed and nobody confirmed delivery. |
| **Received · assignment pending** | It arrived, but the assignment still has to be completed (see below). |
| **Received and assigned** | Done: the asset is assigned to the person. |
| **Cancelled** | It was cancelled and the asset is available again. |

## Confirming delivery

- **The recipient** can sign in, open the shipment and press **Confirm I received the equipment**.
- **An inventory manager** can confirm it for them with **Confirm delivery**, writing how it was verified (e.g. "Confirmed by phone with the user").

Once confirmed, **the asset is assigned** to the person automatically and it is recorded in its history.

### When it stays "assignment pending"

If the person **already held an item of that category**, the delivery is recorded but the assignment is not completed on its own: a manager presses **Complete assignment** and writes the justification (and the approval, when the category requires it). See [Duplicate assignments](help:assignments#duplicate-assignments).

## Rescheduling or cancelling

- **Reschedule delivery**: changes the estimated date and asks for the reason, e.g. "Carrier delayed the delivery".
- **Cancel shipment**: with a reason; the asset becomes available again.

## Tabs and alerts

The shipments page has three tabs:

- **In progress**: everything on its way.
- **Deliveries to confirm**: what arrives today or is already late.
- **Past shipments**: received and cancelled ones, with their closing date.

Shipments due today or late also appear in the dashboard's **Needs attention** and in the menu counter.

<!-- admin-only -->

## Setting up purchase approvers

In **Settings → General**, section **Purchase approvals**, a superadmin chooses who can be recorded as approver on shipments.

<!-- end-admin-only -->
