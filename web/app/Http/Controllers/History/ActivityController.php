<?php

namespace App\Http\Controllers\History;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use Config;
use App;
use DB;

class ActivityController extends Controller
{
    //
	protected $match_schema = ['id', 'player', 'ioc', 'time', 'year', 'tourid', 'tourname', 'level', 'loc', 'ground', 'sd', 'totalprize', 'rank', 'seed', 'entry', 'partnerid', 'partnername', 'partnerioc', 'seq', 'round', 'winorlose', 'oppoid', 'opponame', 'opponation', 'opporank', 'opposeed', 'oppoentry', 'games'];
	protected $tour_schema = ['id', 'player', 'ioc', 'time', 'year', 'tourid', 'tourname', 'level', 'loc', 'ground', 'sd', 'totalprize', 'rank', 'seed', 'entry', 'partnerid', 'partnername', 'partnerioc', 'seq', 'finalround', 'point', 'prize', 'win', 'lose'];

	public function index($lang) {

		App::setLocale($lang);

		return view('activity.index', [
			'pageTitle' => __('frame.menu.activity'),
			'title' => __('frame.menu.activity'),
			'pagetype1' => 'activity',
			'pagetype2' => 'index',
		]);
	}

	public function query(Request $req, $lang) {

		App::setLocale($lang);

		$ret = [];

		$status = $req->input('status', 'wrongP1');

		if ($status != 'ok') {

			$ret['status'] = -1;

			$ret['errmsg'] = __('h2h.warning.' . $status);

		} else {

			$ret['status'] = 0;

			$ret['filter'] = '';

			$p1 = $req->input('p1id');
			$year = $req->input('year');
			$type = $req->input('type', 'atp');

			$file = join('/', [env('ROOT'), 'store', 'activity', $type, $p1 . "*"]);

			$cmd = "cat $file";

			$level = $req->input('level', 'a');
			$sd = $req->input('sd', 's');
			$sfc = $req->input('surface', 'a');
			$md = $req->input('onlyMD', '');
			$final = $req->input('onlyFinal', '');

			if ($year > -1) {
				$cmd .= " | awk -F\"\\t\" '$5 == $year' ";
				$ret['filter'] .= "\t" . $year;
			}

			if ($sd == 's') {
				$cmd .= " | awk -F\"\\t\" '$11 == \"S\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.sd.s');
			} else if ($sd == 'd') {
				$cmd .= " | awk -F\"\\t\" '$11 == \"D\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.sd.d');
			}

			if ($md == 'y') {
//				$cmd .= " | awk -F\"\\t\" '$20 !~ /^Q[1-9R-]/' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.md.y');
			}

			if ($final == 'y') {
//				$cmd .= " | awk -F\"\\t\" '$20 == \"F\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.final.y');
			}

			if ($level == 'g') {
				$cmd .= " | awk -F\"\\t\" '$8 == \"GS\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.g');
			} else if ($level == 'm') {
				$cmd .= " | awk -F\"\\t\" '$8 == \"1000\" || $8 == \"PM\" || $8 == \"P5\" || $8 == \"MS\" || $8 == \"CSS\" || $8 == \"T1\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.m');
			} else if ($level == 't') {
				$cmd .= " | awk -F\"\\t\" '$8 != \"ITF\" && $8 != \"FU\" && $8 != \"CH\" && $8 != \"125K\" && $8 != \"C\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.t');
			} else if ($level == 'ao') {
				$cmd .= " | awk -F\"\\t\" '$8 == \"GS\" && $7 == \"australian open\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.ao');
			} else if ($level == 'rg') {
				$cmd .= " | awk -F\"\\t\" '$8 == \"GS\" && ($7 == \"french open\" || $7 == \"roland garros\")' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.rg');
			} else if ($level == 'wc') {
				$cmd .= " | awk -F\"\\t\" '$8 == \"GS\" && $7 == \"wimbledon\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.wc');
			} else if ($level == 'uo') {
				$cmd .= " | awk -F\"\\t\" '$8 == \"GS\" && $7 == \"us open\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.uo');
			} else if ($level == 'ol') {
				$cmd .= " | awk -F\"\\t\" '$8 == \"OL\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.ol');
			} else if ($level == 'dc') {
				$cmd .= " | awk -F\"\\t\" '$8 == \"DC\" || $8 == \"FC\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.dc');
			} else if ($level == 'yec') {
				$cmd .= " | awk -F\"\\t\" '($8 == \"YEC\" && $7 != \"bali\" && $7 != \"sofia\" && $7 != \"zhuhai\") || $8 == \"WC\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.yec');
			}

			if ($sfc == 'h') {
				$cmd .= " | awk -F\"\\t\" '$10 ~ /^Hard/' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.sfc.h');
			} else if ($sfc == 'c') {
				$cmd .= " | awk -F\"\\t\" '$10 ~ /^Clay/' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.sfc.c');
			} else if ($sfc == 'g') {
				$cmd .= " | awk -F\"\\t\" '$10 ~ /^Grass/' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.sfc.g');
			} else if ($sfc == 'p') {
				$cmd .= " | awk -F\"\\t\" '$10 ~ /^Carpet/' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.sfc.p');
			}

			$ret['filter'] = trim($ret['filter']);
			if ($ret['filter']) {
				$ret['filter'] = str_replace("\t", __('h2h.selectBar.filter.comma'), $ret['filter']);
			}

			$cmd .= " | sort -k5gr,5 -k4gr,4 -k19gr,19 -t$'\t' ";
			unset($r); exec($cmd, $r);

			$ret['win'] = $ret['lose'] = 0;
			$ret['tours'] = [];

			if (!$r) {
				$ret['status'] = -2;
				$ret['errmsg'] = __('h2h.warning.noResult');
			} else {
				foreach ($r as $row) {
					$arr = explode("\t", $row);
					if ($arr[18] == 100) {
						$key = join("\t", [$arr[3], $arr[4], $arr[5]. $arr[6]]);
						foreach ($this->tour_schema as $k => $v) {
							$ret['tours'][$key][$v] = @$arr[$k];
						}
						$ret['tours'][$key]['matches'] = [];
					} else {
						if ($md == 'y' && (preg_match('/^Q[0-9]/', $arr[19]))) continue;
						if ($final == 'y' && ($arr[19] != "F")) continue;

						$key = join("\t", [$arr[3], $arr[4], $arr[5]. $arr[6]]);
						$lastone = count($ret['tours'][$key]['matches']);
						foreach ($this->match_schema as $k => $v) {
							if (in_array($v, ['oppoid', 'opponame', 'opponation', 'opporank'])) {
								$ret['tours'][$key]['matches'][$lastone][$v] = explode("/", @$arr[$k]);
							} else {
								$ret['tours'][$key]['matches'][$lastone][$v] = @$arr[$k];
							}
						}

						if ($ret['tours'][$key]['matches'][$lastone]['games'] && $ret['tours'][$key]['matches'][$lastone]['games'] != "-" && $ret['tours'][$key]['matches'][$lastone]['games'] != "W/O") {
							if ($ret['tours'][$key]['matches'][$lastone]['winorlose'] == "W") ++$ret['win'];
							else ++$ret['lose'];
						}
					}
				}
			}

			$cmd = "grep '^$p1\t' " . join('/', [env('ROOT'), $type, "player_headshot"]) . " | cut -f3";
			unset($r); exec($cmd, $r);
			if ($r && isset($r[0])) {
				if (strpos($r[0], "http") === 0) {
					$ret['p1head'] = $r[0];
				} else {
					$ret['p1head'] = url(env('CDN') . '/images/' . $type . '_headshot/' . preg_replace('/^.*\//', '', $r[0]));
				}
			} else {
				$ret['p1head'] = url(env('CDN') . '/images/' . $type . '_headshot/' . $type . 'player.jpg');
			}

			$tbname = "all_name_" . $type;
			$row = DB::table($tbname)->where('id', $p1)->first();
			$name1 = rename2short($row->first, $row->last, $row->nat);
			$ret['name1'] = $name1;

			$cmd = "grep '^$p1\t' " . join('/', [Config::get('const.root'), $type, "player_rank".($sd == 's' ? "" : "_d")]) . " | cut -f3 | head -1";
			unset($r); exec($cmd, $r);
			if ($r && isset($r[0])) {
				$ret['rank1'] = $r[0];
			} else {
				$ret['rank1'] = "";
			}


		}// if status
//		return json_encode($ret);
		return view('activity.query', ['ret' => $ret]);
	}

	public function new_query(Request $req, $lang) {

		App::setLocale($lang);

		$ret = [];

		$status = $req->input('status', 'wrongP1');

		$kvmap = [];
		foreach (Config::get('const.schema_activity') as $k => $v) {
			$kvmap[$v] = $k + 1;
		}
		$kvmap_match = [];
		foreach (Config::get('const.schema_activity_matches') as $k => $v) {
			$kvmap_match[$v] = $k + 1;
		}

		if ($status != 'ok') {

			$ret['status'] = -1;

			$ret['errmsg'] = __('h2h.warning.' . $status);

		} else {

			$ret['status'] = 0;

			$ret['filter'] = '';

			$p1 = $req->input('p1id');
			$year = $req->input('year');
			$type = $req->input('type', 'atp');
			$level = $req->input('level', 'a');
			$sd = $req->input('sd', 's');
			$sfc = $req->input('surface', 'a');
			$md = $req->input('onlyMD', '');
			$final = $req->input('onlyFinal', '');

			$file1 = join('/', [env('ROOT'), 'data', 'calc', $type, $sd, "year", "unloaded"]);
			$file2 = join('/', [env('ROOT'), 'data', 'calc', $type, $sd, "year", "comingup"]);
			$file3 = join('/', [env('ROOT'), 'data', 'activity', $type, $p1]);

			$cmd = "cat $file1 $file2 $file3 | awk -F\"\\t\" '$1 == \"$p1\"'";

			if ($year > -1) {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['year'] . " == $year' ";
				$ret['filter'] .= "\t" . $year;
			}

			if ($sd == 's') {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['sd'] . " == \"s\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.sd.s');
			} else if ($sd == 'd') {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['sd'] . " == \"d\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.sd.d');
			}

			if ($md == 'y') {
//				$cmd .= " | awk -F\"\\t\" '$20 !~ /^Q[1-9R-]/' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.md.y');
			}

			if ($final == 'y') {
//				$cmd .= " | awk -F\"\\t\" '$20 == \"F\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.final.y');
			}

			if ($level == 'g') {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['level'] . " == \"GS\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.g');
			} else if ($level == 'm') {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['level'] . " == \"1000\" || $" . $kvmap['level'] . " == \"WTA1000\" || $" . $kvmap['level'] . " == \"PM\" || $" . $kvmap['level'] . " == \"P5\" || $" . $kvmap['level'] . " == \"MS\" || $" . $kvmap['level'] . " == \"CSS\" || $" . $kvmap['level'] . " == \"T1\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.m');
			} else if ($level == 't') {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['level'] . " != \"ITF\" && $" . $kvmap['level'] . " != \"FU\" && $" . $kvmap['level'] . " != \"CH\" && $" . $kvmap['level'] . " != \"125K\" && $" . $kvmap['level'] . " != \"C\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.t');
			} else if ($level == 'ao') {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['level'] . " == \"GS\" && $" . $kvmap['city'] . " == \"AUSTRALIAN OPEN\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.ao');
			} else if ($level == 'rg') {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['level'] . " == \"GS\" && ($" . $kvmap['city'] . " == \"FRENCH OPEN\" || $" . $kvmap['city'] . " == \"ROLAND GARROS\")' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.rg');
			} else if ($level == 'wc') {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['level'] . " == \"GS\" && $" . $kvmap['city'] . " == \"WIMBLEDON\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.wc');
			} else if ($level == 'uo') {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['level'] . " == \"GS\" && $" . $kvmap['city'] . " == \"US OPEN\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.uo');
			} else if ($level == 'ol') {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['level'] . " == \"OL\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.ol');
			} else if ($level == 'dc') {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['level'] . " == \"DC\" || $" . $kvmap['level'] . " == \"FC\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.dc');
			} else if ($level == 'yec') {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['level'] . " == \"YEC\" || $" . $kvmap['level'] . " == \"WC\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.yec');
			}

			if ($sfc == 'h') {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['sfc'] . " ~ /^Hard/' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.sfc.h');
			} else if ($sfc == 'c') {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['sfc'] . " ~ /^Clay/' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.sfc.c');
			} else if ($sfc == 'g') {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['sfc'] . " ~ /^Grass/' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.sfc.g');
			} else if ($sfc == 'p') {
				$cmd .= " | awk -F\"\\t\" '$" . $kvmap['sfc'] . " ~ /^Carpet/' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.sfc.p');
			}

			$ret['filter'] = trim($ret['filter']);
			if ($ret['filter']) {
				$ret['filter'] = str_replace("\t", __('h2h.selectBar.filter.comma'), $ret['filter']);
			}

			$cmd .= " | sort -k" . $kvmap['year'] . "gr," . $kvmap['year'] . " -k" . $kvmap['start_date'] . "gr," . $kvmap['start_date'] . " -t$'\t' ";
			unset($r); exec($cmd, $r);

			$ret['win'] = $ret['lose'] = 0;
			$ret['tours'] = [];

			if (!$r) {
				$ret['status'] = -2;
				$ret['errmsg'] = __('h2h.warning.noResult');
			} else {
				foreach ($r as $row) {
					$arr = explode("\t", $row);
					$key = join("\t", [$arr[$kvmap['start_date'] - 1], $arr[$kvmap['year'] - 1], $arr[$kvmap['eid'] - 1]. $arr[$kvmap['city'] - 1]]);
					foreach (Config::get('const.schema_activity') as $k => $v) {
						$ret['tours'][$key][$v] = @$arr[$k];
					}
					$ret['tours'][$key]['matches'] = [];

					$matches_str = $arr[$kvmap['matches'] - 1];
					$matches_arr = explode("@", $matches_str);

					if ($ret['tours'][$key]['partner_id'] != '') {
						$key1 = join('_', [$type, 'profile', $ret['tours'][$key]['partner_id']]);
						$res = Redis::hmget($key1, 'l_' . $lang, 'l_en', 'first', 'last', 'ioc', 'pt', 'hs', 'rank_s');
						$ret['tours'][$key]['partner_name'] = translate2short($ret['tours'][$key]['partner_id'], $res[2], $res[3], $res[4]);
					}

					foreach ($matches_arr as $match_string) {
						$match_arr = explode("!", $match_string);

						if ($md == 'y' && (preg_match('/^Q[0-9]/', $match_arr[$kvmap_match['round']]))) continue;
						if ($final == 'y' && ($match_arr[$kvmap_match['round']] != "F")) continue;

						$lastone = count($ret['tours'][$key]['matches']);
						foreach (Config::get('const.schema_activity_matches') as $k => $v) {
							$ret['tours'][$key]['matches'][$lastone][$v] = @$match_arr[$k + 1];
						}

						$a = explode("/", $ret['tours'][$key]['matches'][$lastone]['orank']);
						$ret['tours'][$key]['matches'][$lastone]['orank'] = $a[0];
						if (isset($a[1])) $ret['tours'][$key]['matches'][$lastone]['opartner_rank'] = $a[1];

						if ($ret['tours'][$key]['matches'][$lastone]['oid'] != '') {
							$key1 = join('_', [$type, 'profile', $ret['tours'][$key]['matches'][$lastone]['oid']]);
							$res = Redis::hmget($key1, 'l_' . $lang, 'l_en', 'first', 'last', 'ioc', 'pt', 'hs', 'rank_s');
							$ret['tours'][$key]['matches'][$lastone]['oname'] = translate2short($ret['tours'][$key]['matches'][$lastone]['oid'], $res[2], $res[3], $res[4]);
						}
						if ($ret['tours'][$key]['matches'][$lastone]['opartner_id'] != '') {
							$key1 = join('_', [$type, 'profile', $ret['tours'][$key]['matches'][$lastone]['opartner_id']]);
							$res = Redis::hmget($key1, 'l_' . $lang, 'l_en', 'first', 'last', 'ioc', 'pt', 'hs', 'rank_s');
							$ret['tours'][$key]['matches'][$lastone]['opartner_name'] = translate2short($ret['tours'][$key]['matches'][$lastone]['opartner_id'], $res[2], $res[3], $res[4]);
						}

						if ($ret['tours'][$key]['matches'][$lastone]['games'] && $ret['tours'][$key]['matches'][$lastone]['games'] != "-" && $ret['tours'][$key]['matches'][$lastone]['games'] != "W/O") {
							if ($ret['tours'][$key]['matches'][$lastone]['wl'] == "W") ++$ret['win'];
							else ++$ret['lose'];
						}
					}
					$ret['tours'][$key]['matches'] = array_reverse($ret['tours'][$key]['matches']);
				}
			}


			$key = join('_', [$type, 'profile', $p1]);
			$res = Redis::hmget($key, 'l_' . $lang, 'l_en', 'first', 'last', 'ioc');
			$ret['p1head'] = fetch_headshot($p1, $type)[1];
			$ret['name1'] = translate2short($p1, $res[2], $res[3], $res[4]);

			$ret['rank1'] = fetch_rank($p1, $type);
		}// if status
//		return json_encode($ret);
		return view('activity.query', ['ret' => $ret]);
	}

}
