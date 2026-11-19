<?php

namespace App\Presenters;

use App\Enums\ActionType;
use App\Enums\ActivityItemType;
use Illuminate\Support\Str;

/**
 * Class CompanyPresenter
 */
class ActionlogPresenter extends Presenter
{
    public function adminuser()
    {
        if ($user = $this->model->user) {
            if (empty($user->deleted_at)) {
                return $user->present()->nameUrl();
            }

            // The user was deleted
            return '<del>'.$user->display_name.'</del> (deleted)';
        }

        return '';
    }

    public function item()
    {
        if ($item = $this->model->item) {
            if (empty($item->deleted_at)) {
                return $this->model->item->present()->nameUrl();
            }

            // The item was deleted
            return '<del>'.$item->name.'</del> (deleted)';
        }

        return '';
    }

    public function icon()
    {

        // User related icons
        if ($this->itemType() == 'user') {

            if ($this->action_type == '2fa reset') {
                return 'fa-solid fa-mobile-screen';
            }

            if ($this->action_type == 'create') {
                return 'fa-solid fa-user-plus';
            }

            if ($this->action_type == 'merged') {
                return 'fa-solid fa-people-arrows';
            }

            if ($this->action_type == 'delete') {
                return 'fa-solid fa-user-minus';
            }

            if ($this->action_type == 'upload deleted') {
                return 'fa-solid fa-trash';
            }

            if ($this->action_type == 'update') {
                return 'fa-solid fa-user-pen';
            }

            return 'fa-solid fa-user';
        }

        // Everything else
        if ($this->action_type == 'create') {
            return 'fa-solid fa-plus';
        }

        if ($this->action_type == 'merged') {
            return 'fa-solid fa-code-merge';
        }

        if (($this->action_type == 'delete') || ($this->action_type == 'upload deleted')) {
            return 'fa-solid fa-trash';
        }

        if ($this->action_type == 'update') {
            return 'fa-solid fa-pen';
        }

        if ($this->action_type == 'restore') {
            return 'fa-solid fa-trash-arrow-up';
        }

        if ($this->action_type == 'upload') {
            return 'fas fa-paperclip';
        }

        if ($this->action_type == 'checkout') {
            return 'fa-solid fa-rotate-left';
        }

        if ($this->action_type == 'checkin from') {
            return 'fa-solid fa-rotate-right';
        }

        if ($this->action_type == 'note added') {
            return 'fas fa-sticky-note';
        }

        if ($this->action_type == 'audit') {
            return 'fas fa-clipboard-check';
        }

        // QuantityAdjust splits three ways by delta sign. Zero-delta is
        // an audit-only confirmation (matches actionType()'s "confirmed
        // quantity" label swap), so it shares the audit icon. Positive
        // and negative deltas get plus / minus so the history row's
        // icon telegraphs whether qty went up or down at a glance.
        if ($this->action_type == ActionType::QuantityAdjust->value) {
            $delta = (int) $this->quantity;
            if ($delta > 0) {
                return 'fa-solid fa-plus';
            }
            if ($delta < 0) {
                return 'fa-solid fa-minus';
            }

            return 'fas fa-clipboard-check';
        }

        // License seat-count changes from the edit-form path (not the
        // adjust-quantity flow) also want +/- icons so the history tab
        // reads consistently regardless of which path added / removed
        // the seats.
        if ($this->action_type == ActionType::AddSeats->value) {
            return 'fa-solid fa-plus';
        }

        if ($this->action_type == ActionType::DeleteSeats->value) {
            return 'fa-solid fa-minus';
        }

        return 'fa-solid fa-rotate-right';

    }

    public function actionType()
    {
        if (str_starts_with((string) $this->action_type, 'shipment ')) {
            return mb_strtolower(trans('asset-shipments.'.str_replace(' ', '_', $this->action_type)));
        }

        // Zero-delta QuantityAdjust entries are audit-only submissions.
        // The operator counted the shelf and confirmed the on-hand qty
        // matches the DB. Rendering these as "adjusted quantity" with a
        // blank change column is confusing. Surface them as "confirmed
        // quantity" instead. The underlying action_type value stays
        // 'adjusted quantity' so filters and reports keep working.
        if ($this->action_type === ActionType::QuantityAdjust->value && (int) $this->quantity === 0) {
            return mb_strtolower(trans('general.confirmed_quantity'));
        }

        return mb_strtolower(trans('general.'.str_replace(' ', '_', $this->action_type)));
    }

    public function activityTitle(): string
    {
        $action = strtolower((string) $this->model->action_type);
        if (str_starts_with($action, 'shipment ')) {
            return trans('asset-shipments.'.str_replace(' ', '_', $action));
        }

        if ($action === ActionType::QuantityAdjust->value) {
            $action = match ((int) $this->model->quantity <=> 0) {
                -1 => 'quantity_decreased',
                1 => 'quantity_increased',
                default => 'quantity_confirmed',
            };
        }

        $key = 'activity-log.actions.'.str_replace(' ', '_', $action);
        $type = ActivityItemType::tryFromAny((string) $this->model->item_type)?->label()
            ?? trans('general.item');
        $title = trans($key, ['type' => mb_strtolower($type)]);

        return $title === $key ? Str::headline((string) $this->model->action_type) : $title;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public function activityFacts(array $meta): array
    {
        $action = $this->model->action_type;
        $target = $meta['_activity_assignment_target'] ?? $this->model->target?->display_name;
        $target = is_string($target) && $target !== '' ? $target : null;
        $missing = trans('activity-log.target_not_recorded');
        $shipment = (array) ($meta['_shipment'] ?? []);
        $facts = match ($action) {
            ActionType::Checkout->value => [
                'previous_assignment' => array_key_exists('_activity_previous_assignment', $meta)
                    ? ($meta['_activity_previous_assignment'] ?? trans('general.unassigned'))
                    : $missing,
                'assigned_to' => $target ?? $missing,
                'duplicate_assignment_reason' => $meta['_duplicate_assignment_reason'] ?? null,
                'duplicate_assignment_approver' => ((array) ($meta['_duplicate_assignment_approval'] ?? []))['approver_name'] ?? null,
                'units' => $this->model->quantity > 1 ? (string) $this->model->quantity : null,
            ],
            ActionType::CheckinFrom->value => [
                ($this->model->targetType() === 'user' ? 'previous_assignment' : 'returned_from') => $target ?? $missing,
            ],
            ActionType::Consumed->value => [
                'issued_to' => $target ?? $missing,
                'units' => $this->model->quantity > 0 ? (string) $this->model->quantity : null,
            ],
            ActionType::Accepted->value, ActionType::Declined->value => ['response_from' => $target],
            ActionType::Requested->value, ActionType::RequestCanceled->value => ['requested_by' => $target],
            ActionType::Audit->value => ['audited_at' => $this->model->location?->name],
            ActionType::MaintenanceComplete->value => ['related_item' => $target],
            ActionType::Merged->value => ['note' => $this->model->note, 'related_item' => $target],
            ActionType::Impersonated->value, ActionType::StoppedImpersonating->value,
            ActionType::TokenRevoked->value, ActionType::TokenUnrevoked->value => ['related_item' => $target],
            ActionType::AddSeats->value, ActionType::DeleteSeats->value => [
                'units' => $this->model->quantity !== null ? (string) abs((int) $this->model->quantity) : null,
            ],
            ActionType::ShipmentCreated->value => [
                'shipment_recipient' => $target,
                'shipment_platform' => $shipment['platform'] ?? null,
                'shipment_tracking' => $shipment['tracking'] ?? null,
                'shipment_expected' => $shipment['expected_on'] ?? null,
            ],
            ActionType::ShipmentReceived->value => [
                'shipment_recipient' => $target,
                'note' => $this->model->note,
            ],
            ActionType::ShipmentRescheduled->value, ActionType::ShipmentCancelled->value => ['note' => $this->model->note],
            ActionType::NoteAdded->value, ActionType::QuantityAdjust->value => ['note' => $this->model->note],
            default => [],
        };

        $details = [];
        foreach ($facts as $label => $value) {
            if (is_string($value) && trim($value) !== '') {
                $details[] = ['label' => trans('activity-log.facts.'.$label), 'value' => $value];
            }
        }

        return $details;
    }

    public function attachmentName(): string
    {
        if ($this->model->original_filename) {
            return $this->model->original_filename;
        }

        $filename = (string) $this->model->filename;
        if (in_array($this->model->action_type, [ActionType::Uploaded->value, ActionType::UploadDeleted->value], true)
            && preg_match('/^[a-z]+-'.(int) $this->model->item_id.'-[a-z0-9]{8}-(.+)$/iD', $filename, $match) === 1) {
            return $match[1];
        }

        return $filename;
    }

    public function target()
    {
        $target = null;
        // Target is messy.
        // On an upload, the target is the item we are uploading to, stored as the "item" in the log.
        if ($this->action_type == 'uploaded') {
            $target = $this->model->item;
        } elseif (($this->action_type == 'accepted') || ($this->action_type == 'declined')) {
            // If we are logging an accept/reject, the target is not stored directly,
            // so we access it through who the item is assigned to.
            // FIXME: On a reject it's not assigned to anyone.
            $target = $this->model->item->assignedTo;
        } elseif ($this->action_type == 'requested') {
            if ($this->model->user) {
                $target = $this->model->user;
            }
        } elseif ($this->model->target) {
            // Otherwise, we'll just take the target of the log.
            $target = $this->model->target;
        }

        if ($target) {
            if (empty($target->deleted_at)) {
                return $target->present()->nameUrl();
            }

            return '<del>'.$target->display_name.'</del>';
        }

        return '';
    }
}
