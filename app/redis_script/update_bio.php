<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

require_once(APP . '/conf/wt_bio.php');

$bio = new Bio();

$pid = get_param($argv, 1, null);

if (!$pid) exit;

if (preg_match('/^[A-Z0-9]{4}$/', $pid)) {
	$gender = "atp";
} else if (preg_match('/^[0-9]{5,6}$/', $pid)) {
	$gender = "wta";
} else {
	exit;
}

$redis = new redis_cli($db_conf['redis']['host'], $db_conf['redis']['port']);

$bio->down_bio($pid, $gender, $redis);

$key = join("_", [$gender, 'profile', $pid]);

$arr = $redis->cmd('HGETALL', $key)->get();
for ($i = 0; $i < count($arr); $i += 2) {
	echo join(" ", [$arr[$i], "=>", $arr[$i + 1]]) . "\n";
}
