<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

require_once(APP . '/conf/wt_bio.php');

$bio = new Bio();

$redis = new redis_cli($db_conf['redis']['host'], $db_conf['redis']['port']);

$fp = fopen("headshot", "r");
while ($line = trim(fgets($fp))) {
	$arr = explode("\t", $line);
	$gender = $arr[0];
	$pid = $arr[1];
	$img = $arr[3];

	$key = join("_", [$gender, 'profile', $pid]);
	$redis->cmd('HSET', $key, 'hs', $img)->set();
}

