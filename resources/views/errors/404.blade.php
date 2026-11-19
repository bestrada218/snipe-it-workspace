@extends('layouts/basic')

{{-- Page title --}}
@section('title')
404
@parent
@stop

{{-- Page content --}}

@section('content')



<div class="row">
  <div class="col-md-8 col-md-offset-2">

    <div style="padding-top: 200px">
      <img src="{{ config('app.url') }}/img/sad-panda.png" style="width: 200px; height: 200px;" class="pull-left" alt="Sad Panda cartoon" />
            <div class="error-content">
              <h2><x-icon type="warning" class="text-yellow" /> {{ trans('general.page_not_found_title') }}</h2>
              <p>
                {!! trans('general.page_not_found_body', ['link' => config('app.url')]) !!}
              </p>

    </div>
</div>
@stop
