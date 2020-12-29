<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 
require_once(APP . '/conf/wt_bio.php');

$redis = new redis_cli($db_conf['redis']['host'], $db_conf['redis']['port']);
$bio = new Bio();

// 800354292	Kaito	Itsusaki	Kaito Itsusaki	JPN	逸崎凯人	逸崎凯人	逸﨑 凱人	逸﨑 凱人

$fp = fopen("itf_chinese_name", "r");
while ($line = trim(fgets($fp))) {
	$arr = explode("\t", $line);
	$itfid = $arr[0];
	$first = $arr[1];
	$last = $arr[2];
	$ioc = $arr[4];
	$s_en = $bio->rename2short($first, $last, $ioc);
	$l_en = $bio->rename2long($first, $last, $ioc);
	$s_zh = $arr[5];
	$l_zh = $arr[6];
	$s_ja = @$arr[7];
	$l_ja = @$arr[8];

	$redis->cmd('HMSET', "itf_profile_" . $itfid,
		'first', $first,
		'last', $last,
		'ioc', $ioc,
		's_en', $s_en,
		'l_en', $l_en,
		's_zh', $s_zh,
		'l_zh', $l_zh
	)->set();

	if ($s_ja && $l_ja) {
		$redis->cmd('HMSET', "itf_profile_" . $itfid,
			's_ja', $s_ja,
			'l_ja', $l_ja
		)->set();
	}
}
