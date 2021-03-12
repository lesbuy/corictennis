<?php

namespace App\Http\Controllers\Draw;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Config;
use App;

class ListController extends Controller
{
    //
	public function index($lang) {

		App::setLocale($lang);

		$this_monday = date('Ymd', strtotime("+1 day"));
		$monday = strtotime("$this_monday last Monday");
		$monday_last = $monday - 7 * 86400;
		$monday_next = $monday + 7 * 86400;

		$ret['ThisWeek'] = self::get_tours($monday);
		$ret['NextWeek'] = self::get_tours($monday_next);
		$ret['LastWeek'] = self::get_tours($monday_last);

//		return json_encode($ret);
		return view('draw.list', ['ret' => $ret]);
	}

	protected function get_tours($monday) {

		$year = date('Y', $monday + 86400);
		$files = [
			Config::get('const.root') . "/store/calendar/" . $year . "/GS", 
			Config::get('const.root') . "/store/calendar/" . $year . "/WT",
			Config::get('const.root') . "/store/calendar/" . $year . "/CH",
			Config::get('const.root') . "/store/calendar/" . $year . "/ITF",
		];

		$monday_last = $monday - 7 * 86400;

		$cmd = "grep -E \"$monday|$monday_last\" " . join(" ", $files);
		unset($r); exec($cmd, $r);

		$info = [];

		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				if ((@$arr[21] == 2 && $arr[6] == $monday_last)	|| $arr[6] == $monday) {
					$match = "";
					if (preg_match('/\/([A-Z]+):(.*)$/', $arr[0], $match)) {
						$level = $match[1];
						$eid = $arr[1];
						$year = $arr[4];
						$title = $arr[7];
						$sfc = $arr[8];
						$city = $arr[9];
						$ioc = $arr[10];

						if ($match[2] == "CH") $level = "ATP-Challenger";
						else if ($match[2] == "125K") $level = "WTA-125K";
						else if ($match[2] == "ITF") {
							if (substr($eid, 0, 1) == "M") {
								$level = "ITF-men";
							} else if (substr($eid, 0, 1) == "W") {
								$level = "ITF-women";
							}
						} else if (preg_match('/^G[AB1-5][1-5]?$/', $match[2])) {
							$level = "ITF-junior";
						}
						$type = explode("/", $match[2]);
						$logo = [];
						foreach ($type as $k => $v) {
							$logo[] = get_tour_logo_by_id_type_name($eid, $v);
						}

						$info[$level][] = [$eid, $year, $logo, $city, $ioc, $title, $sfc];
					}
				}
			}
		}

		uksort($info, "self::itfLevelSort");
		return $info;
	}

	public function byyear($lang, $year) {

		App::setLocale($lang);
		$ret = [];

		foreach (['WT', 'ITF'] as $level) {
			if ($level == 'WT') {
				$file = join('/', [Config::get('const.root'), 'store', 'calendar', $year, "[GW]*"]);
				$cmd = "cat $file";
				unset($r); exec($cmd, $r);
				foreach ($r as $line) {
					$arr = explode("\t", $line);
					$eid = $arr[1];
					$city = $arr[9];
					$date = $arr[5];
					$prize = @$arr[20];
					$ioc = $arr[10];
					//if ($arr[0] == "GS") {
					//	$ret['WT']['GS'][] = [$eid, explode("/", $arr[0]), $city, -$arr[6], $arr[3], 0, $ioc];
					//} else {
						$ret['WT'][$date][] = [$eid, explode("/", $arr[0]), $city, $prize, $arr[3], 0, $ioc];
					//}
				}
				if (isset($ret['WT'])) {
					foreach ($ret['WT'] as $k => $v) {
						usort($ret['WT'][$k], "self::prizeSort");
					}
					ksort($ret['WT']);
				}
			} else {
				$file = join('/', [Config::get('const.root'), 'store', 'calendar', $year, "[CI]*"]);
				$cmd = "cat $file";
				unset($r); exec($cmd, $r); 
				foreach ($r as $line) {
					$arr = explode("\t", $line);
					$eid = $arr[1];
					$city = $arr[9];
					$date = $arr[5];
					$prize = @$arr[20];
					$ioc = $arr[10];

					// 只要大于40k的都放到CH里，包括了挑战赛，125k，以及大于50k的女子itf赛
					if ($prize > 30000) $level = "CH";
					else if ($arr[0] == "ITF" || substr($arr[0], 0, 1) == "M" || substr($arr[0], 0, 1) == "W") $level = "ITF";
					else $level = "J";

					// 如果没有奖金级别，就按12列的奖金数
					if (!$prize) $prize = intval(str_replace("$", "", $arr[11]));
					if (strpos($arr[11], '+') !== false) {
						$hospital = true;
					} else {
						$hospital = false;
					}

					if ($level == "J") {
						if ($arr[0] == "GA") {
							$prize = 8000;
						} else if ($arr[0] == "GB" || $arr[0] == "GB1") {
							$prize = 7000;
						} else if ($arr[0] == "GB2") {
							$prize = 6000;
						} else {
							$prize = (6 - intval(str_replace('G', '', $arr[0]))) * 1000;
						}
					}

//					if (strpos($arr[11], '+') !== false) $prize = intval($prize) + 1; else $prize = intval($prize);

					if ($level == "ITF") {
						if ($arr[3] == "M") $type = 3; else if ($arr[3] == "W") $type = 2;
					} else {
						$type = 4;
					}

					$pr = $prize / 1000;
					if ($level == "CH") {
						// 大于40k的情况
						if (substr($arr[0], 0, 3) == "WTA") {
							$pr = "WTA125";
						} else if ($arr[0] == "125K") {
							$pr = "125K";
						} else if ($arr[0] == "CH") {
							$pr = "CH" . $pr;
						} else if (substr($arr[0], 0, 2) == "CH") {
							$pr = $arr[0];
						} else if ($arr[0] == "ITF" || substr($arr[0], 0, 1) == "W") {
							$pr = "W" . $pr . ($hospital ? "+H" : "");
						}
					} else if ($level == "ITF") {
						if (substr($arr[0], 0, 2) == "W" || substr($arr[0], 0, 2) == "M") {
							$pr = $arr[0] . ($hospital ? "+H" : "");
						} else if ($arr[3] == "M") {
							$pr = "M" . $pr . ($hospital ? "+H" : "");
						} else {
							$pr = "W" . $pr . ($hospital ? "+H" : "");
						}
					} else if ($level == "J") {
						$pr = $arr[0];
					}

/*
					$pr = preg_replace('/000\+/', 'K+', $arr[11]);
					$pr = preg_replace('/000$/', 'K', $pr);
					$match = "";
					preg_match('/^([^0-9]+)([0-9]+)(\+?)$/', $arr[11], $match);
					if (count($match) != 4) continue;

					$match[2] = round($match[2] / 1000, 0);
					$pr = join("", [$match[1], $match[2], "K", $match[3]]);
*/
					$prize = $type * 10000000 + $prize + ($hospital ? 1 : 0);
					$ret[$level][$date][] = [$eid, explode("/", $arr[0]), $city, $prize, $arr[3], $pr, $ioc];
				}
				if (isset($ret['ITF'])) {
					foreach ($ret['ITF'] as $k => $v) {
						$ret['ITF'][$k][] = [1, [""], "blank", 29999999, "M", ""];
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
			}
		}
		$ret['year'] = $year;
//		return json_encode($ret);
		return view('draw.calendar', [
			'ret' => $ret, 
			'pageTitle' => $year . " " . __('frame.menu.calendar'),
			'title' => $year . " " . __('frame.menu.calendar'),
			'pagetype1' => 'calendar',
			'pagetype2' => $year,
		]);
	}

	protected function prizeSort($a, $b) {
		return $a[3] >= $b[3] ? -1 : 1;
	}

	protected function itfLevelSort($a, $b) {
		if ($a == "GS") return -1;
		else if ($b == "GS") return 1;
		else if ($a == "WT") return -1;
		else if ($b == "WT") return 1;
		else if ($a == "ATP-Challenger") return -1;	
		else if ($b == "ATP-Challenger") return 1;
		else if ($a == "WTA-125K") return -1;
		else if ($b == "WTA-125K") return 1;
		else if ($a == "ITF-men") return -1;
		else if ($b == "ITF-men") return 1;
		else if ($a == "ITF-women") return -1;
		else if ($b == "ITF-women") return 1;
		else if ($a == "ITF-junior") return -1;
		else if ($b == "ITF-junior") return 1;

	}

}
