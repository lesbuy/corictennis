<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$reg = get_param($argv, 1, null);
$reg = strtolower($reg);

$redis = new redis_cli($db_conf['redis']['host'], $db_conf['redis']['port']);

$keys = $redis->cmd('KEYS', '*_profile_*')->get();

foreach ($keys as $key) {
	$a = $redis->cmd('HMGET', $key, 'first', 'last', 'ioc')->get();
	if ($a[0] || $a[1]) {
		if (strpos(strtolower($a[0] . " " . $a[1]), $reg) === 0 || strpos(strtolower($a[1]), $reg) === 0) {
			echo join("\t", [$key, $a[0], $a[1], $a[2]]) . "\n";
		}
	}
}
