@extends('layouts/default')

@section('title', trans('asset-tags.title'))

@section('content')
    <x-container>
        <x-box :header="trans('asset-tags.format')">
            <p><code>XX-KKK-PP[-TTT]</code></p>
            <p>{{ trans('asset-tags.optional') }}</p>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">{{ trans('asset-tags.segment') }}</th>
                        <th scope="col">{{ trans('asset-tags.meaning') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (['XX', 'KKK', 'PP', 'TTT'] as $segment)
                        <tr>
                            <th scope="row"><code>{{ $segment }}</code></th>
                            <td>{{ trans('asset-tags.parts.'.$segment) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-box>

        <x-box :header="trans('asset-tags.examples')">
            <ul>
                <li><code>NY-001-LP-W11</code> — {{ trans('asset-tags.laptop_example') }}</li>
                <li><code>NY-003-DT-LNX</code> — {{ trans('asset-tags.desktop_example') }}</li>
                <li><code>NY-006-HS</code> — {{ trans('asset-tags.accessory_example') }}</li>
            </ul>
            <p>{{ trans('asset-tags.storage') }}</p>
        </x-box>
    </x-container>
@stop
