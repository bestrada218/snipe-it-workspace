@props(['items' => []])

{{-- What needs action today, most urgent first, with one action per row. --}}
<section class="box attention-panel" aria-labelledby="attention-title">
    <div class="box-header with-border">
        <h2 class="box-title" id="attention-title">{{ trans('dashboard-attention.title') }}</h2>
        @if (count($items) > 0)
            <span class="attention-count">{{ count($items) }}</span>
        @endif
    </div>
    <div class="box-body attention-list">
        @forelse ($items as $item)
            <div class="attention-row">
                <span class="attention-severity attention-{{ $item['tone'] }}" aria-hidden="true"></span>
                <div class="attention-text">
                    <strong>{{ $item['title'] }}</strong>
                    <span>{{ $item['detail'] }}</span>
                </div>
                <a class="btn btn-default btn-sm" href="{{ $item['url'] }}">{{ $item['action'] }}</a>
            </div>
        @empty
            <p class="attention-clear">{{ trans('dashboard-attention.all_clear') }}</p>
        @endforelse
    </div>
</section>
