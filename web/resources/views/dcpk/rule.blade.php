@extends('layouts.header')

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.dcpk') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.dcpk') }}">
@endif

@section('content')
<div id='iDcRule' class="tips">

	{!! __('dcpk.rule.pick') !!}
	<br>

	{!! __('dcpk.rule.pickRule') !!}
	<br>

	{!! __('dcpk.rule.roundPoint') !!}
	<br>

	{!! __('dcpk.rule.ko_itgl') !!}
	<br>

	{!! __('dcpk.rule.itglNotice') !!}
	<br>

	{!! __('dcpk.rule.dcpkNotice') !!}
	<br>

</div>
@endsection
