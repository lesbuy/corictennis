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
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.guessrank') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.datatables') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.echarts') }}"></script>

<script type="text/javascript" language="javascript" class="init">

function init(day) {
	window.location.href = "{{ url(App::getLocale() . "/guess/rank/" . $sd . '/' . $gran) }}" + "/" + day;
};

$(function() {

	unavailable_columns = get_unavailable_columns(device, "{{ $sd }}", "{{ $gran }}");
	invisible_columns = get_invisible_columns(device, "{{ $sd }}", "{{ $gran }}");
	columns = get_columns(device, "{{ $sd }}", "{{ $gran }}");

	var device = window.innerWidth >= 500 ? 0 : 1;

	var table = $('#iRankTable').dataTable( {
		"dom": '<t>',
		"processing": true,
		"oLanguage": {
			"sProcessing": "{{ __("rank.table.construct.processing") }}",
			"sInfoEmpty": "{{ __("rank.table.construct.empty") }}",
			"sZeroRecords": "{{ __("rank.table.construct.empty") }}",
		},
		"serverSide": true,
		"bAutoWidth": false,
		"ajax": {
			url: "{{ url(join("/", [App::getLocale(), "guess", "rank", $sd, $gran, $date, "query"])) }}",
			type: "POST",
			data: {device: device},
		},
		"iDisplayLength": {{ $gran == "day" || $gran == "week" ? -1 : 100 }},
		"order": [[ 0, "asc" ]],
		fixedHeader: {
			header: true,
			headerOffset: 50
		},
		"drawCallback": function( settings ) {
			var api = new $.fn.dataTable.Api( settings );
			var pagenum = api.page.info().pages;
			var currentpage = api.page.info().page + 1;
			$('#iPageSelector').html("");
			for (var i = 1; i <= pagenum; ++i) {
				$('#iPageSelector').append('<option value=' + i + (i == currentpage ? ' selected' : '') + '>' + '{{ __("rank.table.construct.pagePrefix") }}' + i + '{{ __("rank.table.construct.pageSuffix") }}' + '</option>');
			}

			$('.cPageMid').html(api.page.info().page + 1);
			$('.cPageRight').attr('value', api.page.info().page + 1);
			$('.cPageLeft').attr('value', api.page.info().page - 1);
			if (api.page.info().page <= 0 || api.page.info().page >= pagenum) $('.cPageLeft').addClass('cPageHidden'); else $('.cPageLeft').removeClass('cPageHidden');
			if (api.page.info().page < 0 || api.page.info().page >= pagenum - 1) $('.cPageRight').addClass('cPageHidden'); else $('.cPageRight').removeClass('cPageHidden');
		},
		initComplete: function ( settings ) {

			{{-- Event Setting --}}
			var api = new $.fn.dataTable.Api( settings );

			$('#iPageSelector').on( 'change', function () {
				var val = parseInt($(this).val()) - 1;
				api.page(val).draw(false);
			});

			$('.cPageRight, .cPageLeft').on('click', function () {
				var val = parseInt($(this).attr('value'));
				if (val >= 0 && val < api.page.info().pages) {
					api.page(val).draw(false);
				}
			});

			$('#iPagelenSelector').on( 'change', function () {
				var val = $(this).val();
				api.page.len(val).draw(false);
			} );

			$('#iNameSearcher').on( 'keyup change', function (){
				var val = $(this).val();
				api.column('username:name').search(val, false, true, true).draw();
			} );

		},

		"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {

			if ( aData['me'] == "1" ) {
				$(nRow).addClass("DataIn");
			} else {
				$(nRow).addClass("DataAbsent");
			}

		},
		"columns": columns,
		"columnDefs": [],
	});

	var table1 = $('#iRankTable').DataTable();

	{{-- 置选择项选中或未选中 --}}
	$('#iColumnFilter div:not(.hastitle_title)').each(function(index){
		var dc = $(this).attr('data-column');
		if ($.inArray(dc, unavailable_columns) !== -1){
			$(this).attr('class', 'cColumnHidden');
		} else if ($.inArray(dc, invisible_columns) !== -1){
			$(this).attr('class', 'cColumnUnselected');
		} else {
			$(this).attr('class', 'cColumnSelected');
		}
	});

	$('#iColumnFilter div:not(.hastitle_title)').on( 'click', function (e) {
		e.preventDefault(); 

		var td_class = $(this).attr("class");
		if (td_class == "cColumnUnselected"){
			$(this).addClass("cColumnSelected");
			$(this).removeClass("cColumnUnselected");
		} else if (td_class == "cColumnSelected"){
			$(this).addClass("cColumnUnselected");
			$(this).removeClass("cColumnSelected");
		}

		var column_digit = $(this).attr('data-column');
		var column = table1.column(column_digit + ':name');

		if (column.visible()) {
			invisible_columns.push(column_digit);
		} else {
			removeElement(invisible_columns, column_digit);
		}
		setCookie("invc", invisible_columns.join("|"), "page");
		column.visible( ! column.visible() );
	} );

	@if ($gran == "day")
		$(document).on('click', '#iRankTable>tbody>tr[role=row]', function () {

			var row = table1.row( $(this) );
			var href = row.data()['link'];
			window.location.href = href;

		});

		$('.cResultDirectTo').on('click', function () {
			init($(this).attr('data')); 
		});

		pickmeup('#iDatePicker', {
			format: "Y-m-d",
			hide_on_select  : true,
			min : "2016-01-01",
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
			change: function (formatted) {
				$(this).val(formatted);
				init(formatted);
			},
		});

		$('#iDatePicker')[0].addEventListener('pickmeup-change', function(e) {
			var formatted = e.detail.formatted_date;
			$(this).val(formatted);
			init(formatted);
		});

	@elseif ($gran == "week")

		pickmeup('#iDatePicker', {
			format: "Y-m-d",
			hide_on_select  : true,
			min : "2016-01-01",
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
			var w = GetWeekOfYear(y, m, d);
			if (w < 10) w = '0' + w;
			$(this).val(y + '_' + w);
			init(y + '_' + w);
		});

	@elseif ($gran == "year")

		$(document).on('click', '#iRankTable>tbody>tr[role=row]', function () {

			$('#iMask').fadeIn(500).css('display', '-webkit-flex');

			var row = table1.row( $(this) );
			var id = row.data()['userid'];
			$('#iMask').html('<div id=iAjaxNotice>' + '{{ __("frame.notice.gfw") }}'  + '</div>');

			$.ajax({
				type: 'POST',
				url: "{{ url(join("/", [App::getLocale(), "breakdown", "guess", $sd, $gran, "query"])) }}",
				data: {id: id},
				success: function(data) {
					$('#iMask').html(data);
				}
			});
		});

	@endif

})
</script>

<div id="iRankPage" class="cRankPage">
	<input id=iMaterialPath type=hidden value="{{ env('CDN') }}" />

	@if ($gran != "year" && $gran != "all")
		<div id="iDateSelector" class="cDateSelector">
			@if ($gran != "week")
				<div class="cResultDirectTo unselected" data="{{ date('Y-m-d', strtotime($date . "-2 days")) }}">{{ date('m-d', strtotime($date . "-2 days")) }}</div>
				<div class="cResultDirectTo unselected" data="{{ date('Y-m-d', strtotime($date . "-1 days")) }}">{{ date('m-d', strtotime($date . "-1 days")) }}</div>
			@endif
			<img class="cResultTourTitleArrow" src="{{ url(env('CDN') . '/images/tips/live_tour_arrow.png') }}" />
			<input class="selected cDatePicker" type=text id="iDatePicker" value="{{ $date }}" readonly=readonly />
			@if ($gran != "week")
				<div class="cResultDirectTo unselected" data="{{ date('Y-m-d', strtotime($date . "+1 days")) }}">{{ date('m-d', strtotime($date . "+1 days")) }}</div>
			@endif
		</div>
	@endif

	<div id="iColumnFilter" class="hastitle cColumnFilter">
		<blockTitle class=hastitle_title>{{ __("rank.table.columnFilter.columnFilter") }}</blockTitle>
		<div data-column='change'>{{ __("rank.table.columnFilter.move") }}</div>
		<div data-column='last'>{{ __("rank.table.columnFilter.last") }}</div>
		<div data-column='w_point'>{{ __("rank.table.columnFilter.add") }}</div>
		<div data-column='tour_c'>{{ __("rank.table.columnFilter.tourCount") }}</div>
		<div data-column='streak'>{{ __("rank.table.columnFilter.streak") }}</div>
		<div data-column='win'>{{ __("rank.table.columnFilter.win") }}</div>
		<div data-column='lose'>{{ __("rank.table.columnFilter.lose") }}</div>
		<div data-column='win_r'>{{ __("rank.table.columnFilter.winRate") }}</div>
		<div data-column='q_tour'>{{ __("rank.table.columnFilter.qijiTour.guess.$sd") }}</div>
		<div data-column='q_point'>{{ __("rank.table.columnFilter.qijiPoint.guess.$sd") }}</div>
		<div data-column='w_tour'>{{ __("rank.table.columnFilter.tour") }}</div>
		<div data-column='next_oppo'>{{ __("rank.table.columnFilter.opponent") }}</div>
		<div data-column='matches'>{{ __("rank.table.columnFilter.matches") }}</div>
		<div data-column='scorePerMatch'>{{ __("rank.table.columnFilter.scorePerMatch") }}</div>
	</div>

	<div id="iRowFilter" class="hastitle cRowFilter">
		<blockTitle class=hastitle_title>{{ __("rank.table.rowFilter.rowFilter") }}</blockTitle>
		<select id=iPagelenSelector class=cRowFilterInput>
			<option value=10>{{ __("rank.table.rowFilter.show10") }}</option>
			<option value=25>{{ __("rank.table.rowFilter.show25") }}</option>
			<option value=50>{{ __("rank.table.rowFilter.show50") }}</option>
			<option value=100 {{ in_array($gran, ["day", "week"]) ? '' : 'selected' }}>{{ __("rank.table.rowFilter.show100") }}</option>
			<option value=-1 {{ in_array($gran, ["day", "week"]) ? 'selected' : '' }}>{{ __("rank.table.rowFilter.showAll") }}</option>
		</select>
		<select id=iPageSelector class=cRowFilterInput></select>
		<input id=iNameSearcher type=text placeholder="{{ __("rank.table.rowFilter.searchUser") }}" class=cRowFilterInput></input>
	</div>

	<table id="iRankTable" class="cRankTable plrank">
		<thead id='iRankTableHead' class="cRankTableHead">
			@if ($gran == "year")
				<tr>
					<th colspan="4">{{ __("rank.table.head.overview") }}</th>
					<th colspan="3">{{ __("rank.table.head.rank") }}</th>
					<th colspan="5">{{ __("rank.table.head.period.$gran") }}</th>
					<th colspan="2">{{ __("rank.table.head.qiji.guess.$sd") }}</th>
					<th colspan="4">{{ __("rank.table.head.current") }}</th>
				</tr>
			@endif
			<tr>
				<th>{{ __("rank.table.head.rank") }}</th>
				<th>{{ __("rank.table.head.score") }}</th>
				<th>{{ __("rank.table.head.point") }}</th>
				<th>{{ __("rank.table.head.username") }}</th>
				<th>{{ __("rank.table.head.move") }}</th>
				<th>{{ __("rank.table.head.last") }}</th>
				<th>{{ __("rank.table.head.add") }}</th>
				<th>{{ __("rank.table.head.tourCount") }}</th>
				<th>{{ __("rank.table.head.streak") }}</th>
				<th>{{ __("rank.table.head.win") }}</th>
				<th>{{ __("rank.table.head.lose") }}</th>
				<th>{{ __("rank.table.head.winRate") }}</th>
				<th>{{ __("rank.table.head.qijiTour") }}</th>
				<th>{{ __("rank.table.head.qijiPoint") }}</th>
				<th>{{ __("rank.table.head.tour") }}</th>
				<th>{{ __("rank.table.head.opponent") }}</th>
				<th>{{ __("rank.table.head.matches") }}</th>
				<th>{{ __("rank.table.head.scorePerMatch") }}</th>
			</tr>
		</thead>
	</table>
	<div class=cPageLeft>&lt;&lt;</div>
	<div class=cPageMid>&nbsp;</div>
	<div class=cPageRight>&gt;&gt;</div>
</div>
@endsection
