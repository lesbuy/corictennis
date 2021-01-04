@extends('layouts.header')

@section('content')

<script type="text/javascript" language="javascript" src="https://cdn.bootcss.com/echarts/4.2.0-rc.1/echarts.min.js"></script>
<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.swiper') }}">
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.swiper') }}"></script>

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.home') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.optionpicker') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.home') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.optionpicker') }}">
@endif

@if (is_test_account())
	<script type="text/javascript" language="javascript" src="{{ Config::get('head.test.js.optionpicker') }}"></script>
@else
	<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.optionpicker') }}"></script>
@endif

<script type="text/javascript" language="javascript" class="init">

$(function() {

	var year_for_stat = 0;
	var gender = "atp";

	var in_query = false;

	var p1 = $('#iHomeInput').optionpicker({
		holder: "{{ __('home.card.typein') }}",
		url: "{{ url('select/byname') }}",
	}).data('object');

	$(':radio[name=type]').on('click', function() {
		p1.setOptions({data: {t: $(this).val()}});
		p1.clear();
		gender = $(this).val();
	});

	$(':radio[name=stat]').on('click', function() {
		var v = $(this).val();
		$('.cHomeCard').hide();
		$('#iHomeCard' + v).show();
	});

	$('.cHomeTour, .cHomeRankTable, .cHomeQuick').on('click', function () {
		var href = $(this).attr('href');
		window.location.href = href;
	});

	$('#iHomeSubmit').on('click', function () {

//		var ret = validate(p1);
		if (in_query) return;
		in_query = true;

		var p1id = p1.input.attr('data-id');
		var p1name = p1.input.val().replace(/ /g, "");

		$('#iHomeCardMask').show();

		var c_url = ["{{ slash(url(App::getLocale() . '/player')) }}", gender, p1id, p1name].join("/");
		_hmt.push(['_trackCustomEvent', 'home_panel', {'gender':gender,'p1':p1name}]);
		ga('send', 'pageview', c_url);


		$.ajax({
			type: 'POST',
			url: ["{{ url(App::getLocale() . "/player") }}", gender, p1id].join("/"),
			success: function (data) {
				$('#iHomeCardRight').html(data);
				$('#iHomeStatBasic').trigger('click');
				$('#iHomeCardMask').hide();
				in_query = false;
			}
		});
	});

	$('.cHomePlate').on('click', function (e) {
		var href = $(this).attr('href');
		window.location = href;
	});

	$('.cHomeCardInitHot').on('click', function (e) {
		var pid = $(this).attr('data-id');
		var sex = $(this).attr('data-gender');
		var name = $(this).attr('data-first') + " " + $(this).attr('data-last');

		$('#iHomeType' + sex.toUpperCase()).trigger('click');
		$('#iHomeInput > input').val(name);
		$('#iHomeInput > input').attr('data-id', pid);

	});

	$('.cHomeWheelLink').on('click', function (e) {
		var link = $(this).attr('data-link');
		if (!link) return;
		window.location.href = link;
	});

	$('#iHomeTypeWTA').trigger('click');

	var mySwiper = new Swiper ('#iHomeWheel', {
		direction: 'horizontal',
		loop: true,
		autoplay: 5000,
		autoplayDisableOnInteraction : false,
		
		// 如果需要分页器
		//pagination: '.swiper-pagination',
		//paginationClickable: true,
		
		// 如果需要前进后退按钮
		nextButton: '.swiper-button-next',
		prevButton: '.swiper-button-prev',
		
		// 如果需要滚动条
		//scrollbar: '.swiper-scrollbar',

		onSlideChangeEnd: function(swiper){  
			swiper.startAutoplay();      
		},

		// Disable preloading of all images
		preloadImages: false,
		// Enable lazy loading
		lazyLoading: true,
	});


});
</script>

@php
@endphp
<div id=iHome>

	<div id=iHomeLeft>

		<div id="iHomeWheel" class="swiper-container">
			<div class="swiper-wrapper">
				@foreach ($ret['wheel'] as $v)
					@php $vt = $v['type'] @endphp
					<div class="swiper-slide swiper-lazy" data-background="{{ $v['big'] }}" style="background-image: url({{ url('images/tips/reload.svg') }}); background-position: {{ $v['bg_pos'] }}">
						<div class="cHomeWheelDesc {{ isset($v['link']) && $v['link'] ? "cHomeWheelLink" : "" }} {{ in_array($vt, ['B', 'T', 'R']) ? "cHomeWheelMask" : "" }}" data-link="{{ isset($v['link']) && $v['link'] ? $v['link'] : "" }}"><div>
							<div class="cHomeWheelDescIcon">{!! $vt == "B" ? get_icon('dingdan-') : ($vt == "T" ? get_icon('qita-') : ($vt == "R" ? get_icon('huojian1') : "")) !!}</div>
							<div class="cHomeWheelDescText">{!!
								$vt == "B" ? date(__('home.basic.format'), strtotime($v['pdob'])) . "<br>" .  __('home.wheel.happy_birthday', ['p1' => $v['pname'], 'p2' => $v['page']]) : (
									$vt == "T" ? __('home.wheel.happy_trophy', ['p1' => $v['pname'], 'p2' => $v['tour']]) : (
										$vt == "R" ? __('home.wheel.happy_rocket', ['p1' => $v['pname'], 'p2' => $v['sd'], 'p3' => $v['topn']]) : ( ""
										)
									)
								)
							!!}</div>
							<div class="cHomeWheelDescDot">{!!
								$vt == "B" ? "" : (
									$vt == "T" ? get_icon('shijian') . " " . date('Y/m/d', strtotime($v['date'])) . "&nbsp;&nbsp;&nbsp;" . get_icon('didian') . " " . $v['court'] . ", " . $v['city'] . ", " . $v['loc'] : (
										$vt == "R" ? get_icon('shijian') . " " . date('Y/m/d', strtotime($v['date'])) . "&nbsp;&nbsp;&nbsp;" . get_icon('didian') . " " . $v['city'] : (""
										)
									)
								)
							!!}</div>
						</div></div>
					</div>
				@endforeach
			</div>
			<!-- 如果需要分页器 -->
			<div class="swiper-pagination"></div>
			
			<!-- 如果需要导航按钮 -->
			<div class="swiper-button-prev"></div>
			<div class="swiper-button-next"></div>

			<!--<div class="swiper-scrollbar"></div>-->
		</div>

		<div id=iHomePlates>
			<div class=cHomePlate href="{{ url(App::getLocale() . '/result') }}">{{ __('home.quick.oop') }}</div>
			<div class=cHomePlate href="{{ url(App::getLocale() . '/live') }}">{{ __('home.quick.livescore') }}</div>
			<div class=cHomePlate href="{{ url(App::getLocale() . '/guess') }}">{{ __('home.quick.dcpk') }}</div>
			<div class=cHomePlate>{{ "" }}</div>
		</div>

		<div id=iHomeQuery>
			<input type=radio name=type id=iHomeTypeATP value=atp></input><label class="unselected" for=iHomeTypeATP>{{ __('h2h.selectBar.type.atp') }}</label>
			<input type=radio name=type id=iHomeTypeWTA value=wta></input><label class="unselected" for=iHomeTypeWTA>{{ __('h2h.selectBar.type.wta') }}</label>
			<div id="iHomeInput" class=""></div>
			<div id="iHomeSubmit" class="selected">{!! get_icon('chaxun') !!}</div>
		</div>

		<div id=iHomeCards>
			<div id=iHomeCardMask class=cReload><img src="{{ url('/images/tips/reload.svg') }}" /></div>
			<div id=iHomeCardLeft>
				<input type=radio name=stat id=iHomeStatBasic value=Basic></input><label class="unselected" for=iHomeStatBasic>{{ __('home.stat_left.basic') }}</label>
				<input type=radio name=stat id=iHomeStatMatch value=Match></input><label class="unselected" for=iHomeStatMatch>{{ __('home.stat_left.match') }}</label>
				<input type=radio name=stat id=iHomeStatStat value=Stat></input><label class="unselected" for=iHomeStatStat>{{ __('home.stat_left.stat') }}</label>
				<input type=radio name=stat id=iHomeStatGS value=GS></input><label class="unselected" for=iHomeStatGS>{{ __('home.stat_left.gs') }}</label>
				<input type=radio name=stat id=iHomeStatRecent value=Recent></input><label class="unselected" for=iHomeStatRecent>{{ __('home.stat_left.recent') }}</label>
				<input type=radio name=stat id=iHomeStatHonor value=Honor></input><label class="unselected" for=iHomeStatHonor>{{ __('home.stat_left.honor') }}</label>
				<input type=radio name=stat id=iHomeStatRate value=Rate></input><label class="unselected" for=iHomeStatRate>{{ __('home.stat_left.rate') }}</label>
				<input type=radio name=stat id=iHomeStatRank value=Rank></input><label class="unselected" for=iHomeStatRank>{{ __('home.stat_left.rank') }}</label>
			</div>
			<div id=iHomeCardRight>
				<div id=iHomeCardInit>
					<div id=iHomeCardInitTitle>{{ __('home.card.hot') }}</div>
					@foreach ($ret['hot'] as $hot)
						<div class=cHomeCardInitHot data-id="{{ $hot[3] }}" data-gender="{{ $hot[4] }}" data-first="{{ $hot[5] }}" data-last="{{ $hot[6] }}">
							<span>{{ $hot[0] }}</span>
							<span>{!! get_flag($hot[1]) !!}</span>
							<span>{{ $hot[2] }}</span>
						</div>
					@endforeach
				</div>
			</div>

		</div>

	</div>

	<div id=iHomeRight>

		<div id=iHomeRank class=cHomeBlock>
			<div id=iHomeRankTitle class=cHomeTitle>{{ __('home.title.rank') }}</div>
			@foreach (['atp', 'wta'] as $gender)
				<table class=cHomeRankTable href="{{ url(join("/", [App::getLocale(), 'rank', $gender, 's', 'year'])) }}">
					<tbody>
						<tr><td colspan=3>{{ strtoupper($gender) }}</td></tr>
						@for ($i = 0; $i < 10; ++$i)
							<tr>
								<td>{{ $ret['rank'][$gender][$i][0] }}</td>
								<td>{!! get_flag($ret['rank'][$gender][$i][1]) !!}</td>
								<td>{{ $ret['rank'][$gender][$i][2] }}</td>
							</tr>
						@endfor
					</tbody>
				</table>
			@endforeach
		</div>

		<div id=iHomeTours class=cHomeBlock>
			<div id=iHomeToursTitle class=cHomeTitle>{{ __('home.title.tours') }}</div>
			<div class=cHomeContent>
				@foreach ($ret['tour'] as $tour)
					<div class=cHomeTour href="{{ url(join("/", [App::getLocale(), 'draw', $tour[1], date('Y', time() + 5 * 86400)])) }}">
						<div class="cHomeTourLogo cHomeTourLogo{{ count($tour[0]) }}">
							@foreach ($tour[0] as $level)
								<img src="{{ get_tour_logo_by_id_type_name($tour[1], $level, $tour[2]) }}" />
							@endforeach
						</div>
						<div class=cHomeTourCity>{{ translate_tour($tour[2]) }}</div>
					</div>
				@endforeach
			</div>
		</div>

		<div id=iHomeQuicks class=cHomeBlock>
			<div id=iHomeQuicksTitle class=cHomeTitle>{{ __('home.title.quicks') }}</div>
			<div class=cHomeContent>
				<div class=cHomeQuick href="{{ url(join("/", [App::getLocale(), 'profile', 'atp'])) }}"><div class=cHomeQuickLogo>{!! get_icon('yonghuziliao') !!}</div><div class=cHomeQuickName>{{ __('home.quick.profile') }}</div></div>
				<div class=cHomeQuick href="{{ url(join("/", [App::getLocale(), 'h2h'])) }}"><div class=cHomeQuickLogo>{!! get_icon('competitor') !!}</div><div class=cHomeQuickName>{{ __('home.quick.h2h') }}</div></div>
				<div class=cHomeQuick href="{{ url(join("/", [App::getLocale(), 'history', 'activity'])) }}"><div class=cHomeQuickLogo>{!! get_icon('chengjiu-') !!}</div><div class=cHomeQuickName>{{ __('home.quick.activity') }}</div></div>
				<div class=cHomeQuick href="{{ url(join("/", [App::getLocale(), 'history', 'topn'])) }}"><div class=cHomeQuickLogo>{!! get_icon('huojian-blue') !!}</div><div class=cHomeQuickName>{{ __('home.quick.topnweeks') }}</div></div>
				<div class=cHomeQuick href="{{ url(join("/", [App::getLocale(), 'calendar', date('Y', time() + 5 * 86400)])) }}"><div class=cHomeQuickLogo>{!! get_icon('schedule') !!}</div><div class=cHomeQuickName>{{ __('home.quick.calendar') }}</div></div>
				<div class=cHomeQuick href="{{ url(join("/", [App::getLocale(), 'history', 'official'])) }}"><div class=cHomeQuickLogo>{!! get_icon('diqiu') !!}</div><div class=cHomeQuickName>{{ __('home.quick.official') }}</div></div>
				<div class=cHomeQuick href="{{ url(join("/", [App::getLocale(), 'history', 'gst1'])) }}"><div class=cHomeQuickLogo>{!! get_icon('jiangbei') !!}</div><div class=cHomeQuickName>{{ __('home.quick.tourquery') }}</div></div>
				<div class=cHomeQuick href="{{ url(join("/", [App::getLocale(), 'history', 'evolv'])) }}"><div class=cHomeQuickLogo>{!! get_icon('jiegoudaohang') !!}</div><div class=cHomeQuickName>{{ __('home.quick.evolv') }}</div></div>
			</div>
		</div>

	</div>
</div>

@endsection
