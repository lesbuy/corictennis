<?php

return [
	'selectBar' => [

		'round' => [
			'w' => 'Winner',
			'f' => 'Finalists',
			's' => 'Semifinalists',
			'q' => 'Quarterfinalists',
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
			'yec' => 'ATP/WTA Finals',
			'ol' => 'Olympics',
			'dc' => 'Davis/Fed Cup',

			'gs' => 'GS',
			'ms' => ['atp' => '1000', 'wta' => 'PM/P5'],
			'p' => ['atp' => '500', 'wta' => 'P700'],
			'is' => ['atp' => '250', 'wta' => 'IS'],
			'fc' => ['atp' => 'DC', 'wta' => 'FC'],
			'low' => 'CH&ITF',
			'tour' => 'Tour',

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
        'playerType' => 'Player Type',
        'tourType' => 'Tour Type',
        'sfcType' => 'Surface Type',
        'roundType' => 'Round Type',
        'itemType' => 'S/D Type',
		'startDate' => 'Start Date',
		'endDate' => 'End Date',
		'weeks' => 'Weeks',
		'totalWeeks' => 'Total',
		'topnweeks' => 'Top :p1 总周数'
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

