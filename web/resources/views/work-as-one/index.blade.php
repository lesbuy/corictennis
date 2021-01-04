@extends('layouts.header')

@section('content')

<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.work-as-one') }}"> 

<script type="text/javascript" language="javascript" class="init">

	$(function () {

		$('.cWorkAsOneOption').on('click', function () {

			window.location.href = $(this).attr('href');

		});

	});

</script>

<div id=iWorkAsOne>
	<div id=iWorkAsOneIntro>
		@lang('work-as-one.intro')
	</div>

	<div id=iWorkAsOneOptions>
		@foreach ($ret as $k => $v)

			<div class=cWorkAsOneOption href="{{ url(App::getLocale() . '/work-as-one/' . $k) }}">
<!--				<img class='cWorkAsOneLangImg' src="{{ env('CDN') . '/images/lang/' . $k . '.svg' }}" />-->
				{{ $v }}
			</div>

		@endforeach
	</div>
</div>
@endsection
