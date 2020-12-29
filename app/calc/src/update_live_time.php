<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

date_default_timezone_set('PRC'); 

$objDb = new_db("test");

$gender = get_param($argv, 1, "atp");

if (!isset($argv[2]) || !$argv[2]) {
	$official = date('Y-m-d');
	$live = date('Y-m-d', strtotime("+7 days"));
} else {
	$official = $argv[2];
	if (!isset($argv[3]) || !$argv[3]) {
		$live = date('Y-m-d', strtotime("$official +7 days"));
	} else {
		$live = $argv[3];
	}
}

$tbname = "info";
$cols = ['value_time' => $official];
$conds = [ 'key' => ["calc_${gender}_s_year_official_time", "calc_${gender}_d_year_official_time", "calc_${gender}_s_race_official_time", "calc_${gender}_s_nextgen_official_time"]];
if ($objDb->update($tbname, $cols, $conds) === false){
	echo "===========================上行更新时间 ERROR==============================\n";
}

$cols = ['value_time' => $live];
$conds = [ 'key' => ["calc_${gender}_s_year_live_time", "calc_${gender}_d_year_live_time", "calc_${gender}_s_race_live_time", "calc_${gender}_s_nextgen_live_time"]];
if ($objDb->update($tbname, $cols, $conds) === false){
	echo "===========================上行更新时间 ERROR==============================\n";
}

unset($objDb);
