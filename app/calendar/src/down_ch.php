<?php
//error_reporting(0);
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php');

$cur_year = !isset($argv[1]) ? 2020 : $argv[1];

$fp = fopen(SHARE. "/nation_long2short", "r");
while ($line = trim(fgets($fp))) {
	$arr = explode("\t", $line);
	$nat[$arr[0]] = $arr[1];
}
fclose($fp);

$info = [];
$atpid_map = [];
$wtaid_map = [];

$line = file_get_contents(TEMP . "/calendar_decrypt_m");
$XML = simplexml_load_string($line);

foreach ($XML->Tournament as $event){
	$tour = $event->attributes()->type;
	if ($tour != "CH" && $tour != "TOUR") continue;
	if ($event->attributes()->tA == "" && $event->attributes()->tW != "") {
		$sex = "W";
	} else if ($event->attributes()->tA != "" && $event->attributes()->tW == "") {
		$sex = "M";
	} else {
		$sex = "MW";
	}

	$year = $event->attributes()->year;
	if ($year != $cur_year) continue;

	$type_atp = $event->attributes()->tA;
	$type_wta = $event->attributes()->tW;
	switch ($type_atp) {
		case 20: $type_atp = "GS"; break;
		case 6: $type_atp = "1000"; break;
		case 5: $type_atp = "500"; break;
		case 4: $type_atp = "250"; break;
		case 26: $type_atp = "OL"; break;
		case 24: $type_atp = "AC"; break;
		case 13: $type_atp = "LC"; break;
		case 10: $type_atp = "XXI"; break;
		case 12: $type_atp = "WC"; break;
		case 3: $type_atp = "CH"; break;
	}
	switch ($type_wta) {
		case 20: $type_wta = "GS"; break;
		case 9: $type_wta = "Int"; break;
		case 7: $type_wta = ""; break;
		case 22: $type_wta = "125K"; break;
	}
	if ($type_wta != "125K" && $type_atp != "CH") continue;

	$name = $event->attributes()->name . "";
	$sDate = $event->attributes()->sDate;
	$tDate = date('Y-m-d', strtotime($sDate . " 4 days ago"));
	$sDate = date('Y-m-d', strtotime($tDate . " next Monday"));
	$s = strtotime($sDate);

	$eid = $event->attributes()->idL . "";
	$atpid = $event->attributes()->idA . "";
	$wtaid = $event->attributes()->idW . "";
	if ($eid == "") continue;

	if ($atpid != "") $atpid_map[$atpid] = $eid;
	if ($wtaid != "") $wtaid_map[$wtaid] = $eid;

	$info[$eid] = [
		'sex' => $sex,
		'eid' => $eid,
		'atpid' => $atpid,
		'wtaid' => $wtaid,
		'atptype' => $type_atp,
		'wtatype' => $type_wta,
		'year' => $year,
		'start' => $sDate,
		'unix' => $s,
		'title' => '',
		'sfc' => '',
		'name' => $name,
		'loc' => '',
		'prize' => 0,
		'atpdraw' => [0, 0, 0, 0],
		'wtadraw' => [0, 0, 0, 0],
		'cur' => '$',
		'weeks' => ($eid == "M006" || $eid == "M007" ? 2 : 1)
	];
}

$line = file_get_contents(TEMP . "/calendar_decrypt");
$XML = simplexml_load_string($line);

foreach ($XML->Tournament as $event){
	$tour = $event->attributes()->tour . "";
	$type = $event->attributes()->type . "";
	if ($type != "CH" && $type != "C") continue;

	$id = $event->attributes()->id . "";
	if ($tour == "ch" && isset($atpid_map[$id])) {
		$eid = $atpid_map[$id];
	} else if ($tour == "wta" && isset($wtaid_map[$id])) {
		$eid = $wtaid_map[$id];
	} else {
		continue;
	}

	$_info = &$info[$eid];

	$_info['title'] = $event->attributes()->title . "";
	if ($tour == "wta") $_info['title'] = preg_replace('/ - .*$/', "", $_info['title']);

    $loc = $event->attributes()->loc . ""; 
    if (strpos($loc, "U.S.A") !== false || strpos($loc, "USA") !== false || strpos($loc, "UNITED STATES") !== false) {
        $loc = "U.S.A.";
    } else if (strpos($loc, "U.A.E") !== false) {
        $loc = "U.A.E.";
    } else {
        $loc = ucwords(mb_strtolower($loc));
    }   
    $loc = str_replace(" & ", " and ", $loc);
    $loc = str_replace("St. ", "Saint ", $loc);
    $loc = $nat[$loc];
	$_info['loc'] = $loc;

	$_info['sfc'] = $event->attributes()->sfc . "";
	if ($event->attributes()->indoor . "" == "1") {
		$_info['sfc'] .= "(I)";
	}
	$_info['cur'] = $event->attributes()->cur . "";
	$_info['prize'] += $event->attributes()->prize . "";

	if ($tour == "atp") {
		if ($_info['atptype'] == "") $_info['atptype'] = $event->attributes()->type . "";
		$_info['atpdraw'][0] += $event->attributes()->sDraw . "";
		$_info['atpdraw'][1] += $event->attributes()->dDraw . "";
	} else {
		if ($_info['wtatype'] == "") $_info['wtatype'] = $event->attributes()->type . "";
		$_info['wtadraw'][0] += $event->attributes()->sDraw . "";
		$_info['wtadraw'][1] += $event->attributes()->dDraw . "";
	}
}

foreach ($info as $k => $v) {

    if ($v['sex'] == "W") {                      
        $v['prize'] = 125000;                   
    } else {                                   
        if ($v['prize'] <= 40000) $v['prize'] = 50000;
        else if ($v['prize'] <= 60000) $v['prize'] = 80000;
        else if ($v['prize'] <= 87000) $v['prize'] = 90000;
        else if ($v['prize'] <= 110000) $v['prize'] = 100000;
        else if ($v['prize'] <= 131000) $v['prize'] = 110000;
        else $v['prize'] = 125000;              
    }

	echo join("\t", [
		$v['sex'] == 'M' ? $v['atptype'] : ($v['sex'] == 'W' ? $v['wtatype'] : $v['atptype'] . '/' . $v['wtatype']),
		$v['eid'],
		$v['eid'],
		$v['sex'],
		$v['year'],
		$v['start'],
		$v['unix'],
		$v['title'],
		$v['sfc'],
		$v['name'],
		$v['loc'],
		$v['cur'] . $v['prize'],
		$v['atpdraw'][0],
		$v['atpdraw'][1],
		$v['atpdraw'][2],
		$v['atpdraw'][3],
		$v['wtadraw'][0],
		$v['wtadraw'][1],
		$v['wtadraw'][2],
		$v['wtadraw'][3],
		$v['prize'],
		$v['weeks']
	]) . "\n";
}
