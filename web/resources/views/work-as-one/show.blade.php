@extends('layouts.header')

@section('content')

<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/work-as-one.css') }}">

<script type="text/javascript" language="javascript" class="init">

	$(function () {

		$('input[type="text"]').on('input propertychange', function() {
			var name = $(this).attr('name');
			if (!name.match(/^XXXXXX/)) {
				$(this).attr('name', 'XXXXXX' + name);
			}
		});

	});

	function check() {

		var url = $('#iWorkAsOneForm').attr('action');

		var data = $('#iWorkAsOneForm').serializeArray();

		var postdata = new Object();

		for (var key in data) {
			if (typeof(data[key]) === 'function') continue;
			var name = data[key].name;
			var value = data[key].value;

			if (name.match(/^XXXXXX/)) {
				name = name.substr(6);
				postdata[name] = value;
			}
		}

		$('#iMask').fadeIn(500).css('display', '-webkit-flex');
		$('#iMask').html('<div id=iAjaxNotice>' + '@lang('work-as-one.button.submitting')'  + '</div>');

		$.ajax({
			type: "POST",
			data: postdata,
			url: url,
			success: function (data) {
				$('#iMask').html(data);
			}
		});

		return false;

	}

</script>

<div id=iWorkAsOne>
	<div id=iWorkAsOneIntro>
		@if ($status == 0)
			@lang('work-as-one.intro2')
		@else
			@lang('work-as-one.button.unsupport')
		@endif
	</div>

	@if ($status == 0)
		<form id='iWorkAsOneForm' method='POST' action='{{ url(App::getLocale() . '/work-as-one/' . $lang2 . '/submit') }}' onsubmit="return check();">
			<input type=submit value="@lang('work-as-one.button.submit')" />
			<table id=iWorkAsOneTable>
				<thead><tr><th>@lang('work-as-one.table.category')</th><th>@lang('work-as-one.table.path')</th><th>@lang('work-as-one.table.englishWords')</th><th>@lang('work-as-one.table.yourFilling', ['p1' => Config::get('const.translate.' . $lang2)])</th></tr></thead>
				<tbody>
					@foreach ($ret as $category => $v)

						@foreach ($v as $path => $desc)
							<tr class="{{ $desc[0] }}">
								<td>{{ $category }}</td>
								<td>{{ str_replace(".", " ", $path) }}</td>
								<td>{{ $desc[1] }}</td>
								<td><input type=text name="{{ str_replace(".", "`", $category . "." . $path) }}" value="{{ $desc[2] }}" /></td>
							</tr>
						@endforeach
					@endforeach
				</tbody>
			</table>
		</form>
	@endif
</div>
@endsection
