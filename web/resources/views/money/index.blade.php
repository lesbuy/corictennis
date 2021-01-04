<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,user-scalable=no">
	<title>ACCOUNT</title>
<!--	<link rel="stylesheet" type="text/css" href="./media/css/bootstrap.min.css">-->
	<meta name="_token" content="{!! csrf_token() !!}"/>
	<meta name="csrf-token" content="{{ csrf_token() }}"/>
	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/datatables.min.css') }}">
	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/daterangepicker.css?t=' . time()) }}">
	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/old_optionpicker.css') }}">
	<link rel="stylesheet" type="text/css" href="{{ asset(env('CDN') . '/css/mymoney.css?t=' . time()) }}">
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/jquery.min.js') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/bootstrap.min.js') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/moment.min.js') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/daterangepicker.js') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/datatables.min.js') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/mymoney.js') }}"></script>
	<script type="text/javascript" language="javascript" src="{{ asset(env('CDN') . '/js/old_optionpicker.js') }}"></script>

</head>
<script type="text/javascript" language="javascript" class="init">

$(document).ready(function() {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }   
    });

	// 全局变量，表示等待被删除的id
	var wait_for_del = 0;

	function format ( d ) {
		return '<table>'+
			'<tr>'+
				'<td>Details: </td>'+
				'<td><div>修改</div><div class="del_record" r_id=' + d[0] + '>删除</div><td>' +
			'</tr>'+
		'</table>';
	}

	var table = $('#example').DataTable( {
		"dom": '<rt><fl><ip>',
		"processing": true,
		"oLanguage": {
			"sProcessing": "数据请求中...",
			"sInfo": "总共_TOTAL_条记录 当前显示第_START_至_END_条",
			"sInfoEmpty": "没有记录",
			"sZeroRecords": "没有记录",
			"sSearch": "搜索:_INPUT_",
			"sLengthMenu": "显示_MENU_条记录",
			"oPaginate": {
				"sFirst": "首页",
				"sLast": "末页",
				"sNext": "下一页",
				"sPrevious": "上一页"
			}
		},
		"serverSide": true,
		"ajax": {
			"url": "{{ url('/admin/money/query') }}",
			"type": "POST",
		},

		"columns": [
			{name: 'id', data: 'id', render: function ( data, type, row, meta ) {
				return '<div class="cp_record">拷</div> <div class="mod_record" r_id=' + data + '>改</div> <div class="del_record" r_id=' + data + '>删</div>';
			}},
			{name: 'stamp', data: 'stamp', render: function ( data, type, row, meta ) {return data.replace(/^.* /, '');}},
			{name: 'type', data: 'type'},
			{name: 'c1', data: 'c1'},
			{name: 'c2', data: 'c2'},
			{name: 'a1', data: 'a1'},
			{name: 'a2', data: 'a2'},
			{name: 'price', data: 'price', render: function ( data, type, row, meta ) {
				var _class = row['type'] == "支出" ? "tp_green" : (row['type'] == "收入" ? "tp_red" : "tp_white");
				return '<div class="' + _class + '">' + Number(data).toFixed(2) + '</div>';
			}},
			{name: 'city', data: 'city'},
			{name: 'road', data: 'road'},
			{name: 'company', data: 'company'},
			{name: 'project', data: 'project'},
			{name: 'more', data: 'more'},
			{name: 'ajoin', data: 'ajoin', visible: false},
			{name: 'fact_time', data: 'fact_time', render: function (data) {
				return data ? moment(data).format('M/D H:m') : '';
			}},
		],

		"aLengthMenu": [[10, 15, 20, 25, 50, 100, 200, -1], [10, 15, 20, 25, 50, 100, 200, "全部"]],
		"iDisplayLength": 50,
		"order": [[ 1, "desc" ]],
		"drawCallback": function(settings) {
			// 按日期排序后，把日期抽出来
			var api = this.api();
			var rows = api.rows({
				page: 'current'
			}).nodes();
			var last = null;
 
			var income = 0; var cost = 0;
			var t_income = 0; var t_cost = 0;
			api.column(1, {
				page: 'current'
			}).data().each(function(group, i) {
				group_date = group.replace(/ .*$/, '');
				if (last !== group_date) {
					$(rows).eq(i).before(
						'<tr class="group">' + 
							'<td colspan="14">' +
								'<table><tbody><tr>' + 
									'<td><b>' + group_date + '</b></td>' + 
									'<td id="cost_' + group_date + '"></td>' + 
									'<td id="income_' + group_date + '"></td>' +
									'<td id="outcome_' + group_date + '"></td>' +
								'</tr></tbody></table>' + 
							'</td>' + 
						'</tr>'
					);
					if (last != null){
						$('#cost_' + last).html("总支出：-" + cost.toFixed(0));
						$('#income_' + last).html("总收入：+" + income.toFixed(0));
						$('#outcome_' + last).html("结余：" + (income - cost).toFixed(0));
					}
					income = cost = 0;
					last = group_date;
				}
				var row_data = table.row(i).data();
				if (row_data['type'] == "支出"){
					cost = cost + Number(row_data['price']);
					t_cost += Number(row_data['price']);
				} else if (row_data['type'] == "收入"){
					income = income + Number(row_data['price']);
					t_income += Number(row_data['price']);
				} else if (row_data['type'] == "转账"){
					t_cost += Number(row_data['price']);
					t_income += Number(row_data['price']);
				}
			});
			if (last != null){
				$('#cost_' + last).html("总支出：-" + cost.toFixed(0));
				$('#income_' + last).html("总收入：+" + income.toFixed(0));
				$('#outcome_' + last).html("结余：" + (income - cost).toFixed(0));
			}
			income = cost = 0;
			$('#total_income').html('+' + t_income.toFixed(2));
			$('#total_cost').html('-' + t_cost.toFixed(2));
			$('#total_outcome').html((t_income - t_cost).toFixed(2));

			$('table.display .sorting_1').removeClass('sorting_1');
			$('table.display .sorting').removeClass('sorting');
		},

		initComplete: function () {
			// 各框初始化数据

			// 上方的月份筛选框
			var _s_month = $('#s_month');
			$.ajax({
				type: "POST",
				url: "{{ url('admin/money/select/stamp') }}",
				success: function(data){
					data = $.parseJSON(data);
					for (var d in data){
						if (d != -1)
							_s_month.append('<option value="' + data[d] + '">' + data[d] + '</option>');
					}
				}
			});

			// 中部的账户框
			var _table_account = $('#account_sum_table tbody');
			$.ajax({
				type: "GET",
				url: "{{ url('admin/money/sum/account') }}",
				success: function(data){
					data = $.parseJSON(data);
					for (var d in data){
						if (data[d][1] != data[d][2]) {
							_table_account.append('<tr>'
								+ '<td class=sum-key>' + data[d][0] + '</td>'
								+ '<td class=sum-out>' + data[d][1].toFixed(2) + '</td>'
								+ '<td class=sum-in>' + data[d][2].toFixed(2) + '</td>'
								+ '<td class=sum-sum>' + data[d][3].toFixed(2) + '</td>'
							+ '</tr>');
						}
					}
				}
			});

			// 中部的月份框
			var _month_account = $('#month_sum_table tbody');
			$.ajax({
				type: "GET",
				url: "{{ url('admin/money/sum/month') }}",
				success: function(data){
					data = $.parseJSON(data);
					for (var d in data){
						if (data[d][1] != data[d][2]) {
							_month_account.append('<tr>'
								+ '<td>' + data[d][0] + '</td>'
								+ '<td>' + data[d][1].toFixed(2) + '</td>'
								+ '<td>' + data[d][2].toFixed(2) + '</td>'
								+ '<td>' + data[d][3].toFixed(2) + '</td>'
							+ '</tr>');
						}
					}
				}
			});

			// 中部的分类框
			var _category_account = $('#category_sum_table tbody');
			$.ajax({
				type: "GET",
				url: "{{ url('admin/money/sum/category') }}",
				success: function(data){
					data = $.parseJSON(data);
					for (var d in data){
						if (data[d][1] != data[d][2]) {
							_category_account.append('<tr>'
								+ '<td class=sum-key>' + data[d][0] + '</td>'
								+ '<td class=sum-out>' + data[d][1].toFixed(2) + '</td>'
								+ '<td class=sum-in>' + data[d][2].toFixed(2) + '</td>'
								+ '<td class=sum-sum>' + data[d][3].toFixed(2) + '</td>'
							+ '</tr>');
						}
					}
				}
			});

			// 中部的分类框2
			var _category2_account = $('#category2_sum_table tbody');
			$.ajax({
				type: "GET",
				url: "{{ url('admin/money/sum/category2') }}",
				success: function(data){
					data = $.parseJSON(data);
					for (var d in data){
						if (data[d][1] != data[d][2]) {
							_category2_account.append('<tr>'
								+ '<td class=sum-key>' + data[d][0] + '</td>'
								+ '<td class=sum-out>' + data[d][1].toFixed(2) + '</td>'
								+ '<td class=sum-in>' + data[d][2].toFixed(2) + '</td>'
								+ '<td class=sum-sum>' + data[d][3].toFixed(2) + '</td>'
							+ '</tr>');
						}
					}
				}
			});

/*
			// 中部的月份滚动框
			var d_month_sum = $('#month_sum_scroll');
			$.ajax({
				type: "GET",
				url: "{{ url('admin/money/month') }}",
				success: function(data){
					data = $.parseJSON(data);
					var max_width = 0;
					for (var d in data){
						var year = d.split("-")[0];
						var month = d.split("-")[1];
						var income = data[d][1];
						var cost = data[d][0];
						var outcome = income - cost;
						d_month_sum.append(
							'<div class="d_each_month_block">'
							+ '<div class="d_year">' + year + '</div>'
							+ '<div class="d_month">' + month + '</div>'
							+ '<div class="d_income">+' + income + '</div>'
							+ '<div class="d_cost">-' + cost + '</div>'
							+ '</div>'
						);
						max_width = max_width + 120;
					}
					$('#month_sum_scroll').css('width', max_width + 'px');
				}
			})						
*/
		},

		"fnRowCallback": function( nRow, aData, iDisplayIndex ) {
			
			return nRow;
		},
	} );

	// 时间范围选择控件初始化
	$('div#d_time').html('<input id="reportrange" class="input-sm">');
	$('#reportrange').daterangepicker( {
		startDate: moment().subtract(30, 'days'),
		endDate: moment(),
		minDate: '2011-03-31',	//最小时间
		maxDate : moment(), //最大时间
		showDropdowns : false,
		alwaysShowCalendars: true,
		showWeekNumbers: true,
		showCustomRangeLabel: false,
		timePicker : false, //是否显示小时和分钟
		timePickerIncrement : 60, //时间的增量，单位为分钟
		timePicker12Hour : false, //是否使用12小时制来显示时间
		ranges : {
			'本月': [moment().startOf('month'), moment()],
			'上月': [moment().startOf('month').subtract(1, 'days').startOf('month'), moment().startOf('month').subtract(1, 'days')],
			'本周': [moment().startOf('week'), moment()],
			'上周': [moment().startOf('week').subtract(1, 'days').startOf('week'), moment().startOf('week').subtract(1, 'days')],
			'上个信用卡周期': [
				moment().get('date') >= 15 ? moment().startOf('month').subtract(1, 'days').startOf('month').add(14, 'days') : moment().startOf('month').subtract(1, 'days').startOf('month').subtract(1, 'days').startOf('month').add(14, 'days'),
				moment().get('date') >= 15 ? moment().startOf('month').add(13, 'days') : moment().startOf('month').subtract(1, 'days').startOf('month').add(13, 'days')
			],
			'当前信用卡周期': [
				moment().get('date') >= 15 ? moment().startOf('month').add(14, 'days') : moment().startOf('month').subtract(1, 'days').startOf('month').add(14, 'days'),
				moment()
			],
			'本年': [moment().startOf('year'), moment()],
			'上年': [moment().startOf('year').subtract(1, 'days').startOf('year'), moment().startOf('year').subtract(1, 'days')],
			'全部': [moment('2011-03-31'), moment()],
		},
		opens : 'right', //日期选择框的弹出位置
		buttonClasses : [ 'btn btn-default' ],
		applyClass : 'btn-small btn-primary blue',
		cancelClass : 'btn-small',
		locale : {
			format: 'YYYY-MM-DD',
			separator: ' ~ ',
			applyLabel : '确定',
			cancelLabel : '取消',
			fromLabel : '起始时间',
			toLabel : '结束时间',
			weekLabel: 'W',
			customRangeLabel : '自定义',
			daysOfWeek : [ '日', '一', '二', '三', '四', '五', '六' ],
			monthNames : [ '一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月' ],
			firstDay : 1
		}
	}, function(start, end, label) {//格式化日期显示框
	});
	//设置日期菜单被选项  --开始--
	var dateOption ;
	if("${riqi}"=='day') {
	    dateOption = "今日";
	}else if("${riqi}"=='yday') {
	    dateOption = "昨日";
	}else if("${riqi}"=='week'){
	    dateOption ="最近7日";
	}else if("${riqi}"=='month'){
	    dateOption ="最近30日";
	}else if("${riqi}"=='year'){
	    dateOption ="最近一年";
	}else{
	    dateOption = "自定义";
	}
	$(".daterangepicker").find("li").each(function (){
	    if($(this).hasClass("active")){
		 $(this).removeClass("active");
	    }
	    if(dateOption==$(this).html()){
		 $(this).addClass("active");
	    }
	});
	//设置日期菜单被选项  --结束--

	// 各框初始化
	$('div#d_month').html('<select id="s_month" class="input-sm"><option value="所有">月份</option></select>');

	$('div#d_submit').html('筛选');

	$('div#d_account').html('<select id="s_account" class="input-sm"><option value="000">所有账户</option></select>');

	$('div#i_submit').html('插入');

	var _d_type = $('#d_type').optionpicker({
		url: "{{ url('admin/money/select/type') }}",
		width: 50,
		holder: "类型",
	}).data("object");
	var _d_category1 = $('#d_category1').optionpicker({
		url: "{{ url('admin/money/select/c1') }}",
		width: 50,
		holder: "分类1",
	}).data("object");
	var _d_category2 = $('#d_category2').optionpicker({
		url: "{{ url('admin/money/select/c2') }}",
		width: 60,
		holder: "分类2",
	}).data("object");
	var _d_account1 = $('#d_account1').optionpicker({
		url: "{{ url('admin/money/select/account') }}",
		width: 90,
		holder: "流出账户",
	}).data("object");
	var _d_account2 = $('#d_account2').optionpicker({
		url: "{{ url('admin/money/select/account') }}",
		width: 90,
		holder: "流入账户",
	}).data("object");
	var _d_acc = $('#d_acc').optionpicker({
		url: "{{ url('admin/money/select/account') }}",
		width: 90,
		holder: "账户",
	}).data("object");
	var _d_city = $('#d_city').optionpicker({
		url: "{{ url('admin/money/select/city') }}",
		width: 50,
		holder: "城市",
	}).data("object");
	var _d_road = $('#d_road').optionpicker({
		url: "{{ url('admin/money/select/road') }}",
		width: 80,
		holder: "道路",
	}).data("object");
	var _d_company = $('#d_company').optionpicker({
		url: "{{ url('admin/money/select/company') }}",
		width: 90,
		holder: "商家",
	}).data("object");
	var _d_project = $('#d_project').optionpicker({
		url: "{{ url('admin/money/select/project') }}",
		width: 90,
		holder: "项目",
	}).data("object");
	var _d_more = $('#d_more').optionpicker({
		width: 150,
		holder: "备注",
	}).data("object");

	var _i_time = $('#i_time').optionpicker({
		width: 160,
		holder: "时间",
		default: moment().format('YYYY-MM-DD HH:mm:ss'),
	}).data("object");
	var _i_type = $('#i_type').optionpicker({
		url: "{{ url('admin/money/select/type') }}",
		width: 50,
		holder: "类型",
		default: "支出",
	}).data("object");
	var _i_category1 = $('#i_category1').optionpicker({
		url: "{{ url('admin/money/select/c1') }}",
		width: 50,
		holder: "分类1",
	}).data("object");
	var _i_category2 = $('#i_category2').optionpicker({
		url: "{{ url('admin/money/select/c2') }}",
		width: 60,
		holder: "分类2",
	}).data("object");
	var _i_account1 = $('#i_account1').optionpicker({
		url: "{{ url('admin/money/select/a1') }}",
		width: 90,
		holder: "流出",
	}).data("object");
	var _i_account2 = $('#i_account2').optionpicker({
		url: "{{ url('admin/money/select/a2') }}",
		width: 90,
		holder: "流入",
	}).data("object");
	var _i_price = $('#i_price').optionpicker({
		width: 60,
		holder: "金额",
	}).data("object");
	var _i_city = $('#i_city').optionpicker({
		url: "{{ url('admin/money/select/city') }}",
		width: 50,
		holder: "城市",
		default: "上海",
	}).data("object");
	var _i_road = $('#i_road').optionpicker({
		url: "{{ url('admin/money/select/road') }}",
		width: 80,
		holder: "道路",
	}).data("object");
	var _i_company = $('#i_company').optionpicker({
		url: "{{ url('admin/money/select/company') }}",
		width: 90,
		holder: "商家",
	}).data("object");
	var _i_project = $('#i_project').optionpicker({
		url: "{{ url('admin/money/select/project') }}",
		width: 90,
		holder: "项目",
	}).data("object");
	var _i_more = $('#i_more').optionpicker({
		width: 350,
		holder: "备注",
	}).data("object");
	var _i_fact = $('#i_fact').optionpicker({
		width: 100,
		holder: "生效时间",
	}).data("object");

/*
	// 一级分类框选择之后，自动加载二级分类框
	$('#s_category1').on( 'change', function () {
		var _s_category2 = $('#s_category2');
		_s_category2.empty();
		_s_category2.append('<option value="所有">二级分类</option>');
		$.ajax({
			type: "GET",
			url: "./select.php?column=category2&category1=" + $(this).val(),
			success: function(data){
				data = $.parseJSON(data);
				for (var d in data){
					if (d != -1)
						_s_category2.append('<option value="' + data[d] + '">' + data[d] + '</option>');
				}
			}
		} );
	} );
	$('#is_category1').on( 'change', function () {
		var _is_category2 = $('#is_category2');
		_is_category2.empty();
		$.ajax({
			type: "GET",
			url: "./select.php?column=category2&category1=" + $(this).val(),
			success: function(data){
				data = $.parseJSON(data);
				for (var d in data){
					if (d != -1)
						_is_category2.append('<option value="' + data[d] + '">' + data[d] + '</option>');
				}
			}
		} );
	} );
*/

	// 账户列表查看余额
	$('#s_account').on( 'change', function () {
		$.ajax({
			type: "GET",
			url: "{{ url('admin/money/sum') }}" + '/' + $(this).val(),
			success: function(data){
				data = $.parseJSON(data);
				for (var d in data){
					$('#account_cost').html('-' + data[d][0].toFixed(2));
					$('#account_income').html('+' + data[d][1].toFixed(2));
					$('#account_outcome').html((data[d][1] - data[d][0]).toFixed(2));
				}
			}
		} );
	} );

	// 点击自动填入当前时间
	$('div.add_now').on( 'click', function (){
		_i_time.setValue(moment().format('YYYY-MM-DD HH:mm:ss'));
	} );

	// 当点击了时间范围选择框时，自动把月份选择框置为全部
	$('.applyBtn, .ranges ul li').on( 'click', function (){
		$("#s_month").get(0).selectedIndex = 0;
	} );

	//全局注册删除按钮事件
	$(document).on( 'click', '.del_record', function (){
		var id = $(this).attr('r_id');
		if (!confirm("你要删除这条记录 确定吗?")) return;
		$.ajax({
			type: "POST",
			data: {id: id},
			url: "{{ url('admin/money/delete') }}",
			success: function(data){
				if (data == 0){
					table.draw();
				} else {
					alert("删除失败");
				}
			}
		});
	});

	//全局注册修改按钮事件
	$(document).on( 'click', '.mod_record', function (){
		if ($(this).html() == "改"){
			var id = $(this).attr('r_id');
			if (!confirm("你要修改这条记录 确定吗?")) return;
			wait_for_del = id;
			var row = table.row($(this).parent().parent());
			_i_time.setValue(row.data()['stamp']);
			_i_type.setValue(row.data()['type']);
			_i_category1.setValue(row.data()['c1']);
			_i_category2.setValue(row.data()['c2']);
			_i_account1.setValue(row.data()['a1']);
			_i_account2.setValue(row.data()['a2']);
			_i_price.setValue(row.data()['price']);
			_i_city.setValue(row.data()['city']);
			_i_road.setValue(row.data()['road']);
			_i_company.setValue(row.data()['company']);
			_i_project.setValue(row.data()['project']);
			_i_more.setValue(row.data()['more']);
			_i_fact.setValue(row.data()['fact_time']);

			var row = $(this).parent().parent();
			row.removeClass("odd");
			row.removeClass("even");
			row.addClass("modify");
			$(this).html("消");
		} else if ($(this).html() == "消"){
			if (!confirm("你要撤销修改 确定吗?")) return;
			wait_for_del = 0;
			table.draw();
		}
	});

	//全局注册复制按钮事件
	$(document).on( 'click', '.cp_record', function (){
		var row = table.row($(this).parent().parent());
		_i_time.setValue(row.data()['stamp']);
		_i_type.setValue(row.data()['type']);
		_i_category1.setValue(row.data()['c1']);
		_i_category2.setValue(row.data()['c2']);
		_i_account1.setValue(row.data()['a1']);
		_i_account2.setValue(row.data()['a2']);
		_i_price.setValue(row.data()['price']);
		_i_city.setValue(row.data()['city']);
		_i_road.setValue(row.data()['road']);
		_i_company.setValue(row.data()['company']);
		_i_project.setValue(row.data()['project']);
		_i_more.setValue(row.data()['more']);
		_i_fact.setValue(row.data()['fact_time']);
	});

	// 筛选按钮
	$('#d_submit').on( 'click', function () {
		var _v_time = $('#reportrange').val();
		var _v_month = $('#s_month').val();
		var _v_type = _d_type.getValue(); if (_v_type == "") _v_type = ".*"; _v_type = '^' + _v_type + '$';
		var _v_category1 = _d_category1.getValue(); if (_v_category1 == "") _v_category1 = ".*"; _v_category1 = '^' + _v_category1 + '$';
		var _v_category2 = _d_category2.getValue(); if (_v_category2 == "") _v_category2 = ".*"; _v_category2 = '^' + _v_category2 + '$';
		var _v_account1 = _d_account1.getValue(); if (_v_account1 == "") _v_account1 = ".*"; _v_account1 = '^' + _v_account1 + '$';
		var _v_account2 = _d_account2.getValue(); if (_v_account2 == "") _v_account2 = ".*"; _v_account2 = '^' + _v_account2 + '$';
		var _v_acc = _d_acc.getValue(); if (_v_acc == "") _v_acc = ".*"; _v_acc = '#' + _v_acc + '#';
		var _v_city = unify(_d_city.getValue()); if (_v_city == "") _v_city = ".*"; _v_city = '^' + _v_city + '$';
		var _v_road = unify(_d_road.getValue()); if (_v_road == "") _v_road = ".*"; _v_road = '^' + _v_road + '$';
		var _v_company = _d_company.getValue(); if (_v_company == "") _v_company = ".*"; _v_company = '^' + _v_company + '$';
		var _v_project = _d_project.getValue(); if (_v_project == "") _v_project = ".*"; _v_project = '^' + _v_project + '$';
		var _v_more = _d_more.getValue();

		var tmp_table;
		console.log(table);
		if (_v_month == "所有"){
			tmp_table = table.column('stamp:name').search(_v_time, 'range', false);
		} else {
			tmp_table = table.column('stamp:name').search(_v_month, 'month', false);
		}
		tmp_table
			.column('type:name').search(_v_type, true, false)
			.column('c1:name').search(_v_category1, true, false)
			.column('c2:name').search(_v_category2, true, false)
			.column('a1:name').search(_v_account1, true, false)
			.column('a2:name').search(_v_account2, true, false)
			.column('ajoin:name').search(_v_acc, true, false)
			.column('city:name').search(_v_city, true, false)
			.column('road:name').search(_v_road, true, false)
			.column('company:name').search(_v_company, true, false)
			.column('project:name').search(_v_project, true, false)
			.column('more:name').search(_v_more)
			.draw();
	} );

	// 插入数据按钮
	$('#i_submit').on( 'click', function () {
		var _v_time = _i_time.getValue();
		if (!CheckDateTime(_v_time)) {alert("时间不合法"); return false};
		var _v_type = _i_type.getValue();
		if (_v_type == "") {alert("至少选择一种类型"); return false};
		var _v_category1 = _i_category1.getValue(); 
		if (_v_category1 == "" && (_v_type == "支出" || _v_type == "收入")) {alert("至少选择一种一级分类"); return false};
		var _v_category2 = _i_category2.getValue(); 
		if (_v_category2 == "" && (_v_type == "支出" || _v_type == "收入")) {alert("至少选择一种二级分类"); return false};
		var _v_account1 = _i_account1.getValue(); 
		var _v_account2 = _i_account2.getValue(); 
		if (_v_account1 == "" && _v_account2 == "") {alert("流出与流入账户至少选择一个"); return false};
		var _v_price = _i_price.getValue(); 
		if (_v_price == "" || isNaN(_v_price)) {alert("金额不合法"); return false};
		if (_v_type == "转账" && (_v_account1 == "" || _v_account2 == "")) {alert("转账时必须填写流出与流入账户"); return false};
		if (_v_type == "支出" && (_v_account1 == "" || _v_account2 != "")) {alert("支出时必须且只允许填写流出账户"); return false};
		if (_v_type == "收入" && (_v_account1 != "" || _v_account2 == "")) {alert("收入时必须且只允许填写流入账户"); return false};
		if (_v_type == "变更" && (_v_account1 != "" && _v_account2 != "")) {alert("变更时必须且只允许填写一个账户"); return false};
		var _v_city = _i_city.getValue(); 
		var _v_road = _i_road.getValue(); 
		var _v_company = _i_company.getValue(); 
		var _v_project = _i_project.getValue(); 
		var _v_more = _i_more.getValue();
		var _v_fact = _i_fact.getValue();

		if (!confirm("你要插入这条记录 确定吗?")) return;

		var property_arr = new Array("time", "type", "category1", "category2", "account1", "account2", "price", "city", "road", "company", "project", "more", "fact");
		var condition = new Object();
		for (var i in property_arr){
			eval('condition.' + property_arr[i] + ' = _v_' + property_arr[i]);
		}

		// wait_for_del>0 说明这是修改记录，不是纯插入
		if (wait_for_del > 0){
			condition.wfd = wait_for_del;
		}

		$.ajax( {
			type: "POST",
			url: "{{ url('admin/money/save') }}",
			data: condition,
			success: function(d) {
				if (d == 0){
					table.draw();
					wait_for_del = 0;
				} else {
					alert("插入失败！");
				}
			}
		} );
	} );

/*
	// 月份选择左右按钮
	$('#month_sum_left').on('click', function (){
		var mss = $('#month_sum_scroll');
		if (parseInt(mss.css('left')) >= 0){
			return;
		} else {
			div_scroll(mss, 120);
		}
	});
	$('#month_sum_right').on('click', function (){
		var mss = $('#month_sum_scroll');
		var width = parseInt(mss.css('width'));
		if (parseInt(mss.css('left')) <= 960 - width){
			return;
		} else {
			div_scroll(mss, -120);
		}
	});
*/
	// 月份选择按钮
	$(document).on('click', '#month_sum_table tr', function (){
		var month = $(this).children("td:first-child").html();
		$('#s_month').val(month);
		table.column(1).search(month, "month", false).draw();
	});
	// 账户选择按钮
	$(document).on('click', '#account_sum_table tr', function (e){
		var _account = $(this).children("td:first-child").html();

		if ($(e.target).hasClass('sum-key') || $(e.target).hasClass('sum-sum')) {
			_d_account1.setValue('');
			_d_account2.setValue('');
			_d_acc.setValue(_account == '全部' ? '' : _account);
		} else if ($(e.target).hasClass('sum-out')) {
			_d_account1.setValue(_account == '全部' ? '' : _account);
			_d_account2.setValue('');
			_d_acc.setValue('');
		} else if ($(e.target).hasClass('sum-in')) {
			_d_account1.setValue('');
			_d_account2.setValue(_account == '全部' ? '' : _account);
			_d_acc.setValue('');
		}

		var _v_time = $('#reportrange').val();
		var _v_month = $('#s_month').val();
		var _v_type = _d_type.getValue(); if (_v_type == "") _v_type = ".*"; _v_type = '^' + _v_type + '$';
		var _v_category1 = _d_category1.getValue(); if (_v_category1 == "") _v_category1 = ".*"; _v_category1 = '^' + _v_category1 + '$';
		var _v_category2 = _d_category2.getValue(); if (_v_category2 == "") _v_category2 = ".*"; _v_category2 = '^' + _v_category2 + '$';
		var _v_account1 = _d_account1.getValue(); if (_v_account1 == "") _v_account1 = ".*"; _v_account1 = '^' + _v_account1 + '$';
		var _v_account2 = _d_account2.getValue(); if (_v_account2 == "") _v_account2 = ".*"; _v_account2 = '^' + _v_account2 + '$';
		var _v_acc = _d_acc.getValue(); if (_v_acc == "") _v_acc = ".*"; _v_acc = '#' + _v_acc + '#';
		var _v_city = unify(_d_city.getValue()); if (_v_city == "") _v_city = ".*"; _v_city = '^' + _v_city + '$';
		var _v_road = unify(_d_road.getValue()); if (_v_road == "") _v_road = ".*"; _v_road = '^' + _v_road + '$';
		var _v_company = _d_company.getValue(); if (_v_company == "") _v_company = ".*"; _v_company = '^' + _v_company + '$';
		var _v_project = _d_project.getValue(); if (_v_project == "") _v_project = ".*"; _v_project = '^' + _v_project + '$';
		var _v_more = _d_more.getValue();

		var tmp_table;
		if (_v_month == "所有"){
			tmp_table = table.column('stamp:name').search(_v_time, 'range', false);
		} else {
			tmp_table = table.column('stamp:name').search(_v_month, 'month', false);
		}
		tmp_table
			.column('type:name').search(_v_type, true, false)
			.column('c1:name').search(_v_category1, true, false)
			.column('c2:name').search(_v_category2, true, false)
			.column('a1:name').search(_v_account1, true, false)
			.column('a2:name').search(_v_account2, true, false)
			.column('ajoin:name').search(_v_acc, true, false)
			.column('city:name').search(_v_city, true, false)
			.column('road:name').search(_v_road, true, false)
			.column('company:name').search(_v_company, true, false)
			.column('project:name').search(_v_project, true, false)
			.column('more:name').search(_v_more)
			.draw();

	});

	// 分类选择按钮
	$(document).on('click', '#category_sum_table tr', function (e){
		var _category = $(this).children("td:first-child").html();
		_d_category1.setValue(_category);
		_d_category2.setValue('');

		if ($(e.target).hasClass('sum-out')) {
			_d_type.setValue('支出');
		} else if ($(e.target).hasClass('sum-in')) {
			_d_type.setValue('收入');
		} else {
			_d_type.setValue('');
		}

		var _v_time = $('#reportrange').val();
		var _v_month = $('#s_month').val();
		var _v_type = _d_type.getValue(); if (_v_type == "") _v_type = ".*"; _v_type = '^' + _v_type + '$';
		var _v_category1 = _d_category1.getValue(); if (_v_category1 == "") _v_category1 = ".*"; _v_category1 = '^' + _v_category1 + '$';
		var _v_category2 = _d_category2.getValue(); if (_v_category2 == "") _v_category2 = ".*"; _v_category2 = '^' + _v_category2 + '$';
		var _v_account1 = _d_account1.getValue(); if (_v_account1 == "") _v_account1 = ".*"; _v_account1 = '^' + _v_account1 + '$';
		var _v_account2 = _d_account2.getValue(); if (_v_account2 == "") _v_account2 = ".*"; _v_account2 = '^' + _v_account2 + '$';
		var _v_acc = _d_acc.getValue(); if (_v_acc == "") _v_acc = ".*"; _v_acc = '#' + _v_acc + '#';
		var _v_city = unify(_d_city.getValue()); if (_v_city == "") _v_city = ".*"; _v_city = '^' + _v_city + '$';
		var _v_road = unify(_d_road.getValue()); if (_v_road == "") _v_road = ".*"; _v_road = '^' + _v_road + '$';
		var _v_company = _d_company.getValue(); if (_v_company == "") _v_company = ".*"; _v_company = '^' + _v_company + '$';
		var _v_project = _d_project.getValue(); if (_v_project == "") _v_project = ".*"; _v_project = '^' + _v_project + '$';
		var _v_more = _d_more.getValue();

		var tmp_table;
		if (_v_month == "所有"){
			tmp_table = table.column('stamp:name').search(_v_time, 'range', false);
		} else {
			tmp_table = table.column('stamp:name').search(_v_month, 'month', false);
		}
		tmp_table
			.column('type:name').search(_v_type, true, false)
			.column('c1:name').search(_v_category1, true, false)
			.column('c2:name').search(_v_category2, true, false)
			.column('a1:name').search(_v_account1, true, false)
			.column('a2:name').search(_v_account2, true, false)
			.column('ajoin:name').search(_v_acc, true, false)
			.column('city:name').search(_v_city, true, false)
			.column('road:name').search(_v_road, true, false)
			.column('company:name').search(_v_company, true, false)
			.column('project:name').search(_v_project, true, false)
			.column('more:name').search(_v_more)
			.draw();

	});

	// 分类2选择按钮
	$(document).on('click', '#category2_sum_table tr', function (e){
		var _category = $(this).children("td:first-child").html().split('/');
		
		_d_category1.setValue(_category[0]);
		_d_category2.setValue(_category[1]);

		if ($(e.target).hasClass('sum-out')) {
			_d_type.setValue('支出');
		} else if ($(e.target).hasClass('sum-in')) {
			_d_type.setValue('收入');
		} else {
			_d_type.setValue('');
		}

		var _v_time = $('#reportrange').val();
		var _v_month = $('#s_month').val();
		var _v_type = _d_type.getValue(); if (_v_type == "") _v_type = ".*"; _v_type = '^' + _v_type + '$';
		var _v_category1 = _d_category1.getValue(); if (_v_category1 == "") _v_category1 = ".*"; _v_category1 = '^' + _v_category1 + '$';
		var _v_category2 = _d_category2.getValue(); if (_v_category2 == "") _v_category2 = ".*"; _v_category2 = '^' + _v_category2 + '$';
		var _v_account1 = _d_account1.getValue(); if (_v_account1 == "") _v_account1 = ".*"; _v_account1 = '^' + _v_account1 + '$';
		var _v_account2 = _d_account2.getValue(); if (_v_account2 == "") _v_account2 = ".*"; _v_account2 = '^' + _v_account2 + '$';
		var _v_acc = _d_acc.getValue(); if (_v_acc == "") _v_acc = ".*"; _v_acc = '#' + _v_acc + '#';
		var _v_city = unify(_d_city.getValue()); if (_v_city == "") _v_city = ".*"; _v_city = '^' + _v_city + '$';
		var _v_road = unify(_d_road.getValue()); if (_v_road == "") _v_road = ".*"; _v_road = '^' + _v_road + '$';
		var _v_company = _d_company.getValue(); if (_v_company == "") _v_company = ".*"; _v_company = '^' + _v_company + '$';
		var _v_project = _d_project.getValue(); if (_v_project == "") _v_project = ".*"; _v_project = '^' + _v_project + '$';
		var _v_more = _d_more.getValue();

		var tmp_table;
		if (_v_month == "所有"){
			tmp_table = table.column('stamp:name').search(_v_time, 'range', false);
		} else {
			tmp_table = table.column('stamp:name').search(_v_month, 'month', false);
		}
		tmp_table
			.column('type:name').search(_v_type, true, false)
			.column('c1:name').search(_v_category1, true, false)
			.column('c2:name').search(_v_category2, true, false)
			.column('a1:name').search(_v_account1, true, false)
			.column('a2:name').search(_v_account2, true, false)
			.column('ajoin:name').search(_v_acc, true, false)
			.column('city:name').search(_v_city, true, false)
			.column('road:name').search(_v_road, true, false)
			.column('company:name').search(_v_company, true, false)
			.column('project:name').search(_v_project, true, false)
			.column('more:name').search(_v_more)
			.draw();

	});

	// 预设的按钮
	$(".preset").on('click', function (e) {
		var val_array = $(this).attr("data-preset").split("|");
		if (val_array.length != 12) {
			alert("格式不对");
			return;
		}
		_i_type.setValue(val_array[0]);
		_i_category1.setValue(val_array[1]);
		_i_category2.setValue(val_array[2]);
		_i_account1.setValue(val_array[3]);
		_i_account2.setValue(val_array[4]);
		_i_price.setValue(val_array[5]);
		_i_city.setValue(val_array[6]);
		_i_road.setValue(val_array[7]);
		_i_company.setValue(val_array[8]);
		_i_project.setValue(val_array[9]);
		_i_more.setValue(val_array[10]);
		_i_fact.setValue(val_array[11]);
	});

	$('#block_title_suggest').on('click', function (e) {
		$('#suggest').toggle();
	});
} );

</script>

<body class="dt-example">
	<div class="container">
		<div class=block_title>分类汇总</div>
		<div id=sum_tables>
			<div><table id=month_sum_table><thead><th>月份</th><th>支出</th><th>收入</th><th>结余</th></thead><tbody></tbody></table></div>
			<div><table id=account_sum_table><thead><th>账户</th><th>流出</th><th>流入</th><th>余额</th></thead><tbody></tbody></table></div>
			<div><table id=category_sum_table><thead><th>分类1</th><th>支出</th><th>收入</th><th>结余</th></thead><tbody></tbody></table></div>
			<div><table id=category2_sum_table><thead><th>分类2</th><th>支出</th><th>收入</th><th>结余</th></thead><tbody></tbody></table></div>
		</div>

		<div class=block_title id=block_title_suggest>分类建议(点击展开)</div>
		<div id=suggest style="display: none">
			<table><tbody>
			<tr>
				<td><b>服饰</b>：衣服、裤子、袜子、鞋、眼镜</td>
				<td><b>居家</b>：日用，洗澡，维修，洗衣，水电，住宿，文印，理发，快递，房租，家具</td>
				<td><b>人情</b>：孝敬(给长辈的)，关爱(亲密的人间)，红包(人际间红包往来)，亲友(给一般亲戚和朋友)，捐助</td>
				<td rowspan=4><b>交通</b>：车(买车、洗车、修车)，车险，<br>停车，油费，过路费，罚款(违章)，<br>飞机，火车，公交，地铁，轮渡，<br>出租车，客车，自行车，顺风车</td>
			</tr><tr>
				<td><b>房产</b>：首付，税费，按揭，利息，装修</td>
				<td><b>其它</b>：其它(简单的无法归类的花费)，烂账(不记得花在哪儿了，但是对不上账)</td>
				<td><b>数码</b>：话费，配件(小宗数码产品)，大件(大宗产品，电器等)，充值(会员充值等)，主机，网费(上网费用)</td>
			</tr><tr>
				<td><b>饮食</b>：消夜，晚饭，午饭，早饭，零食，买菜</td>
				<td><b>金融</b>：投资(股票、基金等)，税费(跟银行有关的手续费、滞纳金等)，利息(银行结息)</td>
				<td><b>工作</b>：工资(现金与股票)，福利(医保、公积金、个税退款、过节卡、报销等)，兼职(工作以外收入)，党费</td>
			</tr><tr>
				<td><b>医疗</b>：补助，药品，治疗(挂号、诊疗等)，体检</td>
				<td><b>体育</b>：桌球，滑冰，保龄球，赛车，游泳，网球，射箭，羽毛球，攀岩，健身，滑雪，潜水</td>
				<td><b>娱乐</b>：游玩(景点门票、旅游)，唱歌，电影，桌游，休闲(简单娱乐活动)，博彩，麻将，音乐(演唱会、音乐会)，话剧，德扑</td>
			</tr>
			</tbody></table>
		</div>
		<div class=block_title>数据筛选</div>
		<div id="div_filter">
			<div id="d_time"></div>
			<div id="d_month"></div>
			<div id="d_type"></div>
			<div id="d_category1"></div>
			<div id="d_category2"></div>
			<div id="d_account1"></div>
			<div id="d_account2"></div>
			<div id="d_acc"></div>
			<div id="d_city"></div>
			<div id="d_road"></div>
			<div id="d_company"></div>
			<div id="d_project"></div>
			<div id="d_more"></div>
			<div id="d_submit"></div>
		</div>

		<div class=block_title>插入/修改数据</div>
		<div id="div_insert">
			<div class="add_now">NOW</div>
			<div id="i_time"></div>
			<div id="i_type"></div>
			<div id="i_category1"></div>
			<div id="i_category2"></div>
			<div id="i_account1"></div>
			<div id="i_account2"></div>
			<div id="i_price"></div>
			<div id="i_city"></div>
			<div id="i_road"></div>
			<div id="i_company"></div>
			<div id="i_project"></div>
			<div id="i_more"></div>
			<div id="i_fact"></div>
			<div id="i_submit"></div>
		</div>

		<div class=block_title>预设</div>
		<div>
			<div class=preset data-preset="支出|饮食|零食|浦发RMB||30|上海|金虹桥商场|喜茶|||">喜茶</div>
			<div class=preset data-preset="支出|交通|停车|浦发RMB||9|上海|长泰广场||||">长泰停车</div>
			<div class=preset data-preset="支出|交通|油费|中石油卡||260|上海|妙境路|中石油||5.47|">妙境加油</div>
			<div class=preset data-preset="转账|||招行3876|现金|1000|上海|||||">取钱</div>
			<div class=preset data-preset="支出|数码|主机|浦发RMB||169.16|上海||||vultr.com|">Vultr</div>
			<div class=preset data-preset="支出|交通|停车|现金||16|上海|中山医院||||">中山医院停车</div>
			<div class=preset data-preset="支出|饮食|晚饭|浦发RMB||30|上海|||||">晚饭信用卡</div>
			<div class=preset data-preset="支出|饮食|晚饭|现金||30|上海|||||">晚饭现金</div>
		</div>

<!--
		<div id="month_sum_banner">
			<div id="month_sum_left">
				<
			</div>
			<div id="month_sum">
				<div id="month_sum_scroll"></div>
			</div>
			<div id="month_sum_right">
				>
			</div>
		</div>
		<div id="tip_total">
			<div id="d_account"></div>
			账户汇总： 总流入
			<div id="account_income"></div>
			， 总支出
			<div id="account_cost"></div>
			， 结余
			<div id="account_outcome"></div>
		</div>
-->

		<div class=block_title>数据明细</div>
		<div id="tip_total">
			本页流水： 总流入
			<div id="total_income"></div>
			， 总流出 
			<div id="total_cost"></div>
			， 结余
			<div id="total_outcome"></div>
		</div>
		<section>
			<table id="example" class="display" cellspacing="0" width="100%">
				<thead id='table_head'>
					<tr>
						<th>操作</th>
						<th>时间</th>
						<th>类型</th>
						<th>类一</th>
						<th>类二</th>
						<th>流出</th>
						<th>流入</th>
						<th>价格</th>
						<th>城市</th>
						<th>地点</th>
						<th>商家</th>
						<th>项目</th>
						<th>备注</th>
						<th>Ajoin</th>
						<th>生效</th>
					</tr>
				</thead>
			</table>
		</section>
	</div>

</body>
</html>
