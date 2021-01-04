<script>
	$(function () {
		$('.cDrawRoadTableHolder').each(function () {
			if ($(this).position().left > 100) {
				$(this).hide();
			}
		});

	})
</script>

<div id="iDrawRoad">
	@php
		$keys = array_keys($result);
		$key_count = count($result);
		$round = $keys[0];
		$info = $result[$round];

		$itvl = 1;
	@endphp
	<div id="iDrawRoadTitle">
		{{ $name[$me] }}@if ($double){{ "/" . $name[$partner] }} @endif <span>|</span> {{ __('draw.road.road') }} <span>|</span> {{ $year }} {{ translate_tour($tourname) }} {{ __('draw.selector.' . $st) }}
	</div>

	<div id="iDrawRoadContent">
	@for ($i = 0; $i < $key_count; $i = $i + $itvl)
		{{-- 球员本人头像，以及占位图 --}}
		<table class="{{ $i > 0 ? "cDrawRoadTableHolder" : "" }}">
			<tr class="cDrawRoadLine">
				<td colspan=2>
					<div class="cDrawRoadHeadMe cDrawRoadHead{{ $double ? 2 : 1 }}">
						<div style="background-image: url({{ isset($head[$me]) ? $head[$me] : $head['0000'] }});" ><div class="cDrawRoadNameMe">{{ $seed ? '[' . $seed . ']' : '' }}{{ $name[$me] }}</div></div>
						@if ($double)
							<div style="background-image: url({{ isset($head[$partner]) ? $head[$partner] : $head['0000'] }});"><div class="cDrawRoadNameMe">{{ $seed ? '[' . $seed . ']' : '' }}{{ $name[$partner] }}</div></div>
						@endif
					</div>
				</td>
			</tr>
			<tr><td>&nbsp;</td><td class="cDrawRoadScore cDrawRoadScore{{ $i > 0 ? 2 : 1 }}">&nbsp;</td></tr>
			{{-- cDrawRoadScore1是球员头像左下格， cDrawRoadScore2是占位图右下格，cDrawRoadScore是晋级每格的右下格 --}}
		</table>

		{{-- 2个球员头像 --}}
		<table>
			<tr class="cDrawRoadLine">
				@for ($j = 0; $j < $itvl; ++$j)
					@php
						if ($i + $j >= $key_count) continue;
						$round = $keys[$i + $j];
						$info = $result[$round];
					@endphp
					<td>
						<div class="cDrawRoadHeadOppo cDrawRoadHead{{ $double ? 2 : 1 }}">
							<div style="background-image: url({{ isset($head[$info[1]]) ? $head[$info[1]] : $head['0000'] }});"><div class="{{ $info[4] == "win" ? "cDrawRoadNameWin" : ($info[4] == "lose" ? "cDrawRoadNameLose" : "cDrawRoadNameVS") }}">{{ $info[3] ? '[' . $info[3] . ']' : '' }}{{ $name[$info[1]] }}</div></div>
							@if ($double)
								<div style="background-image: url({{ isset($head[$info[2]]) ? $head[$info[2]] : $head['0000'] }});"><div class="{{ $info[4] == "win" ? "cDrawRoadNameWin" : ($info[4] == "lose" ? "cDrawRoadNameLose" : "cDrawRoadNameVS") }}">{{ $info[3] ? '[' . $info[3] . ']' : '' }}{{ $name[$info[2]] }}</div></div>
							@endif
						</div>
					</td>
				@endfor
			</tr>
			<tr>
				@for ($j = 0; $j < $itvl; ++$j)
					@php
						if ($i + $j >= $key_count) continue;
						$round = $keys[$i + $j];
						$info = $result[$round];
					@endphp
					<td class="cDrawRoadScore cDrawRoadScore{{ strtoupper($info[4]) }}">{{ translate('roundname', $round) }} {{ translate('rank.piechart', $info[4]) }} | <span>{{ $info[5] }}</span></td>
				@endfor
			</tr>
		</table>
	@endfor
	</div>
</div>
