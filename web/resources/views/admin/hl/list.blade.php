<div>
	<table>
		<tbody>
			@foreach ($ret as $tour)
				<tr>
					<td>{{ $tour[0] }}</td>
					<td>{{ $tour[1] }}</td>
					<td>{{ $tour[2] }}</td>
					<td>{{ $tour[3] }}</td>
					<td><a href="{{ url(join("/", ['admin', 'hl', 'detail', $tour[0], $tour[1], $tour[2], urlencode(urlencode($tour[3]))])) }}">进入</a></td>
				</tr>
			@endforeach
		</tbody>
	</table>
</div>
