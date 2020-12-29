<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php');
require_once(APP . '/conf/wt_bio.php');

$pid = get_param($argv, 1, null);

function update($pid, &$redis, &$bio) {
	if (strlen($pid) == 4) {
		$gender = "atp";
	} else if (strlen($pid) == 5 || strlen($pid) == 6) {
		$gender = "wta";
	} else {
		return false;
	}
	$bio->down_bio($pid, $gender, $redis);
}

$redis = new redis_cli('127.0.0.1', 6379);
$bio = new Bio();

$set = [];

if (preg_match('/^[A-Z0-9]{4,6}$/', $pid)) {
	$set[] = $pid;
} else if ($pid == "unknown_birthday") {
	foreach ($redis->cmd('KEYS', 'atp_profile_*')->get() as $p) {
		if ($redis->cmd('HGET', $p, 'birthday')->get() == "1970-01-01" && preg_match('/^atp_profile_[A-Z0-9]{4,6}$/', $p)) {
			$set[] = str_replace('atp_profile_', '', $p);
		}
	}
	foreach ($redis->cmd('KEYS', 'wta_profile_*')->get() as $p) {
		if ($redis->cmd('HGET', $p, 'birthday')->get() == "1970-01-01" && preg_match('/^wta_profile_[A-Z0-9]{4,6}$/', $p)) {
			$set[] = str_replace('wta_profile_', '', $p);
		}
	}
}

foreach ($set as $pid) {
	update($pid, $redis, $bio);
	sleep(1);
}
