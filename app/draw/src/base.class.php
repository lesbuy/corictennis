<?php

if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

define('WORK', APP . '/draw');

abstract class Base{

	protected $year = null;
	protected $tour = ""; // eid
	protected $tourname = "";
	protected $city = "";
	protected $surface = "";
	protected $loc = "";
	protected $atpid = "";
	protected $wtaid = "";
	protected $atpprize = 0;
	protected $wtaprize = 0;
	protected $level = "";
	protected $atp_level = "";
	protected $wta_level = "";
	protected $currency = "$";

	protected $players = []; // 此处的key必须是标准pid
	protected $draws = [];
	protected $teams = [];
	protected $oop = [];
	protected $live_matches = [];
	protected $matches = [];

	protected $quali_first_day;
	protected $first_day;
	protected $uuid2id = [];
	protected $h2h = [];
	protected $rank = [];
	protected $wclist = [];
	protected $qlist = [];
	protected $llist = [];
	protected $match_uuid2matchid = [];

	protected $redis = null;

	public function __construct($tour, $year) {
		$this->tour = $tour;
		$this->year = $year;

		$cmd = "awk -F\"\t\" '$2 == \"$tour\"' " . join("/", [ROOT, 'store', 'calendar', $this->year, '*']);
		unset($r); exec($cmd, $r);
		if ($r) {
			$arr = explode("\t", $r[0]);
			$this->level = preg_replace('/^.*:/', '', $arr[0]);
			if ($this->level == 'CH') {
				$this->level .= ' ' . ($arr[20] / 1000);
			} else if (in_string($this->level, "CH")) {
				// 不变
			} else if ($this->level == 'ITF') {
				if (strpos($arr[11], "+") !== false) {
					$suffix = "+H";
				} else {
					$suffix = "";
				}
				$this->level .= ' ' . $arr[3] . ($arr[20] / 1000) . $suffix;
			} else if (preg_match('/^[WM]\d{2,3}$/', $this->level)) {
				if (strpos($arr[11], "+") !== false) {
					$suffix = "+H";
				} else {
					$suffix = "";
				}
				$this->level = "ITF " . $this->level . $suffix;
			}
			$this->surface = $arr[8];
			$this->first_day = $arr[5];
			$this->loc = $arr[10];
			$this->city = $arr[9];
			$this->tourname = $arr[7];

			$ar = explode("/", $this->level);
			if (count($ar) == 2) {
				$this->atp_level = $ar[0];
				$this->wta_level = $ar[1];
			} else {
				$this->atp_level = $this->wta_level = $ar[0];
				$prize = $arr[20];
				if (in_string($ar[0], "ITF") || preg_match('/^[WM]\d{2,3}$/', $ar[0])) {
					if (in_string($arr[11], "+")) $prize += 1;
				}
				if ($arr[3] == "M") {
					$this->atpprize = $prize;
				} else {
					$this->wtaprize = $prize;
				}
			}
			if (strpos($this->atp_level, 'CH') !== false) {
				$this->atp_level = 'CH';
			} else if (strpos($this->atp_level, 'ITF') !== false) {
				$this->atp_level = 'ITF';
			}
		}
	}

	public function outputDraws($fp = STDOUT) {
		foreach ($this->draws as $event => $draw) {
			$this->outputDraw($event, $fp);
		}
	}

	public function outputDraw($event, $fp = STDOUT) {

		$eventid = $this->draws[$event]['eventid'];

		if (isset($this->draws[$event]['draw']) && $this->draws[$event]['status'] == 0) {

			foreach ($this->draws[$event]['draw'] as $draw_type => $adraw) {

				foreach ($adraw as $group => $agroup) {

					foreach ($agroup as $r1 => $around) {

						foreach ($around as $order => $matchid) {
							$amatch = $this->matches[$matchid];

							if ($draw_type == "KO") {
								$digital_matchid = $eventid . ($r1 * 100 + $order);
							} else {
								$digital_matchid = $eventid . $group . $r1 . $order;
							}
							$match_status = $amatch['mStatus'];
							$score1 = "";
							$score2 = "";
							if ($match_status != "" && $match_status != "A") {
								$score1 = $this->reviseScoreFromArrayToChar($amatch['s1']);
								$score2 = $this->reviseScoreFromArrayToChar($amatch['s2']);
							} else if ($match_status == "A") {
								$score1 = $amatch['s1'];
								$score2 = $amatch['s2'];
							}
							$team1 = $amatch['t1'] == $event ? [] : $this->teams[$amatch['t1']];
							$team2 = $amatch['t2'] == $event ? [] : $this->teams[$amatch['t2']];

							output_content(join("\t", [
								$event . ($draw_type == "RR" ? '/RR' . (isset($amatch['group_name']) ? '/' . $amatch['group_name'] : '') : ''),
								$digital_matchid . '/' . $matchid,
								$amatch['r2'],
								$match_status,
								$score1,
								$score2,
								isset($team1['p'][0]['p']) ? $this->revisePid($team1['p'][0]['p']) : "",
								isset($team1['p'][1]['p']) ? $this->revisePid($team1['p'][1]['p']) : "",
								isset($team2['p'][0]['p']) ? $this->revisePid($team2['p'][0]['p']) : "",
								isset($team2['p'][1]['p']) ? $this->revisePid($team2['p'][1]['p']) : "",
								@$team1['se'],
								@$team2['se'],
								isset($team1['p'][0]['p']) ? $team1['p'][0]['i'] : "",
								isset($team1['p'][1]['p']) ? $team1['p'][1]['i'] : "",
								isset($team2['p'][0]['p']) ? $team2['p'][0]['i'] : "",
								isset($team2['p'][1]['p']) ? $team2['p'][1]['i'] : "",
								isset($team1['p'][0]['p']) ? $team1['p'][0]['f'] : "",
								isset($team1['p'][1]['p']) ? $team1['p'][1]['f'] : "",
								isset($team2['p'][0]['p']) ? $team2['p'][0]['f'] : "",
								isset($team2['p'][1]['p']) ? $team2['p'][1]['f'] : "",
								isset($team1['p'][0]['p']) ? $team1['p'][0]['l'] : "",
								isset($team1['p'][1]['p']) ? $team1['p'][1]['l'] : "",
								isset($team2['p'][0]['p']) ? $team2['p'][0]['l'] : "",
								isset($team2['p'][1]['p']) ? $team2['p'][1]['l'] : "",
								$amatch['h2h'],
								isset($amatch['odd1']) ? $amatch['odd1'] : '',
								isset($amatch['odd2']) ? $amatch['odd2'] : '',
							]) . "\n", $fp);
						}
					}
				}
			}
		}
	}

	public function outputOOPs($fp = STDOUT) {
		$this->redis = new_redis();
		foreach ($this->oop as $day => $oop) {
			$this->outputOOP($day, $fp);
		}

		unset($this->redis); $this->redis = null;
	}

	public function outputOOP($day, $fp = STDOUT) {

		$date_string = $this->oop[$day]['date'];
		foreach ($this->oop[$day]['courts'] as $courtId => $court) {
			$courtName = $court['name'];
			ksort($court['matches']);
			foreach ($court['matches'] as $matchSeq => $match) {
				$matchtime = $match['time'];
				$matchid = $match['id'];

				if (!isset($this->matches[$matchid])) continue;
				$m = $this->matches[$matchid];
				$event = $m['event'];

				if (isset($m['true_unix']) && $m['true_unix'] > $matchtime) {
					$matchtime = $m['true_unix'];
				}

				$matchdura = isset($m['dura']) ? $m['dura'] : "";
				$score = $this->getLiveScore($m, 'completed');

				if ($m['mStatus'] == "" || $m['mStatus'] == "A") {
					$status = 0;
				} else if (strpos("FGHIJKLM", $m['mStatus']) !== false) {
					$status = 2;
				} else if ($m['mStatus'] == "B") {
					$status = 1;
				} else if ($m['mStatus'] == "C") {
					$status = 1.5;
				} else {
					$status = 0;
				}

				$update_time = time();
				$team1 = in_array($m['t1'], [$event, $event . 'LIVE', $event . 'COMEUP', $event . 'TBD']) ? [] : $this->teams[$m['t1']];
				$team2 = in_array($m['t2'], [$event, $event . 'LIVE', $event . 'COMEUP', $event . 'TBD']) ? [] : $this->teams[$m['t2']];
				$betsp1 = $betsp2 = "";
				if ($team1 && isset($team1['b'])) $betsp1 = $team1['b'];
				if ($team2 && isset($team2['b'])) $betsp2 = $team2['b'];

				foreach ([1, 2] as $side) {
					if (count(${'team' . $side})) {
						${'t' . $side . 'id'} = join("/", array_map(function ($d) {return $d['p'];}, ${'team' . $side}['p']));
						${'t' . $side . 'first'} = join("/", array_map(function ($d) {return $d['f'];}, ${'team' . $side}['p']));
						${'t' . $side . 'last'} = join("/", array_map(function ($d) {return $d['l'];}, ${'team' . $side}['p']));
						${'t' . $side . 'ioc'} = join("/", array_map(function ($d) {return $d['i'];}, ${'team' . $side}['p']));

						${'team' . $side . 'full'} = ${'team' . $side}['p'][0]['l'] . ${'team' . $side}['p'][0]['f'];
						${'team' . $side . 'ioc'} = ${'team' . $side}['p'][0]['i'];
						${'team' . $side . 'last'} = ${'team' . $side}['p'][0]['l'];
					} else if ($prev_matches = self::findPrevMatchId($matchid, $event)) {
						$prev_match = $this->matches[$prev_matches[$side - 1]];
						$psb_t1 = $this->teams[$prev_match['t1']];
						$psb_t2 = $this->teams[$prev_match['t2']];
						if ($this->draws[$event]['sd'] == 'S') {
							${'t' . $side . 'id'} = join('|', ['', $psb_t1['p'][0]['p'], $psb_t2['p'][0]['p']]);;
							${'t' . $side . 'first'} = join('|', ['', $psb_t1['p'][0]['f'], $psb_t2['p'][0]['f']]);
							${'t' . $side . 'last'} = join('|', ['', $psb_t1['p'][0]['l'], $psb_t2['p'][0]['l']]);
							${'t' . $side . 'ioc'} = join('|', ['', $psb_t1['p'][0]['i'], $psb_t2['p'][0]['i']]);
							${'team' . $side . 'full'} = join('|', ['', $psb_t1['p'][0]['l'] . $psb_t1['p'][0]['f'], $psb_t2['p'][0]['l'] . $psb_t2['p'][0]['f']]);
						} else {
							${'t' . $side . 'id'} = join('/', [join('|', ['', $psb_t1['p'][0]['p'], $psb_t2['p'][0]['p']]), join('|', ['', @$psb_t1['p'][1]['p'], @$psb_t2['p'][1]['p']])]);;
							${'t' . $side . 'first'} = join('/', [join('|', ['', $psb_t1['p'][0]['f'], $psb_t2['p'][0]['f']]), join('|', ['', @$psb_t1['p'][1]['f'], @$psb_t2['p'][1]['f']])]);
							${'t' . $side . 'last'} = join('/', [join('|', ['', $psb_t1['p'][0]['l'], $psb_t2['p'][0]['l']]), join('|', ['', @$psb_t1['p'][1]['l'], @$psb_t2['p'][1]['l']])]);
							${'t' . $side . 'ioc'} = join('/', [join('|', ['', $psb_t1['p'][0]['i'], $psb_t2['p'][0]['i']]), join('|', ['', @$psb_t1['p'][1]['i'], @$psb_t2['p'][1]['i']])]);
							${'team' . $side . 'full'} = join('/', [join('|', ['', $psb_t1['p'][0]['l'] . $psb_t1['p'][0]['f'], $psb_t2['p'][0]['l'] . $psb_t2['p'][0]['f']]), join('|', ['', @$psb_t1['p'][1]['l'] . @$psb_t1['p'][1]['f'], @$psb_t2['p'][1]['l'] . @$psb_t2['p'][1]['f']])]);
						}
						${'team' . $side . 'ioc'} = ${'team' . $side . 'last'} = 'TBD';
					} else {
						${'t' . $side . 'id'} = ${'t' . $side . 'first'} = ${'t' . $side . 'last'} = ${'t' . $side . 'ioc'} = '';
						${'team' . $side . 'full'} = ${'team' . $side . 'ioc'} = ${'team' . $side . 'last'} = 'TBD';
					}
				}

				output_content(join("\t", [
					$date_string,
					$this->draws[$event]['eventid2'],
					$this->draws[$event]['qm'] == 'M' ? 0 : 1,
					$this->year,
					$this->atpprize + $this->wtaprize,
					$this->tourname,
					$this->city,
					$this->surface,
					$this->level,
					$m['uuid'],
					$courtId,
					$courtName,
					$matchSeq,
					$m['r1'],
					"Maybe," . date('H:i', $matchtime) . ",,,8," . $matchtime,
					$team1full,
					$team2full,
					$team1ioc,
					$team2ioc,
					$team1last,
					$team2last,
					$matchdura,
					$this->tour,
					$t1id,
					$t2id,
					in_array($event, ['MS', 'WS', 'QS', 'PS']) ? @$team1['r'] : '',
					in_array($event, ['MS', 'WS', 'QS', 'PS']) ? @$team2['r'] : '',
					$score,
					$m['h2h'],
					$update_time,
					$status,
					@$team1['se'],
					@$team2['se'],
					isset($m['fsid']) ? $m['fsid'] : "",
					$m['bestof'],
					$t1first,
					$t1last,
					$t2first,
					$t2last,
					$t1ioc,
					$t2ioc,
					$betsp1,
					$betsp2,
					isset($m['betsid']) ? $m['betsid'] : "",
					isset($m['odd1']) ? $m['odd1'] : "",
					isset($m['odd2']) ? $m['odd2'] : "",
				]) . "\n", $fp);
			}
		}
	}

	public function outputLive($fp = STDOUT) {
		foreach ($this->live_matches as $matchid) {

			if (!isset($this->matches[$matchid])) return false;
			$m = $this->matches[$matchid];

			$score = $this->getLiveScore($m, "live");
			$dura = $m['dura'];
			$bestof = $m['bestof'];
			$tip_msg = isset($m['tipmsg']) ? $m['tipmsg'] : "";

			output_content(join("\t", [
				$m['uuid'],
				$this->tour,
				"",
				"", 
				"", 
				"", 
				"", 
				"",
				"", 
				"", 
				"", 
				"", 
				"", 
				"", 
				"", 
				"", 
				$score,
				$dura,
				"", 
				"", 
				"", 
				$this->tour,
				"", 
				"", 
				"", 
				"", 
				"", 
				"", 
				time(),
				$tip_msg,
				$bestof,
			]) . "\n", $fp);
		}
	}

	public function outputPlayers($fp = STDOUT) {
		$this->redis = new_redis();
		foreach ($this->teams as $uuid => $t) {
			$event = substr($uuid, 0, 2);
			foreach ($t['p'] as $p) {
				$pid = $p['p'];
				if (in_array($pid, ['BYE', 'LIVE', 'QUAL', 'TBD', 'COMEUP'])) continue;
				$first = $p['f'];
				$last = $p['l'];
				$ioc = $p['i'];
				$sex = "";
				if (preg_match('/^[A-Z0-9]{4}$/', $pid)) {
					$gender = "atp";
					$sex = "M";
				} else if (preg_match('/^[0-9]{5,6}$/', $pid)) {
					$gender = "wta";
					$sex = "W";
				} else {
					$gender = "itf";
					if (in_array(substr($event, 0, 1), ['M', 'Q', 'B'])) {
						$sex = "M";
					} else {
						$sex = "W";
					}
				}
				$key = join("_", [$gender, 'profile', $pid]);
				$arr = $this->redis->cmd('HMGET', $key, 's_en', 'l_en', 's_zh', 'l_zh', 's_ja', 'l_ja')->get();
				output_content(join("\t", array_merge([$gender, $sex, $pid, $ioc, $first, $last], $arr)) . "\n", $fp);
			}
		}
		unset($this->redis); $this->redis = null;
	}

	public function outputRounds($fp = STDOUT) {
		foreach ($this->draws as $event => $adraw) {
			foreach ($adraw['round'] as $round => $around) {
				$id = $around['id'];
				$point = $around['point'];
				$prize = $around['prize'];
				$alias = $around['alias'];
				$currency = $this->currency;

				output_content(join("\t", [
					$event,
					$id,
					$alias,
					$point,
					$currency . $prize,
				]) . "\n", $fp);
			}
		}
	}

	public function calaTeamFinal() {
		foreach ($this->draws as $event => $anevent) {
			if (isset($anevent['draw']['KO'][0])) {
				ksort($anevent['draw']['KO'][0]);
				foreach ($anevent['draw']['KO'][0] as $x => $around) {
					ksort($around);
					foreach ($around as $y => $matchid) {
						$m = $this->matches[$matchid];
						if (in_array($m['t1'], [$event, $event . 'BYE', $event . 'QUAL', $event . 'TBD', $event . 'LIVE', $event . 'COMEUP']) && 
							in_array($m['t2'], [$event, $event . 'BYE', $event . 'QUAL', $event . 'TBD', $event . 'LIVE', $event . 'COMEUP'])) {
							continue;
						}

						$team1id = $m['t1'];
						$team2id = $m['t2'];
						if ($x == 1) {
							$this->teams[$team1id]['round'] = $m['r1'];
							$this->teams[$team1id]['point'] = $this->draws[$event]['round'][$this->teams[$team1id]['round']]['point'];
							$this->teams[$team1id]['prize'] = $this->draws[$event]['round'][$this->teams[$team1id]['round']]['prize'];
							$this->teams[$team2id]['round'] = $m['r1'];
							$this->teams[$team2id]['point'] = $this->draws[$event]['round'][$this->teams[$team2id]['round']]['point'];
							$this->teams[$team2id]['prize'] = $this->draws[$event]['round'][$this->teams[$team2id]['round']]['prize'];
							if ($anevent['asso'] == 'ATP') {
								if (strpos($this->teams[$team1id]['e'], 'W') !== false) $this->teams[$team1id]['point'] = 0;
								if (strpos($this->teams[$team2id]['e'], 'W') !== false) $this->teams[$team2id]['point'] = 0;
							}
						}

						$this->teams[$team1id]['next'] = $team2id;
						$this->teams[$team2id]['next'] = $team1id;

						$this->teams[$m['t1']]['matches'][] = [$matchid, 1];
						$this->teams[$m['t2']]['matches'][] = [$matchid, 2];

						unset($winner); unset($loser);
						$winnerid = $loserid = null;

						if (in_array($m['mStatus'], ['F', 'H', 'J', 'L'])) {
							$winnerid = $m['t1']; $loserid = $m['t2'];
						} else if (in_array($m['mStatus'], ['G', 'I', 'K', 'M'])) {
							$winnerid = $m['t2']; $loserid = $m['t1'];
						}
						if ($loserid == $event . 'BYE') $loserid = null;

						if ($winnerid) {
							$winner = &$this->teams[$winnerid];
							$_next_match = self::findNextMatchIdAndPos($matchid, $event);
							if ($_next_match !== null) { // 找到下一场比赛
								$next_m = $this->matches[$_next_match[0]];
								$next_round = $next_m['r1'];
							} else { // 找不到下一场比赛
								if ($anevent['qm'] == 'M' && $m['r1'] == 'F') {
									$next_round = 'W';
								} else if ($anevent['qm'] == 'Q' && $m['r'] == $anevent['total_round']) {
									$next_round = 'Qualify';
								} else {
									$next_round = 'R1';
								}
							}
							if (!in_array($m['mStatus'], ['L', 'M']) && $loserid) {
								++$winner['win'];
								if ($winner['streak'] < 0) $winner['streak'] = 0;
								++$winner['streak'];
							}

							$winner['round'] = $next_round;
							$winner['prize'] = $this->draws[$event]['round'][$winner['round']]['prize'];
							if ($anevent['asso'] == 'WTA') {
								if ($winner['win'] == 0) {
									$winner['point'] = $this->draws[$event]['round'][$m['r1']]['point'];
								} else {
									$winner['point'] = $this->draws[$event]['round'][$winner['round']]['point'];
								}
							} else if ($anevent['asso'] == 'ATP') {
								if ($winner['win'] == 0 && $x == 1) {
									$winner['point'] = $this->draws[$event]['round'][$m['r1']]['point'];
								} else {
									$winner['point'] = $this->draws[$event]['round'][$winner['round']]['point'];
								}
							}

							if ($_next_match !== null) {
								$winner['next'] = $this->matches[$_next_match[0]]['t' . (3 - $_next_match[1])];
//								fputs(STDERR, $matchid . "\t" . $_next_match[0] . "\t" . $_next_match[1] . "\t" . $winner['p'][0]['l'] . "\t" . $winner['next'] . "\n");
								if (in_array($winner['next'], [$event, $event . 'BYE', $event . 'COMEUP', $event . 'LIVE', $event . 'TBD'])) {
									$winner['next'] = null;
								}
							}
						}

						$loserPureID = substr($loserid, 2);
						if ($loserPureID && $loserPureID != "QUAL" && $loserPureID != "BYE" && $loserPureID != "LIVE" && $loserPureID != "COMEUP" && $loserPureID != "TBD") {
							$loser = &$this->teams[$loserid];
							if (!in_array($m['mStatus'], ['L', 'M'])) {
								++$loser['loss'];
								if ($loser['streak'] > 0) $loser['streak'] = 0;
								--$loser['streak'];
							}
							$loser['indraw'] = 0;
						}
					} // end foreach around
				} // end foreach anevent
			}
		} // end foreach draws
	}

	// 即将废弃
	public function outputSummary($fp = STDOUT) {

		$teams = [];
		foreach ($this->teams as $ateam) {
			$uuid = $ateam['uuid'];
			$event = substr($uuid, 0, 2);
			if (!in_array($event, ['MS', 'QS', 'MD', 'QD', 'WS', 'PS', 'WD', 'PD'])) continue;
			if (in_array(substr($uuid, 2), ['', 'BYE', 'COMEUP', 'LIVE', 'TBD', 'QUAL'])) continue;
	
			$sd = strtolower(substr($event, 1));
			if (in_array(substr($event, 0, 1), ['M', 'Q'])) $gender = 'atp'; else $gender = 'wta';
			if (in_array(substr($event, 0, 1), ['M', 'W'])) $qm = 'm'; else $qm = 'q';

			$true_pid = join('/', array_map(function ($d) {return $d['p'];}, $ateam['p']));
			if ($ateam['next'] && $ateam['next'] != $event) {
				$next_pid = join('/', array_map(function ($d) {return $d['p'];}, $this->teams[$ateam['next']]['p']));
			} else {
				$next_pid = '';
			}

			if (!isset($teams[$gender][$sd][$true_pid])) {
				$teams[$gender][$sd][$true_pid] = [
					'first' => join('/', array_map(function ($d) {return $d['f'];}, $ateam['p'])),
					'last' => join('/', array_map(function ($d) {return $d['l'];}, $ateam['p'])),
				];
			}
			// 单打按一个人算，双打按两个人算
			$teams[$gender][$sd][$true_pid][$qm] = [
				'win' => $ateam['win'],
				'loss' => $ateam['loss'],
				'streak' => $ateam['streak'],
				'round' => $ateam['round'],
				'point' => $ateam['point'],
				'prize' => $ateam['prize'],
				'indraw' => $ateam['indraw'],
				'next' => $next_pid,
			];

			// 对于双打比赛，增加按每个人算
			if ($sd == 'd') {
				foreach ($ateam['p'] as $idx => $ap) {
					if (!isset($teams[$gender][$sd . 'p'][$ap['p']])) {
						$teams[$gender][$sd . 'p'][$ap['p']] = [
							'first' => $ap['f'],
							'last' => $ap['l'],
						];
					}
					$teams[$gender][$sd . 'p'][$ap['p']][$qm] = [
						'win' => $ateam['win'],
						'loss' => $ateam['loss'],
						'streak' => $ateam['streak'],
						'round' => $ateam['round'],
						'point' => $ateam['point'],
						'prize' => floor($ateam['prize'] / 2),
						'indraw' => $ateam['indraw'],
						'next' => $next_pid,
						'partner' => $ateam['p'][1 - $idx]['p'],
					];
				}
			}
		} // end foreach teams

		
		foreach ($teams as $gender => $agender) {
			foreach ($agender as $sd => $ansd) {
				if ($gender == "atp") {if ($sd == "s") $main_event = "MS"; else $main_event = "MD";}
				else if ($gender == "wta") {if ($sd == "s") $main_event = "WS"; else $main_event = "WD";}

				foreach ($ansd as $true_pid => $ap) {
					unset($res);
					// 先按资格赛算
					if (isset($ap['q'])) {
						$res = $ap['q'];
					}
					if (isset($ap['m'])) {
						if (!isset($res)) $res = $ap['m']; // 如果只有正赛，就按正赛算
						else {
							if ($gender == "atp") { // ATP分数直接累加
								$res['point'] += $ap['m']['point'];
							} else {
								if ($ap['m']['win'] > 0) { // WTA正赛有胜场才更新分数
									if ($res['round'] == "Qualify") { // 如果资格赛出线，则累加正赛分数 
										$res['point'] += $ap['m']['point'];
									} else { // 如果是幸运落败者，则只记正赛分数
										$res['point'] = $ap['m']['point'];
									}
								}
							}

							if ($ap['m']['streak'] * $res['streak'] < 0 || $res['streak'] == 0) {
								$res['streak'] = $ap['m']['streak']; // 如果两个streak不同号或者资格赛streak为0，则按正赛的来
							} else if ($ap['m']['streak'] * $res['streak'] > 0) {
								$res['streak'] += $ap['m']['streak']; // 如果两个streak同号，则相加
							} // 如果正赛streak为0，则仍按资格赛来
							
							$res['win'] += $ap['m']['win'];
							$res['loss'] += $ap['m']['loss'];
							$res['round'] = $ap['m']['round'];
							$res['prize'] = $ap['m']['prize'];
							$res['indraw'] = $ap['m']['indraw'];
							$res['next'] = $ap['m']['next'];
						} // if not exist res
					}

					// main_point 表示在正赛获得的分数，但如果资格赛突围并且正赛还没赢球，则认为正赛没有得分。但如果是落败者进正赛，则认为正赛已经得了LL的分
					$main_point = 0;
					if (isset($ap['m'])) {
						$main_point = $ap['m']['point'];
						if (isset($ap['q']) && $ap['m']['win'] == 0) {
							if ($ap['q']['round'] == "Qualify") {
								$main_point = 0;
							} else {
								$main_point = $ap['q']['point'];
							}
						}
					}
					
					$predict = [];
					if (!preg_match('/^Q[1-9]$/', $res['round']) && $res['indraw'] == 1 && $res['round'] != 'W') {
						foreach ($this->draws[$main_event]['round'] as $round => $around) {
							if ($round == $res['round']) break;
							$predict[] = join("\2", [isset($this->draws[$main_event]['round'][$round]) ? $this->draws[$main_event]['round'][$round]['alias'] : $round, $around['point'] - $main_point]);
						}
					}
					$predict = array_reverse($predict);
					$res['prediction'] = $predict;

					$_lev = $this->{$gender . '_level'};
					if (strpos($_lev, "ITF ") === 0) $_lev = "ITF";
					if (strpos($_lev, "CH ") === 0) $_lev = "CH";

					output_content(join("\t", [
						$gender,
						$sd,
						$true_pid,
						$ap['first'] . ' ' . $ap['last'],
						date('Ymd', strtotime($this->first_day)),
						$this->year,
						$this->tour,
						$_lev,
						$this->loc,
						$res['point'],
						$res['win'],
						$res['loss'],
						isset($this->draws[$main_event]['round'][$res['round']]) ? $this->draws[$main_event]['round'][$res['round']]['alias'] : $res['round'],
						strtolower($this->city),
						$this->currency . $res['prize'],
						$this->surface,
						$this->currency . $this->{$gender . 'prize'},
						$res['indraw'], 
						$res['next'],
						$res['streak'],
						isset($res['partner']) ? $res['partner'] : '',
						join("\1", $res['prediction']),
					]) . "\n", $fp);
				} // end foreach player
			} // end foreach sd
		} // end foreach gender
	}

	public function outputH2H($fp = STDOUT) {
		foreach ($this->matches as $match) {
			$event = $match["event"];
			if (!in_array($event, ["MS", "WS", "MD", "WD", "QS", "PS", "QD", "PD"])) continue;
			$mStatus = $match["mStatus"];
			if ($mStatus == "" || strpos("FGHIJKLM", $mStatus) === false) continue;

			$winnerTeamID = $match["t1"];
			if (in_array(substr($winnerTeamID, 2), ["", "/", "BYE", "QUAL", "LIVE", "COMEUP", "TBD"])) continue;
			$loserTeamID = $match["t2"];
			if (in_array(substr($loserTeamID, 2), ["", "/", "BYE", "QUAL", "LIVE", "COMEUP", "TBD"])) continue;
			$winnerTeamScore = $match["s1"];
			$loserTeamScore = $match["s2"];

			// tour info
			$sd = $this->draws[$match["event"]]["sd"];
			$year = $this->year;
			$date = date('Ymd', strtotime($this->first_day));
			$round = $match["r2"];
			$eid = $this->tour;
			$city = $this->city;
			$level = $this->level;
			$loc = $this->loc;
			$surface = $this->surface;

			// level
			$gender = "atp";
			if (in_array($event, ["WS", "WD", "PS", "PD"])) $gender = "wta";
			$_lev = $this->{$gender . '_level'};
			if (strpos($_lev, "ITF ") === 0) $_lev = "ITF";
			if (strpos($_lev, "CH ") === 0) $_lev = "CH";

			// 记分日期
			$weeks = 1;
			if (in_array($this->tour, ['AO', 'RG', 'WC', 'UO', "0404", "0403", "0609", "0902", "1536", "1038" ])) {
				$weeks = 2;
			}
			$recordday = date('Ymd', strtotime($date) + $weeks * 7 * 86400);

			if ($this->{$gender . '_level'} == "ITF") { // 如果是ITF低级别，再延一周
				$recordday = date('Ymd', strtotime($recordday) + 7 * 86400);
			} 

			// id & score
			if (strpos("GIKM", $mStatus) !== false) {
				swap($winnerTeamID, $loserTeamID);
				swap($winnerTeamScore, $loserTeamScore);
			}

			if ($mStatus == "L" || $mStatus == "M") {
				$scores = "W/O";
			} else {
				$scoresArr = [];
				foreach ($winnerTeamScore as $idx => $set) {
					$score = $set[0] . "-" . $loserTeamScore[$idx][0];
					if ($set[2] > -1) {
						$score .= "(" . min($set[2], $loserTeamScore[$idx][2]) . ")";
					}
					$scoresArr[] = $score;
				}
				if ($mStatus == "H" || $mStatus == "I") {
					$scoresArr[] = "Ret.";
				} else if ($mStatus == "J" || $mStatus == "K") {
					$scoresArr[] = "Def.";
				}
				$scores = join(" ", $scoresArr);
			}

			// 把双打team里两个人顺序调一下，确保id小的在前面
			$teamWinner = $this->teams[$winnerTeamID];
			$teamLoser = $this->teams[$loserTeamID];
			if ($sd == "D") {
				if ($this->idCmp($teamWinner['p'][0]['p'], $teamWinner['p'][1]['p'], $gender) > 0) {
					swap($teamWinner['p'][0], $teamWinner['p'][1]);
				}
				if ($this->idCmp($teamLoser['p'][0]['p'], $teamLoser['p'][1]['p'], $gender) > 0) {
					swap($teamLoser['p'][0], $teamLoser['p'][1]);
				}
			}

			// output
			output_content(join("\t", [
				$gender,
				$sd,
				$teamWinner['p'][0]['p'],
				$sd == "S" ? "" : $teamWinner['p'][1]['p'],
				$teamLoser['p'][0]['p'],
				$sd == "S" ? "" : $teamLoser['p'][1]['p'],
				$teamWinner['p'][0]['f'],
				$sd == "S" ? "" : $teamWinner['p'][1]['f'],
				$teamLoser['p'][0]['f'],
				$sd == "S" ? "" : $teamLoser['p'][1]['f'],
				$teamWinner['p'][0]['l'],
				$sd == "S" ? "" : $teamWinner['p'][1]['l'],
				$teamLoser['p'][0]['l'],
				$sd == "S" ? "" : $teamLoser['p'][1]['l'],
				$teamWinner['p'][0]['i'],
				$sd == "S" ? "" : $teamWinner['p'][1]['i'],
				$teamLoser['p'][0]['i'],
				$sd == "S" ? "" : $teamLoser['p'][1]['i'],
				$scores,
				$year,
				$date,
				$round,
				$eid,
				strtoupper($city),
				$_lev,
				strtoupper($loc),
				$surface,
				$sd == "S" ? $teamWinner['p'][0]['rs'] : $teamWinner['p'][0]['rd'],
				$sd == "S" ? "" : $teamWinner['p'][1]['rd'],
				$sd == "S" ? $teamLoser['p'][0]['rs'] : $teamLoser['p'][0]['rd'],
				$sd == "S" ? "" : $teamLoser['p'][1]['rd'],
				$recordday
			]) . "\n", $fp);
		}
	}

	public function outputActivity($fp = STDOUT) {
		$this->redis = new_redis();

		$teams = [];
		foreach ($this->teams as $ateam) {
			$uuid = $ateam['uuid'];
			$event = substr($uuid, 0, 2);
			if (!in_array($event, ['MS', 'QS', 'MD', 'QD', 'WS', 'PS', 'WD', 'PD'])) continue;
			if (in_array(substr($uuid, 2), ['', 'BYE', 'COMEUP', 'LIVE', 'TBD', 'QUAL'])) continue;
	
			$sd = strtolower(substr($event, 1));
			if (in_array(substr($event, 0, 1), ['M', 'Q'])) $gender = 'atp'; else $gender = 'wta';
			if (in_array(substr($event, 0, 1), ['M', 'W'])) $qm = 'm'; else $qm = 'q';

			$true_pid = join('/', array_map(function ($d) {return $d['p'];}, $ateam['p']));
			if ($ateam['next'] && $ateam['next'] != $event) {
				$next_pid = join('/', array_map(function ($d) {return $d['p'];}, $this->teams[$ateam['next']]['p']));
			} else {
				$next_pid = '';
			}

			if ($sd == 's') {
				if (!isset($teams[$gender][$sd][$true_pid])) {
					$teams[$gender][$sd][$true_pid] = [
						'first' => join('/', array_map(function ($d) {return $d['f'];}, $ateam['p'])),
						'last' => join('/', array_map(function ($d) {return $d['l'];}, $ateam['p'])),
					];
				}
				$teams[$gender][$sd][$true_pid][$qm] = [
					'win' => $ateam['win'],
					'loss' => $ateam['loss'],
					'streak' => $ateam['streak'],
					'round' => $ateam['round'],
					'point' => $ateam['point'],
					'prize' => $ateam['prize'],
					'indraw' => $ateam['indraw'],
					'next' => $next_pid,
					'matches' => $ateam['matches'],
				];
			}

			if ($sd == 'd') {
				foreach ($ateam['p'] as $idx => $ap) {
					if (!isset($teams[$gender][$sd][$ap['p']])) {
						$teams[$gender][$sd][$ap['p']] = [
							'first' => $ap['f'],
							'last' => $ap['l'],
						];
					}
					$teams[$gender][$sd][$ap['p']][$qm] = [
						'win' => $ateam['win'],
						'loss' => $ateam['loss'],
						'streak' => $ateam['streak'],
						'round' => $ateam['round'],
						'point' => $ateam['point'],
						'prize' => floor($ateam['prize'] / 2),
						'indraw' => $ateam['indraw'],
						'next' => $next_pid,
						'partner' => $ateam['p'][1 - $idx]['p'],
						'matches' => $ateam['matches'],
					];
				}
			}
		} // end foreach teams

		
		foreach ($teams as $gender => $agender) {
			foreach ($agender as $sd => $ansd) {
				if ($gender == "atp") {if ($sd == "s") $main_event = "MS"; else $main_event = "MD";}
				else if ($gender == "wta") {if ($sd == "s") $main_event = "WS"; else $main_event = "WD";}

				foreach ($ansd as $true_pid => $ap) {
					unset($res);
					// 先按资格赛算
					if (isset($ap['q'])) {
						$res = $ap['q'];
					}
					if (isset($ap['m'])) {
						if (!isset($res)) $res = $ap['m']; // 如果只有正赛，就按正赛算
						else {
							if ($gender == "atp") { // ATP分数直接累加
								$res['point'] += $ap['m']['point'];
							} else {
								if ($ap['m']['win'] > 0) { // WTA正赛有胜场才更新分数
									if ($res['round'] == "Qualify") { // 如果资格赛出线，则累加正赛分数 
										$res['point'] += $ap['m']['point'];
									} else { // 如果是幸运落败者，则只记正赛分数
										$res['point'] = $ap['m']['point'];
									}
								}
							}

							if ($ap['m']['streak'] * $res['streak'] < 0 || $res['streak'] == 0) {
								$res['streak'] = $ap['m']['streak']; // 如果两个streak不同号或者资格赛streak为0，则按正赛的来
							} else if ($ap['m']['streak'] * $res['streak'] > 0) {
								$res['streak'] += $ap['m']['streak']; // 如果两个streak同号，则相加
							} // 如果正赛streak为0，则仍按资格赛来
							
							$res['win'] += $ap['m']['win'];
							$res['loss'] += $ap['m']['loss'];
							$res['round'] = $ap['m']['round'];
							$res['prize'] = $ap['m']['prize'];
							$res['indraw'] = $ap['m']['indraw'];
							$res['next'] = $ap['m']['next'];
						} // if not exist res
					}

					// main_point 表示在正赛获得的分数，但如果资格赛突围并且正赛还没赢球，则认为正赛没有得分。但如果是落败者进正赛，则认为正赛已经得了LL的分
					$main_point = 0;
					if (isset($ap['m'])) {
						$main_point = $ap['m']['point'];
						if (isset($ap['q']) && $ap['m']['win'] == 0) {
							if ($ap['q']['round'] == "Qualify") {
								$main_point = 0;
							} else {
								$main_point = $ap['q']['point'];
							}
						}
					}
					
					$predict = [];
					if (!preg_match('/^Q[1-9]$/', $res['round']) && $res['indraw'] == 1 && $res['round'] != 'W') {
						foreach ($this->draws[$main_event]['round'] as $round => $around) {
							if ($round == $res['round']) break;
							$predict[] = join("\2", [isset($this->draws[$main_event]['round'][$round]) ? $this->draws[$main_event]['round'][$round]['alias'] : $round, $around['point'] - $main_point]);
						}
					}
					$predict = array_reverse($predict);
					$res['prediction'] = $predict;

					$weeks = 1;
					if (in_array($this->tour, ['AO', 'RG', 'WC', 'UO', "0404", "0403", "0609", "0902", "1536", "1038" ])) {
						$weeks = 2;
					}
					$recordday = date('Ymd', strtotime($this->first_day) + $weeks * 7 * 86400);

					if ($this->{$gender . '_level'} == "ITF") { // 如果是ITF低级别，再延一周
						$recordday = date('Ymd', strtotime($recordday) + 7 * 86400);
					} 

					$all_matchids = [];
					$all_matches = [];
					if (isset($ap['q'])) {foreach ($ap['q']['matches'] as $m) {$all_matchids[] = $m;}}
					if (isset($ap['m'])) {foreach ($ap['m']['matches'] as $m) {$all_matchids[] = $m;}}

					foreach ($all_matchids as $amatch) {
						$matchid = $amatch[0];
						$pos = $amatch[1];

						$the_match = $this->matches[$matchid];
						if (!in_array($the_match['mStatus'], ['F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'])) continue;
						if ((in_array($the_match['mStatus'], ['F', 'H', 'J', 'L']) && $pos == 1) || (in_array($the_match['mStatus'], ['G', 'I', 'K', 'M']) && $pos == 2)) {
							$wl = "W";
						} else {
							$wl = "L";
						}
						$displayScore = self::display_score($the_match['s1'], $the_match['s2'], $the_match['mStatus']);

						$all_matches[] = [
							$this->draws[$the_match['event']]['qm'] == "M" ? 20 + $the_match['r'] : $the_match['r'],
							$the_match['r2'],
							$wl,
							$displayScore,
							$this->draws[$the_match['event']]['sd'] == "S" ?
								@$this->teams[$the_match['t' . (3 - $pos)]]['p'][0]['rs'] :
								join("/", array_map(function ($d) {return @$d['rd'];}, $this->teams[$the_match['t' . (3 - $pos)]]['p'])),
							$this->teams[$the_match['t' . (3 - $pos)]]['s'],
							$this->teams[$the_match['t' . (3 - $pos)]]['e'],
							@$this->teams[$the_match['t' . (3 - $pos)]]['p'][0]['p'],
							@$this->teams[$the_match['t' . (3 - $pos)]]['p'][0]['i'],
							@$this->teams[$the_match['t' . (3 - $pos)]]['p'][1]['p'],
							@$this->teams[$the_match['t' . (3 - $pos)]]['p'][1]['i'],
						];
					}

					if (isset($ap['m'])) {
						$this_seed = $this->teams[$this->matches[$ap['m']['matches'][0][0]]['t' . $ap['m']['matches'][0][1]]]['s'];
						$this_entry = $this->teams[$this->matches[$ap['m']['matches'][0][0]]['t' . $ap['m']['matches'][0][1]]]['e'];
					} else {
						$this_seed = $this_entry = "";
					}

					$_lev = $this->{$gender . '_level'};
					if (strpos($_lev, "ITF ") === 0) $_lev = "ITF";
					if (strpos($_lev, "CH ") === 0) $_lev = "CH";

					output_content(join("\t", [
						$gender,
						$true_pid,
						join("/", array_map(function ($d) use ($gender, $true_pid) {return $this->redis->cmd('HGET', join("_", [$gender, 'profile', $d]), 'ioc')->get();}, explode("/", $true_pid))), // ioc
						$this->{$gender . 'id'},
						$this->tour,
						$this->year,
						date('Ymd', strtotime($this->first_day)),
						$weeks,
						$recordday,
						strtoupper($this->city),
						strtoupper($this->loc),
						$_lev,
						$this->surface,
						$this->currency,
						$this->{$gender . 'prize'},
						$sd,
						isset($this->rank[$sd][$true_pid]) ? $this->rank[$sd][$true_pid] : '', // rank
						$this_seed, // seed
						$this_entry, // entry
						isset($res['partner']) ? $res['partner'] : '',
						isset($res['partner']) ? $this->redis->cmd('HGET', join("_", [$gender, 'profile', $res['partner']]), 'ioc')->get() : "", // partner ioc
						$res['prize'],
						$res['point'],
						0, // award point
						isset($this->draws[$main_event]['round'][$res['round']]) ? $this->draws[$main_event]['round'][$res['round']]['alias'] : $res['round'],
						$res['win'],
						$res['loss'],
						$res['streak'],
						join("@", array_map(function ($d) {return '!' . join('!', $d) . '!';}, $all_matches)), // matches
						$res['indraw'], 
						$res['next'],
						join("\1", $res['prediction']),
					]) . "\n", $fp);
				} // end foreach player
			} // end foreach sd
		} // end foreach gender

		unset($this->redis); $this->redis = null;
	}

	public function appendH2HandFS() {

		$this->redis = new_redis();

		foreach ($this->matches as $matchid => $amatch) {
			$m = &$this->matches[$matchid];
			$event = $m['event'];
			if (in_array($m['t1'], [$event, $event . 'LIVE', $event . 'COMEUP', $event . 'TBD', $event . 'BYE', $event . 'QUAL'])) continue;
			if (in_array($m['t2'], [$event, $event . 'LIVE', $event . 'COMEUP', $event . 'TBD', $event . 'BYE', $event . 'QUAL'])) continue;
			$team1 = $this->teams[$m['t1']];
			$team2 = $this->teams[$m['t2']];

			if (in_array($event, ['QS', 'QD', 'PS', 'PD', 'MS', 'MD', 'WS', 'WD']) && count($team1) && count($team2)) {
				$pid1 = join("/", array_map(function ($d) {return $d['p'];}, $team1['p']));
				$pid2 = join("/", array_map(function ($d) {return $d['p'];}, $team2['p']));
				$h2h = $this->redis->cmd('HGET', 'h2h', join("\t", [$pid1, $pid2]))->get();
				if (!$h2h) $h2h = "0:0";
			} else {
				$h2h = "";
			}

			$name1 = $team1['p'][0]['s'];
			$name2 = $team2['p'][0]['s'];
			$name1s = $team1['p'][0]['s2'];
			$name2s = $team2['p'][0]['s2'];
			$sd = strtolower($this->draws[$event]['sd']);
			$res1 = $res2 = [];                                                           
            $fsid = $betsid = $betsp1 = $betsp2 = $unix = "";
            $odd1 = $odd2 = ""; 
			if (isset($m['date'])) {
				$res1 = $this->redis->cmd('HMGET', join("_", ['fs', $name1, $name2, $sd, $m['date']]), 'fsid', 'unix')->get();
				//print_err(join("_", ['fs', $name1, $name2, $sd, $m['date']]));
				$res2 = $this->redis->cmd('HMGET', join("_", ['fs', $name1s, $name2s, $sd, $m['date']]), 'betsid', 'betsp1', 'betsp2')->get();
				//print_err(join("_", ['fs', $name1s, $name2s, $sd, $m['date']]));
			} else {
				$key_arr = $this->redis->cmd('KEYS', join("_", ['fs', $name1, $name2, $sd, '*']))->get();
				if ($key_arr) {
					$key = $key_arr[count($key_arr) - 1];
					$res1 = $this->redis->cmd('HMGET', $key, 'fsid', 'unix')->get();
					$res2 = $this->redis->cmd('HMGET', $key, 'betsid', 'betsp1', 'betsp2')->get();
				}
			}
			if ($res1) {
                if (isset($res1[0]) && $res1[0]) $fsid = $res1[0];
                if (isset($res1[1]) && $res1[1]) $unix = $res1[1];
			}
			if ($res2) {
				if (isset($res2[0]) && $res2[0]) $betsid = $res2[0];
                if (isset($res2[1]) && $res2[1]) $betsp1 = $res2[1];
                if (isset($res2[2]) && $res2[2]) $betsp2 = $res2[2];
            }

			if ($betsp1) $this->teams[$m['t1']]['b'] = $betsp1;
			if ($betsp2) $this->teams[$m['t2']]['b'] = $betsp2;


			$res = [];
			if ($betsid) {
				$res = $this->redis->cmd('HMGET', join("_", ['odd', $betsid]), 'odd1', 'odd2')->get();
				if ($res) {
					$odd1 = $res[0];
					$odd2 = $res[1];
				}
			}

			$m['h2h'] = $h2h;
			$m['fsid'] = $fsid;
			$m['betsid'] = $betsid;
			$m['odd1'] = $odd1;
			$m['odd2'] = $odd2;
			$m['true_unix'] = $unix;
		}

		unset($this->redis);
		$this->redis = null;
	}

	public function outputRawDraws() {print_r($this->draws);}
	public function outputRawOOPs() {print_r($this->oop);}
	public function outputRawTeams() {print_r($this->teams);}
	public function outputRawMatches() {print_r($this->matches);}
	public function outputRawL() {print_r($this->llist);}
	public function outputRawQ() {print_r($this->qlist);}
	public function outputRawW() {print_r($this->wclist);}

	abstract public function process();
	abstract public function processLive();
	abstract protected function preprocess();
	abstract protected function parsePlayer();
	abstract protected function parseDraw();
	abstract protected function parseResult();
	abstract protected function parseExtra();
	abstract protected function parseSchedule();
	abstract protected function parseLive();

	protected function findNextMatchIdAndPos($matchid, $event) {
		$self_match = &$this->matches[$matchid];
		if ($self_match['type'] != 'KO') return null;
		$x = $self_match['x'];
		$y = $self_match['y'];
		if (isset($this->draws[$event]['draw']['KO'][0][$x + 1])) {
			$next_match = $this->draws[$event]['draw']['KO'][0][$x + 1][ceil($y / 2)];
		} else {
			return null;
		}
		return [$next_match, 2 - $y % 2]; // 第2个参数表示本场比赛胜者在下一场比赛的pos：1 or 2
	}

	protected function findPrevMatchId($matchid, $event) {
		$self_match = &$this->matches[$matchid];
		if ($self_match['type'] != 'KO') return null;
		$x = $self_match['x'];
		$y = $self_match['y'];
		if (isset($this->draws[$event]['draw']['KO'][0][$x - 1])) {
			$prev_match1 = $this->draws[$event]['draw']['KO'][0][$x - 1][$y * 2 - 1];
			$prev_match2 = $this->draws[$event]['draw']['KO'][0][$x - 1][$y * 2];
		} else {
			return null;
		}
		return [$prev_match1, $prev_match2];
	}

	public function revisePid($pid) {
		if (preg_match('/^[A-Z0-9]{4}$/', $pid)) return "atp" . $pid;
		else if (preg_match('/^[0-9]{5,6}$/', $pid)) return "wta" . $pid;
		else return $pid;
	}

	public function splitMatchid($matchid) {

        $event = substr($matchid, 0, 2);
        if (!isset($this->draws[$event])) return false;
        $r1 = intval(substr($matchid, 2, 1));
        $order = intval(substr($matchid, 3, 2));

		return [$event, $r1, $order];

	}

//		$ori: array
//		$element: [
//			$game (>= 0),
//			$is_winner (1: winner, 0: loser or unfinished),
//			$tiebreak (-1: no tb, >=0: tb)
//		]
	public function reviseScoreFromArrayToChar($ori) {
		$score = "";

		foreach ($ori as $set) {

			$game = $set[0];
			$tb = $set[2];
			if ($tb == -1) $tb = 0;
			$score .= chr($game + ord('A')) . chr($tb + ord('A'));

		}

		return $score;
	}

	public function getLiveScore(&$match, $type) {

		if ($type == "live") {
			$score1 = $score2 = ["", "", "", "", "", "", ""];
		} else {
			$score1 = $score2 = ["", "", "", "", "", ""];
		}

		if (in_array($match['mStatus'], ['F', 'H', 'J', 'L'])) {
			$score1[0] = "WINNER";
		} else if (in_array($match['mStatus'], ['G', 'I', 'K', 'M'])) {
			$score2[0] = "WINNER";
		} else {
			if (isset($match['serve']) && $match['serve'] == 1) {
				$score1[0] = "SERVE";
			} else if (isset($match['serve']) && $match['serve'] == 2) {
				$score2[0] = "SERVE";
			}
		}

		if ($type == "live") {
			if (isset($match['p1'])) $score1[1] = $match['p1'];
			if (isset($match['p2'])) $score2[1] = $match['p2'];
		}

		for ($i = 0; $i < 5; ++$i) {
			if (!isset($match['s1'][$i])) continue;

			$a = $match['s1'][$i][0];
			if ($match['s1'][$i][1] == -1 && $match['s1'][$i][2] > -1) {$a .= "<sup>" . $match['s1'][$i][2] . "</sup>";}
			if ($match['s1'][$i][1] == -1) {$a = "<span class=loser>" . $a . "</span>";}
			$score1[$i + ($type == "live" ? 2 : 1)] = $a . "";

			$b = $match['s2'][$i][0];
			if ($match['s2'][$i][1] == -1 && $match['s2'][$i][2] > -1) {$b .= "<sup>" . $match['s2'][$i][2] . "</sup>";}
			if ($match['s2'][$i][1] == -1) {$b = "<span class=loser>" . $b . "</span>";}
			$score2[$i + ($type == "live" ? 2 : 1)] = $b . "";

		}

		return json_encode([$score1, $score2]);
	}

	public function reviseEntry() {

		foreach ($this->llist as $event => $list) {
			foreach ($list as $team_uuid => $v) {
				$entry = "L";
				if (isset($this->qlist[$event][$team_uuid])) $entry = "Q";
				if (isset($this->teams[$event . $team_uuid])) {
					$seed = &$this->teams[$event . $team_uuid]['s'];
					if ($seed == "") $seed = $entry;
					else $seed .= '/' . $entry;
				}
			}
		}
	}

	protected function transSextip($ori, $team = 1) {
		if ($ori == "LS") $des = "WS";
		else if ($ori == "LD") $des = "WD";
		else if ($ori == "RS") $des = "PS";
		else if ($ori == "RD") $des = "PD";
		else if ($ori == "MX") $des = "XD";
		else if ($ori == "XD") $des = "LD";
		else if ($ori == "MC") $des = "BS";
		else $des = $ori;
		if (!in_array($des, ['MS', 'MD', 'QS', 'QD', 'WS', 'WD', 'PS', 'PD', 'XD', 'LD', 'BS', 'GS', 'CS', 'DS', 'CD', 'DD'])) {
			if (isset($this->eventCodeReset[$ori])) {
				$des = $this->eventCodeReset[$des];
			} else {
				$des = ($team == 1 ? 'S' : 'D');
				$des = (++$this->otherSextip) . $des;
				$this->eventCodeReset[$ori] = $des;
			}
		}
		return $des;
	}

	protected function getPid($ori) {

		return strtoupper(preg_replace('/^(atp)|(wta0*)|(itf)/', "", strtolower($ori)));

	}

	protected function idCmp($a, $b, $gender) {
		if ($gender == "atp") {
			return strcmp($a, $b);
		} else if ($gender == "wta") {
			return intval($a) - intval($b);
		} else {
			return -1;
		}
	}

	protected function reviseScore($sScore) {
	/***
		input: 6/3 6/7(4) 6/1
		output: array(s1, s2)
			s1 = [
					[6, 1, -1],
					[6, -1, 4],
					[6, 1, -1]
				]
	***/
		$sScore = strtolower($sScore);
		if (strpos($sScore, "w/o") !== false || strpos($sScore, "w.o") !== false || strpos($sScore, "wo") !== false) return [[], []];
		$sScore = preg_replace('/[^\d \/\(\)-]/', '', $sScore);

		$s1 = $s2 = [];
		$arr = explode(" ", preg_replace('/  */', ' ', trim($sScore)));
		for ($i = 0; $i < 5; ++$i) {
			if (!isset($arr[$i])) break;
			unset($_match);
			preg_match('/^(\d+)[\/-](\d+)(\((\d+)\))?$/', $arr[$i], $_match);
			if (!isset($_match[1]) || !isset($_match[2])) return [[], []];
			$a = intval($_match[1]);
			$b = intval($_match[2]);
			if (isset($arr[$i + 1])) {
				if ($a > $b) {
					$c = 1; $d = -1;
				} else {
					$c = -1; $d = 1;
				}
			} else {
				$c = $d = 0;
			}
			if (isset($_match[4])) {
				$tb = intval($_match[4]);
				if ($a > $b) {
					$e = $tb + 2; $f = $tb;
					if ($e < 7) $e = 7;
				} else {
					$e = $tb; $f = $tb + 2;
					if ($f < 7) $f = 7;
				}
			} else {
				$e = $f = -1;
			}

			$s1[] = [$a, $c, $e];
			$s2[] = [$b, $d, $f];
		}
		return [$s1, $s2];
	}

	protected function display_score($s1, $s2, $mStatus) {
		$aff = "";
		if (in_array($mStatus, ['L', 'M'])) {
			return "W/O";
		} else if (in_array($mStatus, ['H', 'I'])) {
			$aff = " Ret.";
		} else if (in_array($mStatus, ['J', 'K'])) {
			$aff = " Def.";
		}

		if (in_array($mStatus, ['G', 'I', 'K'])) {
			$tmp = $s1; $s1 = $s2; $s2 = $tmp;
		}

		$scores = [];
		for ($i = 0; $i < 5; ++$i) {
			if (!isset($s1[$i])) break;
			$score = $s1[$i][0] . '-' . $s2[$i][0];
			if ($s1[$i][2] > -1) {
				$score .= '(' . min($s1[$i][2], $s2[$i][2]) . ')';
			}
			$scores[] = $score;
		}

		return join(" ", $scores) . $aff;
	}

}
