<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

require_once(APP . '/conf/wt_bio.php');

// 导出中国球员的英文简称

$redis = new redis_cli($db_conf['redis']['host'], $db_conf['redis']['port']);
$bio = new Bio();

foreach (['atp', 'wta', 'itf'] as $gender) {
	$all_keys = $redis->cmd('KEYS', join("_", [$gender, 'profile', '*']))->get();

	foreach ($all_keys as $key) {
		$arr = $redis->cmd('HMGET', $key, 'first', 'last', 'ioc', 's_en', 'l_en', 's_zh', 'l_zh')->get();
		$first = $arr[0];
		$last = $arr[1];
		$ioc = $arr[2];
		if (in_array($ioc, ['CHN'])) {
			print_line($key, preg_replace('/^.*_/', '', $key), $first, $last, $ioc, $arr[3], $arr[4], $arr[5], $arr[6]);
		}
	}
}
