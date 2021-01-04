@extends('layouts.header')

@section('content')

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.gs') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.h2h') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.h2hDetail') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.gs') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.h2h') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.h2hDetail') }}">
@endif
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.gs') }}"></script>

<script type="text/javascript" language="javascript" class="init">

$(function() {

	var type;
	var level;
	var sd;
	var round;

	$(':radio[name=type]').on('click', function() {
		type = $(this).val();
	});

	$(':radio[name=level]').on('click', function() {
		level = $(this).val();
	});

	$(':radio[name=round]').on('click', function() {
		round = $(this).val();
	});

	$(':radio[name=sd]').on('click', function() {
		sd = $(this).val();
	});

	$('#iGSSubmit').on('click', function () {

		if (!validate(type, sd, level, round)) {
			alert("{{ __('h2h.warning.illegal') }}");
			return;
		}

		var c_url = ["{{ slash(url(App::getLocale() . '/history/tourquery')) }}", level, type + sd, round].join("/");
		_hmt.push(['_trackCustomEvent', 'tourquery', {'level':level,'sextip':type + sd,'round':round}]);
		ga('send', 'pageview', c_url);

		$('#iGSResult').html("{{ __('frame.notice.gfw') }}");

		var url = ["{{ url(App::getLocale() . '/history') }}", level, 'gender', type + sd, round].join("/");

		$.ajax({
			type: 'POST',
			url: url,
			success: function (data) {
				$('#iGSResult').html(data);
			}
		});
	});

	$('#iGSInput').on('focus', 'input', function (e) {

		var me = this;
		setTimeout(function () {
			var t = $(me).offset().top;
			console.log(t);
			$('body').scrollTop(t - 70);
		}, 200);
	});

	$(document).on('click', 'pname', function(e) {
		var pid = $(this).attr('data-id');
		var draw_type = type + sd;
		var eid = $(this).attr('eid');
		var year = $(this).attr('year');
		url = ["{{ url(join('/', [App::getLocale(), 'draw'])) }}", eid, year, "road", draw_type, pid].join("/");

		var c_url = ["{{ slash(url(App::getLocale())) }}", "road", eid, year, draw_type, $(this).attr('alt').replace(/ /g, "")].join("/");
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

	{{-- 触发默认值 --}}
	$('#iGSSdS').trigger('click');
	$('#iGSTypeATP').trigger('click');
	$('#iGSLevelGS').trigger('click');
	$('#iGSRoundW').trigger('click');

});

</script>

<div id="iGS" class="cH2H">
	<form id="iGSSelect" class="cH2HSelect">
		{{ csrf_field() }}

        <table>  
            <tr> 
                <td colspan=4 class="cH2HSelectTitleTitle">
					{{ __('h2h.selectBarTitle.title') }}
					<div id="iGSSubmit" class="selected cH2HSubmit">{{ __('h2h.select.query') }}</div>
				</td>
            </tr>
            <tr> 
                <td class="cH2HSelectTitle">{{ __('h2h.selectBarTitle.tourType') }}</td>
                <td>
                    <div>
                        <input type=radio name=level id=iGSLevelGS value=gs></input><label class="unselected" for=iGSLevelGS>{{ __('h2h.selectBar.level.g') }}</label>
                        <input type=radio name=level id=iGSLevelMS value=t1></input><label class="unselected" for=iGSLevelMS>{{ __('h2h.selectBar.level.m') }}</label>
                    </div>
                </td>
				<td class="cH2HSelectTitle">{{ __('h2h.selectBarTitle.roundType') }}</td>
				<td>
					<div>
						<input type=radio name=round id=iGSRoundW value=W></input><label class="unselected" for=iGSRoundW>{{ __('h2h.selectBar.round.w') }}</label>
						<input type=radio name=round id=iGSRoundF value=F></input><label class="unselected" for=iGSRoundF>{{ __('h2h.selectBar.round.f') }}</label>
						<input type=radio name=round id=iGSRoundSF value=SF></input><label class="unselected" for=iGSRoundSF>{{ __('h2h.selectBar.round.s') }}</label>
						<input type=radio name=round id=iGSRoundQF value=QF></input><label class="unselected" for=iGSRoundQF>{{ __('h2h.selectBar.round.q') }}</label>
					</div>
				</td>
			</tr>
            <tr> 
                <td class="cH2HSelectTitle">{{ __('h2h.selectBarTitle.itemType') }}</td>
                <td>
                    <div>
                        <input type=radio name=sd id=iGSSdS value=S></input><label class="unselected" for=iGSSdS>{{ __('h2h.selectBar.sd.s') }}</label>
                        <input type=radio name=sd id=iGSSdD value=D></input><label class="unselected" for=iGSSdD>{{ __('h2h.selectBar.sd.d') }}</label>
                    </div>
                </td>
				<td class="cH2HSelectTitle">{{ __('h2h.selectBarTitle.playerType') }}</td>
				<td>
					<div>
						<input type=radio name=type id=iGSTypeATP value=M></input><label class="unselected" for=iGSTypeATP>{{ __('h2h.selectBar.type.atp') }}</label>
						<input type=radio name=type id=iGSTypeWTA value=W></input><label class="unselected" for=iGSTypeWTA>{{ __('h2h.selectBar.type.wta') }}</label>
						<input type=radio name=type id=iGSTypeMIX value=X></input><label class="unselected" for=iGSTypeMIX>{{ __('h2h.selectBar.type.mix') }}</label>
						<input type=radio name=type id=iGSTypeBOY value=B></input><label class="unselected" for=iGSTypeBOY>{{ __('h2h.selectBar.type.boy') }}</label>
						<input type=radio name=type id=iGSTypeGIRL value=G></input><label class="unselected" for=iGSTypeGIRL>{{ __('h2h.selectBar.type.girl') }}</label>
					</div>
				</td>
            </tr>
		</table>
	</form>
	<div id=iGSResult class="cH2HResult">
	</div>
</div>

@endsection
