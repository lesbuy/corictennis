@extends('layouts.header')

@section('content')

@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.dc') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.draw') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.dc') }}">
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.draw') }}">
@endif
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.dc') }}"></script>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<script type="text/javascript" language="javascript" class="init">

	player_info = @json($ret['info']);

	$(function () {

		$('input[name=selectedBlock]').on('click', function (e) {
			var val = $(this).val();
			$('.cDcBlock').hide();
			$('#iDcBlock' + val).show();
		});
		@if ($ret['show_qf'])
			$('#block0').trigger('click');
		@else
			$('#block1').trigger('click');
		@endif

		@if ($ret['permit'])

			$('.cDcBlockGrid').on('click', function (e) {
				var x = parseInt($(this).attr('data-round'));
				var y = parseInt($(this).attr('data-seq'));
				var value = parseInt($(this).attr('data-value'));

				y = y % (1 << x) == 0 ? y : y + (1 << (x - 1));
				x += 1;
				var next_grid = $('.cDcBlockGrid[data-round=' + x + '][data-seq=' + y + ']');
				var ori_value = next_grid.attr('data-value');
				if (ori_value == 0) {
					next_grid.attr('data-value', value);
					next_grid.html(player_info[value]);
					$('input[name=' + x + '_' + y + ']').val(value);
				} else if (ori_value != value) {
					var next_value = ori_value;
					while (true) {
						if (next_value != ori_value) {
							break;
						} else {
							next_grid.attr('data-value', value);
							next_grid.html(player_info[value]);
							$('input[name=' + x + '_' + y + ']').val(value);
							y = y % (1 << x) == 0 ? y : y + (1 << (x - 1));
							x += 1;
							next_grid = $('.cDcBlockGrid[data-round=' + x + '][data-seq=' + y + ']');
							next_value = next_grid.attr('data-value');
						}
					}
				}
						
			});

			$('#iDcSubmit').on('click', function (e) {

				var data = $('#iDcFillForm').serialize();

				$('#iMask').fadeIn(500).css('display', '-webkit-flex');
				$('#iMask').html('<div id=iAjaxNotice>' + '{{ __('frame.notice.gfw') }}'  + '</div>');

				$.ajax({
					type: 'POST',
					url: "{{ url(join("/", [App::getLocale(), 'dc', $eid, $year, $sextip, 'save'])) }}",
					data: data,
					success: function (data) {
						$('#iAjaxNotice').html(data);
					}
				});

			});

			var t = $.trim($('.cUpdateTime').html());
			$('.cUpdateTime').html(GetLocalDate(t, 8));
		@elseif ($ret['errcode'] == -1)

			$('.cDcBlockGrid').on('click', function (e) {
				$('#iMask').fadeIn(500).css('display', '-webkit-flex');
				$('#iMask').html('<div id=iAjaxNotice>' + '{{ __('dc.errcode.notLogin') }}'  + '</div>');
			});

		@endif
	});

</script>

<div id=iDc>
	@if (App::isLocale('zh') && $eid == "UU" && Auth::check())
	<div class=tips>
<!--		<img src="{{ url('/images/tips/Title_rg.jpeg') }}" width=100% />-->
		本次美网签表挑战，将由《网球》公众号赞助。奖品由《网球》杂志提供<br>
		只有<b>用微信扫《网球》公众号的二维码后参加</b>才有奖。具体细则请见：<a href="https://mp.weixin.qq.com/s/YL6hv8TDW4NBAT9Q9dIvNg">链接</a><br>
		《网球》公众号：<img src="{{ url('/images/tips/QRCode_wangqiumag.jpeg') }}" height=100 /> 奖品提供商：《网球》杂志 <img src="" height=100 /><br>
		奖品设置：<br>
		一等奖（第1名）：品牌球拍1把、品牌腰包1个 、《网球》杂志1本；<br>
		二等奖（第2名）：法网T恤1件、1桶比赛用球、《网球》杂志1本；<br>
		三等奖（第3名）：法网背包1个、1桶比赛用球、《网球》杂志1本；<br>
		四等奖（第4-6名）：品牌腰包1个 、《网球》杂志1本；<br>
		五等奖（第7-10名）：《网球》杂志1本。<br>
		<b>截止时间为正赛首日比赛开打时（8-26 23:00:00）</b><br>
	</div>
	@endif

	<div id=iDrawInfo>
		@if (isset($ret['country']) && $ret['country'])
			<div id="iDrawMap"></div>
			<script type="text/javascript">
			  google.charts.load('current', {
				'packages':['geochart'],
				'mapsApiKey': 'AIzaSyDIaahaRnDU_dHT24krnoKQ1KPtgc71E8k'
			  });
			  google.charts.setOnLoadCallback(drawRegionsMap);
		 
			  function drawRegionsMap() {
				var chart = new google.visualization.GeoChart(document.getElementById('iDrawMap'));
				var data;
				var options;

/*
				data = google.visualization.arrayToDataTable(@json($ret['country']));
				options = {
					region: "{{ $ret['region'] }}",
					backgroundColor: 'transparent',
					displayMode: 'regions',
				};
				chart.draw(data, options);
*/
				data = google.visualization.arrayToDataTable(@json($ret['cityData']));
				options = {
					region: "{{ $ret['region'] }}",
					backgroundColor: 'transparent',
					displayMode: 'markers',
					colorAxis: {colors: ['green', 'blue']}
				}
				chart.draw(data, options);
			  }
			</script>
		@endif

		@if ($ret['status'] > -2)
			<div id='iDrawTour'>
				<div id='iDrawTourName'>
					@foreach ($ret['levels'] as $level)
						@if (get_tour_logo_by_id_type_name($eid, $level))
							<img class='cDrawTourType' src="{{ get_tour_logo_by_id_type_name($eid, $level) }}" />
						@endif
					@endforeach
					{{ join('/', array_map(function ($v) { return translate_tour($v); }, $ret['city'])) }}
				</div>
				<div id='iDrawTourDate'>{{ $ret['date'] }} {{ join('/', array_map(function ($v){ return translate('frame.ground', $v); }, $ret['surface'])) }}</div>
				<div id='iDrawTourTitle'>{{ $ret['title'] }}</div>
				<div id='iDrawTourMore'></div>
			</div>
		@endif

	</div>

	<div id='iDcScoreRank'>
		@if ($ret['permit'])
			<span class=kv><span class="k selected">{{ __('dcpk.guess.deadline') }}</span><span class="v">{{ $ret['ddl'] }}</span>
			<span class=kv><span id='iDcSubmit' class="selected k">{{ __('dc.submit') }}</span></span>
		@elseif ($ret['errcode'] == -1)
			<span class=kv><span class="k selected">{{ __('dcpk.guess.deadline') }}</span><span class="v">{{ $ret['ddl'] }}</span>
			<span class=kv><span class="selected k">{{ __('dc.errcode.notLogin') }}</span></span>
		@elseif ($ret['errcode'] == -2)
			<span class=kv><span class="k selected">{!! $ret['method'] !!}</span><span class="v">{!! $ret['username'] !!}</span></span>
			<span class=kv><span class="k selected">{{ __('dc.score') }}</span><span class=v>{{ $ret['score'] }}</span></span>
			<span class=kv><span class="k selected">{{ __('dc.matches') }}</span><span class=v>{{ $ret['matches'] }}</span></span>
			<span class=kv><span class="k selected">{{ __('dc.rank') }}</span><span class=v>{{ $ret['rank'] }}</span></span>
		@elseif ($ret['errcode'] == -3)
			<span class=kv><span class="selected k">{{ __('dc.errcode.noMatch') }}</span></span>
		@endif
	</div>

	@if (!$ret['permit'] && $ret['errcode'] == -2)
		<div class="tips">
			<a href="{{ url(join("/", [App::getLocale(), 'dc', $eid, $year, $sextip, 'rank'])) }}">{{ __('dc.lookupRank') }}</a>
		</div>
	@endif

	<div id="iDcSectionSelector">
		@foreach ($ret['fill'] as $block => $BLOCK)
			<input type=radio name=selectedBlock id="block{{ $block }}" value="{{ $block }}" /><label class="unselected" for="block{{ $block }}">{{ $block != 0 ? translate('draw.section', $block.'/8') : translate('draw.section', 'qf') }}</label>
		@endforeach
	</div>

	<form name=dcFill id=iDcFillForm>
		@foreach ($ret['fill'] as $block => $BLOCK)
			@foreach ($BLOCK as $round => $ROUND)
				@foreach ($ROUND as $seq => $player)
					@if ($round > 1)
						<input type=hidden name="{{ $round . "_" . $seq }}" value="{{ $player }}" />
					@endif
				@endforeach
			@endforeach
		@endforeach
		@foreach ($ret['fill'] as $block => $BLOCK)
			<div id="iDcBlock{{ $block }}" class=cDcBlock style="display: none">
				<div class="cDcBlockRound cDcBlockRound0">
					@foreach ($BLOCK[min(array_keys($BLOCK))] as $seq => $player)
						<div class="cDcBlockSeq">
							{{ $seq / (1 << (min(array_keys($BLOCK)) - 1)) }}
						</div>
					@endforeach
				</div>
				@foreach ($BLOCK as $round => $ROUND)
					<div class="cDcBlockRound cDcBlockRound{{ $round - min(array_keys($BLOCK)) + 1 }} {{ $round == 1 ? "cDcBlockRoundFirst" : "" }}">
						@foreach ($ROUND as $seq => $player)
							<div class="cDcBlockGrid cDcBlockGrid{{ $ret['permit'] ? 'wait' : $ret['status'][$block][$round][$seq] }} {{ $ret['permit'] ? 'unselected' : $ret['status'][$block][$round][$seq] }}" data-round="{{ $round }}" data-seq="{{ $seq }}" data-value="{{ $player }}">
								@if (!$ret['permit'] && $ret['status'][$block][$round][$seq] == "wrong" && $ret['right'][$block][$round][$seq] > 0 )
									<div class="cDcGridRightAnswer right">
										{!! $ret['info'][$ret['right'][$block][$round][$seq]] !!}
									</div>
								@endif
								{!! $player > 0 ? $ret['info'][$player] : "&nbsp;" !!}
							</div>
						@endforeach
					</div>
				@endforeach
			</div>
		@endforeach
	</form>
</div>

@endsection
