<?php

namespace App\Http\Controllers\Breakdown;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Config;
use App;
use DB;

class BreakdownQueryController extends Controller
{
    //
	public function query(Request $req, $lang, $type, $sd, $period) {

		App::setLocale($lang);

		$id = $req->input('id');

		$ret = ['id' => $id, 'type' => $type, 'sd' => $sd, 'period' => $period];

		if ($type == "guess") {

			$tbname = join('_', ['rank', $type, $sd, $period]);

		} else {
			$tbname = join('_', ['rank', $type, $sd, $period, 'en']);
		}

		$row = DB::table($tbname)->where('id', $id)->first();

		if ($row) {
			$ret['ioc'] = strtoupper($row->ioc);
			$server_file = url(env('CDN') . '/images/bigflag_jpg/' . $ret['ioc'] . '.jpg');

			if ($type == "guess") {
				$ret['nation'] = '';
				$ret['flag_path'] = '';
			} else {
				$ret['flag_path'] = $server_file;
				$ret['nation'] = Config::get('const.flag.' . $row->ioc) . translate('nationname', $row->ioc, true);
			}
			$arr = explode(",", $row->full_name);
			if (count($arr) == 2) {
				$ret['name'] = rename2short($arr[1], $arr[0], $ret['ioc']);
			} else {
				$ret['name'] = $row->full_name;
			}
			$ret['rank'] = $row->c_rank;
			$ret['win'] = $row->win;
			$ret['lose'] = $row->lose;
			$ret['point'] = $row->point;
		}

		if ($type == "guess") {

			$row = DB::table('users')->select('avatar')->where('id', $id)->first();

			if ($row && $row->avatar) {
				$ret['head_path'] = $row->avatar;
			} else {
				$ret['head_path'] = "http://tb.himg.baidu.com/sys/portrait/item/0";
			}

		} else {

			$cmd = "grep '^$id\t' " . join('/', [Config::get('const.root'), $type, "player_headshot"]) . " | cut -f3";

			unset($r); exec($cmd, $r);

			if ($r && isset($r[0])) {
				if (strpos($r[0], "http") === 0) {
					$ret['head_path'] = $r[0];
				} else {
					$ret['head_path'] = get_headshot($type, preg_replace('/^.*\//', '', $r[0]));
				}
			} else {
				$ret['head_path'] = url(env('CDN') . '/images/' . $type . '_headshot/' . $type . 'player.jpg');
			}

		}

		if ($type == "guess") {
			$cmd = "grep '^$id\t' " . join('/', [Config::get('const.root'), "dcpk", "all_results", join('_', ["compose", $sd, $period]) ]);
		} else {
			if ($period == "year") {
				$cmd = "grep '^$id\t' " . join('/', [Config::get('const.root'), 'data/calc', $type, $sd, $period, 'compose']);
			} else {
				$cmd = "grep '^$id\t' " . join('/', [Config::get('const.root'), $type, "all_results", join('_', ["compose", $sd, $period, 'en']) ]);
			}
		}

		unset($r); exec($cmd, $r);

		$bylevel = [];
		$bysfc = [];
		$bydate = [];
		$bydrop = [];
		$byalt = [];
		$scoreLevel = [];
		$scoreSurface = [];
		$scoreMonth = [];
		$scoreYear = [];

		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				$seq = $arr[1];
				$tour = translate_tour($arr[2]);
				$point = $arr[3];
				$round = $arr[4];
				if ($round == "PEN") $round = "-";
				$dropMonth = date("Y.m", strtotime($arr[5]));
				$dropYear = date('Y', strtotime($arr[5]) - 7 * 86400);
				$ground = reviseSurfaceWithoutIndoor($arr[7]);
				$level = preg_replace('/\$|A\$|€|K|k/', '', $arr[8]);
				if ($level < 170 && $level > 0) $tour .= '/' . $arr[8];
				$year = ""; if (isset($arr[9])) $year = $arr[9];

				if ($type == "atp") {
					if ($level > 0 && $level < 40) {
//						$level = __('frame.level.FU');
						$level = 'FU';
					} else if ($level >= 40 && $level < 170) {
//						$level = __('frame.level.CH');
						$level = 'CH';
					} else {
//						$level = __('frame.level.' . $level);
					}
				} else if ($type == "wta") {
					if ($level > 0 && $level < 115) {
//						$level = __('frame.level.ITF');
						$level = 'ITF';
					} else if (($level >= 115 && $level < 170) || $level == "C") {
//						$level = __('frame.level.125K');
						$level = '125K';
					} else {
//						$level = __('frame.level.' . $level);
					}
				} else {
//					$level = __('frame.level.' . $level);
				}

				if ($arr[6] == 100) {
//					$level = __('frame.level.DROP');
					$level = 'DROP';
					@$bydrop[$level][] = [$tour, $point, $round];
				} else {
					if ($seq < 0) {
//						$level = __('frame.level.ALT');
						$level = 'ALT';
						@$byalt[$level][] = [$tour, $point, $round];
					} else {
						@$bylevel[$level][] = [($year ? $year." " : "") . $tour, $point, $round];
						@$scoreLevel[$level] += $point;
					}
					@$bysfc[$ground][] = [$tour, $point, $round];
					@$bydate[$dropMonth][] = [$tour, $point, $round, (int)$arr[5]];
					@$scoreSurface[$ground] += $point;
					@$scoreMonth[$dropMonth] += $point;
					@$scoreYear[$dropYear - 1] += $point;
				}
			}
		}

		krsort($bydate);
		krsort($scoreMonth);

		foreach ($bysfc as $sfc => $rows) {
			array_multisort(array_column($rows, 1), SORT_DESC, $rows);
			$bysfc[$sfc] = $rows;
		}

		foreach ($bydate as $month => $rows) {
			usort($rows, function ($a, $b) {
				return $a[3] > $b[3] ? 1 : -1;
			});
			$bydate[$month] = $rows;
		}
		
		$ret['bylevel'] = $bylevel;
		if (count($byalt) > 0) {$ret['byalt'] = $byalt;}
		if (count($bydrop) > 0) {$ret['bydrop'] = $bydrop;}
		$ret['scoreLevel'] = $scoreLevel;
		$ret['bysfc'] = $bysfc;
		$ret['bydate'] = $bydate;
		$ret['scoreMonth'] = $scoreMonth;

//		return json_encode($ret, JSON_UNESCAPED_UNICODE);
		return view('breakdown.breakdown', [
			'ret' => $ret,
		]);
	}
}
