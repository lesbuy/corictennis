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
		$this->parseSchedule();
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
		$file = join("/", [DATA, 'tour', 'draw', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;

		$sex = substr($this->tour, 0, 1);
		if ($sex == "W") $sex = "F";

		$players = [];

		$bio = new Bio();
		$this->redis = new redis_cli('127.0.0.1', 6379);

		foreach ($xml as $k => $Event) {
			if (!isset($Event['name'])) continue;

			$sextip = $Event['name'];
			if (in_string($sextip, "Main")) {
				if ($sex == "M") $event = "M";
				else $event = "W";
			} else {
				if ($sex == "M") $event = "Q";
				else $event = "P";
			}

			if (in_string($sextip, "Singles")) {
				$event .= "S";
				$sd = "s";
			} else {
				$event .= "D";
				$sd = "d";
			}

			foreach ($Event['rounds'][1] as $amatch) {
				foreach ([1 ,2] as $side) {
					$pids = [];
					foreach ([1, 2] as $pl) {
						if ($pl == 2 && $sd == "s") continue; // 单打时不看player2
						if ($amatch["S" . $side . "P" . $pl . "Id"]) {
							$itfpid = $amatch["S" . $side . "P" . $pl . "Id"];
							if ($itfpid < 10) continue; // Bye是1，资格赛是0

							$first = $amatch["S" . $side . "P" . $pl . "FirstName"];
							$last = $amatch["S" . $side . "P" . $pl . "LastName"];
							$ioc = $amatch["S" . $side . "P" . $pl . "CCode"];

							$wtpid = null;
							// 先从redis里面 itf_redirect找
							$_get_wtpid = $this->redis->cmd('HGET', 'itf_redirect', $itfpid)->get();
							if ($_get_wtpid) {
								$wtpid = substr($_get_wtpid, 12);
//								fputs(STDERR, "MEMORY FOUND: " . $itfpid . " => " . $wtpid . "\n");
							}

							// 如果没找到，就通过名字去查wt pid，如果是Junior比赛，不查
							if ($wtpid === null) {
//								fputs(STDERR, "TO SEEK WTPID: " . $itfpid . "(" . $first . " " . $last . ")\n");
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
					} // foreach player

					if (count($pids) == 0) continue;

					$entry = $seed = "";
					$note = $amatch["S" . $side . "P1Notes"];
					if (in_string($note, "(")) {
						$entry = substr($note, 1, 1);
					} else if (in_string($note, "[")) {
						$seed = intval(substr($note, 1, 1));
					}

					$seeds = [];
					if ($seed) $seeds[] = $seed;
					if ($entry) $seeds[] = $entry;

					$uuid = $event . join("/", $pids);

					$wtpids = join("/", array_map(function ($d) {return $this->players[$d]['p'];}, $pids));
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
				} // foreach side
			} // foreach match
			$this->teams[$event . 'LIVE'] = ['uuid' => $event . 'LIVE', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'LIVE', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			$this->teams[$event . 'TBD'] = ['uuid' => $event . 'TBD', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'TBD', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			$this->teams[$event . 'QUAL'] = ['uuid' => $event . 'QUAL', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'QUAL', 'g' => '', 'f' => '', 'l' => 'Qualifier', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			$this->teams[$event . 'COMEUP'] = ['uuid' => $event . 'COMEUP', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'COMEUP', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			$this->teams[$event . 'BYE'] = ['uuid' => $event . 'BYE', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'BYE', 'g' => '', 'f' => '', 'l' => 'Bye', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
		}

		unset($bio);
		unset($this->redis); $this->redis = null;
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
										} else if ($pid == 2) {
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
						$statusLabel = @$amatch['statusLabel'];

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
					if ($amatch['match']['timeinfo'] && $amatch['match']['timeinfo']['started']){
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
