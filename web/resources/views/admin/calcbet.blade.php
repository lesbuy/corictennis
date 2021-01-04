@isset($_GET['total'])
	@php
		$x = !preg_match('/\//', $_GET['bet1']) ? $_GET['bet1'] : (explode("/", $_GET['bet1'])[0] / explode("/", $_GET['bet1'])[1] + 1);
		$y = !preg_match('/\//', $_GET['bet2']) ? $_GET['bet2'] : (explode("/", $_GET['bet2'])[0] / explode("/", $_GET['bet2'])[1] + 1);
		$z = !preg_match('/\//', $_GET['bet3']) ? $_GET['bet3'] : (explode("/", $_GET['bet3'])[0] / explode("/", $_GET['bet3'])[1] + 1);
		$t = $_GET['total'];
		$x1 = $y * $z;
		$y1 = $x * $z;
		$z1 = $x * $y;
		$x2 = $x1 / ($x1 + $y1 + $z1);
		$y2 = $y1 / ($x1 + $y1 + $z1);
		$z2 = $z1 / ($x1 + $y1 + $z1);
		$x3 = $t * $x2;
		$y3 = $t * $y2;
		$z3 = $t * $z2;
		$exp = $x3 * $x;
	@endphp
@endisset

<form>
分数赔率1: <input type=text id=bet1 name=bet1 />
分数赔率2: <input type=text id=bet2 name=bet2 />
分数赔率3: <input type=text id=bet3 name=bet3 />
总额: <input type=text id=total name=total value=1000 />
<input type=submit />

<div id=result>
	@isset($_GET['total'])
		项目1投入 {{ round($x3, 2) }}<br/>
		项目2投入 {{ round($y3, 2) }}<br/>
		项目3投入 {{ round($z3, 2) }}<br/>
		预期收入 {{ round($exp, 2) }}<br/>
		预期收益 {{ round($exp, 2) - $t }}<br/>
		预期收益率 {{ round(($exp - $t) / $t, 2) * 100 }}%<br/>
	@endisset
</div>

</form>
