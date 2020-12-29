<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 
require_once(APP . '/conf/wt_bio.php');

$first = get_param($argv, 1, null);
$last = get_param($argv, 2, null);

$redis = new redis_cli($db_conf['redis']['host'], $db_conf['redis']['port']);
$bio = new Bio();

$bio->query_itfpid('atp', $first, $last, $redis);
