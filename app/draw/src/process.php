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

$total_tic = tic();

// 读入所有数据并处理
$tic = tic();
$event = new Event($tour, $year);
$event->process();
print_line($tour, $year, "process", "done", toc($tic));

// 输出draw
$tic = tic();
$file_temp_draw = "temp_draw";
$fp_temp_draw = fopen($file_temp_draw, "w");
$event->outputDraws($fp_temp_draw);
fclose($fp_temp_draw);

$cmd = "sort -t\"	\" -k1r,1 -k2,2 -s -u -o $file_temp_draw $file_temp_draw";
exec($cmd, $r);
$cmd = "mv $file_temp_draw " . join("/", [STORE, "draw", $year, $tour]);
exec($cmd, $r);
print_line($tour, $year, "draw", "done", toc($tic));

// 输出activity
$tic = tic();
$file_temp_activity = "temp_activity";
$fp_temp_activity = fopen($file_temp_activity, "w");
$event->outputOOPs($fp_temp_activity);
fclose($fp_temp_activity);

$cmd = "grep atp $file_temp_activity | cut -f2- > " . $file_temp_activity . "_atp; grep wta $file_temp_activity | cut -f2- > " . $file_temp_activity . "_wta";
exec($cmd, $r);
$cmd = "mv " . $file_temp_activity . "_atp " . join("/", [DATA, "activity_current", "atp", $tour]);
exec($cmd, $r);
$cmd = "mv " . $file_temp_activity . "_wta " . join("/", [DATA, "activity_current", "wta", $tour]);
exec($cmd, $r);
$cmd = "rm " . $file_temp_activity;
exec($cmd, $r);
print_line($tour, $year, "activity", "done", toc($tic));

// 输出h2h
$tic = tic();
$file_temp_h2h = "temp_h2h";
$fp_temp_h2h = fopen($file_temp_h2h, "w");
$event->outputH2H($fp_temp_h2h);
fclose($fp_temp_h2h);

$cmd = "grep atp $file_temp_h2h | cut -f2- > " . $file_temp_h2h . "_atp; grep wta $file_temp_h2h | cut -f2- > " . $file_temp_h2h . "_wta";
exec($cmd, $r);
$cmd = "mv " . $file_temp_h2h . "_atp " . join("/", [DATA, "h2h_current", "atp", $tour]);
exec($cmd, $r);
$cmd = "mv " . $file_temp_h2h . "_wta " . join("/", [DATA, "h2h_current", "wta", $tour]);
exec($cmd, $r);
$cmd = "rm " . $file_temp_h2h;
exec($cmd, $r);
print_line($tour, $year, "h2h", "done", toc($tic));

// 巡回赛需要输出round
if ($level != "GS" && $level != "ITF") {
	$tic = tic();
	$file_temp_round = "temp_round";
	$fp_temp_round = fopen($file_temp_round, "w");
	$event->outputRounds($fp_temp_round);
	fclose($fp_temp_round);

	$cmd = "sort -t\"	\" -k1,1 -k2gr,2 -s -o $file_temp_round $file_temp_round";
	exec($cmd, $r);
	$cmd = "mv $file_temp_round " . join("/", [STORE, "round", $year, $tour]);
	exec($cmd, $r);
	print_line($tour, $year, "round", "done", toc($tic));
}

print_line("--------------------------------------------------------", $tour, $year, "all", "done", toc($total_tic) * 1000 . "ms");
