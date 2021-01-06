<?php

namespace App\Http\Controllers\Breakdown;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
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
			$tbname = join('_', ['calc', $type, $sd, $period]);
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
			$ret['name'] = translate2short($id);
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

			$res = Redis::hmget(join("_", [$type, "profile", $id]), 'hs');

			if ($res[0]) {
				if (strpos($res[0], "http") === 0) {
					$ret['head_path'] = $res[0];
				} else {
					$ret['head_path'] = get_headshot($type, preg_replace('/^.*\//', '', $res[0]));
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
						$level = 'FU';
					} else if ($level >= 40 && $level < 170) {
						$level = 'CH';
					} else {
					}
				} else if ($type == "wta") {
					if ($level > 0 && $level < 115) {
						$level = 'ITF';
					} else if (($level >= 115 && $level < 170) || $level == "C") {
						$level = '125K';
					} else {
					}
				} else {
				}

				if ($arr[6] == 100) {
					$level = 'DROP';
					@$bydrop[$level][] = [$tour, $point, $round];
				} else {
					if ($seq < 0) {
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
