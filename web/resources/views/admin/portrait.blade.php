<style>
.person {
	margin: 5px;
	display: inline-block;
	border: 2px solid blue;
	text-align: center;
}

.old, .new {
	height: 452.25px;
	width: 284.25px;
	background-size: contain;
	background-position: top;
	background-repeat: no-repeat;
	display: inline-block;
}

.new {
	cursor: pointer;
	position: relative;
}

.new:before {
	content: '新图';
	position: absolute;
	top: 1%;
	right: 2%;
	font-size: 15px;
}
.selected:after {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background-color: yellow;
	opacity: 0.4;
}

#submit {
	cursor: pointer;
	font-size: 20px;
	line-height: 50px;
	width: 120px;
	text-align: center;
	background-color: #05f;
	color: #fff;
}

#tb td {
	font-size: 13px;
	line-height: 20px;
}
</style>

<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.jquery') }}"></script>

<script>

	$(function () {
		$('.new').on('click', function () {
			if ($(this).hasClass('selected')) {
				$(this).removeClass('selected');
			} else {
				$(this).addClass('selected');
			}
		});

		$('#submit').on('click', function () {
			$('#tb').html('');
			$('.selected').each(function () {
				$('#tb').append('<tr><td>' + $(this).attr('data-id') + '</td><td>' + $(this).attr('data-name') + '</td><td>' + $(this).attr('data-url') + '</td></tr>');
			})
		})
	})

</script>

需要更新的，在新图上点击一下。全部点完后再提交。提交后将下方出现的内容复制给我
<div id=submit>提交</div>

<div id="update_result">
	<table><tbody id="tb">

	</tbody></table>
</div>

<div id="pic_area">
	@foreach ($ret as $p)
		<div class="person">
			<div>{{ join("\t", [$p[0], $p[1], $p[2]]) }}</div>
			<div class="old" style="background-image: url({{ $p[3] }})"></div>
			@if ($p[4] != "")
				<div class="new" data-id="{{ $p[1] }}" data-name="{{ $p[2] }}" data-url="{{ $p[5] }}" style="background-image: url({{ $p[4] }})"></div>
			@endif
		</div>
	@endforeach
</div>
