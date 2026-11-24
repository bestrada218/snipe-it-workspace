@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('admin/settings/general.general_title') }}
    @parent
@stop

{{-- Page content --}}
@section('content')

    <form method="POST" autocomplete="off" class="form-horizontal" role="form" id="create-form" xmlns="http://www.w3.org/1999/html">
        <!-- CSRF Token -->
        {{ csrf_field() }}

        <div class="row">
            <div class="col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2">


                <div class="panel box box-default">
                    <div class="box-header with-border">
                        <h2 class="box-title">
                            <x-icon type="general-settings"/>
                            {{ trans('admin/settings/general.general_settings') }}
                        </h2>
                    </div>

                    <div class="box-body">

                        <div class="col-md-12">

                            <fieldset>
                                <x-form.legend>{{ trans('duplicate-assignments.approval_settings') }}</x-form.legend>
                                <div class="form-group">
                                    <label for="duplicate_assignment_approvers" class="col-md-3 control-label">{{ trans('duplicate-assignments.approvers') }}</label>
                                    <div class="col-md-9">
                                        <input type="hidden" name="duplicate_approvers_configured" value="1">
                                        @php($selectedApprovers = array_map('intval', (array) old('duplicate_assignment_approvers', $setting->duplicate_assignment_approvers ?? [])))
                                        <select class="form-control select2" name="duplicate_assignment_approvers[]" id="duplicate_assignment_approvers" multiple style="width: 100%;" aria-describedby="duplicate-approvers-help">
                                            @foreach ($approvalCandidates as $candidate)
                                                <option value="{{ $candidate->id }}" @selected(in_array($candidate->id, $selectedApprovers, true))>
                                                    {{ $candidate->display_name }}{{ $candidate->username ? ' ('.$candidate->username.')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="help-block" id="duplicate-approvers-help">{{ trans('duplicate-assignments.approvers_help') }}</p>
                                        <x-form.error name="duplicate_assignment_approvers" />
                                        <x-form.error name="duplicate_assignment_approvers.*" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="duplicate_approval_categories" class="col-md-3 control-label">{{ trans('duplicate-assignments.approval_categories') }}</label>
                                    <div class="col-md-9">
                                        @php($selectedApprovalCategories = array_map('intval', (array) old('duplicate_approval_categories', $setting->duplicate_approval_categories ?? [])))
                                        <select class="form-control select2" name="duplicate_approval_categories[]" id="duplicate_approval_categories" multiple style="width: 100%;" aria-describedby="duplicate-approval-categories-help">
                                            @foreach ($approvalCategories as $category)
                                                <option value="{{ $category->id }}" @selected(in_array($category->id, $selectedApprovalCategories, true))>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="help-block" id="duplicate-approval-categories-help">{{ trans('duplicate-assignments.approval_categories_help') }}</p>
                                        <x-form.error name="duplicate_approval_categories" />
                                        <x-form.error name="duplicate_approval_categories.*" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="damage_report_statuses" class="col-md-3 control-label">{{ trans('damage-reports.statuses') }}</label>
                                    <div class="col-md-9">
                                        @php($selectedDamageStatuses = array_map('intval', (array) old('damage_report_statuses', $setting->damage_report_statuses ?? [])))
                                        <select class="form-control select2" name="damage_report_statuses[]" id="damage_report_statuses" multiple style="width: 100%;" aria-describedby="damage-report-statuses-help">
                                            @foreach ($damageStatusCandidates as $statusLabel)
                                                <option value="{{ $statusLabel->id }}" @selected(in_array($statusLabel->id, $selectedDamageStatuses, true))>{{ $statusLabel->name }}</option>
                                            @endforeach
                                        </select>
                                        <p class="help-block" id="damage-report-statuses-help">{{ trans('damage-reports.statuses_help') }}</p>
                                        <x-form.error name="damage_report_statuses" />
                                        <x-form.error name="damage_report_statuses.*" />
                                    </div>
                                </div>
                            </fieldset>

                            @can('superadmin')
                                <fieldset>
                                    <x-form.legend>{{ trans('asset-shipments.purchase_settings') }}</x-form.legend>
                                    <div class="form-group">
                                        <label for="purchase_approvers" class="col-md-3 control-label">{{ trans('asset-shipments.purchase_approvers') }}</label>
                                        <div class="col-md-9">
                                            <input type="hidden" name="purchase_approvers_configured" value="1">
                                            @php($selectedPurchaseApprovers = array_map('intval', (array) old('purchase_approvers', $setting->purchase_approvers ?? [])))
                                            <select class="form-control select2" name="purchase_approvers[]" id="purchase_approvers" multiple style="width: 100%;" aria-describedby="purchase-approvers-help">
                                                @foreach ($approvalCandidates as $candidate)
                                                    <option value="{{ $candidate->id }}" @selected(in_array($candidate->id, $selectedPurchaseApprovers, true))>
                                                        {{ $candidate->display_name }}{{ $candidate->username ? ' ('.$candidate->username.')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <p class="help-block" id="purchase-approvers-help">{{ trans('asset-shipments.purchase_approvers_help') }}</p>
                                            <x-form.error name="purchase_approvers" />
                                            <x-form.error name="purchase_approvers.*" />
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset>
                                    <x-form.legend>{{ trans('asset-returns.settings_title') }}</x-form.legend>
                                    <input type="hidden" name="return_settings_configured" value="1">
                                    <div class="form-group">
                                        <label for="return_partner_name" class="col-md-3 control-label">{{ trans('asset-returns.settings_partner_name') }}</label>
                                        <div class="col-md-7">
                                            <input class="form-control" type="text" id="return_partner_name" name="return_partner_name" maxlength="60"
                                                value="{{ old('return_partner_name', $setting->return_partner_name) }}" aria-describedby="return-partner-name-help">
                                            <p class="help-block" id="return-partner-name-help">{{ trans('asset-returns.settings_partner_name_help') }}</p>
                                            <x-form.error name="return_partner_name" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="return_partner_location_id" class="col-md-3 control-label">{{ trans('asset-returns.settings_partner_location') }}</label>
                                        <div class="col-md-7">
                                            <select class="form-control" id="return_partner_location_id" name="return_partner_location_id" aria-describedby="return-partner-location-help">
                                                <option value="">—</option>
                                                @foreach (\App\Services\AssetReturns::storageLocations() as $storageLocation)
                                                    <option value="{{ $storageLocation->id }}" @selected((string) old('return_partner_location_id', $setting->return_partner_location_id) === (string) $storageLocation->id)>{{ $storageLocation->name }}</option>
                                                @endforeach
                                            </select>
                                            <p class="help-block" id="return-partner-location-help">{{ trans('asset-returns.settings_partner_location_help') }}</p>
                                            <x-form.error name="return_partner_location_id" />
                                        </div>
                                    </div>
                                </fieldset>
                            @endcan

                            <fieldset>
                                <x-form.legend>
                                    {{ trans('admin/settings/general.inventory_modules') }}
                                </x-form.legend>

                                <div class="form-group">
                                    <div class="col-md-9 col-md-offset-3">
                                        <p class="help-block">{{ trans('admin/settings/general.inventory_modules_help') }}</p>
                                        @foreach (array_keys(\App\Models\Setting::INVENTORY_MODULES) as $module)
                                            <input type="hidden" name="inventory_modules[{{ $module }}]" value="0">
                                            <div class="inventory-module-settings-row">
                                                <label class="inventory-module-option">
                                                    <x-input.checkbox
                                                        :name="'inventory_modules['.$module.']'"
                                                        :checked="old('inventory_modules.'.$module, \App\Models\Setting::inventoryModuleEnabled($module))"
                                                    />
                                                    <span>{{ \App\Models\Setting::inventoryModuleLabel($module) }}</span>
                                                </label>
                                                <x-form.inventory-module-name
                                                    :name="'inventory_module_labels['.$module.']'"
                                                    :key-path="'inventory_module_labels.'.$module"
                                                    :label="\App\Models\Setting::inventoryModuleLabel($module)"
                                                    :stored="$setting->inventory_module_labels[$module] ?? null"
                                                    :placeholder="trans('general.'.$module)"
                                                />
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset>
                                <x-form.legend>{{ trans('admin/settings/general.inventory_location_modules') }}</x-form.legend>
                                <div class="form-group">
                                    <div class="col-md-9 col-md-offset-3">
                                        <p class="help-block">{{ trans('admin/settings/general.inventory_location_help') }}</p>
                                        @foreach ([...\App\Models\Setting::inventoryLocationModules(), 'new' => ['name' => null, 'location_id' => null]] as $key => $module)
                                            @if ($key === 'new')
                                                <details class="inventory-location-module inventory-location-add" @if (old('inventory_location_modules.new.name') || $errors->has('inventory_location_modules.new.name') || $errors->has('inventory_location_modules.new.location_id')) open @endif>
                                                    <summary><x-icon type="plus" /> {{ trans('admin/settings/general.inventory_location_add') }}</summary>
                                                    <label for="inventory-location-name-new">{{ trans('general.name') }}</label>
                                                    <input id="inventory-location-name-new" class="form-control" type="text"
                                                        name="inventory_location_modules[new][name]"
                                                        value="{{ old('inventory_location_modules.new.name') }}" maxlength="60">
                                                    <x-form.error name="inventory_location_modules.new.name" />
                                            @else
                                                <div class="inventory-location-module">
                                                    <div class="inventory-module-settings-row">
                                                        <span class="inventory-location-title">{{ \App\Models\Setting::inventoryLocationModuleLabel($module) }}</span>
                                                        <x-form.inventory-module-name
                                                            :name="'inventory_location_modules['.$key.'][name]'"
                                                            :key-path="'inventory_location_modules.'.$key.'.name'"
                                                            :label="\App\Models\Setting::inventoryLocationModuleLabel($module)"
                                                            :stored="$module['name']"
                                                            :placeholder="\App\Models\Setting::inventoryLocationModuleLabel($module)"
                                                        />
                                                    </div>
                                            @endif
                                                <label for="inventory-location-{{ $key }}">{{ trans('general.location') }}</label>
                                                <select class="form-control" id="inventory-location-{{ $key }}" name="inventory_location_modules[{{ $key }}][location_id]">
                                                    <option value="">{{ trans('admin/settings/general.inventory_location_all') }}</option>
                                                    @foreach ($inventoryLocations as $locationId => $locationName)
                                                        <option value="{{ $locationId }}" @selected((string) old('inventory_location_modules.'.$key.'.location_id', $module['location_id']) === (string) $locationId)>{{ $locationName }}</option>
                                                    @endforeach
                                                </select>
                                                <x-form.error :name="'inventory_location_modules.'.$key.'.location_id'" />
                                                <label for="inventory-location-categories-{{ $key }}">{{ trans('admin/settings/general.inventory_location_categories') }}</label>
                                                @php($moduleCategories = array_map('intval', (array) old('inventory_location_modules.'.$key.'.category_ids', $module['category_ids'] ?? [])))
                                                <select class="form-control select2" id="inventory-location-categories-{{ $key }}" name="inventory_location_modules[{{ $key }}][category_ids][]" multiple style="width: 100%;"
                                                    aria-describedby="inventory-location-categories-help-{{ $key }}">
                                                    @foreach ($assetCategories as $categoryId => $categoryName)
                                                        <option value="{{ $categoryId }}" @selected(in_array((int) $categoryId, $moduleCategories, true))>{{ $categoryName }}</option>
                                                    @endforeach
                                                </select>
                                                <p class="help-block" id="inventory-location-categories-help-{{ $key }}">{{ trans('admin/settings/general.inventory_location_categories_help') }}</p>
                                                <x-form.error :name="'inventory_location_modules.'.$key.'.category_ids'" />
                                                @if ($key !== 'new')
                                                    <input type="hidden" name="inventory_location_modules[{{ $key }}][enabled]" value="0">
                                                    <label class="inventory-module-option">
                                                        <x-input.checkbox
                                                            :name="'inventory_location_modules['.$key.'][enabled]'"
                                                            :checked="old('inventory_location_modules.'.$key.'.enabled', $module['enabled'] ?? true)"
                                                        />
                                                        <span>{{ trans('admin/settings/general.inventory_location_show') }}</span>
                                                    </label>
                                                @endif
                                            @if ($key === 'new')
                                                </details>
                                            @else
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset>
                                <x-form.legend>
                                    {{ trans('admin/settings/general.legends.scoping') }}
                                </x-form.legend>

                                <!-- Full Multiple Companies Support -->
                                <x-form.checkbox-row
                                    name="full_multiple_companies_support"
                                    :label="trans('admin/settings/general.full_multiple_companies_support_text')"
                                    :item="$setting"
                                    :help_text="trans('admin/settings/general.full_multiple_companies_support_help_text')"
                                />

                                <!-- Scope Locations with Full Multiple Companies Support -->
                                <div class="form-group">
                                    <div class="col-md-8 col-md-offset-3">
                                        <livewire:location-scope-check />
                                    </div>
                                </div>

                                <!-- Null Company Is Floater -->
                                <x-form.checkbox-row
                                    name="null_company_is_floater"
                                    :label="trans('admin/settings/general.null_company_is_floater_text')"
                                    :item="$setting"
                                    :disabled="! $setting->full_multiple_companies_support"
                                    :help_text="trans('admin/settings/general.null_company_is_floater_help_text')"
                                />
                            </fieldset>

                            <fieldset>
                                <x-form.legend>
                                    {{ trans('admin/settings/general.legends.formats') }}
                                </x-form.legend>

                                <!-- Email domain -->
                                <x-form.row
                                    name="email_domain"
                                    :label="trans('general.email_domain')"
                                    :help_text="trans('general.email_domain_help')"
                                >
                                    <x-slot:input>
                                        <x-input.text
                                            name="email_domain"
                                            :value="old('email_domain', $setting->email_domain)"
                                            placeholder="example.com"
                                        />
                                    </x-slot:input>
                                </x-form.row>

                                <!-- Email format -->
                                <x-form.row
                                    name="email_format"
                                    :label="trans('admin/settings/general.email_formats.email_format')"
                                >
                                    <x-slot:input>
                                        <x-input.email-format-select
                                            name="email_format"
                                            :selected="old('email_format', $setting->email_format)"
                                            style="width: 100%"
                                            aria-label="email_format"
                                        />
                                    </x-slot:input>
                                </x-form.row>

                                <!-- Username format -->
                                <x-form.row
                                    name="username_format"
                                    :label="trans('admin/settings/general.username_formats.username_format')"
                                    :help_text="trans('admin/settings/general.username_format_help')"
                                >
                                    <x-slot:input>
                                        <x-input.username-select
                                            name="username_format"
                                            :selected="old('username_format', $setting->username_format)"
                                            style="width: 100%"
                                            aria-label="username_format"
                                        />
                                    </x-slot:input>
                                </x-form.row>
                            </fieldset>


                            <fieldset>
                                <x-form.legend>
                                    {{ trans('admin/settings/general.legends.profiles') }}
                                </x-form.legend>

                                <!-- user profile edit checkbox -->
                                <x-form.checkbox-row
                                    name="profile_edit"
                                    :label="trans('admin/settings/general.profile_edit_help')"
                                    :item="$setting"
                                />
                            </fieldset>

                            <fieldset>
                                <x-form.legend>
                                    {{ trans('admin/settings/general.legends.eula') }}
                                </x-form.legend>

                                <!-- Require signature for acceptance -->
                                <x-form.checkbox-row
                                    name="require_accept_signature"
                                    :label="trans('admin/settings/general.require_accept_signature')"
                                    :item="$setting"
                                    :help_text="trans('admin/settings/general.require_accept_signature_help_text')"
                                />

                                <!-- Default EULA -->
                                <x-form.row
                                    name="default_eula_text"
                                    :label="trans('admin/settings/general.default_eula_text')"
                                    :help_text="trans('admin/settings/general.default_eula_help_text')"
                                >
                                    <x-slot:input>
                                        <x-input.textarea
                                            name="default_eula_text"
                                            :value="old('default_eula_text', $setting->default_eula_text)"
                                            :placeholder="trans('admin/settings/general.default_eula_text_placeholder')"
                                        />
                                        <x-form.help name="default_eula_text" icon="markdown">
                                            {{ trans('general.markdown') }}
                                        </x-form.help>
                                    </x-slot:input>
                                </x-form.row>
                            </fieldset>

                            <fieldset>
                                <x-form.legend>
                                    {{ trans('admin/settings/general.legends.misc_display') }}
                                </x-form.legend>

                                <!-- Thumb Size -->
                                <x-form.row
                                    name="thumbnail_max_h"
                                    :label="trans('admin/settings/general.thumbnail_max_h')"
                                    :help_text="trans('admin/settings/general.thumbnail_max_h_help')"
                                >
                                    <x-slot:input>
                                        <x-input.text
                                            type="number"
                                            name="thumbnail_max_h"
                                            :value="old('thumbnail_max_h', $setting->thumbnail_max_h ?? '25')"
                                            style="max-width: 100px;"
                                            placeholder="50"
                                            maxlength="3"
                                        />
                                    </x-slot:input>
                                </x-form.row>

                                <!-- Model List prefs -->
                                <x-form.checkbox-row
                                    name="show_in_model_list"
                                    :label="trans('admin/settings/general.show_in_model_list')"
                                    :options="[
                                        'image' => trans('general.image'),
                                        'category' => trans('general.category'),
                                        'manufacturer' => trans('general.manufacturer'),
                                        'model_number' => trans('general.model_no'),
                                    ]"
                                    :selected="$snipeSettings->modellist_displays"
                                />

                                <!-- Shortcuts enable -->
                                <x-form.checkbox-row
                                    name="shortcuts_enabled"
                                    :label="trans('admin/settings/general.shortcuts_enabled')"
                                    :item="$setting"
                                    help_text="{!! trans('admin/settings/general.shortcuts_help_text') !!}"
                                />

                                <!-- Archived in List -->
                                <x-form.checkbox-row
                                    name="show_archived_in_list"
                                    :label="trans('admin/settings/general.show_archived_in_list_text')"
                                    :item="$setting"
                                />

                                <!-- Show assets assigned to user's assets -->
                                <x-form.checkbox-row
                                    name="show_assigned_assets"
                                    :label="trans('admin/settings/general.show_assigned_assets')"
                                    :item="$setting"
                                    :help_text="trans('admin/settings/general.show_assigned_assets_help')"
                                />
                            </fieldset>


                            <fieldset>
                                <x-form.legend>
                                    {{ trans('general.email') }}
                                </x-form.legend>

                                <!-- Mail test -->
                                <div class="form-group">
                                    <label for="login_note" class="col-md-3 control-label">{{ trans('admin/settings/general.test_mail') }}</label>

                                    <div class="col-md-8" id="mailtestrow">
                                        <a class="btn btn-default btn-sm pull-left{{ (config('mail.reply_to.address') == '') ? ' disabled' : '' }}" id="mailtest" style="margin-right: 10px;">
                                            {{ trans('admin/settings/general.mail_test') }}</a>
                                        <span id="mailtesticon" role="status" aria-live="polite" aria-atomic="true"></span>
                                        <span id="mailtestresult"></span>
                                        <span id="mailteststatus" role="status" aria-live="polite" aria-atomic="true"></span>
                                    </div>
                                    <div class="col-md-8 col-md-offset-3">
                                        <div id="mailteststatus-error" class="text-danger" role="alert" aria-live="assertive" aria-atomic="true"></div>
                                    </div>
                                    <div class="col-md-8 col-md-offset-3">
                                        <div class="help-block">
                                            @if (config('mail.reply_to.address') == '')
                                                <p class="text-warning">
                                                    <x-icon type="warning"/> {{ trans('admin/settings/general.mail_test_no_email') }}
                                                </p>
                                            @else
                                                <p>{{ trans('admin/settings/general.mail_test_help', ['replyto' => config('mail.reply_to.address')]) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Load images in emails -->
                                <x-form.checkbox-row
                                    name="show_images_in_email"
                                    :label="trans('admin/settings/general.show_images_in_email')"
                                    :item="$setting"
                                />
                            </fieldset>


                            <fieldset name="checkin-preferences">
                                <x-form.legend>
                                    {{ trans('admin/settings/general.legends.checkin') }}
                                </x-form.legend>

                                <!-- Require Notes on checkin/checkout checkbox -->
                                <x-form.checkbox-row
                                    name="require_checkinout_notes"
                                    :label="trans('admin/settings/general.require_checkinout_notes')"
                                    :item="$setting"
                                    :help_text="trans('admin/settings/general.require_checkinout_notes_help_text')"
                                />
                            </fieldset>


                            <fieldset name="dashboard">
                                <x-form.legend>
                                    {{ trans('admin/settings/general.legends.dashboard') }}
                                </x-form.legend>

                                <!-- login text -->
                                <x-form.row
                                    name="login_note"
                                    :label="trans('admin/settings/general.login_note')"
                                    help_html="{!! trans('admin/settings/general.login_note_help') !!}"
                                >
                                    <x-slot:input>
                                        <x-input.textarea
                                            name="login_note"
                                            :value="old('login_note', $setting->login_note)"
                                            :placeholder="trans('admin/settings/general.login_note_placeholder')"
                                            rows="2"
                                            aria-label="login_note"
                                            :disabled="config('app.lock_passwords')"
                                        />
                                        <x-demo-lock>{{ trans('general.feature_disabled') }}</x-demo-lock>
                                    </x-slot:input>
                                </x-form.row>

                                <!-- dash chart -->
                                <x-form.row
                                    name="dash_chart_type"
                                    :label="trans('general.pie_chart_type')"
                                >
                                    <x-slot:input>
                                        <x-input.select
                                            name="dash_chart_type"
                                            :options="['name' => 'Status Label Name', 'type' => 'Status Label Type']"
                                            :selected="old('dash_chart_type', $setting->dash_chart_type)"
                                            style="width: 80%"
                                        />
                                    </x-slot:input>
                                </x-form.row>

                                <!-- dashboard text -->
                                <x-form.row
                                    name="dashboard_message"
                                    :label="trans('admin/settings/general.dashboard_message')"
                                >
                                    <x-slot:input>
                                        <x-input.textarea
                                            name="dashboard_message"
                                            :value="old('dashboard_message', $setting->dashboard_message)"
                                            rows="2"
                                            aria-label="dashboard_message"
                                            :disabled="config('app.lock_passwords')"
                                        />
                                        <x-demo-lock>{{ trans('general.feature_disabled') }}</x-demo-lock>
                                        <p class="help-block">
                                            {{ trans('admin/settings/general.dashboard_message_help') }}
                                        </p>
                                        <x-form.help name="dashboard_message" icon="markdown">
                                            {{ trans('general.markdown') }}
                                        </x-form.help>
                                    </x-slot:input>
                                </x-form.row>
                            </fieldset>


                            <fieldset>
                                <x-form.legend>
                                    {{ trans('admin/settings/general.legends.misc') }}
                                </x-form.legend>

                                <!-- Privacy Policy Footer -->
                                <x-form.row
                                    name="privacy_policy_link"
                                    :label="trans('admin/settings/general.privacy_policy_link')"
                                    :help_text="trans('admin/settings/general.privacy_policy_link_help')"
                                >
                                    <x-slot:input>
                                        <x-input.text
                                            name="privacy_policy_link"
                                            :value="old('privacy_policy_link', $setting->privacy_policy_link)"
                                            :disabled="config('app.lock_passwords')"
                                        />
                                        <x-demo-lock>{{ trans('general.feature_disabled') }}</x-demo-lock>
                                    </x-slot:input>
                                </x-form.row>

                        <!-- Depreciation method -->
                               <div class="form-group {{ $errors->has('depreciation_method') ? 'error' : '' }}">

                                   <label for="depreciation_method" class="col-md-3 control-label">{{ trans('admin/depreciations/general.depreciation_method') }}</label>

                                   <div class="col-md-8">
                                       <x-input.select
                                           name="depreciation_method"
                                           id="depreciation_method"
                                           :options="['default' => trans('admin/depreciations/general.linear_depreciation'), 'diminish' => trans('admin/depreciations/general.diminishing_depreciation'), 'half_1' => trans('admin/depreciations/general.half_1'), 'half_2' => trans('admin/depreciations/general.half_2')]"
                                           :selected="old('depreciation_method', $setting->depreciation_method)"
                                           style="width: 80%"
                                       />
                                   </div>
                               </div>
                               <!-- /.form-group -->

                                <!-- unique serial -->
                                <x-form.checkbox-row
                                    name="unique_serial"
                                    :label="trans('admin/settings/general.unique_serial')"
                                    :item="$setting"
                                    :help_text="trans('admin/settings/general.unique_serial_help_text')"
                                />

                                <!-- Manager View -->
                                <x-form.checkbox-row
                                    name="manager_view_enabled"
                                    :label="trans('admin/settings/general.manager_view_enabled_text')"
                                    :item="$setting"
                                    :help_text="trans('admin/settings/general.manager_view_enabled_help')"
                                />
                            </fieldset>


                        </div>
                    </div> <!--/.box-body-->
                    <div class="box-footer">
                        <div class="text-left col-md-6">
                            <a class="btn btn-link text-left" href="{{ route('settings.index') }}">{{ trans('button.cancel') }}</a>
                        </div>
                        <div class="text-right col-md-6">
                            <button type="submit" class="btn btn-primary"><x-icon type="checkmark" /> {{ trans('general.save') }}</button>
                        </div>
                    </div>
                </div> <!-- /box -->
            </div> <!-- /.col-md-8-->
        </div>


    </form>

@stop

@section('moar_scripts')
    <!-- bootstrap color picker -->
    <script nonce="{{ csrf_token() }}">
        //color picker with addon
        $(".header-color").colorpicker();
        // toggle the disabled state of asset id prefix
        $('#auto_increment_assets').on('ifChecked', function(){
            $('#auto_increment_prefix').prop('disabled', false).focus();
        }).on('ifUnchecked', function(){
            $('#auto_increment_prefix').prop('disabled', true);
        });


        // Test Mail
        $("#mailtest").click(function(){
            $("#mailtestrow").removeClass('text-success');
            $("#mailtestrow").removeClass('text-danger');
            $("#mailtesticon").html('');
            $("#mailteststatus").html('');
            $('#mailteststatus-error').html('');
            $("#mailtesticon").html('<i class="fas fa-spinner spin"></i> {{ trans('admin/settings/message.mail.sending') }}');
            $.ajax({
                url: '{{ route('api.settings.mailtest') }}',
                type: 'POST',
                headers: {
                    "X-Requested-With": 'XMLHttpRequest',
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content')
                },
                data: {},
                dataType: 'json',

                success: function (data) {
                    console.dir(data);
                    $("#mailtesticon").html('');
                    $("#mailteststatus").html('');
                    $('#mailteststatus-error').html('');
                    $("#mailteststatus").removeClass('text-danger');
                    $("#mailteststatus").addClass('text-success');
                    if (data.message) {
                        $("#mailteststatus").html('<i class="fas fa-check text-success"></i> ' + data.message);
                    } else {
                        $("#mailteststatus").html('<i class="fas fa-check text-success"></i> {{ trans('admin/settings/message.mail.success') }}');
                    }
                },

                error: function (data) {

                    $("#mailtesticon").html('');
                    $("#mailteststatus").html('');
                    $('#mailteststatus-error').html('');
                    $("#mailteststatus").removeClass('text-success');
                    $("#mailteststatus").addClass('text-danger');
                    $("#mailtesticon").html('<i class="fas fa-exclamation-triangle text-danger"></i>');
                    $('#mailteststatus').html('{{ trans('admin/settings/message.mail.error') }}');
                    if (data.responseJSON) {
                        if (data.responseJSON.messages) {
                            $('#mailteststatus-error').html('Error: ' + data.responseJSON.messages);
                        } else {
                            $('#mailteststatus-error').html('{{ trans('admin/settings/message.mail.additional') }}');
                        }
                    } else {
                        console.dir(data);
                    }

                }


            });
        });

    </script>
@stop
