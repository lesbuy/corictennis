@if ($ret['status'] < 0)
	<div id=iAjaxNotice>
		{{ $ret['errmsg'] }}
	</div>
@else
	<div id=iOfficialDetail class="cH2HDetail">

		<div id=iOfficialDetailTableDiv class="cH2HDetailTableDiv">
			<table id=iOfficialDetailTable class="cH2HDetailTable">
				<thead>
					<tr class="selected">
						<th>{{ __('rank.table.head.official') }}</th>
						<th>{{ __('rank.table.head.point') }}</th>
						<th>{{ __('rank.table.head.player') }}</th>
						<th>{{ __('rank.table.head.nation') }}</th>
						<th>{{ __('rank.table.head.tourCount') }}</th>
						<th>{{ __('rank.table.head.age') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($ret['ranks'] as $rank)
						<tr>
							<td>{{ $rank[2] }}</td>
							<td>{{ $rank[3] }}</td>
							<td>{!! get_flag($rank[5]) !!} {{ translate('longname', $rank[1]) }}</td>
							<td>{{ __('nationname.' . $rank[5]) }}</td>
							<td>{{ $rank[4] }}</td>
							<td>{{ $rank[6] > 6 ? $rank[6] . __('h2h.age.year') : '' }}{{ $rank[7] > 0 ? $rank[7] . __('h2h.age.day') : '' }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>

@endif
