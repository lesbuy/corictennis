<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$redis = new redis_cli('127.0.0.1', 6379);

$fp = fopen(SHARE . '/nation_long2short', 'r');
while ($line = trim(fgets($fp))) {
	$arr = explode("\t", $line) ;
	$long = $arr[0];
	$short = $arr[1];
	$redis->cmd('HSET', 'nation_long2short', $long, $short)->set();
}
fclose($fp);
