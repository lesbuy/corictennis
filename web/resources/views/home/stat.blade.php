@php $theme = isset($_COOKIE['theme']) && $_COOKIE['theme'] == "dark" ? "dark" : "light"; @endphp

@if (count($ret) == 0)
	<div class=cHomeCardStatNoData><span>{{ __('home.nodata') }}</span></div>
@else
	<div class=cHomeCardStatChartTd>
		<div class=cHomeCardStatChart id=iHomeCardStatServeChart></div>
		<div class=cHomeCardStatDetail id=iHomeCardStatServeDetail>
			<table>
				<tr><td>{{ __('stat.statTitle.ace') }}</td><td>{{ round($ret['base'][1], 0) }}</td></tr>
				<tr><td>{{ __('stat.statTitle.df') }}</td><td>{{ round($ret['base'][2], 0) }}</td></tr>
				<tr><td>{{ __('stat.statTitle.s1%') }}</td><td>{{ round($ret['serve'][0], 0) }}%</td></tr>
				<tr><td>{{ __('stat.statTitle.s1') }}</td><td>{{ round($ret['serve'][1], 0) }}%</td></tr>
				<tr><td>{{ __('stat.statTitle.s2') }}</td><td>{{ round($ret['serve'][2], 0) }}%</td></tr>
				<tr><td>{{ __('stat.statTitle.s') }}</td><td>{{ round($ret['serve'][3], 0) }}%</td></tr>
				<tr><td>{{ __('stat.statTitle.bs%') }}</td><td>{{ round($ret['serve'][4], 0) }}%</td></tr>
				<tr><td>{{ __('stat.statTitle.sg%') }}</td><td>{{ round($ret['serve'][5], 0) }}%</td></tr>
			</table>
		</div>
	</div>
	<div class=cHomeCardStatChartTd>
		<div class=cHomeCardStatChart id=iHomeCardStatReturnChart></div>
		<div class=cHomeCardStatDetail id=iHomeCardStatReturnDetail>
			<table>
				<tr><td>{{ __('stat.statTitle.r1%') }}</td><td>{{ round($ret['return'][0], 0) }}%</td></tr>
				<tr><td>{{ __('stat.statTitle.r2%') }}</td><td>{{ round($ret['return'][1], 0) }}%</td></tr>
				<tr><td>{{ __('stat.statTitle.rp%') }}</td><td>{{ round($ret['return'][2], 0) }}%</td></tr>
				<tr><td>{{ __('stat.statTitle.bp%') }}</td><td>{{ round($ret['return'][3], 0) }}%</td></tr>
				<tr><td>{{ __('stat.statTitle.rg%') }}</td><td>{{ round($ret['return'][4], 0) }}%</td></tr>
			</table>
		</div>
	</div>

<script>

	var dom;
	var option;
	var chart = [];
	var data;

	function drawRadar() {

		dom = document.getElementById('iHomeCardStatServeChart');
		chart[0] = echarts.init(dom);
		option = {
			radar: {
				indicator: [
					{name: '{!! __('home.stat.s1%') !!}', max: 100},
					{name: '{!! __('home.stat.s1') !!}', max: 100},
					{name: '{!! __('home.stat.s2') !!}', max: 100},
					{name: '{!! __('home.stat.s') !!}', max: 100},
					{name: '{!! __('home.stat.bs%') !!}', max: 100},
					{name: '{!! __('home.stat.sg%') !!}', max: 100},
				],
				shape: 'polygon',
				splitNumber: 4,
				name: {
					textStyle: {
						color: '{{ Config::get('const.globalColor.sep.' . $theme) }}',
						fontSize: $('.cHomeCardStatDetail').css('font-size').replace("px", ""),
					}
				},
				nameGap: 5,
				splitLine: {
					lineStyle: {
						color: '{{ Config::get('const.globalColor.lightGray') }}',
					}
				},
				axisLine: {
					lineStyle: {
						color: '{{ Config::get('const.globalColor.lightGray') }}',
					}
				},
				splitArea: {
					show: false
				},
				startAngle: 120,
			},
			series: [
				{
					type: 'radar',
					lineStyle: {
						normal: {
							width: 0,
						}
					},
					data: [@json($ret['serve']),],
					symbol: 'none',
					itemStyle: {
						normal: {
							color: '{{ Config::get('const.globalColor.hl') }}'
						}
					},
					areaStyle: {
						normal: {
							opacity: 0.6
						}
					}
				},
			]
		};
		chart[0].setOption(option);

		dom = document.getElementById('iHomeCardStatReturnChart');
		chart[1] = echarts.init(dom);
		option = {
			radar: {
				indicator: [
					{name: '{!! __('home.stat.r1%') !!}', max: 100},
					{name: '{!! __('home.stat.r2%') !!}', max: 100},
					{name: '{!! __('home.stat.rp%') !!}', max: 100},
					{name: '{!! __('home.stat.bp%') !!}', max: 100},
					{name: '{!! __('home.stat.rg%') !!}', max: 100},
				],
				shape: 'polygon',
				splitNumber: 4,
				name: {
					textStyle: {
						color: '{{ Config::get('const.globalColor.sep.' . $theme) }}',
						fontSize: $('.cHomeCardStatDetail').css('font-size').replace("px", ""),
					}
				},
				nameGap: 5,
				splitLine: {
					lineStyle: {
						color: '{{ Config::get('const.globalColor.lightGray') }}',
					}
				},
				axisLine: {
					lineStyle: {
						color: '{{ Config::get('const.globalColor.lightGray') }}',
					}
				},
				splitArea: {
					show: false
				},
				startAngle: 162,
			},
			series: [
				{
					type: 'radar',
					lineStyle: {
						normal: {
							width: 0,
						}
					},
					data: [@json($ret['return']),],
					symbol: 'none',
					itemStyle: {
						normal: {
							color: '{{ Config::get('const.globalColor.hl') }}'
						}
					},
					areaStyle: {
						normal: {
							opacity: 0.6
						}
					}
				},
			]
		};
		chart[1].setOption(option);

	};

	drawRadar();

</script>
@endif
