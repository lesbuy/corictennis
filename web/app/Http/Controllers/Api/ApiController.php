<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Config;
use App;
use DB;
use	App\Http\Controllers\Stat\StatController;
use	App\Http\Controllers\Stat\PbPController;
use	App\Http\Controllers\H2H\H2HController;

class ApiController extends Controller
{
    //

	public function ByMatchid(Request $req) {

		App::setLocale('zh');
		$bets_id = resetParam($req->input('match_id'), 0);
		$date = resetParam($req->input('day'), date('Ymd', time()));
		$token = resetParam($req->input('token'), null);
		if (!$token) return ['status' => -3, 'errmsg' => 'Permission Denied', 'result' => []];

		$key = array_search('matchid_bets', Config::get('const.schema_completed')) + 1;

		$cmd = "awk -F\"\\t\" '$$key == $bets_id' " . join('/', [env('ROOT'), 'share', '*completed', date('Y-m-d', strtotime($date))]);
		unset($r); exec($cmd, $r);

		if (!$r) {
			$cmd = "awk -F\"\\t\" '$$key == $bets_id' " . join('/', [env('ROOT'), 'share', '*completed', date('Y-m-d', strtotime($date) - 86400)]);
			unset($r); exec($cmd, $r);
		}
		if (!$r) {
			$cmd = "awk -F\"\\t\" '$$key == $bets_id' " . join('/', [env('ROOT'), 'share', '*completed', date('Y-m-d', strtotime($date) + 86400)]);
			unset($r); exec($cmd, $r);
		}

		if (!$r) return ['status' => 0, 'errmsg' => 'No Result', 'result' => []];
		$line = $r[0];
		$arr = explode("\t", $line);
		$kvmap = [];
		foreach ($arr as $k => $v) {
			$kvmap[Config::get('const.schema_completed.'.$k)] = $v;
		}
		$p1id = $kvmap['p1id'];
		$p2id = $kvmap['p2id'];

		$year = $kvmap['year'];
		$eid = $kvmap['eid'];
		$matchid = $kvmap['matchid'];
		$fsid = $kvmap['fsid'];

		if (in_array($kvmap['sexid'], [0, 2])) {
			$type = 'atp';
		} else if (in_array($kvmap['sexid'], [1, 3])) {
			$type = 'wta';
		} else {
			$type = '';
		}

		if (strpos($kvmap['p1last'], '/') !== false || strpos($kvmap['p2last'], '/') !== false || strpos($kvmap['p1ioc'], '/') !== false || strpos($kvmap['p2ioc'], '/') !== false) {
			$is_double = true;
		} else {
			$is_double = false;
		}

		$score = [['', '' ,'' ,'' ,'', ''], ['', '' ,'' ,'' ,'', '']];
		$ori_score = json_decode($kvmap['score'], true);
		for ($i = 0; $i < 2; ++$i) {
			for ($j = 0; $j < 6; ++$j) {
				$score[$i][$j] = str_replace('&#xe60b;', 'WINNER', str_replace("</sup>", ")", str_replace("<sup>", "(", preg_replace('/<\/?span[^>]*>/', '', @$ori_score[$i][$j]))));
			}
		}

		$ret = [
			'status' => 0, 
			'match_info' => [
				'tour_name' => $kvmap['tour'],
				'tour_city' => $kvmap['city'],
				'surface' => $kvmap['surface'],
				'level' => $kvmap['level'],
				'eventid' => $kvmap['eid'],
				'courtname' => $kvmap['courtname'],
				'courtname_chn' => translate('courtname', str_replace('.', '', strtolower($kvmap['courtname'])), true),
				'matchid' => $kvmap['matchid'],
				'round' => $kvmap['round'],
				'time_cost' => $kvmap['dura'],
				'score' => $score,
				'match_status' => $kvmap['mstatus'],
			],
			'pid' => [
				'home' => $p1id,
				'away' => $p2id,
			],
			'seed' => [
				$kvmap['p1seed'],
				$kvmap['p2seed'],
			],
			'result' => [],
			'persons' => [],
		];

		$all_pids = [$p1id, $p2id];

		$this->process_stat($ret['result'], $year, $eid, $matchid, $p1id, $p2id);
		$this->process_pbp($ret['result'], $year, $eid, $matchid, $p1id, $p2id, $fsid);
		if ($type) $this->process_h2h($ret['result'], $p1id, $p2id, $type);
		$this->process_recent($ret['result'], 'home', $p1id, $type, $is_double, 10, $all_pids);
		$this->process_recent($ret['result'], 'away', $p2id, $type, $is_double, 10, $all_pids);
		$this->process_info($ret['persons'], $type, $all_pids);

		return json_encode($ret);
	}

	protected function process_info(&$ret, $type, $all_pids) {

		$all_pids = array_unique(explode('/', join('/', $all_pids)));
		$rows = DB::table('profile_' . $type)->whereIn('longid', $all_pids)->get();
		foreach ($rows as $row) {
			$ret[$row->longid] = [
				'firstname' => $row->first_name,
				'lastname' => $row->last_name,
				'ioc' => $row->nation3,
				'chn_name_long' => rename2long($row->first_name, $row->last_name, $row->nation3),
				'chn_name_short' => rename2short($row->first_name, $row->last_name, $row->nation3),
				'chn_ioc' => translate('nationname', $row->nation3),
				'birthday' => $row->birthday,
				'height' => $row->height,
				'weight' => $row->weight,
				'prize_career' => $row->prize_c,
				'prize_ytd' => $row->prize_y,
				'rank_single' => $row->rank_s,
				'rank_double' => $row->rank_d,
				'career_high_single' => $row->rank_s_hi,
				'career_high_double' => $row->rank_d_hi,
				'title_single' => $row->title_s_c,
				'title_double' => $row->title_d_c,
			];
		}
	}

	protected function process_stat(&$ret, $year, $eid, $matchid, $p1id, $p2id) {

		$stat_req = new Request;
		$stat_req->merge([
			'id1' => $p1id,
			'id2' => $p2id,
			'p1' => '',
			'p2' => '',
			'eid' => $eid,
			'matchid' => $matchid,
			'year' => $year,
			'ajax' => true,
		]);

		$sc = new StatController;
		$stat_resp = $sc->query($stat_req, 'zh');

		if ($stat_resp['status'] == 0 && isset($stat_resp['stat'])) {
			$ret['stat'] = $stat_resp['stat'];
		}
	}

	protected function process_pbp(&$ret, $year, $eid, $matchid, $p1id, $p2id, $fsid) {

		$pbp_req = new Request;
		$pbp_req->merge([
			'id1' => $p1id,
			'id2' => $p2id,
			'p1' => '',
			'p2' => '',
			'eid' => $eid,
			'matchid' => $matchid,
			'year' => $year,
			'fsid' => $fsid,
			'ajax' => true,
		]);

		$pc = new PbPController;
		$pbp_resp = $pc->query($pbp_req, 'zh');

		if ($pbp_resp['status'] == 0 && isset($pbp_resp['pbp'])) {
			$ret['pbp'] = $pbp_resp['pbp'];
		}
		if ($pbp_resp['status'] == 0 && isset($pbp_resp['serve'])) {
			$ret['serve'] = $pbp_resp['serve'];
		}

//		echo json_encode($pbp_resp);
	}

	protected function process_h2h(&$ret, $p1id, $p2id, $type) {

		$h2h_req = new Request;
		$h2h_req->merge([
			'p1id' => $p1id,
			'p2id' => $p2id,
			'status' => 'ok',
			'method' => 'p',
			'type' => $type,
			'ajax' => true,
		]);

		$hc = new H2HController;
		$h2h_resp = $hc->query($h2h_req, 'zh');

		if ($h2h_resp['status'] == 0 && isset($h2h_resp['matches'])) {
			$ret['h2h_detail'] = $h2h_resp['matches'];
		}

		if ($h2h_resp['status'] == 0) {
			$ret['h2h'] = $h2h_resp['win'] . ':' . $h2h_resp['lose'];
		}
//		echo json_encode($h2h_resp);
	}

	protected function process_recent(&$ret, $idx, $pid, $gender, $is_double, $match_num = 10, &$all_pids) {

		if ($is_double) {
			if (strpos($pid, '/') === false) return;
			$arr = explode("/", $pid);
			$pid1 = $arr[0];
			$pid2 = $arr[1];
			$sd = 'D';
		} else {
			$pid1 = $pid;
			$pid2 = "";
			$sd = 'S';
		}

		$cmd = "grep \"^$pid1	\" " . join("/", [Config::get('const.root'), $gender, 'points_' . strtolower($sd) . '_[lt]*']) . " | awk -F\"\\t\" '$19 == \"$pid2\"'";
		unset($r); exec($cmd, $r);

		$matches = [];
		foreach ($r as $row) {
			$arr = explode("\t", $row);
			$date = $arr[2];
			$year = $arr[3];
			$eid = $arr[4];
			$level = $arr[5];
			$city = translate_tour($arr[11]);
			$sfc = $arr[13];

			if (!$is_double) {
				$cmd = "grep \"	$gender$pid1		\" " . join("/", [Config::get('const.root'), 'store', 'draw', $year, $eid]);
			} else {
				$cmd = "grep -E \"	($gender$pid1	$gender$pid2|$gender$pid2	$gender$pid1)	\" " . join("/", [Config::get('const.root'), 'store', 'draw', $year, $eid]);
			}
			unset($r1); exec($cmd, $r1);
			foreach ($r1 as $row1) {
				$arr1 = explode("\t", $row1);
				if (strpos($row1, "BYE") !== false) continue;
				if (isset($kvmap)) {unset($kvmap); $kvmap = [];}
				foreach (Config::get('const.schema_drawsheet') as $k => $v) {$kvmap[$v] = @$arr1[$k];}
				if (substr($kvmap['sextip'], 1, 1) != $sd) continue;
				$t = substr($kvmap['sextip'], 0, 1);
				if ($gender == "wta" && $t != "P" && $t != "W") continue;
				if ($gender == "atp" && $t != "Q" && $t != "M") continue;

				$pos = 0; // pos记录这个人是在home还是away
				
				if ($gender . $pid1 == $kvmap['P1A'] || $gender . $pid1 == $kvmap['P1B']) {
					$pos = 1;
					$oppo = [$kvmap['Seed2'], []];
					$oppo[1][] = get_ori_id($kvmap['P2A']);
					$all_pids[] = get_ori_id($kvmap['P2A']);
					if ($sd == "D") {
						$oppo[1][] = get_ori_id($kvmap['P2B']);
						$all_pids[] = get_ori_id($kvmap['P2B']);
					}
					$me = [$kvmap['Seed1'], []];
					$me[1][] = get_ori_id($kvmap['P1A']);
					if ($sd == "D") {
						$me[1][] = get_ori_id($kvmap['P1B']);
					}
				} else if ($gender . $pid1 == $kvmap['P2A'] || $gender . $pid1 == $kvmap['P2B']) {
					$pos = 2;
					$oppo = [$kvmap['Seed1'], []];
					$oppo[1][] = get_ori_id($kvmap['P1A']);
					$all_pids[] = get_ori_id($kvmap['P1A']);
					if ($sd == "D") {
						$oppo[1][] = get_ori_id($kvmap['P1B']);
						$all_pids[] = get_ori_id($kvmap['P1B']);
					}
					$me = [$kvmap['Seed2'], []];
					$me[1][] = get_ori_id($kvmap['P2A']);
					if ($sd == "D") {
						$me[1][] = get_ori_id($kvmap['P2B']);
					}
				}

				$wltag = "";
				if ($pos == 1 && in_array($kvmap['mStatus'], ['F', 'H', 'J', 'L'])) $wltag = "W";
				else if ($pos == 2 && in_array($kvmap['mStatus'], ['F', 'H', 'J', 'L'])) $wltag = "L";
				else if ($pos == 1 && in_array($kvmap['mStatus'], ['G', 'I', 'K', 'M'])) $wltag = "L";
				else if ($pos == 2 && in_array($kvmap['mStatus'], ['G', 'I', 'K', 'M'])) $wltag = "W";

				if ($wltag == "") {
					$score = "";
				} else {
					$score = revise_gs_score($kvmap['mStatus'], $kvmap['score1'], $kvmap['score2']);
				}

				$round = $kvmap['round'];
				$roundid = Config::get('const.round2id')[$round];

				$matches[] = [$date, $city, $roundid, $level, $round, $me, $oppo, $score, $wltag];
			}
		}

		$cmd = "awk -F\"\\t\" '$19 != 100 && $11 == \"$sd\" && $16 == \"$pid2\" && $22 != \"0\"' " . join("/", [Config::get('const.root'), 'store', 'activity', $gender, $pid1]) . " | sort -t\"	\" -k4gr,4 | head -" . $match_num;
		unset($r); exec($cmd, $r);
		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				if (isset($kvmap)) {unset($kvmap); $kvmap = [];}
				foreach (Config::get('const.schema_activity_match') as $k => $v) {
					$kvmap[$v] = @$arr[$k];
				}
				$me = [$kvmap['seed'], [$kvmap['id']]];
				if ($sd == "D") {
					$me[1][] = $kvmap['partnerid'];
				}
				$oppo = [$kvmap['opposeed'], []];
				if ($sd == "S") {
					$oppo[1][] = $kvmap['oppoid'];
					$all_pids[] = $kvmap['oppoid'];
				} else {
					$ar1 = explode("/", $kvmap['oppoid']);
					$ar2 = explode("/", $kvmap['opponation']);
					$oppo[1][] = $ar1[0];
					$oppo[1][] = @$ar1[1];
					$all_pids[] = $ar1[0];
					$all_pids[] = @$ar1[1];
				}
				$date = $kvmap['time'];
				$year = $kvmap['year'];
				$eid = $kvmap['tourid'];
				$city = translate_tour($kvmap['tourname']);
				$level = $kvmap['level'];
				$sfc = $kvmap['ground'];

				$wltag = substr($kvmap['winorlose'], 0, 1);
				$score = $kvmap['games'];
				if ($score == "-") $score = "W/O";
				$round = $kvmap['round'];
				$roundid = Config::get('const.round2id')[$round];

				$matches[] = [$date, $city, $roundid, $level, $round, $me, $oppo, $score, $wltag];
			}
		}

		usort($matches, 'self::match_sort');
		if ($sd == "S")	$matches = array_slice($matches, 0, $match_num);
		else $matches = array_slice($matches, 0, $match_num);

		$ret['recent'][$idx] = $matches;
	}

	private function match_sort($a, $b) {
		if ($a[0] > $b[0]) return -1;
		else if ($a[0] < $b[0]) return 1;
		else if ($a[1] != $b[1]) return strcmp($a[1], $b[1]);
		else if ($a[2] < $b[2]) return 1;
		else if ($a[2] > $b[2]) return -1;
		else return 0;
	}

	public function PbpByMatchid(Request $req) {

		App::setLocale('zh');
		$bets_id = resetParam($req->input('match_id'), 0);
		$date = resetParam($req->input('day'), date('Ymd', time()));
		$token = resetParam($req->input('token'), null);
		if (!$token) return ['status' => -3, 'errmsg' => 'Permission Denied', 'result' => []];

		$key = array_search('matchid_bets', Config::get('const.schema_completed')) + 1;

		$cmd = "awk -F\"\\t\" '$$key == $bets_id' " . join('/', [env('ROOT'), 'share', '*completed', date('Y-m-d', strtotime($date))]);
		unset($r); exec($cmd, $r);

		if (!$r) return ['status' => 0, 'errmsg' => 'No Result', 'result' => []];
		$line = $r[0];
		$arr = explode("\t", $line);
		$kvmap = [];
		foreach ($arr as $k => $v) {
			$kvmap[Config::get('const.schema_completed.'.$k)] = $v;
		}
		$p1id = $kvmap['p1id'];
		$p2id = $kvmap['p2id'];

		$year = $kvmap['year'];
		$eid = $kvmap['eid'];
		$matchid = $kvmap['matchid'];
		$fsid = $kvmap['fsid'];

		if (in_array($kvmap['sexid'], [0, 2])) {
			$type = 'atp';
		} else if (in_array($kvmap['sexid'], [1, 3])) {
			$type = 'wta';
		} else {
			$type = '';
		}

		if (strpos($kvmap['p1last'], '/') !== false || strpos($kvmap['p2last'], '/') !== false || strpos($kvmap['p1ioc'], '/') !== false || strpos($kvmap['p2ioc'], '/') !== false) {
			$is_double = true;
		} else {
			$is_double = false;
		}

		$score = [['', '' ,'' ,'' ,'', ''], ['', '' ,'' ,'' ,'', '']];
		$ori_score = json_decode($kvmap['score'], true);
		for ($i = 0; $i < 2; ++$i) {
			for ($j = 0; $j < 6; ++$j) {
				$score[$i][$j] = str_replace('&#xe60b;', 'WINNER', str_replace("</sup>", ")", str_replace("<sup>", "(", preg_replace('/<\/?span[^>]*>/', '', @$ori_score[$i][$j]))));
			}
		}

		$ret = [
			'status' => 0, 
			'match_info' => [
				'tour_name' => $kvmap['tour'],
				'tour_city' => $kvmap['city'],
				'surface' => $kvmap['surface'],
				'level' => $kvmap['level'],
				'eventid' => $kvmap['eid'],
				'courtname' => $kvmap['courtname'],
				'courtname_chn' => translate('courtname', str_replace('.', '', strtolower($kvmap['courtname'])), true),
				'matchid' => $kvmap['matchid'],
				'round' => $kvmap['round'],
				'time_cost' => $kvmap['dura'],
				'score' => $score,
				'match_status' => $kvmap['mstatus'],
			],
			'pid' => [
				'home' => $p1id,
				'away' => $p2id,
			],
			'seed' => [
				$kvmap['p1seed'],
				$kvmap['p2seed'],
			],
			'result' => [],
			'persons' => [],
		];

		$all_pids = [$p1id, $p2id];

		$this->process_pbp($ret['result'], $year, $eid, $matchid, $p1id, $p2id, $fsid);
		$this->process_info($ret['persons'], $type, $all_pids);

		return json_encode($ret);
	}

}
