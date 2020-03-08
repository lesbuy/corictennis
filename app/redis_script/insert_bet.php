<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$redis = new redis_cli('127.0.0.1', 6379);

foreach ([-1, 0, 1, 2] as $itvl) {

	$date = date('Y-m-d', strtotime($itvl . " days"));

	$fp = fopen(join("/", [SHARE, 'down_result', 'bets', $date]), 'r');
	while ($line = trim(fgets($fp))) {
		$arr = explode("\t", $line);
		$sd = $arr[1];
		$p1 = $arr[2];
		$p2 = $arr[4];
		$betsid = $arr[8];
		$betsp1 = $arr[6];
		$betsp2 = $arr[7];
		if ($sd == 0) $sd = 's'; else $sd = 'd';
		$p1 = strtoupper(substr($p1, 0, 3));
		$p2 = strtoupper(substr($p2, 0, 3));

		foreach ([-1, 0, 1] as $itvl1) { // bets数据是按GMT归到天的，所以实际的天可能有3种情况
			$date1 = date('Y-m-d', strtotime($date . ' ' . $itvl1 . " days"));
			$redis->cmd('HMSET', join('_', ['fs', $p1, $p2, $sd, $date1]), 'betsid', $betsid, 'betsp1', $betsp1, 'betsp2', $betsp1)->set();
			$redis->cmd('EXPIRE', join('_', ['fs', $p1, $p2, $sd, $date1]), 86400 * 22)->set();
		}
	}
	fclose($fp);
}
