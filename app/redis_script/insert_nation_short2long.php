<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$redis = new redis_cli('127.0.0.1', 6379);

$fp = fopen('nation_short2long', 'r');
while ($line = trim(fgets($fp))) {
	$arr = explode("\t", $line) ;
	$short = $arr[0];
	$long = $arr[1];
	$redis->cmd('HSET', 'nation_short2long', $short, $long)->set();
}
fclose($fp);
