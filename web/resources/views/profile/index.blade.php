@extends('layouts.header')

@section('content')

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.rankpage') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.rankpage') }}">
@endif
<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.datatables') }}">

<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.echarts') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.datatables') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.profile') }}"></script>

<script type="text/javascript" language="javascript" class="init">

$(function() {

	unavailable_columns = get_unavailable_columns(device, "{{ $type }}");
	invisible_columns = get_invisible_columns(device, "{{ $type }}");
	columns = get_columns(device, "{{ $type }}");
	hlcty = getCookie("hlcty");

	var table = $('#iEntryTable').dataTable( {
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
			url: "{{ url(join("/", [App::getLocale(), "profile", $type, "query"])) }}",
			type: "POST",
			data: {device: device},
		},
		"iDisplayLength": 100,
		"order": [[ 14, "desc" ]],
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

			{{-- Get Data --}}
			$.ajax({
				type: "GET",
				url: "{{ url(join("/", [App::getLocale(), "select", $type, "bycountry"])) }}",
				success: function(data){
					put_array_to_element($('#iCountrySelector'), data);
					put_array_to_element($('#iCountryHighlight'), data);
				}
			});

			$.ajax({
				type: "GET",
				url: "{{ url(join("/", [App::getLocale(), "select", $type, "byyear"])) }}",
				success: function(data){
					put_json_to_element($('#iYearSelector'), data);
				}
			});

			for (var i = 1; i <= 12; ++i){
				$('#iMonthSelector').append('<option value="' + i + '">' + i + '</option>');
			}
			for (var i = 1; i <= 31; ++i){
				$('#iDaySelector').append('<option value="' + i + '">' + i + '</option>');
			}

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
				api.column('name:name').search(val, false, true, true).draw();
			} );

			$('#iCountrySelector').on( 'change', function () {
				var val = $(this).val();
				if (val == "" || val == "All") val = ".*";
				api.column('ioc:name').search( "^" + val + "$", true, false ).draw();
			} );

			$('#iCountryHighlight').on( 'change', function () {
				var val = $(this).val();
				var rows = api.rows(function(idx, data, node) {
					return data['ioc'] == val;
				}).nodes();
				$('tr.cDataTableHl').removeClass("cDataTableHl");
				$(rows).addClass("cDataTableHl");
				setCookie("hlcty", val, "page");
			} );

			$('#iYearSelector, #iMonthSelector, #iDaySelector').on( 'change', function (){
				var y = $('#iYearSelector').val();
				var m = $('#iMonthSelector').val();
				var d = $('#iDaySelector').val();
				var _valid_date = is_valid_date(y + '-' + m + '-' + d);
				if (_valid_date === false){
					alert("非法日期！请重新选择");
				} else {
					console.log(_valid_date);
					api.column('birthday:name').search(_valid_date, true, false).draw();
				}
			} );

		},

		"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {

			if (Number(aData['c_rank']) < Number(aData['highest'])){
				$('td', nRow).eq(0).addClass('cDataTableCareerHigh');
			}

			if ( aData['rank_s'] < 9999 || aData['rank_d'] < 9999 )
			{
				$(nRow).addClass("DataIn");
			} else {
				$(nRow).addClass("DataAbsent");
			}

			if ( aData['nation'] === hlcty ) {
				$(nRow).addClass("cDataTableHl");
			}

			if (iDisplayIndex == 0) {
				$('body').animate({  
					scrollTop: $('#iEntryTable').offset().top - 50
				}, 500);
			}

		},
		"columns": columns,
		"columnDefs": [],
	});

	var table1 = $('#iEntryTable').DataTable();

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

})
</script>

<div id="iEntryPage" class="cRankPage">
	<input id=iMaterialPath type=hidden value="{{ env('CDN') }}" />

	<div id="iColumnFilter" class="hastitle cColumnFilter">
		<blockTitle class=hastitle_title>{{ __("rank.table.columnFilter.columnFilter") }}</blockTitle>
		<div data-column='name'>{{ __("rank.table.columnFilter.name") }}</div>
		<div data-column='birthday'>{{ __("rank.table.columnFilter.birthday") }}</div>
		<div data-column='birthplace'>{{ __("rank.table.columnFilter.birthplace") }}</div>
		<div data-column='residence'>{{ __("rank.table.columnFilter.residence") }}</div>
		<div data-column='height_bri'>{{ __("rank.table.columnFilter.height_bri") }}</div>
		<div data-column='height'>{{ __("rank.table.columnFilter.height") }}</div>
		<div data-column='weight_bri'>{{ __("rank.table.columnFilter.weight_bri") }}</div>
		<div data-column='weight'>{{ __("rank.table.columnFilter.weight") }}</div>
		<div data-column='hand'>{{ __("rank.table.columnFilter.hand") }}</div>
		<div data-column='proyear'>{{ __("rank.table.columnFilter.proyear") }}</div>
		<div data-column='pronoun'>{{ __("rank.table.columnFilter.pronoun") }}</div>
		<div data-column='website'>{{ __("rank.table.columnFilter.website") }}</div>
		<div data-column='prize_c'>{{ __("rank.table.columnFilter.prize_c") }}</div>
		<div data-column='prize_y'>{{ __("rank.table.columnFilter.prize_y") }}</div>
		<div data-column='rank_s'>{{ __("rank.table.columnFilter.rank_s") }}</div>
		<div data-column='rank_s_hi'>{{ __("rank.table.columnFilter.rank_s_hi") }}</div>
		<div data-column='rank_s_hi_date'>{{ __("rank.table.columnFilter.rank_s_hi_date") }}</div>
		<div data-column='title_s_c'>{{ __("rank.table.columnFilter.title_s_c") }}</div>
		<div data-column='title_s_y'>{{ __("rank.table.columnFilter.title_s_y") }}</div>
		<div data-column='win_s_c'>{{ __("rank.table.columnFilter.win_s_c") }}</div>
		<div data-column='lose_s_c'>{{ __("rank.table.columnFilter.lose_s_c") }}</div>
		<div data-column='win_s_y'>{{ __("rank.table.columnFilter.win_s_y") }}</div>
		<div data-column='lose_s_y'>{{ __("rank.table.columnFilter.lose_s_y") }}</div>
		<div data-column='rank_d'>{{ __("rank.table.columnFilter.rank_d") }}</div>
		<div data-column='rank_d_hi'>{{ __("rank.table.columnFilter.rank_d_hi") }}</div>
		<div data-column='rank_d_hi_date'>{{ __("rank.table.columnFilter.rank_d_hi_date") }}</div>
		<div data-column='title_d_c'>{{ __("rank.table.columnFilter.title_d_c") }}</div>
		<div data-column='title_d_y'>{{ __("rank.table.columnFilter.title_d_y") }}</div>
		<div data-column='win_d_c'>{{ __("rank.table.columnFilter.win_d_c") }}</div>
		<div data-column='lose_d_c'>{{ __("rank.table.columnFilter.lose_d_c") }}</div>
		<div data-column='win_d_y'>{{ __("rank.table.columnFilter.win_d_y") }}</div>
		<div data-column='lose_d_y'>{{ __("rank.table.columnFilter.lose_d_y") }}</div>
	</div>

	<div id="iRowFilter" class="hastitle cRowFilte">
		<blockTitle class=hastitle_title>{{ __("rank.table.rowFilter.rowFilter") }}</blockTitle>
		<select id=iPagelenSelector class=cRowFilterInput>
			<option value=10>{{ __("rank.table.rowFilter.show10") }}</option>
			<option value=25>{{ __("rank.table.rowFilter.show25") }}</option>
			<option value=50>{{ __("rank.table.rowFilter.show50") }}</option>
			<option value=100 selected>{{ __("rank.table.rowFilter.show100") }}</option>
			<option value=-1>{{ __("rank.table.rowFilter.showAll") }}</option>
		</select>
		<select id=iPageSelector class=cRowFilterInput></select>
		<input id=iNameSearcher type=text placeholder="{{ __("rank.table.rowFilter.searchPlayer") }}" class=cRowFilterInput></input>
		<select id=iCountryHighlight class=cRowFilterInput><option value=All>{{ __("rank.table.rowFilter.highlightCountry") }}</option></select>
		<select id=iCountrySelector class=cRowFilterInput><option value=All>{{ __("rank.table.rowFilter.filterCountry") }}</option></select>
		<select id=iYearSelector class=cRowFilterInput><option value=0>{{ __("rank.table.rowFilter.filterYear") }}</option></select>-<select id=iMonthSelector class=cRowFilterInput><option value=0>{{ __("rank.table.rowFilter.filterMonth") }}</option></select>-<select id=iDaySelector class=cRowFilterInput><option value=0>{{ __("rank.table.rowFilter.filterDay") }}</option></select>
	</div>

	<table id="iEntryTable" class="cRankTable plrank cEntryTable noPointer" width=100%>
		<thead id='iEntryTableHead' class="cRankTableHead">

			<tr>
				<th rowspan="3">{{ __("rank.table.head.name") }}</th>
				<th rowspan="3">{{ __("rank.table.head.nation") }}</th>
				<th rowspan="3">{{ __("rank.table.head.nation") }}</th>
				<th rowspan="3">{{ __("rank.table.head.birthday") }}</th>
				<th rowspan="3">{{ __("rank.table.head.birthPlace") }}</th>
				<th rowspan="3">{{ __("rank.table.head.residence") }}</th>
				<th rowspan="2" colspan="2">{{ __("rank.table.head.height") }}</th>
				<th rowspan="2" colspan="2">{{ __("rank.table.head.weight") }}</th>
				<th rowspan="3">{{ __("rank.table.head.hand") }}</th>
				<th rowspan="3">{{ __("rank.table.head.proYear") }}</th>
				<th rowspan="3">{{ __("rank.table.head.pronoun") }}</th>
				<th rowspan="3">{{ __("rank.table.head.website") }}</th>
				<th rowspan="2" colspan="2">{{ __("rank.table.head.prize") }}</th>
				<th colspan="9">{{ __("rank.table.head.single") }}</th>
				<th colspan="9">{{ __("rank.table.head.double") }}</th>
			</tr>
			<tr>
				<th colspan="3">{{ __("rank.table.head.rank") }}</th>
				<th colspan="2">{{ __("rank.table.head.title") }}</th>
				<th colspan="2">{{ __("rank.table.head.career") }}</th>
				<th colspan="2">{{ __("rank.table.head.ytd") }}</th>
				<th colspan="3">{{ __("rank.table.head.rank") }}</th>
				<th colspan="2">{{ __("rank.table.head.title") }}</th>
				<th colspan="2">{{ __("rank.table.head.career") }}</th>
				<th colspan="2">{{ __("rank.table.head.ytd") }}</th>
			</tr>
			<tr>
				<th>{{ __("rank.table.head.feet") }}</th>
				<th>{{ __("rank.table.head.centimetre") }}</th>
				<th>{{ __("rank.table.head.pound") }}</th>
				<th>{{ __("rank.table.head.kilo") }}</th>
				<th>{{ __("rank.table.head.career") }}</th>
				<th>{{ __("rank.table.head.ytd") }}</th>
				<th>{{ __("rank.table.head.current") }}</th>
				<th>{{ __("rank.table.head.careerHigh") }}</th>
				<th>{{ __("rank.table.head.careerHighDate") }}</th>
				<th>{{ __("rank.table.head.career") }}</th>
				<th>{{ __("rank.table.head.ytd") }}</th>
				<th>{{ __("rank.table.head.win") }}</th>
				<th>{{ __("rank.table.head.lose") }}</th>
				<th>{{ __("rank.table.head.win") }}</th>
				<th>{{ __("rank.table.head.lose") }}</th>
				<th>{{ __("rank.table.head.current") }}</th>
				<th>{{ __("rank.table.head.careerHigh") }}</th>
				<th>{{ __("rank.table.head.careerHighDate") }}</th>
				<th>{{ __("rank.table.head.career") }}</th>
				<th>{{ __("rank.table.head.ytd") }}</th>
				<th>{{ __("rank.table.head.win") }}</th>
				<th>{{ __("rank.table.head.lose") }}</th>
				<th>{{ __("rank.table.head.win") }}</th>
				<th>{{ __("rank.table.head.lose") }}</th>
			</tr>
		</thead>
	</table>
	<div class=cPageLeft>&lt;&lt;</div>
	<div class=cPageMid>&nbsp;</div>
	<div class=cPageRight>&gt;&gt;</div>
</div>
@endsection
