@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.breakdown') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.breakdown') }}">
@endif
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.breakdown') }}"></script>

<div id=iBreakdown>
<!--	<div id=iBreakdownBackground style="background-image: url({{ $ret["flag_path"] }})">-->
	<div id=iBreakdownBackground>
	</div>
	<div id=iBreakdownSelectBar>
		<div id=iBreakdownSelectBarBg></div>
		<div value=2>{{ __('rank.breakdown.bytype') }}</div>
		<div value=3>{{ __('rank.breakdown.bysfc') }}</div>
		<div value=4>{{ __('rank.breakdown.bydate') }}</div>
	</div>
	<div id=iBreakdownLeft>
		<div id=iBreakdownHead style="background-image: url({{ $ret["head_path"] }})">
			<div id=iBreakdownRank>
				No.{{ $ret['rank'] }}
			</div>
		</div>
		<div id=iBreakdownBelowHead>
			<div id=iBreakdownName>
				{{ $ret['name'] }}
			</div>
			<div id=iBreakdownNation>
				{!! $ret['nation'] !!}
			</div>
			<div id=iBreakdownWLPiechart>
			</div>
		</div>
	</div>
	<div id=iBreakdownRight>
		<div id=iBreakdownContent>
			<div id=iBreakdownContentRotate>
				<div id=iBreakdownContentLevel class=cBreakdownContentBlock>
					<div id=iBreakdownContentLevelTable class=cBreakdownContentTable>
						<table>
							<tbody>
								@foreach ($ret['bylevel'] as $level => $rows)
									<tr class="cBreakdownContentTitleRow Level{{ $level }}">
										<td colspan=3>{{ __('frame.level.' . $level) }}</td>
									</tr>
									@foreach ($rows as $row)
										<tr>
											<td>{{ $row[0] }}</td>
											<td>{{ $row[1] }}</td>
											<td>{{ $row[2] }}</td>
										</tr>
									@endforeach
								@endforeach
							</tbody>
						</table>
					</div>
					@if (isset($ret['byalt']) || isset($ret['bydrop']))
						<div id=iBreakdownContentDropTable class=cBreakdownContentTable>
							<table>
								<tbody>
									@if (isset($ret['bydrop']))
										@foreach ($ret['bydrop'] as $level => $rows)
											<tr class="cBreakdownContentTitleRow Level{{ $level }}">
												<td colspan=3>{{ __('frame.level.' . $level) }}</td>
											</tr>
											@foreach ($rows as $row)
												<tr class=cBreakdownContentDropTr>
													<td>{{ $row[0] }}</td>
													<td>{{ $row[1] }}</td>
													<td>{{ $row[2] }}</td>
												</tr>
											@endforeach
										@endforeach
									@endif

									@if (isset($ret['byalt']))
										<tr class="cBreakdownContentTitleRow LevelALT">
											<td colspan=3>{{ __('frame.level.' . array_keys($ret['byalt'])[0]) }}</td>
										</tr>
										<tr>
											<td colspan=3>
												@foreach (array_values($ret['byalt'])[0] as $row)
													{{ $row[0] }}【{{ $row[1] }}】
												@endforeach
											</td>
										</tr>
									@endif
								</tbody>
							</table>
						</div>
					@endif
					<div id=iBreakdownContentLevelPieChart>

					</div>						
				</div>
				<div id=iBreakdownContentSurface class=cBreakdownContentBlock>
					<div id=iBreakdownContentSurfacePieChart>
					</div>
				</div>
				<div id=iBreakdownContentDate class=cBreakdownContentBlock>
					<div id=iBreakdownContentDropMonthPieChart>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript" language="javascript">

	var device = window.orientation === undefined || window.orientation !== 0 ? 0 : 1;
	var em = $('body').css('font-size').replace("px", "");

	$('#iBreakdownSelectBar div').on('click', function () {
		var idx = $('#iBreakdownSelectBar div').index(this);
		var width = parseInt($(this).css('width'));
		var left = 15 + (idx - 1) * width;
		$('#iBreakdownSelectBarBg').css('left', left + 'px');

		var left = (1 - idx) * 100;
		$('#iBreakdownContentRotate').css('left', left + '%');

		setCookie("rktype",$(this).attr('value'));
		$(this).parent().children().removeClass('cBreakdownSelectBarItemSelected');
		$(this).parent().children().removeClass('selected');
		$(this).parent().children().addClass('unselected');
		$(this).addClass('cBreakdownSelectBarItemSelected');
		$(this).addClass('selected');
		$(this).removeClass('unselected');

		return false;
	});

	var load_timer;

	function resetSizeAndDraw() {

		var w1 = $('#iBreakdownRight').width();
		var w2 = $('#iBreakdownContent').width();
		if (w1 <= 0 || w2 <= 0 || w1 - w2 > 1 || w1 - w2 < -1) {
			return;
		} else {
			clearInterval(load_timer);
			load_timer = null;
		}

		var width = $('.cBreakdownContentBlock').width();
		var height = $('.cBreakdownContentBlock').height();
		$('#iBreakdownContentWLPieChart').width($(this).width());
		$('#iBreakdownContentWLPieChart').height($(this).height());
		$('#iBreakdownContentLevelPieChart').width(width*0.48);
		if (device == 1) {
			$('#iBreakdownContentLevelPieChart').height(width/1.84);
		} else {
			$('#iBreakdownContentLevelPieChart').height(width*0.48);
		}
		$('#iBreakdownContentSurfacePieChart').width(width);
		$('#iBreakdownContentSurfacePieChart').height(height);
		$('#iBreakdownContentDropMonthPieChart').width(width);
		$('#iBreakdownContentDropMonthPieChart').height(height);

		var dom;
		var option;

		dom = document.getElementById('iBreakdownWLPiechart');
		var WLChart = echarts.init(dom);
		option = {
			color: ['#d9d9d9', '#005dff'],
			series: [{
				type: 'pie',
				radius: ['50%', '100%'],
				hoverAnimation: false,
				label: {
					normal: {
						show: true,
						position: 'inside',
						formatter: "{b} {c}",
						color: '#fff',
						textShadowColor: '#000',
						textShadowBlur: 5,
						fontSize: 18,
						fontWeight: 'bold',
					},
				},
/*
				itemStyle: {
					normal: {
						shadowBlur: 20,
						shadowColor: 'rgba(0, 0, 0, 0.9)',
					}
				},
*/
				data: [
					{ 'name': "{{ __('rank.piechart.lose') }}", value: {{ $ret['lose'] }} },
					{ 'name': "{{ __('rank.piechart.win') }}", value: {{ $ret['win'] }} }, 
				],
			}]
		};
		WLChart.setOption(option);

		dom = document.getElementById('iBreakdownContentLevelPieChart');
		var levelChart = echarts.init(dom);
		option = {
//			color: ['#228DAD', '#22AD83', '#896F17', '#E57289', '#FF6F3F'],
			series: [
				{
					type: 'pie',
					radius: device == 0 ? ['50%', '90%'] : ['50%', '90%'],
					hoverAnimation: false,
					startAngle: 0,
					label: {
						normal: {
							show: true,
							position: 'inside',
							formatter: "{b} {c}\n({d}%)",
							color: '#fff',
							textShadowColor: '#000',
							textShadowBlur: 5,
							fontSize: device == 0 ? em : em,
							fontWeight: device == 0 ? 'bold' : 'normal',
						},
					},
/*
					labelLine: {
						normal: {
							lineStyle: {
								color: '#000'
							},
							smooth: 1.7,
							length: device == 0 ? 8 : 5,
							length2: device == 0 ? a5 : 8,
						}
					},
*/
					itemStyle: {
						normal: {
							shadowBlur: 8,
							shadowColor: 'rgba(0, 0, 0, 0.35)',
						}
					},
					data: [
						@foreach ($ret['scoreLevel'] as $k => $v)
							@if ($v > 0)
								{ name: "{{ __('frame.level.' . $k) }}", value: {{ $v }}, itemStyle: {normal: {color: '{{ Config::get('const.levelColor.' . $k) }}'}} },
							@endif
						@endforeach
					],
				},

				{
					type: 'pie',
					radius: ['0%', '0%'],
					label: {
						normal: {
							show: true,
							position: 'center',
							formatter: "{b}\n{c}",
							fontSize: device == 0 ? 1.5 * em : 1.3 * em,
							fontWeight: device == 0 ? 'bold' : 'normal',
							color: '#888',
						},
					},
					itemStyle: {
						normal: {
							shadowBlur: 20,
							shadowColor: 'rgba(0, 0, 0, 0.9)',
						}
					},
					data: [
						{ name: "{{ __('rank.piechart.totalPoint') }}", value: {{ $ret['point'] }}, },
					],
				},
			]
		};
		levelChart.setOption(option);

		dom = document.getElementById('iBreakdownContentSurfacePieChart');
		var surfaceChart = echarts.init(dom);
		option = {
			series: [
				{
					type: 'pie',
					radius: device == 0 ? ['55%', '75%'] : ['45%', '60%'],
					hoverAnimation: false,
					startAngle: 270,
					label: {
						normal: {
							show: true,
							position: 'outside',
							formatter: "{b} {c}",
							fontSize: device == 0 ? em : em,
							fontWeight: device == 0 ? 'bold' : 'normal',
						},
						emphasis: {
							show: true,
							formatter: "{b} {c}",
							color: '#fff',
							fontSize: device == 0 ? 1.2 * em : 1.1 * em,
							padding: device == 0 ? 10 : 4,
							borderRadius: 5,
						},
					},
					labelLine: {
						normal: {
							lineStyle: {
								width: 2,
							},
							smooth: false,
							length: device == 0 ? 40 : 5,
							length2: device == 0 ? 20 : 8,
						}
					},
					itemStyle: {
						normal: {
							shadowBlur: 8,
							shadowColor: 'rgba(0, 0, 0, 0.5)',
						}
					},
					data: [
						@foreach ($ret['bysfc'] as $sfc => $rows)
							@foreach ($rows as $row)
								@if ($row[1] > 0)
									{
										name: "{{ $row[0] }}", 
										value: {{ $row[1] }}, 
										itemStyle: {
											normal: {
												color: '{{ Config::get('const.groundColor.' . $sfc) }}', 
											}
										},
										label: {
											emphasis: {
												backgroundColor: '{{ Config::get('const.groundColor.' . $sfc) }}',
											},
										},
										labelLine: {
											normal: {
												lineStyle: {
													color: '{{ Config::get('const.groundColor.' . $sfc) }}',
												}
											}
										}
									},
								@endif
							@endforeach
						@endforeach
					],
				},
				{
					type: 'pie',
					radius: ['0%', '0%'],
					label: {
						normal: {
							show: true,
							position: 'center',
							formatter: "{b}\n{c}",
							fontSize: device == 0 ? 1.5 * em : 1.3 * em,
							fontWeight: device == 0 ? 'bold' : 'normal',
							color: '#888',
						},
					},
					itemStyle: {
						normal: {
							shadowBlur: 20,
							shadowColor: 'rgba(0, 0, 0, 0.9)',
						}
					},
					data: [
						{ name: "{{ __('rank.piechart.totalPoint') }}", value: {{ $ret['point'] }}, },
					],
				},
			]
		};
		surfaceChart.setOption(option);

		dom = document.getElementById('iBreakdownContentDropMonthPieChart');
		var monthChart = echarts.init(dom);
		option = {
			grid: {
				left: '8%',
				top: '0%',
				bottom: '0%',
				right: '0%',
			},
			yAxis: {
				data: @json(array_keys($ret['scoreMonth'])),
				axisTick: {
					show: false,
				},
				axisLine: {
					show: false,
				},
				axisLabel: {
					fontSize: device == 0 ? em : em,
					fontFamily: 'Consolas',
					color: '#888',
				},
			},
			xAxis: {
				show: false,
			},
			series: [
				{
					type: 'bar',
					data: @json(array_values($ret['scoreMonth'])),
					barWidth: device == 0 ? '50%' : '50%',
					label: {
						normal: {
							show: true,
							position: 'right',
							color: '#888',
							formatter: "{c}",
							fontSize: device == 0 ? 1.2 * em : 1.2 * em,
							fontWeight: device == 0 ?'bold' : 'normal',
						}
					},
					itemStyle: {
						normal: {
							color: '#005dff',
						},
					},
				},
				{
					type: 'bar',
					barWidth: device == 0 ? '50%' : '50%',
					barGap: '0%',
					data: [
						@foreach ($ret['bydate'] as $month => $rows)
							{ 
								name: "@foreach ($rows as $row) {{ $row[0] . '【' . $row[1] . '】' }} @endforeach", 
								value: 0, 
							},
						@endforeach
					],
					label: {
						normal: {
							show: true,
							formatter: function (data) {
								return data.data.name;
							},
							position: 'right',
							fontSize: device == 0 ? em : em,
							color: '#888',
						},
					},
				},
			],
		};
		monthChart.setOption(option);

		var rktype = getCookie('rktype');
		if (rktype != null) {
			$('#iBreakdownSelectBar div:nth-child(' + rktype + ')').trigger('click');
		}
	};

	load_timer = setInterval("resetSizeAndDraw()", 1000);

</script>
