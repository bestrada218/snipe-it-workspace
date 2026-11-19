@props(['tag' => null, 'name' => null])

{{-- The asset tag is the primary identifier when handing something out: show it first and large. --}}
<div class="form-group checkout-asset-tag">
    <span class="col-md-3 control-label">{{ trans('admin/hardware/form.tag') }}</span>
    <div class="col-md-9">
        @if (filled($tag))
            <p class="form-control-static"><span class="checkout-asset-tag-value">{{ $tag }}</span></p>
        @else
            <p class="form-control-static text-warning">{{ trans('asset-tags.missing_on_item') }}</p>
        @endif
        @if (filled($name))
            <p class="checkout-asset-tag-name">{{ $name }}</p>
        @endif
    </div>
</div>
