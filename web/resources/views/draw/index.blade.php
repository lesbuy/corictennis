@extends('layouts.header')

@section('content')

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.draw') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.pickmeup') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.draw') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.pickmeup') }}">
@endif

<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.draw') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.pickmeup') }}"></script>
<!--<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>-->

<script type="text/javascript" language="javascript" class="init">

	$(function () {

		var device = window.innerWidth >= 500 ? 0 : 1;
		var year = {{ $year }};
		var eid = "{{ $eid }}";
		var href = "{{ url(App::getLocale() . '/draw') }}";
		var draw_type;

		$(document).on('click', '#iDrawPartSelector > div', function () {

			var type = $(this).attr('data-id');

			$('.cDrawPart').hide();
			$('.cDrawPart[data-id="' + type + '"]').show();

			$('#iDrawPartSelector > div').removeClass('cDrawPartSelectorSelected');
			$('#iDrawPartSelector > div').removeClass('selected');
			$('#iDrawPartSelector > div').addClass('unselected');
			$(this).addClass('cDrawPartSelectorSelected');
			$(this).removeClass('unselected');
			$(this).addClass('selected');

			setCookie('def_sex', type);
			draw_type = type;

			_hmt.push(['_trackCustomEvent', 'draw_sextip', {'eid':eid,'year':year,'sextip':type}]);
		});

		$(document).on('click', 'pname', function(e) {
			var pid = $(this).attr('data-id');
			if (pid == "LIVE") {
				url = "{{ url(join('/', [App::getLocale(), 'live'])) }}";
				window.location.href = url;
				return;
			} else if (pid == "COMEUP" || pid == "TBD") {
				url = "{{ url(join('/', [App::getLocale(), 'result'])) }}";
				window.location.href = url;
				return;
			} else if (pid == "QUAL") {
				return;
			}

			url = "{{ url(join('/', [App::getLocale(), 'draw', $eid, $year, 'road'])) }}" + '/' + draw_type + '/' + pid;

			var c_url = ["{{ slash(url(App::getLocale() . '/road' . "/" . $eid . "/" . $year)) }}", draw_type, $(this).attr('alt').replace(/ /g, "")].join("/");
			_hmt.push(['_trackCustomEvent', 'draw_road', {'eid':eid,'year':year,'sextip':draw_type,'p1':$(this).attr('alt').replace(/ /g, "")}]);
			ga('send', 'pageview', c_url);

			$('#iMask').fadeIn(500).css('display', '-webkit-flex');
			$('#iMask').html('<div id=iAjaxNotice>' + '{{ __('frame.notice.gfw') }}'  + '</div>');

			$.ajax({
				type: 'POST',
				url: url,
				success: function (data) {
					$('#iMask').html(data);
				}
			});
		});

		$.ajax({
			type: 'POST',
			url: "{{ url(join('/', [App::getLocale(), 'draw', $eid, $year])) }}",
			data: {
				device: device,
				screen_width: window.screen.width,
				screen_height: window.screen.height,
			},
			success: function (data) {
				$('#iDraw').html(data);

				var def_sex = getCookie('def_sex');
				if (def_sex != null && $('#iDrawPartSelector > div[data-id="' + def_sex + '"]').length > 0) {
					$('#iDrawPartSelector > div[data-id="' + def_sex + '"]').trigger('click');
				} else {
					$($('#iDrawPartSelector > div')[0]).trigger('click');
				}

				$("img.cImgPlayerFlag", $('#iDraw')).lazyload();
			}
		});

/*
		@if (in_array($eid, ['AO', 'RG', 'WC', 'UO', 'OL']))

			pickmeup('#iDatePicker', {
				format: "Y",
				hide_on_select  : true,
				min : "1968",
				max : "{{ date('Y', time()) }}",
				prev: '<<',
				next: '>>',
				select_day: false,
				select_month: false,
				default_date: false,
				class_name: "cCalendar",
			});

			$('#iDatePicker')[0].addEventListener('pickmeup-change', function(e) {
				var formatted = e.detail.formatted_date;
				$(this).val(formatted);
				year = formatted;
				if (formatted == 1968) {
					$('#iDrawGsAo + label').hide();
				} else {
					$('#iDrawGsAo + label').show();
				}
				if ((formatted < 1988 || formatted % 4 != 0) && (formatted < 2010 || formatted % 4 != 2)) {
					$('#iDrawGsOl + label').hide();
				} else {
					$('#iDrawGsOl + label').show();
				}

			});

			$(':radio[value="{{ $eid }}"]').trigger('click');

			$(':radio[name=eid]').on('click', function () {
				window.location.href = href + '/' + $(this).val() + '/' + year;
			});

		@endif
*/

	});

</script>
<!--
@if (in_array($eid, ['AO', 'RG', 'WC', 'UO', 'OL']))

	<div id=iDrawGsSelector>

		<input type=text id="iDatePicker" value="{{ $year }}" readonly=readonly />
		<input type=radio name=eid id=iDrawGsAo value=AO /><label for=iDrawGsAo {{ $year == 1968 ? 'style=display:none' : "" }}><img src="{{ url(env('CDN') . '/images/tour_logo/GS-AO.png') }}" /></label>
		<input type=radio name=eid id=iDrawGsRg value=RG /><label for=iDrawGsRg><img src="{{ url(env('CDN') . '/images/tour_logo/GS-RG.png') }}" /></label>
		<input type=radio name=eid id=iDrawGsWc value=WC /><label for=iDrawGsWc><img src="{{ url(env('CDN') . '/images/tour_logo/GS-WC.png') }}" /></label>
		<input type=radio name=eid id=iDrawGsUo value=UO /><label for=iDrawGsUo><img src="{{ url(env('CDN') . '/images/tour_logo/GS-UO.png') }}" /></label>
		<input type=radio name=eid id=iDrawGsOl value=OL /><label for=iDrawGsOl {{ ($year < 1988 || $year % 4 != 0) && ($year < 2010 || $year % 4 != 2) ? 'style=display:none' : "" }}><img src="{{ url(env('CDN') . '/images/tour_logo/ITF-OL.png') }}" /></label>
		
	

	</div>

@endif
-->
<div id=iDraw>

	<img class=cLoading src="{{ url(env('CDN') . '/images/tips/loading-cube.svg') }}" />

</div>

@endsection
