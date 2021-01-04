@if ($ret['status'] < 0)
	<div id=iAjaxNotice>
		{{ $ret['errmsg'] }}
	</div>
@else
	<div id=iH2HDetail class="cH2HDetail">

		<div id=iH2HDetailHeads class="ch2hMethod{{ $ret['method'] }} ch2hDetailHeads">

			<div id=iH2HDetailInfo1 class="ch2hDetailInfo" >
				<div id=iH2HDetailWin class="SideHomeText ch2hDetailWin">{{ $ret['win'] }}<div class="ch2hPortion SideThirdBorder">{{ $ret['win'] + $ret['lose'] == 0 ? 0 : round($ret['win'] / ($ret['win'] + $ret['lose']) * 100, 0) }}%</div></div>
				<div id=iH2HDetailName1 class="ch2hDetailName">{{ $ret['name1'] }}</div>
				<div class="ch2hDetailMore">
					<div class="ch2hDetailIoc" style="background-image: url({{ url(join("/", ['images', 'flag_svg', $ret['ioc1'] . '.svg'])) }})"></div>
					<div id=iH2HDetailRank1 class="ch2hDetailRank SideThirdBorder">{!! $ret['rank1'] ? $ret['rank1'] . '&nbsp;' . '<span class="weakenColor">' . get_icon('ic_paiming') . '</span>' : "" !!}</div>
				</div>
			</div>

			<div id=iH2HDetailHead1 class="ch2hDetailHead" style="background-image: url({{ $ret['p1head'] }})">

			</div>

			<div id=iH2HDetailHead2 class="ch2hDetailHead" style="background-image: url({{ $ret['p2head'] }})">

			</div>

			<div id=iH2HDetailInfo2 class="ch2hDetailInfo" >
				<div id=iH2HDetailLoss class="SideAwayText ch2hDetailLoss">{{ $ret['lose'] }}<div class="ch2hPortion SideThirdBorder">{{ $ret['win'] + $ret['lose'] == 0 ? 0 : round($ret['lose'] / ($ret['win'] + $ret['lose']) * 100, 0) }}%</div></div>
				<div id=iH2HDetailName2 class="ch2hDetailName">{{ $ret['name2'] }}</div>
				<div class="ch2hDetailMore">
					<div id=iH2HDetailRank2 class="ch2hDetailRank SideThirdBorder">{!! $ret['rank2'] ? '<span class="weakenColor">' . get_icon('ic_paiming') . '</span>' . '&nbsp;' . $ret['rank2'] : "" !!}</div>
					<div class="ch2hDetailIoc"  style="background-image: url({{ url(join("/", ['images', 'flag_svg', $ret['ioc2'] . '.svg'])) }})"></div>
				</div>
			</div>

			<div id=iH2HDetailPieChart class="ch2hDetailPieChart"></div>
		</div>

		<div id=iH2HDetailTableDiv class="cH2HDetailTableDiv">
			<div id=iH2HDetailTableFilter class="cH2HDetailTableFilter">{{ $ret['filter'] }}</div>
			<table id=iH2HDetailTable class="cH2HDetailTable">
				@if (count($ret['matches']) > 0)
					<thead><tr>
						<td>{{ __('h2h.thead.year') }}</td><td>{{ __('h2h.thead.level') }}</td><td>{{ __('h2h.thead.surface') }}</td><td>{{ __('h2h.thead.event') }}</td><td>{{ __('h2h.thead.round') }}</td><td>{{ __('h2h.thead.result') }}</td><td>{{ __('h2h.thead.games') }}</td>
					</tr></thead>
				@endif
				<tbody>
					@foreach ($ret['matches'] as $match)
						<tr class="Side{{ $match[7] }}">
							<td>{{ $match[0] }}</td>
							<td>{{ $match[1] }}</td>
							<td>{{ $match[2] }}</td>
							<td>{{ $match[3] }}</td>
							<td>{{ $match[4] }}</td>
							<td>{{ $match[5] }}</td>
							<td>{{ $match[6] }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		</div>
		{!! Config::get('const.logostamp')() !!}
	</div>

	<script type="text/javascript" language="javascript">

		var load_timer;

		function resetSizeAndDraw() {

			clearInterval(load_timer);
			load_timer = null;

			var dom;
			var option;

			dom = document.getElementById('iH2HDetailPieChart');
			var WLChart = echarts.init(dom);
			option = {
				series: [
					{
						type: 'pie',
						radius: ['0%', '95%'],
						label: {
							normal: {
								show: true,
								position: 'center',
								formatter: "{b}",
								fontSize: device == 0 ? 4 * em : 2.5 * em,
								fontWeight: device == 0 ? 'bold' : 'normal',
								color: '#888',
							},
						},
						silent: true,
						startAngle: 0,
						data: [
							{ name: "VS", value: 0, itemStyle: { normal: { color: 'rgba(255, 255, 255, 0.9)'} } },
						],
					},
					{
						type: 'pie',
						radius: ['55%', '85%'],
						label: {
							normal: {
								show: false,
							}
						},
						animation: true,
						animationEasing: 'bounceOut',
						silent: true,
						startAngle: parseInt({{ $ret['lose'] + $ret['win'] == 0 ? 0.5 : $ret['lose'] / ($ret['lose'] + $ret['win']) }} * 180),
						itemStyle: {
							normal: {
								shadowBlur: 20,
								shadowColor: 'rgba(0, 0, 0, 0.2)',
							}
						},
						data: [
							{ 'name': "1", value: {{ $ret['lose'] }}, itemStyle: { normal: { color: '{{ Config::get('const.sideColor.away') }}' } } }, 
							{ 'name': "2", value: {{ $ret['win'] }}, itemStyle: { normal: { color: '{{ Config::get('const.sideColor.home') }}' } } },
						],
					},
				]
			};
			WLChart.setOption(option);

		};

		load_timer = setInterval('resetSizeAndDraw()', 200);

	</script>

@endif
