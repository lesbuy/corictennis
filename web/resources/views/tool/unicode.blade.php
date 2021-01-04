<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<title>UNICODE查询</title>
<script type="text/javascript" language="javascript" src="/js/jquery.min.js"></script>
<script type="text/javascript" language="javascript" src="/js/tool.js?t={{ time() }}"></script>

<style>

body {
    white-space: nowrap;
}
table {
	border-collapse: collapse;
	display: inline-table;
	vertical-align: top;
}

td {
	width: 45px;
	height: 50px;
	font-size: 30px;
	border: 1px solid #ddd;
	text-align: center;
}

.cCodePoint {
	cursor: pointer;
	font-family: apple color emoji,segoe ui emoji,noto color emoji,android emoji,emojisymbols,emojione mozilla,twemoji mozilla,segoe ui symbol, sans-serif, serif;
}

#iDisplay {
	display: inline-block;
	margin: 20px;
	text-align: left;
}

#iIndex { 
	font-size: 25px;
	line-height: 1.8;
}
#iCode {
	font-size: 30px;
	line-height: 1.5;
	margin-top: 20px;
}
#iFont {
	margin: 100px auto;
}
#iFont > div{
	vertical-align: middle;
	font-size: 300px;
	line-height: 1;
	display: inline-block;
	font-family: apple color emoji,segoe ui emoji,noto color emoji,android emoji,emojisymbols,emojione mozilla,twemoji mozilla,segoe ui symbol, sans-serif, serif;
}

#iFont2 {
	height: 300px;
	border: 5px dotted #eee;
}
#iFont1 {
	background-color: #eee;
}

.hl {
	background-color: #999;
	color: #fff;
}

#iFind > span {
	cursor: pointer;
	background-color: #00f;
	color: #fff;
	padding: 0 5px;
	margin: 5px;
}
#iInput1, #iInput2 {
	font-size: 25px;
	width: 35px;
}
</style>

<script>
	$(function () {

		var td_mouseover_event =  function () {
			var x = $(this).attr('row');
			var y = $(this).attr('column');
			$('.hl').removeClass('hl');
			$('td[row=' + x + ']').addClass('hl');
			$('td[column=' + y + ']').addClass('hl');
			var unicode = $(this).attr('data-code');
			$('#iCode').html([
				"Unicode: " + unicode + " (" + UnicodeTool.toDecimal(unicode) + ")",
				"UTF-8: " + UnicodeTool.toUtf8(unicode),
				"UTF-16BE: " + UnicodeTool.toUtf16BE(unicode),
			].join("<br>"));
			$('#iFont2').html("&#x" + $(this).attr('data-code') + ";");
		};

		var input_return = function (e) {
			if (e.keyCode == 13) {
				$(this).prev().trigger('click');
			}
		};

		$('.cCodePoint').on('mouseover', td_mouseover_event);
		$('#iInput1, #iInput2').on('keydown', input_return);

		$('.cCodePoint').on('click', function () {
			$('.cCodePoint').off('mouseover', td_mouseover_event);
		});

		$('#iDisplay').on('mouseover', function () {
			$('.cCodePoint').on('mouseover', td_mouseover_event);
		});

		$('#iSearch').on('click', function () {
			var chr = $('#iInput2').val();
			if (chr === "") return;
			else {
				var uni = chr.codePointAt(0).toString(16);
				var grid; var pos;
				if (uni.length <= 2) {
					grid = '0'; pos = uni;
				} else {
					pos = uni.substr(-2);
					grid = uni.substr(0, uni.length - 2);
				}
				url = ["{{ url('/tool/unicode') }}", grid, pos].join("/");
				window.location.href = url;
			}
		});

		$('#iJump').on('click', function () {
			var chr = parseInt("0x" + $('#iInput1').val());
			if (chr !== undefined && chr !== false && !isNaN(chr)) {
				url = ["{{ url('/tool/unicode') }}", chr.toString(16)].join("/");
				window.location.href = url; 
			}
		});
			

		@if ($row && $col) 
			$('td[row="' + "{{ $row }}" + '"][column="' + "{{ $col }}" + '"]').trigger('mouseover');
		@endif

		$('#iInput2').trigger('focus');

	});
</script>

@if ($prefix !== null)

<table>
	<tr><td></td>
		@for ($i = 48; $i <= 70; ++$i)
			@if ($i < 58 || $i > 64)
				<td column="{{ $i }}">{{ chr($i) }}</td>
			@endif
		@endfor
	</tr>

	@for ($i = 48; $i <= 70; ++$i)
		@if ($i < 58 || $i > 64)
			<tr><td row="{{ $i }}">{{ strlen($prefix) == 1 ? "0" . $prefix : $prefix }}{{ chr($i) }}</td>
				@for ($j = 48; $j <= 70; ++$j)
					@if ($j < 58 || $j > 64)
						<td row="{{ $i }}" column="{{ $j }}" data-code="{{ (strlen($prefix) == 1 ? "0" . $prefix : $prefix) . chr($i) . chr($j) }}" class=cCodePoint>&#x{{ strlen($prefix) == 1 ? "0" . $prefix : $prefix }}{{ chr($i) }}{{ chr($j) }};</td>
					@endif
				@endfor
			</tr>
		@endif
	@endfor

</table>

<div id="iDisplay">

	<div id="iIndex">
		@if ($prev !== null)
			<a href="{{ url('/tool/unicode/' . $prev) }}">上一页</a>
		@endif
		@if ($next !== null)
			<a href="{{ url('/tool/unicode/' . $next) }}">下一页</a>
		@endif
		<div id="iFind">
			<span id="iJump">跳转到页</span><input type=text id="iInput1" />
			<span id="iSearch">查找字符</span><input type=text id="iInput2" />
		</div>
		<div>当前为第{{ $plain }}平面，第{{ $x }}行，第{{ $y }}列</div>
	</div>
	<div id="iCode">&nbsp;<br>&nbsp;<br>&nbsp;<br></div>
	<div id="iFont">
		<div id="iFont1">&ensp;</div>
		<div id="iFont2"></div>
	</div>
	<div>灰色方形为空格占位符。虚线框的宽度才是真实unicode字符的宽度<br>若虚线框宽度为0，字符出现在灰色方形里，则该字符是对前一个字符的修饰</div>
</div>

@endif
