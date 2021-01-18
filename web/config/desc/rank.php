<?php

return [
	'table' => [
		'head' => [
			'overview' => ['cn' => '即时概览', 'en' => 'Live Overview', ],
			'rank' => ['cn' => '排名', 'en' => 'Ranking', ],
			'point' => ['cn' => '积分', 'en' => 'Points', ],
			'player' => ['cn' => '球员', 'en' => 'Player', ],
			'period' => ['cn' => function ($period) {return $period == 'race' || $period == 'nextgen' ? '赛季' : '周期';} , 'en' => function ($period) {return $period == 'race' || $period == 'nextgen' ? 'Year-to-date' : 'Last 52 Weeks';}, ],
			'qiji' => ['cn' => function ($type, $sd) {return "起计";}, 'en' => function ($type, $sd) {return $type == "atp" ? "18th" : ($sd == "s" ? "16th" : "11th");}, ],
			'current' => ['cn' => '本周', 'en' => 'Current', ],
			'predict' => ['cn' => '预测', 'en' => 'Prediction', ],

			'name' => ['cn' => '姓名', 'en' => 'Name', ],
			'move' => ['cn' => '升降', 'en' => 'Mv.', ],
			'official' => ['cn' => '官方', 'en' => 'Offi.', ],
			'careerHigh' => ['cn' => '最佳', 'en' => 'CH', ],
			'alt' => ['cn' => '替补', 'en' => 'Alt', ],
			'drop' => ['cn' => '-', 'en' => '-', ],
			'add' => ['cn' => '+', 'en' => '+', ],
			'age' => ['cn' => '年龄', 'en' => 'Age', ],
			'birth' => ['cn' => '生日', 'en' => 'DOB', ],
			'nation' => ['cn' => '国籍', 'en' => 'Nat.', ],
			'titles' => ['cn' => '冠军', 'en' => 'Ttls', ],
			'tourCount' => ['cn' => '参赛', 'en' => 'Ply.', ],
			'qz0' => ['cn' => '强制0', 'en' => '0s', ],
			'streak' => ['cn' => '连胜', 'en' => 'Stk.', ],
			'prize' => ['cn' => '奖金', 'en' => 'Prizes', ],
			'win' => ['cn' => '胜', 'en' => 'Wins', ],
			'lose' => ['cn' => '负', 'en' => 'Losses', ],
			'winRate' => ['cn' => '胜率', 'en' => 'Win%', ],
			'qijiTour' => ['cn' => '赛事', 'en' => 'Tour', ],
			'qijiPoint' => ['cn' => '分', 'en' => 'Point', ],
			'tour' => ['cn' => '赛事', 'en' => 'Tour', ],
			'partner' => ['cn' => '搭档', 'en' => 'Partner', ],
			'opponent' => ['cn' => '对手', 'en' => 'Opponent', ],
		],
		'columnFilter' => [
			'columnFilter' => ['cn' => '筛选列', 'en' => 'Column Filter', ],
			'move' => ['cn' => '升降', 'en' => 'Move', ],
			'officialRank' => ['cn' => '官方排名', 'en' => 'Official Ranking', ],
			'careerHigh' => ['cn' => '生涯最高', 'en' => 'Career High', ],
			'altScore' => ['cn' => '替补分', 'en' => 'Alternative Points', ],
			'drop' => ['cn' => '本周失效', 'en' => 'Drop', ],
			'add' => ['cn' => '本周新增', 'en' => 'Add', ],
			'age' => ['cn' => '年龄', 'en' => 'Age', ],
			'birth' => ['cn' => '生日', 'en' => 'DOB', ],
			'nation' => ['cn' => '国籍', 'en' => 'Nationality', ],
			'titles' => ['cn' => '赛季冠军数', 'en' => 'YTD Titles', ],
			'tourCount' => ['cn' => function ($period) {return ($period == "race" || $period == "nextgen" ? '赛季' : '周期') . '参赛数';}, 'en' => function ($period) {return ($period == "race" || $period == "nextgen" ? 'YTD' : '52 Weeks') . ' Plays';}, ],
			'qz0' => ['cn' => function ($period) {return ($period == "race" || $period == "nextgen" ? '赛季' : '周期') . '强制0';}, 'en' => function ($period) {return ($period == "race" || $period == "nextgen" ? 'YTD' : '52 Weeks') . ' 0s';}, ],
			'streak' => ['cn' => '连胜', 'en' => 'Streaks', ],
			'prize' => ['cn' => function ($period) {return ($period == "race" || $period == "nextgen" ? '赛季' : '周期') . '奖金';}, 'en' => function ($period) {return ($period == "race" || $period == "nextgen" ? 'YTD' : '52 Weeks') . ' Prizes';}, ],
			'win' => ['cn' => function ($period) {return ($period == "race" || $period == "nextgen" ? '赛季' : '周期') . '胜场数';}, 'en' => function ($period) {return ($period == "race" || $period == "nextgen" ? 'YTD' : '52 Weeks') . ' Wins';}, ],
			'lose' => ['cn' => function ($period) {return ($period == "race" || $period == "nextgen" ? '赛季' : '周期') . '负场数';}, 'en' => function ($period) {return ($period == "race" || $period == "nextgen" ? 'YTD' : '52 Weeks') . ' Losses';}, ],
			'winRate' => ['cn' => function ($period) {return ($period == "race" || $period == "nextgen" ? '赛季' : '周期') . '胜率';}, 'en' => function ($period) {return ($period == "race" || $period == "nextgen" ? 'YTD' : '52 Weeks') . ' Win%';}, ],
			'qijiTour' => ['cn' => function ($type, $sd) {return "起计分赛事";}, 'en' => function ($type, $sd) {return ($type == "atp" ? "18th" : ($sd == "s" ? "16th" : "11th")) . ' Tour';}, ],
			'qijiPoint' => ['cn' => function ($type, $sd) {return "起计分";}, 'en' => function ($type, $sd) {return ($type == "atp" ? "18th" : ($sd == "s" ? "16th" : "11th")) . ' Point';}, ],
			'tour' => ['cn' => '本周赛事', 'en' => 'Current Tour', ],
			'partner' => ['cn' => '本周搭档', 'en' => 'Current Partner', ],
			'opponent' => ['cn' => '本周对手', 'en' => 'Current Opponent', ],
		],
		'rowFilter' => [
			'rowFilter' => ['cn' => '筛选行', 'en' => 'Row Filter', ],
			'show10' => ['cn' => '显示10条', 'en' => 'Show 10', ],
			'show25' => ['cn' => '显示25条', 'en' => 'Show 25', ],
			'show50' => ['cn' => '显示50条', 'en' => 'Show 50', ],
			'show100' => ['cn' => '显示100条', 'en' => 'Show 100', ],
			'showAll' => ['cn' => '显示全部', 'en' => 'Show All', ],
			'searchPlayer' => ['cn' => '搜球员', 'en' => 'Search Player', ],
			'highlightCountry' => ['cn' => '高亮国家/地区', 'en' => 'Highlight Country', ],
			'filterCountry' => ['cn' => '筛选国家/地区', 'en' => 'Filter Country', ],
			'filterYear' => ['cn' => '年', 'en' => 'Year', ],
			'filterMonth' => ['cn' => '月', 'en' => 'Month', ],
			'filterDay' => ['cn' => '日', 'en' => 'Day', ],
			'filterTour' => ['cn' => '筛选本周赛事', 'en' => 'Current Tour', ],
		],
		'timeTip' => [
			'time' => ['cn' => '时间', 'en' => 'Time', ],
			'live' => ['cn' => '即时', 'en' => 'Live for', ],
			'official' => ['cn' => '官方', 'en' => 'Official for', ],
			'update' => ['cn' => '最后更新', 'en' => 'Last Update', ],
			'unknown' => ['cn' => '未知', 'en' => 'Unknown', ],
		],
		'construct' => [
			'processing' => ['cn' => '数据加载中···', 'en' => 'Processing...', ],
			'empty' => ['cn' => '没有记录', 'en' => 'No Records', ],
			'pagePrefix' => ['cn' => '第', 'en' => 'Page ', ],
			'pageSuffix' => ['cn' => '页', 'en' => '', ],
		],
	],

	'piechart' => [
		'win' => ['cn' => '胜', 'en' => 'Win', ],
		'lose' => ['cn' => '负', 'en' => 'Loss', ],
		'totalPoint' => ['cn' => '总分', 'en' => 'Total', ],
	],

	'breakdown' => [
		'bytype' => ['cn' => '按赛事类型', 'en' => 'By Level', ],
		'bysfc' => ['cn' => '按场地类型', 'en' => 'By Surface', ],
		'bydate' => ['cn' => '按失效时间', 'en' => 'By Drop Date', ],
		'level' => ['cn' => '级别', 'en' => 'Level', ],
		'tour' => ['cn' => '赛事', 'en' => 'Tournament', ],
		'point' => ['cn' => '积分', 'en' => 'Points', ],
		'round' => ['cn' => '轮次', 'en' => 'Round', ],
	],

];