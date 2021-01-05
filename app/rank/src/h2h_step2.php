<?php

if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$gender = get_param($argv, 1, "atp", " atp wta ");

$redis = new_redis();

function swap(&$a, &$b) {
    $tmp = $b;
    $b = $a;
    $a = $tmp;
}

function compare($a, $b, $gender) {
    if ($gender == "atp") {
        return strcmp($a, $b);
    } else {
        return intval($a) - intval($b);
    }
}

while ($line = trim(fgets(STDIN))) {

	$arr = explode("\t", $line);
 
        $winner1first = $winner1last = $winner1ioc = "";
        $winner2first = $winner2last = $winner2ioc = "";
        $loser1first = $loser1last = $loser1ioc = "";
        $loser2first = $loser2last = $loser2ioc = "";

/*
        if ($winner1ID != "") {
            $res = $redis->cmd('HMGET', join("_", [$gender, 'profile', $winner1ID]), 'first', 'last', 'ioc')->get();
            $winner1first = $res[0]; $winner1last = $res[1]; $winner1ioc = $res[2];
        }
        if ($winner2ID != "") {
            $res = $redis->cmd('HMGET', join("_", [$gender, 'profile', $winner2ID]), 'first', 'last', 'ioc')->get();
            $winner2first = $res[0]; $winner2last = $res[1]; $winner2ioc = $res[2];
        }
        if ($loser1ID != "") {
            $res = $redis->cmd('HMGET', join("_", [$gender, 'profile', $loser1ID]), 'first', 'last', 'ioc')->get();
            $loser1first = $res[0]; $loser1last = $res[1]; $loser1ioc = $res[2];
        }
        if ($loser2ID != "") {
            $res = $redis->cmd('HMGET', join("_", [$gender, 'profile', $loser2ID]), 'first', 'last', 'ioc')->get();
            $loser2first = $res[0]; $loser2last = $res[1]; $loser2ioc = $res[2];
        }
*/
        echo join("\t", [
            $sd,
            $winner1ID,
            $winner2ID,
            $loser1ID,
            $loser2ID,
            $winner1first,
            $winner2first,
            $loser1first,
            $loser2first,
            $winner1last,
            $winner2last,
            $loser1last,
            $loser2last,
            $winner1ioc,
            $winner2ioc,
            $loser1ioc,
            $loser2ioc,
            $games,
            $year,
            $time,
            $round,
            $eid,
            $city,
            $level,
            $loc,
            $sfc,
            $winner1rank,
            $winner2rank,
            $loser1rank,
            $loser2rank,
        ]) . "\n";
    }
}
