<script type="text/javascript" language="javascript" class="init">
$(function() {
    $('.unixtime').each(function () {
        $(this).html(get_local_time($(this).html()));
    });
}); 
</script>

@if ($ret['status'] < 0)
	<div id=iAjaxNotice style="text-align: left;">
		{{ $ret['errmsg'] }}
	</div>
@endif

@if ($ret['status'] > -3)
	<div id=iDrawInfo>
		@if (false && isset($ret['country']) && $ret['country'])
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
						@if (get_tour_logo_by_id_type_name($ret['eid'], $level))
							<img class='cDrawTourType' src="{{ get_tour_logo_by_id_type_name($ret['eid'], $level) }}" />
						@endif
					@endforeach
					{{ join('/', array_map(function ($v) { return translate_tour($v); }, $ret['city'])) }}
					<img class="cDrawTourType" src="{{ url(join('/', ['images', 'wt_logo', $ret['year'], $ret['eid'] . '.png'])) }}" alt="" />
				</div>
				<div id='iDrawTourDate'>{{ $ret['date'] }} {{ join('/', array_map(function ($v){ return translate('frame.ground', $v); }, $ret['surface'])) }}</div>
				<div id='iDrawTourTitle'>{{ $ret['title'] }}</div>
				<div id='iDrawTourMore'></div>
			</div>
		@endif

		<div id='iDrawYearSelector'>
			<script>
				$(function () {
					$('#iDrawYearSelect').change(function (e) {
						var href = $(this).val();
						window.location.href = href;
					});
				});
			</script>
			<select id='iDrawYearSelect'>
				<option>{{ __('draw.notice.quickTo') }}</option>
				@foreach ($ret['history'] as $item)
					<option value='{{ url(join("/", [App::getLocale(), "draw", $ret['eid'], $item[0]])) }}'>
						{{ $item[0] }}
						{{ translate_tour($item[2]) }}
						{{ '(' . join('/' , array_map(function($v) { return translate('frame.level', $v); }, $item[1])) . ')' }}
					</option>
				@endforeach
			</select>
		</div>
	</div>
@endif

@if ($ret['status'] == 0)

	@php
		$all_types = array_unique(array_map(function ($part) {
			return $part['type'];
		}, $ret['part']));
	@endphp

	<div id="iDrawPartSelector">
		@foreach ($all_types as $type)
			<div data-id="{{ $type }}">{{ translate('draw.selector', $type) }}</div>
		@endforeach
		@isset($ret['round'])
			<div data-id="PAP">{{ __('draw.notice.pointAndPrize') }}</div>
		@endisset
	</div>

	@foreach ($ret['part'] as $part)

		{{-- 一个cDrawPart 代表一个部分，比如男单，女单 --}}

		<div class="cDrawPart cDrawPart{{ substr($part['type'], -1) != "Q" ? substr($part['type'], 1, 1) : "S" }}" data-id="{{ $part['type'] }}" style="display: none">

			@if ($part['title'] != "whole")
				<div class=cDrawPartTitle>{{ translate('draw.section', $part['title']) }}</div>
			@endif

			@if ($part['KO'])

				@php $oddEven = 0; @endphp
				@for ($block_seq = 1; $block_seq <= count($part['position']); ++$block_seq)
					@php ++$oddEven; @endphp
					<table class="cDrawBlockRow Bg{{ $oddEven % 2 == 1 ? 'Odd' : 'Even' }}"><tbody><tr>
					<td style="width: {{ floor((1 - 1 / (2 * $part['rounds'] + 3)) * 50) }}%">

						{{-- 单数block --}}
						@php $block = $part['position'][$block_seq]; @endphp
						<table class="cDrawBlock cDrawBlockLeft {{ $part['blockStyle'] }}">
							@for ($i = 1 + ($block_seq - 1) * $part['block_capacity']; $i < 1 + $block_seq * $part['block_capacity']; ++$i)
								<tr>
									<td class="cDrawSeq {{ $i % 2 == 1 ? 'cDrawGridOdd' : 'cDrawGridEven' }}">{{ $i }}</td>
									@for ($j = 0; $j <= ($part['displayStyle'] == 1 ? $part['rounds'] - 1 : $part['rounds']); ++$j)
										<td class="{{ is_array(@$part['position_style'][$block_seq][$j][$i]) ? join(" ", @$part['position_style'][$block_seq][$j][$i]) : '' }}">{!! 
											is_array(@$block[$j][$i]) ? 
											join("<br>", array_map(function ($v) use ($part) {return get_flag(@$part['p_info'][$v][1]) . @$part['p_info'][$v][0] .' <pname data-id="'. $v . '" alt="' . @$part['p_info'][$v][2] . '">'. @$part['p_info'][$v][3] . '</pname>';}, @$block[$j][$i])) . ($j == $part['rounds'] && $part['title'] != 'sections' && !preg_match('/[PQ]/', $part['type']) ? '<span class=trophy>&#x1F3C6;</span>' : '') : 
											@$block[$j][$i] 
										!!}</td>
									@endfor
								</tr>
							@endfor
						</table>
					</td>
					@if ($part['displayStyle'] == 1)
						<td>
							{{-- 从中间块开始就切换至双数block --}}
							@php ++$block_seq; $block = $part['position'][$block_seq]; @endphp

							<table class="cDrawBlockMid"><tbody>
								@if (!@$block[@$part['rounds']][0] || !is_array(@$block[@$part['rounds']][0]) || !@$block[@$part['rounds']][0][0])
									<tr><td>VS</td></tr>
								@else
									@if ($part['showRound'] && !preg_match('/[PQ]/', $part['type']))
										<tr>
											<td>{{ translate('draw.section', ($block_seq / 2) . '/' . (count($part['position']) / 2)) }}</td>
										</tr>
									@endif
									<tr><td>{!! 
										is_array(@$block[@$part['rounds']][0]) ? 
										join("<br>", array_map(function ($v) use ($part) {return get_flag(@$part['p_info'][$v][1]) . @$part['p_info'][$v][0] .' <pname data-id="'. $v . '" alt="' . @$part['p_info'][$v][2] . '">'. @$part['p_info'][$v][3] . '</pname>';}, @$block[@$part['rounds']][0])) . (!preg_match('/[PQ]/', $part['type']) && count($part['position']) == 2 ? '<span class=trophy>&#x1F3C6;</span>' : '') : 
										@$block[@$part['rounds']][0] 
									!!}</td></tr>
									<tr><td class="cDrawGridScore">{!! @$block[@$part['rounds']][1] ? @$block[@$part['rounds']][1] : '&nbsp;' !!}</td></tr>
								@endif
							</tbody></table>
						</td>
						<td style="width: {{ floor((1 - 1 / (2 * $part['rounds'] + 3)) * 50) }}%">
							<table class="cDrawBlock cDrawBlockRight {{ $part['blockStyle'] }}">
								@for ($i = 1 + ($block_seq - 1) * $part['block_capacity']; $i < 1 + $block_seq * $part['block_capacity']; ++$i)
									<tr>
										@for ($j = $part['rounds'] - 1; $j >= 0; --$j)
											<td class="{{ is_array(@$part['position_style'][$block_seq][$j][$i]) ? join(" ", @$part['position_style'][$block_seq][$j][$i]) : '' }}">{!! 
												is_array(@$block[$j][$i]) ? 
												join("<br>", array_map(function ($v) use ($part) {return get_flag(@$part['p_info'][$v][1]) . @$part['p_info'][$v][0] .' <pname data-id="'. $v . '" alt="' . @$part['p_info'][$v][2] . '">'. @$part['p_info'][$v][3] . '</pname>';}, @$block[$j][$i])) : 
												@$block[$j][$i] 
											!!}</td>
										@endfor
										<td class="cDrawSeq {{ $i % 2 == 1 ? 'cDrawGridOdd' : 'cDrawGridEven' }}">{{ $i }}</td>
									</tr>
								@endfor
							</table>
						</td>
					@endif
					</tr></tbody></table><!-- end cDrawBlockRow -->
				@endfor 

			@else {{-- 如果不是KO，那就是小组赛 --}}
				@for ($block_seq = 0; $block_seq < count($part['position']); ++$block_seq)
					@php $block = $part['position'][$block_seq]; @endphp
					<table class="cDrawBlockRow {{ $part['blockStyle'] }}"><tbody>
					@for ($i = 0; $i <= $part['playerNum'][$block_seq]; ++$i)
						<tr>
						@for ($j = 0; $j <= $part['playerNum'][$block_seq]; ++$j)
							<td class="{{ is_array(@$part['position_style'][$block_seq][$i][$j]) ? join(" ", @$part['position_style'][$block_seq][$i][$j]) : '' }}">{!!
								is_array(@$block[$i][$j]) ? 
								join("<br>", array_map(function ($v) use ($part) {return get_flag(@$part['p_info'][$v][1]) . @$part['p_info'][$v][0] .' <pname data-id="'. $v . '" alt="' . @$part['p_info'][$v][2] . '"><b>'. @$part['p_info'][$v][3] . '</b></pname>';}, @$block[$i][$j])) : 
								@$block[$i][$j] 
							!!}</td>
						@endfor
						</tr>
					@endfor
					</tbody></table>
				@endfor

			@endif

		</div><!-- end part div -->

	@endforeach

	@isset($ret['round'])
		<div class="cDrawPart" data-id="PAP" style="display: none">
			@foreach ($all_types as $type)
				@isset($ret['round'][$type])
					<table class=cDrawPointAndPrizeTable data-id="{{ $type }}">
						<thead><tr><th colspan=3>{{ translate('draw.selector', $type) }}</th></tr><tr><th>{{ __('draw.section.round') }}</th><th>{{ __('draw.section.point') }}</th><th>{{ __('draw.section.prize') }}</th></tr></thead>
						<tbody>
							@foreach ($ret['round'][$type] as $k => $v)
								<tr><td>{{ $k }}</td><td>{{ $v[0] }}</td><td>{{ $v[1] }}</td></tr>
							@endforeach
						</tbody>
					</table>
				@endisset
			@endforeach
		</div>
	@endisset
@endif

