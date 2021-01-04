<?php

namespace App\Http\Controllers\Stat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App;
use Config;

class StatController extends Controller
{

	protected $id1;
	protected $id2;
	protected $p1;
	protected $p2;
	protected $eid;
	protected $matchid;
	protected $year;

	protected $mile_convert;
	protected $feet_convert;

	public function __construct() {

		$this->mile_convert = 1.609344;
		$this->feet_convert = 0.3048;

	}

	public function query(Request $req, $lang) {

		App::setLocale($lang);

		$ret = [];

		$this->id1 = $req->input('id1', 'CG80');
		$this->id2 = $req->input('id2', 'N409');
		$this->p1 = urldecode($req->input('p1', 'Coric'));
		$this->p2 = urldecode($req->input('p2', 'Nadal'));
		$this->eid = $req->input('eid', 'M993');
		$this->matchid = $req->input('matchid', 'MS001');
		$this->year = $req->input('year', '2017');

		$ajax = $req->input('ajax', false);

		// 处理数据统计

		if ($this->eid == 'DC' || $this->eid == 'FC') {

//			$ret = self::process_davis_fed_cup();
			$ret = self::process_itf_event();

//		} else if (in_array($this->eid, ['UO'])) {
//		if (in_array($this->eid, ['RG', 'WC', 'UO'])) {

//			$ret = self::process_grand_slam();

		} else if (in_array($this->eid, ['AO'])) {

			$ret = self::process_ao();

		} else if (in_array($this->eid, ['RG'])) {

			$ret = self::process_rg();

		} else if (in_array($this->eid, ['WC', 'UO'])) {

			$ret = self::process_wc();

		} else if (preg_match('/^[A-Z]{2}[0-9]{3}$/', $this->matchid)){

			//$ret = self::process_atp_wta_tour();
			$e = substr($this->matchid, 0, 2);
			if (in_array($e, ["LS", "LD", "RS", "RD"])) {
				$ret = self::process_wta_tour();
			} else {
				$ret = self::process_atp_tour();
			}

		} else {

			$ret = self::process_itf_event();

		}

		// 处理头像

		$ret['head'] = [];

		$join_id = explode('/', join('/', [$this->id1, $this->id2]));

		foreach ($join_id as $id) {

			if (preg_match('/^[A-Z][A-Z0-9]{3}$/', $id)) {
				$type = "atp";
			} else if (preg_match('/^[0-9]{5,6}$/', $id)) {
				$type = "wta";
			} else {
				$type = "atp";
			}

			$cmd = "grep '^$id\t' " . join('/', [Config::get('const.root'), $type, "player_headshot"]) . " | cut -f3";
			unset($r); exec($cmd, $r);
			if ($r && isset($r[0])) {
				if (strpos($r[0], "http") === 0) {
					$ret['head'][] = $r[0];
				} else {
					$ret['head'][] = url(env('CDN') . '/images/' . $type . '_headshot/' . preg_replace('/^.*\//', '', $r[0]));
				}
			} else {
				$ret['head'][] = url(env('CDN') . '/images/' . $type . '_headshot/' . $type . 'player.jpg');
			}

		}

		$ret['player'] = [$this->p1, $this->p2];

//		return json_encode($ret);
		if ($ajax) {
			return $ret;
		} else {
			return view('stat.stat', ['ret' => $ret]);
		}

	}

	protected function process_davis_fed_cup() {

		if (substr($this->matchid, 0, 1) == "M"){
			$cup = "daviscup";
		} else {
			$cup = "fedcup";
		}

		$url = "http://$cup.tennis-live-scores.com/data/$cup/$this->matchid.json";

		$html = file_get_contents($url);
		$json = json_decode($html, true);

		// 总盘数
		$bestof = @$json["BestOfSets"] + 0;

		// 胜负
		$winner = @$json["WinningSide"] + 0;
		if ($winner == 1) $wl = ["winner", "loser"];
		else if ($winner == 2) $wl = ["loser", "winner"];
		else $wl = ["unfinished", "unfinished"];

		// 每盘比分
		$score = [];
		for ($i = 0; $i < 5; ++$i){
			if (isset($json["Sets"][$i])){
				$a = $json["Sets"][$i]["Side1Score"];
				$b = $json["Sets"][$i]["Side2Score"];

				if (isset($json["Sets"][$i + 1]) && $i < 5) {
					if ($a > $b) {$c = 'SetWinner'; $d = 'SetLoser';}
					else {$c = 'SetLoser'; $d = 'SetWinner';};
				} else {
					if ($winner > 0) {
						if ($winner == 1) {$c = 'SetWinner'; $d = 'SetLoser';}
						else {$c = 'SetLoser'; $d = 'SetWinner';};
					} else {
						$c = $d = '';
					}
				}

				if (isset($json["Sets"][$i]["Side1TieBreakScore"], $json["Sets"][$i]["Side2TieBreakScore"])){
					if ($json["Sets"][$i]["Side1TieBreakScore"] > $json["Sets"][$i]["Side2TieBreakScore"]){
						$b .= "<sup>" . $json["Sets"][$i]["Side2TieBreakScore"] . "</sup>";
					} else {
						$a .= "<sup>" . $json["Sets"][$i]["Side1TieBreakScore"] . "</sup>";
					}
				}
			} else {
				$a = $b = "&nbsp;";
				$c = $d = '';
			}
			$score[] = [$a, $b, $c, $d];
		}

		// 统计参数
		$stat = [];

		for ($j = -1; $j < 5; ++$j){

			if ($j == -1) {
				if (!isset($json["MatchStatistics"])) continue;
				$v = $json["MatchStatistics"];
				$set = 0;
			} else {
				if (!isset($json["Sets"]) || !isset($json["Sets"][$j]) || !isset($json["Sets"][$j]["SetStatistics"])) continue;
				$v = $json["Sets"][$j]["SetStatistics"];
				$set = $j + 1;
			}

			$stat[$set] = [];

			for ($seq = 0; $seq < 2; ++$seq){

				$i = $seq + 1;
				$ace = @$v["Side".$i."AceCount"] + 0;
				$df = @$v["Side".$i."DoubleFaultCount"] + 0;
				$faqiu = @$v["Side".$i."FirstServeCount"] + 0;
				$yifachenggong = @$v["Side".$i."FirstServeInCount"] + 0;
				$yifadefen = @$v["Side".$i."FirstServeInWonCount"] + 0;
				$erfa = @$v["Side".$i."SecondServeCount"] + 0;
				$erfadefen = @$v["Side".$i."SecondServeInWonCount"] + 0;
				$s1_percent = self::add_percentage($yifachenggong."/".$faqiu);
				$s1 = self::add_percentage($yifadefen."/".$yifachenggong);
				$s2 = self::add_percentage($erfadefen."/".$erfa);
				$bp = @$v["Side".$i."BreakPointsWonCount"] + 0;
				$bpjihui = @$v["Side".$i."BreakPointsCount"] + 0;
				$bp_percent = self::add_percentage($bp."/".$bpjihui);
				$wi = @$v["Side".$i."TotalWinners"] + 0;
				$ue = @$v["Side".$i."UnforcedErrors"] + 0;
				$fe = @$v["Side".$i."ForcedErrors"] + 0;
				$np = @$v["Side".$i."NetPointsWon"] + 0;
				$npjihui = @$v["Side".$i."NetPointsTotal"] + 0;
				$np_percent = self::add_percentage($np."/".$npjihui);
				$tp = @$v["Side".$i."TotalPointsWonCount"] + 0;

				$f1fkph = max(@$v["Side".$i."Player1Fastest1stServeKPH"] + 0, @$v["Side".$i."Player2Fastest1stServeKPH"] + 0);
				$f2fkph = max(@$v["Side".$i."Player1Fastest2ndServeKPH"] + 0, @$v["Side".$i."Player2Fastest2ndServeKPH"] + 0);
				$f1akph = max(@$v["Side".$i."Player1Average1stServeKPH"] + 0, @$v["Side".$i."Player2Average1stServeKPH"] + 0);
				$f2akph = max(@$v["Side".$i."Player1Average2ndServeKPH"] + 0, @$v["Side".$i."Player2Average2ndServeKPH"] + 0);

				$f1fmph = max(@$v["Side".$i."Player1Fastest1stServeMPH"] + 0, @$v["Side".$i."Player2Fastest1stServeMPH"] + 0);
				$f2fmph = max(@$v["Side".$i."Player1Fastest2ndServeMPH"] + 0, @$v["Side".$i."Player2Fastest2ndServeMPH"] + 0);
				$f1amph = max(@$v["Side".$i."Player1Average1stServeMPH"] + 0, @$v["Side".$i."Player2Average1stServeMPH"] + 0);
				$f2amph = max(@$v["Side".$i."Player1Average2ndServeMPH"] + 0, @$v["Side".$i."Player2Average2ndServeMPH"] + 0);

				$hour = @$v["DurationInHours"];
				$minute = @$v["DurationInMins"];
				$dura = date('H:i:s', strtotime("$hour:$minute:00"));

				$stat[$set][] = [
					'dura' => $dura,
					'ace' => $ace,
					'df' => $df,
					's1%' => $s1_percent,
					's1' => $s1,
					's2' => $s2,
					'wi' => $wi,
					'ue' => $ue,
					'fe' => $fe,
					'bp%' => $bp_percent,
					'np%' => $np_percent,
					'tp' => $tp,
					'f1f' => [$f1fkph, $f1fmph],
					'f1a' => [$f1akph, $f1amph],
					'f2f' => [$f2fkph, $f2fmph],
					'f2a' => [$f2akph, $f2amph],
				];
			}
		}

		$ratio = self::convertToRatio($stat);

		return [
			'status' => 0,
			'stat' => $stat,
			'ratio' => $ratio,
			'score' => $score,
			'wl' => $wl,
			'bestof' => $bestof,
		];
	}

	protected function process_rg() {

		if ($this->eid == "RG"){
			$prefix = "https://www.rolandgarros.com/api/en-us/matches/";
		}

		$mid = preg_replace('/^.*\//', '', $this->matchid);
		$use_official = true;

		if ($use_official) {
			$ch = curl_init();
			//设置选项，包括URL
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_HEADER, 0); 
			$url = $prefix . $mid;
			curl_setopt($ch, CURLOPT_URL, $url);
			$html = curl_exec($ch);
			$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($response_code > 400) return ['status' => -1, 'errmsg' => __('stat.notice.error')];
	
			$json = json_decode($html, true);
			if (!$json) return ['status' => -1, 'errmsg' => __('stat.notice.error')];

		}

		// 总盘数
		$bestof = 3;
		if (substr($this->matchid, 0, 2) == 'MS') $bestof = 5;
		else if ($this->eid == 'WC' && (substr($this->matchid, 0, 3) == 'QS3' || substr($this->matchid, 0, 2) == 'MD')) $bestof = 5;

		# 用时
		$dura = date('H:i:s', strtotime('2018-1-1 +' . $json["match"]["matchData"]["durationInMinutes"] . " minutes"));

		# 统计
		$stat = [];
		foreach (['matchStatistics', 'setStatistics1', 'setStatistics2', 'setStatistics3', 'setStatistics4', 'setStatistics5'] as $set) {

			if (!isset($json["matchStatisticsSection"][$set]) || $json["matchStatisticsSection"][$set] === NULL) continue;

			$setNum = intval(str_replace("setStatistics", "", $set));
			if (!isset($stat[$setNum])) $stat[$setNum] = [[], []];

			if ($setNum == 0) {
				$stat[$setNum][0]['dura'] = $stat[$setNum][1]['dura'] = $dura;
			} else {
				$stat[$setNum][0]['dura'] = $stat[$setNum][1]['dura'] = "";
			}

			$ace = $df = $wi = $ue = $tp = 0;
			$faqiu = $yifachenggong = $yifadefen = $erfa = $erfadefen = $pofa = $pofajihui = $wangqiandefen = $wangqianqiu = $jiefadefen = $jiefa = 0;

			foreach ($json["matchStatisticsSection"][$set] as $k => $v) {
				if ($v === null) continue;
				foreach (['A', 'B'] as $p) {
					if ($p == 'A') $_p = 0; else $_p = 1;

					if ($k == "aces") $stat[$setNum][$_p]['ace'] = $v['team'.$p.'Value'] + 0;
					else if ($k == "doubleFaults") $stat[$setNum][$_p]['df'] = $v['team'.$p.'Value'] + 0;
					else if ($k == "winners") $stat[$setNum][$_p]['wi'] = $v['team'.$p.'Value'] + 0;
					else if ($k == "unforcedErrors") $stat[$setNum][$_p]['ue'] = $v['team'.$p.'Value'] + 0;
					else if ($k == "totalPointsWon") $stat[$setNum][$_p]['tp'] = $v['team'.$p.'Value'] + 0;
					else if ($k == "firstServeRateIn") {
						$v_tmp = preg_replace('/ .*$/', '', $v['team'.$p.'Value']);
						$arr = explode("/", $v_tmp);
						$yifachenggong = intval($arr[0]) + 0;
						$faqiu = intval(@$arr[1]) + 0;
						$stat[$setNum][$_p]['s1%'] = self::add_percentage($yifachenggong . "/" . $faqiu);
					}
					else if ($k == "winRateOnFirstServe") {
						$v_tmp = preg_replace('/ .*$/', '', $v['team'.$p.'Value']);
						$arr = explode("/", $v_tmp);
						$yifadefen = intval($arr[0]) + 0;
						$yifachenggong = intval(@$arr[1]) + 0;
						$stat[$setNum][$_p]['s1'] = self::add_percentage($yifadefen . "/" . $yifachenggong);
					}
					else if ($k == "winRateOnSecondServe") {
						$v_tmp = preg_replace('/ .*$/', '', $v['team'.$p.'Value']);
						$arr = explode("/", $v_tmp);
						$erfadefen = intval($arr[0]) + 0;
						$erfa = intval(@$arr[1]) + 0;
						$stat[$setNum][$_p]['s2'] = self::add_percentage($erfadefen . "/" . $erfa);
					}
					else if ($k == "netPointsWon") {
						$v_tmp = preg_replace('/ .*$/', '', $v['team'.$p.'Value']);
						$arr = explode("/", $v_tmp);
						$wangqiandefen = intval($arr[0]) + 0;
						$wangqianqiu = intval(@$arr[1]) + 0;
						$stat[$setNum][$_p]['np%'] = self::add_percentage($wangqiandefen . "/" . $wangqianqiu);
					}
					else if ($k == "breakPoint") {
						$v_tmp = preg_replace('/ .*$/', '', $v['team'.$p.'Value']);
						$arr = explode("/", $v_tmp);
						$pofa = intval($arr[0]) + 0;
						$pofajihui = intval(@$arr[1]) + 0;
						$stat[$setNum][$_p]['bp%'] = self::add_percentage($pofa . "/" . $pofajihui);
					}
					else if ($k == "returnPoints") {
						$v_tmp = preg_replace('/ .*$/', '', $v['team'.$p.'Value']);
						$arr = explode("/", $v_tmp);
						$jiefadefen = intval($arr[0]) + 0;
						$jiefa = intval(@$arr[1]) + 0;
						$stat[$setNum][$_p]['rp%'] = self::add_percentage($jiefadefen . "/" . $jiefa);
					}

				}

			}		
		}

		foreach (['A', 'B'] as $p) {
			if ($p == 'A') $_p = 0; else $_p = 1;

			if (isset($json["matchStatisticsSection"]["serviceStatistics"]["fastest"]) && $json["matchStatisticsSection"]["serviceStatistics"]["fastest"] !== null) {
				$f1fmph = $json["matchStatisticsSection"]["serviceStatistics"]["fastest"]["team" . $p . "Value"];
				$f1fkph = self::mile2kilo($f1fmph);
				$stat[0][$_p]['f1f'] = [$f1fkph, $f1fmph];
			}
			if (isset($json["matchStatisticsSection"]["serviceStatistics"]["averageFirstServe"]) && $json["matchStatisticsSection"]["serviceStatistics"]["averageFirstServe"] !== null) {
				$f1amph = $json["matchStatisticsSection"]["serviceStatistics"]["averageFirstServe"]["team" . $p . "Value"];
				$f1akph = self::mile2kilo($f1amph);
				$stat[0][$_p]['f1a'] = [$f1akph, $f1amph];
			}
			if (isset($json["matchStatisticsSection"]["serviceStatistics"]["averageSecondServe"]) && $json["matchStatisticsSection"]["serviceStatistics"]["averageSecondServe"] !== null) {
				$f2amph = $json["matchStatisticsSection"]["serviceStatistics"]["averageSecondServe"]["team" . $p . "Value"];
				$f2akph = self::mile2kilo($f2amph);
				$stat[0][$_p]['f2a'] = [$f2akph, $f2amph];
			}
		}

		$ratio = self::convertToRatio($stat);

		# 胜负
		$wl = ["unfinished", "unfinished"];
		if (isset($json["match"]["teamA"])) {
			if ($json["match"]["teamA"]["winner"] === true && $json["match"]["teamB"]["winner"] === false) {
				$wl = ["winner", "loser"];
			} else if ($json["match"]["teamB"]["winner"] === true && $json["match"]["teamA"]["winner"] === false) {
				$wl = ["loser", "winner"];
			}
		}

		# 比分
		$score = [];
		for ($i = 0; $i < 5; ++$i) {
			if (!isset($json["match"]["teamA"]["sets"][$i])) {
				$a = $b = '&nbsp;';
				$c = $d = '';
			} else {
				if ($json["match"]["teamA"]["sets"][$i]["winner"] === false && $json["match"]["teamB"]["sets"][$i]["winner"] === false) {
					$c = $d = '';
				} else if ($json["match"]["teamA"]["sets"][$i]["winner"] === true) {
					$c = "SetWinner";
					$d = "SetLoser";
				} else {
					$c = "SetLoser";
					$d = "SetWinner";
				}

				$a = $json["match"]["teamA"]["sets"][$i]["score"];
				$b = $json["match"]["teamB"]["sets"][$i]["score"];
				if ($json["match"]["teamA"]["sets"][$i]["tieBreak"] !== null && $json["match"]["teamB"]["sets"][$i]["winner"] === true) {
					$a .= "<sup>" . $json["match"]["teamA"]["sets"][$i]["tieBreak"] . "</sup>";
				} else if ($json["match"]["teamB"]["sets"][$i]["tieBreak"] !== null && $json["match"]["teamA"]["sets"][$i]["winner"] === true) {
					$b .= "<sup>" . $json["match"]["teamB"]["sets"][$i]["tieBreak"] . "</sup>";
				}
			}
			$score[] = [$a, $b, $c, $d];
		}

		return [
			'status' => 0,
			'stat' => $stat,
			'ratio' => $ratio,
			'score' => $score,
			'wl' => $wl,
			'bestof' => $bestof,
		];
	}

	protected function process_wc() {

		if ($this->eid == "WC"){
			$prefix = "https://www.wimbledon.com/en_GB/scores/feeds/2019/matches/complete/";
		} else if ($this->eid == "UO"){
			$prefix = "https://www.usopen.org/en_US/scores/feeds/2020/matches/complete/";
		}

		$mid = preg_replace('/^.*\//', '', $this->matchid);
		$use_official = true;

		if ($use_official) {
			$ch = curl_init();
			//设置选项，包括URL
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_HEADER, 0); 
			$url = $prefix . $mid . ".json";
			curl_setopt($ch, CURLOPT_URL, $url);
			$html = curl_exec($ch);
			$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($response_code > 400) return ['status' => -1, 'errmsg' => __('stat.notice.error')];
	
			$json = json_decode($html, true);
			if (!$json || !isset($json['matches'][0])) return ['status' => -1, 'errmsg' => __('stat.notice.error')];

		}

		// 总盘数
		$bestof = 3;
		if (substr($this->matchid, 0, 2) == 'MS') $bestof = 5;
		else if ($this->eid == 'WC' && (substr($this->matchid, 0, 3) == 'QS3' || substr($this->matchid, 0, 2) == 'MD')) $bestof = 5;

		$match = $json['matches'][0];
		# 用时
		$dura = date('H:i:s', strtotime('2018-1-1 ' . $match["duration"]));

		# 统计
		$stat = [];

		foreach (['match', 'set_1', 'set_2', 'set_3', 'set_4', 'set_5'] as $set) {

			if (!isset($match["base_stats"][$set])) continue;

			$setNum = intval(str_replace("set_", "", $set));
			if (!isset($stat[$setNum])) $stat[$setNum] = [[], []];

			if ($setNum == 0) {
				$stat[$setNum][0]['dura'] = $stat[$setNum][1]['dura'] = $dura;
			} else {
				$stat[$setNum][0]['dura'] = $stat[$setNum][1]['dura'] = "";
			}

			foreach ($match["base_stats"][$set] as $team => $team_value) {
				if ($team == "team_1") $_p = 0;
				else $_p = 1;

				$ace = $df = $wi = $ue = $tp = 0;
				$faqiu = $yifachenggong = $yifadefen = $erfa = $erfadefen = $pofa = $pofajihui = $wangqiandefen = $wangqianqiu = 0;
				foreach ($team_value as $k => $v) {

					if ($k == "t_ace") $stat[$setNum][$_p]['ace'] = $v + 0;
					else if ($k == "df") $stat[$setNum][$_p]['df'] = $v + 0;
					else if ($k == "t_w") $stat[$setNum][$_p]['wi'] = $v + 0;
					else if ($k == "t_ue") $stat[$setNum][$_p]['ue'] = $v + 0;
					else if ($k == "t_f_srv") $faqiu = $v + 0;
					else if ($k == "t_f_srv_in") $yifachenggong = $v + 0;
					else if ($k == "t_f_srv_w") {$yifadefen = $v + 0; $tp += $v + 0;}
					else if ($k == "t_s_srv") $erfa = $v + 0;
					else if ($k == "t_s_srv_w") {$erfadefen = $v + 0; $tp += $v + 0;}
					else if ($k == "t_bp") $pofajihui = $v + 0;
					else if ($k == "t_bp_w") $pofa = $v + 0;
					else if ($k == "t_na") $wangqianqiu = $v + 0;
					else if ($k == "t_np_w") $wangqiandefen = $v + 0;
					else if ($k == "t_p_w_opp_srv") $tp += $v + 0;
				}

				$stat[$setNum][$_p]['s1%'] = self::add_percentage($yifachenggong . "/" . $faqiu);
				$stat[$setNum][$_p]['s1'] = self::add_percentage($yifadefen . "/" . $yifachenggong);
				$stat[$setNum][$_p]['s2'] = self::add_percentage($erfadefen . "/" . $erfa);
				$stat[$setNum][$_p]['np%'] = self::add_percentage($wangqiandefen . "/" . $wangqianqiu);
				$stat[$setNum][$_p]['bp%'] = self::add_percentage($pofa . "/" . $pofajihui);
				$stat[$setNum][$_p]['tp'] = $tp;
			}		

			if (isset($match["return_stats"][$set])) {
				foreach ($match["return_stats"][$set] as $team => $team_value) {
					if ($team == "team_1") $_p = 0;
					else $_p = 1;
					$jiefadefen = $jiefa = 0;

					foreach ($team_value as $k => $v) {
						if ($k == "t_rtn_p") $jiefa = $v + 0;
						else if ($k == "t_rtn_p_w") $jiefadefen = $v + 0;
					}
					$stat[$setNum][$_p]['rp%'] = self::add_percentage($jiefadefen . "/" . $jiefa);
				}
			}

			if (isset($match["distance_run"][$set])) {
				foreach ($match["distance_run"][$set] as $team => $team_value) {
					if ($team == "team_1") $_p = 0;
					else $_p = 1;

					$diskph = intval($team_value[0]);
					$dismph = self::meter2feet($diskph);

					$stat[$setNum][$_p]['dis'] = [$diskph, $dismph];
				}
			}

			if (isset($match["serve_stats"][$set])) {
				foreach ($match["serve_stats"][$set] as $team => $team_value) {
					if ($team == "team_1") $_p = 0;
					else $_p = 1;

					foreach ($team_value as $k => $v) {
						if ($k == "f_srv_f_spd") {$f1fkph = intval($v[0]); $f1fmph = self::kilo2mile($f1fkph); }
						else if ($k == "f_srv_a_spd") {$f1akph = intval($v[0]); $f1amph = self::kilo2mile($f1akph); }
						else if ($k == "s_srv_f_spd") {$f2fkph = intval($v[0]); $f2fmph = self::kilo2mile($f2fkph); }
						else if ($k == "s_srv_a_spd") {$f2akph = intval($v[0]); $f2amph = self::kilo2mile($f2akph); }
					}
					$stat[$setNum][$_p]['f1f'] = [$f1fkph, $f1fmph];
					$stat[$setNum][$_p]['f1a'] = [$f1akph, $f1amph];
					$stat[$setNum][$_p]['f2f'] = [$f2fkph, $f2fmph];
					$stat[$setNum][$_p]['f2a'] = [$f2akph, $f2amph];
				}
			}
		}

		$ratio = self::convertToRatio($stat);

		# 胜负
		$wl = ["unfinished", "unfinished"];
		if ($match['winner'] == 1) {
			$wl = ["winner", "loser"];
		} else if ($match['winner'] == 2) {
			$wl = ["loser", "winner"];
		}

		# 比分
		$score = [];
		for ($i = 0; $i < 5; ++$i) {
			if (!isset($match['scores']['sets'][$i])) {
				$a = $b = '&nbsp;';
				$c = $d = '';
			} else {
				if ($match['scores']['setsWon'][$i+1] == 0) {
					$c = $d = '';
				} else if ($match['scores']['setsWon'][$i+1] == 1) {
					$c = "SetWinner";
					$d = "SetLoser";
				} else {
					$c = "SetLoser";
					$d = "SetWinner";
				}

				$a = $match['scores']['sets'][$i][0]['score'];
				$b = $match['scores']['sets'][$i][1]['score'];
				if ($match['scores']['sets'][$i][0]['tiebreak'] + $match['scores']['sets'][$i][1]['tiebreak'] > 0 && $match['scores']['setsWon'][$i+1] > 0) {
					if ($match['scores']['sets'][$i][0]['tiebreak'] > $match['scores']['sets'][$i][1]['tiebreak']) {
						$b .= "<sup>" . $match['scores']['sets'][$i][1]['tiebreak'] . "</sup>";
					} else {
						$a .= "<sup>" . $match['scores']['sets'][$i][0]['tiebreak'] . "</sup>";
					}
				}

			}
			$score[] = [$a, $b, $c, $d];
		}

		return [
			'status' => 0,
			'stat' => $stat,
			'ratio' => $ratio,
			'score' => $score,
			'wl' => $wl,
			'bestof' => $bestof,
		];
	}

	protected function process_ao() {
		if ($this->eid == "AO"){
			$prefix = "https://prod-scores-api.ausopen.com/match-centre/";
		}

		$st = $sc = [];

		$mid = substr($this->matchid, 0, 5);
		$cmd = "grep '^" . $this->eid . "\t$mid\t' /home/ubuntu/share/mqtt/mstat | head -1 | cut -f4,5";
		unset($r); exec($cmd, $r);
		if (count($r) > 0) {
			$arr = explode("\t", $r[0]);
			if (count($arr) != 2 || !$arr[0] || !$arr[1]) {
				$use_official = true;
			} else {
				$st = json_decode($arr[0], true);
				$sc = json_decode($arr[1], true);
				$use_official = false;
			}
		} else {
			$use_official = true;
		}
		if ($use_official) {
			//初始化
			$ch = curl_init();
			//设置选项，包括URL
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_HEADER, 0); 
			$url = $prefix . $mid;
			curl_setopt($ch, CURLOPT_URL, $url);
			$html = curl_exec($ch);
			$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($response_code > 400) return ['status' => -1, 'errmsg' => __('stat.notice.error')];

			$json = json_decode($html, true);
			if (!$json) return ['status' => -1, 'errmsg' => __('stat.notice.error')];
			else {

				if (isset($json["teams"])) {
					foreach ($json["teams"] as $l => $p) {
						$s = [];
						if (isset($p["status"]) && $p["status"] == "Winner") {
							$s[] = "iconfont";
						} else {
							$s[] = "";
						}
						$s[] = "";
						foreach ($p["score"] as $k => $ss) {
							$point = preg_replace('/[\[\]]/', '', $ss["game"]);
							if (isset($ss["tie_break"]) && $json["teams"][1-$l]['score'][$k]["winner"] === true) {
								$point .= "<sup>" . $ss["tie_break"] . "</sup>";
							}
							if ($json["teams"][1-$l]['score'][$k]["winner"] === true) {
								$point = "<span class=loser>" . $point . "</span>";
							}
							$s[] = $point;
						}
						for ($i = count($s); $i < 7; ++$i) $s[] = "";
						$sc[] = $s;
					}
				}
			}
		}

		// 总盘数
		$bestof = 3;
		if (substr($this->matchid, 0, 2) == 'MS') $bestof = 5;
		else if ($this->eid == 'WC' && (substr($this->matchid, 0, 3) == 'QS3' || substr($this->matchid, 0, 2) == 'MD')) $bestof = 5;

		// 胜负
		$winner = 0;
		if (strpos($sc[0][0], "iconfont") !== false) {
			$winner = 1;
		} else if (strpos($sc[1][0], "iconfont") !== false) {
			$winner = 2;
		}
		if ($winner == 1) $wl = ["winner", "loser"];
		else if ($winner == 2) $wl = ["loser", "winner"];
		else $wl = ["unfinished", "unfinished"];

		// 比分
		$score = [];

		$s1 = $sc[0];
		$s2 = $sc[1];
		for ($i = 2; $i < 7; ++$i){
			if ($s1[$i] !== "" && $s2[$i] !== ""){
				if (strpos($s1[$i], "loser") !== false) {
					$d = "SetWinner";
					$c = "SetLoser";
				} else if (strpos($s2[$i], "loser") !== false) {
					$d = "SetLoser";
					$c = "SetWinner";
				} else {
					$c = $d = "";
				}
				$a = preg_replace('/<[^>]*span[^>]*>/', "", $s1[$i]);
				$b = preg_replace('/<[^>]*span[^>]*>/', "", $s2[$i]);
			} else{
				$a = $b = '&nbsp;';
				$c = $d = '';
			}
			$score[] = [$a, $b, $c, $d];
		}

		// 统计
		$stat = [];

		for ($i = 0; $i < 5; ++$i) {
			foreach ([0, 1] as $p) {
				if ($p == 0) $pname = "teamA"; else $pname = "teamB";
				if (!isset($json['teams'][$p]['score'][$i])) continue;
				if (!isset($json['teams'][$p]['score'][$i]['minutes'])) {
					$stat[$i + 1][$p]['dura'] = "";
				} else {
					$stat[$i + 1][$p]['dura'] = date('H:i:s', strtotime("2018-1-1 +" . $json['teams'][$p]['score'][$i]['minutes'] . " minutes"));
				}
			}
		}

		$stat[0][0]['dura'] = $stat[0][1]['dura'] = date('H:i:s', strtotime("2018-1-1 " . $json['duration']));

		foreach ($json['stats']['key_stats'] as $key_stat) {
			foreach ($key_stat['sets'] as $set) {
				$setNum = $set['set'];
				if ($setNum == "All") $setNum = 0;

				foreach ($set['stats'] as $item) {
					foreach ([0, 1] as $p) {
						if ($p == 0) $pname = "teamA"; else $pname = "teamB";
						if ($item['name'] == "Aces") {$stat[$setNum][$p]["ace"] = $item[$pname]['primary'];}
						else if ($item['name'] == "Double faults") {$stat[$setNum][$p]["df"] = $item[$pname]['primary'];}
						else if ($item['name'] == "1st serve in") {$stat[$setNum][$p]["s1%"] = self::add_percentage($item[$pname]['secondary']);}
						else if ($item['name'] == "Win 1st serve") {$stat[$setNum][$p]["s1"] = self::add_percentage($item[$pname]['secondary']);}
						else if ($item['name'] == "Win 2nd serve") {$stat[$setNum][$p]["s2"] = self::add_percentage($item[$pname]['secondary']);}
						else if ($item['name'] == "Winners") {$stat[$setNum][$p]["wi"] = $item[$pname]['primary'];}
						else if ($item['name'] == "Unforced errors") {$stat[$setNum][$p]["ue"] = $item[$pname]['primary'];}
						else if ($item['name'] == "Break points won") {$stat[$setNum][$p]["bp%"] = self::add_percentage($item[$pname]['secondary']);}
						else if ($item['name'] == "Net points won") {$stat[$setNum][$p]["np%"] = self::add_percentage($item[$pname]['secondary']);}
						else if ($item['name'] == "Total points won") {$stat[$setNum][$p]["tp"] = $item[$pname]['primary'];}
						else if ($item['name'] == "Receiving points won") {$stat[$setNum][$p]["rp%"] = self::add_percentage($item[$pname]['secondary']);}
						else if ($item['name'] == "Fastest serve") {$stat[$setNum][$p]["f1f"] = [$item[$pname]['primary'], self::kilo2mile($item[$pname]['primary'])];}
						else if ($item['name'] == "1st Serve Average") {$stat[$setNum][$p]["f1a"] = [$item[$pname]['primary'], self::kilo2mile($item[$pname]['primary'])];}
						else if ($item['name'] == "2nd serve average") {$stat[$setNum][$p]["f2a"] = [$item[$pname]['primary'], self::kilo2mile($item[$pname]['primary'])];}
						else if ($item['name'] == "") {$stat[$setNum][$p][""] = self::add_percentage($item[$pname]['secondary']);}
					}
				}
			}
		}

		foreach ($json['stats']['serve_stats'] as $key_stat) {
			if ($key_stat['name'] == "1st Serve") $f = 'f1'; else if ($key_stat['name'] == "2nd Serve") $f = 'f2'; else continue;
		
			foreach ($key_stat['sets'] as $set) {
				$setNum = $set['set'];
				if ($setNum == "All") $setNum = 0;

				foreach ($set['stats'] as $item) {
					foreach ([0, 1] as $p) {
						if ($p == 0) $pname = "teamA"; else $pname = "teamB";
						if ($item['name'] == "Fastest serve speed") {$stat[$setNum][$p][$f . "f"] = [$item[$pname]['primary'], self::kilo2mile($item[$pname]['primary'])];}
						else if ($item['name'] == "Average serve speed") {$stat[$setNum][$p][$f . "a"] = [$item[$pname]['primary'], self::kilo2mile($item[$pname]['primary'])];}
					}
				}
			}
		}


/*
		foreach ($st as $sets) {
			$set = $sets["SetNum"];
			if ($set == "all") $set = 0;
			for ($i = 1; $i <= 2; ++$i) {
				$data = $sets["Data"];
				$p = "Player" . $i;

				if (isset($data['TotPtsWon'][$p])) {
					$tp = $data['TotPtsWon'][$p];
				} else {
					$tp = @$data['TotSPtsWon'][$p] + @$data['TotRetPtsWon'][$p];
				}
				$ace = $data['TotAces'][$p];		
				$df = $data['DF'][$p];
//					$wi = $data['TotWin'][$p];
//					$ue = $data['TotUnfErr'][$p];
				$wi = @$data['FHApprWin'][$p] + @$data['FHDropWin'][$p] + @$data['FHGndWin'][$p] + @$data['FHLobWin'][$p] + @$data['FHOvhdWin'][$p] + @$data['FHPassWin'][$p] + @$data['FHVolWin'][$p]
					+ @$data['BHApprWin'][$p] + @$data['BHDropWin'][$p] + @$data['BHGndWin'][$p] + @$data['BHLobWin'][$p] + @$data['BHOvhdWin'][$p] + @$data['BHPassWin'][$p] + @$data['BHVolWin'][$p] + $ace;
				$ue = @$data['FHApprUnf'][$p] + @$data['FHDropUnf'][$p] + @$data['FHGndUnf'][$p] + @$data['FHLobUnf'][$p] + @$data['FHOvhdUnf'][$p] + @$data['FHPassUnf'][$p] + @$data['FHVolUnf'][$p]
					+ @$data['BHApprUnf'][$p] + @$data['BHDropUnf'][$p] + @$data['BHGndUnf'][$p] + @$data['BHLobUnf'][$p] + @$data['BHOvhdUnf'][$p] + @$data['BHPassUnf'][$p] + @$data['BHVolUnf'][$p] + $df;

				$oppowi = @$data['FHApprWin']['Player' . (3 - $i)] + @$data['FHDropWin']['Player' . (3 - $i)] + @$data['FHGndWin']['Player' . (3 - $i)] + @$data['FHLobWin']['Player' . (3 - $i)] + @$data['FHOvhdWin']['Player' . (3 - $i)] + @$data['FHPassWin']['Player' . (3 - $i)] + @$data['FHVolWin']['Player' . (3 - $i)]
						+ @$data['BHApprWin']['Player' . (3 - $i)] + @$data['BHDropWin']['Player' . (3 - $i)] + @$data['BHGndWin']['Player' . (3 - $i)] + @$data['BHLobWin']['Player' . (3 - $i)] + @$data['BHOvhdWin']['Player' . (3 - $i)] + @$data['BHPassWin']['Player' . (3 - $i)] + @$data['BHVolWin']['Player' . (3 - $i)] + @$data['TotAces']['Player' . (3 - $i)];
				$fe = $data['TotPtsWon']['Player' . (3 - $i)] - $oppowi - $ue;

				if (isset($data['TotSPts'][$p])) {
					$faqiu = $data['TotSPts'][$p];
				} else {
					$faqiu = ceil($data['FSPts'][$p] / $data['FSPct'][$p] * 100);
				}
				$yifachenggong = $data['FSPts'][$p];
				$yifadefen = $data['FSPtsWon'][$p];
				$erfadefen = $data['SSPtsWon'][$p];
				if (isset($data['SSPts'][$p])) {
					$erfa = $data['SSPts'][$p];
				} else if (isset($data['SSPtsWonPct'][$p]) && $data['SSPtsWonPct'][$p] != 0){
					$erfa = ceil($data['SSPtsWon'][$p] / $data['SSPtsWonPct'][$p] * 100);
				} else {
					$erfa = 0;
				}
				$pofa = $data['BrkPtsWon'][$p];
				$pofajihui = $data['BrkPts'][$p];
				$wangqiandefen = @$data['NetPtsWon'][$p] + 0;
				$wangqianqiu = @$data['NetPts'][$p] + 0;
				$jiefaqiudefen = $data['TotRetPtsWon'][$p];

				$s1_percent = self::add_percentage($yifachenggong . "/" . $faqiu);
				$s1 = self::add_percentage($yifadefen . "/" . $yifachenggong);;
				$s2 = self::add_percentage($erfadefen . "/" . $erfa);
				$np_percent = self::add_percentage($wangqiandefen . "/" . $wangqianqiu);
				$bp_percent = self::add_percentage($pofa . "/" . $pofajihui);

				// 得分细节
				$asp = @$data['FHApprWin'][$p] + @$data['BHApprWin'][$p]; $as = @$data['FHApprWin'][$p] + @$data['BHApprWin'][$p] + @$data['FHApprUnf'][$p] + @$data['BHApprUnf'][$p];
				$dsp = @$data['FHDropWin'][$p] + @$data['BHDropWin'][$p]; $ds = @$data['FHDropWin'][$p] + @$data['BHDropWin'][$p] + @$data['FHDropUnf'][$p] + @$data['BHDropUnf'][$p];
				$gsp = @$data['FHGndWin'][$p] + @$data['BHGndWin'][$p]; $gs = @$data['FHGndWin'][$p] + @$data['BHGndWin'][$p] + @$data['FHGndUnf'][$p] + @$data['BHGndUnf'][$p];
				$lp = @$data['FHLobWin'][$p] + @$data['BHLobWin'][$p]; $l = @$data['FHLobWin'][$p] + @$data['BHLobWin'][$p] + @$data['FHLobUnf'][$p] + @$data['BHLobUnf'][$p];
				$osp = @$data['FHOvhdWin'][$p] + @$data['BHOvhdWin'][$p]; $os = @$data['FHOvhdWin'][$p] + @$data['BHOvhdWin'][$p] + @$data['FHOvhdUnf'][$p] + @$data['BHOvhdUnf'][$p];
				$psp = @$data['FHPassWin'][$p] + @$data['BHPassWin'][$p]; $ps = @$data['FHPassWin'][$p] + @$data['BHPassWin'][$p] + @$data['FHPassUnf'][$p] + @$data['BHPassUnf'][$p];
				$vp = @$data['FHVolWin'][$p] + @$data['BHVolWin'][$p]; $v = @$data['FHVolWin'][$p] + @$data['BHVolWin'][$p] + @$data['FHVolUnf'][$p] + @$data['BHVolUnf'][$p];

				$as_percent = self::add_percentage($asp."/".$as);
				$ds_percent = self::add_percentage($dsp."/".$ds);
				$gs_percent = self::add_percentage($gsp."/".$gs);
				$l_percent = self::add_percentage($lp."/".$l);
				$os_percent = self::add_percentage($osp."/".$os);
				$ps_percent = self::add_percentage($psp."/".$ps);
				$v_percent = self::add_percentage($vp."/".$v);

				$f1akph = @$data['FSAvgKMH'][$p] + 0;
				$f2akph = @$data['SSAvgKMH'][$p] + 0;
				$f1fkph = @$data['FastKMH'][$p] + 0;
				$f2fkph = 0;
				$diskph = 0;

				$f1fmph = self::kilo2mile($f1fkph);
				$f1amph = self::kilo2mile($f1akph);
				$f2fmph = self::kilo2mile($f1fkph);
				$f2amph = self::kilo2mile($f2akph);
				$dismph = self::meter2feet($diskph);
				
				$dura = "";
				$stat[$set][] = [
					'dura' => $dura,
					'ace' => $ace,
					'df' => $df,
					's1%' => $s1_percent,
					's1' => $s1,
					's2' => $s2,
					'wi' => $wi,
					'ue' => $ue,
					'fe' => $fe,
					'bp%' => $bp_percent,
					'np%' => $np_percent,
					'tp' => $tp,
					'dis' => [$diskph, $dismph],
					'f1f' => [$f1fkph, $f1fmph],
					'f1a' => [$f1akph, $f1amph],
					'f2f' => [$f2fkph, $f2fmph],
					'f2a' => [$f2akph, $f2amph],
					'as' => $as_percent,
					'ds' => $ds_percent,
					'gs' => $gs_percent,
					'l' => $l_percent,
					'os' => $os_percent,
					'ps' => $ps_percent,
					'v' => $v_percent,
				];
			}
		}
*/

		ksort($stat);
		//print_r($stat);

		$ratio = self::convertToRatio($stat);

		return [
			'status' => 0,
			'stat' => $stat,
			'ratio' => $ratio,
			'score' => $score,
			'wl' => $wl,
			'bestof' => $bestof,
		];

	}

	protected function process_grand_slam() {

		if ($this->eid == "RG"){
			$prefix = "http://www.rolandgarros.com/en_FR/";
		} else if ($this->eid == "WC"){
			$prefix = "http://www.wimbledon.com/en_GB/";
		} else if ($this->eid == "UO"){
			$prefix = "http://www.usopen.org/en_US/";
		} else if ($this->eid == "AO"){
			$prefix = "http://www.ausopen.com/en_AU/";
		}

		$matchtype = substr($this->matchid, 0, 2);
		$matchtype = Config::get('const.grandslam.type2id.' . $matchtype);

		$cmd = "grep '^$matchtype" . substr($this->matchid, 2) . "\t' /home/ubuntu/share/mqtt/matchstat | head -1";
		unset($r); exec($cmd, $r);
		if (count($r) > 0){
			$line_arr = explode("\t", trim($r[0]));
			$str = replace_letters($line_arr[1]);
		} else {
			//初始化
			$ch = curl_init();
			//设置选项，包括URL
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_HEADER, 0); 
			$url = $prefix . "xml/gen/scores/completed/" . $matchtype . substr($this->matchid, 2) . ".unc.xml";
			curl_setopt($ch, CURLOPT_URL, $url);
			$html = curl_exec($ch);
			$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($response_code > 400) return ['status' => -1, 'errmsg' => __('stat.notice.error')];

			$html = preg_replace('/<h>.*<\/h>/', "", $html);
			$str = replace_letters(preg_replace('/<[^>]*>/', "", $html));
		}

		$arr = explode("|", $str);

		// 总盘数
		$bestof = 3;
		if (substr($this->matchid, 0, 2) == 'MS') $bestof = 5;
		else if ($this->eid == 'M995' && (substr($this->matchid, 0, 3) == 'QS3' || substr($this->matchid, 0, 2) == 'MD')) $bestof = 5;

		// 胜负
		$winner = 0;
		if (strpos("FGHIJKLM", $arr[5]) !== false){
			if (strpos("FHJL", $arr[5]) !== false){
				$winner = 1;
			} else if (strpos("GIKM", $arr[5]) !== false){
				$winner = 2;
			}
		}
		if ($winner == 1) $wl = ["winner", "loser"];
		else if ($winner == 2) $wl = ["loser", "winner"];
		else $wl = ["unfinished", "unfinished"];

		// 每盘比分
		$score = [];
		$s1 = $arr[16];
		$s2 = $arr[17];
		for ($i = 1; $i <= 5; ++$i){
			if (substr($s1, 2*($i-1), 1)){
				$a = ord(substr($s1, 2*($i-1), 1)) - ord("A");
				$b = ord(substr($s2, 2*($i-1), 1)) - ord("A");

				if (substr($s1, 2 * $i, 1) && $i < 5) {
					if ($a > $b) {$c = 'SetWinner'; $d = 'SetLoser';}
					else {$c = 'SetLoser'; $d = 'SetWinner';}
				} else {
					if ($winner > 0) {
						if ($winner == 1) {$c = 'SetWinner'; $d = 'SetLoser';}
						else {$c = 'SetLoser'; $d = 'SetWinner';};
					} else {
						$c = $d = '';
					}
				}

				if (($a == 6 && $b == 7) || ($a == 0 && $b == 1 && $i == $bestof && $winner == 2)){
					$a .= "<sup>". (ord(substr($s1, 2*($i-1)+1, 1)) - ord("A")) ."</sup>";
				} else if (($a == 7 && $b == 6) || ($a == 1 && $b == 0 && $i == $bestof && $winner == 1)){
					$b .= "<sup>". (ord(substr($s2, 2*($i-1)+1, 1)) - ord("A")) ."</sup>";
				}
			} else{
				$a = $b = '&nbsp;';
				$c = $d = '';
			}
			$score[] = [$a, $b, $c, $d];
		}

		// 数据统计
		$stat = [];

		for ($set = 0; $set <= 5; ++$set) {

			for ($seq = 0; $seq < 2; ++$seq) {

				$idx = 27 + $set * 3 + $seq;

				if (!isset($arr[$idx]) || !$arr[$idx]) continue;

				$ace = $df = $wi = $ue = $faqiu = $yifachenggong = $yifadefen = $erfadefen = $erfa = $pofa = $pofajihui = $wangqiandefen = $wangqianqiu = $jiefaqiudefen = 0;

				if (isset($arr[$idx]) && $arr[$idx]) {
					$ar = explode(":", $arr[$idx]);

					$ace = $ar[9];
					$df = $ar[10];
					$wi = $ar[11];
					$ue = $ar[12];

					$faqiu = $ar[2];
					$yifachenggong = $ar[0];
					$yifadefen = $ar[1];
					$yifachenggonglu = $ar[3];
					$yifadefenlu = $ar[4];
					$erfadefen = $ar[5];
					$erfa = $ar[6];
					$erfachenggonglu = $ar[7];
					$erfadefenlu = $ar[8];
					$pofa = $ar[13];
					$pofajihui = $ar[14];
					$pofalu = $ar[15];
					$wangqiandefen = $ar[16];
					$wangqianqiu = $ar[17];
					$wangqigndefenlu = $ar[18];
					$jiefaqiudefen = $ar[19];
				}

				$s1_percent = self::add_percentage($yifachenggong . "/" . $faqiu);
				$s1 = self::add_percentage($yifadefen . "/" . $yifachenggong);;
				$s2 = self::add_percentage($erfadefen . "/" . $erfa);
				$np_percent = self::add_percentage($wangqiandefen . "/" . $wangqianqiu);
				$bp_percent = self::add_percentage($pofa . "/" . $pofajihui);
				$tp = $yifadefen + $erfadefen + $jiefaqiudefen;

				// 时长
				$dura = '00:00:00';
				$idx = 29 + $set * 3;

				if (isset($arr[$idx]) && $arr[$idx]) {
					$dura = date('H:i:s', strtotime("0:0:0 +" . $arr[$idx] . " minutes"));
				}


				// 发球时速
				$f1fkph = $f1akph = $f2fkph = $f2akph = 0;
				$f1fmph = $f1amph = $f2fmph = $f2amph = 0;
				$idx = 50 + $set * 2 + $seq;

				if (isset($arr[$idx]) && $arr[$idx]) {
					$ar = explode(":", $arr[$idx]);
					$f1fkph = $ar[7] + 0;
					$f1akph = $ar[6] + 0;
					$f2fkph = $ar[15] + 0;
					$f2akph = $ar[14] + 0;

					$f1fmph = self::kilo2mile($ar[7]);
					$f1amph = self::kilo2mile($ar[6]);
					$f2fmph = self::kilo2mile($ar[15]);
					$f2amph = self::kilo2mile($ar[14]);
				}

				// 跑动距离
				$dismph = $diskph = 0;
				$idx = 48 + $seq;

				if (isset($arr[$idx]) && $arr[$idx]) {
					$ar = explode(":", $arr[$idx]);
					if (count($ar) > 1) {
						$ar1 = explode(',', $ar[$set]);
						if (count($ar1) == 2) {
							$dismph = $ar1[1] + 0;
							$diskph = $ar1[0] + 0;
						}
					}
				}

				// 得分细节
				$fe = $as = $ds = $gs = $l = $os = $ps = $v = 0;
				$asp = $dsp = $gsp = $lp = $osp = $psp = $vp = 0;
				$idx = 74 + $set * 2 + $seq;

				if (isset($arr[$idx]) && $arr[$idx]) {
					$ar = explode(":", $arr[$idx]);

					$fe = $as = $ds = $gs = $l = $os = $ps = $v = 0;
					$asp = $dsp = $gsp = $lp = $osp = $psp = $vp = 0;
					for ($j = 0; $j < count($ar); ++$j){
						if ($j % 4 == 0) $fe += $ar[$j];
						if ($j % 4 == 1) continue;
						if (floor($j / 8) == 0) {$as += $ar[$j]; if ($j % 4 == 3) $asp += $ar[$j];}
						if (floor($j / 8) == 1) {$ds += $ar[$j]; if ($j % 4 == 3) $dsp += $ar[$j];}
						if (floor($j / 8) == 2) {$gs += $ar[$j]; if ($j % 4 == 3) $gsp += $ar[$j];}
						if (floor($j / 8) == 3) {$l += $ar[$j]; if ($j % 4 == 3) $lp += $ar[$j];}
						if (floor($j / 8) == 4) {$os += $ar[$j]; if ($j % 4 == 3) $osp += $ar[$j];}
						if (floor($j / 8) == 5) {$ps += $ar[$j]; if ($j % 4 == 3) $psp += $ar[$j];}
						if (floor($j / 8) == 6) {$v += $ar[$j]; if ($j % 4 == 3) $vp += $ar[$j];}
					}
				}

				$as_percent = self::add_percentage($asp."/".$as);
				$ds_percent = self::add_percentage($dsp."/".$ds);
				$gs_percent = self::add_percentage($gsp."/".$gs);
				$l_percent = self::add_percentage($lp."/".$l);
				$os_percent = self::add_percentage($osp."/".$os);
				$ps_percent = self::add_percentage($psp."/".$ps);
				$v_percent = self::add_percentage($vp."/".$v);

				$stat[$set][] = [
					'dura' => $dura,
					'ace' => $ace,
					'df' => $df,
					's1%' => $s1_percent,
					's1' => $s1,
					's2' => $s2,
					'wi' => $wi,
					'ue' => $ue,
					'fe' => $fe,
					'bp%' => $bp_percent,
					'np%' => $np_percent,
					'tp' => $tp,
					'dis' => [$diskph, $dismph],
					'f1f' => [$f1fkph, $f1fmph],
					'f1a' => [$f1akph, $f1amph],
					'f2f' => [$f2fkph, $f2fmph],
					'f2a' => [$f2akph, $f2amph],
					'as' => $as_percent,
					'ds' => $ds_percent,
					'gs' => $gs_percent,
					'l' => $l_percent,
					'os' => $os_percent,
					'ps' => $ps_percent,
					'v' => $v_percent,
				];

			} // for seq

		} // for set

		$ratio = self::convertToRatio($stat);

		return [
			'status' => 0,
			'stat' => $stat,
			'ratio' => $ratio,
			'score' => $score,
			'wl' => $wl,
			'bestof' => $bestof,
		];

	}

	protected function process_atp_wta_tour() {

		$url = "http://ws.protennislive.com/LiveScoreSystem/M/Short/GetMatchStats_VCrypt.aspx?year=$this->year&id=$this->eid&mId=$this->matchid";
		$html = file_get_contents($url);
		$XML = decrypt2xml(trim($html));
		$match = $XML->Tournament->Match;

		if (!$match || !$match->attributes()->csv) return ['status' => -1, 'errmsg' => __('stat.notice.error')];

		$arr = explode(",", replace_letters($match->attributes()->csv) . "");

		// 总盘数
		$bestof = $arr[5];

		// 胜负
		$winner = 0;
		if ($arr[7] == "F"){
			if ($arr[46] % 2 == 0){
				$winner = 1;
			} else if ($arr[46] % 2 == 1){
				$winner = 2;
			}
		}

		if ($winner == 1) $wl = ["winner", "loser"];
		else if ($winner == 2) $wl = ["loser", "winner"];
		else $wl = ["unfinished", "unfinished"];

		// 每盘比分
		$score = [];

		for ($i = 1; $i <= 5; ++$i){
			if ($arr[28 + 2 * $i] != ""){
				$a = $arr[28 + 2 * $i];
				$b = $arr[29 + 2 * $i];

				if ($arr[30 + 2 * $i] !== "" && $i < 5) {
					if ($a > $b) {$c = 'SetWinner'; $d = 'SetLoser';}
					else {$c = 'SetLoser'; $d = 'SetWinner';}
				} else {
					if ($winner > 0) {
						if ($winner == 1) {$c = 'SetWinner'; $d = 'SetLoser';}
						else {$c = 'SetLoser'; $d = 'SetWinner';};
					} else {
						$c = $d = '';
					}
				}

				if (($a == 6 && $b == 7) || ($a == 0 && $b == 1 && $i == $bestof && $winner == 2) || ($a == 3 && $b == 4 && $this->eid == '7696')){
					$a .= "<sup>". $arr[39 + $i] ."</sup>";
				} else if (($a == 7 && $b == 6) || ($a == 1 && $b == 0 && $i == $bestof && $winner == 1) || ($a == 4 && $b == 3 && $this->eid == '7696')){
					$b .= "<sup>". $arr[39 + $i] ."</sup>";
				}
			} else{
				$a = $b = "&nbsp;";
				$c = $d = '';
			}
			$score[] = [$a, $b, $c, $d];

		}

		$stat = [];

		$all[0] = $all[1] = [];
		for ($set = 1; $set <= 5; ++$set){
			$_i = "s" . $set;
			if (isset($match->attributes()->$_i)){

				$stat[$set] = [];

				$s = $match->attributes()->$_i;
				$t = explode("|", $s);

				$idx = array();
				for ($seq = 0; $seq < 2; ++$seq){
					$w[$seq] = explode(",", $t[$seq]);
				}
				for ($seq = 0; $seq < 2; ++$seq){
					$ace = $w[$seq][0] + 0;
					$df = $w[$seq][1] + 0;
					$tp = $w[$seq][10] + 0;

					$faqiu = $w[$seq][5] + 0;
					$yifachenggong = $w[$seq][3] + 0;
					$yifadefen = $w[$seq][2] + 0;
					$erfa = $w[$seq][5] - $w[$seq][3];
					$erfadefen = $w[$seq][4] - $w[$seq][2];
					$faqiudefen = $w[$seq][4] + 0;
					$pofa = $w[$seq][6] + 0;
					$pofajihui = $w[$seq][7] + 0;
					$faqiuju = $w[$seq][8] + 0;

					$oppo_faqiudiufen = $w[1 - $seq][5] - $w[1 - $seq][4];
					$oppo_faqiu = $w[1 - $seq][5] + 0;
					$baofa = $w[$seq][8] - $w[1 - $seq][6];

					@$all[$seq]['ace'] += $ace;
					@$all[$seq]['df'] += $df;
					@$all[$seq]['tp'] += $tp;
					@$all[$seq]['faqiu'] += $faqiu;
					@$all[$seq]['yifachenggong'] += $yifachenggong;
					@$all[$seq]['yifadefen'] += $yifadefen;
					@$all[$seq]['erfa'] += $erfa;
					@$all[$seq]['erfadefen'] += $erfadefen;
					@$all[$seq]['faqiudefen'] += $faqiudefen;
					@$all[$seq]['pofa'] += $pofa;
					@$all[$seq]['pofajihui'] += $pofajihui;
					@$all[$seq]['faqiuju'] += $faqiuju;
					@$all[$seq]['oppo_faqiudiufen'] += $oppo_faqiudiufen;
					@$all[$seq]['oppo_faqiu'] += $oppo_faqiu;
					@$all[$seq]['baofa'] += $baofa;

					$s1_percent = self::add_percentage($yifachenggong . "/" . $faqiu);
					$s1 = self::add_percentage($yifadefen . "/" . $yifachenggong);
					$s2 = self::add_percentage($erfadefen . "/" . $erfa);
					$bp_percent = self::add_percentage($pofa . "/" . $pofajihui);
					$rp_percent = self::add_percentage($oppo_faqiudiufen . "/" . $oppo_faqiu);
					$sg_percent = self::add_percentage($baofa . "/" . $faqiuju);

					$dura = date('H:i:s', strtotime($arr[48 + $set]));

					$stat[$set][] = [
						'dura' => $dura,
						'ace' => $ace,
						'df' => $df,
						's1%' => $s1_percent,
						's1' => $s1,
						's2' => $s2,
						'bp%' => $bp_percent,
						'sg%' => $sg_percent,
						'rp%' => $rp_percent,
						'tp' => $tp,
					];
				} // for seq
			} // if isset
		} // for set

		for ($seq = 0; $seq < 2; ++$seq) {
			@$all[$seq]['s1_percent'] = self::add_percentage($all[$seq]['yifachenggong'] . "/" . $all[$seq]['faqiu']);
			@$all[$seq]['s1'] = self::add_percentage($all[$seq]['yifadefen'] . "/" . $all[$seq]['yifachenggong']);
			@$all[$seq]['s2'] = self::add_percentage($all[$seq]['erfadefen'] . "/" . $all[$seq]['erfa']);
			@$all[$seq]['bp_percent'] = self::add_percentage($all[$seq]['pofa'] . "/" . $all[$seq]['pofajihui']);
			@$all[$seq]['rp_percent'] = self::add_percentage($all[$seq]['oppo_faqiudiufen'] . "/" . $all[$seq]['oppo_faqiu']);
			@$all[$seq]['sg_percent'] = self::add_percentage($all[$seq]['baofa'] . "/" . $all[$seq]['faqiuju']);
			@$all[$seq]['dura'] = $arr[48];

			$stat[0][] = [
				'dura' => $all[$seq]['dura'],
				'ace' => $all[$seq]['ace'],
				'df' => $all[$seq]['df'],
				's1%' => $all[$seq]['s1_percent'],
				's1' => $all[$seq]['s1'],
				's2' => $all[$seq]['s2'],
				'bp%' => $all[$seq]['bp_percent'],
				'sg%' => $all[$seq]['sg_percent'],
				'rp%' => $all[$seq]['rp_percent'],
				'tp' => $all[$seq]['tp'],
			];
		}

		ksort($stat);
			
		$ratio = self::convertToRatio($stat);

		return [
			'status' => 0,
			'stat' => $stat,
			'ratio' => $ratio,
			'score' => $score,
			'wl' => $wl,
			'bestof' => $bestof,
		];

	}

	protected function process_wta_tour() {

		$url = "https://api.wtatennis.com/tennis/tournaments/$this->eid/$this->year/matches/$this->matchid/score";
		$html = file_get_contents($url);
		$match = json_decode($html, true);
		if (!$match || !isset($match[0])) return ['status' => -1, 'errmsg' => __('stat.notice.error')];

		// 总盘数
		$bestof = 3;

		// 胜负
		$winner = 0;
		if ($match[0]["MatchState"] == "F"){
			if ($match[0]["Winner"] % 2 == 0){
				$winner = 1;
			} else if ($match[0]["Winner"] % 2 == 1){
				$winner = 2;
			}
		}
		if ($winner == 1) $wl = ["winner", "loser"];
		else if ($winner == 2) $wl = ["loser", "winner"];
		else $wl = ["unfinished", "unfinished"];

		// 每盘比分
		$score = [];
		for ($i = 1; $i <= 5; ++$i){
			if ($match[0]["ScoreSet" . $i . "A"] != ""){
				$a = intval($match[0]["ScoreSet" . $i . "A"]);
				$b = intval($match[0]["ScoreSet" . $i . "B"]);

				if ($i < 5 && $match[0]["ScoreSet" . ($i + 1) . "A"] !== "") {
					if ($a > $b) {$c = 'SetWinner'; $d = 'SetLoser';}
					else {$c = 'SetLoser'; $d = 'SetWinner';}
				} else {
					if ($winner > 0) {
						if ($winner == 1) {$c = 'SetWinner'; $d = 'SetLoser';}
						else {$c = 'SetLoser'; $d = 'SetWinner';};
					} else {
						$c = $d = '';
					}
				}

				if (($a == 6 && $b == 7) || ($a == 0 && $b == 1 && $i == $bestof && $winner == 2) || ($a == 3 && $b == 4 && $this->eid == '7696')){
					$a .= "<sup>". $match[0]["ScoreTbSet" . $i] ."</sup>";
				} else if (($a == 7 && $b == 6) || ($a == 1 && $b == 0 && $i == $bestof && $winner == 1) || ($a == 4 && $b == 3 && $this->eid == '7696')){
					$b .= "<sup>". $match[0]["ScoreTbSet" . $i] ."</sup>";
				}
			} else{
				$a = $b = "&nbsp;";
				$c = $d = '';
			}
			$score[] = [$a, $b, $c, $d];
		}


		$url = "https://api.wtatennis.com/tennis/tournaments/$this->eid/$this->year/matches/$this->matchid/stats";
		$html = file_get_contents($url);
		$match = json_decode($html, true);
		if (!$match || !isset($match[0])) return ['status' => -1, 'errmsg' => __('stat.notice.error')];

		$stat = [];

		$all[0] = $all[1] = [];
		for ($set = 1; $set <= 5; ++$set){
			if (isset($match[$set])){
				$stat[$set] = [];
				$SET = $match[$set];
				foreach (["a", "b"] as $seq){
					$oppo = $seq == "a" ? "b" : "a";

					$ace = $SET["aces" . $seq];
					$df = $SET["dblflt" . $seq];
					$tp = $SET["totptswon" . $seq];

					$faqiu = $SET["totservplayed" . $seq];
					$yifachenggong = $SET["ptsplayed1stserv" . $seq];
					$yifadefen = $SET["ptswon1stserv" . $seq];
					$erfa = $faqiu - $yifachenggong;
					$faqiudefen = $SET["ptstotwonserv" . $seq];
					$erfadefen = $faqiudefen - $yifadefen;
					$pofa = $SET["breakptsconv" . $seq];
					$pofajihui = $SET["breakptsplayed" . $seq];
					$faqiuju = $SET["servgamesplayed" . $seq];

					$oppo_faqiudiufen = $tp - $faqiudefen;
					$oppo_faqiu = $SET["totservplayed" . $oppo];
					$baofa = $faqiuju - $SET["breakptsconv" . $oppo];

					@$all[$seq]['ace'] += $ace;
					@$all[$seq]['df'] += $df;
					@$all[$seq]['tp'] += $tp;
					@$all[$seq]['faqiu'] += $faqiu;
					@$all[$seq]['yifachenggong'] += $yifachenggong;
					@$all[$seq]['yifadefen'] += $yifadefen;
					@$all[$seq]['erfa'] += $erfa;
					@$all[$seq]['erfadefen'] += $erfadefen;
					@$all[$seq]['faqiudefen'] += $faqiudefen;
					@$all[$seq]['pofa'] += $pofa;
					@$all[$seq]['pofajihui'] += $pofajihui;
					@$all[$seq]['faqiuju'] += $faqiuju;
					@$all[$seq]['oppo_faqiudiufen'] += $oppo_faqiudiufen;
					@$all[$seq]['oppo_faqiu'] += $oppo_faqiu;
					@$all[$seq]['baofa'] += $baofa;

					$s1_percent = self::add_percentage($yifachenggong . "/" . $faqiu);
					$s1 = self::add_percentage($yifadefen . "/" . $yifachenggong);
					$s2 = self::add_percentage($erfadefen . "/" . $erfa);
					$bp_percent = self::add_percentage($pofa . "/" . $pofajihui);
					$rp_percent = self::add_percentage($oppo_faqiudiufen . "/" . $oppo_faqiu);
					$sg_percent = self::add_percentage($baofa . "/" . $faqiuju);

					$dura = $SET["settime"];

					$stat[$set][] = [
						'dura' => $dura,
						'ace' => $ace,
						'df' => $df,
						's1%' => $s1_percent,
						's1' => $s1,
						's2' => $s2,
						'bp%' => $bp_percent,
						'sg%' => $sg_percent,
						'rp%' => $rp_percent,
						'tp' => $tp,
					];
				} // for seq
			} // if isset
		} // for set

		$seconds = 0;
		for ($set = 1; $set <= 5; ++$set){
			if (isset($match[$set])){
				$SET = $match[$set];
				$arr = explode(":", $SET["settime"]);
				$seconds += $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
			}
		}

		foreach (["a", "b"] as $seq){
			@$all[$seq]['s1_percent'] = self::add_percentage($all[$seq]['yifachenggong'] . "/" . $all[$seq]['faqiu']);
			@$all[$seq]['s1'] = self::add_percentage($all[$seq]['yifadefen'] . "/" . $all[$seq]['yifachenggong']);
			@$all[$seq]['s2'] = self::add_percentage($all[$seq]['erfadefen'] . "/" . $all[$seq]['erfa']);
			@$all[$seq]['bp_percent'] = self::add_percentage($all[$seq]['pofa'] . "/" . $all[$seq]['pofajihui']);
			@$all[$seq]['rp_percent'] = self::add_percentage($all[$seq]['oppo_faqiudiufen'] . "/" . $all[$seq]['oppo_faqiu']);
			@$all[$seq]['sg_percent'] = self::add_percentage($all[$seq]['baofa'] . "/" . $all[$seq]['faqiuju']);
			@$all[$seq]['dura'] = date('H:i:s', strtotime("2021-1-1 0:0:0 +" . $seconds . " seconds"));

			$stat[0][] = [
				'dura' => $all[$seq]['dura'],
				'ace' => $all[$seq]['ace'],
				'df' => $all[$seq]['df'],
				's1%' => $all[$seq]['s1_percent'],
				's1' => $all[$seq]['s1'],
				's2' => $all[$seq]['s2'],
				'bp%' => $all[$seq]['bp_percent'],
				'sg%' => $all[$seq]['sg_percent'],
				'rp%' => $all[$seq]['rp_percent'],
				'tp' => $all[$seq]['tp'],
			];
		}

		ksort($stat);
			
		$ratio = self::convertToRatio($stat);

		return [
			'status' => 0,
			'stat' => $stat,
			'ratio' => $ratio,
			'score' => $score,
			'wl' => $wl,
			'bestof' => $bestof,
		];

	}

	protected function process_itf_event() {

		$url = "https://ls.fn.sportradar.com/itf/en/Europe:Berlin/gismo/match_info/$this->matchid";
		$html = file_get_contents($url);
		$XML = json_decode(trim($html), true);
		
		if (!isset($XML["doc"][0]["data"]["match"]) || !$XML["doc"][0]["data"]["match"]) {
			return ['status' => -1, 'errmsg' => __('stat.notice.error')];
		}
		$match = $XML["doc"][0]["data"]["match"];

		// 总盘数
		$bestof = 3;
		if (isset($match['bestofsets'])) $bestof = $match['bestofsets'];

		// 胜负
		$winner = 0;
		if ($match["status"]["name"] == "Ended"){
			if ($match["result"]["winner"] == "home"){
				$winner = 1;
			} else if ($match["result"]["winner"] == "away"){
				$winner = 2;
			}
		}

		if ($winner == 1) $wl = ["winner", "loser"];
		else if ($winner == 2) $wl = ["loser", "winner"];
		else $wl = ["unfinished", "unfinished"];

		// 比分
		$score = [];
		for ($i = 1; $i <= 5; ++$i){
			if (isset($match["periods"]["p".$i])){
				$a = $match["periods"]["p".$i]["home"];
				$b = $match["periods"]["p".$i]["away"];

				if (isset($match["periods"]["p" . ($i + 1)]) && $i < 5) {
					if ($a > $b) {$c = 'SetWinner'; $d = 'SetLoser';}
					else {$c = 'SetLoser'; $d = 'SetWinner';}
				} else {
					if ($winner > 0) {
						if ($winner == 1) {$c = 'SetWinner'; $d = 'SetLoser';}
						else {$c = 'SetLoser'; $d = 'SetWinner';};
					} else {
						$c = $d = '';
					}
				}

				if ($a == 6 && $b == 7){
					$a .= "<sup>" . $match["tiebreaks"]["p".$i]["home"] . "</sup>";
				} else if ($a == 7 && $b == 6){
					$b .= "<sup>" . $match["tiebreaks"]["p".$i]["away"] . "</sup>";
				}
			} else{
				$a = $b = "&nbsp;";
				$c = $d = '';
			}
			$score[] = [$a, $b, $c, $d];
		}

		$url = "https://ls.fn.betradar.com/itf/en/Europe:Berlin/gismo/match_detailsextended/$this->matchid";
		$html = file_get_contents($url);
		$XML = json_decode(trim($html), true);
		$match = $XML["doc"][0]["data"];

		if (isset($match['values'])) {
			$tmpdata = self::reviseItfData($match['values']);
		} else {
			$tmpdata = [];
		}

		$stat = [];

		foreach ($tmpdata as $set => $v1) {

			$stat[$set] = [];

			foreach ($v1 as $seq => $v2) {

				$dura = date('H:i:s', strtotime('0:0:0 +' . (@$v2['Duration'] + 0) . ' seconds'));
				$stat[$set][$seq]['dura'] = $dura;

				$ace = @$v2['Aces'] + 0;
				$stat[$set][$seq]['ace'] = $ace;

				$df = @$v2['Double Faults'] + 0;
				$stat[$set][$seq]['df'] = $df;

				if (isset($v2['Total Winners'])) {
					$stat[$set][$seq]['wi'] = $v2['Total Winners'] + 0;
				} else if (isset($v1[1 - $seq]['Total Winners'])) {
					$stat[$set][$seq]['wi'] = 0;
				}

				if (isset($v2['Unforced Errors'])) {
					$stat[$set][$seq]['ue'] = $v2['Unforced Errors'] + 0;
				} else if (isset($v1[1 - $seq]['Unforced Errors'])) {
					$stat[$set][$seq]['ue'] = 0;
				}

				if (isset($v2['Forced Errors'])) {
					$stat[$set][$seq]['fe'] = $v2['Forced Errors'] + 0;
				} else if (isset($v1[1 - $seq]['Forced Errors'])) {
					$stat[$set][$seq]['fe'] = 0;
				}

				$arr = explode("/", @$v2['1st Serve Successful']); $s1_percent = count($arr) == 3 ? self::add_percentage($arr[0] . "/" . $arr[2]) : self::add_percentage("0/0");
				$stat[$set][$seq]['s1%'] = $s1_percent;

				$arr = explode("/", @$v2['1st Serve Pts. Won']); $s1 = count($arr) == 3 ? self::add_percentage($arr[0] . "/" . $arr[2]) : self::add_percentage("0/0");
				$stat[$set][$seq]['s1'] = $s1;
	
				$arr = explode("/", @$v2['2nd Serve Pts. Won']); $s2 = count($arr) == 3 ? self::add_percentage($arr[0] . "/" . $arr[2]) : self::add_percentage("0/0");
				$stat[$set][$seq]['s2'] = $s2;

				$arr = explode("/", @$v2['Break Points Won']); $bp_percent = count($arr) == 2 ? self::add_percentage($arr[0] . "/" . $arr[1]) : self::add_percentage("0/0");
				$stat[$set][$seq]['bp%'] = $bp_percent;

				if (isset($v2['Net Points Won'])) {
					$arr = explode("/", @$v2['Net Points Won']);
					$stat[$set][$seq]['np%'] = count($arr) == 2 ? self::add_percentage($arr[0] . "/" . $arr[1]) : self::add_percentage("0/0");
				} else if (isset($v1[1 - $seq]['Net Points Won'])) {
					$stat[$set][$seq]['np%'] = 0;
				}

				if (isset($v2['Fastest 1st Serve'])) {
					$stat[$set][$seq]['f1f'] = [$v2['Fastest 1st Serve'] + 0, self::kilo2mile($v2['Fastest 1st Serve'] + 0)];
				}

				if (isset($v2['Average 1st Serve'])) {
					$stat[$set][$seq]['f1a'] = [$v2['Average 1st Serve'] + 0, self::kilo2mile($v2['Average 1st Serve'] + 0)];
				}

				if (isset($v2['Fastest 2nd Serve'])) {
					$stat[$set][$seq]['f2f'] = [$v2['Fastest 2nd Serve'] + 0, self::kilo2mile($v2['Fastest 2nd Serve'] + 0)];
				}

				if (isset($v2['Average 2nd Serve'])) {
					$stat[$set][$seq]['f2a'] = [$v2['Average 2nd Serve'] + 0, self::kilo2mile($v2['Average 2nd Serve'] + 0)];
				}

				$arr = explode("/", @$v2['Service Games Won']); $sg_percent = count($arr) == 3 ? self::add_percentage($arr[0] . "/" . $arr[2]) : self::add_percentage("0/0");
				$stat[$set][$seq]['sg%'] = $sg_percent;

				$arr = explode("/", @$v2['Receiver Points Won']); $rp_percent = count($arr) == 3 ? self::add_percentage($arr[0] . "/" . $arr[2]) : self::add_percentage("0/0");
				$stat[$set][$seq]['rp%'] = $rp_percent;

				$mgr = intval(@$v2['Max Games in a Row']);
				$stat[$set][$seq]['mgr'] = $mgr;

				$mpr = intval(@$v2['Max Points in a Row']);
				$stat[$set][$seq]['mpr'] = $mpr;

				$tp = intval(@$v2['Points won']);
				$stat[$set][$seq]['tp'] = $tp;
			}
		}

		$ratio = self::convertToRatio($stat);
//		$ratio = "";
		return [
			'status' => 0,
			'stat' => $stat,
			'ratio' => $ratio,
			'score' => $score,
			'wl' => $wl,
			'bestof' => $bestof,
		];
	}

	protected function convertToRatio($stat) {

		$ratio = [];

		foreach ($stat as $seq => $set) {

			$ratio[$seq] = [ [], [] ];

			foreach ($set[0] as $k => $v) {

				if ($k == 'dura') continue;
				$v1 = isset($set[0][$k]) && !is_array($set[0][$k]) ? $set[0][$k] : @$set[0][$k][0];
				$v2 = isset($set[1][$k]) && !is_array($set[1][$k]) ? $set[1][$k] : @$set[1][$k][0];

				if (strpos($v1, "%") !== false) {
					$v1 = preg_replace('/%.*$/', '', $v1) + 0;
					$v2 = preg_replace('/%.*$/', '', $v2) + 0;
				} else {
					$denominator = $v1 + $v2;
					if ($denominator == 0) {$v1 = $v2 = 0;}
					else {
						$v1 = round($v1 / $denominator * 100);
						$v2 = round($v2 / $denominator * 100);
					}

					if ($v1 + $v2 > 100) {
						$v2 = 100 - $v1;
					}
				}

				$v1 = -$v1;

				$ratio[$seq][0][] = $v1;
				$ratio[$seq][1][] = $v2;

			}
		}

		return $ratio;
	}

	protected function add_percentage($ratio){

		if ($ratio == "0") return "0% (0/0)";

		$tmp = explode("/", $ratio);
		if (count($tmp) == 2){
			$a = $tmp[0];
			$b = $tmp[1];
		} else if (count($tmp) == 3) {
			$a = $tmp[0];
			$b = $tmp[2];
		} else {
			return "";
		}

		if ($b == 0)
			return "0% (0/0)";
		else
			return round($a/$b*100) . "% (" . $a . "/" . $b . ")";
	}

	protected function kilo2mile($speed) {

		return (int)($speed / $this->mile_convert);

	}

	protected function mile2kilo($speed) {

		return (int)($speed * $this->mile_convert);

	}

	protected function meter2feet($length) {

		return (int)($length / $this->feet_convert);

	}

	protected function feet2meter($length) {

		return (int)($length / $this->feet_convert);

	}

	protected function reviseItfData($a) {

		// 相同name的，value merge到一起。使得下标0表示全部，下标1到N到表示各盘
		foreach ($a as $k => $v) {
			if (!isset($v['name']) || !$v['name']) continue;
			if (!isset($b[$v['name']])) $b[$v['name']] = [];
			$b[$v['name']] = array_merge_recursive($b[$v['name']], $v['value']);
		}

		foreach ($b as $c => $d) {
			if (!is_array($d)) continue;
			if ($c == 'Duration') {
				if (isset($d['periods']) && count($d['periods']) >= 1) {
					$t = &$d['periods'];
					if (!is_array($t)) $t = [$t];
					$sum = array_sum($t);
					array_unshift($t, $sum);
					$b[$c]['home'] = $t;
					$b[$c]['away'] = $t;
					unset($b[$c]['periods']);
				}
			} else {
				foreach (['home', 'away'] as $e) {
					if (isset($d[$e])) {
						// 下标0是一个数，但是下标1却是一组斜杠分隔的数，这种情况，把下标1分拆到下标1到N
						if (is_array($d[$e]) && count($d[$e]) == 2 && !preg_match('/\//', $d[$e][0]) && preg_match('/^\d+(\/\d+)*$/', $d[$e][1])) {
							$t = explode("/", $d[$e][1]);
							if (count($t) > 1) {
								for ($i = 0 ; $i < count($t); ++$i) {
									$b[$c][$e][$i + 1] = $t[$i];
								}
							}
						// 只有一个数，那说明
						} else if (!is_array($d[$e])) {
							$t = explode("/", $d[$e]);
							$b[$c][$e] = []; $b[$c][$e][0] = 0;
							if (count($t) >= 1) {
								for ($i = 0 ; $i < count($t); ++$i) {
									$b[$c][$e][$i + 1] = intval($t[$i]);
								}
							}
						}
					}
				}
			}
		}


		$c = [];
		foreach ($b as $k1 => $v1) {
			foreach ($v1 as $k2 => $v2) {
				if (!in_array($k2, ['home', 'away'])) continue;
				if ($k2 == 'home') $k2 = 0; else if ($k2 == 'away') $k2 = 1;
				foreach ($v2 as $k3 => $v3) {
					@$c[$k3][$k2][$k1] = $v3;
				}
			}
		}
		return $c;
	}

}
