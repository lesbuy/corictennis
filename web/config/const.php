<?php

return [

	'root' => '/home/ubuntu',
	'cdn' => '',

	'translate' => [
		'zh' => '简体中文',
//		'tw' => '繁體中文',
		'en' => 'English',
		'ja' => '日本語',
/*
		'ru' => 'ру́сский',
		'fr' => 'Français',
		'es' => 'Español',
		'pt' => 'Português',
		'it' => 'Italiano',
		'de' => 'Deutsch',
		'ro' => 'limba română',
		'pl' => 'język polski',
		'et' => 'eesti keel',
		'nl' => 'Nederlands',
		'ko' => '한국말',
		'da' => 'dansk',
		'uk' => 'українська мова',
		'cs' => 'čeština',
		'be' => 'беларуская мова',
*/
	],

	'USERTYPE_BAIDU' => 0,
	'USERTYPE_WEIBO' => 1,
	'USERTYPE_PIE' => 2,
	'USERTYPE_WEIXIN' => 3,
	'USERTYPE_WX_HAOLAN' => 4,
	'USERTYPE_WX_PIE' => 5,
	'USERTYPE_WX_WUHAN' => 6,
	'USERTYPE_FACEBOOK' => 7,
	'USERTYPE_GOOGLE' => 8,
	'USERTYPE_WX_WILSON' => 9,
	'USERTYPE_WX_WEIMENG' => 10,
	'USERTYPE_WX_WANGQIU' => 11,

	'TYPE2STRING' => [
		0 => 'baidu', 1 => 'weibo', 3 => 'weixin', 7 =>'facebook', 8 => 'google', 11 => 'wangqiu'
	],

	'TYPE2ICON' => [
		0 => '&#xe611;',
		1 => '&#xe60f;',
		2 => '',
		3 => '&#xe610;',
		4 => '&#xe610;',
		5 => '&#xe610;',
		6 => '&#xe610;',
		7 => '&#xe61d;',
		8 => '&#xe61e;',
		9 => '&#xe610;',
		10 => '&#xe610;',
		11 => '&#xe610;',
	],

	'TYPE2ICONNAME' => [
		0 => 'baidu',
		1 => 'weibo',
		2 => '',
		3 => 'weixin',
		4 => 'weixin',
		5 => 'weixin',
		6 => 'weixin',
		7 => 'facebook',
		8 => 'google',
		9 => 'weixin',
		10 => 'weixin',
		11 => 'weixin',
	],

	'groundColor' => [
/*
		'Hard' => '#028edd',
		'Hard(I)' => '#028edd',
		'Clay' => '#d9770c',
		'Clay(I)' => '#d9770c',
		'Grass' => '#a2ae45',
		'Grass(I)' => '#a2ae45',
		'Carpet' => '#7c7875',
		'Carpet(I)' => '#7c7875',
*/
		'Hard' => '#48a9c5',
		'Hard(I)' => '#48a9c5',
		'Clay' => '#f85a40',
		'Clay(I)' => '#f85a40',
		'Grass' => '#a4c639',
		'Grass(I)' => '#a4c639',
		'Carpet' => '#fd9f3e',
		'Carpet(I)' => '#fd9f3e',
		'Mixed' => '#bbb',
		'' => '#bbb',
	],

	'levelColor' => [
		'GS' => '#ee4c58',
		'YEC' => '#00a78e',
		'WC' => '#00a78e',
		'1000' => '#f07654',
		'PM' => '#f07654',
		'P5' => '#f38654',
		'P700' => '#f69653',
		'500' => '#f69653',
		'250' => '#fdb94e',
		'Int' => '#fdb94e',
		'125K' => '#00ad45',
		'CH' => '#00ad45',
		'FU' => '#5ecc62',
		'ITF' => '#5ecc62',
		'AC' => '#5ecc62',
	],

	'sideColor' => [
		'home' => '#34495E',
		'away' => '#3B9DD4',
		'third' => '#eee',
	],

	'wlColor' => [
		'win' => '#51d5fd',
		'lose' => '#ceead1',
		'wo' => '#f5f5f5',
	],

	'globalColor' => [
		'lightGray' => '#e8e8e8',
		'midGray' => '#868990',
		'weaken' => '#c8c8c8',
		'win' => '#34bf49',
		'diff' => '#ff0000',
		'adv' => '#93dee8',
		'hl' => '#005dff',
		'sep' => [
			'light' => '#202023',
			'dark' => '#c8c8c8',
		],
		'hl_tp' => 'rgba(0,93,255,0.7)',
		'white' => '#fff',
		'black' => '#262728',
	],

	'barColor' => [
		'W' => '#005dff',
		'F' => '#5b99ff',
		'SF' => '#aecdff',
		'QF' => '#d8dce2',
		'Attend' => [
			'light' => '#e8e8e8',
			'dark' => '#363738',
		],
	],

	'logo' => function ($data) { return url(env('CDN') . '/images/level_logo/' . $data . '.png'); },
	'logosvg' => function ($data) { return url(env('CDN') . '/images/tour_logo/' . $data . '.svg'); },
	'logostamp' => function () { return "<div class=\"logostamp\" style=\"background-image: url(" . url(env('CDN') . '/images/tips/logostamp.png') . ");\"></div>"; },

	'loading' => function ($data) {
		return $data == "ring" ? '<div class="CLoad CLoadRing"><div></div><div></div><div></div><div></div></div>' : (
			$data == "square3" ? '<div class="CLoad CLoadSquare3"><img src="' . url(env('CDN') . '/images/tips/load_square3.svg') . '" /></div>' : ""
		);
	},

	'pic' => [
		'yes' => function () { return url(env('CDN') . '/images/tips/yes.png'); },
	],

	'round2id' => [

		'- Q' => 1,
		'Q1' => 2,
		'Q2' => 4,
		'Q3' => 6,
		'Q4' => 7,
		'Q-R1' => 2,
		'Q-R2' => 4,
		'Q-R3' => 6,
		'Q-R4' => 7,
		'QR1' => 2,
		'QR2' => 4,
		'QR3' => 6,
		'QR4' => 7,
		'Q-R128' => 8,
		'Q-R64' => 10,
		'Q-R32' => 12,
		'Q-R16' => 14,
		'Q-Q' => 16,
		'Q-S' => 18,
		'Q-PRE' => 20,
		'Q-F' => 22,
		'Q-W' => 24,
		'Q-' => 24,
		'Q-Round' => 24,
		'QRF' => 25,
		'RR' => 26,
		'RR1' => 28,
		'RR2' => 30,
		'R1' => 32,
		'R2' => 34,
		'R3' => 36,
		'R4' => 38,
		'R5' => 40,
		'R6' => 42,
		'R128' => 44,
		'R64' => 46,
		'R32' => 48,
		'R16' => 50,
		'QF' => 52,
		'SF' => 54,
		'OB' => 56,
		'PRE' => 56,
		'3/4' => 56,
		'3/4 PO' => 56,
		'F' => 58,
		'W' => 60,

	],

	'id2round' => [

		2 => 'Q1',
		4 => 'Q2',
		6 => 'Q3',
		8 => 'Q-R128',
		10 => 'Q-R64',
		12 => 'Q-R32',
		14 => 'Q-R16',
		16 => 'Q-Q',
		18 => 'Q-S',
		20 => 'Q-PRE',
		22 => 'Q-F',
		24 => 'QR',
		25 => 'QRF',
		26 => 'RR',
		28 => 'RR1',
		30 => 'RR2',
		32 => 'R1',
		34 => 'R2',
		36 => 'R3',
		38 => 'R4',
		40 => 'R5',
		42 => 'R6',
		44 => 'R128',
		46 => 'R64',
		48 => 'R32',
		50 => 'R16',
		52 => 'QF',
		54 => 'SF',
		56 => '3/4',
		58 => 'F',
		60 => 'W',

	],

	'grandslam' => [

		'type2id' => [

			'MS' => 1,
			'WS' => 2,
			'MD' => 3,
			'WD' => 4,
			'XD' => 5,
			'BS' => 21,
			'GS' => 22,
			'BD' => 23,
			'GD' => 24,
			'QS' => 11,
			'PS' => 12,
			'QD' => 13,
			'PD' => 14,
			'BQ' => 25,
			'GQ' => 26,
			'CS' => 31,
			'DS' => 32,
			'CD' => 33,
			'DD' => 34,
			'US' => 35,
			'UD' => 36,
			'LD' => 44,
			'ZD' => 45,
			'JD' => 46,
			'SD' => 47,
			'TD' => 41,
			'OD' => 42,
			'RD' => 43,
			'CM' => 51,
			'CW' => 52,
			'ML' => 53,
			'WL' => 54,
			'1S' => 71,
			'1D' => 72,
			'2S' => 73,
			'2D' => 74,
			'3S' => 75,
			'3D' => 76,
			'4S' => 77,
			'4D' => 78,
			'5S' => 79,
			'5D' => 80,

		],

		'id2type' => [

			'1' => 'MS',
			'2' => 'WS',
			'3' => 'MD',
			'4' => 'WD',
			'5' => 'XD',
			'21' => 'BS',
			'22' => 'GS',
			'23' => 'BD',
			'24' => 'GD',
			'11' => 'QS',
			'12' => 'PS',
			'13' => 'QD',
			'14' => 'PD',
			'25' => 'BQ',
			'26' => 'GQ',
			'31' => 'CS',
			'32' => 'DS',
			'33' => 'CD',
			'34' => 'DD',
			'35' => 'US',
			'36' => 'UD',
			'44' => 'LD',
			'45' => 'ZD',
			'46' => 'JD',
			'47' => 'SD',
			'41' => 'TD',
			'42' => 'OD',
			'43' => 'RD',
			'51' => 'CM',
			'52' => 'CW',
			'53' => 'ML',
			'54' => 'WL',
			'71' => '1S',
			'72' => '1D',
			'73' => '2S',
			'74' => '2D',
			'75' => '3G',
			'76' => '3D',
			'77' => '4S',
			'78' => '4D',
			'79' => '5S',
			'80' => '5D',

		],

		'id2oopid' => [
			'1' => 0,  
			'2' => 1,  
			'3' => 2,  
			'4' => 3,  
			'5' => 4,  
			'21' => 5,  
			'22' => 6,  
			'23' => 7,  
			'24' => 8,  
			'11' => 0,  
			'12' => 1,  
			'13' => 2,  
			'14' => 3,  
			'25' => 5,  
			'26' => 6,  
			'31' => 9,  
			'32' => 11, 
			'33' => 10, 
			'34' => 12, 
			'35' => 13, 
			'36' => 14, 
			'44' => 15, 
			'45' => 16, 
			'41' => 15, 
			'42' => 15, 
			'43' => 16, 
			'46' => 15, 
			'47' => 15, 
			'51' => 17, 
			'52' => 18, 
			'53' => 15, 
			'54' => 16, 
			'71' => 71, 
			'72' => 72, 
			'73' => 73, 
			'74' => 74, 
			'75' => 75, 
			'76' => 76, 
			'77' => 77, 
			'78' => 78, 
			'79' => 79, 
			'80' => 80,
		],

	],

	'flag' => [

		'AFG' => '&#x1f1e6;&#x1f1eb;',
		'ALB' => '&#x1f1e6;&#x1f1f1;',
		'ALG' => '&#x1f1e9;&#x1f1ff;',
		'AND' => '&#x1f1e6;&#x1f1e9;',
		'ANG' => '&#x1f1e6;&#x1f1f4;',
		'ANT' => '&#x1f1e6;&#x1f1ec;',
		'ARG' => '&#x1f1e6;&#x1f1f7;',
		'ARM' => '&#x1f1e6;&#x1f1f2;',
		'ARU' => '&#x1f1e6;&#x1f1fc;',
		'ASA' => '&#x1f1e6;&#x1f1f8;',
		'AUS' => '&#x1f1e6;&#x1f1fa;',
		'AUT' => '&#x1f1e6;&#x1f1f9;',
		'AZE' => '&#x1f1e6;&#x1f1ff;',
		'BAH' => '&#x1f1e7;&#x1f1f8;',
		'BAN' => '&#x1f1e7;&#x1f1e9;',
		'BAR' => '&#x1f1e7;&#x1f1e7;',
		'BDI' => '&#x1f1e7;&#x1f1ee;',
		'BEL' => '&#x1f1e7;&#x1f1ea;',
		'BEN' => '&#x1f1e7;&#x1f1ef;',
		'BER' => '&#x1f1e7;&#x1f1f2;',
		'BHU' => '&#x1f1e7;&#x1f1f9;',
		'BIH' => '&#x1f1e7;&#x1f1e6;',
		'BIZ' => '&#x1f1e7;&#x1f1ff;',
		'BLR' => '&#x1f1e7;&#x1f1fe;',
		'BOL' => '&#x1f1e7;&#x1f1f4;',
		'BOT' => '&#x1f1e7;&#x1f1fc;',
		'BRA' => '&#x1f1e7;&#x1f1f7;',
		'BRN' => '&#x1f1e7;&#x1f1ed;',
		'BRU' => '&#x1f1e7;&#x1f1f3;',
		'BUL' => '&#x1f1e7;&#x1f1ec;',
		'BUR' => '&#x1f1e7;&#x1f1eb;',
		'CAF' => '&#x1f1e8;&#x1f1eb;',
		'CAM' => '&#x1f1f0;&#x1f1ed;',
		'CAN' => '&#x1f1e8;&#x1f1e6;',
		'CAY' => '&#x1f1f0;&#x1f1fe;',
		'CGO' => '&#x1f1e8;&#x1f1ec;',
		'CHA' => '&#x1f1f9;&#x1f1e9;',
		'CHI' => '&#x1f1e8;&#x1f1f1;',
		'CHN' => '&#x1f1e8;&#x1f1f3;',
		'CIV' => '&#x1f1e8;&#x1f1ee;',
		'CMR' => '&#x1f1e8;&#x1f1f2;',
		'COD' => '&#x1f1e8;&#x1f1e9;',
		'COK' => '&#x1f1e8;&#x1f1f0;',
		'COL' => '&#x1f1e8;&#x1f1f4;',
		'COM' => '&#x1f1f0;&#x1f1f2;',
		'CPV' => '&#x1f1e8;&#x1f1fb;',
		'CRC' => '&#x1f1e8;&#x1f1f7;',
		'CRO' => '&#x1f1ed;&#x1f1f7;',
		'CUB' => '&#x1f1e8;&#x1f1fa;',
		'CYP' => '&#x1f1e8;&#x1f1fe;',
		'CZE' => '&#x1f1e8;&#x1f1ff;',
		'DEN' => '&#x1f1e9;&#x1f1f0;',
		'DJI' => '&#x1f1e9;&#x1f1ef;',
		'DMA' => '&#x1f1e9;&#x1f1f2;',
		'DOM' => '&#x1f1e9;&#x1f1f4;',
		'ECU' => '&#x1f1ea;&#x1f1e8;',
		'EGY' => '&#x1f1ea;&#x1f1ec;',
		'ERI' => '&#x1f1ea;&#x1f1f7;',
		'ESA' => '&#x1f1f8;&#x1f1fb;',
		'ESP' => '&#x1f1ea;&#x1f1f8;',
		'EST' => '&#x1f1ea;&#x1f1ea;',
		'ETH' => '&#x1f1ea;&#x1f1f9;',
		'FIJ' => '&#x1f1eb;&#x1f1ef;',
		'FIN' => '&#x1f1eb;&#x1f1ee;',
		'FRA' => '&#x1f1eb;&#x1f1f7;',
		'FSM' => '&#x1f1eb;&#x1f1f2;',
		'GAB' => '&#x1f1ec;&#x1f1e6;',
		'GAM' => '&#x1f1ec;&#x1f1f2;',
		'GBR' => '&#x1f1ec;&#x1f1e7;',
		'GBS' => '&#x1f1ec;&#x1f1fc;',
		'GEO' => '&#x1f1ec;&#x1f1ea;',
		'GEQ' => '&#x1f1ec;&#x1f1f6;',
		'GER' => '&#x1f1e9;&#x1f1ea;',
		'GHA' => '&#x1f1ec;&#x1f1ed;',
		'GRE' => '&#x1f1ec;&#x1f1f7;',
		'GRN' => '&#x1f1ec;&#x1f1e9;',
		'GUA' => '&#x1f1ec;&#x1f1f9;',
		'GUI' => '&#x1f1ec;&#x1f1f3;',
		'GUM' => '&#x1f1ec;&#x1f1fa;',
		'GUY' => '&#x1f1ec;&#x1f1fe;',
		'HAI' => '&#x1f1ed;&#x1f1f9;',
		'HKG' => '&#x1f1ed;&#x1f1f0;',
		'HON' => '&#x1f1ed;&#x1f1f3;',
		'HUN' => '&#x1f1ed;&#x1f1fa;',
		'INA' => '&#x1f1ee;&#x1f1e9;',
		'IND' => '&#x1f1ee;&#x1f1f3;',
		'IRI' => '&#x1f1ee;&#x1f1f7;',
		'IRL' => '&#x1f1ee;&#x1f1ea;',
		'IRQ' => '&#x1f1ee;&#x1f1f6;',
		'ISL' => '&#x1f1ee;&#x1f1f8;',
		'ISR' => '&#x1f1ee;&#x1f1f1;',
		'ISV' => '',
		'ITA' => '&#x1f1ee;&#x1f1f9;',
		'IVB' => '',
		'JAM' => '&#x1f1ef;&#x1f1f2;',
		'JOR' => '&#x1f1ef;&#x1f1f4;',
		'JPN' => '&#x1f1ef;&#x1f1f5;',
		'KAZ' => '&#x1f1f0;&#x1f1ff;',
		'KEN' => '&#x1f1f0;&#x1f1ea;',
		'KGZ' => '&#x1f1f0;&#x1f1ec;',
		'KIR' => '&#x1f1f0;&#x1f1ee;',
		'KOR' => '&#x1f1f0;&#x1f1f7;',
		'KOS' => '&#x1f1fd;&#x1f1f0;',
		'KSA' => '&#x1f1f8;&#x1f1e6;',
		'KUW' => '&#x1f1f0;&#x1f1fc;',
		'LAO' => '&#x1f1f1;&#x1f1e6;',
		'LAT' => '&#x1f1f1;&#x1f1fb;',
		'LBA' => '&#x1f1f1;&#x1f1fe;',
		'LBN' => '&#x1f1f1;&#x1f1e7;',
		'LBR' => '&#x1f1f1;&#x1f1f7;',
		'LCA' => '&#x1f1f1;&#x1f1e8;',
		'LES' => '&#x1f1f1;&#x1f1f8;',
		'LIE' => '&#x1f1f1;&#x1f1ee;',
		'LTU' => '&#x1f1f1;&#x1f1f9;',
		'LUX' => '&#x1f1f1;&#x1f1fa;',
		'MAD' => '&#x1f1f2;&#x1f1ec;',
		'MAR' => '&#x1f1f2;&#x1f1e6;',
		'MAS' => '&#x1f1f2;&#x1f1fe;',
		'MAW' => '&#x1f1f2;&#x1f1fc;',
		'MDA' => '&#x1f1f2;&#x1f1e9;',
		'MDV' => '&#x1f1f2;&#x1f1fb;',
		'MEX' => '&#x1f1f2;&#x1f1fd;',
		'MGL' => '&#x1f1f2;&#x1f1f3;',
		'MHL' => '&#x1f1f2;&#x1f1ed;',
		'MKD' => '&#x1f1f2;&#x1f1f0;',
		'MLI' => '&#x1f1f2;&#x1f1f1;',
		'MLT' => '&#x1f1f2;&#x1f1f9;',
		'MNE' => '&#x1f1f2;&#x1f1ea;',
		'MON' => '&#x1f1f2;&#x1f1e8;',
		'MOZ' => '&#x1f1f2;&#x1f1ff;',
		'MRI' => '&#x1f1f2;&#x1f1fa;',
		'MTN' => '&#x1f1f2;&#x1f1f7;',
		'MYA' => '&#x1f1f2;&#x1f1f2;',
		'NAM' => '&#x1f1f3;&#x1f1e6;',
		'NCA' => '&#x1f1f3;&#x1f1ee;',
		'NED' => '&#x1f1f3;&#x1f1f1;',
		'NEP' => '&#x1f1f3;&#x1f1f5;',
		'NGR' => '&#x1f1f3;&#x1f1ec;',
		'NIG' => '&#x1f1f3;&#x1f1ea;',
		'NOR' => '&#x1f1f3;&#x1f1f4;',
		'NRU' => '&#x1f1f3;&#x1f1f7;',
		'NZL' => '&#x1f1f3;&#x1f1ff;',
		'OMA' => '&#x1f1f4;&#x1f1f2;',
		'PAK' => '&#x1f1f5;&#x1f1f0;',
		'PAN' => '&#x1f1f5;&#x1f1e6;',
		'PAR' => '&#x1f1f5;&#x1f1fe;',
		'PER' => '&#x1f1f5;&#x1f1ea;',
		'PHI' => '&#x1f1f5;&#x1f1ed;',
		'PLE' => '&#x1f1f5;&#x1f1f8;',
		'PLW' => '&#x1f1f5;&#x1f1fc;',
		'PNG' => '&#x1f1f5;&#x1f1ec;',
		'POL' => '&#x1f1f5;&#x1f1f1;',
		'POR' => '&#x1f1f5;&#x1f1f9;',
		'PRK' => '&#x1f1f0;&#x1f1f5;',
		'PUR' => '&#x1f1f5;&#x1f1f7;',
		'QAT' => '&#x1f1f6;&#x1f1e6;',
		'ROU' => '&#x1f1f7;&#x1f1f4;',
		'RSA' => '&#x1f1ff;&#x1f1e6;',
		'RUS' => '&#x1f1f7;&#x1f1fa;',
		'RWA' => '&#x1f1f7;&#x1f1fc;',
		'SAM' => '&#x1f1fc;&#x1f1f8;',
		'SEN' => '&#x1f1f8;&#x1f1f3;',
		'SEY' => '&#x1f1f8;&#x1f1e8;',
		'SGP' => '&#x1f1f8;&#x1f1ec;',
		'SKN' => '&#x1f1f0;&#x1f1f3;',
		'SLE' => '&#x1f1f8;&#x1f1f1;',
		'SLO' => '&#x1f1f8;&#x1f1ee;',
		'SMR' => '&#x1f1f8;&#x1f1f2;',
		'SOL' => '&#x1f1f8;&#x1f1e7;',
		'SOM' => '&#x1f1f8;&#x1f1f4;',
		'SRB' => '&#x1f1f7;&#x1f1f8;',
		'SRI' => '&#x1f1f1;&#x1f1f0;',
		'SSD' => '&#x1f1f8;&#x1f1f8;',
		'STP' => '&#x1f1f8;&#x1f1f9;',
		'SUD' => '&#x1f1f8;&#x1f1e9;',
		'SUI' => '&#x1f1e8;&#x1f1ed;',
		'SUR' => '&#x1f1f8;&#x1f1f7;',
		'SVK' => '&#x1f1f8;&#x1f1f0;',
		'SWE' => '&#x1f1f8;&#x1f1ea;',
		'SWZ' => '&#x1f1f8;&#x1f1ff;',
		'SYR' => '&#x1f1f8;&#x1f1fe;',
		'TAN' => '&#x1f1f9;&#x1f1ff;',
		'TGA' => '&#x1f1f9;&#x1f1f4;',
		'THA' => '&#x1f1f9;&#x1f1ed;',
		'TJK' => '&#x1f1f9;&#x1f1ef;',
		'TKM' => '&#x1f1f9;&#x1f1f2;',
		'TLS' => '&#x1f1f9;&#x1f1f1;',
		'TOG' => '&#x1f1f9;&#x1f1ec;',
		'TPE' => '&#x1f1f9;&#x1f1fc;',
		'TTO' => '&#x1f1f9;&#x1f1f9;',
		'TUN' => '&#x1f1f9;&#x1f1f3;',
		'TUR' => '&#x1f1f9;&#x1f1f7;',
		'TUV' => '&#x1f1f9;&#x1f1fb;',
		'UAE' => '&#x1f1e6;&#x1f1ea;',
		'UGA' => '&#x1f1fa;&#x1f1ec;',
		'UKR' => '&#x1f1fa;&#x1f1e6;',
		'URU' => '&#x1f1fa;&#x1f1fe;',
		'USA' => '&#x1f1fa;&#x1f1f8;',
		'UZB' => '&#x1f1fa;&#x1f1ff;',
		'VAN' => '&#x1f1fb;&#x1f1fa;',
		'VEN' => '&#x1f1fb;&#x1f1ea;',
		'VIE' => '&#x1f1fb;&#x1f1f3;',
		'VIN' => '&#x1f1fb;&#x1f1e8;',
		'YEM' => '&#x1f1fe;&#x1f1ea;',
		'ZAM' => '&#x1f1ff;&#x1f1f2;',
		'ZIM' => '&#x1f1ff;&#x1f1fc;',
		'LIB' => '&#x1f1f1;&#x1f1e7;',
		'SIN' => '&#x1f1f8;&#x1f1ec;',
		'TRI' => '&#x1f1f9;&#x1f1f9;',
		'ROM' => '&#x1f1f7;&#x1f1f4;',
		'MAL' => '&#x1f1f2;&#x1f1fe;',
		'NMI' => '&#x1f1f2;&#x1f1f5;',
		'AHO' => '',
		'SCG' => '&#x1f1f7;&#x1f1f8;',
		'YUG' => '',
		'REU' => '&#x1f1eb;&#x1f1f7;',
		'CIS' => '&#x1f1f7;&#x1f1fa;',
		'' => '',

	],

	'iso2' => [

		'AFG' => 'AF',
		'ALB' => 'AL',
		'ALG' => 'DZ',
		'AND' => 'AD',
		'ANG' => 'AO',
		'ANT' => 'AG',
		'ARG' => 'AR',
		'ARM' => 'AM',
		'AUS' => 'AU',
		'AUT' => 'AT',
		'AZE' => 'AZ',
		'BAH' => 'BS',
		'BRN' => 'BH',
		'BAN' => 'BD',
		'BAR' => 'BB',
		'BLR' => 'BY',
		'BEL' => 'BE',
		'BIZ' => 'BZ',
		'BEN' => 'BJ',
		'BHU' => 'BT',
		'BOL' => 'BO',
		'BIH' => 'BA',
		'BOT' => 'BW',
		'BRA' => 'BR',
		'BRU' => 'BN',
		'BUL' => 'BG',
		'BUR' => 'BF',
		'BDI' => 'BI',
		'CAM' => 'KH',
		'CMR' => 'CM',
		'CAN' => 'CA',
		'CPV' => 'CV',
		'CAF' => 'CF',
		'CHA' => 'TD',
		'CHI' => 'CL',
		'CHN' => 'CN',
		'COL' => 'CO',
		'COM' => 'KM',
		'CGO' => 'CG',
		'COD' => 'CD',
		'COK' => 'CK',
		'CRC' => 'CR',
		'CIV' => 'CI',
		'CRO' => 'HR',
		'CUB' => 'CU',
		'CYP' => 'CY',
		'CZE' => 'CZ',
		'DEN' => 'DK',
		'DJI' => 'DJ',
		'DMA' => 'DM',
		'DOM' => 'DO',
		'ECU' => 'EC',
		'EGY' => 'EG',
		'ESA' => 'SV',
		'GEQ' => 'GQ',
		'ERI' => 'ER',
		'EST' => 'EE',
		'ETH' => 'ET',
		'FIJ' => 'FJ',
		'FIN' => 'FI',
		'FRA' => 'FR',
		'GAB' => 'GA',
		'GAM' => 'GM',
		'GEO' => 'GE',
		'GER' => 'DE',
		'GHA' => 'GH',
		'GRE' => 'GR',
		'GRN' => 'GD',
		'GUA' => 'GT',
		'GUI' => 'GN',
		'GBS' => 'GW',
		'GUY' => 'GY',
		'HAI' => 'HT',
		'HON' => 'HN',
		'HUN' => 'HU',
		'ISL' => 'IS',
		'IND' => 'IN',
		'INA' => 'ID',
		'IRI' => 'IR',
		'IRQ' => 'IQ',
		'IRL' => 'IE',
		'ISR' => 'IL',
		'ITA' => 'IT',
		'JAM' => 'JM',
		'JPN' => 'JP',
		'JOR' => 'JO',
		'KAZ' => 'KZ',
		'KEN' => 'KE',
		'KIR' => 'KI',
		'KOS' => 'XK',
		'KUW' => 'KW',
		'KGZ' => 'KG',
		'LAO' => 'LA',
		'LAT' => 'LV',
		'LBN' => 'LB',
		'LES' => 'LS',
		'LBR' => 'LR',
		'LBA' => 'LY',
		'LIE' => 'LI',
		'LTU' => 'LT',
		'LUX' => 'LU',
		'MKD' => 'MK',
		'MAD' => 'MG',
		'MAW' => 'MW',
		'MAS' => 'MY',
		'MDV' => 'MV',
		'MLI' => 'ML',
		'MLT' => 'MT',
		'MHL' => 'MH',
		'MTN' => 'MR',
		'MRI' => 'MU',
		'MEX' => 'MX',
		'FSM' => 'FM',
		'MDA' => 'MD',
		'MON' => 'MC',
		'MGL' => 'MN',
		'MNE' => 'ME',
		'MAR' => 'MA',
		'MOZ' => 'MZ',
		'MYA' => 'MM',
		'NAM' => 'NA',
		'NRU' => 'NR',
		'NEP' => 'NP',
		'NED' => 'NL',
		'NZL' => 'NZ',
		'NCA' => 'NI',
		'NIG' => 'NE',
		'NGR' => 'NG',
		'PRK' => 'KP',
		'NOR' => 'NO',
		'OMA' => 'OM',
		'PAK' => 'PK',
		'PLW' => 'PW',
		'PLE' => 'PS',
		'PAN' => 'PA',
		'PNG' => 'PG',
		'PAR' => 'PY',
		'PER' => 'PE',
		'PHI' => 'PH',
		'POL' => 'PL',
		'POR' => 'PT',
		'QAT' => 'QA',
		'ROU' => 'RO',
		'RUS' => 'RU',
		'RWA' => 'RW',
		'SKN' => 'KN',
		'LCA' => 'LC',
		'VIN' => 'VC',
		'SAM' => 'WS',
		'SMR' => 'SM',
		'STP' => 'ST',
		'KSA' => 'SA',
		'SEN' => 'SN',
		'SRB' => 'RS',
		'SEY' => 'SC',
		'SLE' => 'SL',
		'SGP' => 'SG',
		'SVK' => 'SK',
		'SLO' => 'SI',
		'SOL' => 'SB',
		'SOM' => 'SO',
		'RSA' => 'ZA',
		'KOR' => 'KR',
		'SSD' => 'SS',
		'ESP' => 'ES',
		'SRI' => 'LK',
		'SUD' => 'SD',
		'SUR' => 'SR',
		'SWZ' => 'SZ',
		'SWE' => 'SE',
		'SUI' => 'CH',
		'SYR' => 'SY',
		'TPE' => 'TW',
		'TJK' => 'TJ',
		'TAN' => 'TZ',
		'THA' => 'TH',
		'TLS' => 'TL',
		'TOG' => 'TG',
		'TGA' => 'TO',
		'TTO' => 'TT',
		'TUN' => 'TN',
		'TUR' => 'TR',
		'TKM' => 'TM',
		'TUV' => 'TV',
		'UGA' => 'UG',
		'UKR' => 'UA',
		'UAE' => 'AE',
		'GBR' => 'GB',
		'USA' => 'US',
		'URU' => 'UY',
		'UZB' => 'UZ',
		'VAN' => 'VU',
		'VEN' => 'VE',
		'VIE' => 'VN',
		'YEM' => 'YE',
		'ZAM' => 'ZM',
		'ZIM' => 'ZW',
		'ASA' => 'AS',
		'ARU' => 'AW',
		'BER' => 'BM',
		'CAY' => 'KY',
		'GUM' => 'GU',
		'HKG' => 'HK',
		'PUR' => 'PR',
		'IVB' => 'VG',
		'ISV' => 'VI',
		'LIB' => 'LB',
		'SIN' => 'SG',
		'TRI' => 'TT',
		'ROM' => 'RO',
		'MAL' => 'MY',
		'NMI' => 'MP',
		'AHO' => 'AN',
		'NZL' => 'NC',
		'FRG' => 'DE',
	],

	'iso2ToSubContinent' => [
		'DZ' => '015',
		'EG' => '015',
		'EH' => '015',
		'LY' => '015',
		'MA' => '015',
		'SD' => '015',
		'SS' => '015',
		'TN' => '015',
		'BF' => '011',
		'BJ' => '011',
		'CI' => '011',
		'CV' => '011',
		'GH' => '011',
		'GM' => '011',
		'GN' => '011',
		'GW' => '011',
		'LR' => '011',
		'ML' => '011',
		'MR' => '011',
		'NE' => '011',
		'NG' => '011',
		'SH' => '011',
		'SL' => '011',
		'SN' => '011',
		'TG' => '011',
		'AO' => '017',
		'CD' => '017',
		'ZR' => '017',
		'CF' => '017',
		'CG' => '017',
		'CM' => '017',
		'GA' => '017',
		'GQ' => '017',
		'ST' => '017',
		'TD' => '017',
		'BI' => '014',
		'DJ' => '014',
		'ER' => '014',
		'ET' => '014',
		'KE' => '014',
		'KM' => '014',
		'MG' => '014',
		'MU' => '014',
		'MW' => '014',
		'MZ' => '014',
		'RE' => '014',
		'RW' => '014',
		'SC' => '014',
		'SO' => '014',
		'TZ' => '014',
		'UG' => '014',
		'YT' => '014',
		'ZM' => '014',
		'ZW' => '014',
		'BW' => '018',
		'LS' => '018',
		'NA' => '018',
		'SZ' => '018',
		'ZA' => '018',
		'GG' => '154',
		'JE' => '154',
		'AX' => '154',
		'DK' => '154',
		'EE' => '154',
		'FI' => '154',
		'FO' => '154',
		'GB' => '154',
		'IE' => '154',
		'IM' => '154',
		'IS' => '154',
		'LT' => '154',
		'LV' => '154',
		'NO' => '154',
		'SE' => '154',
		'SJ' => '154',
		'AT' => '155',
		'BE' => '155',
		'CH' => '155',
		'DE' => '155',
		'DD' => '155',
		'FR' => '155',
		'FX' => '155',
		'LI' => '155',
		'LU' => '155',
		'MC' => '155',
		'NL' => '155',
		'BG' => '151',
		'BY' => '151',
		'CZ' => '151',
		'HU' => '151',
		'MD' => '151',
		'PL' => '151',
		'RO' => '151',
		'RU' => '151',
		'SU' => '151',
		'SK' => '151',
		'UA' => '151',
		'AD' => '039',
		'AL' => '039',
		'BA' => '039',
		'ES' => '039',
		'GI' => '039',
		'GR' => '039',
		'HR' => '039',
		'IT' => '039',
		'ME' => '039',
		'MK' => '039',
		'MT' => '039',
		'CS' => '039',
		'RS' => '039',
		'PT' => '039',
		'SI' => '039',
		'SM' => '039',
		'VA' => '039',
		'YU' => '039',
		'BM' => '021',
		'CA' => '021',
		'GL' => '021',
		'PM' => '021',
		'US' => '021',
		'AG' => '029',
		'AI' => '029',
		'AN' => '029',
		'AW' => '029',
		'BB' => '029',
		'BL' => '029',
		'BS' => '029',
		'CU' => '029',
		'DM' => '029',
		'DO' => '029',
		'GD' => '029',
		'GP' => '029',
		'HT' => '029',
		'JM' => '029',
		'KN' => '029',
		'KY' => '029',
		'LC' => '029',
		'MF' => '029',
		'MQ' => '029',
		'MS' => '029',
		'PR' => '029',
		'TC' => '029',
		'TT' => '029',
		'VC' => '029',
		'VG' => '029',
		'VI' => '029',
		'BZ' => '013',
		'CR' => '013',
		'GT' => '013',
		'HN' => '013',
		'MX' => '013',
		'NI' => '013',
		'PA' => '013',
		'SV' => '013',
		'AR' => '005',
		'BO' => '005',
		'BR' => '005',
		'CL' => '005',
		'CO' => '005',
		'EC' => '005',
		'FK' => '005',
		'GF' => '005',
		'GY' => '005',
		'PE' => '005',
		'PY' => '005',
		'SR' => '005',
		'UY' => '005',
		'VE' => '005',
		'TM' => '143',
		'TJ' => '143',
		'KG' => '143',
		'KZ' => '143',
		'UZ' => '143',
		'CN' => '030',
		'HK' => '030',
		'JP' => '030',
		'KP' => '030',
		'KR' => '030',
		'MN' => '030',
		'MO' => '030',
		'TW' => '030',
		'AF' => '034',
		'BD' => '034',
		'BT' => '034',
		'IN' => '034',
		'IR' => '034',
		'LK' => '034',
		'MV' => '034',
		'NP' => '034',
		'PK' => '034',
		'BN' => '035',
		'ID' => '035',
		'KH' => '035',
		'LA' => '035',
		'MM' => '035',
		'BU' => '035',
		'MY' => '035',
		'PH' => '035',
		'SG' => '035',
		'TH' => '035',
		'TL' => '035',
		'TP' => '035',
		'VN' => '035',
		'AE' => '145',
		'AM' => '145',
		'AZ' => '145',
		'BH' => '145',
		'CY' => '145',
		'GE' => '145',
		'IL' => '145',
		'IQ' => '145',
		'JO' => '145',
		'KW' => '145',
		'LB' => '145',
		'OM' => '145',
		'PS' => '145',
		'QA' => '145',
		'SA' => '145',
		'NT' => '145',
		'SY' => '145',
		'TR' => '145',
		'YE' => '145',
		'YD' => '145',
		'AU' => '053',
		'NF' => '053',
		'NZ' => '053',
		'FJ' => '054',
		'NC' => '054',
		'PG' => '054',
		'SB' => '054',
		'VU' => '054',
		'FM' => '057',
		'GU' => '057',
		'KI' => '057',
		'MH' => '057',
		'MP' => '057',
		'NR' => '057',
		'PW' => '057',
		'AS' => '061',
		'CK' => '061',
		'NU' => '061',
		'PF' => '061',
		'PN' => '061',
		'TK' => '061',
		'TO' => '061',
		'TV' => '061',
		'WF' => '061',
		'WS' => '061',
	],

	'pbp' => [

		'lines' => [
			'serveFlag' => 'S',
		],

		'color' => [
			'line' => '#d8d8d8',
			'lineDot' => '#f00',
			'lineDotLabel' => '#fff',
			'lineBSMLabel' => '#fff',
			'gameLabel' => '#000',
			'gameLabelBg' => '#ccc',
			'gameLine' => '#fff',
			'serveDotBorder' => '#d8d8d8',
			'serveDotLabel' => '#fff',
			'liveGame' => '#a8a8a8',
		],

	],

	'schema_completed' => ['sexid', 'isq', 'year', 'totalprize', 'tour', 'city', 'surface', 'level', 'matchid', 'courtseq', 'courtname', 'matchseq', 'round', 'schedule', 'p1eng', 'p2eng', 'ioc1', 'ioc2', 'p1chn', 'p2chn', 'dura', 'eid', 'p1id', 'p2id', 'p1rank', 'p2rank', 'score', 'h2h', 'updatetime', 'mstatus', 'p1seed', 'p2seed', 'fsid', 'bestof', 'p1first', 'p1last', 'p2first', 'p2last', 'p1ioc', 'p2ioc', 'p1id_bets', 'p2id_bets', 'matchid_bets', 'odd1_bets', 'odd2_bets', 'umpireid', 'umpirefirst', 'umpirelast', 'umpireioc', 'mStatus', 'jointEid'],

	'schema_drawsheet' => ['sextip', 'id', 'round', 'mStatus', 'score1', 'score2', 'P1A', 'P1B', 'P2A', 'P2B', 'Seed1', 'Seed2', 'P1ANation', 'P1BNation', 'P2ANation', 'P2BNation', 'P1AFirst', 'P1BFirst', 'P2AFirst', 'P2BFirst', 'P1ALast', 'P1BLast', 'P2ALast', 'P2BLast'],

	'schema_activity_match' => ['id', 'player', 'ioc', 'time', 'year', 'tourid', 'tourname', 'level', 'loc', 'ground', 'sd', 'totalprize', 'rank', 'seed', 'entry', 'partnerid', 'partnername', 'partnerioc', 'seq', 'round', 'winorlose', 'oppoid', 'opponame', 'opponation', 'opporank', 'opposeed', 'oppoentry', 'games'],

	'schema_activity_summary' => ['id', 'player', 'ioc', 'time', 'year', 'tourid', 'tourname', 'level', 'loc', 'ground', 'sd', 'totalprize', 'rank', 'seed', 'entry', 'partnerid', 'partnername', 'partnerioc', 'seq', 'finalround', 'point', 'prize', 'win', 'lose'],

	'schema_calendar' => ['level', 'eid', 'pageid', 'gender', 'year', 'date', 'unixtime', 'title', 'surface', 'city', 'loc', 'totalprize', 'draw_ms', 'draw_md', 'draw_qs', 'draw_qd', 'draw_ws', 'draw_wd', 'draw_ps', 'draw_pd', 'prize', 'weeks'],

	'schema_ranking_sheet' => ['id', 'official_rank', 'live_rank', 'move', 'score', 'player_name', 'name', 'age', 'count', 'qz0', 'streak', 'prize', 'win', 'loss', 'winrate', 'qijifen_eng', 'qijifen', 'week_tour', 'week_point', 'week_round', 'week_in', 'ioc', 'ioc2', 'birthday', 'oppo_full_name', 'h2h', 'partner_full_name', 'highest_rank', 'drop', 'nothing', 'alt', 'predict', 'full_name', 'point1', 'point2', 'point3', 'second_point', 'third_point', 'priority'],

	'schema_points' => ['id', 'name', 'date', 'year', 'eid', 'level', 'loc', 'point', 'win', 'loss', 'final_round', 'city', 'prize', 'surface', 'total_prize', 'in', 'next_oppo', 'streak', 'partnerid', 'prediction'],

	'schema_activity' => ['pid', 'ioc', 'eid', 'joineid', 'year', 'start_date', 'weeks', 'record_date', 'city', 'loc', 'level', 'sfc', 'currency', 'total_prize', 'sd', 'rank', 'seed', 'entry', 'partner_id', 'partner_ioc', 'prize', 'point', 'award_point', 'final_round', 'win', 'loss', 'streak', 'matches'],

	'schema_activity_matches' => ['seq', 'round', 'wl', 'games', 'orank', 'oseed', 'oentry', 'oid', 'oioc', 'opartner_id', 'opartner_ioc', 'partner_id', 'partner_ioc'],

	'schema_calendar' => ['level', 'eid', 'reserved', 'gender', 'year', 'monday', 'mondayUnix', 'title', 'surface', 'city', 'loc', 'prize', 'drawSizeMS', 'drawSizeMD', 'drawSizeQS', 'drawSizeQD', 'drawSizeWS', 'drawSizeWD', 'drawSizePS', 'drawSizePD', 'prizeNum', 'weeks'],

];
