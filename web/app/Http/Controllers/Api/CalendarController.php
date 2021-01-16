<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CalendarController extends Controller
{
    //
    public function getCalendarByYear($lang, $year) {

		App::setLocale($lang);
        $ret = [];
        
        $kv = array_flip(Config::get('const.schema_calendar'));

        // process GS,WT
        $file = join('/', [Config::get('const.root'), 'store', 'calendar', $year, "[GW][ST]"]);
        $cmd = "cat $file";
        unset($r); exec($cmd, $r);
        foreach ($r as $line) {
            $arr = explode("\t", $line);
            $level = $arr[$kv["level"]]; // 巡回赛的level就按level来
            $eid = $arr[$kv["eid"]];
            $city = $arr[$kv["city"]];
            $date = $arr[$kv["monday"]];
            $prize = @arr[$kv["prizeNum"]];
            $ioc = arr[$kv["loc"]];
            $gender = arr[$kv["gender"]]; // M, W, J
            $ret['WT'][$date][] = [
                "eid" => $eid, 
                "level" => $level, 
                "city" => $city, 
                "prize" => $prize, 
                "gender" => $gender, 
                "loc" => $ioc
            ];
        }
        if (isset($ret['WT'])) {
            foreach ($ret['WT'] as $k => $v) {
                usort($ret['WT'][$k], "self::prizeSort");
            }
            ksort($ret['WT']);
        }

        // process CH,ITFs
        $file = join('/', [Config::get('const.root'), 'store', 'calendar', $year, "[CI][HT]*"]);
        $cmd = "cat $file";
        unset($r); exec($cmd, $r); 
        foreach ($r as $line) {
            $arr = explode("\t", $line);
            $level = $arr[$kv["level"]];
            $eid = $arr[$kv["eid"]];
            $city = $arr[$kv["city"]];
            $date = $arr[$kv["monday"]];
            $prize = @arr[$kv["prizeNum"]];
            $prizeStr = arr[$kv["prize"]];
            $ioc = arr[$kv["loc"]];
            $gender = arr[$kv["gender"]]; // M, W, J

            // 只要大于40k的都放到CH里，包括了挑战赛，125k，以及大于50k的女子itf赛
            if ($prize > 40000) $category = "CH";
            else if ($level == "ITF" || substr($level, 0, 1) == "M" || substr($level, 0, 1) == "W") $category = "ITF";
            else $category = "J";

            // 如果没有奖金级别，就按12列的奖金数
            if (!$prize) $prize = intval(str_replace("$", "", $arr[11]));
            if (strpos($prizeStr, '+') !== false) {
                $hospital = true;
            } else {
                $hospital = false;
            }

            if ($category == "J") {
                if ($level == "JA") {
                    $prize = 8000;
                } else if ($level == "JB" || $level == "JB1") {
                    $prize = 7000;
                } else if ($level == "JB2") {
                    $prize = 6000;
                } else {
                    $prize = (6 - intval(str_replace('J', '', $level))) * 1000;
                }
            }

            if ($category == "ITF") {
                if ($gender == "M") $type = 3; else if ($gender == "W") $type = 2;
            } else {
                $type = 4;
            }

            $pr = $prize / 1000;
            if ($category == "CH") {
                // 大于40k的情况
                if (substr($level, 0, 3) == "WTA") {
                    $pr = "WTA125";
                } else if ($level == "125K") {
                    $pr = "125K";
                } else if ($level == "CH") {
                    $pr = "CH" . $pr;
                } else if (substr($level, 0, 2) == "CH") {
                    $pr = $level;
                } else if ($level == "ITF" || substr($level, 0, 1) == "W") {
                    $pr = "W" . $pr . ($hospital ? "+H" : "");
                }
            } else if ($category == "ITF") {
                if (substr($level, 0, 1) == "W" || substr($level, 0, 1) == "M") {
                    $pr = $level . ($hospital ? "+H" : "");
                } else if ($gender == "M") {
                    $pr = "M" . $pr . ($hospital ? "+H" : "");
                } else {
                    $pr = "W" . $pr . ($hospital ? "+H" : "");
                }
            } else if ($category == "J") {
                $pr = $level;
            }

            $prize = $type * 10000000 + $prize + ($hospital ? 1 : 0);
            $ret[$category][$date][] = [
                "eid" => $eid, 
                "level" => $pr, 
                "city" => $city, 
                "prize" => $prize, 
                "gender" => $gender, 
                "loc" => $ioc
            ];
        }
        if (isset($ret['ITF'])) {
            foreach ($ret['ITF'] as $k => $v) {
                $ret['ITF'][$k][] = [
                    "eid" => 1, 
                    "level" => "", 
                    "city" => "blank",
                    "prize" => 29999999, 
                    "gender" => "M", 
                    "loc" => ""
                ];
                usort($ret['ITF'][$k], "self::prizeSort");
            }
        }
        if (isset($ret['CH'])) {
            foreach ($ret['CH'] as $k => $v) {
                usort($ret['CH'][$k], "self::prizeSort");
            }
        }
        if (isset($ret['J'])) {
            foreach ($ret['J'] as $k => $v) {
                usort($ret['J'][$k], "self::prizeSort");
            }
        }

		$ret['year'] = $year;
        return json_encode($ret);
        /*
		return view('draw.calendar', [
			'ret' => $ret, 
			'pageTitle' => $year . " " . __('frame.menu.calendar'),
			'title' => $year . " " . __('frame.menu.calendar'),
			'pagetype1' => 'calendar',
			'pagetype2' => $year,
        ]);
        */
    }
    
    protected function prizeSort($a, $b) {
		return $a["prize"] >= $b["prize"] ? -1 : 1;
	}
}
