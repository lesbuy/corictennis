@if (is_test_account())
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.test.css.draw') }}">
@else
	<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.draw') }}">
@endif
<script type="text/javascript" language="javascript" class="init">
</script>

<div id=iGSG>

	<table id=iGSGStatTable>
		<thead>
			<tr><td colspan=2 class="cGSGTitle">{{ __('gs.notice.stat') }}</td></tr>
		</thead>
		<tbody>
			@foreach ($counts as $number => $persons)
				<tr><td>{{ $number }}</td><td>{!! join("　", $persons) !!}</td></tr>
			@endforeach
		</tbody>
	</table>

	<table id=iGSGTable>
		<thead>
			<tr>
				<td colspan=5 class="cGSGTitle">{{ __('gs.notice.detail') }}</td>
			</tr>
			<tr>
				<th></th>
				<th colspan=1><img class=cImgGS src="{{ get_tour_logo_by_id_type_name("AO", "GS") }}" /></th>
				<th colspan=1><img class=cImgGS src="{{ get_tour_logo_by_id_type_name("RG", "GS") }}" /></th>
				<th colspan=1><img class=cImgGS src="{{ get_tour_logo_by_id_type_name("WC", "GS") }}" /></th>
				<th colspan=1><img class=cImgGS src="{{ get_tour_logo_by_id_type_name("UO", "GS") }}" /></th>
			</tr>
		</thead>

		<tbody>

			@foreach ($ret as $k_year => $v_year)
				<tr>
					<td>{{ $k_year }}</td>
					@foreach (['AO', 'RG', 'WC', 'UO'] as $eid)
						@php $tour = @$v_year[$eid]; @endphp
						<td>
							@if (is_array($tour) && count($tour) > 0)
								{!! join("", array_map(function ($d, $key) use ($eid, $k_year){return "<div class=\"Bg" . ($key % 2 == 0 ? "Odd" : "Even") . "\"><pname data-id=" . $d[1] . " eid=" . $eid . " year=" . $k_year . " alt=\"" . $d[2] . "\">" . $d[0] . "</pname></div>";}, $tour, array_keys($tour))) !!}
							@endif
						</td>
					@endforeach
				</tr>
			@endforeach
		</tbody>
	</table>

</div>
