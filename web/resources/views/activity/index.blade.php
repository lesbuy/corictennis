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

<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.activity') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.echarts') }}"></script>

<script type="text/javascript" language="javascript" class="init">

$(function() {

	var sd;
	var type;
	var level;
	var onlyMD= '';
	var onlyFinal = '';
	var surface;

	var p1 = $('#iActivityInput1').optionpicker({
		holder: "{{ __('h2h.select.player') }}",
		height: 45,
		fontSize: 16,
		maxSelectHeight: 270,
		url: "{{ url('select/byname') }}",
	}).data('object');

	var p2;

	$(':radio[name=type]').on('click', function() {
		p1.setOptions({data: {t: $(this).val()}});
		p1.clear();
		type = $(this).val();
	});

	$(':radio[name=sd]').on('click', function() {
		sd = $(this).val();
	});

	$(':radio[name=level]').on('click', function() {
		level = $(this).val();
	});

	$(':radio[name=sfc]').on('click', function() {
		surface = $(this).val();
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

	$('#iActivitySubmit').on('click', function () {

		var ret = validate(p1);

		var data = {
			status: ret,
			sd: sd,
			p1id: p1.input.attr('data-id'),
			year: $('#iActivityInput2').val(),
			type: type,
			surface: surface,
			level: level,
			onlyMD: onlyMD,
			onlyFinal: onlyFinal,
		}

		var c_url = ["{{ slash(url(App::getLocale() . '/activity')) }}", p1.input.val().replace(/ /g, ""), data.year].join("/");
		_hmt.push(['_trackCustomEvent', 'activity', {'gender':type,'year':$('#iActivityInput2').val(),'sd':sd,'p1':p1.input.val().replace(/ /g, ""),'level':level,'surface':surface}]);
		ga('send', 'pageview', c_url);

		$('#iActivityResult').html("{{ __('frame.notice.gfw') }}");

		$.ajax({
			type: 'POST',
			url: "{{ url(App::getLocale() . "/history/activity/query") }}",
			data: data,
			success: function (data) {
				$('#iActivityResult').html(data);
				$("img.cImgPlayerFlag", $("#iActivityResult")).lazyload();
			}
		});
	});

	$('#iActivityInput').on('focus', 'input', function (e) {

		var me = this;
		setTimeout(function () {
			var t = $(me).offset().top;
			console.log(t);
			$('body').scrollTop(t - 70);
		}, 200);
	});

	{{-- 触发默认值 --}}
	$('#iActivityTypeATP').trigger('click');
	$('#iActivitySdSingle').trigger('click');
	$('#iActivityLevelTour').trigger('click');
	$('#iActivitySurfaceAll').trigger('click');
	$('#iActivityMDOnly').trigger('click');

});

</script>

<div id="iActivity" class="cH2H">
	<form id="iActivitySelect" class="cH2HSelect">
		{{ csrf_field() }}
		<table>
			<tr>
				<td colspan=2 class="cH2HSelectTitleTitle">{{ __('h2h.selectBarTitle.title') }}</td>
			</tr>
			<tr>
				<td class="cH2HSelectTitle">{{ __('h2h.selectBarTitle.playerType') }}</td>
				<td>
					<div>
						<input type=radio name=type id=iActivityTypeATP value=atp></input><label class="unselected" for=iActivityTypeATP>{{ __('h2h.selectBar.type.atp') }}</label>
						<input type=radio name=type id=iActivityTypeWTA value=wta></input><label class="unselected" for=iActivityTypeWTA>{{ __('h2h.selectBar.type.wta') }}</label>
						<input type=radio name=sd id=iActivitySdSingle value=s></input><label class="unselected" for=iActivitySdSingle>{{ __('h2h.selectBar.sd.s') }}</label>
						<input type=radio name=sd id=iActivitySdDouble value=d></input><label class="unselected" for=iActivitySdDouble>{{ __('h2h.selectBar.sd.d') }}</label>
					</div>
				</td>
			</tr>
			<tr>
				<td class="cH2HSelectTitle" rowspan=4>{{ __('h2h.selectBarTitle.tourType') }}</td>
				<td>
					<div>
						<input type=radio name=level id=iActivityLevelAll value=a></input><label class="unselected" for=iActivityLevelAll>{{ __('h2h.selectBar.level.a') }}</label>
						<input type=radio name=level id=iActivityLevelGS value=g></input><label class="unselected" for=iActivityLevelGS>{{ __('h2h.selectBar.level.g') }}</label>
						<input type=radio name=level id=iActivityLevelMS value=m></input><label class="unselected" for=iActivityLevelMS>{{ __('h2h.selectBar.level.m') }}</label>
						<input type=radio name=level id=iActivityLevelTour value=t></input><label class="unselected" for=iActivityLevelTour>{{ __('h2h.selectBar.level.t') }}</label>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<div>
						<input type=radio name=level id=iActivityLevelAO value=ao></input><label class="unselected" for=iActivityLevelAO>{{ __('h2h.selectBar.level.ao') }}</label>
						<input type=radio name=level id=iActivityLevelRG value=rg></input><label class="unselected" for=iActivityLevelRG>{{ __('h2h.selectBar.level.rg') }}</label>
						<input type=radio name=level id=iActivityLevelWC value=wc></input><label class="unselected" for=iActivityLevelWC>{{ __('h2h.selectBar.level.wc') }}</label>
						<input type=radio name=level id=iActivityLevelUO value=uo></input><label class="unselected" for=iActivityLevelUO>{{ __('h2h.selectBar.level.uo') }}</label>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<div>
						<input type=radio name=level id=iActivityLevelYEC value=yec></input><label class="unselected" for=iActivityLevelYEC>{{ __('h2h.selectBar.level.yec') }}</label>
						<input type=radio name=level id=iActivityLevelDC value=dc></input><label class="unselected" for=iActivityLevelDC>{{ __('h2h.selectBar.level.dc') }}</label>
						<input type=radio name=level id=iActivityLevelOL value=ol></input><label class="unselected" for=iActivityLevelOL>{{ __('h2h.selectBar.level.ol') }}</label>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<div>
						<input type=checkbox name=md id=iActivityMDOnly value=y></input><label class="unselected" for=iActivityMDOnly>{{ __('h2h.selectBar.md.y') }}</label>
						<input type=checkbox name=final id=iActivityFinalOnly value=y></input><label class="unselected" for=iActivityFinalOnly>{{ __('h2h.selectBar.final.y') }}</label>
					</div>
				</td>
			</tr>
			<tr>
				<td class="cH2HSelectTitle">{{ __('h2h.selectBarTitle.sfcType') }}</td>
				<td>
					<div>
						<input type=radio name=sfc id=iActivitySurfaceAll value=a></input><label class="unselected" for=iActivitySurfaceAll>{{ __('h2h.selectBar.sfc.a') }}</label>
						<input type=radio name=sfc id=iActivitySurfaceHard value=h></input><label class="unselected" for=iActivitySurfaceHard>{{ __('h2h.selectBar.sfc.h') }}</label>
						<input type=radio name=sfc id=iActivitySurfaceClay value=c></input><label class="unselected" for=iActivitySurfaceClay>{{ __('h2h.selectBar.sfc.c') }}</label>
						<input type=radio name=sfc id=iActivitySurfaceGrass value=g></input><label class="unselected" for=iActivitySurfaceGrass>{{ __('h2h.selectBar.sfc.g') }}</label>
						<input type=radio name=sfc id=iActivitySurfaceCarpet value=p></input><label class="unselected" for=iActivitySurfaceCarpet>{{ __('h2h.selectBar.sfc.p') }}</label>
					</div>
				</td>
			</tr>
		</table>
	</form>
	<div id="iActivityInput" class="cH2HInput">
		<div id="iActivityInput1" class="cH2HInput1"></div>
		<div id="iActivitySubmit" class="cH2HSubmit selected">{!! get_icon('chaxun') !!}</div>
		<select id=iActivityInput2 class="cH2HInput2">
			<option value="-1">{{ __('h2h.selectBar.allYear') }}</option>
			@for ($i = 2020; $i >= 1968; --$i)
				<option value="{{ $i }}" {{ $i == 2020 ? 'selected' : '' }}>{{ $i }}</option>
			@endfor
		</select>
	</div>
	<div id=iActivityResult class="cH2HResult">
	</div>
</div>

@endsection
