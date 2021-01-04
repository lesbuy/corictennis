@extends('layouts.header')

@section('content')

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.dcpk') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.rankpage') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.dcpk') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.rankpage') }}">
@endif
<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.datatables') }}">

<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.guessSign') }}"></script>
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
				url: "{{ url(join("/", [App::getLocale(), "guess", "sign", "query"])) }}",
				type: "POST",
				data: {device: device},
			},
			"iDisplayLength": -1,
			"order": [[ 3, "asc" ]],
			"columns": columns,
			"columnDefs": [],
			"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
				$(nRow).addClass("DataAbsent");
			},
		});

		var table1 = $('#iRankTable').DataTable();
		table1.column(0).search({{ $year }}).column(1).search({{ $week }}).draw();

		$('#iGuessSignUp').on('click', function () {
			var data = $('#iGuessSignForm').serialize();
			data += "&uuid=" + (uuid ? uuid : 0);
			$('#iMask').fadeIn(500).css('display', '-webkit-flex');
			$('#iMask').html('<div id=iAjaxNotice>' + '{{ __('frame.notice.gfw') }}'  + '</div>');

			$.ajax({
				url: '{{ url(join('/', [App::getLocale(), 'guess', 'sign', 'submit'])) }}',
				data: data,
				type: 'POST',
				success: function (data) {
					$('#iAjaxNotice').html(data);
				}
			});
		});

		$('#iGuessSignDdl').html(GetLocalDate($.trim($('#iGuessSignDdl').html()), 8));
	})


</script>

@if ($ret['status'] < 0)
	<div id=iAjaxNotice style="text-align: left;">
		{{ $ret['errmsg'] }}
	</div>
@else

	<div id=iGuessSign class="tips">
		{{ $year }} {{ __('dcpk.dcpk.week', ['p1' => $week]) }} {{ translate_tour($ret['tour']) }}({{ $ret['level'] }})<br>
		{{ $ret['start'] . ' ~ ' . $ret['end'] }}<br>
		@if ($ret['ddl'])
			{!! __('dcpk.rule.signDdl', ['p1' => $ret['start'], 'p2' => '<span id=iGuessSignDdl>' . $ret['ddl'] . '</span>']) !!}
		@else
			{{ __('dcpk.rule.signDdl', ['p1' => $ret['start'], 'p2' => $ret['start'] . " " . __('dcpk.rule.3hour')]) }}
		@endif
		<br>{!! __('dcpk.rule.ipRestrict') !!}
	</div>

	<form id=iGuessSignForm>
		<input type=hidden name=id value={{ Auth::id() }} />
		<input type=hidden name=year value={{ $year }} />
		<input type=hidden name=week value={{ $week }} />
		@if (!$ret['ddl'] || ($ret['ddl'] && time() < strtotime($ret['ddl'])))
			<div id=iGuessSignUp class="selected">{{ __('dcpk.dcpk.signup') }}</div>
		@endif
	</form>

	<table id="iRankTable" class="cRankTable plrank">
		<thead id='iRankTableHead' class="cRankTableHead">
			<tr>
				<th>{{ __("rank.table.head.year") }}</th>
				<th>{{ __("rank.table.head.week") }}</th>
				<th>{{ __("rank.table.head.username") }}</th>
				<th>{{ __("rank.table.head.signTime") }}</th>
				<th>{{ __("rank.table.head.itglPoint") }}</th>
				<th>{{ __("rank.table.head.dcpkPoint") }}</th>
				<th>{{ __("rank.table.head.qualifySeq") }}</th>
				<th>{{ __("rank.table.head.seedSeq") }}</th>
			</tr>
		</thead>
	</table>


@endif


@endsection
