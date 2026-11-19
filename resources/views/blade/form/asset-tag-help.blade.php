@props(['id' => 'asset_tag-help'])

<div class="help-block" id="{{ $id }}">
    <p>{{ \App\Services\AssetTagSequence::hint() }}</p>
    <p>
        <code>XX-KKK-PP[-TTT]</code>
        <span>{{ trans('asset-tags.quick_structure') }}</span>
        <a href="{{ route('guide.asset-tags') }}" target="_blank" rel="noopener">{{ trans('asset-tags.full_guide') }}</a>
    </p>
</div>
