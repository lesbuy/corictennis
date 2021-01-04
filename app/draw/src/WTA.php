<?php

require_once('base.class.php');

class Event extends Base{

	private $walkOverMap = [];

	public function  process() {
		$this->preprocess();
		$this->parsePlayer();
		$this->parseDraw();
		$this->parseResult();
		$this->parseSchedule();
		$this->parseExtra();
		$this->parseLive();
		$this->appendH2HandFS();
		$this->calaTeamFinal();

	}

	protected function preprocess() {
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

	}

	protected function parsePlayer() {
		$file = join("/", [DATA, 'tour', 'player', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;

		$players = [];

		foreach ($xml["events"] as $event) {
			$sextip = $event["eventTypeCode"];
			$sextip = self::transSextip($sextip, count($event["eventPlayers"][0]["players"]));

			foreach ($event["eventPlayers"] as $team) {
				$pids = [];

				foreach ($team["players"] as $p) {
					$pid = $p["id"];
					if (!$pid) continue;
					$pids[] = $pid;
					$gender = "F";
					$first = $p["firstName"];
					$last = $p["lastName"];
					$ioc = $p["countryCode"];
					$short3 = substr(preg_replace('/[^A-Z]/', '', replace_letters(mb_strtoupper($last . $first))), 0, 3); // 取姓的前3个字母，用于flashscore数据
					$last2 = substr(preg_replace('/[^A-Z]/', '', replace_letters(mb_strtoupper(preg_replace('/^.* /', '', str_replace("-", " ", $last))))), 0, 3); // 取名字最后一部分的前3个字母，用于bets数据

					$players[$pid] = [
						'p' => $pid,
						'g' => $gender,
						'f' => $first,
						'l' => $last,
						'i' => $ioc,
						's' => $short3,
						's2' => $last2,
						'rs' => isset($this->rank['s'][$pid]) ? $this->rank['s'][$pid] : '',
						'rd' => isset($this->rank['d'][$pid]) ? $this->rank['d'][$pid] : '',
						
					];
					$this->players[$pid] = [
						'p' => $pid,
						'g' => $gender,
						'f' => $first,
						'l' => $last,
						'i' => $ioc,
						's' => $short3,
						's2' => $last2,
						'rs' => isset($this->rank['s'][$pid]) ? $this->rank['s'][$pid] : '',
						'rd' => isset($this->rank['d'][$pid]) ? $this->rank['d'][$pid] : '',
					];
				}
				if (count($pids) == 0) continue;

				$entry = $team["entryType"];
				if ($entry == "LL") $entry = "L";
				else if ($entry == "WC") $entry = "W";
				else if ($entry == "Alt") $entry = "A";
				else if ($entry == "PR") $entry = "P";
				else if ($entry == "SE") $entry = "S";
				else if ($entry == "ITF") $entry = "I";
				else if ($entry == "JE") $entry = "J";
				$seed = $team["seed"];

				$seeds = [];
				if ($seed) $seeds[] = $seed;
				if ($entry) $seeds[] = $entry;

				$uuid = $sextip . join("/", $pids);

				$rank = isset($this->rank['s'][join("/", $pids)]) ? $this->rank['s'][join("/", $pids)] : '-';

				$this->teams[$uuid] = [
					'uuid' => $uuid,
					's' => $seed,
					'e' => $entry,
					'se' => join("/", $seeds),
					'r' => $rank,
					'p' => array_map(function ($d) use ($players) {
						return $players[$d];
					}, $pids),
					'matches' => [],
					'win' => 0,
					'loss' => 0,
					'streak' => 0,
					'round' => '',
					'point' => 0,
					'prize' => 0,
					'indraw' => 1,
					'next' => null,
				];
			}

			$this->teams[$sextip . 'LIVE'] = ['uuid' => $sextip . 'LIVE', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'LIVE', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			$this->teams[$sextip . 'TBD'] = ['uuid' => $sextip . 'TBD', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'TBD', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			$this->teams[$sextip . 'QUAL'] = ['uuid' => $sextip . 'QUAL', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'QUAL', 'g' => '', 'f' => '', 'l' => 'Qualifier', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			$this->teams[$sextip . 'COMEUP'] = ['uuid' => $sextip . 'COMEUP', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'COMEUP', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			$this->teams[$sextip . 'BYE'] = ['uuid' => $sextip . 'BYE', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'BYE', 'g' => '', 'f' => '', 'l' => 'Bye', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
		}
	}

	protected function parseDraw() {

		$file = join("/", [DATA, 'tour', 'draw', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;

		$web_const = require_once(join("/", [WEB, 'config', 'const.php']));

		foreach ($xml as $event_raw => $Event) {
			$event = self::transSextip($event_raw, count($Event["draw"][0]));
			$event_size = count($Event["draw"]);
			$event_round = count($Event["round"]);
			$eventid = $web_const['grandslam']['type2id'][$event];
			$eventid4oop = $web_const['grandslam']['id2oopid'][$eventid];

			$ko_type = "KO";
			if (strpos($event, "D") !== false) {
				$sd = "D";
			} else {
				$sd = "S";
			}
			if (strpos($event, "P") === 0) {
				$qm = "Q";
			} else {
				$qm = "M";
			}
			$ct = 0;
			// 计算一下ct，总比赛场数

			$this->draws[$event] = [
				'uuid' => $event_raw,
				'event' => $event,
				'eventid' => $eventid,
				'eventid2' => $eventid4oop,
				'total_round' => $event_round,
				'asso' => "WTA",
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

			if (isset($Event["wo"])) {
				foreach ($Event["wo"] as $k => $v) {
					$this->walkOverMap[$k] = $v;
				}
			}

			foreach ($Event["round"] as $k => $roundInfo) {
				$name = $roundInfo["round"];
				$prize = intval($roundInfo["prize"]);
				$roundNum = $roundInfo["roundNum"];
				// point需要从配置文件拿
				$point = 1;

				$placeid = $event_round + 2 - $roundNum; // 2表示亚军，3表示4强，依次递增

				if ($name == "Final") $round = "F";
				else if ($name == "Semifinals") $round = "SF";
				else if ($name == "Quarterfinals") $round = "QF";
				else if ($name == "Group Stage") $round = "RR";
				else if ($name == "Round 1") $round = "Q1";
				else if ($name == "Round 2") $round = "Q2";
				else if ($name == "Round 3") $round = "Q3";
				else if ($name == "Round 4") $round = "Q4";
				else if ($roundNum == 1) $round = "R1";
				else if ($roundNum == 2) $round = "R2";
				else if ($roundNum == 3) $round = "R3";
				else if ($roundNum == 4) $round = "R4";

				$_round = "";
				if (in_array($event, ['MS', 'WS', 'MD', 'WD'])) {
					if ($round == "R1" && $event_size > 8) {$_round = "R" . $event_size;}
					else if ($round == "R2" && $event_size > 16) {$_round = "R" . ($event_size / 2);}
					else if ($round == "R3" && $event_size > 32) {$_round = "R" . ($event_size / 4);}
					else if ($round == "R4" && $event_size > 64) {$_round = "R" . ($event_size / 8);}
				}
				if ($_round == "") $_round = $round;

				$this->draws[$event]['round'][$round] = [
					'id' => $placeid,
					'point' => $point,
					'prize' => $prize,
					'alias' => $_round,
				];

				// 补齐W和Qualify
				if ($round == "F") {
					$round = "W";
					$this->draws[$event]['round'][$round] = [
						'id' => 1,
						'point' => 1,
						'prize' => $prize * 2,
						'alias' => "W",
					];
				}

				if ($qm == "Q" && $roundNum == $event_round) {
					$round = "Qualify";
					$this->draws[$event]['round'][$round] = [
						'id' => 1,
						'point' => 1,
						'prize' => $prize * 2,
						'alias' => "Qualify",
					];
				}
			}

			if ($ko_type == "KO") {
				// 遍历签位
				$drawlines = [];
				foreach ($Event["draw"] as $line) {
					$pids = $line;
					if ($pids[0] == "BYE") $pids = ['BYE'];
					else if ($pids[0] == "QUAL") $pids = ['QUAL'];
					$drawlines[] = $event . join("/", $pids);
				}

				// 组建首轮签表
				for ($i = 0; $i < count($drawlines); $i += 2) {
					$team1 = $drawlines[$i];
					$team2 = $drawlines[$i + 1];

					$r1 = 1;
					$order = $i / 2 + 1;

					$match_seq = pow(2, ceil(log($event_size) / log(2))) / 2 + $i / 2;
					$ori_matchid = sprintf("%s%03d", $event_raw, $match_seq);

					$group = 0; $x = $r1; $y = $order;
					$this->draws[$event]['draw']['KO'][$group][$x][$y] = $ori_matchid;

					if ($qm == "M") {
						if ($event_size == 8) {
							$r2 = $r3 = "QF";
						} else if ($event_size == 4) {
							$r2 = $r3 = "SF";
						} else if ($event_size == 2) {
							$r2 = $r3 = "F";
						} else {
							$r3 = "R" . $event_size;
							$r2 = "R1";
						}
					} else {
						$r2 = $r3 = "Q1";
					}

					// 记录到match里
					$this->matches[$ori_matchid] = [
						'uuid' => $ori_matchid,
						'id' => $ori_matchid,
						'event' => $event,
						'r' => $r1,
						'r1' => $r2,
						'r2' => $r3,
						't1' => $team1,
						't2' => $team2,
						'bestof' => 3,
						'mStatus' => '',
						'h2h' => '',
						'group' => $group,
						'x' => $x,
						'y' => $y,
						'type' => (!$group ? 'KO' : 'RR'),
					];
				} // end for 
			} else { // end if KO. if RR

			} // end if RR

			// 组建后面的比赛。RR比赛总轮数减1
			for ($i = 1; $i < ($ko_type == "KO" ? $event_round : $event_round - 1); ++$i) {
				$r1 = $i + 1;
				if ($qm == "Q") {
					$r2 = $r3 = "Q" . $r1;
				} else {
					$t = $event_size / pow(2, $r1 - 1);
					if ($t == 8) $r2 = $r3 = "QF";
					else if ($t == 4) $r2 = $r3 = "SF";
					else if ($t == 2) $r2 = $r3 = "F";
					else {
						$r2 = "R" . $r1;
						$r3 = "R" . ($event_size / pow(2, $r1 - 1));
					}
				}
				$team1 = $team2 = $event;

				for ($j = 1; $j <= $event_size / pow(2, $r1); ++$j) {
					$order = $j;

					$match_seq = pow(2, ceil(log($event_size) / log(2))) / pow(2, $r1) + $j - 1;
					$ori_matchid = sprintf("%s%03d", $event_raw, $match_seq);

					$group = 0; $x = $r1; $y = $order;
					$this->draws[$event]['draw']['KO'][$group][$x][$y] = $ori_matchid;

					// 记录到match里
					$this->matches[$ori_matchid] = [
						'uuid' => $ori_matchid,
						'id' => $ori_matchid,
						'event' => $event,
						'r' => $r1,
						'r1' => $r2,
						'r2' => $r3,
						't1' => $team1,
						't2' => $team2,
						'bestof' => 3,
						'mStatus' => "",
						'h2h' => '',
						'group' => $group,
						'x' => $x,
						'y' => $y,
						'type' => (!$group ? 'KO' : 'RR'),
					];
				}
			} // end for
		}
	}

	protected function parseResult() {

		$file = join("/", [DATA, 'tour', 'result', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;
	
		foreach ($xml["matches"] as $amatch) {
			$matchid = $amatch["MatchID"];
			self::getResult($matchid, $amatch);
		}
	}

	protected function parseExtra() {
		// 补齐一些walkover的比赛。遍历所有比赛，如果已经有结果就跳过，选手不全也跳过，不在wo信息表中也跳过
		foreach ($this->draws as $event => $Event) {
			foreach ($Event['draw']['KO'][0] as $x => $X) {
				foreach ($X as $y => $matchid) {
					if ($this->matches[$matchid]['mStatus'] != "" && strpos("FGHIJKLM", $this->matches[$matchid]['mStatus']) !== false) {
						continue;
					}
					$team1 = $this->matches[$matchid]['t1'];
					$team2 = $this->matches[$matchid]['t2'];
					if ($team1 == $event || $team2 == $event) continue;
					// 从w/o数据中读入
					$tmp_pid = join("-", array_map(function ($d) {return $d['p'];}, $this->teams[$team1]['p'])) . "-" . join("-", array_map(function ($d) {return $d['p'];}, $this->teams[$team2]['p']));
					if (!isset($this->walkOverMap[$tmp_pid])) {
						continue;
					}
					$mStatus = $this->walkOverMap[$tmp_pid];
					$this->matches[$matchid]['mStatus'] = $mStatus;

					$next_match = self::findNextMatchIdAndPos($matchid, $event);

					if ($mStatus == "L") $winnerTeam = $team1; else $winnerTeam = $team2;

					$this->matches[$next_match[0]]['t' . $next_match[1]] = $winnerTeam;
				}
			}
		}
	}

	protected function parseSchedule() {

		$file = join("/", [DATA, 'tour', 'oop', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;
	
		if (!isset($xml["OOP"]["Schedule"]["Day"])) return false;

		$Days = [];
		if (!is_array($xml["OOP"]["Schedule"]["Day"])) {
			$Days[0] = $xml["OOP"]["Schedule"]["Day"];
		} else {
			$Days = $xml["OOP"]["Schedule"]["Day"];
		}

		foreach ($Days as $aday) {
			$day = $aday["Seq"];
			$isodate = $aday["ISODate"];
			if (!isset($this->oop[$day])) {
				$this->oop[$day] = [
					'date' => $isodate,
					'courts' => [],
				];
			}

			foreach ($aday["Court"] as $acourt) {
				$order = $acourt["CourtId"];
				$name = $acourt["CourtName"];
				if (!isset($this->oop[$day]['courts'][$order])) {
					$this->oop[$day]['courts'][$order] = [
						'name' => $name,
						'matches' => [],
					];
				}

				$time = strtotime($isodate . " " . $acourt["DisplayTime"] . " " . $acourt["UTCOffset"]);
				$next_time = $time;

				foreach ($acourt["Matches"]["Match"] as $amatch) {
					if ($amatch["NotBeforeISOTime"] != "") {
						$time = strtotime($isodate . " " . $amatch["NotBeforeISOTime"]);
					} else {
						$time = $next_time;
					}
					$next_time = $time + 5400;

					$match_seq = $amatch["seq"];
					$matchid = $amatch["MatchId"];

					$event_raw = substr($matchid, 0, 2);
					$event = self::transSextip($event_raw, $amatch["DrawMatchType"] == "D" ? 2 : 1);

					if (!isset($this->matches[$matchid])) continue; // 如果签表没有这场比赛就跳过
					$matches = &$this->oop[$day]['courts'][$order]['matches'];
					$matches[$match_seq] = [
						'id' => $matchid,
						'time' => $time,
						'event' => $event,
					];

					$match = &$this->matches[$matchid];
					$match['date'] = $isodate;
					if ($match['mStatus'] == "") { // 如果已经有结果了就不更改状态
						if ($amatch["Status"] == "Suspended") { // 此处要改
							$score = trim(str_replace("TBF", "", $amatch->FreeText . ''));
							$reviseScore = self::reviseScore($score);
							$match['s1'] = $reviseScore[0];
							$match['s2'] = $reviseScore[1];
							$match['mStatus'] = 'C';
						} else {
							$match['mStatus'] = 'A';
							$match['s1'] = $time;
							$match['s2'] = $name;
						}
					}
		
					$_next_match = self::findNextMatchIdAndPos($matchid, $event);
					if ($_next_match !== null) {
						$next_match = &$this->matches[$_next_match[0]];
						if ($next_match['t' . $_next_match[1]] == $event) { // 只有在下场比赛人员还缺的时候，才修改
							$next_match['t' . $_next_match[1]] = $event . 'COMEUP';
						}
					}
				}
			}
		}
	}

	protected function parseLive() {

		$file = join("/", [SHARE, 'down_result', 'live']);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;

		foreach ($xml["matches"] as $amatch) {
			$matchid = $amatch["MatchID"];
			self::getResult($matchid, $amatch);

			$this->live_matches[] = $matchid;
		}
	}

	protected function getResult($matchid, &$m, $match_time = "", $match_court = "") {
		$event_raw = substr($matchid, 0, 2);
		$event = self::transSextip($event_raw, $m["DrawMatchType"] == "D" ? 2 : 1);

		$match = &$this->matches[$matchid];
		$match['tipmsg'] = '';

		$winner = $m["Winner"];
		$sScore = @$m["ScoreString"];

		$score1 = $score2 = [];

		$mStatus = @$match['mStatus'];
		if ($mStatus == "" || strpos("FGHIJKLM", $mStatus) === false) { // 如果已经有结果了，就不需要再记录结果了
			if ($winner == 2) {
				$mStatus = "F";
			} else if ($winner == 3) {
				$mStatus = "G";
			} else if (strpos($sScore, 'Ret') !== false) {
				if ($winner == 4) $mStatus = "H"; else if ($winner == 5) $mStatus = "I";
			} else if (strpos($sScore, 'Def') !== false) {
				if ($winner == 6) $mStatus = "J"; else if ($winner == 7) $mStatus = "K";
			} else if (strpos($sScore, 'W/O') !== false) {
				if ($winner == 4) $mStatus = "L"; else if ($winner == 5) $mStatus = "M";
			} else if ($winner == 0) {
				$mStatus = "B";
			} else {
				fputs(STDERR, $matchid . "\t" . $sScore . "\t" . $winner . "\n");
			}
		}

		foreach ([1, 2, 3, 4, 5] as $set) {
			$a = $m["ScoreSet" . $set . "A"];
			$b = $m["ScoreSet" . $set . "B"];
			if ($a === '' && $b === '') break;
			$aa = @$m["ScoreSet" . ($set + 1) . "A"];
			$bb = @$m["ScoreSet" . ($set + 1) . "B"];
			if ($aa === '' && $bb === '' && strpos('FGHIJKLM', $mStatus) === false) { // 如果本盘是当前盘，则盘分胜负标记为0
				$c = $d = 0;
			} else {
				if ($a > $b) {$c = 1; $d = -1;}
				else if ($a < $b) {$c = -1; $d = 1;}
				else {$c = $d = 0;}
			}
			$tb = $m["ScoreTbSet" . $set];
			if ($tb === '') {$e = $f = -1;}
			else {
				if ($a > $b) {$f = $tb; $e = $f + 2; if ($e < 7) $e = 7;}
				else {$e = $tb; $f = $e + 2; if ($f < 7) $f = 7;}
			}
			$score1[] = [$a, $c, $e];
			$score2[] = [$b, $d, $f];
		}

		$match['mStatus'] = $mStatus;

		if ($mStatus != "A") {
			$match['dura'] = $m["MatchTimeTotal"]; 
			$match['s1'] = $score1;
			$match['s2'] = $score2;
		} else {
			$match['s1'] = $match_time;
			$match['s2'] = $match_court;
		}

		if ($mStatus == "B") {
			$p1 = $m["PointA"];
			$p2 = $m["PointB"];
			if (($p1 == 50 || $p1 == 'A') && $p2 == 40) {$p1 = 'A'; $p2 = 40;}
			if (($p2 == 50 || $p2 == 'A') && $p1 == 40) {$p2 = 'A'; $p1 = 40;}
			$match['p1'] = $p1;
			$match['p2'] = $p2;
			$serve = $m["Serve"];
			$match['serve'] = ($serve === "" ? "" : (($serve + 0) % 2 == 0 ? 1 : 2));
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

}
