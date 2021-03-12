<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
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
	protected $fsid;

	protected $mile_convert;
	protected $feet_convert;

	public function __construct() {

		$this->mile_convert = 1.609344;
		$this->feet_convert = 0.3048;

	}

	public function query(Request $req, $lang, $home, $away) {

		App::setLocale($lang);

		$ret = [];

		$this->id1 = $req->input('home', 'CG80');
		$this->id2 = $req->input('away', 'N409');
		$this->p1 = urldecode($req->input('p1', 'Coric'));
		$this->p2 = urldecode($req->input('p2', 'Nadal'));
		$this->eid = $req->input('eid', 'M993');
		$this->matchid = $req->input('matchid', 'MS001');
		$this->year = $req->input('year', '2017');
		$this->fsid = $req->input('fsid', '');

		// 处理数据统计

		if ($this->eid == 'DC' || $this->eid == 'FC') {
			$ret = self::process_itf_event();
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

		// 处理pbp
		if (in_array($this->eid, ['AO'])) {
			$this->pbp_ao($ret);
		} else if ($this->fsid != '') {
			$this->pbp_flashscore($ret);
		} else if (preg_match('/^[MW]-ITF-/', $this->eid)) {
			$this->pbp_itf_event($ret);
		}

		// 处理头像
		$merge_arr = explode(',', join(',', [$this->id1, $this->id2]));
		$players = self::get_player_info($merge_arr);

		$ret['players'] = $players;
		return $ret;

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

		// 小分
		$gamePoint = ["", ""]; $serving = 0;

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
					$c = "set-winner";
					$d = "set-loser";
				} else {
					$c = "set-loser";
					$d = "set-winner";
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
			'point' => [$gamePoint[0], $gamePoint[1], $serving],
		];
	}

	protected function process_wc() {

		if ($this->eid == "WC"){
			$prefix = "https://www.wimbledon.com/en_GB/scores/feeds/2019/matches/complete/";
		} else if ($this->eid == "UO"){
			$prefix = "https://www.usopen.org/en_US/scores/feeds/2019/matches/complete/";
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

		// 小分
		$gamePoint = ["", ""]; $serving = 0;

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
					$c = "set-winner";
					$d = "set-loser";
				} else {
					$c = "set-loser";
					$d = "set-winner";
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
			'point' => [$gamePoint[0], $gamePoint[1], $serving],
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

		// 小分
		$gamePoint = ["", ""]; $serving = 0;

		// 比分
		$score = [];

		$s1 = $sc[0];
		$s2 = $sc[1];
		for ($i = 2; $i < 7; ++$i){
			if ($s1[$i] !== "" && $s2[$i] !== ""){
				if (strpos($s1[$i], "loser") !== false) {
					$d = "set-winner";
					$c = "set-loser";
				} else if (strpos($s2[$i], "loser") !== false) {
					$d = "set-loser";
					$c = "set-winner";
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


		ksort($stat);
		$this->unset_nodata_stat_item($stat);
		//print_r($stat);

		$ratio = self::convertToRatio($stat);

		return [
			'status' => 0,
			'stat' => $stat,
			'ratio' => $ratio,
			'score' => $score,
			'wl' => $wl,
			'bestof' => $bestof,
			'point' => [$gamePoint[0], $gamePoint[1], $serving],
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

		// 小分情况
		$point1 = $point2 = $gamePoint = "";
		if ($match[0]["MatchState"] != "U") {
			$point1 = @$match[0]['PointA'];
			$point2 = @$match[0]['PointB']; 
			$serving = @$match[0]['Serve'] == 'A' ? 1 : (@$match[0]['Serve'] == 'B' ? 2 : 0);
			$gamePoint = $this->revise_point($point1, $point2);
		}
		
		// 每盘比分
		$score = [];
		for ($i = 1; $i <= 5; ++$i){
			if (isset($match[0]["ScoreSet" . $i . "A"]) && $match[0]["ScoreSet" . $i . "A"] != ""){
				$a = intval($match[0]["ScoreSet" . $i . "A"]);
				$b = intval($match[0]["ScoreSet" . $i . "B"]);

				if ($i < 5 && $match[0]["ScoreSet" . ($i + 1) . "A"] !== "") {
					if ($a > $b) {$c = 'set-winner'; $d = 'set-loser';}
					else {$c = 'set-loser'; $d = 'set-winner';}
				} else {
					if ($winner > 0) {
						if ($winner == 1) {$c = 'set-winner'; $d = 'set-loser';}
						else {$c = 'set-loser'; $d = 'set-winner';};
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
		$total_dura = @$match[0]["MatchTimeTotal"];

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
				$arr = explode(":", @$SET["settime"]);
				$seconds += intval(@$arr[0]) * 3600 + intval(@$arr[1]) * 60 + intval(@$arr[2]);
			}
		}

		foreach (["a", "b"] as $seq){
			@$all[$seq]['s1_percent'] = self::add_percentage($all[$seq]['yifachenggong'] . "/" . $all[$seq]['faqiu']);
			@$all[$seq]['s1'] = self::add_percentage($all[$seq]['yifadefen'] . "/" . $all[$seq]['yifachenggong']);
			@$all[$seq]['s2'] = self::add_percentage($all[$seq]['erfadefen'] . "/" . $all[$seq]['erfa']);
			@$all[$seq]['bp_percent'] = self::add_percentage($all[$seq]['pofa'] . "/" . $all[$seq]['pofajihui']);
			@$all[$seq]['rp_percent'] = self::add_percentage($all[$seq]['oppo_faqiudiufen'] . "/" . $all[$seq]['oppo_faqiu']);
			@$all[$seq]['sg_percent'] = self::add_percentage($all[$seq]['baofa'] . "/" . $all[$seq]['faqiuju']);
			//@$all[$seq]['dura'] = date('H:i:s', strtotime("2021-1-1 0:0:0 +" . $seconds . " seconds"));
			@$all[$seq]['dura'] = $total_dura;

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
		$this->unset_nodata_stat_item($stat);
			
		$ratio = self::convertToRatio($stat);

		return [
			'status' => 0,
			'stat' => $stat,
			'ratio' => $ratio,
			'score' => $score,
			'wl' => $wl,
			'bestof' => $bestof,
			'point' => [$gamePoint[0], $gamePoint[1], $serving],
		];
	}

	protected function process_atp_tour() {
		$url = "https://www.atptour.com/-/ajax/MatchStats/en/$this->year/$this->eid/$this->matchid";
		$html = file_get_contents($url);
		$match = json_decode($html, true);
		if (!$match || !isset($match["Match"])) return ['status' => -1, 'errmsg' => __('stat.notice.error')];
		$match = $match["Match"];

		// 总盘数
		$bestof = $match["NumberOfSets"];

		// 胜负
		$winner = 0;
		if ($match["Status"] == "F"){
			if ($match["Winner"] == $match["PlayerTeam1"]["PlayerId"]){
				$winner = 1;
			} else if ($match["Winner"] == $match["PlayerTeam2"]["PlayerId"]){
				$winner = 2;
			}
		}
		if ($winner == 1) $wl = ["winner", "loser"];
		else if ($winner == 2) $wl = ["loser", "winner"];
		else $wl = ["unfinished", "unfinished"];

		// 小分情况
		$point1 = $point2 = $gamePoint = "";
		$point1 = @$match['PlayerTeam1']['GamePointsPlayerTeam'];
		$point2 = @$match['PlayerTeam2']['GamePointsPlayerTeam']; 
		$serving = 0;
		if ($match['LastServer']) {
			if ($match['LastServer'] == $match['PlayerTeam1']['PlayerId'] || $match['LastServer'] == $match['PlayerTeam1']['PartnerId']) {
				$serving = 1;
			} else if ($match['LastServer'] == $match['PlayerTeam2']['PlayerId'] || $match['LastServer'] == $match['PlayerTeam2']['PartnerId']) {
				$serving = 2;
			}
		}
		$gamePoint = $this->revise_point($point1, $point2);

		// 每盘比分
		$score = [];
		for ($i = 1; $i <= 5; ++$i){
			if (isset($match["PlayerTeam1"]["Sets"][$i])){
				$a = intval($match["PlayerTeam1"]["Sets"][$i]["SetScore"]);
				$b = intval($match["PlayerTeam2"]["Sets"][$i]["SetScore"]);

				if ($i < 5 && isset($match["PlayerTeam1"]["Sets"][$i + 1])) {
					if ($a > $b) {$c = 'set-winner'; $d = 'set-loser';}
					else {$c = 'set-loser'; $d = 'set-winner';}
				} else {
					if ($winner > 0) {
						if ($winner == 1) {$c = 'set-winner'; $d = 'set-loser';}
						else {$c = 'set-loser'; $d = 'set-winner';};
					} else {
						$c = $d = '';
					}
				}

				if (($a == 6 && $b == 7) || ($a == 0 && $b == 1 && $i == $bestof && $winner == 2) || ($a == 3 && $b == 4 && $this->eid == '7696')){
					$a .= "<sup>". $match["PlayerTeam1"]["Sets"][$i]["TieBreakScore"] ."</sup>";
				} else if (($a == 7 && $b == 6) || ($a == 1 && $b == 0 && $i == $bestof && $winner == 1) || ($a == 4 && $b == 3 && $this->eid == '7696')){
					$b .= "<sup>". $match["PlayerTeam2"]["Sets"][$i]["TieBreakScore"] ."</sup>";
				}
			} else{
				$a = $b = "&nbsp;";
				$c = $d = '';
			}
			$score[] = [$a, $b, $c, $d];
		}

		// 技术统计
		$stat = [];

		$all[0] = $all[1] = [];
		for ($set = 0; $set <= 5; ++$set){
			if (isset($match["PlayerTeam1"]["Sets"][$set])){
				$stat[$set] = [];
				foreach ([1, 2] as $seq){
					$oppo = 3 - $seq;

					$ace = @$match["PlayerTeam" . $seq]["Sets"][$set]["Stats"]["ServiceStats"]["Aces"]["Number"];
					$df = @$match["PlayerTeam" . $seq]["Sets"][$set]["Stats"]["ServiceStats"]["DoubleFaults"]["Number"];
					$tp = @$match["PlayerTeam" . $seq]["Sets"][$set]["Stats"]["PointStats"]["TotalPointsWon"]["Dividend"];

					$faqiu = @$match["PlayerTeam" . $seq]["Sets"][$set]["Stats"]["ServiceStats"]["FirstServe"]["Divisor"];
					$yifachenggong = @$match["PlayerTeam" . $seq]["Sets"][$set]["Stats"]["ServiceStats"]["FirstServe"]["Dividend"];
					$yifadefen = @$match["PlayerTeam" . $seq]["Sets"][$set]["Stats"]["ServiceStats"]["FirstServePointsWon"]["Dividend"];
					$erfa = $faqiu - $yifachenggong;
					$erfadefen = @$match["PlayerTeam" . $seq]["Sets"][$set]["Stats"]["ServiceStats"]["SecondServePointsWon"]["Dividend"];
					$faqiudefen = $yifadefen + $erfadefen;
					$pofa = @$match["PlayerTeam" . $seq]["Sets"][$set]["Stats"]["ReturnStats"]["BreakPointsConverted"]["Dividend"];
					$pofajihui = @$match["PlayerTeam" . $seq]["Sets"][$set]["Stats"]["ReturnStats"]["BreakPointsConverted"]["Divisor"];
					$faqiuju = @$match["PlayerTeam" . $seq]["Sets"][$set]["Stats"]["ServiceStats"]["ServiceGamesPlayed"]["Number"];

					$oppo_faqiudiufen = $tp - $faqiudefen;
					$oppo_faqiu = @$match["PlayerTeam" . $oppo]["Sets"][$set]["Stats"]["ServiceStats"]["FirstServe"]["Divisor"];
					$baofa = $faqiuju - @$match["PlayerTeam" . $oppo]["Sets"][$set]["Stats"]["ReturnStats"]["BreakPointsConverted"]["Dividend"];

					$s1_percent = self::add_percentage($yifachenggong . "/" . $faqiu);
					$s1 = self::add_percentage($yifadefen . "/" . $yifachenggong);
					$s2 = self::add_percentage($erfadefen . "/" . $erfa);
					$bp_percent = self::add_percentage($pofa . "/" . $pofajihui);
					$rp_percent = self::add_percentage($oppo_faqiudiufen . "/" . $oppo_faqiu);
					$sg_percent = self::add_percentage($baofa . "/" . $faqiuju);

					$dura = @$match["PlayerTeam" . $seq]["Sets"][$set]["Stats"]["Time"];

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

		if ($stat[0][0]["dura"] == "") {
			$seconds = 0;
			for ($set = 1; $set <= 5; ++$set){
				if (isset($match["PlayerTeam1"]["Sets"][$set]["Stats"])){
					$arr = explode(":", @$match["PlayerTeam1"]["Sets"][$set]["Stats"]["Time"]);
					$seconds += intval(@$arr[0]) * 3600 + intval(@$arr[1]) * 60 + intval(@$arr[2]);
				}
			}
			$dura = date('H:i:s', strtotime("2021-1-1 0:0:0 +" . $seconds . " seconds"));
			$stat[0][0]["dura"] = $dura;
			$stat[0][1]["dura"] = $dura;
		}

		ksort($stat);
		$this->unset_nodata_stat_item($stat);
			
		$ratio = self::convertToRatio($stat);

		return [
			'status' => 0,
			'stat' => $stat,
			'ratio' => $ratio,
			'score' => $score,
			'wl' => $wl,
			'bestof' => $bestof,
			'point' => [$gamePoint[0], $gamePoint[1], $serving],
		];

	}

	/*
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
					if ($a > $b) {$c = 'set-winner'; $d = 'set-loser';}
					else {$c = 'set-loser'; $d = 'set-winner';}
				} else {
					if ($winner > 0) {
						if ($winner == 1) {$c = 'set-winner'; $d = 'set-loser';}
						else {$c = 'set-loser'; $d = 'set-winner';};
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
	*/

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

		// 小分
		$gamePoint = ["", ""]; $serving = 0;

		// 比分
		$score = [];
		for ($i = 1; $i <= 5; ++$i){
			if (isset($match["periods"]["p".$i])){
				$a = $match["periods"]["p".$i]["home"];
				$b = $match["periods"]["p".$i]["away"];

				if (isset($match["periods"]["p" . ($i + 1)]) && $i < 5) {
					if ($a > $b) {$c = 'set-winner'; $d = 'set-loser';}
					else {$c = 'set-loser'; $d = 'set-winner';}
				} else {
					if ($winner > 0) {
						if ($winner == 1) {$c = 'set-winner'; $d = 'set-loser';}
						else {$c = 'set-loser'; $d = 'set-winner';};
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

		$url = "https://ls.fn.sportradar.com/itf/en/Europe:Berlin/gismo/match_detailsextended/$this->matchid";
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

		$this->unset_nodata_stat_item($stat);

		$ratio = self::convertToRatio($stat);
		return [
			'status' => 0,
			'stat' => $stat,
			'ratio' => $ratio,
			'score' => $score,
			'wl' => $wl,
			'bestof' => $bestof,
			'point' => [$gamePoint[0], $gamePoint[1], $serving],
		];
	}

	protected function pbp_ao(&$ret) {

		$pbp = [];
		$param = [];
		$serve = [];

		$url = "https://itp-ao.infosys-platforms.com/api/match-beats/data/year/" . $this->year . "/eventId/580/matchId/" . substr($this->matchid, 0, 5);
		$html = file_get_contents($url);
		if (!$html) return;

		$json = json_decode($html, true);
		if (!$json) return;

		$server = $winner = 0;

		$smallDot = 1;
		$bigDot = 3;

		foreach ($json['setData'] as $SET) {

			$set = $SET['set'];
			if ($set == 0) {
				return;
			}
			
			$game1 = $game2 = 0;
			$point1 = $point2 = 0;
			$x = 0; $y = 0; // x表示第几分，y增大或者减少，表示p1或者p2得分

/*----------------------第一次输出pbp,param,serve---------------------*/
//			$pbp[$set][] = [$x, $y, $smallDot, [], '0-0'];
//			$param[$set] = ["min" => 0, "max" => 0, "markLines" => []]; // 记录每盘最大值最小值，每局结束的x值以及对应的局数
//			$serve[$set] = [];
/*---------------------------------------------------------------------*/

			foreach ($SET['gameData'] as $GAME) {
				$is_broken = false;
				foreach ($GAME['pointData'] as $POINT) {
					++$x;

					$win_person = $POINT['scorer'];
					$serve_person = $POINT['server']; 
					if ($win_person == 1) { // p1得分，y自增，反之自减
						++$y;
					} else {
						--$y;
					}
					$point1 = $POINT['tm1GameScore'];
					$point2 = $POINT['tm2GameScore'];
					$pointflag = $POINT['result'];
					if ($pointflag == "N") $pointflag = "";
					$flag1 = ''; $flag2 = '';
					$bsm1 = []; $bsm2 = [];
					if (in_array($pointflag, ['A', 'W'])) { // ace, winner 记在得分者头上
						if ($pointflag == 'W') $pointflag = "👍";
						${'flag' . $win_person} = $pointflag;
					} else if (in_array($pointflag, ['UE', 'FE'])) {
						if ($pointflag == 'UE') $pointflag = "👎";
						${'flag' . (3 - $win_person)} = $pointflag;
					} else {
						${'flag' . $win_person} = $pointflag;
					}
					$shot = $POINT['tm1Rally'] + $POINT['tm2Rally'];
					$serve_speed = $POINT['serveSpeed'];

					if (isset($POINT['brkPts'])	&& $POINT['brkPts'] > 0) {
						$bp_num = $POINT['brkPts'];
						${'bsm' . (3 - $serve_person)}[] = ($bp_num > 1 ? $bp_num : '') . 'BP';
					} else {
						$bp_num = null;
					}
					if (isset($POINT['isBrkPt']) && $POINT['isBrkPt'] === true && ($point1 == "GAME" || $point2 == "GAME")) {
						$is_broken = true;
					}

					if ($point1 == "GAME") {$point1 = "🎾"; $point2 = '';}
					if ($point2 == "GAME") {$point2 = "🎾"; $point1 = '';}
					if ($point1 == 'AD' && $point2 == '40') {$point1 = 'A'; $point2 = '';}
					if ($point2 == 'AD' && $point1 == '40') {$point2 = 'A'; $point1 = '';}

					$pbp[$set][] = ['x' => $x * 2 - 1, 'y' => 10000, 's' => 0, 'w' => 0, 'p1' => '', 'p2' => '', 'b1' => [], 'b2' => [], 'f1' => '', 'f2' => '', 'sv' => 0, 'ss' => 0];
					$pbp[$set][] = [
						'x' => $x * 2,
						'y' => $y,
						's' => $serve_person,
						'w' => $win_person,
						'p1' => $point1,
						'p2' => $point2,
						'b1' => $bsm1,
						'b2' => $bsm2,
						'f1' => $flag1,
						'f2' => $flag2,
						'sv' => $shot,
						'ss' => $serve_speed,
					];
				}

				if (!$GAME['isTieBreak']) {
					$game_serve_person = $serve_person;
				} else {
					$game_serve_person = 0;
				}

				$game_win_person = $GAME['gameWinner'];
				$game1 = $GAME['tm1SetScore'];
				$game2 = $GAME['tm2SetScore'];
				$param[$set][] = [
					'x' => ($x + 0.5) * 2, // 划分一局的线,
					'g1' => $game1,
					'g2' => $game2,
					's' => $GAME['isTieBreak'] ? 0 : $game_serve_person,
					'w' => $game_win_person,
					'tb' => $GAME['isTieBreak'],
					'b' => $is_broken,
				];
					
				

/*----------------------每一局结束时输出pbp,输出markArea---------------------*/
//					$pbp[$set][] = [$x, $y, $smallDot, [], ''];
//					$param[$set]['markLines'][] = [$last_x, $x, $game1 . '-' . $game2, $winner];  // 表示从last_x到x这段范围的局分，以及底色
/*--------------------------------------------------------------------*/


/*------------------------------一局结束输出serve-------------------------------*/
//					$serve[$set][] = [floor(($last_x + $x) / 2), $server, $servePerson, $holdOrLost, (0.5 - $server % 2) * 2];
/*----------------------------------------------------------------------------------*/
			} // endforeach GAME

			// 一盘结束多加两个虚拟点，用以容纳最后一条得分线
			foreach (range(0, 1) as $r) {
				$pbp[$set][] = ['x' => (++$x) * 2, 'y' => 10000, 's' => 0, 'w' => 0, 'p1' => '', 'p2' => '', 'b1' => [], 'b2' => [], 'f1' => '', 'f2' => '', 'sv' => 0, 'ss' => 0];
			}

		} // endforeach SET

		$ret['pbp'] = $pbp;
		$ret['markLines'] = $param;
	}

	protected function pbp_flashscore(&$ret) {

		$pbp = [];
		$param = [];
		$serve = [];

		$url = "http://d.livescore.in/x/feed/d_mh_".$this->fsid."_en_4";
		$headers = [
			'Referer: http://d.livescore.in/x/feed/proxy-local',
			'X-Fsign: SW9D1eZo',
		];
		//初始化
		$ch = curl_init();
		//设置选项，包括URL
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $url);
		$html = curl_exec($ch);
		$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($response_code > 400) return;

		//$html = file_get_contents("/home/ubuntu/web/1.php");
		if (!$html) return; 
		if (strlen($html) < 5000) return;
		$DOM = str_get_html($html);
		if (!$DOM) return;

		$set_begin = false;
		$server = $winner = 0;

		$in_progress = false;

		$smallDot = 1;
		$bigDot = 3;

		$set = 0;

		foreach ($DOM->find('.parts-first') as $SET) {

			++$set;

			$game1 = $game2 = 0;
			$point1 = $point2 = 0;
			$x = 0; $y = 0; // x表示第几分，y增大或者减少，表示p1或者p2得分

			/*----------------------第一次输出pbp,param,serve---------------------*/
			//$pbp[$set][] = [$x, $y, $smallDot, [], '0-0'];
			//$param[$set] = ["min" => 0, "max" => 0, "markLines" => []]; // 记录每盘最大值最小值，每局结束的x值以及对应的局数
			//$serve[$set] = [];
			/*---------------------------------------------------------------------*/

			$last_x = 0;
			$last_key = count($SET->find('tr')) - 1;

			$tb_begin = false;
			$in_progress = false;
			foreach ($SET->find('tr') as $key => $line) {

				if (strpos($line->innertext, "Point by point") !== false) {
					$set_begin = true;
					continue;
				}

				if (strpos($line->innertext, "Tiebreak") !== false && $set_begin) {
					$tb_begin = true;
					$point1 = $point2 = 0;
					continue;
				}

				$class = $line->class;
				if (!$class || $class == "current-game-empty-row") continue;
				$class = str_replace("odd", "", $class);
				$class = str_replace("even", "", $class);
				$class = trim($class);

				if ($class == "fifteens_available" || strpos($line->innertext, "Current game") !== false) { // 一局总结

					$server = 0;
					if (strpos($line->innertext, "Current game") !== false) {
						if (strpos($line->children(0)->innertext, 'visible') !== false && strpos($line->children(2)->innertext, 'visible') === false) {
							$server = 1;
						} else if (strpos($line->children(2)->innertext, 'visible') !== false && strpos($line->children(0)->innertext, 'visible') === false) {
							$server = 2;
						}
					} else {
						if ($line->children(1)->innertext != "" && $line->children(3)->innertext == "") {
							$server = 1;
						} else if ($line->children(3)->innertext != "" && $line->children(1)->innertext == "") {
							$server = 2;
						}
					}

					// 当前game正在进行时，winner置0，否则置1或2
					if (strpos($line->innertext, "Current game") !== false) {
						$winner = 0;
					} else if (strpos($line->innertext, "LOST SERVE") !== false) {
						$winner = 3 - $server;
					} else {
						$winner = $server;
					}

					// 本局已结束时才记录game1, game2
					if ($winner > 0) {
						$tmp = $line->children(2)->innertext;
						$tmp = preg_replace('/<[^>]*>/', "", $tmp);
						$tmp_arr = explode("-", $tmp);
						$game1 = trim($tmp_arr[0]) + 0;
						$game2 = trim($tmp_arr[1]) + 0;
					}

					$point1 = $point2 = 0;

					if (strpos($line->innertext, "Current game") !== false) {
						$in_progress = true;
					} else {
						$in_progress = false;
					}

				} else if ($class == "fifteen") { // 每发球局得分

					$tmp = $line->children(0)->innertext;
					$tmp_arr = explode(",", $tmp);
					foreach ($tmp_arr as $eachpoint) {
						++$x;
						$bp = $sp = $mp = false;
						if (strpos($eachpoint, "BP") !== false) $bp = true;
						if (strpos($eachpoint, "SP") !== false) $sp = true;
						if (strpos($eachpoint, "MP") !== false) $mp = true;
						$eachpoint = preg_replace('/<[^>]*>/', "", $eachpoint);
						$eachpoint = preg_replace('/[BSM]P/', "", $eachpoint);
						$eachpoint = str_replace("A", "50", $eachpoint);
						if ($eachpoint == '0:0') continue;

						$ep_arr = explode(":", $eachpoint);
						$p1 = intval(trim($ep_arr[0]));
						$p2 = intval(trim($ep_arr[1]));

						$pointWinner = 0;
						if ($p1 == $point1) {
							if ($p2 > $point2) { // p2增大，算p2得分
								--$y;
								$pointWinner = 2;
							} else { // p2减少，从ad变成40，算p1得分
								++$y;
								$pointWinner = 1;
							}
						} else if ($p2 == $point2) {
							if ($p1 > $point1) { // p1增大，算p1得分
								++$y;
								$pointWinner = 1;
							} else {
								--$y;
								$pointWinner = 2;
							}
						}
						//if ($y > $param[$set]['max']) $param[$set]['max'] = $y;
						//else if ($y < $param[$set]['min']) $param[$set]['min'] = $y;

						$dotValue = [];
						if ($bp || $sp || $mp) {
							$bsm_count = ceil(abs($p1 - $p2) / 15);
							if ($bsm_count <= 1) $bsm_count = ""; 
							if ($bp) $dotValue[] = $bsm_count . 'BP';
							if ($sp) $dotValue[] = $bsm_count . 'SP';
							if ($mp) $dotValue[] = $bsm_count . 'MP';
						}
						if ($pointWinner == 1) {
							$bsm1 = $dotValue;
							$bsm2 = [];
						} else {
							$bsm2 = $dotValue;
							$bsm1 = [];
						}

						$point1 = $p1;
						$point2 = $p2;
						if ($p1 == 50 && $p2 == 40) {$p1 = 'A'; $p2 = '';}
						else if ($p1 == 40 && $p2 == 50) {$p2 = 'A'; $p1 = '';}

						/*-----------------------------每分都输出pbp----------------------------*/
						//$pbp[$set][] = [$x, $y, $dotSize, $dotValue, str_replace("50", "AD", $point1).'-'.str_replace("50", "AD", $point2)];
						$pbp[$set][] = ['x' => $x * 2 - 1, 'y' => 10000, 's' => 0, 'w' => 0, 'p1' => '', 'p2' => '', 'b1' => [], 'b2' => [], 'f1' => '', 'f2' => '', 'sv' => 0, 'ss' => 0];
						$pbp[$set][] = [
							'x' => $x * 2,
							'y' => $y,
							's' => $server,
							'w' => $pointWinner,
							'p1' => $p1,
							'p2' => $p2,
							'b1' => $bsm1,
							'b2' => $bsm2,
							'f1' => "",
							'f2' => "",
							'sv' => 0,
							'ss' => 0,
						];
						/*--------------------------------------------------------------------*/

					} // foreach eachpoint

					// winner > 0 表示本局结束，此时在局尾增加一分，并记下色块
					if (!$in_progress) {

						++$x;
						$pointWinner = $winner;
						$p1 = $p2 = '';
						if ($winner == 1) {
							++$y;
							$p1 = '🎾';
 						} else if ($winner == 2) {
							--$y;
							$p2 = '🎾';
						}
						//if ($y > $param[$set]['max']) $param[$set]['max'] = $y;
						//else if ($y < $param[$set]['min']) $param[$set]['min'] = $y;

						/*
						if ($winner == 1) {
							$color = Config::get('const.sideColor.home');
						} else {
							$color = Config::get('const.sideColor.away');
						}
						*/

						/*----------------------每一局结束时输出pbp,输出markArea---------------------*/
						//$pbp[$set][] = [$x, $y, $smallDot, [], ''];
						$pbp[$set][] = ['x' => $x * 2 - 1, 'y' => 10000, 's' => 0, 'w' => 0, 'p1' => '', 'p2' => '', 'b1' => [], 'b2' => [], 'f1' => '', 'f2' => '', 'sv' => 0, 'ss' => 0];
						$pbp[$set][] = [
							'x' => $x * 2,
							'y' => $y,
							's' => $server,
							'w' => $pointWinner,
							'p1' => $p1,
							'p2' => $p2,
							'b1' => [],
							'b2' => [],
							'f1' => "",
							'f2' => "",
							'sv' => 0,
							'ss' => 0,
						];
						//$param[$set]['markLines'][] = [$last_x, $x, $game1 . '-' . $game2, $color];  // 表示从last_x到x这段范围的局分，以及底色
						//$param[$set]['markLines'][] = [$last_x, $x, $game1 . '-' . $game2, $winner];  // 表示从last_x到x这段范围的局分，以及底色
						if ($winner != $server && $winner > 0) $isBroken = true;
						else $isBroken = false;
						$param[$set][] = [
							'x' => ($x + 0.5) * 2, // 划分一局的线,
							'g1' => $game1,
							'g2' => $game2,
							's' => $server,
							'w' => $winner,
							'tb' => false,
							'b' => $isBroken,
						];
						/*--------------------------------------------------------------------*/
					}

					/*
					if ($server == 1) {
						$color = Config::get('const.sideColor.home'); 
						$servePerson = 'HOME' . ' ' . __('pbp.lines.toServe');
					} else if ($server == 2) {
						$color = Config::get('const.sideColor.away');
						$servePerson = 'AWAY' . ' ' . __('pbp.lines.toServe');
					}
					*/

					/*
					if ($winner == $server && $winner > 0) $holdOrLost = __('pbp.lines.holdServe');
					else if ($winner != $server && $winner > 0) $holdOrLost = __('pbp.lines.lostServe');
					else $holdOrLost = __('pbp.lines.inServe');
					*/

					/*----------------------不管一局有没有结束都输出serve------------------------------*/
					//$serve[$set][] = [floor(($last_x + $x) / 2), $server, $servePerson, $holdOrLost, ($server - 1.5) * 2];
					/*----------------------------------------------------------------------------------*/

					if ($winner > 0) {
						$last_x = $x;
					}

					$in_progress =true; // 每局结束把in_progres置true，如果下面局有局分或者有抢七分，则会被重新置false。否则就认为下面一局是进行中

				} else { // 抢七或抢十每分

					$eachpoint = $line->innertext;
					$bp = $sp = $mp = false;
					if (strpos($eachpoint, "BP") !== false) $bp = true;
					if (strpos($eachpoint, "SP") !== false) $sp = true;
					if (strpos($eachpoint, "MP") !== false) $mp = true;

					$tmp = preg_replace('/<[^>]*>/', "", $line->children(2));
					//echo $tmp."\n";
					$ep_arr = explode("-", $tmp);
					$p1 = intval(trim($ep_arr[0]));
					$p2 = intval(trim($ep_arr[1]));

					// 如果出现 1-0 0-1之类，强制开启tb模式
					if (($p1 == 1 || $p2 == 1) && $tb_begin == false) {
						$tb_begin = true;
						$point1 = $point2 = 0;
					}

					if (!$tb_begin) continue;

					++$x;
					$pointWinner = 0;
					if ($p1 == $point1) {
						if ($p2 > $point2) { // p2增大，算p2得分
							--$y;
							$pointWinner = 2;
						}
					} else if ($p2 == $point2) {
						if ($p1 > $point1) { // p1增大，算p1得分
							++$y;
							$pointWinner = 1;
						}
					}
					//echo trim($ep_arr[0]) . "\t" . trim($ep_arr[1]) . "\n";
					//if ($y > $param[$set]['max']) $param[$set]['max'] = $y;
					//else if ($y < $param[$set]['min']) $param[$set]['min'] = $y;

					$point1 = $p1;
					$point2 = $p2;

					if ($line->children(1)->innertext != "" && $line->children(3)->innertext == "") {
						$server = 1;
					} else if ($line->children(3)->innertext != "" && $line->children(1)->innertext == "") {
						$server = 2;
					}   
					if (strpos($line->innertext, "LOST SERVE") !== false) {
						$winner = 3 - $server;
					} else {
						$winner = $server;
					}

					$dotValue = [];
					if ($bp || $sp || $mp) {
						$bsm_count = abs($p1 - $p2);
						if ($bsm_count <= 1) $bsm_count = ""; 
						if ($bp) $dotValue[] = $bsm_count . 'BP';
						if ($sp) $dotValue[] = $bsm_count . 'SP';
						if ($mp) $dotValue[] = $bsm_count . 'MP';
					}
					if ($pointWinner == 1) {
						$bsm1 = $dotValue;
						$bsm2 = [];
					} else {
						$bsm2 = $dotValue;
						$bsm1 = [];
					}

					// 判断抢七或者抢十是否已经结束,结束之后in_progress置false
					if ($game1 == 0 && $game2 == 0) { //抢十
						$tb = 10;
					} else {
						$tb = 7;
					}
					if (abs($point1 - $point2) >= 2 && ($point1 >= $tb || $point2 >= $tb)) {
						$in_progress = false;
					}

					if ($key == $last_key && !$in_progress) { // key == lastkay表示已经到了一盘的最后一行
						/*----------------------抢七确认结束时输出不带具体比分的pbp--------------------*/
						//$pbp[$set][] = [$x, $y, $smallDot, [], ''];
						$pbp[$set][] = ['x' => $x * 2 - 1, 'y' => 10000, 's' => 0, 'w' => 0, 'p1' => '', 'p2' => '', 'b1' => [], 'b2' => [], 'f1' => '', 'f2' => '', 'sv' => 0, 'ss' => 0];
						$pbp[$set][] = [
							'x' => $x * 2,
							'y' => $y,
							's' => $server,
							'w' => $pointWinner,
							'p1' => $point1,
							'p2' => $point2,
							'b1' => $bsm1,
							'b2' => $bsm2,
							'f1' => "",
							'f2' => "",
							'sv' => 0,
							'ss' => 0,
						];
						/*------------------------------------------------------------------*/

						if ($winner == 1) ++$game1;
						else if ($winner == 2) ++$game2;

						/*
						if ($winner == 1) {
							$color = Config::get('const.sideColor.home');
						} else {
							$color = Config::get('const.sideColor.away');
						}
						*/	

						/*----------------------抢七确认结束时输出markArea------------------*/
						//$param[$set]['markLines'][] = [$last_x, $x, $game1 . '-' . $game2, $color];
						//$param[$set]['markLines'][] = [$last_x, $x, $game1 . '-' . $game2, $winner];
						$param[$set][] = [
							'x' => ($x + 0.5) * 2, // 划分一局的线,
							'g1' => $game1,
							'g2' => $game2,
							's' => 0,
							'w' => $winner,
							'tb' => true,
							'b' => false,
						];
						/*------------------------------------------------------------------*/
					} else {
						/*----------------------抢七每分输出pbp-----------------------------*/
						//$pbp[$set][] = [$x, $y, $dotSize, $dotValue, $point1.'-'.$point2];
						$pbp[$set][] = ['x' => $x * 2 - 1, 'y' => 10000, 's' => 0, 'w' => 0, 'p1' => '', 'p2' => '', 'b1' => [], 'b2' => [], 'f1' => '', 'f2' => '', 'sv' => 0, 'ss' => 0];
						$pbp[$set][] = [
							'x' => $x * 2,
							'y' => $y,
							's' => $server,
							'w' => $pointWinner,
							'p1' => $point1,
							'p2' => $point2,
							'b1' => $bsm1,
							'b2' => $bsm2,
							'f1' => "",
							'f2' => "",
							'sv' => 0,
							'ss' => 0,
						];
						/*------------------------------------------------------------------*/
					}
				} // if fifteens_available
			} //foreach line

			//$m = max(abs($param[$set]['min']), abs($param[$set]['max'])) + 2;
			//if ($m < 10) $m = 10;
			//$param[$set]['min'] = -$m;
			//$param[$set]['max'] = $m;

			// 一盘结束多加两个虚拟点，用以容纳最后一条得分线
			foreach (range(0, 1) as $r) {
				$pbp[$set][] = ['x' => (++$x) * 2, 'y' => 10000, 's' => 0, 'w' => 0, 'p1' => '', 'p2' => '', 'b1' => [], 'b2' => [], 'f1' => '', 'f2' => '', 'sv' => 0, 'ss' => 0];
			}
		} //foreach SET

		$ret['pbp'] = $pbp;
		$ret['markLines'] = $param;
	}

	protected function pbp_itf_event(&$ret) {

		$pbp = [];
		$param = [];

		$json = file_get_contents("https://ls.fn.sportradar.com/itf/en/Europe:Berlin/gismo/match_timeline/" . $this->matchid);
		if (!$json) return;

		$json = json_decode($json, true);
		if (!$json) return;

		$set_begin = false;
		$tb_begin = false;
		$server = $winner = 0;

		$in_progress = false;

		$set = 1;
		$x = $y = 0;
		$last_x = 0;
		$game1 = $game2 = 0;

		/*----------------------第一次输出pbp,param,serve---------------------*/
		//$pbp[$set][] = [$x, $y, $smallDot, [], '0-0'];
		//$param[$set] = ["min" => 0, "max" => 0, "markLines" => []]; // 记录每盘最大值最小值，每局结束的x值以及对应的局数
		//$serve[$set] = [];
		/*---------------------------------------------------------------------*/

		foreach ($json["doc"][0]["data"]["events"] as $ep) {
			$pointtype = $ep["type"];
			$team = @$ep["team"];

			if ($pointtype == "first_server") {

				if ($team == 'home') $server= 1;
				else if ($team == 'away') $server= 2;
				else continue;

			} else if ($pointtype == "score_change_tennis") {

				++$x;
				
				$winner = $ep["team"] == 'home' ? 1 : 2;
				if ($winner == 1) {
					++$y;
				} else if ($winner == 2) {
					--$y;
				}

				$ptrans = $ep["pointflagtranslation"];
				$point1 = intval($ep["game_points"]['home']);
				$point2 = intval($ep["game_points"]['away']);

				if ($ptrans == "Game won" || $ptrans == "Break won" || $ptrans == "Set won" || $ptrans == "Match won") { // 一局结束
					$p1 = $p2 = '';
					if ($winner == 1) {
						//$color = Config::get('const.sideColor.home');
						++$game1;
						if ($tb_begin) {
							$p1 = $last_point1 + 1;
							$p2 = $last_point2;
						} else {
							$p1 = '🎾';
						}
					} else {
						//$color = Config::get('const.sideColor.away');
						++$game2;
						if ($tb_begin) {
							$p2 = $last_point2 + 1;
							$p1 = $last_point1;
						} else {
							$p2 = '🎾';
						}
					}

					$in_progress = false; // 表示一局已结束
					$tb_begin = false;

					/*----------------------每一局结束时输出pbp,输出markArea---------------------*/
					//$pbp[$set][] = [$x, $y, $smallDot, [], ''];
					//$param[$set]['markLines'][] = [$last_x, $x, $game1 . '-' . $game2, $winner];  // 表示从last_x到x这段范围的局分，以及底色
					$pbp[$set][] = ['x' => $x * 2 - 1, 'y' => 10000, 's' => 0, 'w' => 0, 'p1' => '', 'p2' => '', 'b1' => [], 'b2' => [], 'f1' => '', 'f2' => '', 'sv' => 0, 'ss' => 0];
					$pbp[$set][] = [
						'x' => $x * 2,
						'y' => $y,
						's' => $server,
						'w' => $winner,
						'p1' => $p1,
						'p2' => $p2,
						'b1' => [],
						'b2' => [],
						'f1' => "",
						'f2' => "",
						'sv' => 0,
						'ss' => 0,
					];
					if ($winner != $server && $winner > 0) $isBroken = true;
					else $isBroken = false;
					$param[$set][] = [
						'x' => ($x + 0.5) * 2, // 划分一局的线,
						'g1' => $game1,
						'g2' => $game2,
						's' => $server,
						'w' => $winner,
						'tb' => $tb_begin ? true : false,
						'b' => $isBroken,
					];
					/*--------------------------------------------------------------------*/

					/*------------------------------一局结束输出serve-------------------------------*/
					//$serve[$set][] = [floor(($last_x + $x) / 2), $server, $servePerson, $holdOrLost, ($server - 1.5) * 2];
					/*----------------------------------------------------------------------------------*/

					// 新开始一盘
					if ($ptrans == "Set won" || $ptrans == "Match won") {

						// 一盘结束多加两个虚拟点，用以容纳最后一条得分线
						foreach (range(0, 1) as $r) {
							$pbp[$set][] = ['x' => (++$x) * 2, 'y' => 10000, 's' => 0, 'w' => 0, 'p1' => '', 'p2' => '', 'b1' => [], 'b2' => [], 'f1' => '', 'f2' => '', 'sv' => 0, 'ss' => 0];
						}

						$game1 = $game2 = 0;

						if ($ptrans != "Match won") {
							++$set;

							$x = $y = 0;
							/*----------------------盘初输出pbp,param,serve---------------------*/
							//$pbp[$set][] = [$x, $y, $smallDot, [], '0-0'];
							//$param[$set] = ["min" => 0, "max" => 0, "markLines" => []]; // 记录每盘最大值最小值，每局结束的x值以及对应的局数
							//$serve[$set] = [];
							/*---------------------------------------------------------------------*/

						}

					}

					$last_x = $x;

				} else { // 一局没有结束

					$in_progress = true;

					if ($point1 == 1 || $point2 == 1) $tb_begin = true;

					if (!$tb_begin) {
						$server = $ep['service'];
					} else {
						$server = 0;
					}

					$bp = false; if ($ptrans == "break point") $bp = true;
					$sp = false; if ($ptrans == "set point") $sp = true;
					$mp = false; if ($ptrans == "match point") $mp = true;

					$dotValue = [];
					$bsm1 = $bsm2 = [];
					if ($bp || $sp || $mp) {
						if (!$tb_begin) {
							$bsm_count = ceil(abs($point1 - $point2) / 15);
						} else {
							$bsm_count = abs($point1 - $point2);
						}
						
						if ($bsm_count <= 1) $bsm_count = ""; 
						if ($bp) $dotValue[] = $bsm_count . 'BP';
						if ($sp) $dotValue[] = $bsm_count . 'SP';
						if ($mp) $dotValue[] = $bsm_count . 'MP';
						if ($point1 > $point2) {
							$bsm1 = $dotValue;
						} else if ($point1 < $point2) {
							$bsm2 = $dotValue;
						}
					}
					$p1 = $point1; $p2 = $point2;
					if ($p1 == 50 && $p2 == 40) {$p1 = "A"; $p2 = '';}
					if ($p1 == 40 && $p2 == 50) {$p2 = "A"; $p1 = '';}
					$last_point1 = $point1;
					$last_point2 = $point2; // 用于抢七结束前最后一分的计算
					
					/*-----------------------------每分都输出pbp----------------------------*/
					//$pbp[$set][] = [$x, $y, $dotSize, $dotValue, str_replace("50", "AD", $point1).'-'.str_replace("50", "AD", $point2)];
					$pbp[$set][] = ['x' => $x * 2 - 1, 'y' => 10000, 's' => 0, 'w' => 0, 'p1' => '', 'p2' => '', 'b1' => [], 'b2' => [], 'f1' => '', 'f2' => '', 'sv' => 0, 'ss' => 0];
					$pbp[$set][] = [
						'x' => $x * 2,
						'y' => $y,
						's' => $server,
						'w' => $winner,
						'p1' => $p1,
						'p2' => $p2,
						'b1' => $bsm1,
						'b2' => $bsm2,
						'f1' => "",
						'f2' => "",
						'sv' => 0,
						'ss' => 0,
					];
					/*--------------------------------------------------------------------*/

				}

			}
		}

		$ret['pbp'] = $pbp;
		$ret['markLines'] = $param;
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

				$ratio[$seq][0][$k] = $v1;
				$ratio[$seq][1][$k] = $v2;

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

	private function unset_nodata_stat_item(&$stat) {
		foreach ($stat as $set => &$setData) {
			foreach (array_keys($setData[0]) as $key) {
				if (in_array($key, ["dura", "ace", "df", "s1%", "s1", "s2", "tp", "bp%"])) continue;
				$val1 = $setData[0][$key];
				$val2 = $setData[1][$key];
				if (in_array($key, ["wi", "ue", "fe", "mgr", "mpr"])) {
					if ($val1 == 0 && $val2 == 0) {
						unset($setData[0][$key]);
						unset($setData[1][$key]);
					}
				} else if (in_array($key, ["f1a", "f1f", "f2a", "f2f", "dis"])) {
					if ($val1[0] == 0 && $val2[0] == 0) {
						unset($setData[0][$key]);
						unset($setData[1][$key]);
					}
				} else if (in_array($key, ["np%", "rp%", "as", "ds", "gs", "os", "ps", "l", "v", "sg"])) {
					if ($val1 == "0% (0/0)" && $val2 == "0% (0/0)") {
						unset($setData[0][$key]);
						unset($setData[1][$key]);
					}
				}
			}
		}
	}

	private function revise_point($p1, $p2) {
		if (($p1 == 50 || $p1 == "A" || $p1 == "AD" || $p1 == "Ad") && $p2 == 40) {
			$p1 = "A";
			$p2 = "";
		} else if (($p2 == 50 || $p2 == "A" || $p2 == "AD" || $p2 == "Ad") && $p1 == 40) {
			$p2 = "A";
			$p1 = "";
		}
		return [$p1, $p2];
	}

	private function merge_pbp_markLines($pbp, &$markLines) {


	}
}
