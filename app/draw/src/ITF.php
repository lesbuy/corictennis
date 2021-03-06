<?php

require_once('base.class.php');
require_once(APP . '/conf/wt_bio.php');

class Event extends Base{

	protected $itf_point_prize;
	protected $mode = "normal";

	public function process() {
		$this->preprocess();
		$this->parsePlayer();
		$this->parseDraw();
//		$this->parseResult();
		$this->parseLive();
		$this->parseSchedule();
		$this->appendH2HandFS();
		$this->calaTeamFinal();

	}

	public function processLive() {
		$this->mode = "only-live";
		$this->parseLive();
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
			$this->teams[$teamID]['p'] = array_map(function ($d) {
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
			$eventid = $this->web_const['grandslam']['type2id'][$event];
			$eventid4oop = $this->web_const['grandslam']['id2oopid'][$eventid];

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

		$this->redis = new_redis();
		$bio = new Bio();

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
				//usort($date_matches, 'self::sortByCourtIdDesc');
				usort($date_matches, 'self::sortByMatchID');

				foreach ($date_matches as $amatch) {

					if (isset($amatch['match']['cancelled']) && $amatch['match']['cancelled']) {
						continue;
					}

					$match_seq = @explode(";", $amatch['param1'])[1];
					$matchid = $amatch['_id'];

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

					if (!isset($this->matches[$matchid]) && count($this->draws) > 0) {  // 如果找不到这场比赛，再试试按双方选手去找，并且更新正确的matchid
						$p1 = $amatch['param6'] . ($amatch['param8'] ? "/" . $amatch['param8'] : "");
						$p2 = $amatch['param7'] . ($amatch['param9'] ? "/" . $amatch['param9'] : "");
						$matchRound = $amatch['match']['roundname']['shortname'];

						foreach ($this->matches as $_matchID => &$bmatch) {
							if ((substr($bmatch["t1"], 2) == $p1 && substr($bmatch["t2"], 2) == $p2) ||
								(substr($bmatch["t1"], 2) == $p1 && $bmatch["r2"] == $matchRound) || 
								(substr($bmatch["t2"], 2) == $p2 && $bmatch["r2"] == $matchRound)) {
								$bmatch["uuid"] = $matchid;
								$this->matches[$matchid] = $bmatch;
								$_event = $bmatch['event'];
								$this->draws[$_event]["draw"]["KO"][0][$bmatch["x"]][$bmatch["y"]] = $matchid;
								break;
							}
						}
						//continue; // 如果签表没有这场比赛就跳过
					}

					// 如果连draws都找不到，那就只从schedule里找人吧
					if (count($this->draws) == 0) {
						$gender = "atp";
						if (substr($amatch["param4"], 0, 1) == "W") {
							$gender = "wta";
						}
						$sd = "S";
						if ($amatch["param8"] !== null) {
							$sd = "D";
						}
						$qm = "M";
						if ($amatch["match"]["roundname"]["shortname"] == "Q") {
							$qm = "Q";
						}
						if ($gender == "atp" && $qm == "M") {
							$event = "M";
						} else if ($gender == "atp" && $qm == "Q") {
							$event = "Q";
						} else if ($gender == "wta" && $qm == "M") {
							$event = "W";
						} else if ($gender == "wta" && $qm == "Q") {
							$event = "P";
						}
						$event .= $sd;

						$pidhomeA = $amatch["param6"];
						$pidawayA = $amatch["param7"];
						$pidhomeB = $amatch["param8"];
						$pidawayB = $amatch["param9"];
						foreach (["home", "away"] as $side) {
							$SIDE = $amatch["match"]["teams"][$side];
							$uuids = [];
							$pids = [];
							foreach (["A", "B"] as $partner) {
								$itfpid = ${"pid" . $side . $partner};
								if ($itfpid === null) continue;
								$uuids[] = $itfpid;

								$first = $last = $ioc = "";
								if (isset($SIDE["surname"])) {
									$last = $SIDE["surname"];
									$a = explode(",", $SIDE["name"]);
									$first = trim(@$a[1]);
									$ioc = $SIDE["cc"]["ioc"];
								} else {
									$b = $SIDE["children"][$partner == "A" ? 0 : 1];
									$last = $b["surname"];
									$a = explode(",", $b["name"]);
									$first = trim(@$a[1]);
									$ioc = $b["cc"]["ioc"];
								}

								$wtpid = null;
								// 先从redis里面 itf_redirect找
								$_get_wtpid = $this->redis->cmd('HGET', 'itf_redirect', $itfpid)->get();
								if ($_get_wtpid) {
									$wtpid = substr($_get_wtpid, 12);
									//fputs(STDERR, "MEMORY FOUND: " . $itfpid . " => " . $wtpid . "\n");
								}

								// 如果没找到wt pid
								if ($wtpid === null) {
									fputs(STDERR, join("\t", ["TO SEEK WTPID", $itfpid, $first, $last, $gender]). "\n");

									// 如果itf_profile也找不到，那就set一次itf_profile，并且一小时后过期
									if (!$this->redis->cmd('KEYS', 'itf_profile_' . $itfpid)->get()) {
										$l_en = $bio->rename2long($first, $last, $ioc);
										$s_en = $bio->rename2short($first, $last, $ioc);
										$this->redis->cmd('HMSET', 'itf_profile_' . $itfpid, 'first', $first, 'last', $last, 'ioc', $ioc, 'l_en', $l_en, 's_en', $s_en, 'update_time', time())->set();
										$this->redis->cmd('EXPIRE', 'itf_profile_' . $itfpid, 3600)->set();
									}
								}

								$pid = $itfpid;
								// 如果能找到wtpid，那么就查找他的名字
								if ($wtpid !== null) {
									$find_wtpid = $this->redis->cmd('HMGET', join("_", [$gender, 'profile', $wtpid]), 'first', 'last')->get();
									$first = $find_wtpid[0];
									$last = $find_wtpid[1];

									$pid = $wtpid;
								}

								$short3 = substr(preg_replace('/[^A-Z]/', '', replace_letters(mb_strtoupper($last . $first))), 0, 3); // 取姓的前3个字母，用于flashscore数据
								$last2 = substr(preg_replace('/[^A-Z]/', '', replace_letters(mb_strtoupper(preg_replace('/^.* /', '', str_replace("-", " ", $last))))), 0, 3); // 取名字最后一部分的前3个字母，用于bets数据

								$this->players[$itfpid] = [
									'p' => $pid,
									'g' => $gender == "atp" ? "M" : "F",
									'f' => $first,
									'l' => $last,
									'i' => $ioc,
									's' => $short3,
									's2' => $last2,
									'rs' => @$this->rank['s'][$pid],
									'rd' => @$this->rank['d'][$pid],
								];
								$pids[] = $pid;
							}

							$teamUUID = $event . join("/", $uuids);
							$entry = $seed = "";

							if (isset($SIDE["seed"]) && $SIDE["seed"]["type_short"] !== null && $SIDE["seed"]["type_short"] != "S") {
								$entry = $SIDE["seed"]["type_short"];
							} else if (isset($SIDE["seed"]) && $SIDE["seed"]["type_short"] == "S") {
								$seed = $SIDE["seed"]["seeding"];
							}
							$seeds = [];
							if ($seed) $seeds[] = $seed;
							if ($entry) $seeds[] = $entry;

							$this->teams[$teamUUID] = [
								'uuid' => $teamUUID,
								's' => $seed,
								'e' => $entry,
								'se' => join("/", $seeds),
								'r' => @$this->rank['s'][join("/", $pids)],
								'p' => array_map(function ($d) {
									return $this->players[$d];
								}, $uuids),
							];

							if ($side == "home") {
								$this->matches[$matchid]['t1'] = $teamUUID;
							} else if ($side == "away") {
								$this->matches[$matchid]['t2'] = $teamUUID;
							}
						}
						$this->matches[$matchid]['event'] = $event;
						$this->matches[$matchid]['r1'] = $amatch["match"]["roundname"]["shortname"];
						if ($this->matches[$matchid]['r1'] == "Q") $this->matches[$matchid]['r1'] = "QR";
						$this->matches[$matchid]['uuid'] = $matchid;
					} // end if draws

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

			// normal模式下，找不到比赛就跳过。only-live模式不需要找比赛
			if (!isset($this->matches[$matchid]) && $this->mode == "normal") continue;

			if ($amatch["type"] != "score_change_tennis") continue;

			self::getResult($matchid, $amatch['match']);

			$this->live_matches[] = $matchid;
		}
	}

	protected function getResult($matchid, &$m, $match_time = "", $match_court = "") {

		//if (!isset($this->matches[$matchid])) return;

		if (!isset($this->matches[$matchid])) {
			$this->matches[$matchid] = [];
			$this->matches[$matchid]['uuid'] = $matchid;
		}
		$match = &$this->matches[$matchid];
		$event = @$match['event'];

		$match['tipmsg'] = '';
		$match['bestof'] = 3;

		$winner = "";
		$status = @$m['status']['name'];
		if ($status == "Ended" || $status == "Retired" || $status == "Defaulted" || $status == "Walkover" || $m['walkover'] || $m["matchstatus"] == "result") {
			$winner = $m["result"]["winner"];
			if ($winner == "home") $winner = 1;
			else if ($winner == "away") $winner = 2;
			else $winner = 0;
		}

		$mStatus = @$match['mStatus'];
		if ($mStatus != "" && in_string("FGHIJKLMZ", $mStatus)) { // 已经决出结果了，不更改
			//  do nothing
		} else if ($winner) { // 有winner 说明比完了
			if ($winner == 1) $mStatus = "F"; else if ($winner == 2) $mStatus = "G";
			if ($status == "Retired") {
				if ($winner == 1) $mStatus = "H"; else if ($winner == 2) $mStatus = "I";
			} else if ($status == "Default") {
				if ($winner == 1) $mStatus = "J"; else if ($winner == 2) $mStatus = "K";
			} else if ($m['walkover']) {
				if ($winner == 1) $mStatus = "L"; else if ($winner == 2) $mStatus = "M";
			}
		} else if ($status == "Abandoned") {
			$mStatus = 'Z';
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
		return $a['courtdisplayorder'] <= $b['courtdisplayorder'] ? -1 : 1;
	}

	protected function sortByMatchID($a, $b) {
		return $a['_id'] <= $b['_id'] ? -1 : 1;
	}

}
