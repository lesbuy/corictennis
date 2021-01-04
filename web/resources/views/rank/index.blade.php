@extends('layouts.header')

@section('content')

<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.datatables') }}">
@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.rankpage') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.rankpage') }}">
@endif

<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.datatables') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.echarts') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.rankpage') }}"></script>

<script type="text/javascript" language="javascript" class="init">

$(function() {

	$('#iRankUpdateTime').html(GetLocalDate($.trim($('#iRankUpdateTime').html()), 8));

	unavailable_columns = get_unavailable_columns(device, "{{ $sd }}", "{{ $period }}");
	invisible_columns = get_invisible_columns(device, "{{ $sd }}", "{{ $period }}");
	columns = get_columns(device, "{{ $sd }}", "{{ $period }}");
	hlcty = getCookie("hlcty");

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
			url: "{{ url(join("/", [App::getLocale(), "rank", $type, $sd, $period, "query"])) }}",
			type: "POST",
			data: {device: device},
		},
		"iDisplayLength": 100,
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

			$("img.cImgPlayerFlag", this).lazyload();
		},
		initComplete: function ( settings ) { // 一次性的

			{{-- Get Data --}}
			$.ajax({
				type: "GET",
				url: "{{ url(join("/", [App::getLocale(), "select", $type, $sd, $period, "bycountry"])) }}",
				success: function(data){
					put_array_to_element($('#iCountrySelector'), data);
					put_array_to_element($('#iCountryHighlight'), data);
				}
			});

			$.ajax({
				type: "GET",
				url: "{{ url(join("/", [App::getLocale(), "select", $type, $sd, $period, "bytour"])) }}",
				success: function(data){
					put_array_to_element($('#iTourSelector'), data);
				}
			});

			$.ajax({
				type: "GET",
				url: "{{ url(join("/", [App::getLocale(), "select", $type, $sd, $period, "byyear"])) }}",
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
			var age_range = [14, 16, 18, 20, 21, 25, 30, 35, 40]
			for (var i in age_range) {
				if (typeof(age_range[i]) === "function") continue;
				$('#iAgeSelector').append('<option value="U' + age_range[i] + '">' + age_range[i] + '-</option>');
				$('#iAgeSelector').append('<option value="A' + age_range[i] + '">' + age_range[i] + '+</option>');
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
				api.column('engname:name').search(val, false, true, true).draw();
			} );

			$('#iCountrySelector').on( 'change', function () {
				var val = $(this).val();
				ga('send', 'event', 'select', 'country', val, 1);
				if (val == "" || val == "All") val = ".*";
				api.column('ioc:name').search( "^" + val + "$", true, false ).draw();
			} );

			$('#iCountryHighlight').on( 'change', function () {
				var val = $(this).val();
				ga('send', 'event', 'hl', 'country', val, 1);
				var rows = api.rows(function(idx, data, node) {
					return data['ioc'] == val;
				}).nodes();
				$('tr.cDataTableHl').removeClass("cDataTableHl");
				$(rows).addClass("cDataTableHl");
				setCookie("hlcty", val, "page");
			} );

			$('#iTourSelector').on( 'change', function () {
				var val = $(this).val();
				ga('send', 'event', 'select', 'tournament', val, 1);
				val = val.replace(/\$/g, "\\\$");
				if (val == "" || val == "All") val = ".*";
				api.column('w_tour:name').search( "^" + val + "$", true, false ).draw();
			} );

			$('#iYearSelector, #iMonthSelector, #iDaySelector').on( 'change', function (){
				var y = $('#iYearSelector').val();
				var m = $('#iMonthSelector').val();
				var d = $('#iDaySelector').val();
				var _valid_date = is_valid_date(y + '-' + m + '-' + d);
				if (_valid_date === false){
					alert("非法日期！请重新选择");
				} else {
					api.column('birth:name').search(_valid_date, true, false).draw();
				}
			} );

			$('#iAgeSelector').on('change', function (){
				var val = $(this).val();
				ga('send', 'event', 'select', 'age', val, 1);
				if (val == "" || val == "All") {
					api.column('birth:name').search("1970-01-01", 'birth_ge', false).draw();
				} else {
					var a = val.substr(0, 1);
					var b = parseInt(val.substr(1));
					var thisYear = (new Date()).getFullYear();
					if (a == "U") {
						api.column('birth:name').search((thisYear - b) + "-01-01", 'birth_ge', false).draw();
					} else if (a == "A") {
						api.column('birth:name').search((thisYear - b) + "-01-01", 'birth_lt', false).draw();
					} else {
						return;
					}
				}
			})

		},

		"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {

			if (Number(aData['c_rank']) < Number(aData['highest'])){
				$('td', nRow).eq(0).addClass('cDataTableCareerHigh');
			}

			if ( aData['w_in'] == "1" )
			{
				$(nRow).addClass("DataIn");
			}
			if ( aData['w_in'] == "0" )
			{
				$(nRow).addClass("DataOut");
			}
			if ( aData['w_in'] == "3" )
			{
				$(nRow).addClass("DataUpcoming");
			}
			if ( aData['w_in'] == "" )
			{
				$(nRow).addClass("DataAbsent");
			}

			if ( aData['ioc'] === hlcty ) {
				$(nRow).addClass("cDataTableHl");
			}

			if (iDisplayIndex == 0) {
				$('body').animate({  
					scrollTop: $('#iRankTable').offset().top - 50
				}, 500);
			}

		},
		"columns": columns,
		"columnDefs": [],
	});

	var table1 = $('#iRankTable').DataTable();
	table1.draw();

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

	$(document).on('click', '#iRankTable>tbody>tr[role=row]', function () {

		$('#iMask').fadeIn(500).css('display', '-webkit-flex');

		var row = table1.row( $(this) );
		var id = row.data()['id'];
		$('#iMask').html('<div id=iAjaxNotice>' + '{{ __("frame.notice.gfw") }}'  + '</div>');

		var c_url = "{{ slash(url(join("/", [App::getLocale(), "breakdown", $type, $sd, $period]))) }}" + "/" + row.data()['engname'].replace(/ /g, "");
		_hmt.push(['_trackCustomEvent', 'rank_breakdown', {'gender':"{{ $type }}",'sd':"{{ $sd }}",'p1':row.data()['engname'].replace(/ /g, "")}]);
		ga('send', 'pageview', c_url);

		$.ajax({
			type: 'POST',
			url: "{{ url(join("/", [App::getLocale(), "breakdown", $type, $sd, $period, "query"])) }}",
			data: {id: id},
			success: function(data) {
				$('#iMask').html(data);
			}
		});
	});

	$('#iNameTranslation').on('click', function (e) {
		$('#iMask').fadeIn(500).css('display', '-webkit-flex');
		$('#iMask').html('<div id=iAjaxNotice>' + '{{ __("frame.notice.gfw") }}'  + '</div>');
		var c_url = "{{ slash(url(join("/", [App::getLocale(), "help", "translation", "name"]))) }}";
		_hmt.push(['_trackCustomEvent', 'help', {'tips':'translate_rule'}]);
		ga('send', 'pageview', c_url);
		$.ajax({
			type: 'GET',
			url: "{{ url(join("/", [App::getLocale(), "help", "translation", "name"])) }}",
			success: function(data) {
				$('#iMask').html(data);
			},
		});
	});

})
</script>

<div id="iRankPage">
	<div class="tips">
		<div class='cDataUpdateTimeTip'>
			{{ __("rank.table.timeTip.official") }} <span id='iRankOfficialTime'>{{ $official_time }}</span>
		</div>
		<div class='cDataUpdateTimeTip'>
			{{ __("rank.table.timeTip.update") }}
		</div>
		<div class='cDataUpdateTimeTip'>
			<span id='iRankUpdateTime'>{{ $update_time }}</span>
		</div>
		@if (App::isLocale('zh'))
			<div class='cDataUpdateTimeTip' id="iNameTranslation" style="cursor:pointer;font-weight:700;color:#757575;">点此了解关于球员中文译名的解释</div>
		@endif
	</div>
	<div class="tips">{{ __("rank.tips") }}</div>

	<input id=iMaterialPath type=hidden value="{{ env('CDN') }}" />

	<div id="iColumnFilter" class="hastitle cColumnFilter">
		<blockTitle class=hastitle_title>{{ __("rank.table.columnFilter.columnFilter") }}</blockTitle>
		<div data-column='eng_name'>{{ __("rank.table.columnFilter.eng_name") }}</div>
		<div data-column='change'>{{ __("rank.table.columnFilter.move") }}</div>
		<div data-column='f_rank'>{{ __("rank.table.columnFilter.officialRank") }}</div>
		<div data-column='highest'>{{ __("rank.table.columnFilter.careerHigh") }}</div>
		<div data-column='alt_point'>{{ __("rank.table.columnFilter.altScore") }}</div>
		<div data-column='flop'>{{ __("rank.table.columnFilter.drop") }}</div>
		<div data-column='w_point'>{{ __("rank.table.columnFilter.add") }}</div>
		<div data-column='engname'>Engname</div>
		<div data-column='age'>{{ __("rank.table.columnFilter.age") }}</div>
		<div data-column='birth'>{{ __("rank.table.columnFilter.birth") }}</div>
		<div data-column='nation'>{{ __("rank.table.columnFilter.nation") }}</div>
		<div data-column='id'>ID</div>
		<div data-column='ioc'>IOC</div>
		<div data-column='titles'>{{ __("rank.table.columnFilter.titles") }}</div>
		<div data-column='tour_c'>{{ __("rank.table.columnFilter.tourCount") }}</div>
		<div data-column='mand_0'>{{ __("rank.table.columnFilter.qz0") }}</div>
		<div data-column='streak'>{{ __("rank.table.columnFilter.streak") }}</div>
		<div data-column='prize'>{{ __("rank.table.columnFilter.prize") }}</div>
		<div data-column='win'>{{ __("rank.table.columnFilter.win") }}</div>
		<div data-column='lose'>{{ __("rank.table.columnFilter.lose") }}</div>
		<div data-column='win_r'>{{ __("rank.table.columnFilter.winRate") }}</div>
		<div data-column='q_tour'>{{ __("rank.table.columnFilter.qijiTour.$type.$sd") }}</div>
		<div data-column='q_point'>{{ __("rank.table.columnFilter.qijiPoint.$type.$sd") }}</div>
		<div data-column='w_in'>In</div>
		<div data-column='w_tour'>{{ __("rank.table.columnFilter.tour") }}</div>
		<div data-column='partner'>{{ __("rank.table.columnFilter.partner") }}</div>
		<div data-column='next_oppo'>{{ __("rank.table.columnFilter.opponent") }}</div>
		<div data-column='next_h2h'>H2H</div>
		<div data-column='predict_R64'>R64</div>
		<div data-column='predict_R32'>R32</div>
		<div data-column='predict_R16'>R16</div>
		<div data-column='predict_QF'>QF</div>
		<div data-column='predict_SF'>SF</div>
		<div data-column='predict_F'>F</div>
		<div data-column='predict_W'>W</div>
	</div>

	<div id="iRowFilter" class="hastitle cRowFilter">
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
		<select id=iAgeSelector class=cRowFilterInput><option value=All>{{ __("rank.table.rowFilter.filterAge") }}</option></select>
		<select id=iYearSelector class=cRowFilterInput><option value=0>{{ __("rank.table.rowFilter.filterYear") }}</option></select>-<select id=iMonthSelector class=cRowFilterInput><option value=0>{{ __("rank.table.rowFilter.filterMonth") }}</option></select>-<select id=iDaySelector class=cRowFilterInput><option value=0>{{ __("rank.table.rowFilter.filterDay") }}</option></select>
		<select id=iTourSelector class=cRowFilterInput><option value=All>{{ __("rank.table.rowFilter.filterTour") }}</option></select>
	</div>

	<table id="iRankTable" class="cRankTable plrank">
		<thead id='iRankTableHead' class="cRankTableHead">
			<tr>
				<th colspan="4">{{ __("rank.table.head.overview") }}</th>
				<th colspan="3">{{ __("rank.table.head.rank") }}</th>
				<th colspan="3">{{ __("rank.table.head.point") }}</th>
				<th colspan="6">{{ __("rank.table.head.player") }}</th>
				<th colspan="8">{{ __("rank.table.head.period.$period") }}</th>
				<th colspan="2">{{ __("rank.table.head.qiji.$type.$sd") }}</th>
				<th colspan="5">{{ __("rank.table.head.current") }}</th>
				<th colspan="7">{{ __("rank.table.head.predict") }}</th>
			</tr>
			<tr>
				<th>{{ __("rank.table.head.rank") }}</th>
				<th>{{ __("rank.table.head.point") }}</th>
				<th>{{ __("rank.table.head.name") }}</th>
				<th>{{ __("rank.table.head.eng_name") }}</th>
				<th>{{ __("rank.table.head.move") }}</th>
				<th>{{ __("rank.table.head.official") }}</th>
				<th>{{ __("rank.table.head.careerHigh") }}</th>
				<th>{{ __("rank.table.head.alt") }}</th>
				<th>{{ __("rank.table.head.drop") }}</th>
				<th>{{ __("rank.table.head.add") }}</th>
				<th>Engname</th>
				<th>{{ __("rank.table.head.age") }}</th>
				<th>{{ __("rank.table.head.birth") }}</th>
				<th>{{ __("rank.table.head.nation") }}</th>
				<th>ID</th>
				<th>IOC</th>
				<th>{{ __("rank.table.head.titles") }}</th>
				<th>{{ __("rank.table.head.tourCount") }}</th>
				<th>{{ __("rank.table.head.qz0") }}</th>
				<th>{{ __("rank.table.head.streak") }}</th>
				<th>{{ __("rank.table.head.prize") }}</th>
				<th>{{ __("rank.table.head.win") }}</th>
				<th>{{ __("rank.table.head.lose") }}</th>
				<th>{{ __("rank.table.head.winRate") }}</th>
				<th>{{ __("rank.table.head.qijiTour") }}</th>
				<th>{{ __("rank.table.head.qijiPoint") }}</th>
				<th>In</th>
				<th>{{ __("rank.table.head.tour") }}</th>
				<th>{{ __("rank.table.head.partner") }}</th>
				<th>{{ __("rank.table.head.opponent") }}</th>
				<th>H2H</th>
				<th>R64</th>
				<th>R32</th>
				<th>R16</th>
				<th>QF</th>
				<th>SF</th>
				<th>F</th>
				<th>W</th>
			</tr>
		</thead>
	</table>
	<div class=cPageLeft>&lt;&lt;</div>
	<div class=cPageMid>&nbsp;</div>
	<div class=cPageRight>&gt;&gt;</div>
</div>
@endsection
