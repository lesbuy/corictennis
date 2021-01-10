<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$redis = new redis_cli($db_conf['redis']['host'], $db_conf['redis']['port']);

$file = "name_list";
$fp = fopen($file, "r");
while ($pid = trim(fgets($fp))) {
	if (preg_match('/^[A-Z0-9]{4}$/', $pid)) {
		$gender = "atp";
	} else if (preg_match('/^[0-9]{5,6}$/', $pid)) {
		$gender = "wta";
	} else {
		$gender = "itf";
	}
	$arr = $redis->cmd('HMGET', $gender . '_profile_' . $pid, "first", "last", "ioc")->get();
	if (!$arr[0]) continue;
	echo join("\t", [
		$pid,
		$arr[0],
		$arr[1],
		$arr[2],
	]) . "\n";
}
fclose($fp);
