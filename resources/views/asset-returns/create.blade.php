@extends('layouts/default')

@section('title', trans('asset-returns.create'))

@section('content')
    <x-container>
        {{-- Step 1: the person. Their assigned items load automatically. --}}
        <x-box :header="trans('asset-returns.choose_person')">
            <form method="GET" action="{{ route('returns.create') }}" class="form-horizontal" data-return-person>
                <x-input.user-select :label="trans('asset-returns.person')" name="user_id" :selected="$person?->id" required />
                <div class="form-group">
                    <div class="col-md-7 col-md-offset-3">
                        <button type="submit" class="btn btn-default">{{ trans('asset-returns.load_items') }}</button>
                        @unless ($person)
                            <p class="help-block">{{ trans('asset-returns.pick_person_first') }}</p>
                        @endunless
                    </div>
                </div>
            </form>
        </x-box>

        @if ($person)
            <form method="POST" action="{{ route('returns.store') }}" class="form-horizontal">
                @csrf
                <input type="hidden" name="user_id" value="{{ $person->id }}">

                <x-box :header="$person->display_name">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{{ trans('asset-returns.reason') }}</label>
                        <div class="col-md-7 return-reasons">
                            @foreach (\App\Models\AssetReturn::REASONS as $reason)
                                <label><input type="radio" name="reason" value="{{ $reason }}" @checked(old('reason', 'termination') === $reason) required> {{ trans('asset-returns.reasons.'.$reason) }}</label>
                            @endforeach
                            <x-form.error name="reason" />
                        </div>
                    </div>

                    <x-form.row :label="trans('asset-returns.ticket')" name="ticket_number" :placeholder="trans('asset-returns.ticket_placeholder')" maxlength="100" />
                    <x-input.user-select :label="trans('asset-returns.ticket_owner')" name="ticket_owner_id" :selected="old('ticket_owner_id')" />
                    <x-input.user-select :label="trans('asset-returns.return_owner')" name="return_owner_id" :selected="old('return_owner_id', auth()->id())" />
                </x-box>

                <x-box :header="trans('asset-returns.items')">
                    <x-form.error name="items" />
                    @if ($items->isEmpty())
                        <p>{{ trans('asset-returns.no_items') }}</p>
                    @else
                        @if ($clinicLocations->isEmpty())
                            <p class="callout callout-warning">{{ trans('asset-returns.no_locations') }}</p>
                        @endif
                        <div class="table-responsive">
                            <table class="table return-items-table">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ trans('asset-returns.include') }}</th>
                                        <th scope="col">{{ trans('asset-returns.tag') }}</th>
                                        <th scope="col">{{ trans('asset-returns.item') }}</th>
                                        <th scope="col">{{ trans('asset-returns.destination') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        @php($key = $item['key'])
                                        @php($destination = old('items.'.$key.'.destination', 'partner'))
                                        <tr data-return-item>
                                            <td><input type="checkbox" name="items[{{ $key }}][include]" value="1" @checked(old('items.'.$key.'.include', old('_token') ? false : true)) aria-label="{{ trans('asset-returns.include') }} {{ $item['tag'] }}"></td>
                                            <td><strong class="return-tag">{{ $item['tag'] }}</strong></td>
                                            <td>{{ $item['name'] }}@if ($item['category'])<span class="return-item-category">{{ $item['category'] }}</span>@endif</td>
                                            <td class="return-destination">
                                                <select class="form-control" name="items[{{ $key }}][destination]" data-return-destination aria-label="{{ trans('asset-returns.destination') }}">
                                                    @foreach (\App\Models\AssetReturnItem::DESTINATIONS as $option)
                                                        <option value="{{ $option }}" @selected($destination === $option)>{{ trans('asset-returns.destinations.'.$option, ['partner' => $partner]) }}</option>
                                                    @endforeach
                                                </select>
                                                <div data-when="partner">
                                                    <span class="return-question">{{ trans('asset-returns.previously_with_partner', ['partner' => $partner]) }}</span>
                                                    @foreach (['1' => 'yes', '0' => 'no'] as $value => $text)
                                                        <label><input type="radio" name="items[{{ $key }}][previously_with_partner]" value="{{ $value }}" @checked((string) old('items.'.$key.'.previously_with_partner') === (string) $value)> {{ trans('asset-returns.'.$text) }}</label>
                                                    @endforeach
                                                </div>
                                                <div data-when="storage">
                                                    <select class="form-control" name="items[{{ $key }}][location_id]" aria-label="{{ trans('asset-returns.storage_location') }}">
                                                        <option value="">{{ trans('asset-returns.choose_location') }}</option>
                                                        @foreach ($clinicLocations as $location)
                                                            <option value="{{ $location->id }}" @selected((string) old('items.'.$key.'.location_id') === (string) $location->id)>{{ $location->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <x-form.error :name="'items.'.$key" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-box>

                <x-box :header="trans('asset-returns.notes')">
                    <x-form.row :label="trans('asset-returns.note')" name="note" type="textarea" :rows="3" :placeholder="trans('asset-returns.note_placeholder')" />
                    <p class="help-block col-md-offset-3">{{ trans('asset-returns.notes_help') }}</p>
                    <div class="return-actions">
                        <a class="btn btn-default" href="{{ route('returns.index') }}">{{ trans('general.cancel') }}</a>
                        <button type="submit" class="btn btn-theme" @disabled($items->isEmpty())>{{ trans('asset-returns.save') }}</button>
                    </div>
                </x-box>
            </form>
        @endif
    </x-container>
@stop

@push('js')
    <script nonce="{{ csrf_token() }}">
        // Picking the person loads their items right away.
        $('[data-return-person] select[name="user_id"]').on('change', function () {
            this.form.submit();
        });

        // Each item shows the question that fits its destination.
        document.querySelectorAll('[data-return-item]').forEach(row => {
            const select = row.querySelector('[data-return-destination]');
            const sync = () => row.querySelectorAll('[data-when]').forEach(part => {
                part.hidden = part.dataset.when !== select.value;
            });
            select.addEventListener('change', sync);
            sync();
        });
    </script>
@endpush
