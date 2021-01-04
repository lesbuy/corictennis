<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<title>UNICODE旗帜查询</title>
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
	width: 32px;
	font-size: 30px;
	line-height: 32px;
	text-align: center;
	padding: 0;
/*	box-shadow: 0 0 1px rgba(0,0,0,0.1);
	transform: skewX(12deg);*/
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
#iFont > div{
	vertical-align: middle;
	font-size: 500px;
	line-height: 1;
	display: inline-block;
	font-family: apple color emoji,segoe ui emoji,noto color emoji,android emoji,emojisymbols,emojione mozilla,twemoji mozilla,segoe ui symbol, sans-serif, serif;
}

#iFont2 {
	height: 500px;
}

.hl {
	background-color: #ddd;
}

img.flag {
	height: 100px;
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
			$('.focus').removeClass('focus');
			$(this).addClass('focus');
			var unicode = $(this).attr('data-code').split("/");
			if ($(this).attr('data-iso3')) {
				$('#iCode').html([
					"ISO2代码: " + x + y,
					"ISO3代码: " + $(this).attr('data-iso3'),
					"IOC代码: " + $(this).attr('data-ioc') + ($(this).attr('data-isioc') == 2 ? " 不是IOC成员" : ""),
					"国名: " + $(this).attr('data-eng') + " / " + $(this).attr('data-chn'),
					"全名: " + $(this).attr('data-eng-long') + " / " + $(this).attr('data-chn-long'),
					"Unicode: " + "\\u" + unicode[0] + "\\u" + unicode[1],
					"UTF-8: " + UnicodeTool.toUtf8(unicode[0]) + UnicodeTool.toUtf8(unicode[1]),
					"UTF-16BE: " + UnicodeTool.toUtf16BE(unicode[0]) + UnicodeTool.toUtf16BE(unicode[1]),
				].join("<br>"));
			} else {
				$('#iCode').html("");
			}
			$('#iFont2').html("<img class=flag src='/images/iso2_svg/" + x + y + ".svg' />" + '<br>' + $(this).html());
		};

		$('.cCodePoint').on('mouseover', td_mouseover_event);

		$('.cCodePoint').on('click', function () {
			$('.cCodePoint').off('mouseover', td_mouseover_event);
		});

		$('#iDisplay').on('mouseover', function () {
			$('.cCodePoint').on('mouseover', td_mouseover_event);
		});

		$('#iInput2').trigger('focus');

		var input_return = function (e) {
			var td = $('.focus');
			if (td.length != 1) return;
			var x = td.attr('row').charCodeAt(0);
			var y = td.attr('column').charCodeAt(0);
			if (e.keyCode == 37) { if (y == 65) return; else y -= 1;}
			else if (e.keyCode == 38) { if (x == 65) return; else x -= 1;}
			else if (e.keyCode == 39) { if (y == 90) return; else y += 1;}
			else if (e.keyCode == 40) { if (x == 90) return; else x += 1;}
			else return;
			var _x = String.fromCharCode(x);
			var _y = String.fromCharCode(y);
			$('td[row=' + _x + '][column=' + _y + ']').trigger('mouseover');
		};

		$(document).on('keydown', input_return);

	});
</script>
<div class="flags flag-jp"></div>

<table>
	<tr><td></td>
		@for ($i = 0xe6; $i <= 0xff; ++$i)
			<td column="{{ chr($i - 165) }}">{{ chr($i - 165) }}</td>
		@endfor
	</tr>

	@for ($i = 0xe6; $i <= 0xff; ++$i)
		<tr><td row="{{ chr($i - 165) }}">{{ chr($i - 165) }}</td>
			@for ($j = 0xe6; $j <= 0xff; ++$j)
				@php $chri = chr($i - 165); $chrj = chr($j - 165); @endphp
				<td row="{{ $chri }}" column="{{ $chrj }}" data-code="1f1{{ dechex($i) }}/1f1{{ dechex($j) }}" class=cCodePoint data-iso3="{{ isset($ret[$chri . $chrj]) ? $ret[$chri . $chrj][0] : "" }}" data-ioc="{{ isset($ret[$chri . $chrj]) ? $ret[$chri . $chrj][1] : "" }}" data-eng="{{ isset($ret[$chri . $chrj]) ? $ret[$chri . $chrj][2] : "" }}" data-chn="{{ isset($ret[$chri . $chrj]) ? $ret[$chri . $chrj][3] : "" }}" data-isioc="{{ isset($ret[$chri . $chrj]) ? $ret[$chri . $chrj][4] : "" }}" data-eng-long="{{ isset($ret[$chri . $chrj]) ? $ret[$chri . $chrj][5] : "" }}" data-chn-long="{{ isset($ret[$chri . $chrj]) ? $ret[$chri . $chrj][6] : "" }}">{!!
					isset($ret[$chri . $chrj]) ? "&#x1f1" . dechex($i) . ";&#x1f1" . dechex($j) . ";" : ""
				!!}</td>
			@endfor
		</tr>
	@endfor

</table>

<div id="iDisplay">

	<div id="iCode">&nbsp;<br>&nbsp;<br>&nbsp;<br></div>
	<div id="iFont">
		<div id="iFont2"></div>
	</div>
</div>

<style>
	.flags {
		background-image: url(/images/tips/flags-1.png);
		background-repeat: no-repeat;
		width: 112px;
		height: 70px;
	}

	.flag-ad {background-position: -336px 0px;}
	.flag-ae {background-position: -448px 0px;}
	.flag-af {background-position: -560px 0px;}
	.flag-ag {background-position: -672px 0px;}
	.flag-ai {background-position: -896px 0px;}
	.flag-al {background-position: -1232px 0px;}
	.flag-am {background-position: -1344px 0px;}
	.flag-an {background-position: -1456px 0px;}
	.flag-ao {background-position: -1568px 0px;}
	.flag-aq {background-position: -1792px 0px;}
	.flag-ar {background-position: -1904px 0px;}
	.flag-as {background-position: -2016px 0px;}
	.flag-at {background-position: -2128px 0px;}
	.flag-au {background-position: -2240px 0px;}
	.flag-aw {background-position: -2464px 0px;}
	.flag-ax {background-position: -2576px 0px;}
	.flag-az {background-position: -2800px 0px;}
	.flag-ba {background-position: 0px -70px;}
	.flag-bb {background-position: -112px -70px;}
	.flag-bd {background-position: -336px -70px;}
	.flag-be {background-position: -448px -70px;}
	.flag-bf {background-position: -560px -70px;}
	.flag-bg {background-position: -672px -70px;}
	.flag-bh {background-position: -784px -70px;}
	.flag-bi {background-position: -896px -70px;}
	.flag-bj {background-position: -1008px -70px;}
	.flag-bl {background-position: -1232px -70px;}
	.flag-bm {background-position: -1344px -70px;}
	.flag-bn {background-position: -1456px -70px;}
	.flag-bo {background-position: -1568px -70px;}
	.flag-bq {background-position: -1792px -70px;}
	.flag-br {background-position: -1904px -70px;}
	.flag-bs {background-position: -2016px -70px;}
	.flag-bt {background-position: -2128px -70px;}
	.flag-bv {background-position: -2352px -70px;}
	.flag-bw {background-position: -2464px -70px;}
	.flag-by {background-position: -2688px -70px;}
	.flag-bz {background-position: -2800px -70px;}
	.flag-ca {background-position: 0px -140px;}
	.flag-cc {background-position: -224px -140px;}
	.flag-cd {background-position: -336px -140px;}
	.flag-cf {background-position: -560px -140px;}
	.flag-cg {background-position: -672px -140px;}
	.flag-ch {background-position: -784px -140px;}
	.flag-ci {background-position: -896px -140px;}
	.flag-ck {background-position: -1120px -140px;}
	.flag-cl {background-position: -1232px -140px;}
	.flag-cm {background-position: -1344px -140px;}
	.flag-cn {background-position: -1456px -140px;}
	.flag-co {background-position: -1568px -140px;}
	.flag-cr {background-position: -1904px -140px;}
	.flag-cs {background-position: -2016px -140px;}
	.flag-cu {background-position: -2240px -140px;}
	.flag-cv {background-position: -2352px -140px;}
	.flag-cw {background-position: -2464px -140px;}
	.flag-cx {background-position: -2576px -140px;}
	.flag-cy {background-position: -2688px -140px;}
	.flag-cz {background-position: -2800px -140px;}
	.flag-dd {background-position: -336px -210px;}
	.flag-de {background-position: -448px -210px;}
	.flag-dj {background-position: -1008px -210px;}
	.flag-dk {background-position: -1120px -210px;}
	.flag-dm {background-position: -1344px -210px;}
	.flag-do {background-position: -1568px -210px;}
	.flag-dz {background-position: -2800px -210px;}
	.flag-ec {background-position: -224px -280px;}
	.flag-ee {background-position: -448px -280px;}
	.flag-eg {background-position: -672px -280px;}
	.flag-eh {background-position: -784px -280px;}
	.flag-er {background-position: -1904px -280px;}
	.flag-es {background-position: -2016px -280px;}
	.flag-et {background-position: -2128px -280px;}
	.flag-eu {background-position: -2240px -280px;}
	.flag-fi {background-position: -896px -350px;}
	.flag-fj {background-position: -1008px -350px;}
	.flag-fk {background-position: -1120px -350px;}
	.flag-fm {background-position: -1344px -350px;}
	.flag-fo {background-position: -1568px -350px;}
	.flag-fr {background-position: -1904px -350px;}
	.flag-ga {background-position: 0px -420px;}
	.flag-gb {background-position: -112px -420px;}
	.flag-gd {background-position: -336px -420px;}
	.flag-ge {background-position: -448px -420px;}
	.flag-gf {background-position: -560px -420px;}
	.flag-gg {background-position: -672px -420px;}
	.flag-gh {background-position: -784px -420px;}
	.flag-gi {background-position: -896px -420px;}
	.flag-gl {background-position: -1232px -420px;}
	.flag-gm {background-position: -1344px -420px;}
	.flag-gn {background-position: -1456px -420px;}
	.flag-gp {background-position: -1680px -420px;}
	.flag-gq {background-position: -1792px -420px;}
	.flag-gr {background-position: -1904px -420px;}
	.flag-gs {background-position: -2016px -420px;}
	.flag-gt {background-position: -2128px -420px;}
	.flag-gu {background-position: -2240px -420px;}
	.flag-gw {background-position: -2464px -420px;}
	.flag-gy {background-position: -2688px -420px;}
	.flag-hk {background-position: -1120px -490px;}
	.flag-hm {background-position: -1344px -490px;}
	.flag-hn {background-position: -1456px -490px;}
	.flag-hr {background-position: -1904px -490px;}
	.flag-ht {background-position: -2128px -490px;}
	.flag-hu {background-position: -2240px -490px;}
	.flag-id {background-position: -336px -560px;}
	.flag-ie {background-position: -448px -560px;}
	.flag-il {background-position: -1232px -560px;}
	.flag-im {background-position: -1344px -560px;}
	.flag-in {background-position: -1456px -560px;}
	.flag-io {background-position: -1568px -560px;}
	.flag-iq {background-position: -1792px -560px;}
	.flag-ir {background-position: -1904px -560px;}
	.flag-is {background-position: -2016px -560px;}
	.flag-it {background-position: -2128px -560px;}
	.flag-je {background-position: -448px -630px;}
	.flag-jm {background-position: -1344px -630px;}
	.flag-jo {background-position: -1568px -630px;}
	.flag-jp {background-position: -1680px -630px;}
	.flag-ke {background-position: -448px -700px;}
	.flag-kg {background-position: -672px -700px;}
	.flag-kh {background-position: -784px -700px;}
	.flag-ki {background-position: -896px -700px;}
	.flag-km {background-position: -1344px -700px;}
	.flag-kn {background-position: -1456px -700px;}
	.flag-kp {background-position: -1680px -700px;}
	.flag-kr {background-position: -1904px -700px;}
	.flag-kw {background-position: -2464px -700px;}
	.flag-ky {background-position: -2688px -700px;}
	.flag-kz {background-position: -2800px -700px;}
	.flag-la {background-position: 0px -770px;}
	.flag-lb {background-position: -112px -770px;}
	.flag-lc {background-position: -224px -770px;}
	.flag-li {background-position: -896px -770px;}
	.flag-lk {background-position: -1120px -770px;}
	.flag-lr {background-position: -1904px -770px;}
	.flag-ls {background-position: -2016px -770px;}
	.flag-lt {background-position: -2128px -770px;}
	.flag-lu {background-position: -2240px -770px;}
	.flag-lv {background-position: -2352px -770px;}
	.flag-ly {background-position: -2688px -770px;}
	.flag-ma {background-position: 0px -840px;}
	.flag-mc {background-position: -224px -840px;}
	.flag-md {background-position: -336px -840px;}
	.flag-me {background-position: -448px -840px;}
	.flag-mf {background-position: -560px -840px;}
	.flag-mg {background-position: -672px -840px;}
	.flag-mh {background-position: -784px -840px;}
	.flag-mk {background-position: -1120px -840px;}
	.flag-ml {background-position: -1232px -840px;}
	.flag-mm {background-position: -1344px -840px;}
	.flag-mn {background-position: -1456px -840px;}
	.flag-mo {background-position: -1568px -840px;}
	.flag-mp {background-position: -1680px -840px;}
	.flag-mq {background-position: -1792px -840px;}
	.flag-mr {background-position: -1904px -840px;}
	.flag-ms {background-position: -2016px -840px;}
	.flag-mt {background-position: -2128px -840px;}
	.flag-mu {background-position: -2240px -840px;}
	.flag-mv {background-position: -2352px -840px;}
	.flag-mw {background-position: -2464px -840px;}
	.flag-mx {background-position: -2576px -840px;}
	.flag-my {background-position: -2688px -840px;}
	.flag-mz {background-position: -2800px -840px;}
	.flag-na {background-position: 0px -910px;}
	.flag-nc {background-position: -224px -910px;}
	.flag-ne {background-position: -448px -910px;}
	.flag-nf {background-position: -560px -910px;}
	.flag-ng {background-position: -672px -910px;}
	.flag-ni {background-position: -896px -910px;}
	.flag-nl {background-position: -1232px -910px;}
	.flag-no {background-position: -1568px -910px;}
	.flag-np {background-position: -1680px -910px;}
	.flag-nr {background-position: -1904px -910px;}
	.flag-nu {background-position: -2240px -910px;}
	.flag-nz {background-position: -2800px -910px;}
	.flag-om {background-position: -1344px -980px;}
	.flag-pa {background-position: 0px -1050px;}
	.flag-pe {background-position: -448px -1050px;}
	.flag-pf {background-position: -560px -1050px;}
	.flag-pg {background-position: -672px -1050px;}
	.flag-ph {background-position: -784px -1050px;}
	.flag-pk {background-position: -1120px -1050px;}
	.flag-pl {background-position: -1232px -1050px;}
	.flag-pm {background-position: -1344px -1050px;}
	.flag-pn {background-position: -1456px -1050px;}
	.flag-pr {background-position: -1904px -1050px;}
	.flag-ps {background-position: -2016px -1050px;}
	.flag-pt {background-position: -2128px -1050px;}
	.flag-pw {background-position: -2464px -1050px;}
	.flag-py {background-position: -2688px -1050px;}
	.flag-qa {background-position: 0px -1120px;}
	.flag-re {background-position: -448px -1190px;}
	.flag-ro {background-position: -1568px -1190px;}
	.flag-rs {background-position: -2016px -1190px;}
	.flag-ru {background-position: -2240px -1190px;}
	.flag-rw {background-position: -2464px -1190px;}
	.flag-sa {background-position: 0px -1260px;}
	.flag-sb {background-position: -112px -1260px;}
	.flag-sc {background-position: -224px -1260px;}
	.flag-sd {background-position: -336px -1260px;}
	.flag-se {background-position: -448px -1260px;}
	.flag-sg {background-position: -672px -1260px;}
	.flag-sh {background-position: -784px -1260px;}
	.flag-si {background-position: -896px -1260px;}
	.flag-sj {background-position: -1008px -1260px;}
	.flag-sk {background-position: -1120px -1260px;}
	.flag-sl {background-position: -1232px -1260px;}
	.flag-sm {background-position: -1344px -1260px;}
	.flag-sn {background-position: -1456px -1260px;}
	.flag-so {background-position: -1568px -1260px;}
	.flag-sr {background-position: -1904px -1260px;}
	.flag-ss {background-position: -2016px -1260px;}
	.flag-st {background-position: -2128px -1260px;}
	.flag-su {background-position: -2240px -1260px;}
	.flag-sv {background-position: -2352px -1260px;}
	.flag-sx {background-position: -2576px -1260px;}
	.flag-sy {background-position: -2688px -1260px;}
	.flag-sz {background-position: -2800px -1260px;}
	.flag-tc {background-position: -224px -1330px;}
	.flag-td {background-position: -336px -1330px;}
	.flag-tf {background-position: -560px -1330px;}
	.flag-tg {background-position: -672px -1330px;}
	.flag-th {background-position: -784px -1330px;}
	.flag-tj {background-position: -1008px -1330px;}
	.flag-tk {background-position: -1120px -1330px;}
	.flag-tl {background-position: -1232px -1330px;}
	.flag-tm {background-position: -1344px -1330px;}
	.flag-tn {background-position: -1456px -1330px;}
	.flag-to {background-position: -1568px -1330px;}
	.flag-tr {background-position: -1904px -1330px;}
	.flag-tt {background-position: -2128px -1330px;}
	.flag-tv {background-position: -2352px -1330px;}
	.flag-tw {background-position: -2464px -1330px;}
	.flag-tz {background-position: -2800px -1330px;}
	.flag-ua {background-position: 0px -1400px;}
	.flag-ug {background-position: -672px -1400px;}
	.flag-um {background-position: -1344px -1400px;}
	.flag-un {background-position: -1456px -1400px;}
	.flag-us {background-position: -2016px -1400px;}
	.flag-uy {background-position: -2688px -1400px;}
	.flag-uz {background-position: -2800px -1400px;}
	.flag-va {background-position: 0px -1470px;}
	.flag-vc {background-position: -224px -1470px;}
	.flag-ve {background-position: -448px -1470px;}
	.flag-vg {background-position: -672px -1470px;}
	.flag-vi {background-position: -896px -1470px;}
	.flag-vn {background-position: -1456px -1470px;}
	.flag-vu {background-position: -2240px -1470px;}
	.flag-wf {background-position: -560px -1540px;}
	.flag-ws {background-position: -2016px -1540px;}
	.flag-xk {background-position: -1120px -1610px;}
	.flag-ye {background-position: -448px -1680px;}
	.flag-yt {background-position: -2128px -1680px;}
	.flag-yu {background-position: -2240px -1680px;}
	.flag-za {background-position: 0px -1750px;}
	.flag-zm {background-position: -1344px -1750px;}
	.flag-zw {background-position: -2464px -1750px;}
</style>
