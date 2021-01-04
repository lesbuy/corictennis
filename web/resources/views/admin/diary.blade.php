<title>Daily Records</title>
<meta name="viewport" content="width=device-width,user-scalable=no">
<meta name="csrf-token" content="{{ csrf_token() }}"/>

<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.jquery') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.base') }}"></script>
<script type="text/javascript" language="javascript" src="{{ Config::get('head.common.js.datatables') }}"></script>
<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.bootstrap') }}">
<link rel="stylesheet" type="text/css" href="{{ Config::get('head.common.css.datatables') }}">

<script type="text/javascript" language="javascript" class="init">
$(function() {
	
	var table = $('#table').DataTable({
		"dom": '<rt><fl><ip>',
		"ajax": {
			url: "{{ url(join("/", ['admin', 'diary', 'query'])) }}",
			type: "POST",
		},
		"serverSide": true,
		"aLengthMenu": [[10, 15, 20, 25, 50, 100, 200, -1], [10, 15, 20, 25, 50, 100, 200, "全部"]],
		"iDisplayLength": 50,
		"order": [[ 0, "desc" ]],
		"columns": [
			{name: 'date', data: 'date'},
			{name: 'content', data: 'content'},
		],
		"fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
			$('td', nRow).eq(1).addClass('editable');
			$('td', nRow).eq(1).attr('data-id', aData.id);
		},
		initComplete: function () {
			var year = (new Date()).getFullYear();
			var month = (new Date()).getMonth() + 1;
			var _s_month = $('#s_month');
			for (i = month; i >= 1; --i) {
				str = year + '-' + (i < 10 ? '0' : '') + i;
				_s_month.append('<option value="' + str + '">' + str + '</option>');
			}
			for (j = year - 1; j >= 2010; --j) {
				for (i = 12; i >= 1; --i) {
					str = j + '-' + (i < 10 ? '0' : '') + i;
					_s_month.append('<option value="' + str + '">' + str + '</option>');
				}
			}
		},
	});

	$('#d_month').html('<select id="s_month" class="input-sm"><option value="所有">月份</option></select>');

	$(document).on('change', '#s_month', function (e) {
		_v_month = $('#s_month').val();
		if (_v_month == "所有"){
			tmp_table = table.column('date:name').search('1970-1-1', 'from', false);;
		} else {
			tmp_table = table.column('date:name').search(_v_month, 'month', false);
		}
		tmp_table.draw();
	});

	$(document).on('click', '.editable', function (e) {
		if ($('.editing').length == 0) {
			$(this).removeClass('editable');
			$(this).addClass('editing');
			$(this).html('<textarea haschange=0>' + $(this).html() + '</textarea>');
			$(this).children(0).focus();
		}
	});

	$(document).on('input propertychange', '.editing > textarea', function (e) {
		$(this).attr('haschange', 1);
	});

	$(document).on('blur', '.editing > textarea', function (e) {
		var content = $.trim($(this).val()).replace(/[\r\n]/, '');
		var _parent = $(this).parent();
		var id = _parent.attr('data-id');
		_parent.html(content);
		_parent.removeClass('editing');
		_parent.addClass('editable');
		if ($(this).attr('haschange') == 1) {
			save(id, content);
		}
	});

	$(document).keyup(function(e) {
		if (event.keyCode == 13) {
			e.stopPropagation();
			if ($('.editing').length != 1) return;
			check_and_save();
			var td = $('.editing');
			var content = $.trim(td.children('textarea').val()).replace(/[\r\n]/, '');
			td.html(content);
		}
	});
			
	function save(id, content) {
		$('#saving_flag').html('SAVING...');
		var ajax_save = $.ajax({
			url: "{{ url(join("/", ['admin', 'diary', 'save'])) }}",
			type: 'POST',
			timeout: 5000,
			data: {id: id, content: content},
			success: function (d) {
				if (d == 0) {
					$('#saving_flag').html('SAVED');
					return true;
				} else {
					$('#saving_flag').html(d + ' NOT SAVED');
					return false;
				}
			},
			complete: function(XMLHttpRequest, status) {
				if (status == 'timeout' || status == 'error'){
					ajax_save.abort();
					$('#saving_flag').html(status + ' NOT SAVED');
					return false;
				}
				return true;
			}
		});
	}

	function check_and_save() {
		$('.editing').each(function () {
			if ($(this).children('textarea').length != 1) return;
			if ($(this).children('textarea').attr('haschange') != 1) return;
			var text_area = $(this).children('textarea');
			var content = $.trim(text_area.val()).replace(/[\r\n]/, '');
			var id = $(this).attr('data-id');
			text_area.attr('haschange', 0);
			if (!save(id, content)) {
				text_area.attr('haschange', 1);
			}
		});
	};

	var save_timer = setInterval(check_and_save, 10000);

});
</script>
<style>
#d_month {
	margin: 20px;
}
#saving_flag {
	position: fixed;
	right: 5px;
	top: 5px;
	padding: 5px;
	z-index: 1;
	color: #fff;
	background-color: #f00;
	border-radius: 5px;
}
.editing > textarea {
	width: 100%;
	height: 100px;
    border: 0;
}
#table td, #table th {
	vertical-align: middle;
	font-size: small;
}
#table tbody tr.even {
	background-color: #f7f7f7;
}
#table th {
	border-bottom: 0;
}
</style>

<span id='saving_flag'></span>
<div id="d_month"></div>

<table id="table" class="table" width=98%>
	<thead id='table-head' class="table-head">
		<tr>
			<th>日期</th>
			<th>内容</th>
		</tr>
	</thead>
</table>
