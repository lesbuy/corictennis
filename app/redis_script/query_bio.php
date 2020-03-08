<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$pid = get_param($argv, 1, null);

$redis = new redis_cli($db_conf['redis']['host'], $db_conf['redis']['port']);

$keys = $redis->cmd('KEYS', '*_profile_' . $pid)->get();

foreach ($keys as $key) {
	$arr = $redis->cmd('HGETALL', $key)->get();
	for ($i = 0; $i < count($arr); $i += 2) {
		echo join(" ", [$arr[$i], "=>", $arr[$i + 1]]) . "\n";
	}
}
