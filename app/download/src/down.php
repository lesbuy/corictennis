<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$asso = get_param($argv, 1, 'wta');

if ($asso == "wta") {
	require_once('WTA.php');
} else if ($asso == "atp") {
	require_once('ATP.php');
} else if ($asso == "ch") {
	require_once('CH.php');
} else if ($asso == "itf-men" || $asso == "itf-women") {
	require_once('ITF.php');
} else if ($asso == "ao") {
	require_once('AO.php');
}

$down = new Down($asso);
$down->process();
