<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

require_once('wt_bio.new.php');

$redis = new redis_cli('127.0.0.1', 6379);

$bio = new Bio();
echo $bio->query_wtpid('wta', 'Na', 'Li', $redis) . "\n";
