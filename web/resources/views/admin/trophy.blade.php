<head>
	<meta charset="utf-8">
	<meta name="_token" content="{!! csrf_token() !!}"/>
	<meta name="csrf-token" content="{!! csrf_token() !!}"/>
	<title>添加夺冠图片</title>
<!--	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/jquery.min.js') }}"></script>-->
	<script type="text/javascript" language="javascript" src="http://code.jquery.com/jquery-latest.js"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/jquery.lazy.min.js?v=1.0.0') }}"></script>
	<script>
		$(function(){
			$('.trophy_img').Lazy({
				beforeLoad: function(element){
					console.log('image "' + element.data('src') + '" is about to be loaded');
				},
				afterLoad: function(element) {
					console.log('image "' + element.data('src') + '" was loaded successfully');
				},
				onLoad :function (element) {
					console.log('image "' + element.data('src') + '" is LOADING');
				},
			});
			$('.inputbox').bind('input propertychange', function() {
				$(this).next().val(1);
				$(this).parent().parent().find('.trophy_img').css("background-image", "url(" + $(this).val() + ")");
			});

			$('input[type=radio]').on('click', function () {
				var val = $(this).val();
				var img = $(this).parent().parent().find('.trophy_img');
				img.removeClass('pos1');
				img.removeClass('pos-1');
				img.removeClass('pos0');
				img.addClass('pos' + val);
			});
		})
	</script>
</head>
<body>

<style type="text/css">
table {
	width: 100%;
	font-size: 20px;
	line-height: 25px;
	border-collapse: collapse;
}

table td{
	border: 1px solid #ccc;
	padding: 5px;
}

table td:last-child{
	width: 40%;
}

table .inputbox{
	background: transparent;
}

table div.trophy_img{
	height: 97.2px;
	width: 180px;
	position: relative;
	background-position: top;
	background-size: cover;
	display: block;
}
.shadow1, .shadow2 {
	position: absolute;
	left: 0;
	right: 0;
	height: 18.25%;
	background-color: rgba(0,0,0,0.9);
}
.trophy_img.pos0 .shadow1, .trophy_img.pos1 .shadow1 {top: 0}
.trophy_img.pos-1 .shadow1 {bottom: 18.25%}
.trophy_img.pos0 .shadow2, .trophy_img.pos-1 .shadow2 {bottom: 0}
.trophy_img.pos1 .shadow2 {top: 18.25%}

table tr:hover{
	background: rgba(0,0,0,0.1);
}

input:checked + label {
	background-color: #000;
	color: #fff;
}
input[type=radio] {
	width: 0;
	height: 0;
	opacity: 0;
}
input + label {
	cursor: pointer;
	white-space: nowrap;
}

#submit{
	position: fixed;
	left: 10px;
	top: 10px;
	font-size: 20px;
	color: #fff;
	line-height: 60px;
	height: 60px;
	border: 0 solid #bbb;
	border-radius: 50px;
	width: 60px;
	cursor: pointer;
	background: -moz-radial-gradient(center, ellipse cover,  rgba(0,0,0,0.57) 0%, rgba(0,0,0,0) 100%); /* FF3.6-15 */
	background: -webkit-radial-gradient(center, ellipse cover,  rgba(0,0,0,0.57) 0%,rgba(0,0,0,0) 100%); /* Chrome10-25,Safari5.1-6 */
	background: radial-gradient(ellipse at center,  rgba(0,0,0,0.57) 0%,rgba(0,0,0,0) 100%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#91000000', endColorstr='#00000000',GradientType=1 ); /* IE6-9 fallback on horizontal gradient */
}
</style>

<form action="/admin/update/trophy/submit" method="post" name="pics" id="pics"> 
	{{ csrf_field() }}
	<table>
		@foreach ($winners as $winner)
			<tr>
				<td>{{ $winner['date'] }}</td>
				<td>{{ $winner['city'] }}</td>
				<td>{{ $winner['win'] }}</td>
				<td><div class="trophy_img pos{{ $winner['pos'] }}" data-src="{{ $winner['ori'] }}"><div class="shadow1"></div><div class="shadow2"></div></div></td>
				<td>
					<input type=radio name="pos{{ $winner['id'] }}" id="i-{{ $winner['id'] }}-top" value="-1" {{ $winner['pos'] == -1 ? "checked" : "" }}/><label for="i-{{ $winner['id'] }}-top">上部</label>
					<input type=radio name="pos{{ $winner['id'] }}" id="i-{{ $winner['id'] }}-center" value="0" {{ $winner['pos'] == 0 ? "checked" : "" }} /><label for="i-{{ $winner['id'] }}-center">中部</label>
					<input type=radio name="pos{{ $winner['id'] }}" id="i-{{ $winner['id'] }}-bottom" value="1" {{ $winner['pos'] == 1 ? "checked" : "" }}/><label for="i-{{ $winner['id'] }}-bottom">下部</label>
				</td>
				<td>
					<input class="inputbox" style="width:100%" name="image{{ $winner['id'] }}" value="{{ $winner['ori'] }}" type=text />
					<input name="change{{ $winner['id'] }}" value="0" type=hidden />
				</td>
			</tr>
		@endforeach
	</table>
	<input id="submit" type="submit" />
</form> 
</body>
