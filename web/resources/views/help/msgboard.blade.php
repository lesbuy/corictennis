<style>

#iHelpMsgBoard {
	width: 300px;
	line-height: 1.5;
	padding: 10px;
}

input, textarea {
	width: 100%;
}

textarea {
	height: 8em;
	display: block;
}

#iHelpMsgSubmit, #iHelpMsgCancel {
	width: 30%;
	margin: 5px 8%;
	line-height: 3;
	text-align: center;
	display: inline-block;
	background-color: #46a9bf;
	color: #fff;
	cursor: pointer;
}
</style>

<script>
	$('#iHelpMsgBoard').on('click', function (e) {
		e.stopPropagation();
	});

	$('#iHelpMsgSubmit').on('click', function () {

		if ($('input[name=username]').length > 0 && !$.trim($('input[name=username]').val())) {
			alert('{{ __('help.msgboard.error.invalidName') }}');
		} else if (!$.trim($('textarea[name=msg]').val())) {
			alert('{{ __('help.msgboard.error.invalidContent') }}');
		} else {
			$.ajax({
				type: 'POST',
				url: '{{ url(App::getLocale() . "/msgboard/submit") }}',
				data: $('#iHelpMsgForm').serialize(),
				success: function(data) {
					$('#iHelpMsgBoard').html(data);
				},
			});
		}
	});

	$('#iHelpMsgCancel').on('click', function () {
		$(this).parent().parent().parent().fadeOut(500);
	});
</script>

<div id=iHelpMsgBoard>
	<form id=iHelpMsgForm>
		
		{!! __('help.msgboard.notice') !!} <br>
		@auth
		@else
			{!! __('help.msgboard.loginFirst') !!} <br>
			<label>{{ __('help.msgboard.name') }}</label><br>
			<input type=text name=username /><br>
		@endauth
		<label>{{ __('help.msgboard.content') }}</label><br>
		<textarea name=msg></textarea>
		<div id=iHelpMsgSubmit>{{ __('help.msgboard.submit') }}</div>
		<div id=iHelpMsgCancel>{{ __('help.msgboard.cancel') }}</div>
	</form>
</div>
