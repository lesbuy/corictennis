<?php

return [
	'levelIntro' => [
		'GS' => 'Grand Slam',
		'1000' => 'ATP 1000（1990 ~ ）',
		'WTA1000' => 'WTA 1000（2021 ~ ），former as WTA Premier 5',
		'WTA1000M' => 'WTA 1000 Mandatory（2021 ~ ），former as WTA Premier Mandatory',
		'500' => 'ATP 500（2009 ~ ）',
		'WTA500' => 'WTA 500（2021 ~ ），former as WTA Premier',
		'250' => 'ATP 250（2009 ~ ）',
		'WTA250' => 'WTA 250（2021 ~ ），former as WTA International',
		'CSS' => 'ATP Championship Series, Single Week（1990 ~ 1999），as ATP 1000',
		'MS' => 'ATP Master Series（2000 ~ 2008），as ATP 1000',
		'ISG' => 'ATP International Series Gold（2004 ~ 2008），as ATP 500',
		'CS' => 'ATP Championship Series（2000 ~ 2003），as ATP 500',
		'CSD' => 'ATP Championship Series Double-Up Week（1990 ~ 1999），as ATP 500',
		'IS' => 'ATP International Series（2004 ~ 2008），as ATP 250',
		'WS' => 'ATP World Series（1993 ~ 2003），as ATP 250',
		'WSD' => 'ATP World Series Designated Week（1990 ~ 1992），as ATP 250',
		'WSF' => 'ATP World Series Free Week（1990 ~ 1992），as ATP 250',
		'PM' => 'WTA Premier Mandatory（2009 ~ 2020），as WTA 1000 Mandatory',
		'P5' => 'WTA Premier 5（2009 ~ 2020），as WTA 1000',
		'P700' => 'WTA Premier（2009 ~ 2020），as WTA 500',
		'Int' => 'WTA Premier（2009 ~ 2020），as WTA 250',
		'T1' => 'WTA Tie I（1990 ~ 2008），as WTA 1000 Mandatory and WTA 1000',
		'T2' => 'WTA Tie II（1990 ~ 2008），as WTA 500',
		'T3' => 'WTA Tie III（1990 ~ 2008），as WTA 250',
		'T4' => 'WTA Tie IV（1990 ~ 2008），as WTA 250',
		'T5' => 'WTA Tie V（1990 ~ 2008），as WTA 250',
		'CH' => 'ATP Challengers',
		'125K' => 'WTA 125K Series（2009 ~ 2020），as WTA 125',
		'WTA125' => 'WTA 125（2021 ~ ），former as WTA 125K Series',
		'ITF' => 'ITF Circuit',
		'FU' => 'ITF Futures（2000 ~ 2018），as ITF Mens\' Circuit',
		'OL' => 'Olympics',
		'DC' => 'Davis Cup',
		'FC' => 'Fed Cup，as Billie Jean King Cup',
		'YEC' => 'WTA Finals',
		'WC' => 'ATP Finals',
		'LC' => 'Laver Cup（2017 ~ ）',
		'AC' => 'ATP Cup（2020 ~ ）',
		'XXI' => 'ATP Nextgen Finals / WTA Elite Trophy',
		'GP' => 'Grand Prix（1970 ~ 1989），as ATP Tour',
		'WCT' => 'World Championship Tennis（1968 ~ 1989），merged by Grand Prix as ATP Tour at 1990',
		'GSC' => 'Grand Slam Cup（1990 ~ 1999）',
		'ATP' => 'ATP Tour',
		'WTA' => 'WTA Tour',
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

