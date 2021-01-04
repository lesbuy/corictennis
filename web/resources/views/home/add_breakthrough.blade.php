<meta name="_token" content="{!! csrf_token() !!}"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>

<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.optionpicker') }}">
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.jquery') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.base') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.optionpicker') }}"></script>

<script type="text/javascript" language="javascript" class="init">

$(function (){
	var gender = "atp";
	var width = 300;
	var p1 = $('#iHomeInput').optionpicker({
		holder: "在此输入英文名",
		height: 45,
		width: width,
		fontSize: 16,
		maxSelectHeight: 270,
		url: "{{ url('select/byname') }}",
	}).data('object');

	$(':radio[name=gender]').on('click', function() {
		p1.setOptions({data: {t: $(this).val()}});
		p1.clear();
		gender = $(this).val();
	});

	$('.submit').on('click', function () {

		var data = $(this).parent().find('input').serialize();
		if (!data.match(/pid/)) {
			if (p1.input.attr('data-id') == undefined) {
				alert('你还没选人呢');
				return;
			}
			data = data + "&pid=" + p1.input.attr('data-id');
		}
		console.log(data);
		$.ajax({
			type: 'POST',
			url: "{{ url("/admin/update/breakthrough/post") }}",
			data: data,
			success: function (data) {
				alert(data);
			},
		});
	});

	$('.update').on('click', function () {

		var data = $(this).parent().find('input').serialize();
		data = data + "&method=update";
		console.log(data);
		$.ajax({
			type: 'POST',
			url: "{{ url("/admin/update/breakthrough/post") }}",
			data: data,
			success: function (data) {
				alert(data);
			},
		});
	});

	$('.delete').on('click', function () {

		var data = $(this).parent().find('input').serialize();
		data = data + "&method=delete";
		console.log(data);
		$.ajax({
			type: 'POST',
			url: "{{ url("/admin/update/breakthrough/post") }}",
			data: data,
			success: function (data) {
				alert(data);
			},
		});
	});
});

</script>

<style>
#iHomeInput {
	width: 200px;
	margin-bottom: 300px;
}

.submit, .update, .delete {
	cursor: pointer;
	background-color: #000;
	color: #fff;
	padding: 5px;
}

.cOPSelect > div > div:hover {
	background-color: #666;
	color: #fff;
}

#iHomeSubmit {
	display: inline-block;
}
</style>


选手创造生涯最高数据添加页
本页主要作用就是上传图片URL。也可以修改城市，TOPN，日期（一般是下周一）
<hr>

系统识别出的可能的人选有：(本周单打首进Top1,3,5,10,20,50,100,500，双打首进Top1,10的潜在选手。按即时排名算的)
<table>
	<tr><td>pid</td><td>Name</td><td>IOC</td><td>单双</td><td>TopN</td><td>城市</td><td>日期</td><td>图片url</td><td>提交</td>  
	@foreach ($possible as $person)
		<tr>
			<td><input type=text name="pid" value="{{ $person[2] }}" readonly /><input type=hidden name="gender" value="{{ $person[7] }}" readonly /></td>
			<td><input type=text name="pname" value="{{ $person[3] }} {{ $person[4] }}" readonly /></td>
			<td><input type=text name="ioc" value="{{ $person[5] }}" readonly /></td>
			<td><input type=text name="sd" value="{{ $person[0] }}" readonly /></td>
			<td><input type=text name="topn" value="{{ $person[1] }}" /></td>
			<td><input type=text name="city" value="{{ $person[6] }}" /></td>
			<td><input type=text name="date" value="{{ date('Y-m-d', strtotime('next Monday')) }}" /></td>
			<td><input type=text name="imgsrc" value="" /></td>
			<td class="submit">提交</td>
		</tr>
	@endforeach
</table>

<hr>

最近30条新添加的数据是：

<table>
	<tr><td>记录id</td><td>Name</td><td>IOC</td><td>单双</td><td>TopN</td><td>城市</td><td>日期</td><td>图片url</td><td>修改</td><td>删除</td>
	@foreach ($list as $person)	
		<tr>
			<td><input type=text name="id" value="{{ $person[0] }}" readonly /><input type=hidden name="gender" value="{{ $person[1] }}" readonly /><input type=hidden name="pid" value="{{ $person[9] }}" readonly /></td>
			<td><input type=text name="pname" value="{{ $person[6] }}" disabled /></td>
			<td><input type=text name="ioc" value="{{ $person[7] }}" disabled /></td>
			<td><input type=text name="sd" value="{{ $person[2] }}" disabled /></td>
			<td><input type=text name="topn" value="{{ $person[3] }}" /></td>
			<td><input type=text name="city" value="{{ $person[5] }}" /></td>
			<td><input type=text name="date" value="{{ $person[4] }}" /></td>
			<td><input type=text name="imgsrc" value="{{ $person[8] }}" /></td>
			<td class="update">修改</td>
			<td class="delete">删除</td>
		</tr>
	@endforeach
</table>
	
<hr>
手动添加一条数据：
<div id=iHomeQuery>
	<input type=radio name=gender id=iHomeTypeATP value=atp checked></input><label class="unselected" for=iHomeTypeATP>{{ __('h2h.selectBar.type.atp') }}</label>
	<input type=radio name=gender id=iHomeTypeWTA value=wta></input><label class="unselected" for=iHomeTypeWTA>{{ __('h2h.selectBar.type.wta') }}</label>
	<input type=text name=date placeholder="日期 YYYY-mm-dd" value="{{ date('Y-m-d', strtotime('next Monday')) }}" />在<input type=text name=city placeholder="城市" />
	首次进入<input type=text name=sd placeholder="单打s， 双打d" value="s" />
	Top<input type=text name=topn placeholder="N" value=10 /> 相关图片:<input type=text name=imgsrc placeholder="url地址" />
	<div id="iHomeSubmit" class="submit">提交</div>
	<br>&nbsp;<br>
	<div id="iHomeInput" class=""></div>
</div>

