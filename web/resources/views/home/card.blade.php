@if (isset($ret['error']))
<script>
	if (rank_timer) clearInterval(rank_timer);
	if (stat_timer) clearInterval(stat_timer);
	if (ytd_timer) clearInterval(ytd_timer);
	if (career_timer) clearInterval(career_timer);
	if (honor_timer) clearInterval(honor_timer);
	if (rate_timer) clearInterval(rate_timer);
	alert("{{ $ret['error'] }}");
</script>
@else

@php $theme = isset($_COOKIE['theme']) && $_COOKIE['theme'] == "dark" ? "dark" : "light"; @endphp
<script>
	$(function () {

		year_for_stat = {{ $ret['stat']['default'] }};
		sd_for_match = "s";
		filter_for_match = "all";

		var pid = "{{ $ret['id'] }}";
		var gender = "{{ $ret['gender'] }}";

		$('.cHomeCardBasicRankSelector').on('click', function() {
			sd = $(this).attr('value');
			$('.cHomeCardBasicRankNum').hide();
			$('#iHomeCardBasicRank' + sd).show();
			$('.cHomeCardBasicRankSelector').removeClass('cHomeCardSelected');
			$(this).addClass('cHomeCardSelected');
		});

		$('.cHomeCardGSSelector').on('click', function () {
			sd = $(this).attr('value');
			$('.cHomeCardGSWinNum').hide();
			$('.cHomeCardGSLossNum').hide();
			$('.cHomeCardGSGrid').hide();
			$('#iHomeCardGSWin' + sd).show();
			$('#iHomeCardGSLoss' + sd).show();
			$('#iHomeCardGSGrid' + sd).show();
			$('.cHomeCardGSSelector').removeClass('cHomeCardSelected');
			$(this).addClass('cHomeCardSelected');
		});

		$('.cHomeCardRecentSelector').on('click', function () {
			sd = $(this).attr('value');
			$('.cHomeCardRecentGrid').hide();
			$('#iHomeCardRecentGrid' + sd).show();
			$('.cHomeCardRecentSelector').removeClass('cHomeCardSelected');
			$(this).addClass('cHomeCardSelected');
		});

		$('.cHomeCardHonorSelector').on('click', function () {
			sd = $(this).attr('value');
			$('.cHomeCardHonorGrid').hide();
			$('#iHomeCardHonorGrid' + sd).show();
			$('.cHomeCardHonorSelector').removeClass('cHomeCardSelected');
			$(this).addClass('cHomeCardSelected');
		});

		$('.cHomeCardRankSelector').on('click', function () {
			sd = $(this).attr('value');
			$('.cHomeCardRankLineChart').hide();
			$('#iHomeCardRankLineChart' + sd).show();
			$('.cHomeCardRankCHNum').hide();
			$('#iHomeCardRankCHNum' + sd).show();
			$('.cHomeCardRankSelector').removeClass('cHomeCardSelected');
			$(this).addClass('cHomeCardSelected');
		});

		@if ($ret['basic']['rank'][1] == "-")
			$('#iHomeCardBasicRS').trigger('click');
			$('#iHomeCardGSRS').trigger('click');
			$('#iHomeCardRankRS').trigger('click');
			$('#iHomeCardRecentRS').trigger('click');
			$('#iHomeCardHonorRS').trigger('click');
		@elseif ($ret['basic']['rank'][0] == "-")
			$('#iHomeCardBasicRD').trigger('click');
			$('#iHomeCardGSRD').trigger('click');
			$('#iHomeCardRankRD').trigger('click');
			$('#iHomeCardRecentRD').trigger('click');
			$('#iHomeCardHonorRD').trigger('click'); 
		@elseif ($ret['basic']['rank'][0] <= $ret['basic']['rank'][1] + 50)
			$('#iHomeCardBasicRS').trigger('click');
			$('#iHomeCardGSRS').trigger('click');
			$('#iHomeCardRankRS').trigger('click');
			$('#iHomeCardRecentRS').trigger('click');
			$('#iHomeCardHonorRS').trigger('click');
		@else
			$('#iHomeCardBasicRD').trigger('click');
			$('#iHomeCardGSRD').trigger('click');
			$('#iHomeCardRankRD').trigger('click');
			$('#iHomeCardRecentRD').trigger('click');
			$('#iHomeCardHonorRD').trigger('click');
		@endif

		$('#iHomeCardStatSelectorYear').on('change', function () {
			year_for_stat = $(this).val();
			$('#iHomeCardMask').show();
			$.ajax({
				type: 'POST',
				url: ["{{ url(App::getLocale() . "/player") }}", gender, pid, 'stat', year_for_stat].join("/"),
				success: function (data) {
					$('#iHomeCardStatContent').html(data);
					$('#iHomeCardStatSelectorYear').addClass('selected');
					$('#iHomeCardStatSelectorCareer').removeClass('selected');
					$('#iHomeCardMask').hide();
					if (stat_timer != null) {clearInterval(stat_timer); stat_timer = null};
				}
			});
		});
		$('#iHomeCardStatSelectorCareer').on('click', function () {
			$('#iHomeCardMask').show();
			$.ajax({
				type: 'POST',
				url: ["{{ url(App::getLocale() . "/player") }}", gender, pid, 'stat', 0].join("/"),
				success: function (data) {
					$('#iHomeCardStatContent').html(data);
					$('#iHomeCardStatSelectorCareer').addClass('selected');
					$('#iHomeCardStatSelectorYear').removeClass('selected');
					$('#iHomeCardMask').hide();
					if (stat_timer != null) {clearInterval(stat_timer); stat_timer = null};
				}
			});
		});

		$('.cHomeCardBasicNameAudio').on('click', function () {
			var audio = document.getElementById('audio');
			audio.play();
		});

		function refresh_match_data() {
			$('#iHomeCardMask').show();

			$.ajax({
				type: 'POST',
				url: ["{{ url(App::getLocale() . "/player") }}", gender, pid, 'match', sd_for_match, filter_for_match].join("/"),
				success: function (data) {
					$('#iHomeCardMask').hide();
					j = $.parseJSON(data);

					option = YTDChart.getOption();
					option.xAxis[0].max = j.match.ytd.T;
					option.series[0].data[0].value = j.match.ytd.T;
					option.series[0].data[1].value = j.match.ytd.T;
					option.series[0].data[2].value = j.match.ytd.T;
					option.series[0].data[3].value = j.match.ytd.T;
					option.series[1].data[0].value = j.match.ytd.T;
					option.series[1].data[1].value = j.match.ytd.W;
					option.series[1].data[2].value = j.match.ytd.L;
					option.series[1].data[3].value = j.match.ytd.W;
					$('.cHomeCardMatchText4[for=ytd]').html([j.match.ytd.T, j.match.ytd.W, j.match.ytd.L, j.match.ytd.T == 0 ? '0%' : Math.round(j.match.ytd.W / j.match.ytd.T * 100) + "%"].join("<br>"));
					YTDChart.setOption(option);

					option = CareerChart.getOption();
					option.xAxis[0].max = j.match.career.T;
					option.series[0].data[0].value = j.match.career.T;
					option.series[0].data[1].value = j.match.career.T;
					option.series[0].data[2].value = j.match.career.T;
					option.series[0].data[3].value = j.match.career.T;
					option.series[1].data[0].value = j.match.career.T;
					option.series[1].data[1].value = j.match.career.W;
					option.series[1].data[2].value = j.match.career.L;
					option.series[1].data[3].value = j.match.career.W;
					$('.cHomeCardMatchText4[for=career]').html([j.match.career.T, j.match.career.W, j.match.career.L, j.match.career.T == 0 ? '0%' : Math.round(j.match.career.W / j.match.career.T * 100) + "%"].join("<br>"));
					CareerChart.setOption(option);

				}
			});

		};

		$(':radio[name=filter]').on('click', function() {
			filter_for_match = $(this).val();
			refresh_match_data();
		});

		$('.cHomeCardMatchSelector').on('click', function() {
			sd_for_match = $(this).attr('value');
			refresh_match_data();
			$('.cHomeCardMatchSelector').removeClass('cHomeCardSelected');
			$(this).addClass('cHomeCardSelected');

			$('.cHomeCardMatchPrize').hide();
			$('#iHomeCardMatchPrize' + sd_for_match).show();
		});

	});
</script>


<div class=cHomeCard id=iHomeCardBasic>
	<div id=iHomeCardBasicHead style="background-image: url({{ $ret['basic']['head'] }})"></div>
	<div id=iHomeCardBasicInfo>
		<div id=iHomeCardBasicName class="{{ $ret['basic']['audio'] ? "cHomeCardBasicNameAudio" : "" }}">{!! 
			($ret['basic']['audio'] ? get_icon('shengyin') . "&nbsp;&nbsp;" : "") . $ret['basic']['name'] 
		!!}</div>
		@if ($ret['basic']['audio'])
			<audio id="audio" controls name="media" style="display: none">
				<source src="{{ url(join("/", ['audio', $ret['basic']['audio']])) }}" type="audio/mpeg"></source>
			</audio>
		@endif
		<table id=iHomeCardBasicDetail>
			<tr><td>{{ __('home.basic.age') }}</td><td>{{ $ret['basic']['age'][0] }}<span>{{ $ret['basic']['age'][1] }}</span></td></tr>
			<tr><td>{{ __('home.basic.height') }}</td><td>{{ $ret['basic']['height'][0] }}<span>{{ $ret['basic']['height'][1] }}</span></td></tr>
			<tr><td>{{ __('home.basic.pro') }}</td><td>{{ $ret['basic']['proyear'] }}</td></tr>
			<tr><td>{{ __('home.basic.play') }}</td><td>{{ $ret['basic']['play'][0] }}<span>{{ $ret['basic']['play'][1] }}</span></td></tr>
			<tr><td>{{ __('home.basic.residence') }}</td><td>{{ $ret['basic']['residence'] }}</td></tr>
			<tr><td>{{ __('home.basic.birthplace') }}</td><td>{{ $ret['basic']['birthplace'] }}</td></tr>
		</table>
	</div>
	<div id=iHomeCardBasicRank>
		<div id=iHomeCardBasicRankCurrent>{{ __('home.basic.current') }}</div>
		<div id=iHomeCardBasicRankSingle class=cHomeCardBasicRankNum>{{ $ret['basic']['rank'][0] }}</div>
		<div id=iHomeCardBasicRankDouble class=cHomeCardBasicRankNum>{{ $ret['basic']['rank'][1] }}</div>
		<div id=iHomeCardBasicRS class=cHomeCardBasicRankSelector value=Single>{{ __('home.basic.s') }}</div>
		<div id=iHomeCardBasicRD class=cHomeCardBasicRankSelector value=Double>{{ __('home.basic.d') }}</div>
		<div id=iHomeCardBasicCountry>{{ $ret['basic']['country'] }}</div>
		<div id=iHomeCardBasicFlag style="background-image:url({{ get_flag_url($ret['basic']['ioc']) }})"></div>
	</div>
</div>



<div class=cHomeCard id=iHomeCardMatch>
	<div id=iHomeCardMatchLeft>
		<div class="cHomeCardTopName" id=iHomeCardMatchName>{{ $ret['basic']['name'] }} <pname alt="{!! __('home.more.match') !!}">{!! get_icon('beizhu') !!}</pname></div>
		<div id=iHomeCardMatchRS class="cHomeCardTopSelector cHomeCardTopSelectorLeft cHomeCardMatchSelector cHomeCardSelected" value=s>{{ __('home.basic.s') }}</div>
		<div id=iHomeCardMatchRD class="cHomeCardTopSelector cHomeCardMatchSelector" value=d>{{ __('home.basic.d') }}</div>

		<div class=cHomeCardMatchText3>{{ __('home.match.ytd') }}</div>
		<div class=cHomeCardMatchText3>{{ __('home.match.career') }}</div>

		@foreach (['s', 'd'] as $sd)
			<div class=cHomeCardMatchPrize id=iHomeCardMatchPrize{{ $sd }}>
				<table><tbody>
					<tr>
						<td>{!! get_icon('creditcard') !!} {{ __('home.match.prize') }} {{ $ret['match']['prize']['ytd'] }}<br><span class="cHomeCardMatchPrizeLight weakenColor">{{ __('home.match.pct_of_career') }} {{ $ret['match']['prize']['career'] == 0 ? '0%' : round($ret['match']['prize']['ytd'] / $ret['match']['prize']['career'] * 100, 1) . "%" }}</span></td>
						<td>{!! get_icon('tubiaozhizuomoban') !!} {{ __('home.match.title') }} {{ $ret['match']['title'][$sd]['ytd'] }}<br><span class="cHomeCardMatchPrizeLight weakenColor">{{ __('home.match.pct_of_career') }} {{ $ret['match']['title'][$sd]['career'] == 0 ? '0%' : round($ret['match']['title'][$sd]['ytd'] / $ret['match']['title'][$sd]['career'] * 100, 1) . "%" }}</span></td>
					</tr>
					<tr><td colspan=2>{!! get_icon('tubiao115') !!} {{ __('home.match.ytd_high') }} {{ $ret['rank']['ytdh'][strtoupper($sd)] }}<span class="cHomeCardMatchPrizeLight weakenColor">{{ $ret['rank']['ytdhdate'][strtoupper($sd)] != "-" ? date(__('home.match.date_format'), strtotime($ret['rank']['ytdhdate'][strtoupper($sd)])) : "" }}</span></td></tr>
				</tbody></table>
				<table><tbody>
					<tr>
						<td>{!! get_icon('creditcard') !!} {{ __('home.match.prize') }} {{ $ret['match']['prize']['career'] }}</td>
						<td>{!! get_icon('tubiaozhizuomoban') !!} {{ __('home.match.title') }} {{ $ret['match']['title'][$sd]['career'] }}</td>
					</tr>
					<tr><td colspan=2>{!! get_icon('tubiao115') !!} {{ __('home.match.career_high') }} {{ $ret['rank']['ch'][strtoupper($sd)] }} <span class="cHomeCardMatchPrizeLight weakenColor">{{ date(__('home.match.date_format'), strtotime($ret['rank']['chdate'][strtoupper($sd)])) }}</span><br><span class="cHomeCardMatchPrizeLight weakenColor">{{ __('home.match.weeks', ['p1' => $ret['rank']['chdura'][strtoupper($sd)]]) }}</span></td></tr>
				</tbody></table>
			</div>
		@endforeach

		<div class=cHomeCardMatchChartHalf>
			<div class=cHomeCardMatchText2>{{ __('home.match.matches') }}<br>{{ __('home.match.win') }}<br>{{ __('home.match.loss') }}<br>{{ __('home.match.winrate') }}</div>
			<div id=iHomeCardMatchYTDChart></div>
			<div class=cHomeCardMatchText4 for=ytd>{{ $ret['match']['count']['ytd'][0] }}<br>{{ $ret['match']['count']['ytd'][1] }}<br>{{ $ret['match']['count']['ytd'][2] }}<br>{{ $ret['match']['count']['ytd'][0] == 0 ? "0%" : round($ret['match']['count']['ytd'][1] / $ret['match']['count']['ytd'][0] * 100, 0) . "%" }}</div>
		</div>
		<div class=cHomeCardMatchChartHalf>
			<div class=cHomeCardMatchText2>{{ __('home.match.matches') }}<br>{{ __('home.match.win') }}<br>{{ __('home.match.loss') }}<br>{{ __('home.match.winrate') }}</div>
			<div id=iHomeCardMatchCareerChart></div>
			<div class=cHomeCardMatchText4 for=career>{{ $ret['match']['count']['career'][0] }}<br>{{ $ret['match']['count']['career'][1] }}<br>{{ $ret['match']['count']['career'][2] }}<br>{{ $ret['match']['count']['career'][0] == 0 ? "0%" : round($ret['match']['count']['career'][1] / $ret['match']['count']['career'][0] * 100, 0) . "%" }}</div>
		</div>
	</div>
	<div id=iHomeCardMatchRight>
		<input type=radio name=filter id=iHomeCardMatchAll value=all checked></input><label class="cHomeCardMatchFilter cHomeCardMatchFilterLine unselected" for=iHomeCardMatchAll>{{ __('home.match.all') }}</label>
		<input type=radio name=filter id=iHomeCardMatchGS value=gs></input><label class="cHomeCardMatchFilter cHomeCardMatchFilterLine unselected" for=iHomeCardMatchGS>{{ __('home.match.gs') }}</label>
		<input type=radio name=filter id=iHomeCardMatchMS value=ms></input><label class="cHomeCardMatchFilter cHomeCardMatchFilterLine unselected" for=iHomeCardMatchMS>{{ __('home.match.ms') }}</label>
		<input type=radio name=filter id=iHomeCardMatchAO value=ao></input><label class="cHomeCardMatchFilter unselected" for=iHomeCardMatchAO>{{ __('home.match.ao') }}</label>
		<input type=radio name=filter id=iHomeCardMatchRG value=rg></input><label class="cHomeCardMatchFilter unselected" for=iHomeCardMatchRG>{{ __('home.match.rg') }}</label>
		<input type=radio name=filter id=iHomeCardMatchWC value=wc></input><label class="cHomeCardMatchFilter unselected" for=iHomeCardMatchWC>{{ __('home.match.wc') }}</label>
		<input type=radio name=filter id=iHomeCardMatchUO value=uo></input><label class="cHomeCardMatchFilter unselected" for=iHomeCardMatchUO>{{ __('home.match.uo') }}</label>
		<input type=radio name=filter id=iHomeCardMatchYEC value=yec></input><label class="cHomeCardMatchFilter unselected" for=iHomeCardMatchYEC>{{ __('home.match.yec') }}</label>
		<input type=radio name=filter id=iHomeCardMatchOL value=ol></input><label class="cHomeCardMatchFilter unselected" for=iHomeCardMatchOL>{{ __('home.match.ol') }}</label>
		<input type=radio name=filter id=iHomeCardMatchDC value=dc></input><label class="cHomeCardMatchFilter unselected" for=iHomeCardMatchDC>{{ __('home.match.dc') }}</label>
		<input type=radio name=filter id=iHomeCardMatchFC value=fc></input><label class="cHomeCardMatchFilter unselected" for=iHomeCardMatchFC>{{ __('home.match.fc') }}</label>
		<input type=radio name=filter id=iHomeCardMatchHard value=hard></input><label class="cHomeCardMatchFilter unselected" for=iHomeCardMatchHard>{{ __('home.match.hard') }}</label>
		<input type=radio name=filter id=iHomeCardMatchClay value=clay></input><label class="cHomeCardMatchFilter unselected" for=iHomeCardMatchClay>{{ __('home.match.clay') }}</label>
		<input type=radio name=filter id=iHomeCardMatchGrass value=grass></input><label class="cHomeCardMatchFilter unselected" for=iHomeCardMatchGrass>{{ __('home.match.grass') }}</label>
		<input type=radio name=filter id=iHomeCardMatchCarpet value=carpet></input><label class="cHomeCardMatchFilter unselected" for=iHomeCardMatchCarpet>{{ __('home.match.carpet') }}</label>
	</div>
</div>



<div class=cHomeCard id=iHomeCardStat>
	<div class="cHomeCardTopName" id=iHomeCardStatName>{{ $ret['basic']['name'] }} <pname alt="{!! __('home.more.stat') !!}">{!! get_icon('beizhu') !!}</pname></div>
	<div id=iHomeCardStatSelectorCareer class="unselected cHomeCardStatSelector" value=0 {{ !$ret['stat']['career'] ? "disabled" : "" }}>{{ $ret['stat']['career'] ? __('home.stat.career') : "" }}</div>
	<select id=iHomeCardStatSelectorYear class="unselected cHomeCardStatSelector">
		@for ($i = $ret['stat']['default']; $i >= $ret['stat']['start']; --$i)
			<option value={{ $i }}>{{ __('home.stat.season', ['p1' => $i]) }}</option>
		@endfor
	</select>
	<div class=cHomeCardStatTitle>{{ __('home.stat.serve') }}</div>
	<div class=cHomeCardStatTitle>{{ __('home.stat.return') }}</div>

	<div id=iHomeCardStatContent></div>
</div>



<div class=cHomeCard id=iHomeCardGS>
	<div class="cHomeCardTopName" id=iHomeCardGSName>{{ $ret['basic']['name'] }} <pname alt="{!! __('home.more.gs') !!}">{!! get_icon('beizhu') !!}</pname>
		&nbsp;
		<span id=iHomeCardGSWL class="cHomeCardTopTip">
			{{ __('home.gs.total_win') }} <span id=iHomeCardGSWinSingle class=cHomeCardGSWinNum>{{ @$ret['gs']['all']['all']['S']['win'] + 0 }}</span><span id=iHomeCardGSWinDouble class=cHomeCardGSWinNum>{{ @$ret['gs']['all']['all']['D']['win'] + 0 }}</span>
			{{ __('home.gs.total_loss') }} <span id=iHomeCardGSLossSingle class=cHomeCardGSLossNum>{{ @$ret['gs']['all']['all']['S']['loss'] + 0 }}</span><span id=iHomeCardGSLossDouble class=cHomeCardGSLossNum>{{ @$ret['gs']['all']['all']['D']['loss'] + 0 }}</span>
		</span>
	</div>
	<div id=iHomeCardGSRS class="cHomeCardTopSelector cHomeCardTopSelectorLeft cHomeCardGSSelector" value=Single>{{ __('home.basic.s') }}</div>
	<div id=iHomeCardGSRD class="cHomeCardTopSelector cHomeCardGSSelector" value=Double>{{ __('home.basic.d') }}</div>
	
	@foreach (['Single', 'Double'] as $sd)
		<table class=cHomeCardGSGrid id=iHomeCardGSGrid{{ $sd }}>
			<tr id=iHomeCardGSGridHead><td></td>
				@for ($i = $ret['gs']['info'][0]; $i <= $ret['gs']['info'][1]; ++$i)
					<td>{{ date('\'y', strtotime($i . "-1-1")) }}</td>
				@endfor
			</tr>
			@foreach (['AO', 'RG', 'WC', 'UO'] as $eid)
				<tr><td>{!! $eid . "<span class=weakenColor>" . (@$ret['gs']['all'][$eid][substr($sd, 0, 1)]['win'] + 0) . "-" . (@$ret['gs']['all'][$eid][substr($sd, 0, 1)]['loss'] + 0) . "</span>" !!}</td>
					@for ($i = $ret['gs']['info'][0]; $i <= $ret['gs']['info'][1]; ++$i)
						<td {{ in_array(@$ret['gs']['detail'][$i][$eid][substr($sd, 0, 1)]['round'], ['W','F','SF','QF']) ? "class=cHomeCardGSRound" . @$ret['gs']['detail'][$i][$eid][substr($sd, 0, 1)]['round'] : "" }}>{{ @$ret['gs']['detail'][$i][$eid][substr($sd, 0, 1)]['round'] }}</td>
					@endfor
				</tr>
			@endforeach
		</table>
	@endforeach
</div>


<div class=cHomeCard id=iHomeCardRecent>
	<div class="cHomeCardTopName" id=iHomeCardRecentName>{{ $ret['basic']['name'] }}</div>
	<div id=iHomeCardRecentRS class="cHomeCardTopSelector cHomeCardTopSelectorLeft cHomeCardRecentSelector" value=Single>{{ __('home.basic.s') }}</div>
	<div id=iHomeCardRecentRD class="cHomeCardTopSelector cHomeCardRecentSelector" value=Double>{{ __('home.basic.d') }}</div>
	
	@foreach (['Single', 'Double'] as $sd)
		<div class=cHomeCardRecentGrid id=iHomeCardRecentGrid{{ $sd }}>
			@foreach ($ret['recent'][substr($sd, 0, 1)] as $match)
				<div class="cHomeCardRecentGridMatch {{ $match[8] == "L" ? "text-left" : "text-right" }}">
					@if ($match[8] == "W") <div class="cHomeCardRecentGridWinLoss Statuswinner">{{ __('home.recent.win') }}</div> @endif
					@if ($match[8] == "") <div class="cHomeCardRecentGridWinLoss Statuswinner">{!! get_icon('-jijiangkaishi') !!}</div> @endif
					@foreach ($match[5][1] as $person)
						<div class="cHomeCardRecentGridHead" style="background-image: url({{ $person[3] }})"></div>
					@endforeach
					<div class="cHomeCardRecentGridMiddle">
						<table><tr>
							<td>{!! join("<br>", array_map(function ($person) use ($match) {return get_flag($person[1]) . $person[2] . ($match[5][0] != "" ? " [".$match[5][0]."]" : ""); }, $match[5][1])) !!}</td>
							<td>{{ $match[8] != "" ? $match[7] : __('home.recent.upcoming') }}</td>
							<td>{!! join("<br>", array_map(function ($person) use ($match) {return get_flag($person[1]) . $person[2] . ($match[6][0] != "" ? " [".$match[6][0]."]" : ""); }, $match[6][1])) !!}</td>
						</tr><tr class="weakenColor">
							<td>{{ $match[1] }}</td>
							<td>{{ $match[4] }}</td>
							<td>{{ date('Y-m-d', strtotime($match[0])) }}</td>
						</tr></table>
					</div>
					@foreach ($match[6][1] as $person)
						<div class="cHomeCardRecentGridHead" style="background-image: url({{ $person[3] }})"></div>
					@endforeach
					@if ($match[8] == "L") <div class="cHomeCardRecentGridWinLoss Statusloser">{{ __('home.recent.loss') }}</div> @endif
				</div>
			@endforeach
		</div>
	@endforeach

</div>


<div class=cHomeCard id=iHomeCardHonor>
	<div class="cHomeCardTopName" id=iHomeCardHonorName>{{ $ret['basic']['name'] }}</div>
	<div id=iHomeCardHonorRS class="cHomeCardTopSelector cHomeCardTopSelectorLeft cHomeCardHonorSelector" value=Single>{{ __('home.basic.s') }}</div>
	<div id=iHomeCardHonorRD class="cHomeCardTopSelector cHomeCardHonorSelector" value=Double>{{ __('home.basic.d') }}</div>
	
	@foreach (['Single', 'Double'] as $sd)
		<div class=cHomeCardHonorGrid id=iHomeCardHonorGrid{{ $sd }}>
			<div class="cHomeCardHonorGridRankPoint">
				<div class="cHomeCardHonorGridRankPointTitle weakenColor">{{ __('home.honor.rank') }}</div>
				<div class="{{ $ret['rank']['ch'][substr($sd, 0, 1)] != "-" && $ret['rank']['ch'][substr($sd, 0, 1)] <= 1 ? "cHomeCardHonorGridRankPointLight" : "cHomeCardHonorGridRankPointGray" }}">NO.1</div>
				<div class="cHomeCardHonorGridRankPointTwoLines {{ $ret['rank']['ch'][substr($sd, 0, 1)] != "-" && $ret['rank']['ch'][substr($sd, 0, 1)] <= 5 ? "cHomeCardHonorGridRankPointLight" : "cHomeCardHonorGridRankPointGray" }}">TOP<br>5</div>
				<div class="cHomeCardHonorGridRankPointTwoLines {{ $ret['rank']['ch'][substr($sd, 0, 1)] != "-" && $ret['rank']['ch'][substr($sd, 0, 1)] <= 10 ? "cHomeCardHonorGridRankPointLight" : "cHomeCardHonorGridRankPointGray" }}">TOP<br>10</div>
				<div class="cHomeCardHonorGridRankPointTwoLines {{ $ret['rank']['ch'][substr($sd, 0, 1)] != "-" && $ret['rank']['ch'][substr($sd, 0, 1)] <= 50 ? "cHomeCardHonorGridRankPointLight" : "cHomeCardHonorGridRankPointGray" }}">TOP<br>50</div>
				<div class="cHomeCardHonorGridRankPointTwoLines {{ $ret['rank']['ch'][substr($sd, 0, 1)] != "-" && $ret['rank']['ch'][substr($sd, 0, 1)] <= 100 ? "cHomeCardHonorGridRankPointLight" : "cHomeCardHonorGridRankPointGray" }}">TOP<br>100</div>
				<div class="cHomeCardHonorGridRankPointTitle weakenColor">{{ __('home.honor.point') }} <pname alt="{!! __('home.more.point') !!}">{!! get_icon('beizhu') !!}</pname></div>
				<div class="{{ $ret['rank']['maxpoint'][substr($sd, 0, 1)] >= 10000 ? "cHomeCardHonorGridRankPointLight" : "cHomeCardHonorGridRankPointGray" }}">10000</div>
				<div class="{{ $ret['rank']['maxpoint'][substr($sd, 0, 1)] >= 6000 ? "cHomeCardHonorGridRankPointLight" : "cHomeCardHonorGridRankPointGray" }}">6000</div>
				<div class="{{ $ret['rank']['maxpoint'][substr($sd, 0, 1)] >= 3000 ? "cHomeCardHonorGridRankPointLight" : "cHomeCardHonorGridRankPointGray" }}">3000</div>
				<div class="{{ $ret['rank']['maxpoint'][substr($sd, 0, 1)] >= 1000 ? "cHomeCardHonorGridRankPointLight" : "cHomeCardHonorGridRankPointGray" }}">1000</div>
				<div class="{{ $ret['rank']['maxpoint'][substr($sd, 0, 1)] >= 500 ? "cHomeCardHonorGridRankPointLight" : "cHomeCardHonorGridRankPointGray" }}">500</div>
			</div>

			@php $win_titles = $ret['honor'][substr($sd, 0, 1)][0]; @endphp
			<table class=cHomeCardHonorGridTable><tbody>
				<tr class="cHomeCardHonorGridTableTitle">
					<td colspan=5><div class="weakenColor">{!! __('home.honor.W') !!} <pname alt="{!! __('home.more.W') !!}">{!! get_icon('beizhu') !!}</pname></div></td>
					<td colspan=2><div class="weakenColor">{!! __('home.honor.tour') !!} <pname alt="{!! __('home.more.tour.' . $ret['gender']) !!}">{!! get_icon('beizhu') !!}</pname></div></td>
				</tr>
				<tr>
					<td class="{{ $win_titles['W']['AO'][0] == 0 ? 'gray' : '' }}">
						<img src="{!! get_tour_logo_by_id_type_name('AO', 'GS') !!}" />
						@if ($win_titles['W']['AO'][0] > 0)
							<span class="cHomeCardHonorGSWNum selected">{{ $win_titles['W']['AO'][0] }}</span>
						@endif
						@if ($win_titles['F']['AO'][0] > 0)
							<span class="cHomeCardHonorGSFNum unselected">{{ $win_titles['F']['AO'][0] }}</span>
						@endif
					</td>
					<td class="{{ $win_titles['W']['RG'][0] == 0 ? 'gray' : '' }}">
						<img src="{!! get_tour_logo_by_id_type_name('RG', 'GS') !!}" />
						@if ($win_titles['W']['RG'][0] > 0)
							<span class="cHomeCardHonorGSWNum selected">{{ $win_titles['W']['RG'][0] }}</span>
						@endif
						@if ($win_titles['F']['RG'][0] > 0)
							<span class="cHomeCardHonorGSFNum unselected">{{ $win_titles['F']['RG'][0] }}</span>
						@endif
					</td>
					<td class="{{ $win_titles['W']['WC'][0] == 0 ? 'gray' : '' }}">
						<img src="{!! get_tour_logo_by_id_type_name('WC', 'GS') !!}" />
						@if ($win_titles['W']['WC'][0] > 0)
							<span class="cHomeCardHonorGSWNum selected">{{ $win_titles['W']['WC'][0] }}</span>
						@endif
						@if ($win_titles['F']['WC'][0] > 0)
							<span class="cHomeCardHonorGSFNum unselected">{{ $win_titles['F']['WC'][0] }}</span>
						@endif
					</td>
					<td class="{{ $win_titles['W']['UO'][0] == 0 ? 'gray' : '' }}">
						<img src="{!! get_tour_logo_by_id_type_name('UO', 'GS') !!}" />
						@if ($win_titles['W']['UO'][0] > 0)
							<span class="cHomeCardHonorGSWNum selected">{{ $win_titles['W']['UO'][0] }}</span>
						@endif
						@if ($win_titles['F']['UO'][0] > 0)
							<span class="cHomeCardHonorGSFNum unselected">{{ $win_titles['F']['UO'][0] }}</span>
						@endif
					</td>
					<td class="{{ $win_titles['W']['OL'][0] == 0 ? 'gray' : '' }}">
						<img src="{!! get_tour_logo_by_id_type_name('OL', 'GS') !!}" />
						@if ($win_titles['W']['OL'][0] > 0)
							<span class="cHomeCardHonorGSWNum selected">{{ $win_titles['W']['OL'][0] }}</span>
						@endif
						@if ($win_titles['F']['OL'][0] > 0)
							<span class="cHomeCardHonorGSFNum unselected">{{ $win_titles['F']['OL'][0] }}</span>
						@endif
					</td>
					<td rowspan=3 class="text-right cHomeCardHonorGridChartText">
						<div>{{ __('home.honor.level.gs') }}</div> 
						<div>{{ __('home.honor.level.1000.' . $ret['gender']) }}</div> 
						<div>{{ __('home.honor.level.500.' . $ret['gender']) }}</div> 
						<div>{{ __('home.honor.level.250.' . $ret['gender']) }}</div> 
						<div>{{ __('home.honor.level.ol') }}</div> 
						<div>{{ __('home.honor.level.yec') }}</div> 
						<div>{{ __('home.honor.level.tour') }}</div> 
						<div>{{ __('home.honor.level.nontour') }}</div> 
					</td>
					<td rowspan=3 class="cHomeCardHonorGridChart cHomeCardHonorGridChartText" id=iHomeCardHonorGridChart{{ $sd }}>
					</td>
				</tr>
				<tr>
					<td><div class="LevelYEC">{{ __('home.honor.level.yec') }}<br>{{ $win_titles['W']['YEC'][0] }}</div></td>
					<td><div class="Level1000">{{ __('home.honor.level.1000.' . $ret['gender']) }}<br>{{ $win_titles['W']['1000'][0] }}</div></td>
					<td><div class="Level500">{{ __('home.honor.level.500.' . $ret['gender']) }}<br>{{ $win_titles['W']['500'][0] }}</div></td>
					<td><div class="Level250">{{ __('home.honor.level.250.' . $ret['gender']) }}<br>{{ $win_titles['W']['250'][0] }}</div></td>
					<td><div class="LevelTOUR">{{ __('home.honor.level.tour') }}<br>{{ $win_titles['W']['TOUR'][0] }}</div></td>
				</tr>
				<tr>
					<td><div class="SurfaceHard">{{ __('frame.ground.Hard') }}<br>{{ $win_titles['W']['Hard'][0] }}</div></td>
					<td><div class="SurfaceClay">{{ __('frame.ground.Clay') }}<br>{{ $win_titles['W']['Clay'][0] }}</div></td>
					<td><div class="SurfaceGrass">{{ __('frame.ground.Grass') }}<br>{{ $win_titles['W']['Grass'][0] }}</div></td>
					<td><div class="SurfaceCarpet">{{ __('frame.ground.Carpet') }}<br>{{ $win_titles['W']['Carpet'][0] }}</div></td>
					<td><div class="LevelCH">{{ __('home.honor.level.nontour') }}<br>{{ $win_titles['W']['NONTOUR'][0] }}</div></td>
				</tr>
			</tbody></table>

		</div>
	@endforeach

</div>


<div class=cHomeCard id=iHomeCardRate>
	<div class="cHomeCardTopName" id=iHomeCardRateName>{{ $ret['basic']['name'] }} <pname alt="{!! __('home.more.rate') !!}">{!! get_icon('beizhu') !!}</pname>
		&nbsp;
		<span id="iHomeCardRateLegend">
			<span style="background-color: {{ Config::get('const.globalColor.diff') }}">&nbsp;&nbsp;&nbsp;&nbsp;</span><span>{{ __('home.rate.legend.win') }}</span>
			<span style="background-color: {{ Config::get('const.globalColor.win') }}">&nbsp;&nbsp;&nbsp;&nbsp;</span><span>{{ __('home.rate.legend.loss') }}</span>
			<span style="background-color: {{ Config::get('const.globalColor.hl') }}">&nbsp;&nbsp;&nbsp;&nbsp;</span><span>{{ __('home.rate.legend.winrate') }}</span>
			<span style="background-color: {{ Config::get('const.globalColor.sep.' . $theme) }}">&nbsp;&nbsp;&nbsp;&nbsp;</span><span>{{ __('home.rate.legend.50%') }}</span>
		</span>
	</div>
	<div class=cHomeCardRateChart id=iHomeCardRateChart></div>
</div>



<div class=cHomeCard id=iHomeCardRank>
	<div class="cHomeCardTopName" id=iHomeCardRankName>{{ $ret['basic']['name'] }} <pname alt="{!! __('home.more.rank') !!}">{!! get_icon('beizhu') !!}</pname>
		<span id=iHomeCardRankCH class="cHomeCardTopTip">
			{{ __('home.rank.ch') }} <span id=iHomeCardRankCHNumSingle class=cHomeCardRankCHNum>{{ $ret['rank']['ch']['S'] }}<span>{{ $ret['rank']['chdate']['S'] }}</span><span>{{ __('home.rank.weeks', ['p1' => $ret['rank']['chdura']['S']]) }}</span></span><span id=iHomeCardRankCHNumDouble class=cHomeCardRankCHNum>{{ $ret['rank']['ch']['D'] }}<span>{{ $ret['rank']['chdate']['D'] }}</span><span>{{ __('home.rank.weeks', ['p1' => $ret['rank']['chdura']['D']]) }}</span></span>
		</span>
	</div>
	<div id=iHomeCardRankRS class="cHomeCardTopSelector cHomeCardTopSelectorLeft cHomeCardRankSelector" value=Single>{{ __('home.basic.s') }}</div>
	<div id=iHomeCardRankRD class="cHomeCardTopSelector cHomeCardRankSelector" value=Double>{{ __('home.basic.d') }}</div>
	<div class=cHomeCardRankLineChart id=iHomeCardRankLineChartSingle></div>
	<div class=cHomeCardRankLineChart id=iHomeCardRankLineChartDouble></div>
</div>


<script type="text/javascript" language="javascript">

	var dom;
	var option;
	var rankChart = [];
	var honorChart = [];
	var YTDChart;
	var CareerChart;
	var RateChart;
	var rank_timer;
	var stat_timer;
	var ytd_timer;
	var career_timer;
	var rate_timer;
	var honor_timer;
	var data_honor = [];

	function drawRankCurve() {

		if (document.getElementById('iHomeCardRank').style.display == "none") return;
		else {
			clearInterval(rank_timer);
			rank_timer = null;

			var w = $('#iHomeCardRank').width();
			var h = $('#iHomeCardRank').height();

			d = @json($ret['rank']['dot']);
			var arr = ['Single', 'Double'];

			for (i in arr) {

				x = arr[i];
				if (typeof x === 'function') continue;
				idx = x.substr(0, 1);
				var data = d[idx];

				$('#iHomeCardRankLineChart' + x).css('width', w + 'px');

				dom = document.getElementById('iHomeCardRankLineChart' + x);
				rankChart[idx] = echarts.init(dom);
				option = {
					tooltip: {
						trigger: 'axis'
					},
					grid: {
						left: '5%',
						right: '5%',
						top: '5%',
					},
					xAxis: {
						data: data.map(function (item) {
							return item[0];
						}),
						axisLabel: {
							textStyle: {
								color: '{{ Config::get('const.globalColor.midGray') }}',
							}
						}
					},
					yAxis: {
						splitLine: {show: false},
						axisLine: {show: false},
						axisLabel: {show: false},
						axisTick: {show: false},
						type: 'log',
						inverse: true,
					},
					dataZoom: [
						{
							type: 'slider',
							fillerColor: '{{ Config::get('const.globalColor.hl_tp') }}',
							handleStyle: {
								color: '{{ Config::get('const.globalColor.hl_tp') }}',
							},
						}, 
						{
							type: 'inside',
						}
					],
					series: {
						name: "{{ $ret['basic']['name'] }}",
						type: 'line',
						smooth: true,
						showSymbol: false,
						data: data.map(function (item) {return item[1];}),
						sampling: true,
						markLine: {
							silent: true,
							symbol: 'none',
							label: {position: 'end'},
							lineStyle: {
								normal: {
									color: '{{ Config::get('const.globalColor.midGray') }}',
								}
							},
							data: [{yAxis: 1},{yAxis: 5},{yAxis: 10},{yAxis: 20},{yAxis: 100},{yAxis: 200},{yAxis: 1000},],
						},
						lineStyle: {
							normal: {
								color: '{{ Config::get('const.globalColor.sep.' . $theme) }}',
								type: 'solid',
							}
						},
						itemStyle: {
							normal: {
								color: '{{ Config::get('const.globalColor.sep.' . $theme) }}',
							}
						}
					}
				}
				rankChart[idx].setOption(option);
			}
		}
	}

	if (rank_timer) clearInterval(rank_timer); 
	rank_timer = setInterval('drawRankCurve()', 500);

	function waitForStat() {
		if (document.getElementById('iHomeCardStat').style.display == "none") return;
		else {
			clearInterval(stat_timer);
			stat_timer = null;
			$('#iHomeCardStatSelectorYear').val(year_for_stat).change();
		}
		
	}

	if (stat_timer) clearInterval(stat_timer);
	stat_timer = setInterval('waitForStat()', 2000);

	function drawYTDChart () {
		if (document.getElementById('iHomeCardMatch').style.display == "none") return;
		else {
			clearInterval(ytd_timer);
			ytd_timer = null;

			dom = document.getElementById('iHomeCardMatchYTDChart');
			YTDChart = echarts.init(dom);

            option = {
                xAxis:  {
                    type: 'value',
                    show: false,
                    min: 0,
                    max: {{ $ret['match']['count']['ytd'][0] }},
                },
                yAxis: {
                    type: 'category',
                    inverse: true,
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
                animation: true,
                animationEasing: 'bounceOut',
                silent: true,
                series: [
                    {
                        type: 'bar',
                        barGap: '-100%',
                        itemStyle: {
                            normal: {
                                barBorderRadius: 20,
								color: '{{ Config::get('const.globalColor.lightGray') }}',
                            }
                        },
                        barWidth: '13',
                        data:[
                            {value: {{ $ret['match']['count']['ytd'][0] }}},
                            {value: {{ $ret['match']['count']['ytd'][0] }}},
                            {value: {{ $ret['match']['count']['ytd'][0] }}},
                            {value: {{ $ret['match']['count']['ytd'][0] }}},
                        ],
                    },
                    {
                        type: 'bar',
                        barGap: '-100%',
                        itemStyle: {
                            normal: {
                                barBorderRadius: 20,
                            }
                        },
                        barWidth: '13',
                        data:[
                            {value: {{ $ret['match']['count']['ytd'][0] }}, itemStyle: {normal: {color: '{{ Config::get('const.globalColor.lightGray') }}'}}},
                            {value: {{ $ret['match']['count']['ytd'][1] }}, itemStyle: {normal: {color: '{{ Config::get('const.globalColor.win') }}'}}},
                            {value: {{ $ret['match']['count']['ytd'][2] }}, itemStyle: {normal: {color: '{{ Config::get('const.globalColor.midGray') }}'}}},
                            {value: {{ $ret['match']['count']['ytd'][3] }}, itemStyle: {normal: {color: '{{ Config::get('const.globalColor.hl') }}'}}},
                        ],
                    },
                ]
            };
			YTDChart.setOption(option);
		}
	};

	if (ytd_timer) clearInterval(ytd_timer); 
	ytd_timer = setInterval('drawYTDChart()', 1000);

	function drawCareerChart () {
		if (document.getElementById('iHomeCardMatch').style.display == "none") return;
		else {
			clearInterval(career_timer);
			career_timer = null;

			dom = document.getElementById('iHomeCardMatchCareerChart');
			CareerChart = echarts.init(dom);

            option = {
                xAxis:  {
                    type: 'value',
                    show: false,
                    min: 0,
                    max: {{ $ret['match']['count']['career'][0] }},
                },
                yAxis: {
                    type: 'category',
                    inverse: true,
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
                animation: true,
                animationEasing: 'bounceOut',
                silent: true,
                series: [
                    {
                        type: 'bar',
                        barGap: '-100%',
                        itemStyle: {
                            normal: {
                                barBorderRadius: 20,
								color: '{{ Config::get('const.globalColor.lightGray') }}',
                            }
                        },
                        barWidth: '13',
                        data:[
                            {value: {{ $ret['match']['count']['career'][0] }}},
                            {value: {{ $ret['match']['count']['career'][0] }}},
                            {value: {{ $ret['match']['count']['career'][0] }}},
                            {value: {{ $ret['match']['count']['career'][0] }}},
                        ],
                    },
                    {
                        type: 'bar',
                        barGap: '-100%',
                        itemStyle: {
                            normal: {
                                barBorderRadius: 20,
                            }
                        },
                        barWidth: '13',
                        data:[
                            {value: {{ $ret['match']['count']['career'][0] }}, itemStyle: {normal: {color: '{{ Config::get('const.globalColor.lightGray') }}'}}},
                            {value: {{ $ret['match']['count']['career'][1] }}, itemStyle: {normal: {color: '{{ Config::get('const.globalColor.win') }}'}}},
                            {value: {{ $ret['match']['count']['career'][2] }}, itemStyle: {normal: {color: '{{ Config::get('const.globalColor.midGray') }}'}}},
                            {value: {{ $ret['match']['count']['career'][3] }}, itemStyle: {normal: {color: '{{ Config::get('const.globalColor.hl') }}'}}},
                        ],
                    },
                ]
            };
			CareerChart.setOption(option);
		}
	};

	if (career_timer) clearInterval(career_timer); 
	career_timer = setInterval('drawCareerChart()', 1000);

	x_interval = function (idx, value) {if (idx <= 50 && idx % 10 === 0) return true; else if (idx % 100 === 0) return true; return false;};

	var data_for_rate = @json($ret['winrate']);
	function drawRateChart () {
		if (document.getElementById('iHomeCardRate').style.display == "none") return;
		else {
			clearInterval(rate_timer);
			rate_timer = null;

			dom = document.getElementById('iHomeCardRateChart');
			RateChart = echarts.init(dom);

			option = {
				backgroundColor: 'transparent',
				grid: {
					show: true,
					backgroundColor: 'transparent',
					left: '6%',
					right: '6%',
					top: 15,
					borderWidth: 0,
					z: 0,
				},
				tooltip: {
					trigger: 'axis',
					formatter: '{!! __('home.rate.tip_formatter') !!}',
					textStyle: {
						fontSize: $('html').css('font-size').replace(/px/, ''),
					},
				},
				xAxis: {
					data: data_for_rate.map(function (item, idx) {
						return idx;
					}),
					splitLine: {show: false,},
					axisTick: {show: false,},
					axisLine: {show: false,},
					axisLabel: {
						interval: x_interval,
						showMinLabel: true,
						showMaxLabel: true,
						formatter: '{value}',
						inside: false,
						margin: 5,
						color: '{{ Config::get('const.globalColor.midGray') }}',
						fontSize: $('html').css('font-size').replace(/px/, ''),
					},
				},
				yAxis: [
					{
						splitNumber: 4,
						splitLine: {
							lineStyle: {
								color: '{{ Config::get('const.globalColor.weaken') }}',
								width: 1,
							},
						},
						axisLabel: {
							inside: false,
							color: '{{ Config::get('const.globalColor.midGray') }}',
							fontSize: $('html').css('font-size').replace(/px/, ''),
						},
						axisTick: {show: false,},
						axisLine: {
							show: true,
							lineStyle: {
								width: 1,
								type: 'solid',
								color: '{{ Config::get('const.globalColor.weaken') }}',
							},

						},
						zlevel: 3,
					},
					{
						show: false,
						max: 100,
						min: 0,
					}
				],
				dataZoom: [
					{
						startValue: 1,
						endValue: 54,
						fillerColor: '{{ Config::get('const.globalColor.hl_tp') }}',
						handleStyle: {
							color: '{{ Config::get('const.globalColor.hl_tp') }}',
						},
					}, {
						type: 'inside'
					}
				],
				series: [
					{
						name: 'Win',
						type: 'line',
						yAxisIndex: 0,
						showSymbol: false,
						lineStyle: {
							normal: {
								color: '{{ Config::get('const.globalColor.diff') }}',
								width: 3,
							},
						},
						smooth: true,
						data: data_for_rate.map(function (item) {
							return item[0];
						}),
						zlevel: 3,
						markLine: {
							symbol: 'none',
							data: [
								{xAxis: 10,},
								{xAxis: 20,},
								{xAxis: 30,},
								{xAxis: 40,},
								{xAxis: 50,},
								{xAxis: 100,},
								{xAxis: 200,},
								{xAxis: 300,},
								{xAxis: 400,},
								{xAxis: 500,},
							],
							lineStyle: {
								normal: {
									width: 1,
									type: 'solid',
									color: '{{ Config::get('const.globalColor.weaken') }}',
								}
							},
							label: {normal: {show: false}},
						},
					},
					{
						name: 'Loss',
						type: 'line',
						yAxisIndex: 0,
						smooth: true,
						showSymbol: false,
						lineStyle: {
							normal: {
								color: '{{ Config::get('const.globalColor.win') }}',
								width: 3,
							},
						},
						data: data_for_rate.map(function (item) {
							return item[1];
						}),
					},
					{
						name: 'Win Rate',
						type: 'line',
						yAxisIndex: 1,
						smooth: true,
						showSymbol: false,
						lineStyle: {
							normal: {
								color: '{{ Config::get('const.globalColor.hl') }}',
								width: 5,
								shadowBlur: 10,
								shadowColor: 'rgba(0, 0, 0, 0.1)',
							},
						},
						data: data_for_rate.map(function (item) {
							return item[1] + item[0] === 0 ? 0 : Math.round(item[0] / (item[0] + item[1]) * 1000) / 10;
						}),
						markLine: {
							symbol: ['circle', 'circle'],
							symbolSize: [12, 12],
							data: [
								{yAxis: 50,},
							],
							lineStyle: {
								normal: {
									width: 3,
									type: 'solid',
									color: '{{ Config::get('const.globalColor.sep.' . $theme) }}',
								}
							},
							label: {normal: {show: false}},
						},
					},
				]
			};
			RateChart.setOption(option);
			RateChart.dispatchAction({
				type: 'showTip',
				seriesIndex: 2,
				dataIndex: 10,
			});
		}
	}

	if (rate_timer) clearInterval(rate_timer);
	rate_timer = setInterval('drawRateChart()', 1000); 

	f = function (data, round, sd) {
		var ret = [];
		for (var i in data[round]) {
			if (i > -1) {
				if (data.Attend[i][0] > 0) {
					ret.push({value: data[round][i][0] / data.Attend[i][0] * 100, sd: sd});
				} else {
					ret.push({value :0, sd: sd});
				}
			}
		}
		return ret;
	};
	get_attend = function (data, idx) {
		return data.Attend[idx][0];
	};
	get_detail = function (data, round, idx) {
		if (round == "Attend") return "";
	    return data[round][idx][1].map(function (item, idx) {
			if (idx == 0) return "<table><tr><td>&nbsp;&nbsp;" + item + "&nbsp;&nbsp;</td>";
			else if (idx % 2 == 0) return "<tr><td>&nbsp;&nbsp;" + item + "&nbsp;&nbsp;</td>";
			else return "<td>&nbsp;&nbsp;" + item + "&nbsp;&nbsp;</td></tr>";
		}).join('');
	};

	function drawHonorCurve() {

		if (document.getElementById('iHomeCardHonor').style.display == "none") return;
		else {
			clearInterval(honor_timer);
			honor_timer = null;

			var w = Math.max($('#iHomeCardHonorGridChartSingle').width(), $('#iHomeCardHonorGridChartDouble').width());
			var h = Math.max($('#iHomeCardHonorGridChartSingle').height(), $('#iHomeCardHonorGridChartDouble').height());

			d = @json($ret['honor']);
			var arr = ['Single', 'Double'];

			for (i in arr) {

				x = arr[i];
				if (typeof x === 'function') continue;
				idx = x.substr(0, 1);
				data_honor[idx] = d[idx][1];

				$('#iHomeCardHonorGridChart' + x).css('width', w + 'px');
				$('#iHomeCardHonorGridChart' + x).css('height', h + 'px');
				dom = document.getElementById('iHomeCardHonorGridChart' + x);
				honorChart[x] = echarts.init(dom);

				option = {
					yAxis: {
						data: [
							"{{ __('home.honor.level.gs') }}", 
							"{{ __('home.honor.level.1000.' . $ret['gender']) }}", 
							"{{ __('home.honor.level.500.' . $ret['gender']) }}", 
							"{{ __('home.honor.level.250.' . $ret['gender']) }}", 
							"{{ __('home.honor.level.ol') }}", 
							"{{ __('home.honor.level.yec') }}", 
							"{{ __('home.honor.level.tour') }}", 
							"{{ __('home.honor.level.nontour') }}", 
						],
						inverse: true,
						axisTick: {show: false},
						axisLabel: {show:false},
						axisLine: {show: false},
					},
					xAxis: {
						splitLine: {show: false},
						axisLabel: {show:false},
						axisTick: {show: false},
						axisLine: {show: false},
					},
					grid: {
						right: '2%',
						top: '0%',
						bottom: '0%',
						left: '1%',
					},
					tooltip: {
					    show: true,
					    formatter: function (obj) {
							var num = Math.round(obj.data.value * get_attend(data_honor[obj.data.sd], obj.dataIndex) / 100);
							if (obj.seriesIndex == 4) {
								return obj.name + " " + obj.seriesName + ": " + num;
							} else {
								return num > 0 ? obj.name + " " + obj.seriesName + ": " + num + "<br>" + get_detail(data_honor[obj.data.sd], obj.seriesName, obj.dataIndex) : "";
							}
					    },
						textStyle: {
							fontSize: $('.cHomeCardHonorGridChart').css('font-size').replace(/px/, ''),
						},
					},
					textStyle: {
						fontSize: $('.cHomeCardHonorGridChart').css('font-size').replace(/px/, ''),
					},
					animationDurationUpdate: 1200,
					series: [
						@foreach (['W', 'F', 'SF', 'QF'] as $round)
							{
								type: 'bar',
								name: '{{ $round }}',
								itemStyle: {normal: {color: '{{ Config::get('const.barColor.' . $round) }}'}},
								barWidth: '60%',
								stack: 1,
								barGap: '-100%', // Make series be overlap
								data: f(data_honor[idx], '{{ $round }}', idx),
								label: {
									normal: {
										position: 'insideRight',
										show: true,
										color: '{{ $round == "W" || $round == "F" ? Config::get('const.globalColor.white') : Config::get('const.globalColor.black') }}',
										formatter: function (obj) {
											var num = Math.round(obj.data.value * get_attend(data_honor[idx], obj.dataIndex) / 100); 
											return num > 0 ? obj.seriesName + ":" + num : ""
										}
									}
								},
							},
						@endforeach
						{
							type: 'bar',
							name: '{{ __('home.honor.attend') }}',
							itemStyle: {normal: {color: '{{ Config::get('const.barColor.Attend.' . $theme) }}'}},
							barWidth: '60%',
							stack: 2,
							zlevel: -1,
							barGap: '-100%', // Make series be overlap
							data: f(data_honor[idx], 'Attend', idx),
							label: {
								normal: {
									show: true,
									position:'insideRight',
									color: '{{ Config::get('const.globalColor.midGray') }}',
									formatter:function (obj) {
										num = Math.round(obj.data.value * get_attend(data_honor[idx], obj.dataIndex) / 100);
										return num;
									},
								}
							},
						},
					]
				};

				honorChart[x].setOption(option);
			}
		}
	}

	if (honor_timer) clearInterval(honor_timer); 
	honor_timer = setInterval('drawHonorCurve()', 1000);

</script>


@endif
