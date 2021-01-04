@extends('layouts.header')

@section('content')

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.h2h') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.h2hDetail') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.optionpicker') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.h2h') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.h2hDetail') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.optionpicker') }}">
@endif

@if (is_test_account())
	<script type="text/javascript" language="javascript" src="{{ Config::get('head.test.js.optionpicker') }}"></script>
@else
	<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.optionpicker') }}"></script>
@endif
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.echarts') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.topn') }}"></script>

<script type="text/javascript" language="javascript" class="init">

$(function() {

	var sd;
	var type;

	var p1 = $('#iTopNInput1').optionpicker({
		holder: "{{ __('h2h.select.player') }}",
		height: 45,
		fontSize: 16,
		maxSelectHeight: 270,
		url: "{{ url('select/byname') }}",
	}).data('object');

	var p2 = $('#iTopNInput2').optionpicker({
		holder: "{{ __('h2h.select.topN') }}",
		height: 45,
		fontSize: 16,
		maxSelectHeight: 270,
		url: "",
	}).data('object');

	$(':radio[name=type]').on('click', function() {
		p1.setOptions({data: {t: $(this).val()}});
		p2.setOptions({data: {t: $(this).val()}});
		p1.clear();
		p2.clear();
		type = $(this).val();
	});

	$(':radio[name=sd]').on('click', function() {
		sd = $(this).val();
	});

	$('#iTopNSubmit').on('click', function () {

		var ret = validate(p1, p2);

		var data = {
			status: ret,
			sd: sd,
			p1id: p1.input.attr('data-id'),
			p2id: p2.input.attr('data-id'),
			type: type,
		}

		var c_url = ["{{ slash(url(App::getLocale() . '/topn')) }}", p1.input.val().replace(/ /g, ""), sd, data.p2id].join("/");
		_hmt.push(['_trackCustomEvent', 'topn', {'sd':sd,'p1':p1.input.val().replace(/ /g, ""),'digit':data.p2id,'gender':type}]);
		ga('send', 'pageview', c_url);

		$('#iTopNResult').html("{{ __('frame.notice.gfw') }}");

		$.ajax({
			type: 'POST',
			url: "{{ url(App::getLocale() . "/history/topn/query") }}",
			data: data,
			success: function (data) {
				$('#iTopNResult').html(data);
			}
		});
	});

	$('#iTopNInput').on('focus', 'input', function (e) {

		var me = this;
		setTimeout(function () {
			var t = $(me).offset().top;
			$('body').scrollTop(t - 70);
		}, 200);
	});

	{{-- 触发默认值 --}}
	$('#iTopNTypeWTA').trigger('click');
	$('#iTopNSdS').trigger('click');

});

</script>

<div id="iTopN" class="cH2H">
	<form id="iTopNSelect" class="cH2HSelect">
		<table>
			<tr>
				<td colspan=4 class="cH2HSelectTitleTitle">{{ __('h2h.selectBarTitle.title') }}</td>
			</tr>
			<tr>
				<td class="cH2HSelectTitle">{{ __('h2h.selectBarTitle.type') }}</td>
				<td>
					<div>
						<input type=radio name=type id=iTopNTypeATP value=atp></input><label for=iTopNTypeATP>{{ __('h2h.selectBar.type.atp') }}</label>
						<input type=radio name=type id=iTopNTypeWTA value=wta></input><label for=iTopNTypeWTA>{{ __('h2h.selectBar.type.wta') }}</label>
					</div>
				</td>
				<td class="cH2HSelectTitle">{{ __('h2h.selectBarTitle.sd') }}</td>
				<td>
					<div>
						<input type=radio name=sd id=iTopNSdS value=s></input><label for=iTopNSdS>{{ __('h2h.selectBar.sd.s') }}</label>
						<input type=radio name=sd id=iTopNSdD value=d></input><label for=iTopNSdD>{{ __('h2h.selectBar.sd.d') }}</label>
					</div>
				</td>
			</tr>
		</table>
	</form>
	<div id="iTopNInput" class="cH2HInput">
		<div id="iTopNInput1" class="cH2HInput1"></div>
		<div id="iTopNSubmit" class="cH2HSubmit selected">{!! get_icon('chaxun') !!}</div>
		<div id="iTopNInput2" class="cH2HInput2"></div>
	</div>
	<div id=iTopNResult class="cH2HResult">
	</div>
</div>

@endsection
