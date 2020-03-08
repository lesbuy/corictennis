<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

if (isset($argv[1])) $tour = $argv[1];
if (isset($argv[2])) $year = $argv[2];

$level = "";

if (in_array($tour, ['AO', 'RG', 'WC', 'UO'])) {
	$level = "GS";
} else {
	$level = "WT";
}

if ($level == "GS") require_once($tour . '.php');
else if ($level == "WT") require_once('WT.php');

$event = new Event($tour, $year);
$event->process();
$event->outputLive();
