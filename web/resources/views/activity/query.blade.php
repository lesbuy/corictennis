@if ($ret['status'] < 0)
	<div id=iAjaxNotice>
		{{ $ret['errmsg'] }}
	</div>
@else
	<div id=iActivityDetail class="cH2HDetail">

		<div id=iActivityDetailNames class="cH2HDetailNames">
			<div id=iActivityDetailName1 class="SideHome cH2HDetailName1">{{ $ret['name1'] }}</div>
			<div id=iActivityDetailName2 class="SideAway cH2HDetailName2">{{ $ret['filter'] }}</div>
		</div>

		<div id=iActivityDetailHeads class="cH2HDetailHeads">
			<div id=iActivityDetailHead1 class="cH2HDetailHead1" style="background-image: url({{ $ret['p1head'] }})"><div id=iActivityDetailRank1 class="cH2HDetailRank1">{{ $ret['rank1'] ? 'NO.' . $ret['rank1'] : "-" }}</div></div>
			<div id=iActivityDetailWin class="SideHomeText cH2HDetailWin">{{ $ret['win'] }}<div class="cH2HPortion SideHomeBorder">{{ __('rank.piechart.win') }} {{ $ret['win'] + $ret['lose'] == 0 ? 0 : round($ret['win'] / ($ret['win'] + $ret['lose']) * 100, 0) }}%</div></div>
			<div id=iActivityDetailPieChart class="cH2HDetailPieChart"></div>
			<div id=iActivityDetailLoss class="SideAwayText cH2HDetailLoss">{{ $ret['lose'] }}<div class="cH2HPortion SideAwayBorder">{{ __('rank.piechart.lose') }} {{ $ret['win'] + $ret['lose'] == 0 ? 0 : round($ret['lose'] / ($ret['win'] + $ret['lose']) * 100, 0) }}%</div></div>
		</div>

		<div id=iActivityDetailTableDiv class="cH2HDetailTableDiv">
			<table id=iActivityDetailTable class="cH2HDetailTable"><tbody>
				@foreach ($ret['tours'] as $k1 => $tour)
					@if (count($tour['matches']) > 0)
						<tr><td colspan=4 id=iActivityDetailTour class="cActivityTour">
							<img class="cActivityLogo" src="{{ get_tour_logo_by_id_type_name($tour['eid'], $tour['level'], $tour['city']) }}" />
							<span>{{ translate_tour($tour['city']) }} ({{ translate_tour($tour['loc']) }})</span>
							<span>{{ date('Y-m-d', strtotime($tour['start_date'])) }}</span>
							<span class="cActivityInfo">
								@if ($tour['sd'] == "d")
									<span class=kv><span class="k selected cActivityPtK">{{ __('h2h.item.partner') }}</span><span class="v cActivityPtV">{{ @$tour['partner_name'] }}</span></span>
								@endif
								<span class=kv><span class="k selected cActivityPrizeK">{{ __('h2h.item.prize') }}</span><span class="v cActivityPrizeV">{{ $tour['currency'] . $tour['prize'] }}</span></span>
								<span class=kv><span class="k selected cActivityRankK">{{ __('h2h.item.rank') }}</span><span class="v cActivityRankV">{{ $tour['rank'] . ($tour['seed'] ? "[".$tour['seed']."]" : "") . ($tour['entry'] ? "[".$tour['entry']."]" : "") }}</span></span>
								<span class=kv><span class="k selected cActivitySfc">{{ translate('frame.ground', $tour['sfc']) }}</span></span>
								<span class=kv><span class="k selected cActivityPoint">{{ $tour['point'] }}</span></span>
							</span>
						</td></tr>
						@foreach ($tour['matches'] as $k2 => $match)
							<tr>
								<td>{{ @$match['round'] }}</td>
								<td>{{ @$match['wl'] }}</td>
								<td>
									{!! get_flag(@$match['oioc']) !!}
									{{ @$match['orank'] > 0 && @$match['orank'] < 9999 ? @$match['orank'] : '' }} 
									{{ @$match['oname'] }}<br/>
									{!! isset($match['opartner_ioc']) ? get_flag(@$match['opartner_ioc']) : '' !!}
									{{ isset($match['opartner_rank']) && @$match['opartner_rank'] > 0 && @$match['opartner_rank'] < 9999 ? @$match['opartner_rank'] : '' }} 
									{{ isset($match['opartner_name']) ? @$match['opartner_name'] : '' }}
								</td>
								<td>{{ @$match['games'] }}</td>
							</tr>
						@endforeach
					@endif
				@endforeach
			</tbody></table>
		</div>
	</div>

	<script type="text/javascript" language="javascript">

		var load_timer;

		function resetSizeAndDraw() {

			var width = $('#iActivityDetailHeads').width() - $('#iActivityDetailWin').width() * 2 - $('#iActivityDetailHead1').width() * 2;

			if (width <= 0) {
				return;
			} else {
				clearInterval(load_timer);
				load_timer = null;
			}

			var dom;
			var option;

			dom = document.getElementById('iActivityDetailPieChart');
			var WLChart = echarts.init(dom);
			option = {
				color: ['{{ Config::get('const.sideColor.away') }}', '{{ Config::get('const.sideColor.home') }}'],
				series: [{
					type: 'pie',
					radius: ['58%', '85%'],
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
							shadowColor: 'rgba(0, 0, 0, 0.6)',
						}
					},
					data: [
						{ 'name': "1", value: {{ $ret['lose'] }} }, 
						{ 'name': "2", value: {{ $ret['win'] }} },
					],
				}]
			};
			WLChart.setOption(option);

		};

		load_timer = setInterval('resetSizeAndDraw()', 200);

	</script>

@endif
