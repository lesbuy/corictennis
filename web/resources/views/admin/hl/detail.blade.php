<meta name="_token" content="{!! csrf_token() !!}"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.jquery') }}"></script>

<script>

	$(function() {

		$('button').on('click', function () {

			var data = $(this).parent().serialize();
			var tr = $(this).parent().parent().parent();
			var me = $(this);

			$.ajax({
				url: "{{ url('admin/hl/save') }}",
				type: 'POST',
				data: data,
				success: function (data) {
					if (data == 1) {
						tr.css('background-color', '#bbb');
						me.css('background-color', 'buttonface');
						me.css('color', 'buttontext');
					} else {
						alert('提交失败');
					}
				}
			});
		});

		$('input[type=input]').on('change', function () {
			$(this).parent().parent().parent().css('background-color', 'transparent');
			$(this).parent().children('button').css('background-color', '#000');
			$(this).parent().children('button').css('color', '#fff');
		});

	});
</script>

<style>

	table {
		border-collapse:collapse;
	}

	td {
		border: 1px solid #ccc;
		border-collapos
	}

	form {
		margin: 0;
	}

	input[type=input] {
		background-color: transparent;
		border: 0;
	}

</style>

<div>
	<table>
		<tbody>
			@foreach ($ret as $match)
				<tr style="{{ $match['hl'] || $match['whole'] ? "background-color: #bbb" : "" }}">
					<td>{{ $match['year'] }}</td>
					<td>{{ $match['date'] }}</td>
					<td>{{ $match['matchdate'] }}</td>
					<td>{{ $match['eid'] }}</td>
					<td>{{ $match['city'] }}</td>
					<td>{{ $match['matchid'] }}</td>
					<td>{{ $match['round'] }}</td>
					<td>{{ $match['p1id'] }}</td>
					<td>{{ $match['p2id'] }}</td>
					<td>{{ $match['p1name'] }}</td>
					<td>{{ $match['p2name'] }}</td>
					<td>
						<form action="javascript:void(0)" method="POST">
							{{ csrf_field() }}
							<input type=hidden name=year value="{{ $match['year'] }}" />
							<input type=hidden name=date value="{{ $match['date'] }}" />
							<input type=hidden name=matchdate value="{{ $match['matchdate'] }}" />
							<input type=hidden name=eid value="{{ $match['eid'] }}" />
							<input type=hidden name=city value="{{ $match['city'] }}" />
							<input type=hidden name=matchid value="{{ $match['matchid'] }}" />
							<input type=hidden name=round value="{{ $match['round'] }}" />
							<input type=hidden name=p1id value="{{ $match['p1id'] }}" />
							<input type=hidden name=p2id value="{{ $match['p2id'] }}" />
							<input type=hidden name=p1name value="{{ $match['p1name'] }}" />
							<input type=hidden name=p2name value="{{ $match['p2name'] }}" />
							<input type=input name=hl placeholder="HL的URL" value="{{ $match['hl'] }}" />
							<input type=input name=whole placeholder="全场URL" value="{{ $match['whole'] }}" />
							<button type="submit">提交</button>
						</form>
					</td>
				</tr>
			@endforeach
		</tbody>
	</table>
</div>
