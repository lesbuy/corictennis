<style>
table {
	border-collapse: collapse;
}
td {
	font-size: 12px;
	border: 1px solid #ccc;
	text-align: center;
	padding: 0 1px;
}
</style>

本站 {{ $ret['tour'] }}, 开始于 {{ $ret['start_date'] }}, 下一个GS是 {{ $ret['next_gs'] }}

<table><tbody>

<tr>
	@foreach (array_values($ret['data'])[0] as $key => $value)
		<td>{!! $key !!}</td>
	@endforeach
</tr>
@foreach ($ret['data'] as $pid => $v)
	<tr>
		@foreach ($v as $key => $value)
			<td>{!! $value !!}</td>
		@endforeach
	</tr>
@endforeach 

</tbody></table>
