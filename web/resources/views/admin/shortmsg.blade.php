<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
@php
header("Access-Control-Allow-Origin:*");
@endphp
<html>
<head>
	<meta charset="utf-8">
	<link rel="shortcut icon" type="image/ico" href="{{ asset(env('CDN') . '/images/tips/logo.ico') }}">
	<meta name="viewport" content="width=400,user-scalable=no">
	<meta name="description" content="" />
	<meta name="renderer" content="webkit">
	<meta name="keywords" content="{{ __('frame.title.keyword') }}" />
	<meta name="_token" content="{!! csrf_token() !!}"/>
	<meta name="csrf-token" content="{{ csrf_token() }}"/>
	<meta http-equiv="Cache-Control" content="no-transform" /> 
	<meta http-equiv="Cache-Control" content="no-siteapp" /> 
	<title>{{ isset($title) ? '&#x1f3be;' . $title . '&#x1f3be;' . ' ' : '' }}{{ __('frame.title.root') }}</title>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/jquery.min.js') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/base.js?t=' . time()) }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/encode.js') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/frame.js') }}"></script>
	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/datatables.min.css') }}">
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/datatables.min.js') }}"></script>

<style>
table {
	border-collapse: collapse !important;
}

td {
	border: 1px solid;
	font-size: 12px;
	padding: 4px !important;
}

.editable {
	display: inline-block;
}

</style>

<script>

function newObject(name, editable = false) {
	var obj = new Object;
	obj.name = name;
	obj.data = name;
	if (editable) {
		obj.render = function (data, type, row, meta) {
			return '<div class=editable col="' + name + '" data-id=' + row['id'] + ' >' + data + '</div>';
		}
	}
	return obj;
}

</script>

</head>
<body>
<script type="text/javascript" language="javascript" class="init">
$(function() {

	$(document).on('click', '.editable', function (e) {
		var id = $(this).attr('data-id');
		var content = $.trim($(this).html());
		if (!content.match('<input type="text"')) {
			$(this).html('<input type=text value=\'' + content + '\' />');
		}
	});

	$(document).on('blur', '.editable > input', function (e) {
		var content = $.trim($(this).val());
		var id = $(this).parent().attr('data-id');
		var col = $(this).parent().attr('col');
		$(this).parent().html(content);

		$.ajax({
			type: 'POST',
			url: '{{ url(join("/", ['admin', 'shortmsg', 'save'])) }}',
			data: {
				id: id,
				col: col,
				content: content,
			},
			success: function (data) {
			},
		});
	});

	var table = $('#iRankTable').dataTable( {
		"dom": '<rt><flip>',
		"processing": true,
		"oLanguage": {
			"sProcessing": "",
			"sInfoEmpty": "",
			"sZeroRecords": "",
		},
		"serverSide": true,
		"bAutoWidth": false,
		"ajax": {
			url: "{{ url(join("/", ["admin", "shortmsg", "query"])) }}",
			type: "POST",
		},
		"iDisplayLength": 50,
		"order": [[ 1, "desc" ]],
		"columns": [
			newObject('read'),
			newObject('id'),
			newObject('userid'),
			newObject('username'),
			newObject('msg'),
			newObject('created_at'),
			newObject('reply', true),
		],
		"drawCallback": function( settings ) {

		},
		initComplete: function ( settings ) {

		},

		"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
			
		},
		"columnDefs": [],
	});

});
</script>

<table id="iRankTable" class="cRankTable plrank" width=100%>
	<thead id='iRankTableHead'>
		<tr>
			<th>READ</th>
			<th>ID</th>
			<th>USERID</th>
			<th>USERNAME</th>
			<th>MSG</th>
			<th>TIME</th>
			<th>REPLY</th>
		</tr>
	</thead>
</table>

</body>
</html>
