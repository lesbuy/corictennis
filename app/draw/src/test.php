<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$tour = get_param($argv, 1, '0718');
$year = get_param($argv, 2, 2020);
$asso = get_param($argv, 3, "");

$level = "";

if (in_array($tour, ['AO', 'RG', 'WC', 'UO'])) {
	$level = "GS";
} else if (strlen($tour) > 6) {
	if (substr($tour, 0, 1) == "J") $level = "JU";
	else $level = "ITF";
} else {
//	$level = "WT";
	if ($asso == "atp") {
		$level = "ATP";
	} else if ($asso == "wta") {
		$level = "WTA";
	} else {
		exit;
	}
}

if ($level == "GS") require_once($tour . '.php');
else require_once($level . '.php');

$event = new Event($tour, $year);
$event->processLive();

#$event->calaTeamFinal();
$event->outputRawDraws();
$event->outputRawMatches();
$event->outputRawTeams();
$event->outputH2H();
#$event->outputRounds();
#$event->outputRawOOPs();
#$event->outputPlayers();
#$event->outputOOPs();
#$event->outputLive();
#$event->output();

#$event->outputDraws();
