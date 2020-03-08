<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$redis = new redis_cli('127.0.0.1', 6379);

foreach ([-1, 0, 1] as $itvl) {

	$date = date('Y-m-d', strtotime($itvl . " days"));

	$fp = fopen(join("/", [SHARE, 'down_result', 'fs', 'etl', $date]), 'r');
	while ($line = trim(fgets($fp))) {
		$arr = explode("\t", $line);
		$fsid = $arr[3];
		$p1 = $arr[5];
		$p2 = $arr[6];
		$unix = $arr[7];
		$sd = $arr[2];
		if ($sd < 2) $sd = 's'; else $sd = 'd';

		foreach ([-1, 0] as $itvl1) {
			$date1 = date('Y-m-d', strtotime($date . ' ' . $itvl1 . " days"));
			$redis->cmd('HMSET', join('_', ['fs', $p1, $p2, $sd, $date1]), 'fsid', $fsid, 'unix', $unix)->set();
			$redis->cmd('EXPIRE', join('_', ['fs', $p1, $p2, $sd, $date1]), 86400 * 22)->set();
		}
	}
	fclose($fp);
}
