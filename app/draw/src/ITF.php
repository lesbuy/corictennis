<?php

require_once('base.class.php');
require_once(APP . '/conf/wt_bio.php');

class Event extends Base{

	protected $itf_point_prize;

	public function  process() {
		$this->preprocess();
		$this->parsePlayer();
		$this->parseDraw();
//		$this->parseResult();
		$this->parseLive();
		$this->parseSchedule();
		$this->appendH2HandFS();
		$this->calaTeamFinal();

	}

	protected function preprocess() {
		$file = join("/", [DATA, 'rank', 'atp', 's', 'history', $this->first_day]);
		if (!file_exists($file)) $file = join("/", [DATA, 'rank', 'atp', 's', 'current']);
		$fp = fopen($file, "r");
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);
			$uuid = $arr[0];
			$rank = $arr[2];
			$this->rank['s'][$uuid] = $rank;
		}
		fclose($fp);

		$file = join("/", [DATA, 'rank', 'wta', 's', 'history', $this->first_day]);
		if (!file_exists($file)) $file = join("/", [DATA, 'rank', 'wta', 's', 'current']);
		$fp = fopen($file, "r");
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);
			$uuid = $arr[0];
			$rank = $arr[2];
			$this->rank['s'][$uuid] = $rank;
		}
		fclose($fp);

		$file = join("/", [DATA, 'rank', 'atp', 'd', 'history', $this->first_day]);
		if (!file_exists($file)) $file = join("/", [DATA, 'rank', 'atp', 'd', 'current']);
		$fp = fopen($file, "r");
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);
			$uuid = $arr[0];
			$rank = $arr[2];
			$this->rank['d'][$uuid] = $rank;
		}
		fclose($fp);

		$file = join("/", [DATA, 'rank', 'wta', 'd', 'history', $this->first_day]);
		if (!file_exists($file)) $file = join("/", [DATA, 'rank', 'wta', 'd', 'current']);
		$fp = fopen($file, "r");
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);
			$uuid = $arr[0];
			$rank = $arr[2];
			$this->rank['d'][$uuid] = $rank;
		}
		fclose($fp);

		$this->itf_point_prize = require_once(APP . '/draw/conf/itf_conf.php');
	}

	protected function parsePlayer() {
		$file = join("/", [DATA, 'tour', 'player', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;

		$sex = substr($this->tour, 0, 1);
		if ($sex == "W") $sex = "F";

		$players = [];

		if (!isset($xml["allEvents"])) return;

		$this->players = $xml["players"];
		foreach ($this->players as $itfpid => $info) {
			$pid = $info['p'];
			$this->players[$itfpid]['rs'] = isset($this->rank['s'][$pid]) ? $this->rank['s'][$pid] : '';
			$this->players[$itfpid]['rd'] = isset($this->rank['d'][$pid]) ? $this->rank['d'][$pid] : '';
		}

		$this->teams = $xml["teams"];
		foreach ($this->teams as $teamID => $info) {
			$wtpids = join("/", array_map(function ($d) {
				return $this->players[$d]['p'];
			}, $info['p']));
			$sd = 's';
			if (count($info['p']) == 2) $sd = 'd';
			$this->teams[$teamID]['r'] = isset($this->rank[$sd][$wtpids]) ? $this->rank[$sd][$wtpids] : '-';
			$this->teams[$teamID]['matches'] = [];
			$this->teams[$teamID]['win'] = 0;
			$this->teams[$teamID]['loss'] = 0;
			$this->teams[$teamID]['streak'] = 0;
			$this->teams[$teamID]['round'] = '';
			$this->teams[$teamID]['point'] = 0;
			$this->teams[$teamID]['prize'] = 0;
			$this->teams[$teamID]['indraw'] = 1;
			$this->teams[$teamID]['next'] = null;
			$this->teams['p'] = array_map(function ($d) {
				return $this->players[$d];
			}, $info['p']);
		}

		foreach ($xml["allEvents"] as $event) {
			$this->teams[$event . 'LIVE'] = ['uuid' => $event . 'LIVE', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'LIVE', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			$this->teams[$event . 'TBD'] = ['uuid' => $event . 'TBD', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'TBD', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			$this->teams[$event . 'QUAL'] = ['uuid' => $event . 'QUAL', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'QUAL', 'g' => '', 'f' => '', 'l' => 'Qualifier', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			$this->teams[$event . 'COMEUP'] = ['uuid' => $event . 'COMEUP', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'COMEUP', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			$this->teams[$event . 'BYE'] = ['uuid' => $event . 'BYE', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'BYE', 'g' => '', 'f' => '', 'l' => 'Bye', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
		}
	}

	protected function parseDraw() {

		$file = join("/", [DATA, 'tour', 'draw', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;

		$web_const = require_once(join("/", [WEB, 'config', 'const.php']));

		foreach ($xml as $k => $Event) {
			if (!isset($Event['name'])) continue;

			$this->currency = "$";

			$sex = substr($this->tour, 0, 1);
			if ($sex == "W") $sex = "F";

			$sextip = $Event['name'];
			if (in_string($sextip, "Main")) {
				if ($sex == "M") $event = "M";
				else $event = "W";
				$qm = "M";
			} else {
				if ($sex == "M") $event = "Q";
				else $event = "P";
				$qm = "Q";
			}

			if (in_string($sextip, "Singles")) {
				$event .= "S";
				$sd = "S";
			} else {
				$event .= "D";
				$sd = "D";
			}
			if (isset($this->draws[$event])) continue; // 防止重复

			$event_raw = $event;

			$event_round = $Event['maxRounds'];
			$event_size = $Event['drawsizeFrom'];
			$eventid = $web_const['grandslam']['type2id'][$event];
			$eventid4oop = $web_const['grandslam']['id2oopid'][$eventid];

			$ko_type = "KO";

			$ct = 0;
			foreach ($Event['rounds'] as $k => $around) {
				$ct += count($around);
			}

			$this->draws[$event] = [
				'uuid' => $event_raw,
				'event' => $event,
				'eventid' => $eventid,
				'eventid2' => $eventid4oop,
				'total_round' => $event_round,
				'asso' => in_array($event, ['QS', 'QD', 'MS', 'MD']) ? 'ATP' : 'WTA',
				'status' => 0,
				'type' => $ko_type,
				'sd' => $sd,
				'qm' => $qm,
				'ct' => $ct,
				'groups' => 0,
				'playersPerGroup' => 0,
				'maxRRRounds' => 0,
				'matchesPerGroupPerRound' => 0,
				'group' => [],
				'draw' => [],
				'round' => [],
				'group_id2name' => [],
				'group_name2id' => [],
			];

			if ($ko_type == "KO") {

				// 组建每轮签表
				foreach ($Event['rounds'] as $r1 => $around) {
					if ($qm == "M") {
						if (count($around) == 1) {
							$r2 = $r3 = "F";
						} else if (count($around) == 2) {
							$r2 = $r3 = "SF";
						} else if (count($around) == 4) {
							$r2 = $r3 = "QF";
						} else {
							$r3 = "R" . (count($around) * 2);
							$r2 = "R" . round(log($event_size / count($around)) / log(2));
						}
					} else {
						$r2 = $r3 = "Q" . $r1;
					}

					if (!isset($this->draws[$event]['round'][$r2])) {
						$point = 0; $prize = 0;
						$placeid = $event_round - $r1 + 2;
						if (isset($this->itf_point_prize[$this->atpprize + $this->wtaprize][$event])) {
							if (isset($this->itf_point_prize[$this->atpprize + $this->wtaprize][$event][$placeid])) {
								$point = $this->itf_point_prize[$this->atpprize + $this->wtaprize][$event][$placeid][0];
								$prize = $this->itf_point_prize[$this->atpprize + $this->wtaprize][$event][$placeid][1];
							}
						}
						$this->draws[$event]['round'][$r2] = [
							'id' => $placeid,
							'point' => $point,
							'prize' => $prize,
							'alias' => $r3,
						];

						if ($placeid == 2) { // 对于决赛或者决胜轮，添加冠军和出线的分数资金
							$point = 0; $prize = 0; $placeid = 1;
							if ($qm == "Q") $alias = "Qualify"; else $alias = "W";
							if (isset($this->itf_point_prize[$this->atpprize + $this->wtaprize][$event][$placeid])) {
								$point = $this->itf_point_prize[$this->atpprize + $this->wtaprize][$event][$placeid][0];
								$prize = $this->itf_point_prize[$this->atpprize + $this->wtaprize][$event][$placeid][1];
							}
							$this->draws[$event]['round'][$alias] = [
								'id' => $placeid,
								'point' => $point,
								'prize' => $prize,
								'alias' => $alias,
							];
						}
					}

					$invalid_match_count = 0; // 对于参赛选手不明的比赛（BYE vs BYE，QUAL vs QUAL)
					foreach ($around as $order => $amatch) {
						if ($amatch['S1P1Id'] < 10 && $amatch['S2P1Id'] < 10) {
							++$invalid_match_count;
						}

						$matchid = sprintf("%s%d%02d", $event, $r1, $order);
						if (isset($amatch['sr_match_id'])) {
							$uuid = $amatch['sr_match_id'];
						} else {
							$uuid = $matchid;
						}

						$teams = [];

						foreach ([1, 2] as $side) {
							$pids = [];

							foreach ([1, 2] as $pl) {
								if ($pl == 2 && $sd == "S") continue; // 单打时跳过player2

								$pid = $amatch['S'. $side . "P" . $pl . "Id"];
								if ($pid > 10) { // pid为0表示选手未知，1表示Bye，2表示资格赛？
									$pids[] = $pid;
								} else if ($pl == 1) { // pid不正常时，只看player1
									if ($r1 == 1) { // 第一轮可能有bye或者qual
										if ($pid == 1) {
											$pids[] = "BYE";
										} else if ($pid == 0) {
											$pids[] = "QUAL";
										}
									} else {
										$pids[] = "";
									}
								}
							}
							
							$teams[] = $event . join("/", $pids);
						}
						$team1 = $teams[0];
						$team2 = $teams[1];

						$group = 0; $x = $r1; $y = $order;
						$this->draws[$event]['draw']['KO'][$group][$x][$y] = $uuid;

						// 记录比赛结果
						$mStatus = "";
						$playStatus = @$amatch['status'];
						$statusLabel = @$amatch['StatusLabel'];

						if ($playStatus == "upcoming") {
							// 表示未开打
						} else if ($playStatus == "live") {
							$mStatus = "B";
						} else if ($amatch['WinningS'] == 1) {
							$mStatus = "F";
							if ($statusLabel == "Ret.") {
								$mStatus = "H";
							} else if ($statusLabel == "Def.") {
								$mStatus = "J";
							} else if ($statusLabel == "W/O") {
								$mStatus = "L";
							}
						} else if ($amatch['WinningS'] == 2) {
							$mStatus = "G";
							if ($statusLabel == "Ret.") {
								$mStatus = "I";
							} else if ($statusLabel == "Def.") {
								$mStatus = "K";
							} else if ($statusLabel == "W/O") {
								$mStatus = "M";
							}
						}
						$revise = self::revise_itf_score($amatch, $mStatus);
						$s1 = $revise[0];
						$s2 = $revise[1];
//						$s1 = $s2 = [];

						// 记录到match里
						$this->matches[$uuid] = [
							'uuid' => $uuid,
							'id' => $matchid,
							'event' => $event,
							'r' => $r1,
							'r1' => $r2,
							'r2' => $r3,
							't1' => $team1,
							't2' => $team2,
							's1' => $s1,
							's2' => $s2,
							'bestof' => 3,
							'mStatus' => $mStatus,
							'h2h' => '',
							'group' => $group,
							'x' => $x,
							'y' => $y,
							'type' => (!$group ? 'KO' : 'RR'),
						];
					} // end foreach match
					if ($r1 == 1 && $invalid_match_count * 2 > $event_size * 0.7) {
						unset($this->draws[$event]);
						break;
					}
				} // end foreach round 
			} // end if KO
		}
	}

	protected function parseResult() {}

	protected function parseExtra() {}

	protected function parseSchedule() {

		$itf_tour_id = join('-', [$this->tour, $this->year]);
		$tour_begins = false;
		$j = -2;
		for ($i = -2; $i < 9; ++$i) { // 从周一前2天开始找
			$date = date('Ymd', strtotime($this->first_day . " " . $i . " days"));
			$file = join("/", [SHARE, 'down_result', 'itf_event', $date]);
			if (!file_exists($file)) continue;
			$json = json_decode(file_get_contents($file), true);
	
			if (!$tour_begins) { // 在找到之前就一直找
				foreach ($json['doc'][0]['data']['tournaments'] as $atour) {
					if ($atour['itfid'] == $itf_tour_id) {
						$tour_begins = true;
						$j = $i; // j记下i为何值的时候是真正开始日
						break;
					}
				}
			}

			if ($tour_begins) {
				$day = $i - $j + 1;
				$isodate = date('Y-m-d', strtotime($date));
//				echo $isodate . "\n";
				$this->oop[$day] = [
					'date' => $isodate,
					'courts' => [],
				];
			
				$date_matches =	array_values(array_filter(
					$json['doc'][0]['data']['matches'],
					function ($d) use ($itf_tour_id) {
						return $d['param4'] == $itf_tour_id;
					}
				));
				usort($date_matches, 'self::sortByCourtIdDesc');

				foreach ($date_matches as $amatch) {

					if (isset($amatch['match']['cancelled']) && $amatch['match']['cancelled']) {
						continue;
					}

					$match_seq = @explode(";", $amatch['param1'])[1];
					$matchid = $amatch['_id'];
//					echo $matchid . "\n";

					$order = $amatch['courtdisplayorder'];
					$name = @explode(";", $amatch['param1'])[0];
					if (!isset($this->oop[$day]['courts'][$order])) {
						$this->oop[$day]['courts'][$order] = [
							'name' => $name,
							'matches' => [],
						];
					}
					$time = $amatch['match']['_dt']['uts'];
					if ($amatch['match']['timeinfo'] && $amatch['match']['timeinfo']['started'] && $amatch['match']['timeinfo']['running']){
						$time = $amatch['match']['timeinfo']['started'];
					}

					if (!isset($this->matches[$matchid])) continue; // 如果签表没有这场比赛就跳过
					$this->matches[$matchid]['date'] = $isodate;
					$event = $this->matches[$matchid]['event'];
					
					$this->oop[$day]['courts'][$order]['matches'][$match_seq] = [
						'id' => $matchid,
						'time' => $time,
						'event' => $event,
					];

					self::getResult($matchid, $amatch['match'], $time, $name);
				}
			}
		}

	}

	protected function parseLive() {

		$file = join("/", [DATA, 'tour', 'live_itf']);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;
	
		foreach ($xml['doc'][0]['data'] as $amatch) {
			$matchid = $amatch['matchid'];
			if (!isset($this->matches[$matchid])) continue;

			self::getResult($matchid, $amatch['match']);

			$this->live_matches[] = $matchid;
		}
	}

	protected function getResult($matchid, &$m, $match_time = "", $match_court = "") {

		if (!isset($this->matches[$matchid])) return;

		$match = &$this->matches[$matchid];
		$event = $match['event'];

		$match['tipmsg'] = '';

		$winner = "";
		$status = @$m['status']['name'];
		if ($status == "Ended" || $status == "Retired" || $status == "Defaulted") {
			$winner = $m["result"]["winner"];
			if ($winner == "home") $winner = 1;
			else if ($winner == "away") $winner = 2;
		}

		$mStatus = $match['mStatus'];
		if ($mStatus != "" && in_string("FGHIJKLM", $mStatus)) { // 已经决出结果了，不更改
			//  do nothing
		} else if ($winner) { // 有winner 说明比完了
			if ($winner == 1) $mStatus == "F"; else if ($winner == 2) $mStatus == "G";
			if ($status == "Retired") {
				if ($winner == 1) $mStatus == "H"; else if ($winner == 2) $mStatus == "I";
			} else if ($status == "Default") {
				if ($winner == 1) $mStatus == "J"; else if ($winner == 2) $mStatus == "K";
			} else if ($m['walkover']) {
				if ($winner == 1) $mStatus == "L"; else if ($winner == 2) $mStatus == "M";
			}
		} else if ($status == "Interrupted") {
			$mStatus = 'C';
		} else if ($m['matchstatus'] == 'live') {
			$mStatus = 'B';
		} else {
			$mStatus = 'A';
		}

		$match['mStatus'] = $mStatus;

		$score1 = [];
		$score2 = [];
		for ($i = 1; $i <= 5; ++$i) {
			if (!isset($m['periods']['p' . $i])) continue;
			$a = $m['periods']['p' . $i]['home'];
			$b = $m['periods']['p' . $i]['away'];
			if (isset($m['tiebreaks']['p' . $i])) {
				$e = $m['tiebreaks']['p' . $i]['home'];
				$f = $m['tiebreaks']['p' . $i]['away'];
			} else {
				$e = $f = -1;
			}
			if (isset($m['periods']['p' . ($i + 1)]) || (!isset($m['periods']['p' . ($i + 1)]) && $winner)) {
				if ($a > $b) {
					$c = 1; $d = -1;
				} else {
					$c = -1; $d = 1;
				}
			} else {
				$c = $d = 0;
			}
			$score1[] = [$a, $c, $e];
			$score2[] = [$b, $d, $f];
		}
		$match['s1'] = $score1;
		$match['s2'] = $score2;

		if ($mStatus != "A") {
			$match['dura'] = $m['timeinfo'] && $m['timeinfo']['played'] ? date('H:i:s', strtotime('0:0:0 +' . $m['timeinfo']['played'] . " seconds")) : ""; 
			$match['s1'] = $score1;
			$match['s2'] = $score2;
		} else {
			$match['s1'] = $match_time;
			$match['s2'] = $match_court;
		}

		if ($mStatus == "B") {
			$p1 = $p2 = $serve = "";
			if (isset($m['gamescore'])) {
				$p1 = $m['gamescore']['home'];
				$p2 = $m['gamescore']['away'];
				$serve = $m['gamescore']['service'];
			}
			if (($p1 == 50 || $p1 == 'A') && $p2 == 40) {$p1 = 'A'; $p2 = 40;}
			if (($p2 == 50 || $p2 == 'A') && $p1 == 40) {$p2 = 'A'; $p1 = 40;}
			$match['p1'] = $p1;
			$match['p2'] = $p2;
			$match['serve'] = $serve;
		}

		// fill in next match if completed
		if (isset($match['type']) && $match['type'] == "KO") {

			$winner = "";
			if (in_array($match['mStatus'], ['F', 'H', 'J', 'L'])) $winner = $match['t1'];
			else if (in_array($match['mStatus'], ['G', 'I', 'K', 'M'])) $winner = $match['t2'];
			else if ($match['mStatus'] == "B") $winner = $event . "LIVE";
			else if ($match['mStatus'] == "C") $winner = $event . "TBD";
			else if ($match['mStatus'] == "A") $winner = $event . "COMEUP";

			$_next_match = self::findNextMatchIdAndPos($matchid, $event);
			if ($winner != "" && $_next_match !== null) {
				$next_match = &$this->matches[$_next_match[0]];
				$next_match['t' . $_next_match[1]] = $winner;
			}
		}

		return true;

	}

	protected function revise_itf_score(&$amatch, $mStatus) {
		$s1 = $s2 = [];
		if (in_array($mStatus, ['L', 'M'])) {
			return [[], []];
		} else {
			for ($i = 1; $i <= 5; ++$i) {
				if (!isset($amatch['Set' . $i . 'S1Sc'], $amatch['Set' . $i . 'S2Sc'])) break; // 这一盘是Null表示结束了
				$a = $amatch['Set' . $i . 'S1Sc'];
				$b = $amatch['Set' . $i . 'S2Sc'];
				$e = isset($amatch['Set' . $i . 'S1TBSc']) ? intval($amatch['Set' . $i . 'S1TBSc']) : -1;
				$f = isset($amatch['Set' . $i . 'S2TBSc']) ? intval($amatch['Set' . $i . 'S2TBSc']) : -1;;
				$c = $d = 0;
				if ($a > $b) {
					$c = 1; $d = -1;
				} else {
					$c = -1; $d = 1;
				}
				$s1[] = [$a, $c, $e];
				$s2[] = [$b, $d, $f];
			}
		}
		return [$s1, $s2];
	}

	protected function sortByCourtIdDesc($a, $b) {
		return $a['courtdisplayorder'] < $b['courtdisplayorder'] ? -1 : 1;
	}

}
