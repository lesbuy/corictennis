@extends('layouts.header')

@section('content')

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.dcpk') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.dcpk') }}">
@endif

<script>
	$(function () {
		$('input:checkbox').change(function () {
			var all = $(this).parent().parent().find('input:text');
			if ($(this).is(':checked')) {
				all.attr('disabled', false);
			} else {
				all.attr('disabled', true);
			}
		});

		$('#submit').click(function () {

			var form = $(this).parent();
			var data = form.serialize();

			var url = "{{ url(join('/', [App::getLocale(), 'guess', 'select', $ret['date'], 'save'])) }}";

			$.ajax({
				type: 'POST',
				data: data,
				url: url,
				success: function (data) {
					$('#iGuessSelectMessage').html(data);
				}
			});
		});

		$('#submitOne').click(function () {

			var form = $(this).parent();
			var data = form.serialize();

			var url = "{{ url(join('/', [App::getLocale(), 'guess', 'select', $ret['date'], 'saveOne'])) }}";

			$.ajax({
				type: 'POST',
				data: data,
				url: url,
				success: function (data) {
					$('#iGuessSelectMessage').html(data);
				}
			});
		});

		$('#delete').click(function () {

			var form = $(this).parent();
			var data = form.serialize();

			var url = "{{ url(join('/', [App::getLocale(), 'guess', 'select', $ret['date'], 'delete'])) }}";

			$.ajax({
				type: 'POST',
				data: data,
				url: url,
				success: function (data) {
					$('#iGuessSelectMessage').html(data);
				}
			});
		});

		$('#abandon').click(function () {

			var form = $(this).parent();
			var data = form.serialize();

			var url = "{{ url(join('/', [App::getLocale(), 'guess', 'select', $ret['date'], 'abandon'])) }}";

			$.ajax({
				type: 'POST',
				data: data,
				url: url,
				success: function (data) {
					$('#iGuessSelectMessage').html(data);
				}
			});
		});

		@foreach ($ret['avail'] as $k => $v)
			$('input:checkbox[name="' + '{{ $k }}' + '_checkbox"]').trigger('click');
		@endforeach

	});
</script>

<div id="iGuessSelect">
	<div id=iGuessSelectMessage></div>

	<div>Add One Record</div>
	<form method=POST id=iGuessAddForm>
		<div id=submitOne>提交</div>

		<input type=text name="eid" placeholder="eid" />
		<input type=text name="matchid" placeholder="matchid" />
		<input type=text name="city" placeholder="city，全小写" />
		<input type=text name="round" placeholder="round" />
		<input type=text name="tour" placeholder="tour，可以不用填" />
		<input type=text name="p1id" placeholder="p1id，不用填" />
		<input type=text name="p2id" placeholder="p2id，不用填" />
		<input type=text name="p1eng" placeholder="p1eng" />
		<input type=text name="p2eng" placeholder="p2eng" />
		<input type=text name="earliest" placeholder="earliest" />
	</form>

	<div>Add By Schedules</div>
	<form method=POST id=iGuessSelectForm>
		<div id=submit>提交</div>
		<div id=delete>删除</div>
		<div id=abandon>作废</div>

		<table><tbody>
			@if (isset($ret['matches']))
				@foreach ($ret['matches'] as $match)
					<tr>
						<td><input style="background-color: #666;color:#fff" type=text name="{{ $match['eid'] }}`{{ $match['matchid'] }}`city" value="{{ $match['city'] }}" disabled /></td>
						<td><input style="height:10px;width:10px;opacity:100" type=checkbox name="{{ $match['eid'] }}`{{ $match['matchid'] }}`checkbox" /></td>
						<td><input type=text name="{{ $match['eid'] }}`{{ $match['matchid'] }}`round" value="{{ $match['round'] }}" disabled /></td>
						<td><input type=text name="{{ $match['eid'] }}`{{ $match['matchid'] }}`tour" value="{{ $match['tour'] }}" disabled /></td>
						<td><input type=text name="{{ $match['eid'] }}`{{ $match['matchid'] }}`eid" value="{{ $match['eid'] }}" disabled /></td>
						<td><input type=text name="{{ $match['eid'] }}`{{ $match['matchid'] }}`matchid" value="{{ $match['matchid'] }}" disabled /></td>
						<td><input type=text name="{{ $match['eid'] }}`{{ $match['matchid'] }}`p1id" value="{{ $match['p1id'] }}" disabled /></td>
						<td><input type=text name="{{ $match['eid'] }}`{{ $match['matchid'] }}`p2id" value="{{ $match['p2id'] }}" disabled /></td>
						<td><input type=text name="{{ $match['eid'] }}`{{ $match['matchid'] }}`p1eng" value="{{ $match['p1eng'] }}" placeholder="如果修改在此填" disabled /></td>
						<td><input type=text name="{{ $match['eid'] }}`{{ $match['matchid'] }}`p2eng" value="{{ $match['p2eng'] }}" placeholder="如果修改在此填" disabled /></td>
						<td><input type=text name="{{ $match['eid'] }}`{{ $match['matchid'] }}`earliest" value="{{ $match['earliest'] }}" disabled /></td>
					</tr>
				@endforeach
			@endif
		</tbody></table>
	</form>
</div>
@endsection
