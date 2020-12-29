<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

require_once(APP . '/conf/wt_bio.php');

// 更新一下profile库里所有人的shortname longname

$redis = new redis_cli($db_conf['redis']['host'], $db_conf['redis']['port']);
$bio = new Bio();

foreach (['atp', 'wta', 'itf'] as $gender) {
	$all_keys = $redis->cmd('KEYS', join("_", [$gender, 'profile', '*']))->get();

	foreach ($all_keys as $key) {
		$arr = $redis->cmd('HMGET', $key, 'first', 'last', 'ioc')->get();
		$first = $arr[0];
		$last = $arr[1];
		$ioc = $arr[2];
		if ($ioc == "CHN") {
			$long = $bio->rename2long($first, $last, $ioc);
			$short = $bio->rename2short($first, $last, $ioc);

			$redis->cmd('HMSET', $key, 'l_en', $long, 's_en', $short)->set();
		}
	}
}
