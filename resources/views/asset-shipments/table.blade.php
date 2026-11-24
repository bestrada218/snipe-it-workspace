<div class="table-responsive">
    <table class="table table-striped workspace-shipment-table">
        <thead>
            <tr>
                <th scope="col">{{ trans('asset-shipments.asset') }}</th>
                <th scope="col">{{ trans('asset-shipments.recipient') }}</th>
                <th scope="col">{{ trans('asset-shipments.tracking') }}</th>
                <th scope="col">{{ ($past ?? false) ? trans('asset-shipments.closed_on') : trans('asset-shipments.expected') }}</th>
                <th scope="col">{{ trans('asset-shipments.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $record)
                @continue(! $record->asset)
                <tr>
                    <td>
                        <a href="{{ route('shipments.show', $record) }}">
                            <strong>{{ $record->asset->asset_tag }}</strong>
                            <span class="workspace-shipment-item">{{ $record->asset->name ?: $record->asset->model?->name }}</span>
                        </a>
                    </td>
                    <td>{{ $record->recipient?->display_name }}</td>
                    <td>{{ $record->tracking_number }}</td>
                    <td>
                        @if ($past ?? false)
                            {{ Helper::getFormattedDateObject($record->assigned_at ?? $record->cancelled_at, 'date', false) }}
                        @else
                            {{ Helper::getFormattedDateObject($record->expected_on, 'date', false) }}
                        @endif
                    </td>
                    <td><span class="workspace-shipment-status" data-status="{{ $record->status() }}">{{ trans('asset-shipments.'.$record->status()) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5">{{ $empty ?? trans('asset-shipments.empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
