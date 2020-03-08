<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$redis = new redis_cli('127.0.0.1', 6379);

foreach ([-1, 0, 1, 2] as $itvl) {

	$date = date('Y-m-d', strtotime($itvl . " days"));

	$fp = fopen(join("/", [SHARE, 'down_result', 'odds', $date]), 'r');
	while ($line = trim(fgets($fp))) {
		$arr = explode("\t", $line);
		$betsid = $arr[1];
		$p1 = $arr[2];
		$p2 = $arr[3];
		$time = $arr[4];

		$redis->cmd('HMSET', join('_', ['odd', $betsid]), 'odd1', $p1, 'odd2', $p2, 'time', $time)->set();
		$redis->cmd('EXPIRE', join('_', ['odd', $betsid]), 86400 * 22)->set();
	}
	fclose($fp);
}
