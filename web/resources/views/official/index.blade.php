@extends('layouts.header')

@section('content')

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.h2h') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.h2hDetail') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.pickmeup') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.optionpicker') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.h2h') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.h2hDetail') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.pickmeup') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.optionpicker') }}">
@endif

<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.echarts') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.optionpicker') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.pickmeup') }}"></script>

<script type="text/javascript" language="javascript" class="init">

$(function() {

	var sd;
	var type;
	var date = '{{ date('Y-m-d', time()) }}';

	$(':radio[name=type]').on('click', function() {
		type = $(this).val();
	});

	$(':radio[name=sd]').on('click', function() {
		sd = $(this).val();
	});

	$('#iOfficialSubmit').on('click', function () {

		var data = {
			status: 'ok',
			sd: sd,
			type: type,
			date: date,
		}

		var c_url = ["{{ slash(url(App::getLocale() . '/official')) }}", sd, type, date].join("/");
		_hmt.push(['_trackCustomEvent', 'official', {'gender':type,'sd':sd,'start_time':date}]);
		ga('send', 'pageview', c_url);

		$('#iOfficialResult').html("{{ __('frame.notice.gfw') }}");

		$.ajax({
			type: 'POST',
			url: "{{ url(App::getLocale() . "/history/official/query") }}",
			data: data,
			success: function (data) {
				$('#iOfficialResult').html(data);
			}
		});
	});

	pickmeup('#iDatePicker', {
		format: "Y-m-d",
		hide_on_select  : true,
		min : "1973-01-01",
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
	});

	$('#iDatePicker')[0].addEventListener('pickmeup-change', function(e) {
		var formatted = e.detail.formatted_date;
		$(this).val(formatted);
		date = formatted;
	});

	{{-- 触发默认值 --}}
	$('#iOfficialTypeATP').trigger('click');
	$('#iOfficialSdSingle').trigger('click');

});

</script>

<div id="iOfficial" class="cH2H">
	<form id="iOfficialSelect" class="cH2HSelect">
		<table>
			<tr>
				<td colspan=4 class="cH2HSelectTitleTitle">{{ __('h2h.selectBarTitle.title') }}</td>
			</tr>
			<tr>
				<td class="cH2HSelectTitle">{{ __('h2h.selectBarTitle.type') }}</td>
				<td>
					<div>
						<input type=radio name=type id=iOfficialTypeATP value=atp></input><label class="unselected" for=iOfficialTypeATP>{{ __('h2h.selectBar.type.atp') }}</label>
						<input type=radio name=type id=iOfficialTypeWTA value=wta></input><label class="unselected" for=iOfficialTypeWTA>{{ __('h2h.selectBar.type.wta') }}</label>
					</div>
				</td>
				<td class="cH2HSelectTitle">{{ __('h2h.selectBarTitle.sd') }}</td>
				<td>
					<div>
						<input type=radio name=sd id=iOfficialSdSingle value=s></input><label class="unselected" for=iOfficialSdSingle>{{ __('h2h.selectBar.sd.s') }}</label>
						<input type=radio name=sd id=iOfficialSdDouble value=d></input><label class="unselected" for=iOfficialSdDouble>{{ __('h2h.selectBar.sd.d') }}</label>
					</div>
				</td>
			</tr>
		</table>
	</form>
	<div id="iOfficialInput" class="cH2HInput">
		<div id="iOfficialInput1" class="cH2HInput1">
			<input class="cDatePicker" type=text id="iDatePicker" value="{{ date('Y-m-d', time()) }}" readonly=readonly />
		</div>
		<div id="iOfficialSubmit" class="cH2HSubmit selected">{!! get_icon('chaxun') !!}</div>
	</div>
	<div id=iOfficialResult class="cH2HResult">
	</div>
</div>

@endsection
