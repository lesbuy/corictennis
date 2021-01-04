@extends('layouts.header')

@section('content')

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.rankpage') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.pickmeup') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.rankpage') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.pickmeup') }}">
@endif
<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.datatables') }}">

<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.pickmeup') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.guesscalendar') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.datatables') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.echarts') }}"></script>

<script type="text/javascript" language="javascript" class="init">

function init(day) {
	window.location.href = "{{ url(App::getLocale() . "/guess/calendar/") }}" + "/" + day;
};

$(function() {

	columns = get_columns(device);

	var table = $('#iRankTable').dataTable( {
		"dom": '<t>',
		"processing": true,
		"oLanguage": {
			"sProcessing": "{{ __("rank.table.construct.processing") }}",
			"sInfoEmpty": "{{ __("rank.table.construct.empty") }}",
			"sZeroRecords": "{{ __("rank.table.construct.processing") }}",
		},
		"deferLoading": true,
		"serverSide": true,
		"bAutoWidth": false,
		"ajax": {
			url: "{{ url(join("/", [App::getLocale(), "guess", "calendar", "query"])) }}",
			type: "POST",
			data: {device: device},
		},
		"iDisplayLength": 100,
		"order": [[ 1, "asc" ]],
		"columns": columns,
		"columnDefs": [],
		"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
			$(nRow).addClass("DataAbsent");
		},
	});

	var table1 = $('#iRankTable').DataTable();
	table1.column(0).search("{{ $year }}").draw();

	pickmeup('#iDatePicker', {
		format: "Y",
		hide_on_select  : true,
		min : "2016",
		max : "{{ date('Y', time()) }}",
		prev: '<<',
		next: '>>',
		select_day: false,
		select_month: false,
		default_date: false,
		class_name: "cCalendar",
	});

	$('#iDatePicker')[0].addEventListener('pickmeup-change', function(e) {
		var formatted = e.detail.formatted_date;
		$(this).val(formatted);
		init(formatted);
	});

})
</script>

<div id="iRankPage" class="cRankPage">
	<input id=iMaterialPath type=hidden value="{{ env('CDN') }}" />

	<div id=iCalendarSelector class="cDateSelector">
		<img class="cResultTourTitleArrow" src="{{ url(env('CDN') . '/images/tips/live_tour_arrow.png') }}" />
		<input class="selected cDatePicker" type=text id="iDatePicker" value="{{ $year }}" readonly=readonly />
	</div>

	<table id="iRankTable" class="cRankTable plrank">
		<thead id='iRankTableHead' class="cRankTableHead">
			<tr>
				<th>{{ __("rank.table.head.year") }}</th>
				<th>{{ __("rank.table.head.week") }}</th>
				<th>{{ __("rank.table.head.start") }}</th>
				<th>{{ __("rank.table.head.tour") }}</th>
				<th>{{ __("rank.table.head.level") }}</th>
				<th>{{ __("rank.table.head.itgl") }}</th>
				<th>{{ __("rank.table.head.dcpk") }}</th>
			</tr>
		</thead>
	</table>
</div>
@endsection
