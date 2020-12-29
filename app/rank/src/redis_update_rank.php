<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$gender = get_param($argv, 1, 'atp');

$redis = new redis_cli('127.0.0.1', 6379);

foreach (["s", "d"] as $sd) {
	$redis->cmd('DEL', join("_", ["rank", $gender, $sd]))->set();
	$fp = fopen(DATA . "/rank/$gender/$sd/current", 'r');
	while ($line = trim(fgets($fp))) {
		$arr = explode("\t", $line) ;
		$id = $arr[0];
		$rank = $arr[2];
		$redis->cmd('HSET', join("_", ["rank", $gender, $sd]), $id, $rank)->set();
	}
	fclose($fp);
}
