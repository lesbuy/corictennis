<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
@php
header("Access-Control-Allow-Origin:*");
@endphp
<html>
<head>
@php
	$x_proto = $_SERVER['HTTP_X_FORWARDED_PROTO'];
	$dmn = $_SERVER['HTTP_HOST'];
	if ($dmn == "rank-tennis.com" || $x_proto == "http") {
		header("Location: https://www.rank-tennis.com" . $_SERVER['REQUEST_URI']);
		exit;
	}
@endphp
@php
	$currentUrl = Route::current()->uri;
	$params = Route::current()->parameters;
	$currentLang = @$params['lang'];
	$currentUrl = str_replace("{lang}", "", $currentUrl);
	$currentUrl = str_replace(array_keys($params), array_values($params), $currentUrl);
	$currentUrl = preg_replace('/[\{\}]/', "", $currentUrl);

	if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'tongji.baidu.com') !== false) {
		setcookie('from', 'bd', time() + 3600 * 24 * 30, '/');
	}

	setcookie('from', 'bd', time() - 3600 * 24 * 30, '/');

@endphp

	<meta charset="utf-8">
	<link rel="shortcut icon" type="image/ico" href="{{ asset(env('CDN') . '/images/tips/newlogo.ico') }}">
@if (is_test_account())
	<meta name="viewport" content="width=device-width,user-scalable=no">
@else
	<meta name="viewport" content="width=400,user-scalable=no">
@endif
	<meta name="description" content="" />
	<meta name="renderer" content="webkit">
	<meta name="keywords" content="{{ __('frame.title.keyword') }}" />
	<meta name="_token" content="{!! csrf_token() !!}"/>
	<meta name="csrf-token" content="{{ csrf_token() }}"/>
	<meta http-equiv="Cache-Control" content="no-transform" /> 
	<meta http-equiv="Cache-Control" content="no-siteapp" /> 
	<meta name="format-detection" content="telephone=no" />
	<title>{!! isset($title) ? '&#x1f3be;' . $title . '&#x1f3be;' . ' ' : '' !!}{{ __('frame.title.root') }}</title>
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.bootstrap') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.emoji') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.load') }}">

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.base') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.frame') }}">
	@if (isset($_COOKIE['theme']) && $_COOKIE['theme'] == "dark")
		<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.dark') }}">
	@else
		<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.light') }}">
	@endif
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.base') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.frame') }}">
	@if (isset($_COOKIE['theme']) && $_COOKIE['theme'] == "dark")
		<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.dark') }}">
	@else
		<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.light') }}">
	@endif
@endif
	<style>
		@font-face {
		  font-family: 'iconfont';  /* project id 70586 */
		  src: url('//at.alicdn.com/t/font_70586_e79ph5vn8un.eot');
		  src: url('//at.alicdn.com/t/font_70586_e79ph5vn8un.eot?#iefix') format('embedded-opentype'),
		  url('//at.alicdn.com/t/font_70586_e79ph5vn8un.woff') format('woff'),
		  url('//at.alicdn.com/t/font_70586_e79ph5vn8un.ttf') format('truetype'),
		  url('//at.alicdn.com/t/font_70586_e79ph5vn8un.svg#iconfont') format('svg');
		}
	</style>


	<script type="text/javascript" language="javascript" src="//at.alicdn.com/t/font_70586_e79ph5vn8un.js"></script>
	<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.jquery') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.encode') }}"></script>
@if (is_test_account())
	<script type="text/javascript" language="javascript" src="{{ Config::get('head.test.js.base') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ Config::get('head.test.js.frame') }}"></script>
@else
	<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.base') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.frame') }}"></script>
@endif
	<script src="https://cdn.bootcss.com/jquery_lazyload/1.9.7/jquery.lazyload.min.js"></script>

<script>
	var lang = getCookie('lang', '/');
	if (lang !== null && lang != '{{ $currentLang }}') {
		var rdi = '/' + lang + '{{ $currentUrl }}';
		window.location.href = rdi;
	}

</script>

<script>
	var GLOBAL_source = '{{ Auth::check() ? get_channel_from_id(Auth::user()->method) : "未登录" }}';
	var GLOBAL_userid = {{ Auth::check() ? Auth::id() : 0 }};
	var GLOBAL_username = __f('{{ Auth::check() ? Auth::user()->oriname : "" }}');
	var GLOBAL_islogin = '{{ Auth::check() ? 1 : 2 }}';
	var GLOBAL_lang = '{{ App::getLocale() }}';
	var GLOBAL_theme = '{{ !isset($_COOKIE['theme']) ? 'light' : $_COOKIE['theme'] }}';
	var GLOBAL_pagetype1 = "{{ isset($pagetype1) && $pagetype1 !== NULL ? $pagetype1 : "none" }}";
	var GLOBAL_pagetype2 = "{{ isset($pagetype2) && $pagetype2 !== NULL ? $pagetype2 : "none" }}";
</script>

{{-- Holmes代码 --}}
<script>
	var _hmt = _hmt || [];
	_hmt.push(['_setUserTag', '17', GLOBAL_source]);
	_hmt.push(['_setUserTag', '2444', GLOBAL_userid]);
	_hmt.push(['_setUserTag', '2445', GLOBAL_username]);

	@if (Auth::check())
		_hmt.push(['_setUserId', GLOBAL_userid]);
	@endif
	_hmt.push(['_setVisitTag', '18', GLOBAL_islogin]);
	_hmt.push(['_setPageTag', '19', GLOBAL_lang]);

	_hmt.push(['_setVisitTag', '2500', GLOBAL_theme]);

	_hmt.push(['_setPageTag', '6310', GLOBAL_pagetype1]);
	_hmt.push(['_setPageTag', '6572', GLOBAL_pagetype2]);

	(function() {  var hm = document.createElement("script");  hm.src = "https://hm.baidu.com/hm.js?3b995bf0c6a621a743d0cf009eaf5c8a";  var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(hm, s);})();

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
<script data-ad-client="ca-pub-4292980114755588" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
{{-- 结束GA代码 --}}

{{-- 新GA代码 --}}
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-7GW8TTD6GW"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-7GW8TTD6GW');
</script>
{{-- 结束新GA代码 --}}

{{-- GIO代码 --}}
<script type='text/javascript'>
      var _vds = _vds || [];
      window._vds = _vds;
//			_vds.push(['setCS2', 'Username', '{{ Auth::check() ? Auth::user()->method . "`" . Auth::user()->oriname : '0' }}']);

      (function(){
//        _vds.push(['setAccountId', 'b5a4a4e8c14b687f']);
        (function() {
          var vds = document.createElement('script');
          vds.type='text/javascript';
          vds.async = true;
          vds.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'dn-growing.qbox.me/vds.js';
          var s = document.getElementsByTagName('script')[0];
//          s.parentNode.insertBefore(vds, s);
        })();
      })();
  </script>
{{-- 结束GIO代码 --}}

<!-- Google Tag Manager
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-WRKV5HC');</script>
 End Google Tag Manager -->

{{-- 站长平台 --}}
<script>
(function(){
    var bp = document.createElement('script');
    var curProtocol = window.location.protocol.split(':')[0];
    if (curProtocol === 'https') {
        bp.src = 'https://zz.bdstatic.com/linksubmit/push.js';
    }
    else {
        bp.src = 'http://push.zhanzhang.baidu.com/push.js';
    }
    var s = document.getElementsByTagName("script")[0];
    s.parentNode.insertBefore(bp, s);
})();
</script>
{{-- 结束站长平台 --}}

</head>

@php $theme = isset($_COOKIE['theme']) && $_COOKIE['theme'] == "dark" ? "dark" : "light"; @endphp
@php $rttype = isset($_COOKIE['rttype']) && $_COOKIE['rttype'] == "native" ? "native" : "local"; @endphp

<body {{ !isset($_COOKIE['theme']) ? "" : "class=" . $_COOKIE['theme'] }}>
<script type="text/javascript" language="javascript" class="init"> 

	var device = window.orientation === undefined || window.orientation !== 0 ? 0 : 1;
	var em = $('body').css('font-size').replace("px", "");

	uuid = null;
	make_tip('img.cImgPlayerFlag', 'flag');
	make_tip('pname', 'pname');
	make_tip('div.cPlayerFlag', 'data-ioc');

	var theme = '{{ isset($_COOKIE['theme']) && $_COOKIE['theme'] == "dark" ? "dark" : "light" }}';

	window.passUuid = function (id) {
		uuid = id;
		console.log(uuid);
	};

	$(function () {

		if (navigator.userAgent.indexOf('Chrome') != -1 && window.orientation === undefined && $('html').css('font-size') == "12px") {
			$('#iMask').fadeIn(500).css('display', '-webkit-flex');
			$('#iMask').html('<div id=iAjaxNotice>' + "{!! __('frame.notice.chrome_mininum_fontsize_not_compatible') !!}" + '</div>');
		}

		// 菜单里的签表点击后加载
//		$('#iMenuForDraw').on('click', function () {
			var div = $('#iMenuDraw');
//			if (div.html() == "") {
				div.html("<img class=cLoading src=\"{{ url(env('CDN') . '/images/tips/loading-cube.svg') }}\" />");
				$.ajax({
					url: "{{ url(join("/", [App::getLocale(), "draw", "list"])) }}",
					type: "GET",
					success: function (data) {
						div.html(data);
					}
				})
//			} else if (div.html().match(/Loading/)) {
//				div.html("");
//			}
//		});

		// 意见建议按钮
		$('#iButtonMsg').on('click', function () {
			$('#iMask').fadeIn(500).css('display', '-webkit-flex');
			$('#iMask').html('<div id=iAjaxNotice>' + '{{ __('frame.notice.gfw') }}'  + '</div>');
			$.ajax({
				type: 'GET',
				url: "{{ url(App::getLocale() . "/msgboard") }}",
				success: function (data) {
					$('#iMask').html(data);
				}
			});
		});

		// 读取后台消息
		if (getCookie('msg_read') === null) {
			$.ajax({
				type: 'GET',
				url: "{{ url(App::getLocale() . "/msgboard/show") }}",
				success: function (data) {
					if (data) {
						$('#iMask').fadeIn(500).css('display', '-webkit-flex');
						$('#iMask').html(data);
					}
					setCookie('msg_read', 1, '/', 0.3);
				},
			});
		}

		// 底部菜单链接
		$('#iFooterRules a').on('click', function () {
			var type = $(this).attr('type');
			var link = $(this).attr('link');
			if (/^\/images\//.test(link)) {
				link = "{{ url('/') }}" + link;
			} else if (/^\//.test(link)) {
				link = "{{ url(App::getLocale()) }}" + link;
			}

			if (type == "popup") {
				$('#iMask').fadeIn(500).css('display', '-webkit-flex');
				$('#iMask').html('<div id=iAjaxNotice>' + '{{ __('frame.notice.gfw') }}'  + '</div>');
				$.ajax({
					type: 'GET',
					url: link,
					success: function (data) {
						$('#iMask').html(data);
					}
				});
			} else if (type == "refer") {
				window.location.href = link;
			}
		});

		// 广告点击监测
		$('.tn-ad1').on('click', function () {
			_hmt.push(['_trackCustomEvent', 'ad_click', {'position':'left'}]);
		});
		$('.tn-ad2').on('click', function () {
			_hmt.push(['_trackCustomEvent', 'ad_click', {'position':'top'}]);
		});
		$('.tn-ad3').on('click', function () {
			_hmt.push(['_trackCustomEvent', 'ad_click', {'position':'bottom'}]);
		});
		$('.tn-rebo').on('click', function () {
			_hmt.push(['_trackCustomEvent', 'rebo_click', {'position': 'banner'}]);
			open_new_window('http://ya8.in');
		});

		$("img.lazyload").lazyload({
			placeholder: '{{ url('/images/tips/rank-tennis.svg') }}'
		});

		$("img.cImgPlayerFlag").lazyload();

	});

	function open_new_window(url) {
		var tempwindow = window.open('_blank');
		tempwindow.location = url;
	};

</script>
<div>
<!-- 顶部导航栏开始 -->
<div id="top-nav">
	<div class="tn-user" id="open_menu"><a>{!! get_icon('caidan-copy') !!}<div>{{ __('frame.menu.menu') }}</div></a></div>
	<div class="tn-user"><a target=_self href="{{ url(App::getLocale()) }}"><img id=iImgTopLogo src="{{ url('/images/tips/coric-top-logo.svg') }}" /></a></div>
<!--	<div class="tn-user"><a target=_self href="/bbs/">{!! get_icon('liuyan') !!}<div>{{ __('frame.menu.forum') }}</div></a></div>-->
	<div class="tn-user"><a target=_self href="{{ url(App::getLocale() . '/live') }}">{!! get_icon('live') !!}<div>{{ __('frame.menu.live') }}</div></a></div>
<!--	<div class="tn-user"><a target=_self><i class="iconfont">&#xe615;</i><div>APK</div></a></div>-->
	<div class="tn-lang">
		{!! get_icon('yuyan') !!}
		<div class='cFrameLangSelect' id='iFrameLangSelect'>
			@foreach (array_keys(Config::get('const.translate')) as $lang)
				<div class='cFrameLangOption' href='{{ url($lang . $currentUrl)  }}' data-lang='{{ $lang }}'>
					{{ Config::get('const.translate.' . $lang) }}
				</div>
			@endforeach
		</div>
	</div>
	<div class="tn-lang">
		{!! get_icon('theme') !!}
		<div class='cFrameLangSelect' id='iFrameThemeSelect'>
			<div class='cFrameLangOption' data-id='light'>{{ __('frame.theme.light') }}</div>
			<div class='cFrameLangOption' data-id='dark'>{{ __('frame.theme.dark') }}</div>
		</div>
	</div>
</div>
<!-- 顶部导航栏结束 -->

<!-- 中部栏开始 -->
<div id="top-container">

	<!-- 左部菜单栏开始 -->
	<div id="top-menu">
		<div id="C_login" class="hastitle">
			@auth
				<blockTitle class="hastitle_title">
					<a data-role=none id="menu_text_logout" onclick="event.preventDefault();document.getElementById('logout-form').submit();" href="{{ route('logout') }}">{!! get_icon('zhuxiao') !!}&nbsp;{{ __('frame.menu.logout') }}</a>
					<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">{{ csrf_field() }}</form>
				</blockTitle>
				<div id="top-menu-portrait">
					<img src="{{ Auth::user()->bigavatar }}" />
				</div>
				<a data-role=none id="menu_text_usertype">{!! get_icon(Config::get('const.TYPE2ICONNAME.' . Auth::user()->method)) !!}</a>
				<a data-role=none id="menu_text_username">{{ Auth::user()->oriname }}</a>
			@else
				<blockTitle class="hastitle_title">{!! get_icon('denglu') !!}&nbsp;{{ __('frame.menu.login') }}</blockTitle>
				<a data-role=none href="{{ url('login/baidu') }}">{!! get_icon(Config::get('const.TYPE2ICONNAME.0')) !!}</a>
				<a data-role=none href="{{ url('login/weibo') }}">{!! get_icon(Config::get('const.TYPE2ICONNAME.1')) !!}</a>
				<a data-role=none href="{{ url('login/facebook') }}">{!! get_icon(Config::get('const.TYPE2ICONNAME.7')) !!}</a>
				<a data-role=none href="{{ url('login/google') }}">{!! get_icon(Config::get('const.TYPE2ICONNAME.8')) !!}</a>
			@endauth
		</div>
		<ul id="menu-table">
			<li>
				<a data-role=none href="{{ url(App::getLocale() . '/home') }}">{{ __('frame.menu.home') }}</a>
			</li>
			<li>
				<a data-role=none>{{ __('frame.menu.rank') }}</a>
				<ul>
					<li><a data-role=none href="{{ url(App::getLocale() . '/rank/atp/s/year') }}">{{ __('frame.menu.atp_s_year') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/rank/wta/s/year') }}">{{ __('frame.menu.wta_s_year') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/rank/atp/d/year') }}">{{ __('frame.menu.atp_d_year') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/rank/wta/d/year') }}">{{ __('frame.menu.wta_d_year') }}</a></li>
<!--
					<li><a data-role=none href="{{ url(App::getLocale() . '/rank/atp/s/race') }}">{{ __('frame.menu.atp_s_race') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/rank/wta/s/race') }}">{{ __('frame.menu.wta_s_race') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/rank/atp/s/nextgen') }}">{{ __('frame.menu.atp_s_nextgen') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/rank/custom/') }}">{{ __('frame.menu.custom') }}</a></li>
-->
				</ul>
			</li>
			<li>
				<a data-role=none href="{{ url(App::getLocale() . ($rttype == 'local' ? '/result' : '/oop')) }}">{{ __('frame.menu.score') }}</a>
			</li>
			<li>
				<a data-role=none href="{{ url(App::getLocale() . '/calendar/2021') }}">{{ __('frame.menu.calendar') }}</a>
			</li>
			<li>
				<a data-role=none id="iMenuForDraw">{{ __('frame.menu.draw') }}</a>
				<ul id=iMenuDraw></ul>
			</li>
			<li>
				<a data-role=none>{{ __('frame.menu.dc') }}</a>
				<ul>
					<li><a data-role=none href="{{ url(App::getLocale() . '/dc/RG/2020/MS') }}">{{ "ATP " . translate_tour('Roland Garros') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/dc/RG/2020/WS') }}">{{ "WTA " . translate_tour('Roland Garros') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/dc/UO/2020/MS') }}">{{ "ATP " . translate_tour('US Open') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/dc/UO/2020/WS') }}">{{ "WTA " . translate_tour('US Open') }}</a></li>
				</ul>
			</li>
			<li>
				<a data-role=none href="{{ url(App::getLocale() . '/h2h') }}">{{ __('frame.menu.h2h') }}</a>
			</li>
			<li>
				<a data-role=none>{{ __('frame.menu.guess.game') }}</a>
				<ul>
					<li><a data-role=none href="{{ url(App::getLocale() . '/guess') }}">{{ __('frame.menu.guess.pick') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/guess/calendar/2020') }}">{{ __('frame.menu.guess.schedule') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/guess/rule') }}">{{ __('frame.menu.guess.rule') }}</a></li>
					<li>
						<a data-role=none>{{ __('frame.menu.guess.itgl.race') }}</a>
						<ul>
							<li><a data-role=none href="{{ url(App::getLocale() . '/guess/rank/itgl/year/0') }}">{{ __('frame.menu.guess.itgl.year') }}</a></li>
							<li><a data-role=none href="{{ url(App::getLocale() . '/guess/rank/itgl/day') }}">{{ __('frame.menu.guess.itgl.day') }}</a></li>
							<li><a data-role=none href="{{ url(App::getLocale() . '/guess/rank/itgl/week') }}">{{ __('frame.menu.guess.itgl.week') }}</a></li>
							<li><a data-role=none href="{{ url(App::getLocale() . '/guess/rank/itgl/all/0') }}">{{ __('frame.menu.guess.itgl.all') }}</a></li>
						</ul>
					</li>
					<li>
						<a data-role=none>{{ __('frame.menu.guess.dcpk.race') }}</a>
						<ul>
							<li><a data-role=none href="{{ url(App::getLocale() . '/guess/rank/dcpk/year/0') }}">{{ __('frame.menu.guess.dcpk.year') }}</a></li>
							<li><a data-role=none href="{{ url(App::getLocale() . '/draw/D46/2019') }}">{{ translate_tour('London')." ".__('frame.menu.draw') }}</a></li>
							<li><a data-role=none href="{{ url(App::getLocale() . '/draw/D44/2019') }}">{{ translate_tour('Shenzhen')." ".__('frame.menu.draw') }}</a></li>
							<li><a data-role=none href="{{ url(App::getLocale() . '/guess/sign') }}">{{ __('frame.menu.guess.dcpk.sign') }}</a></li>
						</ul>
					</li>
				</ul>
			</li>
			<li>
				<a data-role=none>{{ __('frame.menu.entrylist') }}</a>
				<ul>
					<li><a data-role=none href="{{ url(App::getLocale() . '/entrylist/atp') }}">ATP</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/entrylist/wta') }}">WTA</a></li>
				</ul>
			</li>
			<li>
				<a data-role=none href="{{ url(App::getLocale() . '/history/activity') }}">{{ __('frame.menu.activity') }}</a>
			</li>
			<li>
				<a data-role=none href="{{ url(App::getLocale() . '/history/gst1') }}">{{ __('frame.menu.tourquery') }}</a>
			</li>
			<li>
				<a data-role=none>{{ __('frame.menu.historyRank') }}</a>
				<ul>
					<li><a data-role=none href="{{ url(App::getLocale() . '/history/official') }}">{{ __('frame.menu.officialRank') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/history/evolv') }}">{{ __('frame.menu.rankEvolution') }}</a></li>
				</ul>
			</li>
			<li>
				<a data-role=none href="{{ url(App::getLocale() . '/history/topn') }}">{{ __('frame.menu.topN') }}</a>
			</li>
			<li>
				<a data-role=none>{{ __('frame.menu.profile') }}</a>
				<ul>
					<li><a data-role=none href="{{ url(App::getLocale() . '/profile/atp') }}">ATP</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/profile/wta') }}">WTA</a></li>
				</ul>
			</li>
<!--
			<li>
				<a data-role=none href="{{ url(App::getLocale() . '/work-as-one') }}">{{ __('frame.menu.assistTrans') }}</a>
			</li>
-->
		</ul>
		@if ((isset($_COOKIE['from']) && $_COOKIE['from'] == "bd") || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'tongji.baidu.com') !== false))
			<div class="C_nobg">
				<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
				<ins class="adsbygoogle tn-ad1"
					 style="display:inline-block;width:200px;height:200px;border:1px solid #d3e0e9;"
					 data-ad-client="ca-pub-4292980114755588"
					 data-ad-slot="6730377083"></ins>
				<script>
				(adsbygoogle = window.adsbygoogle || []).push({});
				</script>
			</div>
		@else
			<div class="C_nobg">
				<a href="http://95ybty.com" target="_blank"><img class="lazyload" style="width:100%" data-original="{{ url('/images/tips/yabo-left-200.png') }}" /></a>
			</div>
		@endif

		<div class="C_nobg">
			<div id="iNoticeTW">{{ __('frame.notice.tw') }}</div>
			@if (!isset($_COOKIE['theme']) || $_COOKIE['theme'] == "light")
				<a data-role=none class="pv_counter" href="https://info.flagcounter.com/0DgQ"><img src="https://s07.flagcounter.com/count2/0DgQ/bg_FFFFFF/txt_777777/border_D3E0E9/columns_2/maxflags_30/viewers_Visitors/labels_1/pageviews_1/flags_0/percent_0/" alt="Flag Counter" border="0" width=200></a>
			@else
				<a data-role=none class="pv_counter" href="https://info.flagcounter.com/0DgQ"><img src="https://s07.flagcounter.com/count2/0DgQ/bg_4C4C4C/txt_FFFFFF/border_CCCCCC/columns_2/maxflags_30/viewers_Visitors/labels_1/pageviews_1/flags_0/percent_0/" alt="Flag Counter" border="0"></a>
			@endif
		</div>
<!--
		<div class="C_nobg">
			@if (!isset($_COOKIE['theme']) || $_COOKIE['theme'] == "light")
				<script type="text/javascript" src="http://feedjit.com/serve/?vv=1515&amp;tft=3&amp;dd=0&amp;wid=&amp;pid=0&amp;proid=0&amp;bc=FFFFFF&amp;tc=545454&amp;brd1=CCCCCC&amp;lnk=6AA5C4&amp;hc=878787&amp;hfc=FFFFFF&amp;btn=62BBDE&amp;ww=200&amp;went=10"></script><noscript><a href="http://feedjit.com/">Live Traffic Stats</a></noscript>
			@else
				<script type="text/javascript" src="http://feedjit.com/serve/?vv=1515&amp;tft=3&amp;dd=0&amp;wid=&amp;pid=0&amp;proid=0&amp;bc=4C4C4C&amp;tc=FFFFFF&amp;brd1=012B6B&amp;lnk=EEEEEE&amp;hc=FFFFFF&amp;hfc=0764B0&amp;btn=C99700&amp;ww=200&amp;wne=10&amp;srefs=0"></script>
				<noscript><a data-role=none href="http://feedjit.com/">Live Traffic Stats</a></noscript>
			@endif
		</div>
-->
<!--
		<div class="C_nobg">
			@if (!isset($_COOKIE['theme']) || $_COOKIE['theme'] == "light")
				 <center><script>var color='red';var nim_border_r=false;var nim_width=120;var nim_bgcolor='DEDEDE';var nim_border_c=false;var nim_color='919191';var nim_online_size=false;var nim_counter_size=false;var nim_counter_h_size=false;var nim_count=false;</script> <script type='text/javascript' src='https://alivestats.com/widget.js'></script> <br><a href='https://alivestats.com' target='_blank' title='Live Traffic Feed'>Live Traffic Feed</a></center>
				<center><script>var color='CCCCCC';var l=10;var w=200;</script><script type='text/javascript' src='https://alivestats.com/feed/widget.js'></script><br><a href='https://alivestats.com' target='_blank' title='Live Traffic Feed'>Live Traffic Feed</a></center>
			@else
				 <center><script>var color='red';var nim_border_r=false;var nim_width=120;var nim_bgcolor='636B6F';var nim_border_c=false;var nim_color='F5F5F5';var nim_online_size=false;var nim_counter_size=false;var nim_counter_h_size=false;var nim_count=false;</script> <script type='text/javascript' src='https://alivestats.com/widget.js'></script> <br><a href='https://alivestats.com' target='_blank' title='Live Traffic Feed'>Live Traffic Feed</a></center>
				<center><script>var color='636b6f';var l=10;var w=200;</script><script type='text/javascript' src='https://alivestats.com/feed/widget.js'></script><br><a href='https://alivestats.com' target='_blank' title='Live Traffic Feed'>Live Traffic Feed</a></center>
			@endif
		</div>
-->
	</div>
	<!-- 左部菜单栏结束 -->
	<!-- 遮罩层开始 -->
	<div id="menu_mask"></div>
	<!-- 遮罩层结束 -->
	<!-- 右部内容栏开始 -->
	<div id="top-right">
		@if (isset($pageTitle))
			<div class=pageTitle>{{ $pageTitle }}</div>
		@endif

		@if (1 || (isset($_COOKIE['from']) && $_COOKIE['from'] == "bd") || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'tongji.baidu.com') !== false))
			<div id="tn-ad2">
				<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
				<ins class="adsbygoogle tn-ad2"
					 style="display:block"
					 data-ad-client="ca-pub-4292980114755588"
					 data-ad-slot="1371167482"
					 data-ad-format="auto"></ins>
				<script>
						(adsbygoogle = window.adsbygoogle || []).push({});
				</script>
			</div>
		@else
			<div id="tn-ad2">
				<a href="http://95ybty.com" target="_blank"><img class="lazyload" style="width:100%" data-original="{{ url('/images/tips/yabo-top-1560.png') }}" /></a>
			</div>
		@endif
		@yield('content')
<!--		<img class="tn-rebo" src="{{ url('/images/tips/rebo.jpg') }}" />-->
		@if (1 || (isset($_COOKIE['from']) && $_COOKIE['from'] == "bd") || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'tongji.baidu.com') !== false))
			<div id="tn-ad3">
				<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
				<ins class="adsbygoogle tn-ad3"
					 style="display:block"
					 data-ad-client="ca-pub-4292980114755588"
					 data-ad-slot="7042597884"
					 data-ad-format="auto"></ins>
				<script>
						(adsbygoogle = window.adsbygoogle || []).push({});
				</script>
			</div>
		@else
			<div id="tn-ad3" style="height: auto;">
				<a href="http://95ybty.com" target="_blank"><img class="lazyload" style="width:100%" data-original="{{ url('/images/tips/yabo-top-970.png') }}" /></a>
			</div>
		@endif
	</div>
	<!-- 右部内容栏结束 -->
</div>
<!-- 中部栏结束 -->

</div>
<div id="top-footer">
	<div id="iFooterRules">
		<table><tbody><tr>
			<td>
				<div>{{ __('help.footer.menu.intro') }}</div>
				<div>{{ __('help.footer.menu.about') }}</div>
				<div><a link="/msgboard" type=popup>{{ __('help.footer.menu.bugreport') }}</a></div>
				<div><a link="https://weibo.com/liu1995910/" type=refer>{{ __('help.footer.menu.ui_designer') }}</a></div>
			</td>
			<td>
				<div>{{ __('help.footer.menu.rule') }}</div>
				<div><a link="/help/translation/name" type=popup>{{ __('help.footer.menu.translate') }}</a></div>
				<div>{{ __('help.footer.menu.dc') }}</div>
				<div><a link="/help/rule/dcpk" type=popup>{{ __('help.footer.menu.dcpk') }}</a></div>
				<div></div>
				<div></div>
			</td>
			<td>
				<div>{{ __('help.footer.menu.link') }}</div>
				<div><a link="https://www.atpworldtour.com/" type=refer>{{ __('help.footer.menu.atp') }}</a></div>
				<div><a link="http://www.wtatennis.com/" type=refer>{{ __('help.footer.menu.wta') }}</a></div>
				<div><a link="https://www.itftennis.com/" type=refer>{{ __('help.footer.menu.itf') }}</a></div>
				<div><a link="http://www.protennislive.com/LSHD/main.html?year=2019" type=refer>{{ __('help.footer.menu.atpwtalive') }}</a></div>
				<div><a link="https://www.tennisforum.com/" type=refer>{{ __('help.footer.menu.wtf') }}</a></div>
				<div><a link="https://www.menstennisforums.com/" type=refer>{{ __('help.footer.menu.tf') }}</a></div>
				<div></div>
				<div></div>
			</td>
			<td>
				<div>{{ __('help.footer.menu.social') }}</div>
				<div><a link="https://weibo.com/GoCoric" type=refer>{{ __('help.footer.menu.weibo') }}</a></div>
				<div><a link="/images/tips/2_dim_code.png" type=refer>{{ __('help.footer.menu.weixin') }}</a></div>
				<div><a link="http://tieba.baidu.com/home/main?un=%E7%BE%8E%E7%BD%91%E5%86%A0%E5%86%9B%E4%B8%98%E9%87%8C%E5%A5%87" type=refer>{{ __('help.footer.menu.tieba') }}</a></div>
<!--				<div><a link="http://coric.blog" type=refer>{{ __('help.footer.menu.blog') }}</a></div>-->
			</td>
		</tr></tbody></table>
	</div>
	<div>
		{!! __('frame.menu.footer') !!} 2014-2019 <span id=iButtonMsg><div>{!! get_icon('yijianfankui') !!}</div>{!! __('help.msgboard.leave') !!}</span>
	</div>
</div>
<div id=iMask>
</div>
<div id=cty_tip>
</div>
</body>

<iframe id="uuid" src="" style="display: none;"></iframe>
</html>
