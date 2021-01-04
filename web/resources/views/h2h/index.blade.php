@extends('layouts.header')

@section('content')

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.optionpicker') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.h2h') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.h2hDetail') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.optionpicker') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.h2h') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.h2hDetail') }}">
@endif

@if (is_test_account())
	<script type="text/javascript" language="javascript" src="{{ Config::get('head.test.js.optionpicker') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ Config::get('head.test.js.h2h') }}"></script>
@else
	<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.optionpicker') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.h2h') }}"></script>
@endif
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.echarts') }}"></script>

<script type="text/javascript" language="javascript" class="init">

$(function() {

	var method;
	var type;
	var level;
	var onlyMD= '';
	var onlyFinal = '';
	var sd = 's';
	var surface;

	var height = $('#iH2HSubmit').height();
	var tmp_width = ($('#iH2HInput').width() - $('#iH2HSubmit').width() - $('#iH2HSubmit').css('margin').replace(/px/, '') * 2 - $('#iH2HSubmit').css('padding').replace(/px/, '') * 2) / 2 * 0.99;
	var width = tmp_width > 33 * em ? 33 * em : tmp_width;

	var p1 = $('#iH2HInput1').optionpicker({
		holder: "{{ __('h2h.select.player1') }}",
		height: height,
		width: width,
		multi: false,
		fontSize: 1.2 * em,
		maxSelectHeight: 6 * height,
		url: "{{ url('select/byname') }}",
	}).data('object');

	var p2 = $('#iH2HInput2').optionpicker({
		holder: "{{ __('h2h.select.player2') }}",
		height: height,
		width: width,
		multi: false,
		fontSize: 1.2 * em,
		maxSelectHeight: 6 * height,
		url: "{{ url('select/byname') }}",
	}).data('object');

	$(':radio[name=type]').on('click', function() {
		p1.setOptions({data: {t: $(this).val()}});
		p2.setOptions({data: {t: $(this).val()}});
		p1.clear(); p1.clear_multi();
		p2.clear(); p2.clear_multi();
		type = $(this).val();
	});

	$(':radio[name=method]').on('click', function() {
		var val = $(this).val();
		if (val == 'p') {
			p1.setOptions({
				multi: false,
			});
			p2.setOptions({
				multi: false,
				holder: "{{ __('h2h.select.player2') }}",
				url: "{{ url('select/byname') }}",
			});
			method = 'p';
		} else if (val == 'm') {
			p1.setOptions({
				multi: true,
				holder: "{{ __('h2h.select.player1') }}",
			});
			p2.setOptions({
				multi: true,
				holder: "{{ __('h2h.select.player2') }}",
				url: "{{ url('select/byname') }}",
			});
			method = 'm';
			p1.clear();
			p2.clear();
			if (p1.getNum() > 0) p1.setShortsIntoPlaceholder();
			if (p2.getNum() > 0) p2.setShortsIntoPlaceholder();
		} else if (val == 'c') {
			p1.setOptions({
				multi: false,
			});
			p2.setOptions({
				multi: false,
				holder: "{{ __('h2h.select.country') }}",
				url: "{{ url('select/bynation') }}",
			});
			method = 'c';
		} else if (val == 't') {
			p1.setOptions({
				multi: false,
			});
			p2.setOptions({
				multi: false,
				holder: "{{ __('h2h.select.topN') }}",
				url: "",
			});
			method = 't';
		}
		p2.clear();

	});

	$(':radio[name=level]').on('click', function() {
		level = $(this).val();
	});

	$(':radio[name=sfc]').on('click', function() {
		surface = $(this).val();
	});

	$(':radio[name=sd]').on('click', function() {
		sd = $(this).val();
	});

	$(':checkbox[name=md]').on('click', function() {
		if ($(this).is(':checked')) {
			onlyMD = 'y';
		} else {
			onlyMD = '';
		}
	});

	$(':checkbox[name=final]').on('click', function() {
		if ($(this).is(':checked')) {
			onlyFinal = 'y';
		} else {
			onlyFinal = '';
		}
	});

	$('#iH2HSubmit').on('click', function () {

		var ret = validate(method, p1, p2, sd);

		var data = {
			status: ret,
			method: method,
			p1id: p1.getValue(),
			p2id: p2.getValue(),
			type: type,
			surface: surface,
			level: level,
			onlyMD: onlyMD,
			onlyFinal: onlyFinal,
			sd: sd,
		}

		var c_url = ["{{ slash(url(App::getLocale() . '/h2h')) }}", p1.getText().replace(/ /g, ""), p2.getText().replace(/ /g, "")].join("/");
		_hmt.push(['_trackCustomEvent', 'h2h_submit', {'p1':p1.getText().replace(/ /g, ""),'p2':p2.getText().replace(/ /g, "")}]);
		ga('send', 'pageview', c_url);

		$('#iH2HResult').html("{{ __('frame.notice.gfw') }}");

		$.ajax({
			type: 'POST',
			url: "{{ url(App::getLocale() . "/h2h/query") }}",
			data: data,
			success: function (data) {
				$('#iH2HResult').html(data);
			}
		});
	});

	$('#iH2HInput').on('focus', 'input', function (e) {

		var me = this;
		setTimeout(function () {
			var t = $(me).offset().top;
			console.log(t);
			$('body').scrollTop(t - 70);
		}, 200);
	});

	{{-- 触发默认值 --}}
	$('#iH2HSdS').trigger('click');
	$('#iH2HTypeWTA').trigger('click');
	$('#iH2HMethodP').trigger('click');
	$('#iH2HLevelAll').trigger('click');
	$('#iH2HSurfaceAll').trigger('click');

});

</script>

<div id="iH2H" class="cH2H">
	<form id="iH2HSelect" class="cH2HSelect">
		{{ csrf_field() }}
		<table>
			<tr>
				<td colspan=2 class="cH2HSelectTitleTitle">{{ __('h2h.selectBarTitle.title') }}</td>
			</tr>
			<tr>
				<td class="cH2HSelectTitle">{{ __('h2h.selectBarTitle.type') }}</td>
				<td>
					<div>
						<input type=radio name=method id=iH2HMethodP value=p></input><label class="unselected" for=iH2HMethodP>{{ __('h2h.selectBar.method.p') }}</label>
						<input type=radio name=method id=iH2HMethodC value=c></input><label class="unselected" for=iH2HMethodC>{{ __('h2h.selectBar.method.c') }}</label>
						<input type=radio name=method id=iH2HMethodT value=t></input><label class="unselected" for=iH2HMethodT>{{ __('h2h.selectBar.method.t') }}</label>
						<input type=radio name=method id=iH2HMethodM value=m></input><label class="unselected" for=iH2HMethodM>{{ __('h2h.selectBar.method.m') }}</label>
					</div>
				</td>
			</tr>
			<tr>
				<td class="cH2HSelectTitle">{{ __('h2h.selectBarTitle.playerType') }}</td>
				<td>
					<div>
						<input type=radio name=type id=iH2HTypeATP value=atp></input><label class="unselected" for=iH2HTypeATP>{{ __('h2h.selectBar.type.atp') }}</label>
						<input type=radio name=type id=iH2HTypeWTA value=wta></input><label class="unselected" for=iH2HTypeWTA>{{ __('h2h.selectBar.type.wta') }}</label>
						<input type=radio name=sd id=iH2HSdS value=s></input><label class="unselected" for=iH2HSdS>{{ __('h2h.selectBar.sd.s') }}</label>
						<input type=radio name=sd id=iH2HSdD value=d></input><label class="unselected" for=iH2HSdD>{{ __('h2h.selectBar.sd.d') }}</label>
					</div>
				</td>
			</tr>
			<tr>
				<td class="cH2HSelectTitle" rowspan=2>{{ __('h2h.selectBarTitle.tourType') }}</td>
				<td>
					<div>
						<input type=radio name=level id=iH2HLevelAll value=a></input><label class="unselected" for=iH2HLevelAll>{{ __('h2h.selectBar.level.a') }}</label>
						<input type=radio name=level id=iH2HLevelGS value=g></input><label class="unselected" for=iH2HLevelGS>{{ __('h2h.selectBar.level.g') }}</label>
						<input type=radio name=level id=iH2HLevelMS value=m></input><label class="unselected" for=iH2HLevelMS>{{ __('h2h.selectBar.level.m') }}</label>
						<input type=radio name=level id=iH2HLevelTour value=t></input><label class="unselected" for=iH2HLevelTour>{{ __('h2h.selectBar.level.t') }}</label>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<div>
						<input type=checkbox name=md id=iH2HMDOnly value=y></input><label class="unselected" for=iH2HMDOnly>{{ __('h2h.selectBar.md.y') }}</label>
						<input type=checkbox name=final id=iH2HFinalOnly value=y></input><label class="unselected" for=iH2HFinalOnly>{{ __('h2h.selectBar.final.y') }}</label>
					</div>
				</td>
			</tr>
			<tr>
				<td class="cH2HSelectTitle">{{ __('h2h.selectBarTitle.sfcType') }}</td>
				<td>
					<div>
						<input type=radio name=sfc id=iH2HSurfaceAll value=a></input><label class="unselected" for=iH2HSurfaceAll>{{ __('h2h.selectBar.sfc.a') }}</label>
						<input type=radio name=sfc id=iH2HSurfaceHard value=h></input><label class="unselected" for=iH2HSurfaceHard>{{ __('h2h.selectBar.sfc.h') }}</label>
						<input type=radio name=sfc id=iH2HSurfaceClay value=c></input><label class="unselected" for=iH2HSurfaceClay>{{ __('h2h.selectBar.sfc.c') }}</label>
						<input type=radio name=sfc id=iH2HSurfaceGrass value=g></input><label class="unselected" for=iH2HSurfaceGrass>{{ __('h2h.selectBar.sfc.g') }}</label>
						<input type=radio name=sfc id=iH2HSurfaceCarpet value=p></input><label class="unselected" for=iH2HSurfaceCarpet>{{ __('h2h.selectBar.sfc.p') }}</label>
					</div>
				</td>
			</tr>
		</table>

	</form>
	<div id="iH2HInput" class="cH2HInput">
		<div id="iH2HInput1" class="cH2HInput1"></div>
		<div id="iH2HSubmit" class="cH2HSubmit selected">{!! get_icon('competitor') !!}</div>
		<div id="iH2HInput2" class="cH2HInput2"></div>
	</div>
	<div id=iH2HResult class="cH2HResult">
	</div>
</div>

@endsection
