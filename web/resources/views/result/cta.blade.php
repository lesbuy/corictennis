<html>
<head>
	<meta charset="utf-8">
	<link rel="shortcut icon" type="image/ico" href="{{ asset(env('CDN') . '/images/tips/logo.ico') }}">
	<meta name="viewport" content="width=400,user-scalable=no">
	<meta name="description" content="" />
	<meta name="renderer" content="webkit">
	<meta name="_token" content="{!! csrf_token() !!}"/>
	<meta name="csrf-token" content="{{ csrf_token() }}"/>
	<meta http-equiv="Cache-Control" content="no-transform" /> 
	<meta http-equiv="Cache-Control" content="no-siteapp" /> 
	<meta name="format-detection" content="telephone=no" />
	<title>比分/赛程/赛果</title>
	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/base.css?v=1.0.5') }}">
	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/frame.css') }}">
	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/emoji.css') }}">
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/jquery.min.js') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/bootstrap.min.js') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/base.js') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/encode.js') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/frame.js') }}"></script>

	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/cta.css?v=1.0.7') }}">
	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/pickmeup.css') }}">
	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/h2hDetail.css') }}">
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/result.js?v=1.0.1') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/echarts.common.min.js') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/pickmeup.js') }}"></script>

	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/light.css') }}">

{{-- Holmes代码 --}}
<script>
	var _hmt = _hmt || [];
	_hmt.push(['_setUserTag', '17', '{{ Auth::check() ? get_channel_from_id(Auth::user()->method) : "未登录" }}']);
	@if (Auth::check())
		_hmt.push(['_setUserTag', '2444', {{ Auth::id() }}]);
		_hmt.push(['_setUserTag', '2445', __f('{{ Auth::user()->oriname }}')]);
	@endif
	_hmt.push(['_setVisitTag', '18', '{{ Auth::check() ? 1 : 2 }}']);
	_hmt.push(['_setPageTag', '19', '{{ App::getLocale() }}']);

	var pagetype1 = "{{ isset($pagetype1) && $pagetype1 !== NULL ? $pagetype1 : "none" }}";
	var pagetype2 = "{{ isset($pagetype2) && $pagetype2 !== NULL ? $pagetype2 : "none" }}";
	_hmt.push(['_setPageTag', '6310', pagetype1]);
	_hmt.push(['_setPageTag', '6572', pagetype2]);

	(function() {  var hm = document.createElement("script");  hm.src = "//hm.baidu.com/hm.js?3b995bf0c6a621a743d0cf009eaf5c8a";  var s = document.getElementsByTagName("script")[0];   s.parentNode.insertBefore(hm, s);})();
</script>
{{-- 结束Holmes代码 --}}

{{-- GA代码 --}}
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	ga('create', 'UA-72796132-1', 'auto');
	ga('set', 'dimension3', '{{ Auth::check() ? Auth::user()->method . "`" . Auth::user()->oriname : '0' }}');
	ga('send', 'pageview');

</script>
{{-- 结束GA代码 --}}

{{-- GIO代码 --}}
<script type='text/javascript'>
      var _vds = _vds || [];
      window._vds = _vds;
			_vds.push(['setCS2', 'Username', '{{ Auth::check() ? Auth::user()->method . "`" . Auth::user()->oriname : '0' }}']);

      (function(){
        _vds.push(['setAccountId', 'b5a4a4e8c14b687f']);
        (function() {
          var vds = document.createElement('script');
          vds.type='text/javascript';
          vds.async = true;
          vds.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'dn-growing.qbox.me/vds.js';
          var s = document.getElementsByTagName('script')[0];
          s.parentNode.insertBefore(vds, s);
        })();
      })();
  </script>
{{-- 结束GIO代码 --}}

</head>
<body>

<div id="top-container">

<!--
<script type="text/javascript" language="javascript" class="init">

	var client = new WebSocket('ws://13.112.53.53:8080/', 'coric-live');
	var cron_stop = false;

	client.onerror = function() {
		console.log('Connection Error');
	};

	client.onopen = function() {
		console.log('WebSocket Client for Live Score Connected');

		function sendIdentity() {
			if (client.readyState === client.OPEN) {
				var identity = {id: 0, usertype: -1, username: ''};
				@auth
					identity.id = '{{ Auth::id() }}';
					identity.usertype = '{{ Auth::user()->method }}';
					identity.username = '{{ Auth::user()->oriname }}';
				@endauth
				identity.lang = '{{ Route::current()->parameters()['lang'] }}';
				client.send(JSON.stringify(identity));
			}
		}
	    sendIdentity();

		function sendHeartbeat() {
			var identity = {heartbeat: true};
			client.send(JSON.stringify(identity));
			if (!cron_stop) 
				setTimeout(sendHeartbeat, 120000);
		}
		sendHeartbeat();
	};

	client.onclose = function() {
		console.log('Client Closed');
	};

	client.onmessage = function(e) {
		if (typeof e.data === 'string') {
//			console.log("Received: '" + e.data + "'");
			if (e.data != "stop") {
				update_data(e.data);
			} else {
				cron_stop = true;
				$('#iMask').fadeIn(500).css('display', '-webkit-flex');
				$('#iMask').html('<div id=iAjaxNotice>' + '{{ __('frame.notice.nomorelive') }}'  + '</div>');
			}
		}
	};

</script>
-->

<script type="text/javascript" language="javascript" class="init">

var show_status;
@if (isset($show_status) && $show_status == 1)
	show_status = 1;
@else
	show_status = -1;
@endif

var live_timer;

entrytime = {{ $now }};
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

			matchBlock.find('.cResultMatchDura').html(dura);
			matchBlock.find('.cResultMatchMidPointFlag').html(pointflag);

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
	window.location.href = "{{ url(App::getLocale() . "/ctalive") }}" + "/" + day;
};

$(function() {

	{{-- 在时间范围内才刷新 --}}
	var ts = parseInt(new Date() / 1000);
	if (ts >= {{ $timestamp[0] }} && ts <= {{ $timestamp[1] }}) {
		live_timmer = setInterval(live_update, 10000);
	}

	{{-- 点击title则打开或关闭下面的内容。或下面已加载过则不再加载，否则从服务器加载 --}}
	$('.cResultTourTitle').on('click', function () {

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
				url: "{{ url(join('/', [App::getLocale(), 'result', $date])) }}",
				data: {eid: tourid, show_status: show_status},

				success: function (data) {
					content.html(data);
					content.attr('is-open', 1);
				}
			});

		}

	});

	$('.cResultDirectTo').on('click', function () {
		init($(this).attr('data'));
	});

	$('#iMask').on('click', function () {
		$(this).fadeOut(500, function () {
			$(this).html("");
		})
	});

	$('#iResultSelectBar div').on('click', function () {
		var idx = $('#iResultSelectBar div').index(this);
		var width = parseInt($(this).css('width'));
		var left = 15 + (idx - 1) * width;
		$('#iResultSelectBarBg').css('left', left + 'px');

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

	{{-- 在live模式下，把没有进行比赛的赛事不显示。非live模式下初始化日期控件 --}}
	@if (isset($show_status) && $show_status == 1)
		$('.cResultTour').each(function () {

			if ($(this).find('.cResultMatch').length == $(this).find('.cResultMatch.cResultHidden').length) {
				$(this).addClass('cResultHidden');
			}
		});
	@else

		pickmeup('#iDatePicker', {
			format: "Y-m-d",
			hide_on_select  : true,
			min : "2016-01-01",
			max : GetDateStr(1),
			prev: '<<',
			next: '>>',
			default_date: false,
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
				init(formatted);
			},
		});

		$('#iDatePicker')[0].addEventListener('pickmeup-change', function(e) {
			var formatted = e.detail.formatted_date;
			$(this).val(formatted);
			init(formatted);
		});
	@endif

});

</script>

<div class=tips style="display: none">{{ __('result.notice.nolive') }}</div>

<div id=iResult>

	@if (!isset($show_status))
		<div id="iDateSelector">
			<input class="cDatePicker" type=text id="iDatePicker" value="{{ $date }}" readonly=readonly />
			<img class="cResultTourTitleArrow" src="{{ url(env('CDN') . '/images/tips/live_tour_arrow.png') }}" />
			<div class="cResultDirectTo" data="{{ date('Y-m-d', strtotime($date . "-2 days")) }}">{{ date('m-d', strtotime($date . "-2 days")) }}</div>
			<div class="cResultDirectTo" data="{{ date('Y-m-d', strtotime($date . "-1 days")) }}">{{ date('m-d', strtotime($date . "-1 days")) }}</div>
			<div class="cResultDirectTo" data="{{ date('Y-m-d', strtotime($date . "+1 days")) }}">{{ date('m-d', strtotime($date . "+1 days")) }}</div>
		</div>
		<div id=iResultSelectBar>
			<div id=iResultSelectBarBg></div>
			<div status=-1>{{ __('result.selectBar.all') }}</div>
			<div status=1>{{ __('result.selectBar.live') }}</div>
			<div status=2>{{ __('result.selectBar.completed') }}</div>
			<div status=0>{{ __('result.selectBar.upcoming') }}</div>
		</div>
	@endif

	@foreach ($ret as $tour)

		<div class=cResultTour id="iResult{{ $tour[0] }}" tour-id="{{ $tour[0] }}">
			<div class=cResultTourTitle style="background-color: {{ $tour[2] }}" is-open={{ $tour[3] }}>
				<img class=cResultTourTitleArrow src="{{ url(env('CDN') . '/images/tips/live_tour_arrow.png') }}" />
				@foreach ($tour[5] as $logo)
					<img src="{{ $logo }}" />
				@endforeach
				<div class=cResultTourTitleInfo>
					<div class=cResultTourInfoCity>{{ $tour[1] }}</div>
					<div class=cResultTourInfoName>{{ $tour[4] }}</div>
				</div>
			</div>
			<div class=cResultTourContent is-open={{ $tour[3] }}>
				@if ($tour[3] == 1)
					@include('result.content')
				@else
					<img class=cLoading src="{{ url(env('CDN') . '/images/tips/loading-cube.svg') }}" />
				@endif
			</div>
		</div>

	@endforeach

</div>
</div>

<div id=iMask>
</div>
<div id=cty_tip>
</div>


</body>
</html>
