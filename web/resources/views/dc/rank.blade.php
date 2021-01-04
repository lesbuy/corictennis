@extends('layouts.header')

@section('content')

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.dc') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.rankpage') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.dc') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.rankpage') }}">
@endif
<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.datatables') }}">

<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.dcRank') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.datatables') }}"></script>

<script type="text/javascript" language="javascript" class="init">

	columns = get_columns(device);

	$(function() {
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
				url: "{{ url(join("/", [App::getLocale(), "dc", $eid, $year, $sextip, "rank", "query"])) }}",
				type: "POST",
				data: {device: device},
			},
			"iDisplayLength": 100,
			"order": [[ 5, "asc" ]],
			"columns": columns,
			"columnDefs": [],

			initComplete: function ( settings ) {

				{{-- Event Setting --}}
				var api = new $.fn.dataTable.Api( settings );

				$('#iPageSelector').on( 'change', function () {
					var val = parseInt($(this).val()) - 1;
					api.page(val).draw(false);
				});

				$('#iPagelenSelector').on( 'change', function () {
					var val = $(this).val();
					api.page.len(val).draw(false);
				} );

				$('#iNameSearcher').on( 'keyup change', function (){
					var val = $(this).val();
					api.column('username:name').search(val, false, true, true).draw();
				} );

				$('.cPageRight, .cPageLeft').on('click', function () {
					var val = parseInt($(this).attr('value'));
					if (val >= 0 && val < api.page.info().pages) {
						api.page(val).draw(false);
					}
				});
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

			"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
				if ({{ Auth::check() }}  && aData['userid'] == {{ Auth::id() }}) {
					$(nRow).addClass("DataIn");
				} else {
					$(nRow).addClass("DataAbsent");
				}
			}

		});

		var table1 = $('#iRankTable').DataTable();
		table1.column(0).search({{ $year }}).column(1).search("{{ $eid }}").column(2).search("{{ $sextip }}").draw();

		$(document).on('click', '#iRankTable>tbody>tr[role=row]', function () {

			var row = table1.row( $(this) );
			var href = row.data()['link'];
			window.location.href = href;

		});


	})


</script>

<div id=iDcRank>

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
		<input id=iNameSearcher type=text placeholder="{{ __("rank.table.rowFilter.searchUser") }}" class=cRowFilterInput></input>
	</div>

	<table id="iRankTable" class="cRankTable plrank">
		<thead id='iRankTableHead' class="cRankTableHead">
			<tr>
				<th>{{ __("rank.table.head.year") }}</th>
				<th>{{ __("rank.table.head.eid") }}</th>
				<th>{{ __("rank.table.head.sextip") }}</th>
				<th>{{ __("rank.table.head.userid") }}</th>
				<th>{{ __("rank.table.head.username") }}</th>
				<th>{{ __("rank.table.head.rank") }}</th>
				<th>{{ __("rank.table.head.score") }}</th>
				<th>{{ __("rank.table.head.matches") }}</th>
				<th>{{ __("rank.table.head.link") }}</th>
			</tr>
		</thead>
	</table>
	<div class=cPageLeft>&lt;&lt;</div>
	<div class=cPageMid>&nbsp;</div>
	<div class=cPageRight>&gt;&gt;</div>

</div>

@endsection
