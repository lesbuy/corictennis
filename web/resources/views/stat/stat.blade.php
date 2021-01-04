@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.stat') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.stat') }}">
@endif

@if ($ret['status'] < 0)
	<div id=iAjaxNotice>
		{{ $ret['errmsg'] }}
	</div>
@else

	@php
		$code = ['winner' => "zhengque", 'loser' => "cuowu", 'unfinished' => "qm", ];
	@endphp

	<div id=iStat>

		<div id=iStatNames>
			<div id=iStatName1 class="SideHome">{{ $ret['player'][0] }}</div>
			<div id=iStatName2 class="SideAway">{{ $ret['player'][1] }}</div>
		</div>

		<div id=iStatHeads>
			<div class=cStatHead style="background-image: url({{ $ret['head'][0] }})">
				<div class="StatusFlag Status{{ $ret['wl'][0] }}">{!! get_icon($code[$ret['wl'][0]]) !!}</div>
			</div>
			@if (isset($ret['head'][2]))
				<div class=cStatHead style="background-image: url({{ $ret['head'][1] }})">
					<div class="StatusFlag Status{{ $ret['wl'][0] }}">{!! get_icon($code[$ret['wl'][0]]) !!}</div>
				</div>
			@endif

			<div id=iStatScoreDiv>
				<table id=iStatScoreTable best-of="{{ $ret['bestof'] }}"><tbody>
					@foreach ($ret['score'] as $s)
						<tr>
							<td class="Status{{ substr(strtolower($s[2]), 3) ? substr(strtolower($s[2]), 3) : 'unfinished' }}">{!! $s[0] !!}</td>
							<td class="Status{{ substr(strtolower($s[3]), 3) ? substr(strtolower($s[3]), 3) : 'unfinished' }}">{!! $s[1] !!}</td>
						</tr>
					@endforeach
				</tbody></table>
			</div>

			@if (isset($ret['head'][2]))
				<div class=cStatHead style="background-image: url({{ $ret['head'][2] }})">
					<div class="StatusFlag Status{{ $ret['wl'][1] }}">{!! get_icon($code[$ret['wl'][1]]) !!}</div>
				</div>
			@else
				<div class=cStatHead style="background-image: url({{ $ret['head'][1] }})">
					<div class="StatusFlag Status{{ $ret['wl'][1] }}">{!! get_icon($code[$ret['wl'][1]]) !!}</div>
				</div>
			@endif
			@if (isset($ret['head'][3]))
				<div class=cStatHead style="background-image: url({{ $ret['head'][3] }})">
					<div class="StatusFlag Status{{ $ret['wl'][1] }}">{!! get_icon($code[$ret['wl'][1]]) !!}</div>
				</div>
			@endif
		</div>

		<div id=iStatDiv>
			<div id="iStatUnitSelector">
				<input type=radio name=speedUnit id=iStatSpeedKPH value="cStatKPH" checked /><label for=iStatSpeedKPH class="selected">{{ __('stat.selector.kph') }}</label>
				<input type=radio name=speedUnit id=iStatSpeedMPH value="cStatMPH" /><label for=iStatSpeedMPH class="unselected">{{ __('stat.selector.mph') }}</label>
			</div>
			<table id=iStatSelectors><tbody><tr>
				@foreach ($ret['stat'] as $seq => $set)
					<td class="cStatSelector {{ $loop->first ? "cStatSelectorSelected" : "" }} {{ $loop->first ? "selected" : "unselected" }}" set-seq="{{ $seq }}">
						{{ __('stat.selector.' . $seq) }}
					</td>
				@endforeach
			</tr></tbody></table>

			@foreach ($ret['stat'] as $seq => $set)
				<table id="iStatTable{{ $seq }}" class="cStatTable {{ $loop->first ? "cStatTableSelected" : "" }}"><tbody>
					@foreach ($set[0] as $k => $v)
						<tr>
							@if ($loop->first)
								<td colspan=3>{{ $v }}</td>
							@else
								@if (is_array($v) && count($v) == 2)
									<td class="cStatTdLeft"><span class="cStatSpeed cStatKPH">{{ $set[0][$k][0] }}</span><span class="cStatSpeed cStatMPH">{{ $set[0][$k][1] }}</span></td>
								@else
									<td class="cStatTdLeft">{{ $set[0][$k] }}</td>
								@endif

								@if ($loop->index == 1)
									<td class="cStatTdForChart">
										{{ __('stat.statTitle.' . $k) }}
										<div class="cStatTableBarChart" id="iStatBarChart{{ $seq }}" filled="0"></div>
									</td>
								@else
									<td>{{ __('stat.statTitle.' . $k) }}</td>
								@endif

								@if (is_array($v) && count($v) == 2)
									<td class="cStatTdRight"><span class="cStatSpeed cStatKPH">{{ $set[1][$k][0] }}</span><span class="cStatSpeed cStatMPH">{{ $set[1][$k][1] }}</span></td>
								@else
									<td class="cStatTdRight">{{ $set[1][$k] }}</td>
								@endif
							@endif
						</tr>
					@endforeach
				</tbody</table>
			@endforeach
		</div>
	</div>

	<script type="text/javascript" language="javascript">

		var load_timer;

		var Charts = new Array();

		var chartData = @json($ret['ratio']);

		function resetSizeAndDraw(idx) {

			var chart = $('#iStatBarChart' + idx);
			if (chart.attr('filled') == "1") return;
			
			if (chart.height() == 0) {
				return;
			} else {
				clearInterval(load_timer);
				load_timer = null;
			}

			chart.css('width', chart.parent().parent().width() + 'px');
			chart.css('height', (chartData[idx][0].length * chart.height()) + 'px');
			chart.css('left', '-' + chart.parent().prev().innerWidth() + 'px');

			var dom;
			var option;

			dom = document.getElementById('iStatBarChart' + idx);
			Charts[idx] = echarts.init(dom);

			option = {
				color: ['transparent', '{{ Config::get('const.sideColor.home') }}', 'transparent', '{{ Config::get('const.sideColor.away') }}'],
				backgroundColor: 'rgba(255,255,255,0.01)',
				xAxis:  {
					type: 'value',
					show: false,
					min: -120,
					max: 120,
				},
				yAxis: {
					type: 'category',
					inverse: true,
					data: [],
					axisLine: {
						show: false,
					}
				},
				grid: {
					show: true,
					left: '0%',
					right: '0%',
					top: '0%',
					bottom: '0%',
					borderWidth: 0,
				},
				itemStyle: {
					normal: {
						shadowBlur: 0,
						shadowColor: 'rgba(0, 0, 0, 0.6)',
					}
				},
				animation: true,
				animationEasing: 'bounceOut',
				silent: true,
				series: [
					{
						type: 'bar',
						stack: idx + '',
						label: {normal: {show: false,}},
						barWidth: '78%',
						data: chartData[idx][0].map(function (item) {return -20;}),
						itemStyle: {color: 'transparent',},
					},
					{
						type: 'bar',
						stack: idx + '',
						label: {normal: {show: false,}},
						barWidth: '78%',
						data: chartData[idx][0],
						itemStyle: {color: '{{ Config::get('const.sideColor.home') }}',},
					},
					{
						type: 'bar',
						stack: idx + '',
						label: {normal: {show: false,}},
						data: chartData[idx][1].map(function (item) {return 20;}),
						itemStyle: {color: 'transparent',},
					},
					{
						type: 'bar',
						stack: idx + '',
						label: {normal: {show: false,}},
						data: chartData[idx][1],
						itemStyle: {color: '{{ Config::get('const.sideColor.away') }}',},
					},
				]
			};
			Charts[idx].setOption(option);
			chart.attr('filled', '1');

		};


		$(function () {

			$('.cStatSelector').on('click', function (e) {
				e.stopPropagation();

				var seq = $(this).attr('set-seq');

				$('.cStatTable').removeClass('cStatTableSelected');
				$('#iStatTable' + seq).addClass('cStatTableSelected');

				$('.cStatSelector').removeClass('cStatSelectorSelected');
				$(this).addClass('cStatSelectorSelected');

				$('.cStatSelector').removeClass('selected');
				$('.cStatSelector').addClass('unselected');
				$(this).removeClass('unselected');
				$(this).addClass('selected');


				resetSizeAndDraw(seq);
			});

			$(':radio[name=speedUnit]').on('click', function(e) {
				e.stopPropagation();
				var className = $(this).val();
				$('.cStatSpeed').hide();
				$('.' + className).show();
				$(this).parent().children('label').removeClass('selected');
				$(this).parent().children('label').addClass('unselected');
				$(this).next().removeClass('unselected');
				$(this).next().addClass('selected');
			});

			$(':radio[name=speedUnit] + label').on('click', function(e) {
				e.stopPropagation();
			});

			load_timer = setInterval('resetSizeAndDraw(0)', 1000);
		});

	</script>

@endif
