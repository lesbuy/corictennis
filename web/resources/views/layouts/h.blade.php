<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
@php
header("Access-Control-Allow-Origin:*");
@endphp
<html>
<head>
@php
	$currentUrl = Route::current()->uri;
	$params = Route::current()->parameters;
	$currentLang = @$params['lang'];
	$currentUrl = str_replace("{lang}", "", $currentUrl);
	$currentUrl = str_replace(array_keys($params), array_values($params), $currentUrl);
	$currentUrl = preg_replace('/[\{\}]/', "", $currentUrl);
@endphp

	<meta charset="utf-8">
	<link rel="shortcut icon" type="image/ico" href="{{ asset(env('CDN') . '/images/tips/logo.ico') }}">
	<meta name="viewport" content="width=400,user-scalable=no">
	<meta name="description" content="" />
	<meta name="renderer" content="webkit">
	<meta name="keywords" content="{{ __('frame.title.keyword') }}" />
	<meta name="_token" content="{!! csrf_token() !!}"/>
	<meta name="csrf-token" content="{{ csrf_token() }}"/>
	<meta http-equiv="Cache-Control" content="no-transform" /> 
	<meta http-equiv="Cache-Control" content="no-siteapp" /> 
	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/bootstrap.min.css') }}">
	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/base.css?v=1.0.5') }}">
	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/frame.css?v=1.0.2') }}">
	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/dark.css?v=1.0.6') }}">
	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/emoji.css') }}">
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/jquery.min.js') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/base.js') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/encode.js') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/tmp.js?v=1.0.5') }}"></script>

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

	@if (!isset($_COOKIE['theme']))
		_hmt.push(['_setVisitTag', '2500', 'light']);
	@else
		_hmt.push(['_setVisitTag', '2500', '{{ $_COOKIE['theme'] }}']);
	@endif

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
<body {{ !isset($_COOKIE['theme']) ? "" : "class=" . $_COOKIE['theme'] }}>
<script type="text/javascript" language="javascript" class="init"> 

	uuid = null;
	make_tip('img.cImgPlayerFlag', 'flag');
	make_tip('pname', 'pname');

	window.passUuid = function (id) {
		uuid = id;
		console.log(uuid);
	};

	$(function () {

		// 菜单里的签表点击后加载
		$('#iMenuForDraw').on('click', function () {
			var div = $('#iMenuDraw');
			if (div.html() == "") {
				div.html("<img class=cLoading src=\"{{ url(env('CDN') . '/images/tips/loading-cube.svg') }}\" />");
				$.ajax({
					url: "{{ url(join("/", [App::getLocale(), "draw", "list"])) }}",
					type: "GET",
					success: function (data) {
						div.html(data);
					}
				})
			} else if (div.html().match(/Loading/)) {
				div.html("");
			}
		});

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

	});

</script>
<div>
<!-- 顶部导航栏开始 -->
<div id="top-nav">
	<div class="tn-user" id="open_menu"><a><i class="iconfont">&#xe614;</i><div>{{ __('frame.menu.menu') }}</div></a></div>
	<div class="tn-user"><a target=_self href="{{ url(App::getLocale()) }}"><img id=iImgTopLogo src="{{ url('/images/tips/coric-top-logo.svg') }}" /></a></div>
	<div class="tn-user"><a target=_self href="/bbs/"><i class="iconfont">&#xe613;</i><div>{{ __('frame.menu.forum') }}</div></a></div>
	<div class="tn-user"><a target=_self href="{{ url(App::getLocale() . '/live') }}"><i class="iconfont">&#xe61c;</i><div>{{ __('frame.menu.live') }}</div></a></div>
<!--	<div class="tn-user"><a target=_self><i class="iconfont">&#xe615;</i><div>APK</div></a></div>-->
	<div class="tn-lang">
		<i class="iconfont">&#xe61f;</i>
		<div class='cFrameLangSelect' id='iFrameLangSelect'>
			@foreach (array_keys(Config::get('const.translate')) as $lang)
				<div class='cFrameLangOption' href='{{ url($lang . $currentUrl)  }}' data-lang='{{ $lang }}'>
					{{ Config::get('const.translate.' . $lang) }}
				</div>
			@endforeach
		</div>
	</div>
	<div class="tn-lang">
		<i class="iconfont">&#xe87f;</i>
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
					<a data-role=none id="menu_text_logout" onclick="event.preventDefault();document.getElementById('logout-form').submit();" href="{{ route('logout') }}"><i class="iconfont">&#xe620;</i>&nbsp;{{ __('frame.menu.logout') }}</a>
					<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">{{ csrf_field() }}</form>
				</blockTitle>
				<div id="top-menu-portrait">
					<img src="{{ Auth::user()->bigavatar }}" />
				</div>
				<a data-role=none id="menu_text_usertype"><i class="iconfont">{{ Config::get('const.TYPE2ICON.' . Auth::user()->method) }}</i></a>
				<a data-role=none id="menu_text_username">{{ Auth::user()->oriname }}</a>
			@else
				<blockTitle class="hastitle_title"><i class="iconfont">&#xe621;</i>&nbsp;{{ __('frame.menu.login') }}</blockTitle>
				<a data-role=none href="{{ url('login/baidu') }}"><i class="iconfont">{{ Config::get('const.TYPE2ICON.0') }}</i></a>
				<a data-role=none href="{{ url('login/weibo') }}"><i class="iconfont">{{ Config::get('const.TYPE2ICON.1') }}</i></a>
				<a data-role=none href="{{ url('login/facebook') }}"><i class="iconfont">{{ Config::get('const.TYPE2ICON.7') }}</i></a>
				<a data-role=none href="{{ url('login/google') }}"><i class="iconfont">{{ Config::get('const.TYPE2ICON.8') }}</i></a>
			@endauth
		</div>
		<ul id="menu-table">
			<li>
				<a data-role=none>{{ __('frame.menu.rank') }}</a>
				<ul>
					<li><a data-role=none href="{{ url(App::getLocale() . '/rank/atp/s/year') }}">{{ __('frame.menu.atp_s_year') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/rank/wta/s/year') }}">{{ __('frame.menu.wta_s_year') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/rank/atp/d/year') }}">{{ __('frame.menu.atp_d_year') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/rank/wta/d/year') }}">{{ __('frame.menu.wta_d_year') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/rank/atp/s/race') }}">{{ __('frame.menu.atp_s_race') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/rank/wta/s/race') }}">{{ __('frame.menu.wta_s_race') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/rank/atp/s/nextgen') }}">{{ __('frame.menu.atp_s_nextgen') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/rank/custom/') }}">{{ __('frame.menu.custom') }}</a></li>
				</ul>
			</li>
			<li>
				<a data-role=none href="{{ url(App::getLocale() . '/result') }}">{{ __('frame.menu.score') }}</a>
			</li>
			<li>
				<a data-role=none href="{{ url(App::getLocale() . '/calendar/2018') }}">{{ __('frame.menu.calendar') }}</a>
			</li>
			<li>
				<a data-role=none id="iMenuForDraw">{{ __('frame.menu.draw') }}</a>
				<ul id=iMenuDraw></ul>
			</li>
			<li>
				<a data-role=none>{{ __('frame.menu.dc') }}</a>
				<ul>
					<li><a data-role=none href="{{ url(App::getLocale() . '/dc/5014/2018/MS') }}">{{ "ATP " . translate_tour('Shanghai') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/dc/M015/2018/WS') }}">{{ "WTA " . translate_tour('Beijing') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/dc/1075/2018/WS') }}">{{ "WTA " . translate_tour('Wuhan') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/dc/UO/2018/MS') }}">{{ "ATP " . translate_tour('US Open') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/dc/UO/2018/WS') }}">{{ "WTA " . translate_tour('US Open') }}</a></li>
				</ul>
			</li>
			<li>
				<a data-role=none href="{{ url(App::getLocale() . '/h2h') }}">{{ __('frame.menu.h2h') }}</a>
			</li>
			<li>
				<a data-role=none>{{ __('frame.menu.guess.game') }}</a>
				<ul>
					<li><a data-role=none href="{{ url(App::getLocale() . '/guess') }}">{{ __('frame.menu.guess.pick') }}</a></li>
					<li><a data-role=none href="{{ url(App::getLocale() . '/guess/calendar/2018') }}">{{ __('frame.menu.guess.schedule') }}</a></li>
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
							<li><a data-role=none href="{{ url(App::getLocale() . '/draw/D41/2018') }}">{{ translate_tour('Shanghai')." ".__('frame.menu.draw') }}</a></li>
							<li><a data-role=none href="{{ url(App::getLocale() . '/draw/D40/2018') }}">{{ translate_tour('Beijing')." ".__('frame.menu.draw') }}</a></li>
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
				<a data-role=none href="{{ url(App::getLocale() . '/history/official') }}">{{ __('frame.menu.officialRank') }}</a>
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
		<div class="C_nobg">
			<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
			<!-- 矩形_125_125 -->
			<ins class="adsbygoogle tn-ad1"
				 style="display:inline-block;width:200px;height:200px;border:1px solid #d3e0e9;"
				 data-ad-client="ca-pub-4292980114755588"
				 data-ad-slot="6730377083"></ins>
			<script>
			(adsbygoogle = window.adsbygoogle || []).push({});
			</script>
		</div>
		<div class="C_nobg">
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
		<div id="tn-ad2">
			<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
			<!-- 自适应 -->
			<ins class="adsbygoogle tn-ad2"
				 style="display:block"
				 data-ad-client="ca-pub-4292980114755588"
				 data-ad-slot="1371167482"
				 data-ad-format="auto"></ins>
			<script>
					(adsbygoogle = window.adsbygoogle || []).push({});
			</script>
		</div>
		@yield('content')
		<div id="tn-ad3">
			<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
			<!-- 自适应2 -->
			<ins class="adsbygoogle tn-ad3"
				 style="display:block"
				 data-ad-client="ca-pub-4292980114755588"
				 data-ad-slot="7042597884"
				 data-ad-format="auto"></ins>
			<script>
					(adsbygoogle = window.adsbygoogle || []).push({});
			</script>
		</div>
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
				<div></div>
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
				<div><a link="http://www.protennislive.com/LSHD/main.html?year=2018" type=refer>{{ __('help.footer.menu.atpwtalive') }}</a></div>
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
				<div><a link="https://twitter.com/lesbuy" type=refer>{{ __('help.footer.menu.twitter') }}</a></div>
			</td>
		</tr></tbody></table>
	</div>
	<div>
		{!! __('frame.menu.footer') !!} 2014-2018 <span id=iButtonMsg><div><i class="iconfont">&#xe63c;</i></div>{!! __('help.msgboard.leave') !!}</span>
	</div>
</div>
<div id=iMask>
</div>
<div id=cty_tip>
</div>
</body>
<iframe id="uuid" src="" style="display: none;"></iframe>
</html>
