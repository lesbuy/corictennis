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

<script type="text/javascript" language="javascript" class="init">

	$(function () {

		var url = "{{ url(App::getLocale() . '/calendar') }}";

		$(':radio[name="_type"]').on('click', function () {
			var _type = $(this).val();
			$('.cCalendarTable').hide();
			$('.cCalendarTable[data-id="' + _type + '"]').show();
		});

		$(':radio[name="_sex"]').on('click', function () {
			var _sex = $(this).val();
			$('.cCalendarTour').hide();

			if (_sex == "all") {
				$('.cCalendarTour').show();
			} else if (_sex == "mw") {
				$('.cCalendarTourMW').show();
			} else if (_sex == "m") {
				$('.cCalendarTourMW, .cCalendarTourM').show();
			} else if (_sex == "w") {
				$('.cCalendarTourMW, .cCalendarTourW').show();
			}
		});

		$('.cCalendarTour').on('click', function() {
			var href = $(this).attr('href');
			window.location.href = href;
		});

		pickmeup('#iDatePicker', {
			format: "Y",
			hide_on_select  : true,
			min : "1968",
//			max : "{{ date('Y', time()) }}",
			max : "2021",
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
			window.location.href = url + '/' + formatted;
		});

		$(':radio[value="WT"]').trigger('click');
		$(':radio[value="all"]').trigger('click');

	});

</script>

<div id=iCalendar>

	<div id=iCalendarSelector class="cDateSelector">
		<img class="cResultTourTitleArrow" src="{{ url(env('CDN') . '/images/tips/live_tour_arrow.png') }}" />
		<input class="selected cDatePicker" type=text id="iDatePicker" value="{{ $ret['year'] }}" readonly=readonly />
		@if (isset($ret['WT']))
			<input type=radio name="_type" id=iCalendarWT value=WT /><label class="unselected" for=iCalendarWT>{{ __('draw.calendar.WT') }}</label>
		@endif
		@if (isset($ret['CH']))
			<input type=radio name="_type" id=iCalendarCH value=CH /><label class="unselected" for=iCalendarCH>{{ __('draw.calendar.CH') }}</label>
		@endif
		@if (isset($ret['ITF']))
			<input type=radio name="_type" id=iCalendarITF value=ITF /><label class="unselected" for=iCalendarITF>{{ __('draw.calendar.ITF') }}</label>
		@endif
		@if (isset($ret['J']))
			<input type=radio name="_type" id=iCalendarJ value=J /><label class="unselected" for=iCalendarJ>{{ __('draw.calendar.J') }}</label>
		@endif
		<br>
		<input type=radio name="_sex" id=iCalendarAll value=all /><label class="unselected" for=iCalendarAll>{{ __('draw.calendar.ALL') }}</label>
		<input type=radio name="_sex" id=iCalendarM value=m /><label class="unselected" for=iCalendarM>{{ __('draw.calendar.M') }}</label>
		<input type=radio name="_sex" id=iCalendarW value=w /><label class="unselected" for=iCalendarW>{{ __('draw.calendar.W') }}</label>
	</div>

	@foreach (['WT', 'CH', 'ITF', 'J'] as $type)
		@if (isset($ret[$type]))
			<table class='cCalendarTable' data-id="{{ $type }}" style="display: none"><tbody>
				@foreach ($ret[$type] as $date => $tours) 
					<tr><td>{{ $date }}</td><td>
						@foreach ($tours as $tour)
							@if ($tour[2] == "blank")
								<br>
							@else
								<div class="cCalendarTour cCalendarTour{{ $tour[4] }}" href="{{ route('draw', ['lang' => App::getLocale(), 'eid' => $tour[0], 'year' => $ret['year']]) }}">
									@foreach ($tour[1] as $level)
										<img src="{{ get_tour_logo_by_id_type_name($tour[0], $level) }}" />
									@endforeach
									{!! get_flag($tour[6]) !!}{{ $type != "WT" ? $tour[5] . " " : "" }}{{ translate_tour($tour[2]) }}
								</div>
							@endif
						@endforeach
					</td></tr>
				@endforeach
			</tbody></table>
		@endif
	@endforeach

</div>

@endsection
