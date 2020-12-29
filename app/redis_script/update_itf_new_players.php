<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

require_once(APP . '/conf/wt_bio.php');

$bio = new Bio();

$redis = new redis_cli($db_conf['redis']['host'], $db_conf['redis']['port']);

// 从itf新选手列表插入到itf_profile中，如果在profile中已经有了，或者已经redirect了，就不插入
$fp = fopen("itf_new_players", "r");
while ($line = fgets($fp)) {
	$arr = explode("\t", $line);
	$itfpid = $arr[1];
	$first = ucwords(strtolower($arr[3]));
	$last = ucwords(strtolower(trim($arr[4])));
	$ioc = $arr[2];
	$sex = $arr[0];

	if (!$redis->cmd('KEYS', 'itf_profile_' . $itfpid)->get() && !$redis->cmd('HGET', 'itf_redirect', $itfpid)->get()) {
		$l_en = $bio->rename2long($first, $last, $ioc);
		$s_en = $bio->rename2short($first, $last, $ioc);
		$redis->cmd('HMSET', 'itf_profile_' . $itfpid, 'first', $first, 'last', $last, 'ioc', $ioc, 'l_en', $l_en, 's_en', $s_en)->set();

		if ($sex == "M") {
			$wtpid = $bio->query_wtpid("atp", $first, $last, $redis, $itfpid);
		} else if ($sex == "F") {
			$wtpid = $bio->query_wtpid("wta", $first, $last, $redis, $itfpid);
		}   
	}
}

