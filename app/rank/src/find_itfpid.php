<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

require_once(APP . '/conf/wt_bio.php');

$redis = new redis_cli('127.0.0.1', 6379);

$e = new Bio();

$fp = fopen('all_atp_pid', 'r');

while ($line = trim(fgets($fp))) {
	$e->query_itfpid('atp', $line, $redis);
}

fclose($fp);
