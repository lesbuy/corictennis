@extends('layouts.header')

@section('content')

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.dcpk') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.pickmeup') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.dcpk') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.pickmeup') }}">
@endif

<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.dcpk') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.pickmeup') }}"></script>

<script type="text/javascript" language="javascript" class="init">

	var submit_url = '{{ url(join('/', [App::getLocale(), 'guess', 'submit'])) }}';

	$(function () {

		$('.cUpdateTime').each(function () {
			var t = $.trim($(this).html());
			$(this).html(GetLocalDate(t, 8));
		});

		$('.cDrawGuessSubmit').on('click', function (e) {

			var form = $('#iDcpkGuessForm' + $(this).attr('value'));
			var data = form.serialize();

			$('#iMask').fadeIn(500).css('display', '-webkit-flex');
			$('#iMask').html('<div id=iAjaxNotice>' + '{{ __('frame.notice.gfw') }}'  + '</div>');

			$.ajax({
				url: submit_url,
				data: data,
				type: 'POST',
				success: function (data) {
					$('#iAjaxNotice').html(data);
				}
			});
		});

		$('.cDrawGuessMatchExpired input, .cDrawGuessMatchExpired select').attr('disabled', true);
			
	});

</script>
<div id=iDcpkGuess>
	@if ($ret['status'] == -2)
		<div id=iAjaxNotice>{{ __('dcpk.errcode.noMatch') }}</div>
	@else

		<div class=tips>{!! __('dcpk.tip') !!}</div>
<!--		<img class="tn-rebo" src="{{ url('/images/tips/rebo.jpg') }}" />-->

		@foreach ($ret['matches'] as $date => $matches)
			<form class='cDrawGuessForm' name='form-{{ $date }}' id='iDcpkGuessForm{{ $date }}'>
				<div class="cDrawGuessFormTitle">
					<input type=text name=date value="{{ $date }}" class='cDrawGuessDate' readonly />
					<div class='cDrawGuessScoreRank'>
						@if ($ret['permit'][$date])
							<span class=kv><span class="k selected">{{ __('dcpk.guess.deadline') }}</span><span class=v><span class=cUpdateTime>{{ date('Y-m-d H:i', strtotime($date) + $ret['deadline'][$date] * 3600) }}</span></span></span>
							@if ($ret['status'] == -1 )
								<span class=kv><span class="k selected">{{ __('dcpk.errcode.notLogin') }}</span></span>
							@elseif ($ret['status'] == -3 )
								<span class=kv><span class="k selected">{{ __('dcpk.errcode.notClosed') }}</span></span>
							@elseif ($ret['status'] == -4 )
								<span class=kv><span class="k selected">{{ __('dcpk.errcode.notPermitted') }}</span></span>
							@else
								<div class='cDrawGuessSubmit selected' value="{{ $date }}">{{ __('dcpk.guess.submit') }}</div>
							@endif
						@else
							@if ($ret['rank'][$date] !== NULL)
								<span class=kv><span class="k selected">{!! get_icon(Config::get('const.TYPE2ICONNAME.' . $ret['rank'][$date]['usertype'])) !!}</span><span class="v">{{ $ret['rank'][$date]['username'] }}</span></span>
								<span class=kv><span class="k selected">{{ __('dcpk.guess.total') }}</span><span class=v>{{ sprintf("%.2f", $ret['rank'][$date]['score'] / 100) }}</span></span>
								<span class=kv><span class="k selected">{{ __('dcpk.guess.rank') }}</span><span class=v>{{ $ret['rank'][$date]['rank'] }}</span></span>
								<span class=kv><span class="k selected">{{ __('dcpk.guess.weekRank') }}</span><span class=v>{{ $ret['rank'][$date]['weekRank'] }}</span></span>
							@else
								<span class=kv><span class="k wrong">{{ $ret['status'] == -3 || $ret['status'] == 0 || $ret['status'] == -4 ? __('dcpk.guess.absent') : __('dcpk.errcode.notLogin') }}</span></span>
							@endif
						@endif
					</div>
				</div>

				@if (!$ret['permit'][$date] || $ret['status'] != -3)
					@foreach ($matches as $seq => $match)
						<div id='iDrawGuessMatch{{ $seq }}' class='cDrawGuessMatch {{ $match[6] == 3 ? 'cDrawGuessMatchExpired' : '' }}'>
							<div>{{ translate_tour($match[0]) }} {{ translate('roundname', $match[1]) }}</div>
							<div class="cDrawGuessMatchPlayer">
								<div>
									<span class="cDrawGuessMatchOdd">{{ $match[4] ? $match[4] : '' }}</span>
									<input type=radio name="winner{{ $seq }}" value=1 id="winner1{{ $seq }}" {{ (isset($ret['fill'][$seq]) && $ret['fill'][$seq][0] == 1) || (!isset($ret['fill'][$seq]) && $ret['permit'][$date]) ? 'checked' : '' }} {{ !$ret['permit'][$date] ? 'disabled' : '' }}/>
									<label for="winner1{{ $seq }}" class="{{ !$ret['permit'][$date] && $match[6] == 1 ? 'cDrawGuessMatchPlayerRight' : '' }}">
										{{ join('/', array_map(function($v) use($ret) {if (isset($ret['name'][$v])) return $ret['name'][$v]; else return $v;}, $match[2])) }}
										<div class="cDrawGuessMatchPlayerRightDotLeft {{ !$ret['permit'][$date] && $match[6] == 1 ? 'DotWin' : '' }}"></div>
									</label>
								</div>
								<div>VS</div>
								<div>
									<input type=radio name="winner{{ $seq }}" value=2 id="winner2{{ $seq }}" {{ isset($ret['fill'][$seq]) && $ret['fill'][$seq][0] == 2 ? 'checked' : '' }} {{ !$ret['permit'][$date] ? 'disabled' : '' }}/>
									<label for="winner2{{ $seq }}" class="{{ !$ret['permit'][$date] && $match[6] == 2 ? 'cDrawGuessMatchPlayerRight' : '' }}">
										<div class="cDrawGuessMatchPlayerRightDotRight {{ !$ret['permit'][$date] && $match[6] == 2 ? 'DotWin' : '' }}"></div>
										{{ join('/', array_map(function($v) use($ret) {if (isset($ret['name'][$v])) return $ret['name'][$v]; else return $v;}, $match[3])) }}
									</label>
									<span class="cDrawGuessMatchOdd">{{ $match[5] ? $match[5] : '' }}</span>
								</div>
							</div>
							<div>
								{{ __('dcpk.guess.sets') }}
								<sep class="{{ !$ret['permit'][$date] ? (isset($ret['fill'][$seq]) && $match[7] !== NULL ? ($match[7] == $ret['fill'][$seq][1] ? "Statuswinner" : "Statusloser") : "") : "selected" }}">
									@if ($ret['permit'][$date])
										<select name="set{{ $seq }}">
											@for ($i = 2; $i <= 5; ++$i)
												<option value={{ $i }} {{ (isset($ret['fill'][$seq]) && $ret['fill'][$seq][1] == $i) || (!isset($ret['fill'][$seq]) && $i == 3) ? 'selected' : '' }}>{{ $i }}</option>
											@endfor
										</select>
									@else
										@if ($match[7] !== NULL && $match[7] !== @$ret['fill'][$seq][1])
											<span class="Statuswinner">{{ $match[7] }}</span>
										@endif
										{{ isset($ret['fill'][$seq]) && $ret['fill'][$seq][1] !== NULL ? $ret['fill'][$seq][1] : '-' }}
									@endif
								</sep>
								{{ __('dcpk.guess.aces') }}
								<sep class="{{ !$ret['permit'][$date] ? (isset($ret['fill'][$seq]) && $match[8] !== NULL ? ($match[8] == $ret['fill'][$seq][2] ? "Statuswinner" : "Statusloser") : "") : "selected" }}">
									@if ($ret['permit'][$date])
										<select name="ace{{ $seq }}">
											@for ($i = 0; $i < 100; ++$i)
												<option value={{ $i }} {{ isset($ret['fill'][$seq]) && $ret['fill'][$seq][2] == $i ? 'selected' : '' }}>{{ $i }}</option>
											@endfor
										</select>
									@else
										@if ($match[8] !== NULL && $match[8] !== @$ret['fill'][$seq][2])
											<span class="Statuswinner">{{ $match[8] }}</span>
										@endif
										{{ isset($ret['fill'][$seq]) && $ret['fill'][$seq][2] !== NULL ? $ret['fill'][$seq][2] : '-' }}
									@endif
								</sep>
								{{ __('dcpk.guess.time') }}
								<sep class="{{ !$ret['permit'][$date] ? (isset($ret['fill'][$seq]) && $match[9] !== NULL ? (floor($match[9] / 60) == floor($ret['fill'][$seq][3] / 60) ? "Statuswinner" : "Statusloser") : "") : "selected" }}">
									@if ($ret['permit'][$date])
										<select name="hour{{ $seq }}">
											@for ($i = 0; $i < 10; ++$i)
												<option value={{ $i }} {{ isset($ret['fill'][$seq]) && floor($ret['fill'][$seq][3] / 60) == $i ? 'selected' : '' }}>{{ $i }}</option>
											@endfor
										</select>
									@else
										@if ($match[9] !== NULL && floor($match[9] / 60) !== floor(@$ret['fill'][$seq][3] / 60))
											<span class="Statuswinner">{{ floor($match[9] / 60) }}</span>
										@endif
										{{ isset($ret['fill'][$seq]) && $ret['fill'][$seq][3] !== NULL ? floor($ret['fill'][$seq][3] / 60) : '-' }}
									@endif
								</sep>
								{{ __('dcpk.guess.hour') }}
								<sep class="{{ !$ret['permit'][$date] ? (isset($ret['fill'][$seq]) && $match[9] !== NULL ? ($match[9] % 60 == $ret['fill'][$seq][3] % 60 ? "Statuswinner" : "Statusloser") : "") : "selected" }}">
									@if ($ret['permit'][$date])
										<select name="minute{{ $seq }}">
											@for ($i = 0; $i < 60; ++$i)
												<option value={{ $i }} {{ isset($ret['fill'][$seq]) && $ret['fill'][$seq][3] % 60 == $i ? 'selected' : '' }}>{{ $i }}</option>
											@endfor
										</select>
									@else
										@if ($match[9] !== NULL && $match[9] % 60 !== @$ret['fill'][$seq][3] % 60)
											<span class="Statuswinner">{{ $match[9] % 60 }}</span>
										@endif
										{{ isset($ret['fill'][$seq]) && $ret['fill'][$seq][3] !== NULL ? $ret['fill'][$seq][3] % 60 : '-' }}
									@endif
								</sep>
								{{ __('dcpk.guess.minute') }}

							</div>
							<div class='cDrawGuessMatchScore'>{{ !$ret['permit'][$date] && isset($ret['fill'][$seq]) && $ret['fill'][$seq][4] !== NULL ? sprintf("%.2f", $ret['fill'][$seq][4] / 100) : '' }}</div>
						</div>
					@endforeach
					{{--  end each match --}}
				@endif


			</form>
		@endforeach
		{{--  end each date --}}
	@endif
</div>

@endsection
