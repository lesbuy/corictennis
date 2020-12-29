<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$eid = get_param($argv, 1, 'W-ITF-MEX-31A');
$year = intval(get_param($argv, 2, 2019));
$sex = get_param($argv, 3, 'W');
$tourid = intval(get_param($argv, 4, 1100180603));

$cookie = file_get_contents(ROOT . '/cookie/itf_curl_cookie');

function into_type($v1, $atype, &$types, $count = 0) {
	if (!isset($v1[0])) {
		$v1 = [$v1];
	}
	foreach ($v1 as $v2) {
		$K1 = $v2['dataName'];
		$V1 = $v2['valueCode'];
		$atype[$K1] = $V1;
		if (!$v2['subFilter']) {
			$types[] = $atype;
		} else {
			into_type($v2['subFilter'], $atype, $types, ++$count);
		}
	}
}

$draws = [];
$filter_url = "https://www.itftennis.com/Umbraco/Api/TournamentApi/GetEventFilters?tournamentKey=" . strtolower($eid) . "-" . $year;
$html = http($filter_url, NULL, $cookie);
if ($html) {
	$json = json_decode($html, true);
	if ($json) {
		print_r($json);
		$types = [];
		$atype = [];
		foreach ($json['filters'] as $v1) {
			$atype = [];
			into_type($v1, $atype, $types);
		}

		foreach ($types as $t1) {
			$total_size = 0;

			$t1['tournamentId'] = $tourid;
			$t1['tourType'] = 'N';
			$t1['weekNumber'] = 0;
			$post_data = json_encode($t1);

			$type_mix = $sex . @$t1['playerTypeCode'] . $t1['eventClassificationCode'] . $t1['matchTypeCode'];
			if ($type_mix == 'MMS') $sextip = 'MS';
			else if ($type_mix == 'MQS') $sextip = 'QS';
			else if ($type_mix == 'MMD') $sextip = 'MD';
			else if ($type_mix == 'MQD') $sextip = 'QD';
			else if ($type_mix == 'WMS') $sextip = 'WS';
			else if ($type_mix == 'WQS') $sextip = 'PS';
			else if ($type_mix == 'WMD') $sextip = 'WD';
			else if ($type_mix == 'WQD') $sextip = 'PD';
			else if ($type_mix == 'JBMS') $sextip = 'BS';
			else if ($type_mix == 'JBMD') $sextip = 'BD';
			else if ($type_mix == 'JBQS') $sextip = 'BQ';
			else if ($type_mix == 'JGMS') $sextip = 'GS';
			else if ($type_mix == 'JGMD') $sextip = 'GD';
			else if ($type_mix == 'JGQS') $sextip = 'GQ';
			else $sextip = "";

			$draw_url = "https://www.itftennis.com/Umbraco/Api/TournamentApi/GetDrawsheet";
			echo $draw_url . "\n";
			print_r($t1);
			$content_length = strlen(json_encode($t1));
			$html1 = http($draw_url, json_encode($t1), $cookie, ["Content-length: " . $content_length]);
			echo $html1 . "\n";
			if ($html1) {
				$draw_j = json_decode($html1, true);
				$draws[$sextip] = $draw_j;
			}
			sleep(2);
		}
	}
}
echo json_encode($draws);
