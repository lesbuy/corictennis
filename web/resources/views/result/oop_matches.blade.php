@php $count = 0; @endphp
@foreach ($ret as $tour)
	@php ++$count; @endphp
	@if ($count == 2)
<!--		<img class="tn-rebo" src="{{ url('/images/tips/rebo.jpg') }}" />-->
	@endif
	<div class=cResultTour id="iResult{{ $tour[0] }}" tour-id="{{ $tour[0] }}">
		<div class="cResultTourTitle"  is-open={{ $tour[3] }}>
			<img class="cResultTourTitleArrow" src="{{ url(env('CDN') . '/images/tips/live_tour_arrow.png') }}" />
			<div class="cResultTourTitleBlank Surface{{ $tour[2] }}"></div>
			@foreach ($tour[5] as $logo)
				<img src="{{ $logo }}" />
			@endforeach
			<div class=cResultTourTitleInfo>
				<div class=cResultTourInfoCity>{{ $tour[1] }}</div>
				<div class=cResultTourInfoName>{{ $tour[4] }}</div>
			</div>
		</div>
		<div class=cResultTourContent is-open={{ $tour[3] }}>
			@if ($tour[3] == 1)
				@include('result.content')
			@else
				<img class=cLoading src="{{ url(env('CDN') . '/images/tips/loading-cube.svg') }}" />
			@endif
		</div>
	</div>

@endforeach

