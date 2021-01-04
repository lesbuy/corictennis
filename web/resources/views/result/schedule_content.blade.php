<script type="text/javascript" language="javascript" class="init">
$(function() {
	$('#iResult{{ $date }} .cResultMatchTime').each(function () {
		$(this).html(get_local_time($(this).html(), "{{ $date }}"));
		$(this).css('visibility', 'visible');
	});
});
</script>

@foreach ($v as $courtName => $matches)
	<div class=cResultCourt court-id="{{ strtolower(preg_replace('/^.*\t|[\' \"-]/', '', $courtName)) }}">
		<div class=cResultCourtTitle>{{ explode("\t", $courtName)[1] }}</div>
		@foreach ($matches as $match)
			<div class="cResultMatch {{ $match[24] }} {{ $match[20] }}" match-id="{{ $match[0] }}" best-of="{{ $match[17] }}" match-status="{{ $match[16] }}" is-double="{{ $match[19] }}">
				<div class=cResultMatchLeft>
					<div class=cResultMatchTime>{{ $match[9] }}</div>
					<div>
						<div class=cResultMatchGender>{{ $match[1] }}</div>
						<div class=cResultMatchRound>{{ $match[2] }}</div>
					</div>
					<div class=cResultMatchDura>
						@if ($match[8])
							{!! get_icon('clock') !!} {{ $match[8] }}
						@else
							&nbsp;
						@endif
					</div>
					<div class=cResultMatchClick>
						<div class=cResultMatchStat><a href="javascript:void(0)" onclick='{!! $match[22] !!}'>{{ __('result.notice.stat') }}</a></div>
						<div class=cResultMatchDetail><a href="javascript:void(0)" onclick='{!! $match[23] !!}'>{{ __('result.notice.detail') }}</a></div>
						@if ($match[7] && $match[21])
							<div class=cResultMatchH2H><a href="javascript:void(0)" onclick='{!! $match[21] !!}'>{{ $match[7] }}</a></div>
						@endif
						@if ($match[26] && App::isLocale('zh'))
							<div class=cResultMatchH2H><a href="{{ $match[26] }}" target=_blank>{{ __('result.notice.hl') }}</a></div>
						@endif
						@if ($match[27] && App::isLocale('zh'))
							<div class=cResultMatchH2H><a href="{{ $match[27] }}" target=_blank>{{ __('result.notice.replay') }}</a></div>
						@endif
					</div>
				</div>

				<div class=cResultMatchMid>
					<div class=cResultMatchMidPointFlag {{ $match[18] != "" ? "" : "style=display:none" }}>{{ $match[18] }}</div>
					<table>
						<tr class="cResultMatchMidTableRowOdd cResultMatchMidTableRow{{ $match[10] }}">
							<td>{!! $match[5] !!}<div>
							<div {{ $match[14][0] === "" ? "class=hidden" : "" }}>{!! $match[14][0] !!}</div>
							<div {{ $match[14][1] === "" ? "class=hidden" : "" }}>{!! $match[14][1] !!}</div>
							<div {{ $match[14][2] === "" ? "class=hidden" : "" }}>{!! $match[14][2] !!}</div>
							<div {{ $match[14][3] === "" ? "class=hidden" : "" }}>{!! $match[14][3] !!}</div>
							<div {{ $match[14][4] === "" ? "class=hidden" : "" }}>{!! $match[14][4] !!}</div>
							<div>{!! $match[12] !!}</div>
							</div></td>
						</tr>
						<tr class="cResultMatchMidTableRow{{ $match[11] }}">
							<td>{!! $match[6] !!}<div>
							<div {{ $match[15][0] === "" ? "class=hidden" : "" }}>{!! $match[15][0] !!}</div>
							<div {{ $match[15][1] === "" ? "class=hidden" : "" }}>{!! $match[15][1] !!}</div>
							<div {{ $match[15][2] === "" ? "class=hidden" : "" }}>{!! $match[15][2] !!}</div>
							<div {{ $match[15][3] === "" ? "class=hidden" : "" }}>{!! $match[15][3] !!}</div>
							<div {{ $match[15][4] === "" ? "class=hidden" : "" }}>{!! $match[15][4] !!}</div>
							<div>{!! $match[13] !!}</div>
							</div></td>
						</tr>
					</table>
				</div>
			</div>
		@endforeach
	</div>
@endforeach
