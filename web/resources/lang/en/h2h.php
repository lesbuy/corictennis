<?php

return [
	'levelIntro' => [
		'GS' => 'Grand Slam',
		'1000' => 'ATP 1000（1990 ~ ）',
		'WTA1000' => 'WTA 1000（2021 ~ ），前身为超五顶级赛',
		'WTA1000M' => 'WTA 1000强制赛（2021 ~ ），WTA1000强制赛，前身为强制顶级赛(皇冠明珠赛)',
		'500' => 'ATP 500（2009 ~ ），ATP500赛',
		'WTA500' => 'WTA 500（2021 ~ ），WTA500赛，前身为顶级赛',
		'250' => 'ATP 250（2009 ~ ），ATP250赛',
		'WTA250' => 'WTA 250（2021 ~ ），WTA250赛，前身为国际赛',
		'CSS' => 'ATP Championship Series, Single Week（1990 ~ 1999），ATP冠军系列赛，相当于ATP1000赛',
		'MS' => 'ATP Master Series（2000 ~ 2008），ATP大师系列赛，相当于ATP1000赛',
		'ISG' => 'ATP International Series Gold（2004 ~ 2008），相当于ATP500赛',
		'CS' => 'ATP Championship Series（2000 ~ 2003），相当于ATP500赛',
		'CSD' => 'ATP Championship Series Double-Up Week（1990 ~ 1999），相当于ATP500赛',
		'IS' => 'ATP International Series（2004 ~ 2008），相当于ATP250赛',
		'WS' => 'ATP World Series（1993 ~ 2003），相当于ATP250赛',
		'WSD' => 'ATP World Series Designated Week（1990 ~ 1992），相当于ATP250赛',
		'WSF' => 'ATP World Series Free Week（1990 ~ 1992），相当于ATP250赛',
		'PM' => 'WTA Premier Mandatory（2009 ~ 2020），强制顶级赛，相当于WTA1000强制赛',
		'P5' => 'WTA Premier 5（2009 ~ 2020），超五顶级赛，相当于WTA1000赛',
		'P700' => 'WTA Premier（2009 ~ 2020），顶级赛，相当于WTA500赛',
		'Int' => 'WTA Premier（2009 ~ 2020），顶级赛，相当于WTA500赛',
		'T1' => 'WTA Tie I（1990 ~ 2008），一级赛，相当于WTA1000赛与WTA1000强制赛',
		'T2' => 'WTA Tie II（1990 ~ 2008），二级赛，相当于WTA500赛',
		'T3' => 'WTA Tie III（1990 ~ 2008），三级赛，相当于WTA250赛',
		'T4' => 'WTA Tie IV（1990 ~ 2008），四级赛，相当于WTA250赛',
		'T5' => 'WTA Tie V（1990 ~ 2008），五级赛，相当于WTA250赛',
		'CH' => 'ATP Challengers，ATP挑战赛',
		'125K' => 'WTA 125K（2009 ~ 2020），125K系列赛，相当于WTA125赛',
		'WTA125' => 'WTA 125（2021 ~ ），WTA125赛，前身为125K系列赛',
		'ITF' => 'ITF Circuit，ITF巡回赛',
		'FU' => 'ITF Futures（2000 ~ 2018），ITF希望赛，相当于ITF男子巡回赛',
		'OL' => 'Olympics，奥运会',
		'DC' => 'Davis Cup，戴维斯杯',
		'FC' => 'Fed Cup，联合会杯，现为比莉-简·金杯',
		'YEC' => 'WTA Finals，WTA年终总决赛',
		'WC' => 'ATP Finals，ATP年终总决赛',
		'LC' => 'Laver Cup（2017 ~ ），拉沃尔杯',
		'AC' => 'ATP Cup（2020 ~ ），ATP杯',
		'XXI' => 'ATP Nextgen Finals / WTA Elite Trophy，小年终赛',
		'GP' => 'Grand Prix（1970 ~ 1989），ATP巡回赛的前身',
		'WCT' => 'World Championship Tennis（1968 ~ 1989），1990年与Grand Prix合并为ATP巡回赛',
		'GSC' => 'Grand Slam Cup（1990 ~ 1999），大满贯杯',
		'ATP' => 'ATP赛事',
		'WTA' => 'WTA赛事',
	],

	'selectBar' => [

		'round' => [
			'w' => 'Winner',
			'f' => 'Finalists',
			's' => 'Semifinalists',
			'q' => 'Quarterfinalists',
			'final' => 'Final',
			'sf' => 'SF',
			'qf' => 'QF',
			'md' => 'Main Draw',
		],

		'method' => [

			'p' => 'VS Player',
			'c' => 'VS Country',
			't' => 'VS Top N',
			'm' => 'VS Many',

		],

		'type' => [

			'atp' => 'Men\'s',
			'wta' => 'Women\'s',
			'mix' => 'Mixed',
			'boy' => 'Boys\'',
			'girl' => 'Girls\'',

		],

		'level' => [

			'a' => 'All Levels',
			'g' => 'GS Only',
			'm' => 'MS/T1/PM/P5 Only',
			't' => 'World Tours Only',
			'ao' => 'Aus Open',
			'rg' => 'Roland Garros',
			'wc' => 'Wimbledon',
			'uo' => 'US Open',
			'yec' => 'YEC',
			'ol' => 'Olympics',
			'dc' => 'Davis/Fed Cup',

			'gs' => 'GS',
			'ms' => ['atp' => '1000', 'wta' => 'PM/P5'],
			'p' => ['atp' => '500', 'wta' => 'P700'],
			'is' => ['atp' => '250', 'wta' => 'IS'],
			'fc' => ['atp' => 'DC', 'wta' => 'FC'],
			'low' => 'CH&ITF',
			'tour' => 'Circuit Tours',

		],

		'md' => [
			'y' => 'Main Draw Only',

		],

		'final' => [
			'y' => 'Final Only',
		],

		'sfc' => [
			'a' => 'All Surfaces',
			'h' => 'Hard Only',
			'c' => 'Clay Only',
			'g' => 'Grass Only',
			'p' => 'Carpet Only',

		],

		'sd' => [
			's' => 'Singles',
			'd' => 'Doubles',
		],

		'filter' => [
			'desc' => 'Filter: ',
			'comma' => ', ',
		],

		'allYear' => 'All Years',
	],

	'select' => [

		'player1' => 'Player 1',
		'player2' => 'Player 2',
		'player' => 'Player',
		'country' => 'Country',
		'topN' => 'Top N',
		'query' => 'Search',

	],

	'warning' => [

		'wrongP1' => 'Wrong Player 1',
		'wrongP2' => 'Wrong Player 2',
		'wrongNation' => 'Wrong Nation',
		'wrongN' => 'Wrong Number',
		'equalP1P2' => 'No Same Players',
		'noDoubleMulti' => 'Many VS Many not supported in Doubles',
		'noResult' => 'No Results',
		'notEnd' => 'Continued',
		'illegal' => 'Illegal Conditions!',

	],

    'selectBarTitle' => [
		'title' => 'Selections',
        'type' => 'H2H Type',
		'sd' => 'S/D Type',
		'notOnlyGS' => 'Not Only GS',
        'playerType' => 'Player Type',
        'tourType' => 'Tour Type',
        'sfcType' => 'Surface Type',
        'roundType' => 'Round Type',
        'itemType' => 'S/D Type',
		'startDate' => 'Start Date',
		'endDate' => 'End Date',
		'weeks' => 'Weeks',
		'totalWeeks' => 'Total',
		'topnweeks' => 'Top :p1 Weeks'
    ],

	'age' => [
		'year' => 'y',
		'day' => 'd',
	],

	'item' => [
		'prize' => 'PRIZE',
		'rank' => 'RANK',
		'partner' => 'PARTNER',
	],

	'thead' => [
		'year' => 'YEAR',
		'level' => 'LEVEL',
		'surface' => 'SFC',
		'event' => 'EVENT',
		'round' => 'R.',
		'result' => 'RESULT',
		'games' => 'GAMES',
	],

];

