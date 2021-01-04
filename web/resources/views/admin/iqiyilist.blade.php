<title>爱奇艺网球直播列表</title>

<style>

html {
	font-size: 12px;
}

div {
	font-size: 2rem;
	line-height: 1.4;
	border: 3px solid grey;
	margin: 5px;
	padding: 10px;
}

a {
	text-decoration: none;
}

.info {
	font-size: 1.1rem;
}

</style>


@foreach ($ret as $court)

	<a href="{{ $court[4] }}">

		<div>
			{{ $court[2] }} | {{ $court[1] }} <br> 
			{{ $court[0] }} <br>
			<span class=info>{{ $court[3] }}</span>
		</div>

	</a>
@endforeach
