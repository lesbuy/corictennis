<meta name="csrf-token" content="{{ csrf_token() }}"/>
<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/jquery.min.js') }}"></script>
<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/base.js') }}"></script>

<script type="text/javascript" language="javascript" class="init">
$(function() {

	$.ajax({
		type: 'POST',
		url: "{{ url('auth/weixin/slazenger') }}",
		data: "<xml> <ToUserName>< ![CDATA[toUser] ]></ToUserName>  <FromUserName>< ![CDATA[fromUser] ]></FromUserName>  <CreateTime>1348831860</CreateTime>  <MsgType>< ![CDATA[text] ]></MsgType>  <Content>< ![CDATA[this is a test] ]></Content>  <MsgId>1234567890123456</MsgId>  </xml>",
		success: function (data) {
			$('#test').html(data);
		},
	});
});

</script>

<div id=test>

</div>

