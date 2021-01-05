<?php

if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$gender = get_param($argv, 1, "atp", " atp wta ");

$redis = new_redis();

$store = [];

while ($line = trim(fgets(STDIN))) {

	$arr = explode("\t", $line);
	$winner1ID = $arr[1];
	$winner2ID = $arr[2];
	$loser1ID = $arr[3];
	$loser2ID = $arr[4];
 
	$winner1first = $winner1last = $winner1ioc = "";
	$winner2first = $winner2last = $winner2ioc = "";
	$loser1first = $loser1last = $loser1ioc = "";
	$loser2first = $loser2last = $loser2ioc = "";

	if ($winner1ID != "") {
		if (!isset($store[$winner1ID])) {
			$res = $redis->cmd('HMGET', join("_", [$gender, 'profile', $winner1ID]), 'first', 'last', 'ioc')->get();
			$store[$winner1ID] = $res;
		} else {
			$res = $store[$winner1ID];
		}
		$winner1first = $res[0]; $winner1last = $res[1]; $winner1ioc = $res[2];
	}
	if ($winner2ID != "") {
		if (!isset($store[$winner2ID])) {
			$res = $redis->cmd('HMGET', join("_", [$gender, 'profile', $winner2ID]), 'first', 'last', 'ioc')->get();
			$store[$winner2ID] = $res;
		} else {
			$res = $store[$winner2ID];
		}
		$winner2first = $res[0]; $winner2last = $res[1]; $winner2ioc = $res[2];
	}
	if ($loser1ID != "") {
		if (!isset($store[$loser1ID])) {
			$res = $redis->cmd('HMGET', join("_", [$gender, 'profile', $loser1ID]), 'first', 'last', 'ioc')->get();
			$store[$loser1ID] = $res;
		} else {
			$res = $store[$loser1ID];
		}
		$loser1first = $res[0]; $loser1last = $res[1]; $loser1ioc = $res[2];
	}
	if ($loser2ID != "") {
		if (!isset($store[$loser2ID])) {
			$res = $redis->cmd('HMGET', join("_", [$gender, 'profile', $loser2ID]), 'first', 'last', 'ioc')->get();
			$store[$loser2ID] = $res;
		} else {
			$res = $store[$loser2ID];
		}
		$loser2first = $res[0]; $loser2last = $res[1]; $loser2ioc = $res[2];
	}

	$arr[5] = $winner1first;
	$arr[6] = $winner2first;
	$arr[7] = $loser1first;
	$arr[8] = $loser2first;
	$arr[9] = $winner1last;
	$arr[10] = $winner2last;
	$arr[11] = $loser1last;
	$arr[12] = $loser2last;
	$arr[13] = $winner1ioc;
	$arr[14] = $winner2ioc;
	$arr[15] = $loser1ioc;
	$arr[16] = $loser2ioc;

	echo join("\t", $arr) . "\n";
}
