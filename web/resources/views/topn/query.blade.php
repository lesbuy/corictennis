@if ($ret['status'] < 0)
	<div id=iAjaxNotice>
		{{ $ret['errmsg'] }}
	</div>
@else
	<div id=iTopNDetail class="cH2HDetail">

		<div id=iTopNDetailNames class="cH2HDetailNames">
			<div id=iTopNDetailName1 class="SideHome cH2HDetailName1">{{ $ret['p1name'] }}</div>
			<div id=iTopNDetailName2 class="SideAway cH2HDetailName2">{{ __('h2h.selectBarTitle.topnweeks', ['p1' => $ret['n']]) }}</div>
		</div>

		<div id=iTopNDetailTableDiv class="cH2HDetailTableDiv">
			<table id=iTopNDetailTable class="cH2HDetailTable"><tbody>
				<tr>
					<td rowspan={{ count($ret['ranks']) + 1 }} id="iTopNDetailHeadsTd">
						<div id=iTopNDetailHeads class="cH2HDetailHeads">
							<div id=iTopNDetailHead1 class="cH2HDetailHead1" style="background-image: url({{ $ret['p1head'] }})"></div>
							<div id=iTopNDetailWin class="SideHomeText cH2HDetailWin">{{ $ret['win'] }}<div class="cH2HPortion SideHomeBorder">{{ __('h2h.selectBarTitle.totalWeeks') }}</div></div>
						</div>
					</td>
					<td>{{ __('h2h.selectBarTitle.startDate') }}</td>
					<td>{{ __('h2h.selectBarTitle.endDate') }}</td>
					<td>{{ __('h2h.selectBarTitle.weeks') }}</td>
				</tr>
				@foreach ($ret['ranks'] as $rank)
					<tr>
						<td>{{ $rank[0] }}</td>
						<td>{{ $rank[1] }}</td>
						<td>{{ $rank[2] }}</td>
					</tr>
				@endforeach
			</tbody></table>
		</div>
	</div>

@endif
