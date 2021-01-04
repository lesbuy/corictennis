@extends('layouts.header')

@section('content')

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.result') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.pickmeup') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.h2hDetail') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.result') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.pickmeup') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.h2hDetail') }}">
@endif

<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.result') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.echarts') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.pickmeup') }}"></script>

<script type="text/javascript" language="javascript" class="init">

var show_status;
@if (isset($show_status) && $show_status == 1)
	show_status = 1;
@else
	show_status = -1;
@endif

var live_timer;

entrytime = {{ time() }};
refresh_ddl = (new Date()).getTime() + 600000;
refresh_interval = 10;

function open_h2h(eid, sextype, matchid, year, id1, id2, p1, p2, sd) {
	var data = {
		status: 'ok',
		method: 'p',
		p1id: id1,
		p2id: id2,
//		p1: p1,
//		p2: p2,
		type: sextype,
		surface: 'a',
		level: 'a',
		onlyMD: '',
		onlyFinal: '',
		sd: sd,
	}

	var c_url = ["{{ slash(url(App::getLocale() . '/h2h')) }}", p1.replace(/ /g, ""), p2.replace(/ /g, "")].join("/");
	_hmt.push(['_trackCustomEvent', 'result_h2h', {'p1':p1.replace(/ /g, ""),'p2':p2.replace(/ /g, "")}]);
	ga('send', 'pageview', c_url);

	$('#iMask').fadeIn(500).css('display', '-webkit-flex');
	$('#iMask').html('<div id=iAjaxNotice>' + '{{ __('frame.notice.gfw') }}'  + '</div>');

	$.ajax({
		type: 'POST',
		url: "{{ url(App::getLocale() . "/h2h/query") }}",
		data: data,
		success: function (data) {
			$('#iMask').html(data);
		}
	});
};

function open_stat(eid, sextype, matchid, year, id1, id2, p1, p2) {
	var data = {
		id1: id1,
		id2: id2,
		p1: p1,
		p2: p2,
		eid: eid,
		type: sextype,
		matchid: matchid,
		year: year,
	}

	var c_url = ["{{ slash(url(App::getLocale() . '/matchstat')) }}", eid, matchid, year, p1.replace(/ /g, ""), p2.replace(/ /g, "")].join("/");
	_hmt.push(['_trackCustomEvent', 'result_stat', {'year':year,'eid':eid,'p1':p1.replace(/ /g, ""),'p2':p2.replace(/ /g, ""),'matchid':matchid}]);
	ga('send', 'pageview', c_url);

	$('#iMask').fadeIn(500).css('display', '-webkit-flex');
	$('#iMask').html('<div id=iAjaxNotice>' + '{{ __('frame.notice.gfw') }}'  + '</div>');

	$.ajax({
		type: 'POST',
		url: "{{ url(App::getLocale() . "/stat/query") }}",
		data: data,
		success: function (data) {
			$('#iMask').html(data);
		}
	});
};

function open_detail(fsid, eid, sextype, matchid, year, id1, id2, p1, p2) {
	var data = {
		fsid: fsid,
		id1: id1,
		id2: id2,
		p1: p1,
		p2: p2,
		eid: eid,
		type: sextype,
		matchid: matchid,
		year: year,
	}

	var c_url = ["{{ slash(url(App::getLocale() . '/matchdetail')) }}", eid, matchid, year, p1.replace(/ /g, ""), p2.replace(/ /g, "")].join("/");
	_hmt.push(['_trackCustomEvent', 'result_pbp', {'year':year,'eid':eid,'p1':p1.replace(/ /g, ""),'p2':p2.replace(/ /g, ""),'matchid':matchid}]);
	ga('send', 'pageview', c_url);

	$('#iMask').fadeIn(500).css('display', '-webkit-flex');
	$('#iMask').html('<div id=iAjaxNotice>' + '{{ __('frame.notice.gfw') }}'  + '</div>');

	$.ajax({
		type: 'POST',
		url: "{{ url(App::getLocale() . "/pbp/query") }}",
		data: data,
		success: function (data) {
			$('#iMask').html(data);
		}
	});
};

function update_data(data) {
	data = $.parseJSON(data);
	for (var tourId in data) {
		if (typeof(tourId) === "function") continue;
		if (tourId === "ts") {
			entrytime = data[tourId];
			continue;
		}
		for (var matchId in data[tourId]) {
			if (typeof(matchId) === "function") continue;
			matchInfo = data[tourId][matchId];
			var matchBlock = $('#iResult' + tourId + ' .cResultMatch[match-id="' + matchId + '"]');
			if (matchBlock.length == 0) continue;
			if (matchBlock.attr('match-status') == 2) continue;

			var status = matchInfo[0];
			var result = new Array(matchInfo[1], matchInfo[2]);
			var score = new Array(matchInfo[3], matchInfo[4]);
			var point = new Array(matchInfo[5], matchInfo[6]);
			var dura = matchInfo[7];
			var pointflag = matchInfo[8];

			matchBlock.find('.cResultMatchDura').html("<i class=iconfont>&#xe625;</i> " + dura);
			if (pointflag != "") {
				matchBlock.find('.cResultMatchMidPointFlag').html(pointflag);
				matchBlock.find('.cResultMatchMidPointFlag').show(500);
			} else {
				matchBlock.find('.cResultMatchMidPointFlag').hide(500);
			}

			{{-- 当status变化时进行修改。如果不符合当前显示的status则隐藏，如果符合则显示。同时整个赛事也显示 --}}
			if (parseInt(matchBlock.attr('match-status')) != status) {
				matchBlock.attr('match-status', status);
				if (show_status > -1) {
					var ppp = matchBlock.parent().parent().parent();
					if (!matchBlock.hasClass('cResultHidden') && show_status != status) {
						matchBlock.addClass('cResultHidden');
						if (ppp.find('.cResultMatch').length == ppp.find('.cResultMatch.cResultHidden').length && !ppp.hasClass('cResultHidden')) {
							ppp.addClass('cResultHidden');
						}
					} else if (matchBlock.hasClass('cResultHidden') && show_status == status) {
						matchBlock.removeClass('cResultHidden');
						if (ppp.hasClass('cResultHidden')) {
							ppp.removeClass('cResultHidden');
						}
					}
				}
			}

			{{-- 更新比分 --}}
			var tr = new Array(matchBlock.find('tr:first-child'), matchBlock.find('tr:last-child'));

			for (i = 0; i < 2; ++i) {
				var td = tr[i].find('div>div:last-child');
				if (reviseScoreTd(td.html()) < reviseScoreTd(point[i])) {
					td.addClass('cResultMatchMidTableCellHL');
				} else {
					td.removeClass('cResultMatchMidTableCellHL');
				}
				td.html(point[i]);
			}

			for (i = 0; i < 2; ++i) {
				if (result[i] == "Winner") {
					tr[i].addClass('cResultMatchMidTableRowWinner');
					tr[1-i].removeClass('cResultMatchMidTableRowWinner');
					tr[i].removeClass('cResultMatchMidTableRowServe');
					tr[i-1].removeClass('cResultMatchMidTableRowServe');
				} else if (result[i] == "Serve") {
					tr[i].addClass('cResultMatchMidTableRowServe');
					tr[1-i].removeClass('cResultMatchMidTableRowServe');
				} else {
					tr[i].removeClass('cResultMatchMidTableRowWinner');
					tr[i].removeClass('cResultMatchMidTableRowServe');
				}
			}

			if (score[0].length == 5 && score[1].length == 5) {
				for (i = 0; i < 2; ++i) {
					for (j = 0; j < 5; ++j) {
						var _i = i + 1;
						var _j = j + 1;

						var td = tr[i].find('div>div:nth-child(' + _j + ')');
						if (reviseScoreTd(td.html()) < reviseScoreTd(score[i][j]) && status == 1) {
							td.addClass('cResultMatchMidTableCellHL');
						} else {
							td.removeClass('cResultMatchMidTableCellHL');
						}

						td.html(score[i][j]);
						if (score[i][j] === "") td.addClass('hidden'); else td.removeClass('hidden');
					}
				}
			}
		}
	}
}

function live_update() {

	if ((new Date()).getTime() > refresh_ddl) {
		window.location.reload();
	}

	var itvl = entrytime + refresh_interval;
	itvl = itvl - itvl % refresh_interval;

	$.ajax ({
		type: 'GET',
		url: "{{ url(App::getLocale() . "/result/live") }}" + '/' + itvl,

		success: function (data) {
			update_data(data);
		}
	});
};

function init(day) {

	var arr = day.split('-');
	var y = parseInt(arr[0]);
	var m = parseInt(arr[1]) - 1;
	var d = parseInt(arr[2]);

	$.ajax({

		type: 'POST',
		url: "{{ url(join("/", [App::getLocale(), 'oop'])) }}" + "/" + day + "/" + ((new Date(y, m, d)).getTime() / 1000),
		success: function (data) {
			$('.cLoading').hide();
			$('#iResultDetail').html(data);
		},
	});
};

function redirectto(day) {
	window.location.href = "{{ url(join("/", [App::getLocale(), 'oop'])) }}" + "/" + day;
}

$(function() {

	{{-- 在时间范围内才刷新 --}}
	var ts = parseInt(new Date() / 1000);

	@if (isset($date) && $date)
		@php $arr = explode("-", $date); @endphp
		var yyyy = parseInt("{{ $arr[0] }}");
		var mm = parseInt("{{ $arr[1] }}") - 1;
		var dd = parseInt("{{ $arr[2] }}");
	@else
		var day = new Date();
		var yyyy = day.getFullYear();
		var mm = day.getMonth();
		var dd = day.getDate();
	@endif

	var today = formatDate(yyyy, mm, dd, 0);
	var tomorrow = formatDate(yyyy, mm, dd, 1);
	var yesterday = formatDate(yyyy, mm, dd, -1);
	var daybeforeyesterday = formatDate(yyyy, mm, dd, -2);

	var min_refresh_time = (new Date(yyyy, mm, dd)).getTime();
	var max_refresh_time = (new Date(yyyy, mm, dd)).getTime() + 86400000 + 7200000;
	var now = new Date().getTime();
	if (now >= min_refresh_time && now <= max_refresh_time) {
		live_timmer = setInterval(live_update, 10000);
	}

	{{-- 点击title则打开或关闭下面的内容。或下面已加载过则不再加载，否则从服务器加载 --}}
	$(document).on('click', '.cResultTourTitle', function () {

		var tourid = $(this).parent().attr('tour-id');
		var content = $(this).next();
		var me = $(this);

		if (me.attr('is-open') == 1) {
			content.hide();
			me.attr('is-open', 1 - me.attr('is-open'));
		} else if (content.attr('is-open') == 1) {
			content.show();
			me.attr('is-open', 1 - me.attr('is-open'));
		} else {
			content.show();
			me.attr('is-open', 1 - me.attr('is-open'));
			$.ajax({
				type: 'POST',
				url: "{{ url(join('/', [App::getLocale(), 'oop'])) }}" + "/" + today + "/" + ((new Date(yyyy, mm, dd)).getTime() / 1000) + "/" + tourid,
				data: {show_status: show_status},

				success: function (data) {
					content.html(data);
					content.attr('is-open', 1);
				}
			});

		}

	});

	$(document).on('click', '.cResultDirectTo', function () {
		redirectto($(this).attr('data'));
	});

	$('.cTimeSwitchDirectTo').on('click', function () {
		$(this).parent().children().removeClass('selected');
		$(this).addClass('selected');

		var data = $(this).attr('data');
		setCookie('rttype', data, '/', 2 * 365);

		var url = ["", "{{ App::getLocale() }}", data == "local" ? "result" : "oop", "{{ isset($date) ? $date : "" }}"].join("/");

		window.location.href = url;
	});

	$('#iResultSelectBar div').on('click', function () {
		var idx = $('#iResultSelectBar div').index(this);
		var width = parseInt($(this).css('width'));
		var left = 15 + (idx - 1) * width;
		$('#iResultSelectBarBg').css('left', left + 'px');
		$(this).parent().children().removeClass('selected');
		$(this).parent().children().addClass('unselected');
		$(this).addClass('selected');
		$(this).removeClass('unselected');


		var status = $(this).attr('status');
		if (status == -1) {
			$('.cResultMatch').removeClass('cResultHidden');
		} else {
			$('.cResultMatch[match-status!="' + status + '"]').addClass('cResultHidden');
			$('.cResultMatch[match-status="' + status + '"]').removeClass('cResultHidden');
		}

		show_status = status;
		return false;
	});

	$(document).on('click', '.cResultMatchOdds', function () {
        _hmt.push(['_trackCustomEvent', 'rebo_click', {'position': 'match'}]);
        open_new_window('http://95ybty.com');
    })

	{{-- 在live模式下，把没有进行比赛的赛事不显示。非live模式下初始化日期控件 --}}
	@if (isset($show_status) && $show_status == 1)
		$('.cResultTour').each(function () {

			if ($(this).find('.cResultMatch').length == $(this).find('.cResultMatch.cResultHidden').length) {
				$(this).addClass('cResultHidden');
			}
		});
	@else


		var dateSelecter = $('#iDateSelector');
		dateSelecter.append('<div class="cResultDirectTo unselected" data=' + daybeforeyesterday + '>' + daybeforeyesterday.substr(5) + '</div>');
		dateSelecter.append('<div class="cResultDirectTo unselected" data=' + yesterday + '>' + yesterday.substr(5) + '</div>');
		dateSelecter.append('<img class="cResultTourTitleArrow" src="' + "{{ url(env('CDN') . '/images/tips/live_tour_arrow.png') }}" + '" />');
		dateSelecter.append('<input class="selected cDatePicker" type=text id="iDatePicker" value="' + today + '" readonly=readonly />');
		dateSelecter.append('<div class="cResultDirectTo unselected" data=' + tomorrow + '>' + tomorrow.substr(5) + '</div>');

		pickmeup('#iDatePicker', {
			format: "Y-m-d",
			hide_on_select  : true,
			min : "2016-01-01",
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
			change: function (formatted) {
				$(this).val(formatted);
				redirectto(formatted);
			},
		});

		$('#iDatePicker')[0].addEventListener('pickmeup-change', function(e) {
			var formatted = e.detail.formatted_date;
			$(this).val(formatted);
			redirectto(formatted);
		});
	@endif

	init(today);

	if (window.innerWidth < 500) {
		$('.cResultAds728').remove();
	} else {
		$('.cResultAds300').remove();
	}
});

</script>

@php $rttype = isset($_COOKIE['rttype']) && $_COOKIE['rttype'] == "native" ? "native" : "local"; @endphp

<div class=tips style="display: none">{{ __('result.notice.nolive') }}</div>

<div id=iResult>

	@if (!isset($show_status))

		<div id="iTimeSwitchSelector" class="cDateSelector">
			<div class="unselected {{ $rttype == 'local' ? 'selected' : '' }} cTimeSwitchDirectTo" data="local">{{ __('result.option.time_switch.local') }}</div>
			<div class="unselected {{ $rttype == 'native' ? 'selected' : '' }} cTimeSwitchDirectTo" data="native">{{ __('result.option.time_switch.native') }}</div>
		</div>

		<div id="iDateSelector" class="cDateSelector">
		</div>
		<div id=iResultSelectBar>
			<div id=iResultSelectBarBg></div>
			<div class="selected" status=-1>{{ __('result.selectBar.all') }}</div>
			<div class="unselected" status=1>{{ __('result.selectBar.live') }}</div>
			<div class="unselected" status=2>{{ __('result.selectBar.completed') }}</div>
			<div class="unselected" status=0>{{ __('result.selectBar.upcoming') }}</div>
		</div>
	@endif

	<div id=iResultDetail>
		<img class=cLoading src="{{ url(env('CDN') . '/images/tips/loading-cube.svg') }}" />
	</div>
</div>
@endsection
