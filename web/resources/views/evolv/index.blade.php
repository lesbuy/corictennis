@extends('layouts.header')

@section('content')

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.evolv') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.pickmeup') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.optionpicker') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.evolv') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.pickmeup') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.optionpicker') }}">
@endif
<script type="text/javascript" language="javascript" src="https://cdn.bootcss.com/echarts/4.2.0-rc.1/echarts.min.js"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.optionpicker') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.pickmeup') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.interpolate') }}"></script>

@php $theme = isset($_COOKIE['theme']) && $_COOKIE['theme'] == "dark" ? "dark" : "light"; @endphp

<script type="text/javascript" language="javascript" class="init">

var gender = "atp";
var freq = "every-week";
var topn = 10;
var start = "2018-01-01";
var end = "{{ date('Y-m-d', time()) }}";
var itvl = 160;
var loop = 1;
var animationSpeed = 600;
var maxItvl = 2560;
var minItvl = 10;
var loopIndex = 0;
var timer = null;
var theme = "{{ $theme }}";
{{-- 表示纵轴左边和右边的长度倍数。在pc上是20%，在手机上是50% --}}
var diff = device == 0 ? 0.2 : 0.5;
{{-- 表示纵轴左边，topn标记与人名的长度倍数。在pc上是20%，在手机上是20% --}}
var diff2 = device == 0 ? 0.2 : 0.2;

var origin_data = [];
var cur_no1_id;
var cur_no1_input;
var dom;
var chart;

$(function (){

	$('#iEvSpeed').html("{{ __('evolv.selectBar.speed') }}" + (1000 / itvl) + "/s");
	document.datePicker.dateStart.value = start;
	document.datePicker.dateEnd.value = end;

	$(':radio[name=gender]').on('click', function() {
		if (timer) {clearInterval(timer); timer = null; $('#iEvPlay').show(); $('#iEvPause').hide();}
		loopIndex = 0;
		gender = $(this).val();
		init();
	});
	$(':radio[name=freq]').on('click', function() {
		if (timer) {clearInterval(timer); timer = null; $('#iEvPlay').show(); $('#iEvPause').hide();} 
		freq = $(this).val();
		loopIndex = 0;
		init();
	});
	$(':radio[name=loop]').on('click', function() {
		loop = $(this).val();
	});
	$('#iEvTopNSelector').on('change', function () {
		if (timer) {clearInterval(timer); timer = null; $('#iEvPlay').show(); $('#iEvPause').hide();} 
		topn = parseInt($(this).val());
		loopIndex = 0;
		init();
	});

	pickmeup('#iDatePickerStart', {
		format: "Y-m-d",
		hide_on_select  : true,
		min : "1973-01-01",
		max : GetDateStr(1),
		prev: '<<',
		next: '>>',
		default_date: false,
		position: 'bottom',
		first_day: {{ __('frame.datePicker.firstDay') }},
		title_format: "{{ __('frame.datePicker.showMonthAfterYear') }}",
		class_name: "cCalendar",
		locale: '{{ App::getLocale() }}',
		locales: {
			{{ App::getLocale() }}: {
				daysMin: ['{{ __('frame.datePicker.sun') }}','{{ __('frame.datePicker.mon') }}','{{ __('frame.datePicker.tue') }}','{{ __('frame.datePicker.wed') }}','{{ __('frame.datePicker.thu') }}','{{ __('frame.datePicker.fri') }}','{{ __('frame.datePicker.sat') }}'],  
				daysShort: ['{{ __('frame.datePicker.sun') }}','{{ __('frame.datePicker.mon') }}','{{ __('frame.datePicker.tue') }}','{{ __('frame.datePicker.wed') }}','{{ __('frame.datePicker.thu') }}','{{ __('frame.datePicker.fri') }}','{{ __('frame.datePicker.sat') }}'],  
				days: ['{{ __('frame.datePicker.sun') }}','{{ __('frame.datePicker.mon') }}','{{ __('frame.datePicker.tue') }}','{{ __('frame.datePicker.wed') }}','{{ __('frame.datePicker.thu') }}','{{ __('frame.datePicker.fri') }}','{{ __('frame.datePicker.sat') }}'],  
				monthsShort: ['{{ __('frame.datePicker.jan') }}','{{ __('frame.datePicker.feb') }}','{{ __('frame.datePicker.mar') }}','{{ __('frame.datePicker.apr') }}','{{ __('frame.datePicker.may') }}','{{ __('frame.datePicker.jun') }}','{{ __('frame.datePicker.jul') }}','{{ __('frame.datePicker.aug') }}','{{ __('frame.datePicker.sep') }}','{{ __('frame.datePicker.oct') }}','{{ __('frame.datePicker.nov') }}','{{ __('frame.datePicker.dec') }}'],  
				months: ['{{ __('frame.datePicker.jan') }}','{{ __('frame.datePicker.feb') }}','{{ __('frame.datePicker.mar') }}','{{ __('frame.datePicker.apr') }}','{{ __('frame.datePicker.may') }}','{{ __('frame.datePicker.jun') }}','{{ __('frame.datePicker.jul') }}','{{ __('frame.datePicker.aug') }}','{{ __('frame.datePicker.sep') }}','{{ __('frame.datePicker.oct') }}','{{ __('frame.datePicker.nov') }}','{{ __('frame.datePicker.dec') }}'],  
			},
		},
	});
	$('#iDatePickerStart')[0].addEventListener('pickmeup-change', function(e) {
		var formatted = e.detail.formatted_date;
		$(this).val(formatted);
		start = formatted;
		if (timer) {clearInterval(timer); timer = null; $('#iEvPlay').show(); $('#iEvPause').hide();} loopIndex = 0;
		init();
	});

	pickmeup('#iDatePickerEnd', {
		format: "Y-m-d",
		hide_on_select  : true,
		min : "1973-01-01",
		max : GetDateStr(1),
		prev: '<<',
		next: '>>',
		default_date: false,
		position: 'bottom',
		first_day: {{ __('frame.datePicker.firstDay') }},
		title_format: "{{ __('frame.datePicker.showMonthAfterYear') }}",
		class_name: "cCalendar",
		locale: '{{ App::getLocale() }}',
		locales: {
			{{ App::getLocale() }}: {
				daysMin: ['{{ __('frame.datePicker.sun') }}','{{ __('frame.datePicker.mon') }}','{{ __('frame.datePicker.tue') }}','{{ __('frame.datePicker.wed') }}','{{ __('frame.datePicker.thu') }}','{{ __('frame.datePicker.fri') }}','{{ __('frame.datePicker.sat') }}'],  
				daysShort: ['{{ __('frame.datePicker.sun') }}','{{ __('frame.datePicker.mon') }}','{{ __('frame.datePicker.tue') }}','{{ __('frame.datePicker.wed') }}','{{ __('frame.datePicker.thu') }}','{{ __('frame.datePicker.fri') }}','{{ __('frame.datePicker.sat') }}'],  
				days: ['{{ __('frame.datePicker.sun') }}','{{ __('frame.datePicker.mon') }}','{{ __('frame.datePicker.tue') }}','{{ __('frame.datePicker.wed') }}','{{ __('frame.datePicker.thu') }}','{{ __('frame.datePicker.fri') }}','{{ __('frame.datePicker.sat') }}'],  
				monthsShort: ['{{ __('frame.datePicker.jan') }}','{{ __('frame.datePicker.feb') }}','{{ __('frame.datePicker.mar') }}','{{ __('frame.datePicker.apr') }}','{{ __('frame.datePicker.may') }}','{{ __('frame.datePicker.jun') }}','{{ __('frame.datePicker.jul') }}','{{ __('frame.datePicker.aug') }}','{{ __('frame.datePicker.sep') }}','{{ __('frame.datePicker.oct') }}','{{ __('frame.datePicker.nov') }}','{{ __('frame.datePicker.dec') }}'],  
				months: ['{{ __('frame.datePicker.jan') }}','{{ __('frame.datePicker.feb') }}','{{ __('frame.datePicker.mar') }}','{{ __('frame.datePicker.apr') }}','{{ __('frame.datePicker.may') }}','{{ __('frame.datePicker.jun') }}','{{ __('frame.datePicker.jul') }}','{{ __('frame.datePicker.aug') }}','{{ __('frame.datePicker.sep') }}','{{ __('frame.datePicker.oct') }}','{{ __('frame.datePicker.nov') }}','{{ __('frame.datePicker.dec') }}'],  
			},
		},
	});
	$('#iDatePickerEnd')[0].addEventListener('pickmeup-change', function(e) {
		var formatted = e.detail.formatted_date;
		$(this).val(formatted);
		end = formatted;
		if (timer) {clearInterval(timer); timer = null; $('#iEvPlay').show(); $('#iEvPause').hide();} loopIndex = 0;
		init();
	});

	$('#iEvPlay').on('click', function () { play(); });
	$('#iEvPause').on('click', function () { pause(); });
	$('#iEvSpeedUp').on('click', function () { speedup(); });
	$('#iEvSpeedDown').on('click', function () { speeddown(); });

	dom = document.getElementById('iEvChart');
	chart = echarts.init(dom);

	chart.on('click', function (params){
		var x = params.event.offsetX;
		var y = params.event.offsetY;
		var rate = Math.round(this.convertFromPixel({seriesIndex: 5}, [x, y])[0]);
		loopIndex = rate;
		$('#storeStatus > input').attr('data-current', 0);
		$('#storeStatus > input').attr('data-days', 0);
		$('#storeStatus > input').attr('data-weeks', 0);
		$('#storeStatus > input').attr('data-consecutive-days', 0);
		$('#storeStatus > input').attr('data-consecutive-weeks', 0);
		run();
	});

	init();
});

	function run() {
		var d = origin_data[loopIndex];
		var year = d[0];
		d = d.concat();
		d.shift();
		var value_max = Math.max.apply(null, d.map(function (item) {return item[1]}));
		if (value_max == 0) value_max = 10000;
		value_max = Math.ceil(value_max * 1.2 / 100) * 100;
		{{-- 这是X轴的左边最小值 --}}
		value_min = -value_max * diff;
		var progressBar = loopIndex;

		if (year) {
			$('#iEvDate').html(year);
			cur_no1_id = d[0][0].split("\1")[2];
			cur_no1_input = $('#storeStatus > #info-' + cur_no1_id);

			if (cur_no1_input.attr('data-current') == 0) {
				$('#storeStatus > input').attr('data-consecutive-days', 0);
				$('#storeStatus > input').attr('data-consecutive-weeks', 0);
			}

			$('#storeStatus > input').attr('data-current', 0);
			cur_no1_input.attr('data-current', 1);

			$('#iEvNo1Head').css('background-image', 'url(' + cur_no1_input.attr('data-head-src') + ')');
			$('#iEvNo1Name').html(decodeURIComponent(cur_no1_input.attr('data-flag')) + ' ' + cur_no1_input.attr('data-name'));
			$('#iEvNo1TitleDate').html(year);
		}

		cur_no1_input.attr('data-days', parseInt(cur_no1_input.attr('data-days')) + 1);
		cur_no1_input.attr('data-consecutive-days', parseInt(cur_no1_input.attr('data-consecutive-days')) + 1);
		if (parseInt(cur_no1_input.attr('data-days')) % 7 == 1) {
			cur_no1_input.attr('data-weeks', parseInt(cur_no1_input.attr('data-weeks')) + 1);
			cur_no1_input.attr('data-consecutive-weeks', parseInt(cur_no1_input.attr('data-consecutive-weeks')) + 1);
		}

		$('#iEvNo1Days').html(cur_no1_input.attr('data-days'));
		$('#iEvNo1ConsDays').html(cur_no1_input.attr('data-consecutive-days'));
		$('#iEvNo1Weeks').html(cur_no1_input.attr('data-weeks'));
		$('#iEvNo1ConsWeeks').html(cur_no1_input.attr('data-consecutive-weeks'));

		chart.setOption({
/*
			yAxis: [{
				data: d.map(function (item) {return item[0]}),
			}],
*/
			xAxis: [
				{},
				{
					max: value_max,
					min: value_min,
				},
			],
			series: [
				{
					data: d.map(function (item) {return {name: item[0], value: item[1] }}),
				},
				{
					data: d.map(function (item) {return {name: (device == 0 ? item[0].replace(/\1.*$/, '') : item[0].replace(/\1.*$/, '')), value: item[1], itemStyle: {color: findColor(item[0].replace(/\1.*$/, ''))}}}),
					label: {normal: {formatter: '{b}'}},
				},
				{
					data: d.map(function (item) {return {name: item[0].split("\1")[1] + (device == 0 ? " | " : "\n") + item[0].split("\1")[0], value: value_min * (1 - diff2), }}),
					label: {normal: {formatter: '{b}'}},
				},
				{
					data: [...Array(topn * 1.5).keys()].map(function (item) {return {value: value_min * diff2, itemStyle: {
						color: item == 0 ? '{{ Config::get('const.barColor.W') }}' : (item < 5 ? '{{ Config::get('const.barColor.F') }}' : (item < 10 ? '{{ Config::get('const.barColor.SF') }}' : '{{ Config::get('const.barColor.QF') }}')),
					}}}),
				},
				{
					data:[progressBar],
				},
				{},
				{
					data:[progressBar],
				}
			],
//			animationDurationUpdate: animationSpeed,
		});

		if (loopIndex + 1 == origin_data.length) {
			if (loop == 0) {
				loopIndex = 0;
			} else {
				if (timer) {clearInterval(timer); timer = null; $('#iEvPlay').show(); $('#iEvPause').hide();} 
			}
		} else {
			loopIndex = (loopIndex + 1) % origin_data.length;
		}
	}

	function play() {
		if (!timer)
			timer = setInterval('run()', itvl);
		$('#iEvSpeed').html("{{ __('evolv.selectBar.speed') }}" + (1000 / itvl) + "/s");
		$('#iEvPlay').hide();
		$('#iEvPause').show();
	};

	function pause() {
		clearInterval(timer);
		timer = null;
		$('#iEvSpeed').html("{{ __('evolv.selectBar.speed') }}" + (1000 / itvl) + "/s");
		$('#iEvPlay').show();
		$('#iEvPause').hide();
	};

	function speedup() {
		itvl = itvl == minItvl ? maxItvl : itvl / 2;
//		animationSpeed = itvl;
		if (timer) {
			clearInterval(timer);
			timer = null;
			timer = setInterval('run()', itvl);
		}
		$('#iEvSpeed').html("{{ __('evolv.selectBar.speed') }}" + (1000 / itvl) + "/s");
	}

	function speeddown() {
		itvl = itvl == maxItvl ? minItvl : itvl * 2;
//		animationSpeed = itvl;
		if (timer) {
			clearInterval(timer);
			timer = null;
			timer = setInterval('run()', itvl);
		}
		$('#iEvSpeed').html("{{ __('evolv.selectBar.speed') }}" + (1000 / itvl) + "/s");
	}

	function init() {

		var c_url = "{{ slash(url(App::getLocale()) . "/history/evolv/query/") }}" + [gender, topn, start, end, freq].join("/");
		_hmt.push(['_trackCustomEvent', 'evolv', {'gender':gender,'digit':topn,'start_time':start,'end_time':end}]);
		ga('send', 'pageview', c_url);


		var url = "{{ url(App::getLocale() . "/history/evolv/query/") }}" + "/" + [gender, topn, start, end, freq].join("/");
		$.ajax({
			type: 'GET',
			url: url,
			data: [],
			success: function (data) {
				var origin_ret = JSON.parse(data);
				origin_data = interpolate(origin_ret.data, topn * 1.5, freq);
				
				$('#storeStatus').html("");
				for (var k in origin_ret.head) {
					$('#storeStatus').append(
						'<input type=hidden'
						+ ' id="info-' + k + '"'
						+ ' data-head-src="' + origin_ret.head[k] + '"'
						+ ' data-days=0 data-weeks=0 data-consecutive-days=0 data-consecutive-weeks=0 data-current=0'
						+ ' data-name="' + origin_ret.name[k] + '"'
						+ ' data-flag="' + origin_ret.flag[k] + '"'
						+ ' data-birth="' + origin_ret.birth[k] + '"'
						+ ' />'
					);
				}

				$('#iEvNo1TitleTitle').html(gender.toUpperCase() + " Top" + topn);

				option = {
					grid: [
						{{-- 主背景 --}}
						{
							left: '0%',
							right: '0%',
							top: '5%',
							height: '135%',
							z: -5,
							show: true,
							borderWidth: 0,
						},
						{{-- 进度条背景 --}}
						{
							bottom: '0%',
							height: '5%',
							left: '0%',
							right: '0%',
							z: -2,
							backgroundColor: theme == "light" ? '{{ Config::get('const.globalColor.white') }}' : (theme == "dark" ? '{{ Config::get('const.globalColor.black') }}' : ""),
							show: true,
							borderWidth: 0,
						}
					],
					yAxis: [
						{
							axisTick: {show: false},
							axisLine: {show: false},
							axisLabel: {show: false},
							data: [...Array(topn * 1.5).keys()].map(function (item) {return item + 1}),
							inverse: true,
						},
						{
							axisTick: {show: false},
							axisLine: {show: false},
							axisLabel: {show: false},
							data: [],
							gridIndex: 1,
						},
					],
					xAxis: [
						{{-- 因为需要2个x轴，第一个x轴是在下方的，第二个才是上方，所以第一个轴简单处理 --}}
						{
							splitLine: {show: false},
						},
						{{-- 主要使用这个轴作为x轴 --}}
						{
							splitLine: {
								show: true,
								lineStyle: {
									color: '{{ Config::get('const.globalColor.lightGray') }}',
									type: 'dash',
								},
								z: 100,
							},
							axisLabel: {
								show: true,
								color: '{{ Config::get('const.globalColor.midGray') }}',
								formatter: function (item, idx) {
									if (item >= 0) return item;
									else return "";
								},
								showMaxLabel: false,
							},
							axisLine: {show: false},
							axisTick: {show: false},
							z: -3,
							max: 10000,
							min: -10000 * diff,
						},
						{
							gridIndex: 1,
							splitLine: {show: false},
							axisLine: {show: false},
							axisLabel: {show: false},
							axisTick: {show: false},
							max: origin_data.length - 1,
						}
					],
					animationDurationUpdate: animationSpeed,
					animationEasing: 'linear',
					textStyle: {
						fontSize: $('html').css('font-size').replace(/px/, '') * 1,
					},
					series: [
						{{-- 色条1，显示右侧分数 --}}
						{
							type: 'bar',
							itemStyle: {
								normal: {
									color: 'transparent'
								}
							},
							barGap: '-100%',
							xAxisIndex: 1,
							yAxisIndex: 0,
							z: -3,
							label: {
								normal: {
									position: 'right',
									show: true,
									color: '{{ Config::get('const.globalColor.midGray') }}',
								}
							},
							data: [...Array(topn * 1.5).keys()].map(function (item) {return 0}),
							silent: true,
							barWidth: topn == 20 ? '80%' : '60%',
						},
						{{-- 色条2，显示右内侧人名 --}}
						{
							type: 'bar',
							itemStyle: {
								normal: {
									shadowColor: 'rgba(0,0,0,0.1)',
									shadowBlur: 10,
								}
							},
							xAxisIndex: 1,
							yAxisIndex: 0,
							z: -3,
							label: {
								normal: {
									position: 'insideRight',
									show: true,
									color: '{{ Config::get('const.globalColor.white') }}',
								}
							},
							data: [...Array(topn * 1.5).keys()].map(function (item) {return 0}),
							silent: true,
							barWidth: topn == 20 ? '80%' : '60%',
						},
						{{-- 左边无色条，固定宽度，显示人名 --}}
						{
							type: 'bar',
							stack: 2,
							itemStyle: {
								normal: {
									color: '{{ Config::get('const.globalColor.white') }}',
									shadowColor: 'rgba(0,0,0,0.1)',
									shadowBlur: 10,
								}
							},
							xAxisIndex: 1,
							yAxisIndex: 0,
							z: -3,
							label: {
								normal: {
									position: 'insideLeft',
									show: true,
									color: '{{ Config::get('const.globalColor.black') }}',
								}
							},
							data: [...Array(topn * 1.5).keys()].map(function (item) {return -10000 * diff * (1 - diff2);}),
							silent: true,
							barWidth: topn == 20 ? '80%' : '60%',
						},
						{{-- 左侧TopN --}}
						{
							type: 'bar',
							stack: 2,
							itemStyle: {
								normal: {
									shadowColor: 'rgba(0,0,0,0.1)',
									shadowBlur: 10,
								}
							},
							xAxisIndex: 1,
							yAxisIndex: 0,
							z: -3,
							label: {
								normal: {
									position: 'inside',
									show: true,
									color: '{{ Config::get('const.globalColor.white') }}',
									formatter: '{b}'
								}
							},
							data: [...Array(topn * 1.5).keys()].map(function (item) {return -10000 * diff * diff2;}),
							silent: true,
							barWidth: topn == 20 ? '80%' : '60%',
						},
						{{-- 进度条蓝色部分 --}}
						{
							type: 'bar',
							xAxisIndex: 2,
							yAxisIndex: 1,	
							stack: 3,
							z: -1,
							data: [],
							barWidth: 10,
							barGap: '-100%',
							color: '{{ Config::get('const.globalColor.hl') }}',
						},
						{{-- 进度条灰色部分 --}}
						{
							type: 'bar',
							xAxisIndex: 2,
							yAxisIndex: 1,	
							stack: 4,
							z: -2,
							data: [origin_data.length],
							barWidth: 10,
							barGap: '-100%',
							itemStyle: {
								normal: {
									color: '{{ Config::get('const.globalColor.lightGray') }}',
								},
								emphasis: {
									color: '{{ Config::get('const.globalColor.lightGray') }}',
								}
							}
						},
						{{-- 进度条上的圆点 --}}
						{
							type: 'line',
							xAxisIndex: 2,
							yAxisIndex: 1,
							data: [],
							z: 5,
							smooth: true,
							symbolSize: 13,
							itemStyle: {
								borderWidth: 0,
								shadowColor: 'rgba(0,0,0,0.1)',
								shadowBlur: 10,
							}
						}
					]
				};

				chart.clear();
				chart.setOption(option);

				run();
			}
		});

	}

</script>

<div id=iEv>
	<div id=iEvSelector>
		<div id=iEvSelectorLeft>
			<label class="weakenColor">{{ __('evolv.selectBar.player_type') }}</label>
			<input type=radio name=gender id=iEvGenderATP value=atp checked></input><label class="unselected" for=iEvGenderATP>{{ __('evolv.selectBar.gender.atp') }}</label>
			<input type=radio name=gender id=iEvGenderWTA value=wta></input><label class="unselected" for=iEvGenderWTA>{{ __('evolv.selectBar.gender.wta') }}</label>
			<label class="weakenColor">{{ __('evolv.selectBar.period_type') }}</label>
			<input type=radio name=freq id=iEvFreqEW value="every-week" checked></input><label class="unselected" for=iEvFreqEW>{{ __('evolv.selectBar.freq.ew') }}</label>
			<input type=radio name=freq id=iEvFreqYE value="year-end"></input><label class="unselected" for=iEvFreqYE>{{ __('evolv.selectBar.freq.ye') }}</label>
			<label class="weakenColor">{{ __('evolv.selectBar.topn') }}</label>
			<select class="selected" id=iEvTopNSelector>
				<option value=10 selected>Top 10</option>
				<option value=20>Top 20</option>
			</select>
		</div>
<!--
		<input type=radio name=loop id=iEvLoop0 value="0"></input><label class="unselected" for=iEvLoop0>{!! get_icon('xunhuan') !!}</label>
		<input type=radio name=loop id=iEvLoop1 value="1" checked></input><label class="unselected" for=iEvLoop1>{!! get_icon('danquxunhuan') !!}</label>
-->
		<div id=iEvSelectorRight>
			<form name=datePicker>
				<label class="weakenColor">{{ __('evolv.selectBar.period_date') }}</label>
				<input class="unselected cDatePicker" type=text name=dateStart id="iDatePickerStart" readonly=readonly />
				<label class="weakenColor"> - </label>
				<input class="unselected cDatePicker" type=text name=dateEnd id="iDatePickerEnd" readonly=readonly />
			</form>
		</div>
	</div>

	<div id=iEvNo1>
		<div id=iEvNo1Text class="diffColor">#<br>1</div>
		<div id=iEvNo1Head></div>
		<div id=iEvNo1Info>
			<div id=iEvNo1Name></div>
			<table id=iEvNo1Table><tbody>
				<tr><td>{{ __('evolv.desc.age') }}</td><td colspan=4 id=iEvNo1Age></td></tr>
				<tr><td>{{ __('evolv.desc.days') }} <pname alt="{!! __('evolv.more') !!}">{!! get_icon('beizhu') !!}</pname></td><td>{{ __('evolv.desc.total') }}</td><td id=iEvNo1Days></td><td>{{ __('evolv.desc.consecutive') }}</td><td id=iEvNo1ConsDays></td></tr>
				<tr><td>{{ __('evolv.desc.weeks') }}</td><td>{{ __('evolv.desc.total') }}</td><td id=iEvNo1Weeks></td><td>{{ __('evolv.desc.consecutive') }}</td><td id=iEvNo1ConsWeeks></td></tr>
			</tbody></table>
		</div>
		<div id=iEvNo1Title>
			<div id=iEvNo1TitleTitle></div>
			<div id=iEvNo1TitleDate></div>
		</div>
	</div>
	<div id=iEvChart>

	</div>

	<div id=iEvController>
		<div id=iEvControllerLeft>
			<div id=iEvPlay>{!! get_icon('play') !!}</div>
			<div id=iEvPause style="display: none">{!! get_icon('zantingtingzhi') !!}</div>
			<div id=iEvDate></div>
		</div>
		<div id=iEvControllerRight>
			<div id=iEvSpeed></div>
			<div id=iEvSpeedDown>{!! get_icon('kuaitui') !!}</div>
			<div id=iEvSpeedUp>{!! get_icon('kuaijin') !!}</div>
		</div>
	</div>

</div>

<div id=storeStatus>
</div>

@endsection
