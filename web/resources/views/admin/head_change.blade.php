<style>
div.img {
	width: 108px;
	height: 144px;
	background-position: center;
	background-size: cover;
}

input[type=checkbox] {
	width: 50px;
	height: 50px;
}
</style>

<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/jquery.min.js') }}"></script>

<script>
 
$(function() {
	$('#submit').on('click', function(e) {
		var arr = [];
		$('.checkbox').each(function () {
			if ($(this).prop("checked")) {
				arr.push($(this).attr('data-id'));
			}
		});

		document.write(arr.join("<br>"));
	});
});
</script>

<form id=form>
<table><tbody>
	@foreach ($ret as $line)
		<tr>
			<td>{{ $loop->iteration }}</td>
			<td>{{ $line[0] }}</td>
			<td>{{ $line[1] }}</td>
			<td><div class=img style="background-image: url({{ url(join("/", ["images", $sex."_headshot", $line[2]])) }})"></div></td>
			<td>是否该换成</td>
			<td><div class=img style="background-image: url({{ $line[3] }})"></div></td>
			<td><input class=checkbox type=checkbox data-id="{{ $line[0] }}" style="zoom:200%;" /></td>
		</tr>
	@endforeach
</tbody></table>

<input type=button id=submit value="提交" />
</form>
