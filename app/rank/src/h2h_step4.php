<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$gender = get_param($argv, 1, 'all');

$redis = new redis_cli('127.0.0.1', 6379);

if ($gender == 'atp' || $gender == 'all') {
	$fp = fopen(DATA . '/h2h/atp_summary', 'r');
	while ($line = trim(fgets($fp))) {
		$arr = explode("\t", $line) ;
		$field = join("\t", [$arr[0], $arr[1]]);
		$h2h = join(":", [$arr[2], $arr[3]]);
		$redis->cmd('HSET', 'h2h', $field, $h2h)->set();
	}
	fclose($fp);
}

if ($gender == 'wta' || $gender == 'all') {
	$fp = fopen(DATA . '/h2h/wta_summary', 'r');
	while ($line = trim(fgets($fp))) {
		$arr = explode("\t", $line) ;
		$field = join("\t", [$arr[0], $arr[1]]);
		$h2h = join(":", [$arr[2], $arr[3]]);
		$redis->cmd('HSET', 'h2h', $field, $h2h)->set();
	}
	fclose($fp);
}
