<style>

#iHelpMsgBoard {
	width: 300px;
	line-height: 1.5;
	padding: 10px;
}

#iHelpMsgSubmit {
	width: 60%;
	margin: 5px auto;
	line-height: 2.5;
	text-align: center;
	background-color: #46a9bf;
	color: #fff;
	cursor: pointer;
}

.cHelpMsg {
	margin: 10px;
	padding: 5px;
	border: 1px solid #bbb;
}

.cHelpReply {
	margin: 10px;
	padding-left: 10px;
}

</style>

<script>
	$('#iHelpMsgBoard').on('click', function (e) {
		e.stopPropagation();
	});

	$('#iHelpMsgSubmit').on('click', function () {
		$(this).parent().parent().fadeOut(500);
	});
</script>

<div id=iHelpMsgBoard>
		
		{{ __('help.msgboard.gotMsg', ['p1' => count($msg)]) }} <br>

		@foreach ($msg as $pair)
			<div class=cHelpMsg>{!! $pair[0] !!}</div>
			<div class=cHelpReply>{!! $pair[1] !!}</div>
		@endforeach
		<div id=iHelpMsgSubmit>{{ __('help.msgboard.iknow') }}</div>
</div>
