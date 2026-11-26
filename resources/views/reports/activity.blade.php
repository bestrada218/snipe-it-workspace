@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('general.activity_report') }} 
@parent
@stop

@section('header_right')
    <form method="POST" action="{{ route('reports.activity.post') }}" accept-charset="UTF-8" class="form-horizontal">
    {{csrf_field()}}
        <button type="submit" class="btn btn-theme">
        <x-icon type="download" />
        {{ trans('general.download_all') }}
    </button>
    </form>
@stop

{{-- Page content.
     $typeFilters and $actionTypes are prepared in
     ReportsController::getActivityReport from the ActivityItemType and
     ActionType enums. --}}
@section('content')
    <x-container>
        <x-box>
                {{-- Standalone filter row above the table (not wired via
                     bs-table's data-toolbar) so the filters don't share
                     a row with the search / columns / export / refresh
                     controls on narrow viewports. No overflow property, because
                     overflow on either axis creates a clipping region
                     that eats the datepicker popup when it opens below
                     the input. --}}
                {{-- All four multi-selects use the shared .js-select2-
                     checkboxes init in snipeit.js: compact "N selected"
                     collapsed state, checkbox rendering in the open
                     dropdown, badge strip below with X-remove, and
                     Select All / Clear buttons. Static mode (no data-
                     endpoint) reads from the pre-rendered <option>
                     tags. The user picker is AJAX against the users
                     selectlist. --}}
                {{-- Combined badge strip for all four filters. Each
                     widget's own .js-select2-checkboxes-badges div
                     is redirected into this shared container by
                     snipeit.js (via data-badges-container). The CSS
                     below makes those per-widget wrappers display:
                     contents so all badges visually render in a
                     single flex-wrap row below the toolbar. --}}
                <style>
                    #activityReportFilterBadges {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 4px;
                        padding: 0 0 10px;
                    }
                    #activityReportFilterBadges .js-select2-checkboxes-badges-shared {
                        display: contents;
                        margin: 0;
                    }
                </style>
                <div id="activityReportToolbar" style="padding: 5px 0 10px; display: flex; gap: 8px; align-items: flex-start; flex-wrap: wrap;">
                    <div style="flex: 1 1 160px; min-width: 0;">
                        <select
                            id="activityReportItemTypeFilter"
                            class="js-select2-checkboxes form-control" data-badges-container="#activityReportFilterBadges"
                            aria-label="{{ trans('general.item_type_label') }}"
                            data-placeholder="{{ trans('general.item_type_label') }}"
                            data-select-all="1"
                            data-select-all-label="{{ trans('general.select_all') }}"
                            data-clear-all-label="{{ trans('general.deselect_all') }}"
                            multiple
                            style="width: 100%;"
                        >
                            @foreach ($typeFilters as $filter)
                                <option value="{{ $filter['value'] }}" data-icon="{{ $filter['icon'] }}">{{ $filter['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="flex: 1 1 160px; min-width: 0;">
                        <select
                            id="activityReportTargetTypeFilter"
                            class="js-select2-checkboxes form-control" data-badges-container="#activityReportFilterBadges"
                            aria-label="{{ trans('general.target_type_label') }}"
                            data-placeholder="{{ trans('general.target_type_label') }}"
                            data-select-all="1"
                            data-select-all-label="{{ trans('general.select_all') }}"
                            data-clear-all-label="{{ trans('general.deselect_all') }}"
                            multiple
                            style="width: 100%;"
                        >
                            @foreach ($typeFilters as $filter)
                                <option value="{{ $filter['value'] }}" data-icon="{{ $filter['icon'] }}">{{ $filter['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="flex: 1 1 160px; min-width: 0;">
                        <select
                            id="activityReportActionTypeFilter"
                            class="js-select2-checkboxes form-control" data-badges-container="#activityReportFilterBadges"
                            aria-label="{{ trans('general.action_type_label') }}"
                            data-placeholder="{{ trans('general.action_type_label') }}"
                            data-select-all="1"
                            data-select-all-label="{{ trans('general.select_all') }}"
                            data-clear-all-label="{{ trans('general.deselect_all') }}"
                            multiple
                            style="width: 100%;"
                        >
                            @foreach ($actionTypes as $actionType)
                                <option value="{{ $actionType }}">{{ in_array($actionType, ['logged in', 'logged out'], true) ? trans('general.'.str_replace(' ', '_', $actionType)) : ucwords($actionType) }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- AJAX user picker on the same .js-select2-
                         checkboxes init. Searches against the users
                         selectlist endpoint (FMCS-scoped by
                         Company::scopeCompanyables). The initial-load
                         JS reads created_by from the URL and hydrates
                         chips for each matching user. --}}
                    <div style="flex: 1 1 170px; min-width: 0;">
                        <select
                            id="activityReportCreatedByFilter"
                            class="js-select2-checkboxes" data-badges-container="#activityReportFilterBadges"
                            data-endpoint="users"
                            data-placeholder="{{ trans('general.created_by_label') }}"
                            aria-label="{{ trans('general.created_by_label') }}"
                            multiple
                            style="width: 100%;"
                        >
                        </select>
                    </div>

                    <div style="flex: 1 1 135px; min-width: 0;">
                        <x-input.datepicker
                            id="activityReportCreatedAtFrom"
                            name="created_at_from"
                            :placeholder="trans('general.date_from')"
                        />
                    </div>
                    <div style="flex: 1 1 135px; min-width: 0;">
                        <x-input.datepicker
                            id="activityReportCreatedAtTo"
                            name="created_at_to"
                            :placeholder="trans('general.date_to')"
                        />
                    </div>
                </div>
                <div id="activityReportFilterBadges" aria-live="polite"></div>
                <div id="activityReportColumnControls" class="activity-layout-tools">
                    <p id="activityColumnInstructions">{{ trans('activity-log.drag_instructions') }}</p>
                    <button type="button" class="btn btn-link" id="resetActivityColumns">{{ trans('activity-log.reset_columns') }}</button>
                    <span id="activityColumnsStatus" role="status"></span>
                    <button type="button" class="btn btn-link" id="retryActivityColumns" hidden>{{ trans('activity-log.retry_columns') }}</button>
                </div>
                <table
                    data-columns="{{ json_encode(\App\Presenters\HistoryPresenter::activityReportColumns(auth()->user()->activity_report_columns ?? [])) }}"
                        data-cookie-id-table="activityReport-{{ auth()->id() }}"
                        data-cookies-enabled='["bs.table.pageList", "bs.table.pageNumber", "bs.table.sortOrder", "bs.table.sortName", "bs.table.searchText"]'
                        data-id-table="activityReport"
                        data-side-pagination="server"
                        data-advanced-search="false"
                        data-mobile-responsive="false"
                        data-sort-order="desc"
                        data-sort-name="created_at"
                        id="activityReport"
                    data-url="{{ $canManageReports ? route('api.reports.inventory.activity') : route('api.dashboard.activity') }}"
                    class="table table-striped snipe-table"
                        data-export-options='{
                        "fileName": "activity-report-{{ date('Y-m-d') }}",
                        "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                        }'>
                </table>
        </x-box>
    </x-container>
@stop


@section('moar_scripts')
@include ('partials.bootstrap-table', ['exportFile' => 'activity-export', 'search' => true])

<script nonce="{{ csrf_token() }}">
    const $activityTable = $('#activityReport');
    const activityTable = $activityTable[0];
    const $columnsStatus = $('#activityColumnsStatus');
    const $retryColumns = $('#retryActivityColumns');
    const columnsUrl = @json(route('profile.activity-report-columns.update'));
    const defaultColumns = {{ \Illuminate\Support\Js::from(\App\Presenters\HistoryPresenter::activityReportColumns()) }};
    const handleLabels = {
        move: @json(trans('activity-log.move_column')),
        resize: @json(trans('activity-log.resize_column')),
    };
    const getActivityColumns = () => $activityTable.bootstrapTable('getOptions').columns[0];
    const getActivityHeaders = () => Array.from(activityTable.querySelectorAll('thead th[data-field]'));
    const clampColumnWidth = width => Math.min(1600, Math.max(48, Math.round(width)));

    let gesture = null;
    let pendingLayout = null;
    let failedLayout = null;
    let savingLayout = false;

    function setActivityTableWidth(columns) {
        const width = columns.reduce((total, column) => total + (column.visible ? Number(column.width) : 0), 0);
        $activityTable.css('min-width', width);
    }

    function addActivityHandles() {
        const columns = getActivityColumns();
        setActivityTableWidth(columns);

        const titles = new Map(columns.map(column => [column.field, column.title]));
        for (const header of getActivityHeaders()) {
            if (header.querySelector('.activity-column-handle')) {
                continue;
            }
            for (const kind of ['move', 'resize']) {
                const handle = document.createElement('button');
                const label = handleLabels[kind] + ' · ' + titles.get(header.dataset.field);
                handle.type = 'button';
                handle.className = 'activity-column-handle activity-column-' + kind;
                handle.dataset.columnHandle = kind;
                handle.title = label;
                handle.setAttribute('aria-label', label);
                handle.setAttribute('aria-describedby', 'activityColumnInstructions');
                header.append(handle);
            }
        }
    }

    function saveActivityLayout() {
        if (savingLayout || pendingLayout === null) {
            return;
        }

        const layout = pendingLayout;
        pendingLayout = null;
        failedLayout = null;
        savingLayout = true;
        $retryColumns.prop('hidden', true);
        $columnsStatus.text(@json(trans('activity-log.columns_saving')));

        $.ajax({
            url: columnsUrl,
            method: 'PUT',
            contentType: 'application/json',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            data: JSON.stringify({columns: layout}),
        }).done(function () {
            $columnsStatus.text(layout.length ? @json(trans('activity-log.columns_saved')) : @json(trans('activity-log.columns_reset')));
        }).fail(function () {
            failedLayout = layout;
            $columnsStatus.text(@json(trans('activity-log.columns_error')));
            $retryColumns.prop('hidden', false);
        }).always(function () {
            savingLayout = false;
            saveActivityLayout();
        });
    }

    function queueActivityLayout(columns) {
        pendingLayout = columns.map(({field, width, visible}) => ({field, width, visible}));
        saveActivityLayout();
    }

    function applyActivityLayout(columns, reset = false) {
        const scrollLeft = activityTable.parentElement.scrollLeft;
        $activityTable.bootstrapTable('refreshOptions', {columns: [columns]});
        activityTable.parentElement.scrollLeft = scrollLeft;
        queueActivityLayout(reset ? [] : columns);
    }

    function moveActivityColumn(field, target, side) {
        if (!target || target === field) {
            return;
        }

        const columns = getActivityColumns();
        const from = columns.findIndex(column => column.field === field);
        const [column] = columns.splice(from, 1);
        const to = columns.findIndex(column => column.field === target) + (side === 'after' ? 1 : 0);
        if (from !== to) {
            columns.splice(to, 0, column);
            applyActivityLayout(columns);
        }
    }

    function updateDropTarget() {
        const state = gesture;
        const body = activityTable.parentElement;
        const {left, right} = body.getBoundingClientRect();
        const x = Math.max(left + 1, Math.min(right - 1, state.x));
        if (x < left + 24) {
            body.scrollLeft -= 10;
        } else if (x > right - 24) {
            body.scrollLeft += 10;
        }

        state.target?.removeAttribute('data-activity-drop');
        state.target = getActivityHeaders().find(header => {
            const box = header.getBoundingClientRect();
            return box.left <= x && box.right > x;
        });
        if (state.target && state.target !== state.header) {
            const box = state.target.getBoundingClientRect();
            state.side = (x < (box.left + box.right) / 2) === (state.direction === 1) ? 'before' : 'after';
            state.target.dataset.activityDrop = state.side;
        }
    }

    function trackDropTarget() {
        updateDropTarget();
        gesture.frame = requestAnimationFrame(trackDropTarget);
    }

    function previewColumnWidth(state, x) {
        const columns = getActivityColumns();
        const column = columns.find(column => column.field === state.field);
        const width = clampColumnWidth(column.width + (x - state.startX) * state.direction);
        state.header.style.width = width + 'px';
        column.width = width;
        setActivityTableWidth(columns);
        return columns;
    }

    $activityTable.on('post-header.bs.table', addActivityHandles);
    $activityTable.on('column-switch.bs.table column-switch-all.bs.table', function () {
        addActivityHandles();
        queueActivityLayout(getActivityColumns());
    });

    activityTable.addEventListener('pointerdown', function (event) {
        const handle = event.target.closest('.activity-column-handle');
        if (!handle || event.button !== 0 || gesture) {
            return;
        }

        const header = handle.closest('th');
        gesture = {
            header,
            field: header.dataset.field,
            kind: handle.dataset.columnHandle,
            pointerId: event.pointerId,
            startX: event.clientX,
            x: event.clientX,
            direction: getComputedStyle(activityTable).direction === 'rtl' ? -1 : 1,
        };
        activityTable.setPointerCapture(event.pointerId);
    });

    activityTable.addEventListener('pointermove', function (event) {
        const state = gesture;
        if (!state || event.pointerId !== state.pointerId) {
            return;
        }

        state.x = event.clientX;
        if (!state.active && Math.abs(state.x - state.startX) < 4) {
            return;
        }

        state.active = true;
        activityTable.dataset.columnGesture = state.kind;
        state.header.classList.add('activity-column-active');
        if (state.kind === 'resize') {
            previewColumnWidth(state, state.x);
        } else if (!state.frame) {
            trackDropTarget();
        }
    });

    function finishActivityGesture(event) {
        if (!gesture || event.pointerId !== gesture.pointerId) {
            return;
        }

        const state = gesture;
        gesture = null;
        cancelAnimationFrame(state.frame);
        state.target?.removeAttribute('data-activity-drop');
        state.header.classList.remove('activity-column-active');
        delete activityTable.dataset.columnGesture;
        if (!state.active) {
            return;
        }

        if (event.type !== 'pointerup') {
            const columns = getActivityColumns();
            state.header.style.width = columns.find(column => column.field === state.field).width + 'px';
            setActivityTableWidth(columns);
        } else if (state.kind === 'resize') {
            applyActivityLayout(previewColumnWidth(state, event.clientX));
        } else {
            moveActivityColumn(state.field, state.target?.dataset.field, state.side);
        }
    }

    for (const type of ['pointerup', 'pointercancel', 'lostpointercapture']) {
        activityTable.addEventListener(type, finishActivityGesture);
    }

    $activityTable.on('keydown', '.activity-column-handle', function (event) {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
            return;
        }

        event.preventDefault();
        const direction = getComputedStyle(activityTable).direction === 'rtl' ? -1 : 1;
        const step = (event.key === 'ArrowLeft' ? -1 : 1) * direction;
        const field = this.closest('th').dataset.field;
        const kind = this.dataset.columnHandle;
        if (kind === 'resize') {
            const columns = getActivityColumns();
            const column = columns.find(column => column.field === field);
            column.width = clampColumnWidth(column.width + step * 16);
            applyActivityLayout(columns);
        } else {
            const headers = getActivityHeaders();
            const index = headers.findIndex(header => header.dataset.field === field);
            moveActivityColumn(field, headers[index + step]?.dataset.field, step < 0 ? 'before' : 'after');
        }
        getActivityHeaders().find(header => header.dataset.field === field)
            ?.querySelector('[data-column-handle="' + kind + '"]')?.focus();
    });

    $('#resetActivityColumns').on('click', function () {
        applyActivityLayout(defaultColumns.map(column => ({...column})), true);
    });

    $retryColumns.on('click', function () {
        pendingLayout = failedLayout;
        saveActivityLayout();
    });

    // Activity report toolbar. Six filters all round-trip through the
    // browser URL so the view is shareable, bookmarkable, and
    // back/forward restores the prior filter state.
    //
    //   item_type       select2 of allowlisted types + icon chips
    //   target_type     same list, filters on the target column
    //   action_type     select2 of ActionType enum values
    //   created_by      ajax user picker (FMCS-scoped by snipeit.js)
    //   created_at_from datepicker, start-of-day inclusive on server
    //   created_at_to   datepicker, end-of-day inclusive on server
    //
    // Wrapped in $(function(){}) so snipeit.js has already auto-inited
    // the .js-data-ajax user picker and the datepickers by the time we
    // bind our change handlers. Change handlers are delegated off the
    // toolbar container (not bound directly) so handler attachment
    // does not depend on each select being present / wrapped at bind
    // time.
    $(function () {
        var baseUrl = @json($canManageReports ? route('api.reports.inventory.activity') : route('api.dashboard.activity'));
        var userLookupUrl = @json(route('api.users.selectlist'));

        var $itemFilter = $('#activityReportItemTypeFilter');
        var $targetFilter = $('#activityReportTargetTypeFilter');
        var $actionTypeFilter = $('#activityReportActionTypeFilter');
        var $createdByFilter = $('#activityReportCreatedByFilter');
        var $createdAtFrom = $('#activityReportCreatedAtFrom');
        var $createdAtTo = $('#activityReportCreatedAtTo');

        // Flag flipped on while popstate / initial-load hydration is
        // writing values back into the dropdowns + datepickers from the
        // URL. The change / dp.change handlers skip URL re-push and
        // table refetch while this is on, since hydration already
        // batches those at the end.
        var isHydrating = false;

        // Build the current filter params. Multi-selects return arrays
        // from .val() (jQuery converts to [], [a], [a,b], etc.) and
        // $.param serializes those as ?item_type[]=a&item_type[]=b.
        // Empty arrays and empty strings are omitted so we never emit
        // a bare ?item_type= with no value.
        function currentFilterParams() {
            var params = {};
            var multiFilters = {
                item_type: $itemFilter.val(),
                target_type: $targetFilter.val(),
                action_type: $actionTypeFilter.val(),
                created_by: $createdByFilter.val(),
            };
            Object.keys(multiFilters).forEach(function (key) {
                var val = multiFilters[key];
                if (Array.isArray(val) && val.length > 0) {
                    params[key] = val;
                }
            });
            if ($createdAtFrom.val()) params.created_at_from = $createdAtFrom.val();
            if ($createdAtTo.val()) params.created_at_to = $createdAtTo.val();
            return params;
        }

        // Refetch the bs-table body with the current filter params.
        // The URL is updated by bs-table's addrbar extension via the
        // patched updateHistoryState below. Pushing our own history
        // state here would be clobbered on the next onLoadSuccess
        // (addrbar re-pushes with its own known keys only).
        function refreshActivityReport() {
            var qs = $.param(currentFilterParams());
            $('#activityReport').bootstrapTable('refresh', {
                url: baseUrl + (qs ? '?' + qs : ''),
                silent: true,
            });
        }

        // Set a (multi) select's value from hydration. Accepts either
        // a scalar or an array. Triggers plain 'change' so select2
        // (which binds its UI sync on plain 'change', not on namespaced
        // events) re-renders chips. The isHydrating flag keeps the
        // change handler from re-pushing URL state in a loop.
        function setSelect($select, value) {
            var normalized = value == null ? [] : (Array.isArray(value) ? value : [value]);
            var current = $select.val() || [];
            if (!Array.isArray(current)) {
                current = current ? [current] : [];
            }
            if (current.length === normalized.length && current.every(function (v, i) { return String(v) === String(normalized[i]); })) {
                return;
            }
            $select.val(normalized).trigger('change');
        }

        // Set a datepicker's value without opening the picker. Blade's
        // datepicker component wraps the input in a div[data-provide=
        // datetimepicker] bound by snipeit.js, so we set the underlying
        // input value and let the picker read it on next open.
        function setDate($dateInput, value) {
            if ($dateInput.val() === (value || '')) {
                return;
            }
            $dateInput.val(value || '');
        }

        // Hydrate the created_by multi-picker from an array of ids in
        // the URL. The ajax picker starts empty, so each id needs its
        // user record fetched to populate the chip label. One request
        // per id against the users selectlist with search=id. Chips
        // appended as they come back, which is fine because select2
        // re-renders on each append.
        function hydrateCreatedByFromUrl(userIds) {
            if (!userIds || userIds.length === 0) {
                return;
            }
            userIds.forEach(function (userId) {
                if (!userId) {
                    return;
                }
                $.ajax({
                    url: userLookupUrl,
                    data: { search: userId },
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    },
                }).done(function (response) {
                    var rows = (response && response.results) || [];
                    var match = rows.find(function (r) { return String(r.id) === String(userId); });
                    if (!match) {
                        return;
                    }
                    var $opt = new Option(match.text, match.id, true, true);
                    $createdByFilter.append($opt).trigger('change');
                });
            });
        }

        // select2 init for all four selects is handled by snipeit.js's
        // .js-select2-checkboxes auto-init path. That gives us the
        // compact "N selected" collapsed state, dropdown-stays-open on
        // tick, badge strip with X-remove, and the Select All / Clear
        // buttons for free.

        // Event delegation off the toolbar container so handler
        // attachment is independent of whether each select / datepicker
        // has been wrapped by select2 / eonasdan yet. 'change' fires on
        // every select interaction and on direct typing in a datepicker
        // input. 'dp.change' is eonasdan's own event, fired on the
        // .input-group.date wrapper for calendar picks.
        var filterHandler = function () {
            if (isHydrating) {
                return;
            }
            refreshActivityReport();
        };
        $('#activityReportToolbar')
            .on('change', 'select, input', filterHandler)
            .on('dp.change', '.input-group.date', filterHandler);

        // Patch bs-table's addrbar URL management to preserve our
        // custom filter params. addrbar owns the query string and
        // re-pushes it on every onLoadSuccess with only its own known
        // keys (page/size/sort/order/search), which would otherwise
        // clobber any custom params we push ourselves. Same pattern
        // the advanced-search deeplinking uses in bootstrap-table.blade.
        // Runs after bs-table init because the partial's $(function(){})
        // registers before ours.
        var preservedKeys = ['item_type', 'target_type', 'action_type', 'created_by', 'created_at_from', 'created_at_to'];
        var tableInstance = $('#activityReport').data('bootstrap.table');
        if (tableInstance && typeof tableInstance.updateHistoryState === 'function') {
            var origUpdateHistoryState = tableInstance.updateHistoryState;
            tableInstance.updateHistoryState = function () {
                var params = this.searchParams;
                if (params) {
                    // Strip both scalar (key) and array (key[]) forms
                    // so a cleared filter actually leaves the URL.
                    preservedKeys.forEach(function (k) {
                        params.delete(k);
                        params.delete(k + '[]');
                    });
                    var filterParams = currentFilterParams();
                    Object.keys(filterParams).forEach(function (k) {
                        var v = filterParams[k];
                        if (Array.isArray(v)) {
                            v.forEach(function (item) { params.append(k + '[]', item); });
                        } else {
                            params.set(k, v);
                        }
                    });
                }
                return origUpdateHistoryState.apply(this, arguments);
            };
        }

        // URL multi-value keys use the ?key[]=a&key[]=b convention
        // (what jQuery $.param emits for arrays and what Laravel
        // reads as an array). URLSearchParams.getAll('key[]') returns
        // the full list. Falls back to a single scalar lookup for
        // callers that encoded as ?key=val.
        function readMultiParam(urlParams, key) {
            var arr = urlParams.getAll(key + '[]');
            if (arr.length > 0) {
                return arr;
            }
            var single = urlParams.get(key);
            return single ? [single] : [];
        }

        function hasAnyFilterParam(urlParams) {
            var keys = ['item_type', 'target_type', 'action_type', 'created_by'];
            for (var i = 0; i < keys.length; i++) {
                if (urlParams.has(keys[i]) || urlParams.has(keys[i] + '[]')) {
                    return true;
                }
            }
            return urlParams.has('created_at_from') || urlParams.has('created_at_to');
        }

        window.addEventListener('popstate', function () {
            var p = new URLSearchParams(window.location.search);
            isHydrating = true;
            setSelect($itemFilter, readMultiParam(p, 'item_type'));
            setSelect($targetFilter, readMultiParam(p, 'target_type'));
            setSelect($actionTypeFilter, readMultiParam(p, 'action_type'));
            setDate($createdAtFrom, p.get('created_at_from'));
            setDate($createdAtTo, p.get('created_at_to'));
            // created_by needs its labels refetched since the ajax picker
            // only knows about options it has already loaded.
            $createdByFilter.empty();
            hydrateCreatedByFromUrl(readMultiParam(p, 'created_by'));
            isHydrating = false;
            refreshActivityReport();
        });

        var initialParams = new URLSearchParams(window.location.search);
        if (hasAnyFilterParam(initialParams)) {
            isHydrating = true;
            setSelect($itemFilter, readMultiParam(initialParams, 'item_type'));
            setSelect($targetFilter, readMultiParam(initialParams, 'target_type'));
            setSelect($actionTypeFilter, readMultiParam(initialParams, 'action_type'));
            setDate($createdAtFrom, initialParams.get('created_at_from'));
            setDate($createdAtTo, initialParams.get('created_at_to'));
            hydrateCreatedByFromUrl(readMultiParam(initialParams, 'created_by'));
            isHydrating = false;
            refreshActivityReport();
        }
    });
</script>
@stop
