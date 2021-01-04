		@foreach ($ret as $week => $tours)
			<div class="cMenuDrawLevel">{{ __('frame.menu.drawList.' . $week) }}</div>
			@foreach ($tours as $level => $tournaments)
				<div class="cMenuDrawLine">
					@foreach ($tournaments as $tour)
						<a class="cMenuDrawTour" href="{{ url(join("/", [App::getLocale(), "draw", $tour[0], $tour[1]])) }}">
							@if (($loop->first && strpos($level, "-") !== false) || strpos($level, "-") === false)
								@foreach ($tour[2] as $logo)
									<img class="cMenuTourLogo" src="{{ $logo }}" />
								@endforeach
							@endif
							{{ translate_tour(strtolower($tour[3])) }}
						</a>
					@endforeach
				</div>
			@endforeach
		@endforeach
