@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.pbp') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.pbp') }}">
@endif

@if ($ret['status'] < 0)
	<div id=iAjaxNotice>
		{{ $ret['errmsg'] }}
	</div>
@else

	<div id=iPbP>

		<div id=iPbPTables>

			<div id=iPbPNames>
				<div id=iPbPName1 class="SideHome">{{ $ret['player'][0] }}</div>
				<div id=iPbPName2 class="SideAway">{{ $ret['player'][1] }}</div>
			</div>

			<div class=cPbPHeads>
				<div class=cPbPHead style="background-image: url({{ $ret['head'][0] }})">
				</div>
				@if (isset($ret['head'][2]))
					<div class=cPbPHead style="background-image: url({{ $ret['head'][1] }})">
					</div>
				@endif
			</div>

			<div class=cPbPHeads>
				@if (isset($ret['head'][2]))
					<div class=cPbPHead style="background-image: url({{ $ret['head'][2] }})">
					</div>
				@else
					<div class=cPbPHead style="background-image: url({{ $ret['head'][1] }})">
					</div>
				@endif
				@if (isset($ret['head'][3]))
					<div class=cPbPHead style="background-image: url({{ $ret['head'][3] }})">
					</div>
				@endif
			</div>

			<table id=iPbPSelectors><tbody><tr>
				@foreach ($ret['pbp'] as $seq => $set)
					<td id="iPbPSelector{{ $seq }}" class="cPbPSelector" set-seq="{{ $seq }}">
						{{ __('stat.selector.' . $seq) }}
					</td>
				@endforeach
			</tr></tbody></table>

			<div id=iPbPDivs>
				@foreach ($ret['pbp'] as $seq => $set)
					<div id="iPbPChart{{ $seq }}" class="cPbPChart">
					</div>
				@endforeach
			</div>
		</div>

	</div>

	<script type="text/javascript" language="javascript">

		var load_timer;

		var Charts = new Array();

		var pbp = @json($ret['pbp']);

		var param = @json($ret['param']);

		var serve = @json($ret['serve']);

		function resetSizeAndDraw(idx) {

			var chart = $('#iPbPChart' + idx);
			if (chart.attr('filled') == "1") return;

			if (chart.width() < 300 || chart.width() != chart.parent().width()) {
				return;
			} else {
				clearInterval(load_timer);
				load_timer = null;
			}

			chart.css('width', chart.parent().width() + 'px');
			chart.css('height', (pbp[idx].length * 25) + 'px');

			var dom;
			var option;
			dom = document.getElementById('iPbPChart' + idx);
			Charts[idx] = echarts.init(dom);

			option = {
				tooltip: {
					trigger: 'item'
				},
				grid: {
					left: '0%',
					right: '0%',
					bottom: '0%',
					top: 0,
				},
				backgroundColor: '{{ Config::get('const.pbp.color.liveGame') }}',
				yAxis: {
					data: pbp[idx].map(function (item) {
						return item[0];
					}),
					axisTick: {
						show: false,
					},
					axisLabel: {
						show: false,
					},
					axisLine: {
						lineStyle: {
							opacity: 0.3,
						},
					},
					zlevel: 1,
					interval: 1,
					min: 0,
					max: pbp[idx].length - 1,
				},
				xAxis: {
					type: 'value',
					min: param[idx].min,
					max: param[idx].max,
					interval: 0,
					axisTick: {
						show: false,
						inside:true,
						length: 10,
					},
					axisLine: {
						show: false,
					}
				},

				series: [
					{{-- 主折线 --}}
					{
						type:'line',
						zlevel: 0,
						showAllSymbol: true,
						itemStyle: {
							normal: {
								color: '{{ Config::get('const.pbp.color.lineDot') }}',
							},
						},
						lineStyle: {
							normal: {
								color: '{{ Config::get('const.pbp.color.line') }}',
							}
						},
						label: {
							normal: {
								show: true,
								position: 'left',
								color: '{{ Config::get('const.pbp.color.lineDotLabel') }}',
								formatter: function (data) {
									return data.data.points;
								},
							},
						},
						tooltip: {
							formatter: function (data) {
								return data.data.points;
							},
						},
						data: pbp[idx].map(function (item) {
							return {
								value: item[1],
								points: item[4],
								symbolSize: item[2],
							};
						}),
						markPoint: {
							data: pbp[idx].filter(function (item) {
								return item[3].length > 0;
							}).map(function (item) {
								return {
									yAxis: item[0],
									xAxis: item[1],
									bsm_flag: item[3].join(' '),
								};
							}),
							symbol: 'circle',
							symbolRotate: 270,
							symbolSize: 10,
							label: {
								normal: {
									show: true,
									fontSize: 15,
									position: 'right',
									color: '{{ Config::get('const.pbp.color.lineBSMLabel') }}',
									formatter: function (data) {
										return data.data.bsm_flag;
									},
								},
							},
							tooltip: {
								show: false,
							},
						},
					},
					{{-- 主折线结束 --}}
					{{-- 背景区域 --}}
					{
						type:'line',
						data: param[idx].markLines.map(function (item){
							return {
								value: [0, item[1]],
								points: item[2],
							};
						}),
						showSymbol: true,
						showAllSymbol:true,
						symbolSize: 1,
						lineStyle: {
							normal: {
								width: 0,
							}
						},
						label: {
							normal: {
								show: true,
								formatter: function(data) {
									return data.data.points;
								},
								position: 'inside',
								fontSize: 20,
								color: '{{ Config::get('const.pbp.color.gameLabel') }}',
								padding : 5,
								backgroundColor: '{{ Config::get('const.pbp.color.gameLabelBg') }}',
							},
						},
						tooltip: {
							formatter: function (data) {
								return data.data.points;
							},
						},
						markArea: {
							silent: true,
							data: param[idx].markLines.map(function (item){
								return [
									{
										yAxis: item[0],
									},
									{
										yAxis: item[1],
										itemStyle: {
											normal: {
												color: item[3],
											}
										}
									}
								];
							}),
						},
						markLine: {
							silent: true,
							symbol: ['pin', 'pin'],
							data: param[idx].markLines.map(function (item){
								return {
									yAxis: item[1]
								};
							}),
							label: {
								normal: {
									show: false,
								}
							},
							lineStyle: {
								normal: {
									color: '{{ Config::get('const.pbp.color.gameLine') }}',
								}
							},
						},
					},
					{{-- 背景区域结束 --}}
					{{-- 发球标记 --}}
					{
						type: 'line',
						data: serve[idx].map(function (item){
							return {
								value: [(param[idx].max - 1) * item[4], item[0]],
								itemStyle: {
									normal: {
										color: item[1],
										borderWidth: 1,
										borderColor: '{{ Config::get('const.pbp.color.serveDotBorder') }}',
									},
								},
								label: {
									normal: {
										show: true,
										position: 'inside',
										formatter: function (data) {
											return "{{ Config::get('const.pbp.lines.serveFlag') }}";
										},
										fontSize: 18,
										color: '{{ Config::get('const.pbp.color.serveDotLabel') }}',
									}
								},
								tooltip: {
									formatter: function (data) {
										return item[2] + '<br>' + item[3];
									},
								},
							};
						}),
						lineStyle: {
							normal: {
								width: 0,
							}
						},
						symbol: 'circle',
						showSymbol: true,
						showAllSymbol:true,
						symbolSize: 25,
					},
					{{-- 发球标记结束 --}}
				],
			};

			Charts[idx].setOption(option);

			chart.attr('filled', '1');

		};

		$(function () {

			$('.cPbPSelector').on('click', function (e) {
				e.stopPropagation();

				var seq = $(this).attr('set-seq');

				$('.cPbPChart').removeClass('cPbPChartSelected');
				$('#iPbPChart' + seq).addClass('cPbPChartSelected');

				$('.cPbPSelector').removeClass('cPbPSelectorSelected');
				$(this).addClass('cPbPSelectorSelected');

				$('.cPbPSelector').removeClass('selected');
				$('.cPbPSelector').addClass('unselected');
				$(this).removeClass('unselected');
				$(this).addClass('selected');

				resetSizeAndDraw(seq);

			});

			var current_set = $('.cPbPChart').length;
			
			$('#iPbPChart' + current_set).addClass('cPbPChartSelected');
			$('#iPbPSelector' + current_set).trigger('click');

			load_timer = setInterval('resetSizeAndDraw(' + current_set + ')', 200);

		});

	</script>

@endif
