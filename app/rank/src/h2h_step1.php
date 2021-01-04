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
    $sd = strtoupper($arr[14]);
    $year = $arr[4];
    $time = $arr[5];
    $eid = $arr[2];
    $city = $arr[8];
    $level = $arr[10];
    $loc = $arr[9];
    $sfc = $arr[11];
    
    $pid = $arr[0];
    $partner_id = $arr[18];

    $_rankArr = explode("/", $arr[15]);
    $rank = $_rankArr[0];
    $partner_rank = @$_rankArr[1];

    $info = $arr[27];

    $matches = explode("@", $info);

    foreach ($matches as $match) {
        $cols = explode("!", $match);
        $round = $cols[2];
        $wl = $cols[3];
        $games = $cols[4];
        $oppo_id = $cols[8];
        if ($oppo_id == "" || $oppo_id == "BYE" || $wl == "") continue;
        $oppo_partner_id = $cols[10];
        $_rankArr = explode("/", $cols[5]);
        $oppo_rank = $_rankArr[0];
        $oppo_partner_rank = @$_rankArr[1];

        $me_id = $arr[0];
        $me_partner_id = $arr[18];
        $me_rank = $rank;
        $me_partner_rank = $partner_rank;

        if (isset($cols[12]) && !$me_partner_id) {
            $me_partner_id = $cols[12];
        }

        if ($sd == "D" && compare($me_id, $me_partner_id, $gender) > 0) {
            swap($me_id, $me_partner_id);
            swap($me_rank, $me_partner_rank);
        }
        if ($sd == "D" && compare($oppo_id, $oppo_partner_id, $gender) > 0) {
            swap($oppo_id, $oppo_partner_id);
            swap($oppo_rank, $oppo_partner_rank);
        }

        $winner1ID = $me_id;
        $winner2ID = $me_partner_id;
        $loser1ID = $oppo_id;
        $loser2ID = $oppo_partner_id;
        $winner1rank = $me_rank;
        $winner2rank = $me_partner_rank;
        $loser1rank = $oppo_rank;
        $loser2rank = $oppo_partner_rank;

        if ($wl == "L") {
            swap($winner1ID, $loser1ID);
            swap($winner2ID, $loser2ID);
            swap($winner1rank, $loser1rank);
            swap($winner2rank, $loser2rank);
        }
 
        $winner1first = $winner1last = $winner1ioc = "";
        $winner2first = $winner2last = $winner2ioc = "";
        $loser1first = $loser1last = $loser1ioc = "";
        $loser2first = $loser2last = $loser2ioc = "";

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
