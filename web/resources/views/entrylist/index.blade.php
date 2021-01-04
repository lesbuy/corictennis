@extends('layouts.header')

@section('content')

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.rankpage') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.rankpage') }}">
@endif
<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.datatables') }}">

<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.echarts') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.schedule') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.datatables') }}"></script>

<script type="text/javascript" language="javascript" class="init">

$(function() {

	unavailable_columns = get_unavailable_columns(device, "{{ $type }}");
	invisible_columns = get_invisible_columns(device, "{{ $type }}");
	columns = get_columns(device, "{{ $type }}");

	var table = $('#iEntrylistTable').dataTable( {
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
			url: "{{ url(join("/", [App::getLocale(), "entrylist", $type, "query"])) }}",
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
				api.column('name:name').search(val, false, true, true).draw();
			} );

		},

		"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
			$(nRow).addClass("DataAbsent"); 
		},
		"columns": columns,
		"columnDefs": [],
	});

	var table1 = $('#iEntrylistTable').DataTable();

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

<div class=tips>
	{{ __('frame.dataSrc') }} <a href="http://tennisteen.it/entry-list.html" target=_blank >http://tennisteen.it/entry-list.html</a>
</div>
<div id="iEntrylistPage" class="cRankPage">
	<input id=iMaterialPath type=hidden value="{{ env('CDN') }}" />

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
	</div>

	<table id="iEntrylistTable" class="cRankTable plrank noPointer">
		<thead id='iEntrylistTableHead' class="cRankTableHead">
			<tr>
				<th>{{ __("rank.table.head.rank") }}</th>
				<th>{{ __("rank.table.head.player") }}</th>
				@for ($i = 1; $i <= 6; ++$i)
					<th> {{ date('Y-m-d', strtotime('+' . $i . ' week last monday')) }} </th>
				@endfor
			</tr>
		</thead>
	</table>
	<div class=cPageLeft>&lt;&lt;</div>
	<div class=cPageMid>&nbsp;</div>
	<div class=cPageRight>&gt;&gt;</div>
</div>
@endsection
