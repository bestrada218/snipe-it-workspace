<style>
    .workspace-ui {
        --workspace-background: #f5f4f7;
        --workspace-surface: #fff;
        --workspace-navigation: #fbfafc;
        --workspace-ink: #282332;
        --workspace-muted: #625b6d;
        --workspace-border: #e2dce7;
        --workspace-accent: #60406d;
        --workspace-tint: #f0e8f2;
        --workspace-primary: #60406d;
        --workspace-primary-hover: #4d305a;
        font-family: "Avenir Next", "Segoe UI", sans-serif;
        color: var(--workspace-ink);
        font-size: 14px;
    }

    [data-theme="dark"] .workspace-ui {
        --workspace-background: #211e27;
        --workspace-surface: #2a2631;
        --workspace-navigation: #26222d;
        --workspace-ink: #f4edf6;
        --workspace-muted: #c4b9ca;
        --workspace-border: #453b4d;
        --workspace-accent: #e1b9e9;
        --workspace-tint: #3e3047;
        --workspace-primary: #765184;
        --workspace-primary-hover: #664473;
    }

    .workspace-ui .wrapper,
    .workspace-ui .content-wrapper {
        background: var(--workspace-background);
    }

    .workspace-ui .content-header,
    .workspace-ui section.content {
        padding-inline: clamp(16px, 3vw, 38px);
    }

    .workspace-ui .content-header {
        padding-block: 28px 16px;
    }

    .workspace-ui .content-header .pagetitle {
        color: var(--workspace-ink);
        font-family: inherit;
        font-size: clamp(24px, 2.5vw, 32px);
        font-weight: 700;
        letter-spacing: -.035em;
        line-height: 1.18;
    }

    .workspace-ui .pagetitle .breadcrumb-item:not(.active) {
        font-size: .67em;
        font-weight: 600;
        letter-spacing: 0;
    }

    .workspace-ui .pagetitle .breadcrumb-item a {
        color: var(--workspace-accent);
    }

    .workspace-ui .pagetitle ul {
        margin-bottom: 0;
    }

    .workspace-ui .main-header .navbar {
        --main-theme-color: var(--workspace-surface);
        --nav-primary-text-color: var(--workspace-ink);
        --main-theme-hover: var(--workspace-tint);
        --nav-hover-text-color: var(--workspace-accent);
        background: var(--workspace-surface) !important;
        border-bottom: 1px solid var(--workspace-border);
    }

    .workspace-ui .main-header .navbar-brand,
    .workspace-ui .main-header .navbar .nav > li > a,
    .workspace-ui .main-header .navbar a.sidebar-toggle {
        color: var(--workspace-ink) !important;
    }

    .workspace-ui .main-header .navbar-brand {
        font-family: inherit;
        font-weight: 700;
        letter-spacing: -.025em;
    }

    .workspace-ui .main-header .navbar .nav > li > a:hover,
    .workspace-ui .main-header .navbar .nav > .active > a,
    .workspace-ui .main-header .navbar a.sidebar-toggle:hover {
        background: var(--workspace-tint) !important;
        color: var(--workspace-accent) !important;
    }

    .workspace-ui .main-header #tagSearch,
    .workspace-ui .main-header #topSearchButton {
        background: var(--workspace-navigation) !important;
        border: 1px solid var(--workspace-border) !important;
        color: var(--workspace-ink) !important;
    }

    .workspace-ui .main-header #tagSearch {
        border-radius: 6px 0 0 6px;
    }

    .workspace-ui .main-header #topSearchButton {
        border-radius: 0 6px 6px 0;
    }

    .workspace-ui .main-header #tagSearch::placeholder {
        color: var(--workspace-muted);
    }

    .workspace-ui .main-sidebar {
        background: var(--workspace-navigation);
        border-inline-end: 1px solid var(--workspace-border);
    }

    .workspace-ui .sidebar-menu > li:hover,
    .workspace-ui .sidebar-menu > li > .treeview-menu,
    .workspace-ui .sidebar-menu .treeview-menu > li {
        background: var(--workspace-navigation);
    }

    .workspace-ui .sidebar-menu > li > a,
    .workspace-ui .sidebar-menu .treeview-menu > li > a {
        border-left-color: transparent;
        color: var(--workspace-muted) !important;
        font-weight: 600;
    }

    .workspace-ui .sidebar-menu > li > a {
        min-height: 40px;
        padding-block: 10px;
    }

    .workspace-ui .sidebar-menu > li > a:hover,
    .workspace-ui .sidebar-menu > li.active > a,
    .workspace-ui .sidebar-menu .treeview-menu > li > a:hover,
    .workspace-ui .sidebar-menu .treeview-menu > li.active > a {
        background: var(--workspace-tint) !important;
        color: var(--workspace-accent) !important;
    }

    .workspace-ui .sidebar-menu > li > .workspace-replacement-nav,
    .workspace-ui .sidebar-menu .treeview-menu > li > .workspace-replacement-nav {
        display: flex;
        align-items: baseline;
        gap: 4px;
        white-space: normal;
    }

    .workspace-replacement-nav > .fa-fw {
        flex-shrink: 0;
    }

    .workspace-ui .sidebar-menu > li.active > a {
        border-inline-start-color: var(--workspace-accent);
    }

    .workspace-ui .content .box {
        --box-bg: var(--workspace-surface);
        --box-header-bottom-border-color: var(--workspace-border);
        background: var(--workspace-surface);
        border: 1px solid var(--workspace-border);
        border-radius: 8px;
        box-shadow: none;
    }

    .workspace-ui .content .box-title {
        font-family: inherit;
        font-weight: 700;
    }

    #locationsListingTable > tbody > tr > td.text-padding-number-cell,
    #locationsListingTable > tfoot > tr > th.text-padding-number-cell,
    #locationsListingTable > tfoot > tr > th.text-padding-number-cell > .th-inner {
        padding-inline: 8px !important;
        text-align: center;
        font-variant-numeric: tabular-nums;
    }

    #locationsListingTable > tfoot > tr > th.text-padding-number-cell {
        padding-inline: 0 !important;
    }

    #locationsListingTable.snipe-table--sticky-right-1:has(> thead > tr > th:last-child[data-field="actions"]) > tfoot > tr > :last-child {
        position: sticky;
        right: var(--sticky-right-offset-1, 0);
        z-index: 2;
        background: var(--workspace-surface);
    }

    #locationsListingTable:has(> thead > tr > th:first-child.bs-checkbox) > tfoot > tr > :first-child {
        position: sticky;
        left: 0;
        z-index: 2;
        background: var(--workspace-surface);
    }

    .location-hierarchy-name {
        display: inline-flex;
        align-items: baseline;
        gap: .4rem;
        margin-inline-start: calc(var(--location-depth) * 1.15rem);
    }

    .location-hierarchy-name::before {
        content: "";
        align-self: center;
        inline-size: .65rem;
        block-size: .65rem;
        flex: none;
        border-inline-start: 1px solid var(--workspace-muted);
        border-block-end: 1px solid var(--workspace-muted);
    }

    .checkout-asset-tag .control-label {
        padding-top: 14px;
    }

    .checkout-asset-tag-value {
        display: inline-block;
        padding: 4px 12px;
        border: 1px solid var(--workspace-border);
        border-radius: 6px;
        background: var(--workspace-tint);
        color: var(--workspace-ink);
        font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: .02em;
    }

    .checkout-asset-tag-name {
        margin: 2px 0 0;
        color: var(--workspace-muted);
        font-weight: 600;
    }

    .workspace-ui .main-footer {
        --main-footer-bg-color: var(--workspace-background);
        --main-footer-top-border-color: var(--workspace-border);
        --main-footer-text-color: var(--workspace-muted);
    }

    .workspace-ui :is(.workspace-metric, .workspace-replacement-alert, .workspace-empty-actions a, .sidebar-menu a, #tagSearch, .workspace-asset-form button):focus-visible {
        outline: 2px solid var(--workspace-accent);
        outline-offset: -2px;
    }

    :is(.workspace-metrics, .workspace-replacement-windows) {
        display: flex;
        flex-wrap: wrap;
    }

    .workspace-metrics > .workspace-metric,
    .workspace-replacement-windows > .workspace-replacement-alert {
        flex: 1 1 min(100%, 156px);
    }

    .workspace-metrics {
        gap: 1px;
        overflow: hidden;
        margin-bottom: 22px;
        border: 1px solid var(--workspace-border);
        border-radius: 8px;
        background: var(--workspace-border);
    }

    .workspace-ui .workspace-metric {
        display: grid;
        grid-template-columns: 30px minmax(0, 1fr);
        gap: 4px 11px;
        align-items: center;
        min-width: 0;
        padding: 15px;
        color: var(--workspace-ink) !important;
        background: var(--workspace-surface);
        text-decoration: none;
    }

    .workspace-ui .workspace-metric:hover {
        background: var(--workspace-navigation);
    }

    .workspace-metric-icon {
        display: grid;
        grid-row: span 2;
        place-items: center;
        width: 30px;
        height: 30px;
        border-radius: 6px;
        background: var(--workspace-tint);
        color: var(--workspace-accent);
    }

    .workspace-metric-label {
        color: var(--workspace-muted);
        font-size: 13px;
        font-weight: 600;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }

    .workspace-metric-value {
        font-size: 23px;
        font-variant-numeric: tabular-nums;
        font-weight: 700;
        letter-spacing: -.035em;
        line-height: 1;
    }

    .activity-action-title {
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .activity-event-details > * + * {
        margin-top: 8px;
    }

    .activity-attachment {
        overflow-wrap: anywhere;
    }

    .activity-detail-muted {
        display: block;
        color: var(--workspace-muted);
        font-size: .9em;
    }

    .activity-change-list {
        margin: 0;
        padding: 0;
        list-style: none;
        overflow-wrap: anywhere;
        white-space: pre-wrap;
    }

    .activity-change-list li + li {
        margin-top: 7px;
    }

    .activity-change-field {
        display: block;
    }

    .activity-change-before,
    .activity-change-list em {
        color: var(--workspace-muted);
    }

    .dashboard-activity-details {
        min-width: 210px;
    }

    #activityReport {
        table-layout: fixed;
    }

    #activityReport :is(th, td),
    :is(#activityReport, #dashActivityReport) nobr {
        overflow-wrap: anywhere;
        white-space: normal;
    }

    .activity-layout-tools {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: 4px 12px;
        padding: 8px 0 6px;
        color: var(--workspace-muted);
        font-size: 13px;
    }

    .activity-layout-tools p {
        flex: 1 1 280px;
        margin: 0;
    }

    .activity-layout-tools .btn {
        padding: 2px 0;
        font-size: inherit;
    }

    .activity-layout-tools [role="status"]:empty {
        display: none;
    }

    #activityReport th[data-field] {
        position: relative;
    }

    .bootstrap-table:has(#activityReport) th .th-inner {
        padding-inline: 27px 13px;
        white-space: normal;
        text-overflow: clip;
        overflow-wrap: anywhere;
    }

    .bootstrap-table:has(#activityReport) th .th-inner.sortable {
        padding-inline-end: 29px;
        background-position: right 12px center;
    }

    .sticky-header-container .activity-column-handle {
        display: none;
    }

    .activity-column-handle {
        position: absolute;
        inset-block: 0;
        margin: 0;
        padding: 0;
        border: 0;
        background: transparent;
        color: var(--workspace-muted);
        touch-action: none;
        z-index: 1;
    }

    .activity-column-move {
        inset-inline-start: 2px;
        width: 22px;
        cursor: grab;
    }

    .activity-column-move::after {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 10px;
        height: 15px;
        transform: translate(-50%, -50%);
        background: radial-gradient(circle at center, currentColor 1px, transparent 1.2px) 0 0 / 5px 5px;
        content: "";
        opacity: .68;
    }

    .activity-column-resize {
        inset-inline-end: 0;
        width: 11px;
        cursor: col-resize;
    }

    .activity-column-resize::after {
        position: absolute;
        inset-block: 7px;
        inset-inline-end: 3px;
        width: 1px;
        background: currentColor;
        content: "";
        opacity: .42;
    }

    .activity-column-handle:is(:hover, :focus-visible) {
        background: var(--workspace-tint);
        color: var(--workspace-accent);
    }

    .activity-column-handle:focus-visible {
        outline: 2px solid var(--workspace-accent);
        outline-offset: -2px;
    }

    #activityReport[data-column-gesture] {
        user-select: none;
    }

    #activityReport[data-column-gesture="move"],
    #activityReport[data-column-gesture="move"] * {
        cursor: grabbing;
    }

    #activityReport[data-column-gesture="resize"],
    #activityReport[data-column-gesture="resize"] * {
        cursor: col-resize;
    }

    #activityReport .activity-column-active {
        background: var(--workspace-tint);
    }

    #activityReport [data-activity-drop]::before {
        position: absolute;
        inset-block: 0;
        width: 3px;
        background: var(--workspace-accent);
        content: "";
        pointer-events: none;
        z-index: 2;
    }

    #activityReport [data-activity-drop="before"]::before {
        inset-inline-start: 0;
    }

    #activityReport [data-activity-drop="after"]::before {
        inset-inline-end: 0;
    }

    .workspace-duplicate-warning {
        padding: 12px 14px;
        border: 1px solid var(--workspace-border);
        border-inline-start: 3px solid #ad7314;
        border-radius: 5px;
        background: var(--workspace-navigation);
        color: var(--workspace-ink);
    }

    .workspace-duplicate-warning p {
        margin: 4px 0 0;
    }

    .workspace-duplicate-dialog {
        width: min(470px, calc(100vw - 32px));
        padding: 22px;
        border: 1px solid var(--workspace-border);
        border-radius: 8px;
        background: var(--workspace-surface);
        color: var(--workspace-ink);
    }

    .workspace-duplicate-dialog::backdrop {
        background: rgb(22 17 26 / .55);
    }

    .workspace-duplicate-dialog h2 {
        margin: 0 0 12px;
        font-weight: 700;
        font-family: inherit;
        font-size: 22px;
    }

    .workspace-duplicate-dialog label {
        display: block;
        margin-top: 12px;
    }

    .workspace-duplicate-dialog {
        max-height: calc(100vh - 32px);
        overflow: auto;
    }

    .workspace-order-toggle.btn-link {
        padding: 0;
        font: inherit;
        color: inherit;
        text-align: left;
    }

    .workspace-shipment-toggle {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-height: 36px;
        max-width: 100%;
        margin: 0;
        font-weight: 500;
        cursor: pointer;
    }

    .workspace-shipment-toggle input[type="checkbox"] {
        flex: 0 0 auto;
        position: static;
        width: 20px;
        height: 20px;
        margin: 0;
        border-radius: 4px;
        background: var(--workspace-surface);
        color: var(--workspace-accent);
        transform: none;
        cursor: pointer;
    }

    .workspace-shipment-toggle input[type="checkbox"]:focus-visible {
        outline: 2px solid var(--workspace-accent);
        outline-offset: 3px;
    }

    .workspace-shipment-form .form-control[type="date"] {
        height: auto;
        min-height: 34px;
        line-height: 1.4;
    }

    .workspace-shipment-form .form-control[type="date"]::-webkit-date-and-time-value {
        margin: 0;
        text-align: start;
    }

    .workspace-shipment-form .form-control[type="file"] {
        height: auto;
        min-height: 34px;
        padding: 4px 8px;
    }

    .workspace-shipment-form input[type="file"]::file-selector-button {
        margin-inline-end: 8px;
        padding: 3px 8px;
        border: 0;
        border-radius: 4px;
        background: var(--workspace-tint);
        color: var(--workspace-ink);
        cursor: pointer;
    }

    .workspace-shipment-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 12px;
    }

    .workspace-shipment-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .workspace-shipment-item {
        display: block;
    }

    .workspace-shipment-status {
        display: inline-block;
        border-radius: 5px;
        padding: 4px 9px;
        color: var(--workspace-accent);
        background: var(--workspace-tint);
        font-weight: 600;
    }

    .workspace-shipment-status:is([data-status="due"], [data-status="late"]) {
        border-inline-start: 3px solid #ad7314;
    }

    .workspace-shipment-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 210px), 1fr));
        gap: 12px 20px;
        margin: 16px 0;
        overflow-wrap: anywhere;
    }

    .workspace-shipment-note {
        white-space: pre-wrap;
    }

    .workspace-shipment-action {
        padding-block: 12px;
        border-top: 1px solid var(--workspace-border);
    }

    .workspace-shipment-action summary {
        cursor: pointer;
        color: var(--workspace-accent);
        font-weight: 600;
    }

    .workspace-shipment-receipt summary.btn {
        display: inline-block;
        color: #fff;
    }

    .workspace-shipment-receipt form {
        margin-top: 12px;
    }

    .workspace-shipment-action label,
    .workspace-shipment-action .btn {
        margin-top: 8px;
    }

    .workspace-duplicate-group {
        border: 1px solid var(--workspace-border);
        border-radius: 8px;
        overflow: hidden;
    }

    .workspace-duplicate-group + .workspace-duplicate-group {
        margin-top: 10px;
    }

    .workspace-duplicate-group > summary {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto;
        align-items: center;
        gap: 12px;
        padding: 13px 16px;
        background: var(--workspace-navigation);
        cursor: pointer;
    }

    .workspace-duplicate-group > summary::-webkit-details-marker {
        display: none;
    }

    .workspace-duplicate-group > summary > span:first-child > * {
        display: block;
        overflow-wrap: anywhere;
    }

    .workspace-duplicate-disclosure {
        color: var(--workspace-accent);
        font-weight: 600;
    }

    .workspace-duplicate-group[open] .workspace-duplicate-disclosure i {
        transform: rotate(90deg);
    }

    .workspace-duplicate-count {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .workspace-duplicate-members {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr));
        gap: 10px;
        margin: 0;
        padding: 14px;
        border-top: 1px solid var(--workspace-border);
        list-style: none;
    }

    .workspace-duplicate-member {
        padding: 12px;
        min-width: 0;
        border: 1px solid var(--workspace-border);
        border-radius: 6px;
        overflow-wrap: anywhere;
    }

    .workspace-duplicate-item-heading {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 6px;
    }

    .workspace-duplicate-item-heading a > * {
        display: block;
    }

    .workspace-duplicate-missing {
        margin: 8px 0 0;
    }

    .workspace-duplicate-approval {
        display: grid;
        gap: 8px;
        margin: 8px 0 0;
    }

    .workspace-duplicate-approval dd {
        white-space: pre-wrap;
    }

    .workspace-duplicate-approvals {
        margin-top: 9px;
    }

    .workspace-duplicate-approvals summary {
        color: var(--workspace-accent);
        cursor: pointer;
    }

    .workspace-duplicate-recorded {
        margin-top: 9px;
        padding-top: 9px;
        border-top: 1px solid var(--workspace-border);
    }

    @media (max-width: 480px) {
        .workspace-duplicate-group > summary {
            grid-template-columns: minmax(0, 1fr) auto;
        }

        .workspace-duplicate-disclosure {
            grid-column: 1 / -1;
        }
    }

    .workspace-duplicate-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 16px;
    }

    .workspace-ui .workspace-replacement-panel .box-body {
        padding: 16px;
    }

    .workspace-replacement-windows {
        gap: 10px;
    }

    .workspace-ui .workspace-replacement-alert {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 14px;
        border: 1px solid var(--workspace-border);
        border-radius: 6px;
        background: var(--workspace-navigation);
        color: var(--workspace-ink);
        text-decoration: none;
    }

    .workspace-ui .workspace-replacement-alert:hover {
        border-color: var(--workspace-accent);
        background: var(--workspace-tint);
    }

    .workspace-replacement-alert .workspace-metric-label {
        font-size: 14px;
        line-height: 1.4;
        overflow-wrap: break-word;
    }

    .workspace-replacement-footer {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: space-between;
        gap: 8px 16px;
        margin-top: 14px;
        color: var(--workspace-muted);
    }

    .workspace-replacement-footer p {
        margin: 0;
    }

    .workspace-replacement-footer a {
        font-weight: 600;
    }

    .workspace-ui .workspace-empty .box-body {
        padding: clamp(20px, 3vw, 32px);
    }

    .workspace-empty h2 {
        margin: 0 0 6px;
        color: var(--workspace-ink);
        font-family: inherit;
        font-size: 21px;
        font-weight: 700;
        letter-spacing: -.02em;
    }

    .workspace-empty p {
        margin-bottom: 19px;
        color: var(--workspace-muted);
    }

    .workspace-empty-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .workspace-ui .workspace-empty-actions .btn,
    .workspace-ui .workspace-asset-form button[type="submit"] {
        border-radius: 5px;
        font-weight: 650;
    }

    .workspace-ui .workspace-empty-actions .btn-primary,
    .workspace-ui .workspace-asset-form button[type="submit"].btn-success {
        border-color: var(--workspace-primary);
        background: var(--workspace-primary);
        color: #fff !important;
    }

    .workspace-ui .workspace-empty-actions .btn-primary:hover,
    .workspace-ui .workspace-asset-form button[type="submit"].btn-success:hover {
        border-color: var(--workspace-primary-hover);
        background: var(--workspace-primary-hover);
    }

    .workspace-ui .workspace-empty-actions .workspace-secondary-action {
        background: var(--workspace-navigation);
        border-color: var(--workspace-border);
        color: var(--workspace-ink) !important;
    }

    .workspace-ui .workspace-empty-actions .workspace-secondary-action:hover {
        background: var(--workspace-tint);
    }

    .inventory-module-settings-row {
        display: flex;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 8px;
        max-width: 460px;
        margin-bottom: 6px;
    }

    .inventory-location-title {
        min-height: 36px;
        align-content: center;
        font-weight: 650;
    }

    .inventory-module-editor {
        max-width: 100%;
    }

    .inventory-module-editor summary,
    .inventory-location-add summary {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 36px;
        width: fit-content;
        padding-inline: 9px;
        border-radius: 5px;
        color: var(--workspace-accent);
        cursor: pointer;
        list-style: none;
    }

    .inventory-module-editor summary::-webkit-details-marker,
    .inventory-location-add summary::-webkit-details-marker {
        display: none;
    }

    .inventory-module-editor summary:hover,
    .inventory-module-editor[open] summary,
    .inventory-location-add summary:hover {
        background: var(--workspace-tint);
    }

    .inventory-module-editor input {
        margin-top: 5px;
        width: min(260px, 100%);
    }

    .inventory-location-add[open] > .form-control {
        margin-bottom: 8px;
    }

    .workspace-ui .workspace-fixed-metric {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto auto;
        align-items: center;
        gap: 12px;
        margin-bottom: 22px;
        padding: 13px 16px;
        border: 1px solid var(--workspace-border);
        border-radius: 8px;
        color: var(--workspace-ink) !important;
        background: var(--workspace-surface);
        text-decoration: none;
    }

    .workspace-fixed-metric .workspace-metric-icon {
        grid-row: auto;
    }

    .workspace-fixed-metric-label {
        font-weight: 650;
        overflow-wrap: anywhere;
    }

    .workspace-ui .workspace-fixed-metric:hover {
        background: var(--workspace-navigation);
        border-color: var(--workspace-accent);
    }

    .workspace-ui :is(.workspace-fixed-metric, .inventory-module-editor summary, .inventory-location-add summary):focus-visible {
        outline: 2px solid var(--workspace-accent);
        outline-offset: 2px;
    }

    .inventory-location-module {
        display: grid;
        gap: 5px;
        max-width: 460px;
        margin-block: 16px;
    }

    .inventory-location-add {
        display: block;
    }

    .inventory-location-module label {
        margin: 0;
    }

    .inventory-module-option {
        display: flex;
        align-items: center;
        gap: 10px;
        width: fit-content;
        max-width: 100%;
        min-height: 36px;
        margin: 0;
        font-weight: 500;
        cursor: pointer;
    }

    .inventory-module-option input[type="checkbox"] {
        flex: none;
        margin: 0;
        transform: none;
        cursor: pointer;
    }

    .workspace-ui .workspace-asset-form {
        --table-stripe-bg: var(--workspace-navigation);
        --input-border-color: var(--workspace-border);
        --color-fg: var(--workspace-ink);
        max-width: 1020px;
    }

    .workspace-ui .workspace-asset-form .btn-theme {
        --main-theme-color: var(--workspace-tint);
        --nav-primary-text-color: var(--workspace-accent);
        border-color: var(--workspace-border) !important;
        border-radius: 5px;
        background: var(--workspace-tint) !important;
        color: var(--workspace-accent) !important;
    }

    .workspace-ui .workspace-asset-form > :is(.box-header, .box-body) {
        padding: 20px clamp(16px, 3vw, 32px);
    }

    .workspace-ui .workspace-asset-form > .box-header {
        background: var(--workspace-navigation);
        border-radius: 8px 8px 0 0;
    }

    .workspace-ui .workspace-asset-form > .box-header .row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .workspace-ui .workspace-asset-form > .box-header .row::before,
    .workspace-ui .workspace-asset-form > .box-header .row::after {
        display: none;
    }

    .workspace-ui .workspace-asset-form > .box-header .row > div {
        min-width: 0;
        width: auto;
    }

    .workspace-ui .workspace-asset-form > .box-header .box-title {
        padding-block: 0 !important;
    }

    .workspace-ui .workspace-asset-form .control-label {
        color: var(--workspace-ink);
        font-weight: 650;
        text-align: start;
    }

    .workspace-ui .workspace-asset-form .form-control,
    .workspace-ui .workspace-asset-form .select2-selection--single {
        border-radius: 5px;
    }

    @media (max-width: 991px) {
        .workspace-asset-form .form-group:has(> label.control-label + .col-md-7 + :is(.col-md-1, .col-md-2)) {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
            padding-inline: 15px;
            gap: 5px 8px;
        }

        .workspace-asset-form .form-group:has(> label.control-label + .col-md-7 + :is(.col-md-1, .col-md-2)) > * {
            grid-column: 1 / -1;
            width: auto;
            padding-inline: 0;
        }

        .workspace-asset-form .form-group > label.control-label + .col-md-7:has(+ :is(.col-md-1, .col-md-2)) {
            grid-column: 1;
        }

        .workspace-asset-form .form-group > label.control-label + .col-md-7 + :is(.col-md-1, .col-md-2) {
            grid-column: 2;
        }
    }

    @media (min-width: 768px) and (max-width: 1100px) {
        .workspace-ui:not(.sidebar-collapse) .navbar-custom-menu .navbar-nav > li > a {
            padding-inline: 10px;
        }

        .workspace-ui:not(.sidebar-collapse) .workspace-header-shortcut {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip-path: inset(50%);
        }
    }
</style>
