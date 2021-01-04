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

<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.rankpage') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.datatables') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.pickmeup') }}"></script>

<script type="text/javascript" language="javascript" class="init">

$(function() {

	var next_monday = '{{ $next_monday }}';

	pickmeup('#iDatePicker', {
		format: "Y-m-d",
		hide_on_select  : true,
		min : "{{ date('Y-m-d', strtotime($next_monday) - 86400 * 52 * 7) }}",
		max : GetDateStr(1),
		prev: '<<',
		next: '>>',
		default_date: false,
		first_day: {{ __('frame.datePicker.firstDay') }},
		title_format: "{{ __('frame.datePicker.showMonthAfterYear') }}",
		class_name: "cCalendar",
		locale: '{{ App::getLocale() }}',
		locales: {
			{{ App::getLocale() }}: {
				daysMin: ['{{ __('frame.datePicker.sun') }}','{{ __('frame.datePicker.mon') }}','{{ __('frame.datePicker.tue') }}','{{ __('frame.datePicker.wed') }}','{{ __('frame.datePicker.thu') }}','{{ __('frame.datePicker.fri') }}','{{ __('frame.datePicker.sat') }}'],  
				daysShort: ['{{ __('frame.datePicker.sun') }}','{{ __('frame.datePicker.mon') }}','{{ __('frame.datePicker.tue') }}','{{ __('frame.datePicker.wed') }}','{{ __('frame.datePicker.thu') }}','{{ __('frame.datePicker.fri') }}','{{ __('frame.datePicker.sat') }}'],  
				days: ['{{ __('frame.datePicker.sun') }}','{{ __('frame.datePicker.mon') }}','{{ __('frame.datePicker.tue') }}','{{ __('frame.datePicker.wed') }}','{{ __('frame.datePicker.thu') }}','{{ __('frame.datePicker.fri') }}','{{ __('frame.datePicker.sat') }}'],  
				monthsShort: ['{{ __('frame.datePicker.jan') }}','{{ __('frame.datePicker.feb') }}','{{ __('frame.datePicker.mar') }}','{{ __('frame.datePicker.apr') }}','{{ __('frame.datePicker.may') }}','{{ __('frame.datePicker.jun') }}','{{ __('frame.datePicker.jul') }}','{{ __('frame.datePicker.aug') }}','{{ __('frame.datePicker.sep') }}','{{ __('frame.datePicker.oct') }}','{{ __('frame.datePicker.nov') }}','{{ __('frame.datePicker.dec') }}'],  
				months: ['{{ __('frame.datePicker.jan') }}','{{ __('frame.datePicker.feb') }}','{{ __('frame.datePicker.mar') }}','{{ __('frame.datePicker.apr') }}','{{ __('frame.datePicker.may') }}','{{ __('frame.datePicker.jun') }}','{{ __('frame.datePicker.jul') }}','{{ __('frame.datePicker.aug') }}','{{ __('frame.datePicker.sep') }}','{{ __('frame.datePicker.oct') }}','{{ __('frame.datePicker.nov') }}','{{ __('frame.datePicker.dec') }}'],  
			},
		},
		render : function (date) {
			if (date.getDay() != 1) {
				return {disabled : true};
			}
			return {};
		},
	});

	$('#iDatePicker')[0].addEventListener('pickmeup-change', function(e) {
		var formatted = e.detail.formatted_date;
		var y = e.detail.date.getFullYear();
		var m = e.detail.date.getMonth() + 1;
		var d = e.detail.date.getDate();
		if (m < 10) m = '0' + m;
		if (d < 10) d = '0' + d;
		$(this).val(y + '-' + m + '-' + d);
		init(y + '-' + m + '-' + d);
	});

	function init(day) {

		if ( $.fn.dataTable.isDataTable( '#iRankTable' ) ) {
			var table = $('#iRankTable').DataTable();
			table.destroy();
		}

		var c_url = ["{{ slash(url(App::getLocale())) }}", 'rank', type, sd, 'custom', day, next_monday, 'query'].join("/");
		_hmt.push(['_trackCustomEvent', 'rank_custom', {'gender':type,'sd':sd,'start_time':day,'end_time':next_monday}]);
		ga('send', 'pageview', c_url);

		var table = $('#iRankTable').dataTable( {
			"dom": '<t>',
			"processing": true,
			"oLanguage": {
				"sProcessing": "{{ __("rank.table.construct.processing") }}",
				"sInfoEmpty": "{{ __("rank.table.construct.empty") }}",
				"sZeroRecords": "{{ __("rank.table.construct.processing") }}",
			},
			"serverSide": true,
			"bAutoWidth": false,
			"ajax": {
				url: ["{{ url(App::getLocale()) }}", 'rank', type, sd, 'custom', day, next_monday, 'query'].join("/"),
				type: "POST",
			},
			"iDisplayLength": 200,
			"order": [[ 0, "asc" ]],
			fixedHeader: {
				header: true,
				headerOffset: 50
			},
			"drawCallback": function( settings ) {
				var api = new $.fn.dataTable.Api( settings );
			},
			initComplete: function ( settings ) {

				{{-- Event Setting --}}
				var api = new $.fn.dataTable.Api( settings );

			},

			"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {

				if (iDisplayIndex == 0) {
					$('body').animate({  
						scrollTop: $('#iRankTable').offset().top - 50
					}, 500);
				}

			},

			"columnDefs": [
				{
					targets: [3, 4],
					render: function (data, type, row) {
						var ret = '';
						var j = $.parseJSON(data);
						for (i in j) {
							if (typeof(j[i]) == "function") continue;
							ret = ret + '<div class=cBreakdownItem>' + j[i][0] + ' (' + j[i][1] + ')</div>';
						}
						return ret;
					}
				},
			],
		});
	}

	$(':radio[name=sd]').on('click', function() {
		sd = $(this).val();
	});

	$(':radio[name=type]').on('click', function() {
		type = $(this).val();
	});

	$('#iH2HSdS').trigger('click');
	$('#iH2HTypeWTA').trigger('click');

})
</script>

<div id="iRankPage">

	<input id=iMaterialPath type=hidden value="{{ env('CDN') }}" />

	<div id="iRankCustomTypeSelector" class="hastitle">
		<blockTitle class=hastitle_title>{{ __('rank.table.timeTip.chooseFirst') }}</blockTitle>
		<div class=cSelect>
			<input type=radio name=type id=iH2HTypeATP value=atp></input><label for=iH2HTypeATP>{{ __('h2h.selectBar.type.atp') }}</label>
			<input type=radio name=type id=iH2HTypeWTA value=wta></input><label for=iH2HTypeWTA>{{ __('h2h.selectBar.type.wta') }}</label>
			<input type=radio name=sd id=iH2HSdS value=s></input><label for=iH2HSdS>{{ __('h2h.selectBar.sd.s') }}</label>
			<input type=radio name=sd id=iH2HSdD value=d></input><label for=iH2HSdD>{{ __('h2h.selectBar.sd.d') }}</label>
		</div>
	</div>

	<div id="iDataUpdateTime" class="hastitle">
		<blockTitle class=hastitle_title>{{ __('rank.table.timeTip.chooseMonday') }}</blockTitle>
		<div id="iDateSelector" class="cDateSelector">
			<input class="cDatePicker" type=text id="iDatePicker" value="{{ date('Y-m-d', strtotime($next_monday) - 86400 * 52 * 7) }}" readonly=readonly />
			<img class="cResultTourTitleArrow" src="{{ url(env('CDN') . '/images/tips/live_tour_arrow.png') }}" />
		</div>
	</div>

	<table id="iRankTable" class="cRankTable plrank">
		<thead id='iRankTableHead' class="cRankTableHead">
			<tr>
				<th>{{ __("rank.table.head.rank") }}</th>
				<th>{{ __("rank.table.head.player") }}</th>
				<th>{{ __("rank.table.head.point") }}</th>
				<th>{{ __("rank.table.head.breakdown") }}</th>
				<th>{{ __("rank.table.head.alt") }}</th>
			</tr>
		</thead>
	</table>
</div>
@endsection
