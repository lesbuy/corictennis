<?php

if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php');


$gender = get_param($argv, 1, "atp", " atp wta ");
$sd = get_param($argv, 1, "s", " s d ");

$calced = [];

$fp = fopen(join("/", [DATA, "calc", $gender, $sd, "year", "rank"]), "r");
while ($line = trim(fgets($fp))) {
    $arr = explode("\t", $line);
    $pid = $arr[0];
    $rank = $arr[2];
    $point = $arr[5];
    $plays = $arr[14];
    $calced[$pid] = [
        'rank' => $rank,
        'point' => $point,
        'plays' => $plays,
    ];
}
fclose($fp);

echo join("\t", [
    'PID',
    'Name',
    'Rank',
    'MyRank',
    'Point',
    'MyPoint',
    'Plays',
    'MyPlays',
    'DiffRank',
    'DiffPoint',
    'DiffPlays',
]) . "\n";

$fp = fopen(join("/", [DATA, "rank", $gender, $sd, "current"]), "r");
while ($line = trim(fgets($fp))) {
    $arr = explode("\t", $line);
    $pid = $arr[0];
    $name = $arr[1];
    $rank = $arr[2];
    $point = $arr[3];
    $plays = $arr[4];

    $calcRank = $calced[$pid]['rank'];
    $calcPoint = $calced[$pid]['point'];
    $calcPlays = $calced[$pid]['plays'];

    echo join("\t", [
        $pid,
        $name,
        $rank,
        $calcRank,
        $point,
        $calcPoint,
        $plays,
        $calcPlays,
        $calcRank - $rank,
        $calcPoint - $point,
        $calcPlays - $plays,
    ]) . "\n";
}
fclose($fp);