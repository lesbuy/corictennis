<?php

require_once('base.class.php');
require_once(APP . '/rank/src/wt_bio.new.php');

class Event extends Base{

	protected $eventCodeReset = [];
	protected $otherSextip = 0;

	public function  process() {
		$this->preprocess();
		$this->parsePlayer();
		$this->parseDraw();
//		$this->parseResult();
//		$this->parseSchedule();
//		$this->parseLive();
//		$this->appendH2HandFS();
//		$this->calaTeamFinal();

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

	}

	protected function parsePlayer() {
		$file = join("/", [SHARE, 'down_result', 'draw', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;

		$sex = substr($this->tour, 0, 1);
		if ($sex == "W") $sex = "F";

		$players = [];

		$bio = new Bio();
		$this->redis = new redis_cli('127.0.0.1', 6379);

		foreach ($xml as $sextip => $event) {

			if ($event['koGroups']) {
				foreach ($event['koGroups'][0]['rounds'][0]['matches'] as $amatch) {
					foreach ($amatch['teams'] as $ateam) {
						$pids = [];
						foreach ($ateam['players'] as $p) {
							if (!$p) continue;
							$itfpid = $p['playerId'];
							$first = $p['givenName'];
							$ioc = $p['nationality'];
							$last = ucwords(strtolower($p['familyName']));

							$wtpid = null;
							// 先从redis里面 itf_redirect找
							$_get_wtpid = $this->redis->cmd('HGET', 'itf_redirect', $itfpid)->get();
							if ($_get_wtpid) {
								$wtpid = substr($_get_wtpid, 12);
//								fputs(STDERR, "MEMORY FOUND: " . $itfpid . " => " . $wtpid . "\n");
							}

							// 如果没找到，就通过名字去查wt pid，如果是Junior比赛，不查
							if ($wtpid === null) {
								if ($sex == "M") {
									$wtpid = $bio->query_wtpid("atp", $first, $last, $this->redis, $itfpid);
								} else if ($sex == "F") {
									$wtpid = $bio->query_wtpid("wta", $first, $last, $this->redis, $itfpid);
								}
							}

							// 如果itf_profile找不到，也没有redirect到atpwta_profile，那么就插入一个新的itf_profile
							if (!$this->redis->cmd('HGET', 'itf_redirect', $itfpid)->get() && !$this->redis->cmd('KEYS', 'itf_profile_' . $itfpid)->get()) {
								$this->redis->cmd('HMSET', 'itf_profile_' . $itfpid, 'first', $first, 'last', $last, 'ioc', $ioc)->set();
							}

							$pid = $itfpid;
							// 如果能找到wtpid，那么就查找他的名字
							if ($wtpid !== null) {
								$find_wtpid = $this->redis->cmd('HMGET', join("_", [$sex == "M" ? "atp" : "wta", 'profile', $wtpid]), 'first', 'last')->get();
								$first = $find_wtpid[0];
								$last = $find_wtpid[1];

								$pid = $wtpid;
							}

							$short3 = substr(preg_replace('/[^A-Z]/', '', replace_letters(mb_strtoupper($last . $first))), 0, 3); // 取姓的前3个字母，用于flashscore数据
							$last2 = substr(preg_replace('/[^A-Z]/', '', replace_letters(mb_strtoupper(preg_replace('/^.* /', '', str_replace("-", " ", $last))))), 0, 3); // 取名字最后一部分的前3个字母，用于bets数据

							$this->players[$itfpid] = [
								'p' => $pid,
								'g' => $sex,
								'f' => $first,
								'l' => $last,
								'i' => $ioc,
								's' => $short3,
								's2' => $last2,
								'rs' => isset($this->rank['s'][$pid]) ? $this->rank['s'][$pid] : '',
								'rd' => isset($this->rank['d'][$pid]) ? $this->rank['d'][$pid] : '',
							];

							$pids[] = $itfpid;
						}

						if (count($pids) == 0) continue;

						$entry = $ateam['entryStatus'];
						if ($entry == "Direct Acceptance") $entry = "";
						else if ($entry == "Qualifier") $entry = "Q";
						else if ($entry == "Lucky Loser") $entry = "L";
						else if ($entry == "Alternative") $entry = "A";
						else if ($entry == "Proteced Ranking") $entry = "P";
						else if ($entry == "Special Exempt") $entry = "S";
						else if ($entry == "Wild Card") $entry = "W";

						$seed = $ateam['seeding'];

						$seeds = [];
						if ($seed) $seeds[] = $seed;
						if ($entry) $seeds[] = $entry;

						$uuid = $sextip . join("/", $pids);

						$wtpids = join("/", array_map(function ($d) {return $this->players[$d]['p'];}, $pids));
						$sd = count($pids) == 1 ? 's' : 'd';
						$rank = isset($this->rank[$sd][$wtpids]) ? $this->rank[$sd][$wtpids] : '-';

						$this->teams[$uuid] = [
							'uuid' => $uuid,
							's' => $seed,
							'e' => $entry,
							'se' => join("/", $seeds),
							'r' => $rank,
							'p' => array_map(function ($d) {return $this->players[$d];}, $pids),
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
				}

				$this->teams[$sextip . 'LIVE'] = ['uuid' => $sextip . 'LIVE', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'LIVE', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
				$this->teams[$sextip . 'TBD'] = ['uuid' => $sextip . 'TBD', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'TBD', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
				$this->teams[$sextip . 'QUAL'] = ['uuid' => $sextip . 'QUAL', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'QUAL', 'g' => '', 'f' => '', 'l' => 'Qualifier', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
				$this->teams[$sextip . 'COMEUP'] = ['uuid' => $sextip . 'COMEUP', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'COMEUP', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
				$this->teams[$sextip . 'BYE'] = ['uuid' => $sextip . 'BYE', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'BYE', 'g' => '', 'f' => '', 'l' => 'Bye', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			}
		}

		unset($bio);
		unset($this->redis); $this->redis = null;
	}

	protected function parseDraw() {

		$file = join("/", [SHARE, 'down_result', 'draw', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;

		$web_const = require_once(join("/", [WEB, 'config', 'const.php']));

		foreach ($xml as $event => $Event) {

			$this->currency = "$";
			$event_raw = $event;

			$event_round = count($Event['koGroups'][0]['rounds']);
			$event_size = count($Event['koGroups'][0]['rounds'][0]['matches']) * 2;
			$eventid = $web_const['grandslam']['type2id'][$event];
			$eventid4oop = $web_const['grandslam']['id2oopid'][$eventid];

			$ko_type = "KO";

			if (strpos($event, "D") !== false) {
				$sd = "D";
			} else {
				$sd = "S";
			}
			if (substr($event, 0, 1) == "Q" || substr($event, 0, 1) == "P" || substr($event, 1, 1) == "Q") {
				$qm = "Q";
			} else {
				$qm = "M";
			}
			$ct = 0;
			foreach ($Event['koGroups'][0]['rounds'] as $around) {
				$ct += count($around['matches']);
			}

			$this->draws[$event] = [
				'uuid' => $event_raw,
				'event' => $event,
				'eventid' => $eventid,
				'eventid2' => $eventid4oop,
				'total_round' => $event_round,
				'asso' => in_array($event, ['QS', 'QD', 'MS', 'MD']) ? 'ATP' : (in_array($event, ['PS', 'PD', 'WS', 'WD']) ? 'WTA' : (in_array($event, ['BS', 'BD', 'BQ']) ? 'BOY' : (in_array($event, ['GS', 'GD', 'GQ']) ? 'GIRL' : ''))),
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
				foreach ($Event['koGroups'][0]['rounds'] as $around) {
					$r1 = $around['roundNumber'];
					$round_desc = $around['roundDesc'];
					if ($round_desc == "Final") {
						$r2 = $r3 = "F";
					} else if ($round_desc == "Semi-finals") {
						$r2 = $r3 = "SF";
					} else if ($round_desc == "Quarter-finals") {
						$r2 = $r3 = "QF";
					} else if ($qm == "Q") {
						$r2 = $r3 = "Q" . $r1;
					} else {
						$r2 = "R" . $r1;
						if ($round_desc == "1st Round") {
							$r3 = "R" . $event_size;
						} else if ($round_desc == "2nd Round") {
							$r3 = "R" . ($event_size / 2);
						} else if ($round_desc == "3rd Round") {
							$r3 = "R" . ($event_size / 4);
						} else if ($round_desc == "4th Round") {
							$r3 = "R" . ($event_size / 8);
						}
					}

					$invalid_match_count = 0; // 对于参赛选手不明的比赛（BYE vs BYE，QUAL vs QUAL），为了让他们有不重复的join id，借助这个自增变量
					foreach ($around['matches'] as $k => $amatch) {
						$uuid = $amatch['matchId'];
						
						$order = $k + 1;
						$matchid = sprintf("%s%d%02d", $event, $r1, ($k + 1));

						$teams = [];
						foreach ($amatch['teams'] as $ateam) {
							$pids = [];
							$ap = $ateam['players'][0];
							if (!$ap) { // player1为null的时候
								if ($r1 == 1) { // 只有第一轮才记bye或qual
									if ($amatch['resultStatusCode'] == "BYE") {
										$pids[] = "BYE";
									} else {
										$pids[] = "QUAL";
									}
								} else { // 非第一轮说明选手还不明确
									$pids[] = "";
								}
							} else {
								$pids[] = $ap['playerId'];
							}
							
							if ($sd == "d") { // 双打比赛，当player2不为null才加入
								$ap = $ateam['players'][1];
								if ($ap) {
									$pids[] = $ap['playerId'];
								}
							}
							$teams[] = $event . join("/", $pids);
						}
						$team1 = $teams[0];
						$team2 = $teams[1];

						// uuid4join是为了与oop join用的。因为oop里的matchid不一样，只能通过选手id来join
						$uuid4join = $event . join("", [
							@$amatch['teams'][0]['players'][0]['playerId'],
							@$amatch['teams'][1]['players'][0]['playerId'],
							@$amatch['teams'][0]['players'][1]['playerId'],
							@$amatch['teams'][1]['players'][1]['playerId'],
						]);
						if ($uuid4join == $event) {
							$uuid4join = $event . 'unknown' . (++$invalid_match_count);
						}

						$group = 0; $x = $r1; $y = $order;
						$this->draws[$event]['draw']['KO'][$group][$x][$y] = $uuid4join;

						// 记录比赛结果
						$mStatus = "";
						$playStatus = $amatch['playStatusCode'];
						if ($playStatus == "TP") { // 没比完

						} else if ($amatch['teams'][0]['isWinner'] === true) {
							$mStatus = "F";
							if ($amatch['resultStatusCode'] == "RET") {
								$mStatus = "H";
							} else if ($amatch['resultStatusCode'] == "DEF") {
								$mStatus = "J";
							} else if ($amatch['resultStatusCode'] == "WO") {
								$mStatus = "L";
							}
						} else if ($amatch['teams'][1]['isWinner'] === true) {
							$mStatus = "G";
							if ($amatch['resultStatusCode'] == "RET") {
								$mStatus = "I";
							} else if ($amatch['resultStatusCode'] == "DEF") {
								$mStatus = "K";
							} else if ($amatch['resultStatusCode'] == "WO") {
								$mStatus = "M";
							}
						}
						$revise = self::revise_itf_score($amatch['teams'][0]['scores'], $amatch['teams'][1]['scores'], $mStatus);
						$s1 = $revise[0];
						$s2 = $revise[1];

						// 记录到match里
						$this->matches[$uuid4join] = [
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
				} // end foreach round 
			} // end if KO
		}
	}

	protected function parseResult() {

		$file = join("/", [SHARE, 'down_result', 'completed', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = simplexml_load_file($file);
		if (!$xml) return false;
	
		foreach ($xml->Tournament->Date as $adate) {
			foreach ($adate->Match as $amatch) {
				$matchid = $amatch->attributes()->matchId . '';
				self::getResult($matchid, $amatch);
			}
		}
	}

	protected function parseExtra() {}

	protected function parseSchedule() {

		$file = join("/", [SHARE, 'down_result', 'OOP', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = simplexml_load_file($file);
		if (!$xml) return false;
	
		foreach ($xml->Schedule->Day as $aday) {
			$isodate = $aday->ISODate . '';
			$day = $aday->attributes()->Seq . '';
			$this->oop[$day] = [
				'date' => $isodate,
				'courts' => [],
			];
		
			foreach ($aday->Court as $acourt) {
				$order = $acourt->attributes()->CourtId . '';
				$name = $acourt->CourtName . '';
				$this->oop[$day]['courts'][$order] = [
					'name' => $name,
					'matches' => [],
				];
				$tz = $acourt->attributes()->UTCOffset . '';
				$local_time = $acourt->DisplayTime . '';
				$next_time = strtotime($isodate . ' ' . $local_time . ' ' . $tz);

				foreach ($acourt->Matches->Match as $amatch) {
					$match_seq = $amatch->attributes()->seq . '';
					$matchid = $amatch->MatchId . '';

					$local_time = $amatch->NotBeforeISOTime . '';
					if ($local_time == "") {
						$time = $next_time;
					} else {
						$time = strtotime($isodate . ' ' . $local_time);
					}

					$next_time = $time + 5400;

					$event_raw = substr($matchid, 0, 2);
					$event = self::transSextip($event_raw, count($amatch->Players->Player));

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

						if ($amatch->Status . '' == "Suspended" && strpos($amatch->FreeTxt . '', 'TBF') !== false) {
							$score = trim(str_replace("TBF", "", $amatch->FreeTxt . ''));
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

					// 如果赛程中能读到选手名字，就读
					if ($match['t1'] == $event && $amatch->Players[0]->attributes()->isKnown . '' == 1) {
						$pids = [];
						foreach ($amatch->Players[0]->Player as $ap) {
							$pids[] = $ap->attributes()->id . '';
						}
						$match['t1'] = $event . join('/', $pids);
						$match['x'] = $this->teams[$match['t1']]['pos'];
						$match['group'] = $this->teams[$match['t1']]['group'];
						$match['group_name'] = $this->draws[$event]['group_id2name'][$match['group']];
					}
					if ($match['t2'] == $event && $amatch->Players[1]->attributes()->isKnown . '' == 1) {
						$pids = [];
						foreach ($amatch->Players[1]->Player as $ap) {
							$pids[] = $ap->attributes()->id . '';
						}
						$match['t2'] = $event . join('/', $pids);
						$match['y'] = $this->teams[$match['t2']]['pos'];
					}
					if ($match['x'] > 0 && $match['y'] > 0 && isset($this->draws[$event]['draw']['RR'][$match['group']]) && !isset($this->draws[$event]['draw']['RR'][$match['group']][$match['x']][$match['y']])) {
						$this->draws[$event]['draw']['RR'][$match['group']][$match['x']][$match['y']] = $matchid;
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

		$xml = simplexml_load_file($file);
		if (!$xml) return false;
	
		foreach ($xml->Tournament as $atour) {
			if ($atour->attributes()->id . '' != $this->tour) continue;
			foreach ($atour->Match as $amatch) {
				$matchid = $amatch->attributes()->mId . '';
				self::getResult($matchid, $amatch);

				$this->live_matches[] = $matchid;
			}
		}
	}

	protected function getResult($matchid, &$m, $match_time = "", $match_court = "") {

		if (isset($m->attributes()->draw)) {
			$event_raw = $m->attributes()->draw . '';
		} else {
			$event_raw = substr($m->attributes()->mId . '', 0, 2);
		}
		$event = self::transSextip($event_raw, intval($m->attributes()->isDoubles) + 1);

		$match = &$this->matches[$matchid];
		$match['tipmsg'] = $m->attributes()->msg . '';

		$winner = $m->attributes()->winner . '';
		$sScore = @$m->attributes()->sS . '';

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
			$a = $m->attributes()->{'s' . $set . 'A'} . '';
			$b = $m->attributes()->{'s' . $set . 'B'} . '';
			if ($a === '' && $b === '') break;
			$aa = @$m->attributes()->{'s' . ($set + 1) . 'A'} . '';
			$bb = @$m->attributes()->{'s' . ($set + 1) . 'B'} . '';
			if ($aa === '' && $bb === '' && strpos('FGHIJKLM', $mStatus) === false) { // 如果本盘是当前盘，则盘分胜负标记为0
				$c = $d = 0;
			} else {
				if ($a > $b) {$c = 1; $d = -1;}
				else if ($a < $b) {$c = -1; $d = 1;}
				else {$c = $d = 0;}
			}
			$tb = $m->attributes()->{'tb' . $set} . '';
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
			$match['dura'] = $m->attributes()->mt . ''; 
			$match['s1'] = $score1;
			$match['s2'] = $score2;
		} else {
			$match['s1'] = $match_time;
			$match['s2'] = $match_court;
		}

		if ($mStatus == "B") {
			$p1 = $m->attributes()->ptA . '';
			$p2 = $m->attributes()->ptB . '';
			if (($p1 == 50 || $p1 == 'A') && $p2 == 40) {$p1 = 'A'; $p2 = 40;}
			if (($p2 == 50 || $p2 == 'A') && $p1 == 40) {$p2 = 'A'; $p1 = 40;}
			$match['p1'] = $p1;
			$match['p2'] = $p2;
			$serve = $m->attributes()->serve . '';
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

	protected function revise_itf_score($score1, $score2, $mStatus) {
		$s1 = $s2 = [];
		if (in_array($mStatus, ['L', 'M'])) {
			return [[], []];
		} else {
			for ($i = 0; $i < 5; ++$i) {
				if ($score1[$i] === null && $score2[$i] === null) break;
				$a = $score1[$i]['score'];
				$b = $score2[$i]['score'];
				$e = $score1[$i]['losingScore'];
				$f = $score2[$i]['losingScore'];
				if ($e === null && $f === null) {
					$e = $f = -1;
				} else if ($e === null) {
					$e = $f + 2;
					if ($e < 7) $e = 7;
				} else if ($f === null) {
					$f = $e + 2;
					if ($f < 7) $f = 7;
				}
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

}
