<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$redis = new redis_cli('127.0.0.1', 6379);

$fp = fopen('wta_p5_count', 'r');
while ($line = trim(fgets($fp))) {
	$arr = explode("\t", $line);
	$pid = $arr[0];
	$year_count = $arr[3];
	$race_count = $arr[4];

	$redis->cmd('HMSET', 'wta_p5_count_' . $pid, 
		'year', $year_count,
		'race', $race_count
	)->set();
}
fclose($fp);
