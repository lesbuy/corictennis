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
		</thead>
		<tbody>
			@foreach ($ret as $k_year => $v_year)
				@isset ($tour_title['t' . $k_year . '_' . substr($sex, 0 , 1)])
					<tr class=cGSGTrTitle><td></td>
						@for ($i = 0; $i < 10; ++$i)
							@isset ($tour_title['t' . $k_year . '_' . substr($sex, 0 , 1)][$i])
								<td class="selected">{{ translate_tour($tour2name[$tour_title['t' . $k_year . '_' . substr($sex, 0 , 1)][$i]]) }}</td>
							@endisset
						@endfor
					</tr>
				@endisset
				<tr>
					<td>{{ $k_year }}</td>
					@for ($eid = 0; $eid < 10; ++$eid)
						@isset ($v_year[$eid])
							@php $tour = @$v_year[$eid]; @endphp
							<td>
								@if (is_array($tour) && count($tour) > 0)
									{!! join("", array_map(function ($d, $key) use ($eid, $k_year){return "<div class=\"Bg" . ($key % 2 == 0 ? "Odd" : "Even") . "\"><pname data-id=" . $d[1] . " eid=" . $d[3] . " year=" . $k_year . " alt=\"" . $d[2] . "\">" . $d[0] . "</pname></div>";}, $tour, array_keys($tour))) !!}
								@endif
							</td>
						@endisset
					@endfor
				</tr>
			@endforeach
		</tbody>
	</table>

</div>
