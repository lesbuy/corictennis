<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use App;
use Config;

class H2HController extends Controller
{
	public function query(Request $req, $lang, $gender, $sd, $homes, $aways) {
		App::setLocale($lang);

		$homes = $req->input('home', null);
		$aways = $req->input('away', null);
		$_round = $req->input('round', '');
		$_mode = $req->input('mode', 'p');

		if (!$homes || !$aways) {
			return ['status' => -1, 'errmsg' => __('api.h2h.notice.lack_player')];
		}

		// home和away如果有相同的选手，则返回错误
		$homes_arr = array_keys(array_flip(explode(',', $homes)));
		$aways_arr = array_keys(array_flip(explode(',', $aways)));
		$merge_arr = array_keys(array_flip(array_merge($homes_arr, $aways_arr)));
		if (count($homes_arr) + count($aways_arr) != count($merge_arr)) {
			return ['status' => -1, 'errmsg' => __('api.h2h.notice.dupli_player')];
		}

		// schema
		$out_schema = array_flip(Config::get('const.schema_activity'));
		$in_schema = array_flip(Config::get('const.schema_activity_matches'));

		// 获取过滤条件
		$filter = [];

		$filter['sd'] = [$sd];

		$_levels = $req->input('level', 'all');
		if (strpos($_levels, "all") === false && $_levels != "") { // level不为空，并且没有all，才添加条件
			$filter['level'] = [];
			$levels = explode(',', $_levels);
			foreach ($levels as $level) {
				if ($level == "GS") {
					$filter['level'][] = 'GS';
				} else if ($level == "MS") {
					$filter['level'][] = '1000';
					$filter['level'][] = 'WTA1000';
					$filter['level'][] = 'WTA1000M';
					$filter['level'][] = 'CSS';
					$filter['level'][] = 'MS';
					$filter['level'][] = 'PM';
					$filter['level'][] = 'P5';
					$filter['level'][] = 'T1';
				} else if ($level == 'P') {
					$filter['level'][] = '500';
					$filter['level'][] = 'WTA500';
					$filter['level'][] = 'ISG';
					$filter['level'][] = 'CS';
					$filter['level'][] = 'CSD';
					$filter['level'][] = 'P700';
					$filter['level'][] = 'T2';
				} else if ($level == 'IS') {
					$filter['level'][] = '250';
					$filter['level'][] = 'WTA250';
					$filter['level'][] = 'IS';
					$filter['level'][] = 'WS';
					$filter['level'][] = 'WSD';
					$filter['level'][] = 'WSF';
					$filter['level'][] = 'Int';
					$filter['level'][] = 'T3';
					$filter['level'][] = 'T4';
					$filter['level'][] = 'T5';
					$filter['level'][] = 'T4B';
					$filter['level'][] = 'T4A';
				} else if ($level == 'YEC') {
					$filter['level'][] = 'WC';
					$filter['level'][] = 'YEC';
				} else if ($level == "OL") {
					$filter['level'][] = "OL";
				} else if ($level == "DC") {
					if ($_round == "" || $_round == "MD") { // 选择了QF，SF，F时，不显示团体比赛记录
						$filter['level'][] = "DC";
						$filter['level'][] = "FC";
					}
				} else if ($level == "CH") {
					$filter['level'][] = "CH";
					$filter['level'][] = "125K";
					$filter['level'][] = "WTA125";
				} else if ($level == "ITF") {
					$filter['level'][] = "ITF";
					$filter['level'][] = "FU";
				} else if ($level == "tour") {
					$filter['level'][] = "XXI";
					$filter['level'][] = "ET";
					$filter['level'][] = "WTA";
					$filter['level'][] = "IN";
					$filter['level'][] = "GSC";
					$filter['level'][] = "ATP";
					$filter['level'][] = "WCT";
					if ($_round == "" || $_round == "MD") { // 选择了QF，SF，F时，不显示团体比赛记录
						$filter['level'][] = "AC";
						$filter['level'][] = "LC";
					}
				}
			}
			$filter['level'] = array_keys(array_flip($filter['level']));
		}

		$_eids = $req->input('eid', 'all');
		if (strpos($_eids, "all") === false && $_eids != "") { // eid列表不为空，且没有all，才添加条件
			$filter['eid'] = [];
			$eids = explode(',', $_eids);
			foreach ($eids as $eid) {
				if ($eid == "AO") {
					$filter['eid'][] = 'AO';
					$filter['eid'][] = '0901';
					$filter['eid'][] = '0580';
					$filter['eid'][] = 'M044';
				} else if ($eid == "RG") {
					$filter['eid'][] = 'RG';
					$filter['eid'][] = '0903';
					$filter['eid'][] = '0520';
					$filter['eid'][] = 'M041';
				} else if ($eid == 'WC') {
					$filter['eid'][] = 'WC';
					$filter['eid'][] = '0904';
					$filter['eid'][] = '0540';
					$filter['eid'][] = 'M042';
				} else if ($eid == 'UO') {
					$filter['eid'][] = 'UO';
					$filter['eid'][] = '0905';
					$filter['eid'][] = '0560';
					$filter['eid'][] = 'M043';
				} else if ($eid == 'YEC') {
					$filter['eid'][] = '0808';
					$filter['eid'][] = '0605';
					$filter['eid'][] = '0419';
				} else if ($eid == "OL") {
					$filter['eid'][] = "0650";
					$filter['eid'][] = "0096";
				}
			}
			$filter['eid'] = array_keys(array_flip($filter['eid']));
		}

		$_sfcs = $req->input('sfc', 'all');
		if (strpos($_sfcs, "all") === false && $_sfcs != "") { // 场地不为空，且没有all，才添加条件
			$filter['sfc'] = [];
			$sfcs = explode(',', $_sfcs);
			foreach ($sfcs as $sfc) {
				if ($sfc == "h") {
					$filter['sfc'][] = "Hard";
					$filter['sfc'][] = "Hard(I)";
				} else if ($sfc == 'c') {
					$filter['sfc'][] = "Clay";
					$filter['sfc'][] = "Clay(I)";
				} else if ($sfc == 'g') {
					$filter['sfc'][] = "Grass";
					$filter['sfc'][] = "Grass(I)";
				} else if ($sfc == 'p') {
					$filter['sfc'][] = "Carpet";
					$filter['sfc'][] = "Carpet(I)";
				}
			}
		}

		$files = join(' ', array_map(function ($d) use ($gender) {
			if (preg_match('/^([A-Z][A-Z0-9]{3}|[0-9]{5,6})$/', $d)) return join('/', [Config::get('const.root'), 'data', 'activity', $gender, $d]);
			else return "";
		}, $merge_arr));
		$files .= ' ' . join('/', [Config::get('const.root'), 'data', 'calc', $gender, $sd, 'year', 'unloaded']);
		$files .= ' ' . join('/', [Config::get('const.root'), 'data', 'calc', $gender, $sd, 'year', 'comingup']);

		$conditions_a = [];
		foreach ($filter as $col => $values) {
			$conditions_b = [];
			foreach ($values as $value) {
				$conditions_b[] = "$" . ($out_schema[$col] + 1) . "==\"" . $value . "\"";
			}
			$conditions_a[] = '(' . join("||", $conditions_b) . ')';
		}

		if ($_mode == 'p') { // p模式下，match列必须有对方id，首列要是己方id
			$conditions_b = [];
			foreach ($homes_arr as $pid) {
				$conditions_c = [];
				foreach ($aways_arr as $oppo) {
					$conditions_c[] = "$" . ($out_schema['matches'] + 1) . "~/!" . $oppo . "!/";
				}
				$conditions_b[] = "$" . ($out_schema['pid'] + 1) . "==\"" . $pid . "\"&&(" . join("||", $conditions_c) . ")";
			}
			foreach ($aways_arr as $pid) {
				$conditions_c = [];
				foreach ($homes_arr as $oppo) {
					$conditions_c[] = "$" . ($out_schema['matches'] + 1) . "~/!" . $oppo . "!/";
				}
				$conditions_b[] = "$" . ($out_schema['pid'] + 1) . "==\"" . $pid . "\"&&(" . join("||", $conditions_c) . ")";
			}
			$conditions_a[] = '(' . join("||", $conditions_b) . ')';

		} else { // c模式和t模式下，只需要首列是己方id

			$conditions_b = [];
			foreach ($homes_arr as $pid) {
				$conditions_b[] = "$" . ($out_schema['pid'] + 1) . "==\"" . $pid . "\"";
			}
			$conditions_a[] = '(' . join("||", $conditions_b) . ')';
		}

		// 是否为正赛，是否为决赛
		$_rounds = explode(",", $_round);
		$md = $onlyQF = $onlySF = $onlyF = false;
		if (in_array("MD", $_rounds)) {
			$md = true;
		}
		$conditions_b = [];
		if (in_array("QF", $_rounds)) {
			$conditions_b[] = '($' . ($out_schema['matches'] + 1) . "~/!QF!/" . ')';
			$onlyQF = true;
		} 
		if (in_array("SF", $_rounds)) {
			$conditions_b[] = '($' . ($out_schema['matches'] + 1) . "~/!SF!/" . ')';
			$onlySF = true;
		}
		if (in_array("F", $_rounds)) {
			$conditions_b[] = '($' . ($out_schema['matches'] + 1) . "~/!F!/" . ')';
			$onlyF = true;
		}
		if (count($conditions_b) > 0) {
			$conditions_a[] = '(' . join("||", $conditions_b) . ')';
		}

		$cmd = "awk -F\"\\t\" '" . join("&&", $conditions_a) . "' " . $files;
		unset($r); exec($cmd, $r);

		$ret = [];
		foreach ($r as $row) {
			$row_arr = explode("\t", $row);

			$date = $row_arr[$out_schema['start_date']];
			$level = $row_arr[$out_schema['level']];
			$sfc = $row_arr[$out_schema['sfc']];
			if (in_string($sfc, "(I)")) {
				$indoor = true;
				$sfc = str_replace("(I)", "", $sfc);
			} else {
				$indoor = false;
			}
			$sfc = strtolower($sfc);

			$city = $row_arr[$out_schema['city']];
			$year = $row_arr[$out_schema['year']];
			$eid = $row_arr[$out_schema['eid']];

			$pid = $row_arr[$out_schema['pid']];
			$prank = intval($row_arr[$out_schema['rank']]);
			if ($prank == 0 || $prank == 9999) $prank = null;
			$pname = translate2short($pid);

			if ($sd == "d") {
				$pid2 = $row_arr[$out_schema['partner_id']]; // 外层能拿到搭档
				if ($pid2) {
					$pname2 = translate2short($pid2);
				} else {
					$pname2 = "";
				}
			}

			$matches = explode("@", $row_arr[$out_schema['matches']]);
			foreach ($matches as $match) {
				if ($sd == 'd' && !$pid2) { // 如果双打比赛外层没有拿到搭档，则记下来。搭档在后面的数据里
					$noPid2 = true;
				} else {
					$noPid2 = false;
				}
				$match_arr = explode("!", $match);
				$wl = $match_arr[$in_schema['wl'] + 1];
				$round = $match_arr[$in_schema['round'] + 1];
				if ($onlyF && $round != "F") continue;
				if ($onlySF && $round != "SF") continue;
				if ($onlyQF && $round != "QF") continue;
				if ($md && preg_match('/^Q[0-9]/', $round)) continue;
				$games = $match_arr[$in_schema['games'] + 1];
				if ($games == "UNP") continue; // UNP表示没打，不计入
				if ($games == "" && $wl == "L" && $gender == "wta") continue; // 分数为空，L的比赛不计。有可能是W/O，也有可能是没打
				if (!$games || $games == "-") $games = "W/O";
				$oid = $match_arr[$in_schema['oid'] + 1];
				$orank = intval($match_arr[$in_schema['orank'] + 1]);
				if ($orank == 0 || $orank == 9999) $orank = null;
				$oname = translate2short($oid);

				if ($_mode == 'c') { // c模式下要求对方ioc至少有一个符合country
					$oioc = $match_arr[$in_schema['oioc'] + 1];
					$opartner_ioc = $match_arr[$in_schema['opartner_ioc'] + 1];
					if (!in_array($oioc, $aways_arr) && !in_array($opartner_ioc, $aways_arr)) continue;
				}
				if ($_mode == 't') { // t模式下必须有排名并且<=输入
					$orank = $match_arr[$in_schema['orank'] + 1];
					if (!$orank || $orank > $aways_arr[0]) continue;
				}

				if ($sd == "d") {
					$oid2 = $match_arr[$in_schema['opartner_id'] + 1];
					$oname2 = translate2short($oid2);

					if ($noPid2) {
						$pid2 = $match_arr[$in_schema['partner_id'] + 1];
						if ($pid2) {
							$pname2 = translate2short($pid2);
						} else {
							$pname2 = "";
						}
					}
				}

				if ($wl == "W" || $wl == "") {
					$p1 = [[$pid, $pname, $prank]];
					if ($sd == "d") $p1[] = [$pid2, $pname2];
					$p2 = [[$oid, $oname, $orank]];
					if ($sd == "d") $p2[] = [$oid2, $oname2];
				} else if ($wl == "L") {
					$p1 = [[$oid, $oname, $orank]];
					if ($sd == "d") $p1[] = [$oid2, $oname2];
					$p2 = [[$pid, $pname, $prank]];
					if ($sd == "d") $p2[] = [$pid2, $pname2];
				}
				
				if ($noPid2) { // 如果外层没有双打搭档，则本场p1,p2已经找好之后就清空pid2，以免影响下一场比赛
					$pid2 = $pname2 = "";
				}

				if ($_mode == 'p') { // p模式下，筛选满足人的条件的比赛，并算得哪方获胜
					if ($sd == "s") {
						if (in_array($p1[0][0], $homes_arr) && in_array($p2[0][0], $aways_arr)) { // 胜者在前，败者在后
							$wintag = 1;
						} else if (in_array($p2[0][0], $homes_arr) && in_array($p1[0][0], $aways_arr)) { // 胜者在后，败者在前
							$wintag = 2;
						} else {
							continue; // 不满足
						}
					} else {
						if (count($homes_arr) == 1 && count($aways_arr) == 1) { // 1V1，一方能匹配上一个就行
							if (
								(in_array($p1[0][0], $homes_arr) || in_array($p1[1][0], $homes_arr))
								&& (in_array($p2[0][0], $aways_arr) || in_array($p2[1][0], $aways_arr))
							) {
								$wintag = 1;
							} else if (
								(in_array($p2[0][0], $homes_arr) || in_array($p2[1][0], $homes_arr))
								&& (in_array($p1[0][0], $aways_arr) || in_array($p1[1][0], $aways_arr))
							) {
								$wintag = 2;
							} else {
								continue;
							}
						} else if (count($homes_arr) == 2 && count($aways_arr) == 2) { // 2V2，需要双方完全匹配
							if (
								(in_array($p1[0][0], $homes_arr) && in_array($p1[1][0], $homes_arr))
								&& (in_array($p2[0][0], $aways_arr) && in_array($p2[1][0], $aways_arr))
							) {
								$wintag = 1;
							} else if (
								(in_array($p2[0][0], $homes_arr) && in_array($p2[1][0], $homes_arr))
								&& (in_array($p1[0][0], $aways_arr) && in_array($p1[1][0], $aways_arr))
							) {
								$wintag = 2;
							} else {
								continue;
							}
						}
					}
				} else if ($_mode == 'c' || $_mode == 't') {
					if ($wl == "W") $wintag = 1;
					else if ($wl == "L") $wintag = 2;
					else $wintag = 0;
				}

				if ($games == "W/O") $wintag = 0; // W/O的时候wintag为0
				$gamesArr = [];
				$game = $games;
				if ($game != "W/O") {
					if (in_string($game, "Ret") || in_string($game, "Def")) {
						$game = preg_replace("/ [RrDd].*$/", "", $game);
					}
					$gameA = explode(" ", $game);
					foreach ($gameA as $_set) {
						$_setA = explode("-", $_set);
						if (count($_setA) != 2) continue;
						unset($m1); unset($m2);
						preg_match("/^(\d+)$/", $_setA[0], $m1);
						preg_match("/^(\d+)(\((\d+)\))?$/", $_setA[1], $m2);
						$s1 = [intval($m1[1]), null, 0];
						$s2 = [intval($m2[1]), null, 0];
						if ($m1[1] > $m2[1]) {
							$s1[2] = 1;
							if (isset($m2[3])) $s2[1] = $m2[3];
						} else if ($m1[1] < $m2[1]) {
							$s2[2] = 1;
							if (isset($m2[3])) $s1[1] = $m2[3];
						}
						$gamesArr[] = [
							$s1,
							$s2,
						];
					}
				}
				
				$round_num = sprintf("%02d", Config::get('const.round2id.' . $round)); // 把round转成数字，排序用

				$allp = array_map(function ($d) {return $d[0];}, array_merge($p1, $p2)); // 把所有选手id排个序，去重用
				sort($allp);

				$ret[join("\t", [$date, $eid, $round_num, join("/", $allp)])] = [ // 日期、轮次、所有选手排序来去重
					'date' => $date,
					'year' => $year,
					'level' => $level,
					'levelIcon' => Config::get('const.levelIcon.' . $level),
					'sfc' => $sfc,
					'indoor' => $indoor,
					'city' => translate_tour($city, $level),
					'round' => $round,
					'win' => $p1,
					'loss' => $p2,
					'games' => $games,
					'winside' => $wintag,
					'gamesArr' => $gamesArr,
				];
			}
		}

		krsort($ret);

		// 算胜负场
		$win = $loss = 0;
		foreach ($ret as $match) {
			$wintag = $match['winside'];
			if ($wintag == 1) ++$win;
			else if ($wintag == 2) ++$loss;
		}

		if ($win == 0 && $loss == 0) {
			$win_pct = $loss_pct = 50;
		} else {
			$win_pct = round($win / ($win + $loss) * 100);
			$loss_pct = round($loss / ($win + $loss) * 100);
		}
		if ($win_pct + $loss_pct > 100) {
			if ($win_pct > $loss_pct) {
				$win_pct -= $win_pct + $loss_pct - 100;
			} else {
				$loss_pct -= $win_pct + $loss_pct - 100;
			}
		}

		$players = self::get_player_info($merge_arr);
		
		return json_encode([
			'status' => 0,
			'home' => $win,
			'away' => $loss,
			'home_pct' => $win_pct,
			'away_pct' => $loss_pct,
			'players' => $players,
			'matches' => array_values($ret)
		]);
	}

	private function get_player_info($merge_arr) {
		$lang = App::getLocale();
		$ret = [];
		foreach ($merge_arr as $pid) {
			if (preg_match('/^[A-Z0-9]{4}$/', $pid)) {
				$gender = 'atp';
			} else if (preg_match('/^[0-9]{5,6}$/', $pid)) {
				$gender = 'wta';
			} else {
				$gender = 'itf';
			}
			$key = join('_', [$gender, 'profile', $pid]);
			$res = Redis::hmget($key, 'l_' . $lang, 's_' . $lang, 'l_en', 's_en', 'first', 'last', 'ioc');

			$res1 = fetch_portrait($pid, $gender);
			$res2 = fetch_headshot($pid, $gender);
			$res3 = fetch_rank($pid, $gender);

			$ret[$pid] = [
				'id' => $pid,
				'pid' => $pid,
				'name' => $res[2],
				'shortname' => $res[3],
				'long' => $res[0] ? $res[0] : $res[2],
				'short' => $res[1] ? $res[1] : $res[3],
				'first' => $res[4],
				'last' => $res[5],
				'ioc' => $res[6],
				'pt' => $res1[1],
				'hs' => $res2[1],
				'has_pt' => $res1[0],
				'has_hs' => $res2[0],
				'rank' => $res3,
			];
		}
		return $ret;
	}
}
