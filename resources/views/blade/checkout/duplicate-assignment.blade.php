@props(['type' => 'assets', 'item' => null])

@php($availableApprovers = \App\Services\DuplicateAssignments::approvers())

{{-- On the create form there is no item yet: the check uses the selected
     model and counts one unit per asset tag being created. --}}
<div class="form-group" data-duplicate-assignment
    @if ($item)
        data-preview="{{ route('duplicate-assignments.preview', ['type' => $type, 'id' => $item->getKey()]) }}"
    @else
        data-preview-model="{{ route('duplicate-assignments.preview-model', ['model' => '__MODEL__']) }}"
    @endif
    >
    <div class="col-md-8 col-md-offset-3">
        <div class="workspace-duplicate-warning" role="status" hidden>
            <strong data-duplicate-title></strong>
            <p data-duplicate-message></p>
        </div>
        <x-form.error name="duplicate_assignment_reason" />
        <x-form.error name="duplicate_assignment_ticket" />
        <x-form.error name="duplicate_assignment_approver" />
        <x-form.error name="duplicate_assignment_evidence" />
    </div>
    <input type="hidden" name="duplicate_assignment_confirmed" value="0">
    <input type="hidden" name="duplicate_assignment_reason" value="">
    <input type="hidden" name="duplicate_assignment_ticket" value="">
    <dialog class="workspace-duplicate-dialog" aria-labelledby="duplicate-assignment-question">
        <h2 id="duplicate-assignment-question">{{ trans('duplicate-assignments.confirm_title') }}</h2>
        <p data-duplicate-dialog-message></p>
        <label for="duplicate-assignment-motive">{{ trans('duplicate-assignments.reason') }}</label>
        <textarea id="duplicate-assignment-motive" class="form-control" rows="3" maxlength="1000"
            placeholder="{{ trans('duplicate-assignments.reason_placeholder') }}">{{ old('duplicate_assignment_reason') }}</textarea>
        <label for="duplicate-assignment-ticket">{{ trans('duplicate-assignments.ticket') }}</label>
        <input id="duplicate-assignment-ticket" type="text" class="form-control" maxlength="100" aria-required="true"
            placeholder="{{ trans('duplicate-assignments.ticket_placeholder') }}" value="{{ old('duplicate_assignment_ticket') }}">
        <p class="help-block" data-duplicate-requirement></p>
        <div data-duplicate-approval hidden>
        <label for="duplicate-assignment-approver">{{ trans('duplicate-assignments.approved_by') }}</label>
        <select id="duplicate-assignment-approver" name="duplicate_assignment_approver" class="form-control" aria-required="true">
            <option value="">{{ trans('duplicate-assignments.select_approver') }}</option>
            @foreach ($availableApprovers as $approver)
                <option value="{{ $approver->id }}" @selected((string) old('duplicate_assignment_approver') === (string) $approver->id)>
                    {{ $approver->display_name }}{{ $approver->username ? ' ('.$approver->username.')' : '' }}
                </option>
            @endforeach
        </select>
        @if ($availableApprovers->isEmpty())
            <p class="help-block">{{ trans('duplicate-assignments.no_approvers') }}</p>
        @endif
        <label for="duplicate-assignment-evidence">{{ trans('duplicate-assignments.evidence') }}</label>
        <input id="duplicate-assignment-evidence" name="duplicate_assignment_evidence" type="file" class="form-control"
            accept=".pdf,.jpg,.jpeg,.png,.webp" aria-required="true" aria-describedby="duplicate-evidence-help">
        <p class="help-block" id="duplicate-evidence-help">{{ trans('duplicate-assignments.evidence_help') }}</p>
        </div>
        <div class="workspace-duplicate-actions">
            <button type="button" data-duplicate-cancel class="btn btn-default">{{ trans('duplicate-assignments.cancel') }}</button>
            <button type="button" data-duplicate-confirm class="btn btn-theme" data-no-approvers="{{ $availableApprovers->isEmpty() ? '1' : '0' }}">{{ trans('duplicate-assignments.confirm') }}</button>
        </div>
    </dialog>
</div>

@once
    @push('js')
        <script nonce="{{ csrf_token() }}">
            $(function () {
                document.querySelectorAll('[data-duplicate-assignment]').forEach(panel => {
                    const form = panel.closest('form');
                    const dialog = panel.querySelector('dialog');
                    const warning = panel.querySelector('[role="status"]');
                    const reason = dialog.querySelector('textarea');
                    const approver = dialog.querySelector('[name="duplicate_assignment_approver"]');
                    const evidence = dialog.querySelector('[name="duplicate_assignment_evidence"]');
                    const confirmed = panel.querySelector('[name="duplicate_assignment_confirmed"]');
                    const submittedReason = panel.querySelector('[name="duplicate_assignment_reason"]');
                    const ticket = dialog.querySelector('#duplicate-assignment-ticket');
                    const submittedTicket = panel.querySelector('[name="duplicate_assignment_ticket"]');
                    const messages = {
                        warning: @json(trans('duplicate-assignments.warning')),
                        multiWarning: @json(trans('duplicate-assignments.multi_warning')),
                        assigned: @json(trans('duplicate-assignments.warning_title')),
                        multiple: @json(trans('duplicate-assignments.multi_title')),
                        failed: @json(trans('duplicate-assignments.check_failed')),
                        reason: @json(trans('duplicate-assignments.reason')),
                        ticket: @json(trans('duplicate-assignments.ticket_required')),
                        approver: @json(trans('duplicate-assignments.select_approver')),
                        evidence: @json(trans('duplicate-assignments.evidence_help')),
                        justificationOnly: @json(trans('duplicate-assignments.justification_only')),
                        approvalRequired: @json(trans('duplicate-assignments.approval_required')),
                    };
                    const approvalFields = dialog.querySelector('[data-duplicate-approval]');
                    const requirement = dialog.querySelector('[data-duplicate-requirement]');
                    const confirmButton = panel.querySelector('[data-duplicate-confirm]');
                    let requiresApproval = false;

                    // Laptops and desktops (the categories chosen in settings) need an
                    // approver and evidence; every other duplicate only needs a justification.
                    function applyRequirement(data) {
                        requiresApproval = Boolean(data?.requires_approval);
                        approvalFields.hidden = !requiresApproval;
                        requirement.textContent = requiresApproval ? messages.approvalRequired : messages.justificationOnly;
                        confirmButton.disabled = requiresApproval && confirmButton.dataset.noApprovers === '1';
                    }
                    let pending;
                    let approved = false;
                    let savedSubmitter;
                    let submitting = false;

                    function selection() {
                        const targetType = form.querySelector('[name="checkout_to_type"]:checked');
                        const tagCount = Array.from(form.querySelectorAll('[name^="asset_tags"]'))
                            .filter(input => input.value.trim() !== '').length;
                        return {
                            user: !targetType || targetType.value === 'user' ? form.elements.assigned_user?.value : '',
                            quantity: panel.dataset.previewModel
                                ? Math.max(1, tagCount)
                                : Math.max(1, Number(form.elements.checkout_qty?.value) || 1),
                        };
                    }

                    function previewUrl() {
                        if (panel.dataset.preview) {
                            return panel.dataset.preview;
                        }
                        const model = form.elements.model_id?.value;
                        return model ? panel.dataset.previewModel.replace('__MODEL__', encodeURIComponent(model)) : null;
                    }

                    function display(data) {
                        warning.hidden = !data?.duplicate;
                        if (!data?.duplicate) {
                            return '';
                        }

                        const text = (data.existing ? messages.warning : messages.multiWarning).replace(/:(existing|category|total)\b/g, (_, key) => ({
                            existing: data.existing,
                            category: data.category,
                            total: data.existing + data.incoming,
                        })[key]);
                        warning.querySelector('[data-duplicate-title]').textContent = data.existing ? messages.assigned : messages.multiple;
                        warning.querySelector('[data-duplicate-message]').textContent = text;
                        return text;
                    }

                    async function check() {
                        pending?.abort();
                        const current = selection();
                        const url = previewUrl();
                        if (!current.user || !url) {
                            display(null);
                            return null;
                        }
                        const controller = new AbortController();
                        pending = controller;
                        try {
                            const response = await fetch(url + '?' + new URLSearchParams(current), {
                                signal: controller.signal,
                                headers: {'Accept': 'application/json'},
                            });
                            if (!response.ok) {
                                throw new Error(messages.failed);
                            }
                            const result = await response.json();
                            return pending === controller ? result : undefined;
                        } catch (error) {
                            if (error.name === 'AbortError' || pending !== controller) {
                                return undefined;
                            }
                            warning.hidden = false;
                            warning.querySelector('[data-duplicate-title]').textContent = '';
                            warning.querySelector('[data-duplicate-message]').textContent = messages.failed;
                            return undefined;
                        }
                    }

                    $(form).on('change', '[name="assigned_user"], [name="checkout_to_type"], [name="checkout_qty"], [name="model_id"], [name^="asset_tags"]', async () => {
                        approved = false;
                        confirmed.value = '0';
                        submittedReason.value = '';
                        reason.value = '';
                        submittedTicket.value = '';
                        ticket.value = '';
                        approver.value = '';
                        evidence.value = '';
                        const result = await check();
                        if (result !== undefined) {
                            display(result);
                        }
                    });
                    check().then(result => {
                        if (result !== undefined) {
                            display(result);
                        }
                    });

                    form.addEventListener('submit', async event => {
                        if (approved || !selection().user) {
                            return;
                        }

                        event.preventDefault();
                        if (submitting) {
                            return;
                        }

                        submitting = true;
                        savedSubmitter = event.submitter;
                        const result = await check();
                        submitting = false;
                        if (result === undefined) {
                            return;
                        }

                        if (result?.duplicate) {
                            applyRequirement(result);
                            dialog.querySelector('[data-duplicate-dialog-message]').textContent = display(result);
                            dialog.showModal();
                            reason.focus();
                        } else {
                            approved = true;
                            form.requestSubmit(savedSubmitter || undefined);
                            approved = false;
                        }
                    });

                    const fields = [reason, ticket, approver, evidence];
                    for (const field of fields) {
                        field.addEventListener('input', () => field.setCustomValidity(''));
                        field.addEventListener('change', () => field.setCustomValidity(''));
                    }
                    dialog.addEventListener('close', () => {
                        for (const field of fields) {
                            field.setCustomValidity('');
                        }
                        if (confirmed.value !== '1') {
                            evidence.value = '';
                        }
                    });
                    panel.querySelector('[data-duplicate-cancel]').addEventListener('click', () => dialog.close());
                    panel.querySelector('[data-duplicate-confirm]').addEventListener('click', () => {
                        if (!reason.value.trim()) {
                            reason.setCustomValidity(messages.reason);
                            reason.reportValidity();
                            return;
                        }

                        if (!ticket.value.trim()) {
                            ticket.setCustomValidity(messages.ticket);
                            ticket.reportValidity();
                            return;
                        }

                        if (requiresApproval && !approver.value) {
                            approver.setCustomValidity(messages.approver);
                            approver.reportValidity();
                            return;
                        }
                        if (requiresApproval && !evidence.files.length) {
                            evidence.setCustomValidity(messages.evidence);
                            evidence.reportValidity();
                            return;
                        }

                        if (!requiresApproval) {
                            approver.value = '';
                            evidence.value = '';
                        }
                        confirmed.value = '1';
                        submittedReason.value = reason.value.trim();
                        submittedTicket.value = ticket.value.trim();
                        dialog.close();
                        approved = true;
                        form.requestSubmit(savedSubmitter || undefined);
                        approved = false;
                    });
                });
            });
        </script>
    @endpush
@endonce
