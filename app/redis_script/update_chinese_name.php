<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

require_once(APP . '/conf/wt_bio.php');

$bio = new Bio();

$redis = new redis_cli($db_conf['redis']['host'], $db_conf['redis']['port']);

$fp = fopen("chinese_name", "r");
while ($line = trim(fgets($fp))) {
	$arr = explode("\t", $line);
	$pid = $arr[0];
	$s_zh = $arr[2];
	$l_zh = $arr[3];
	$s_ja = @$arr[4];
	$l_ja = @$arr[5];

	if (preg_match('/^[A-Z0-9]{4}$/', $pid)) {
		$gender = "atp";
	} else if (preg_match('/^[0-9]{5,6}$/', $pid)) {
		$gender = "wta";
	} else {
		$gender = "itf";
	}

	$key = join("_", [$gender, 'profile', $pid]);
	$redis->cmd('HMSET', $key, 's_zh', $s_zh, 'l_zh', $l_zh)->set();

	if ($s_ja) {
		$redis->cmd('HMSET', $key, 's_ja', $s_ja, 'l_ja', $l_ja)->set();
	}
}

