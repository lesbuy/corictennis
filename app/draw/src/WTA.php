<?php

require_once('base.class.php');

class Event extends Base{

	private $walkOverMap = [];

	public function process() {
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

	public function processLive() {
		$this->parseLive();
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
		$file = join("/", [DATA, 'tour', 'draw', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;

		$players = [];

		if (!isset($xml["Draws"]["Events"]["Event"][0])) {
			$xml["Draws"]["Events"]["Event"] = [$xml["Draws"]["Events"]["Event"]];
		}
		foreach ($xml["Draws"]["Events"]["Event"] as $event) {
			$sextip = $event["EventTypeCode"];
			$sextip = self::transSextip($sextip, count($event["Draw"]["DrawLine"][0]["Players"]["Player"]) == 2 ? 2 : 1);

			foreach ($event["Results"]["Round"][0]["Match"] as $amatch) {

				foreach ($amatch["Players"]["PT"] as $team) {
					if (count($team["Player"]) > 2) {
						$team["Player"] = [$team["Player"]];
					}

					$pids = [];

					foreach ($team["Player"] as $p) {
						$pid = $p["id"];
						if (!$pid) continue;
						$pids[] = $pid;
						$gender = "F";
						$first = $p["FirstName"];
						$last = $p["SurName"];
						$ioc = $p["Country"];
						$short3 = substr(preg_replace('/[^A-Z]/', '', replace_letters(mb_strtoupper($last))), 0, 3); // 取姓的前3个字母，用于flashscore数据
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

					$entry = $team["eType"];
					if ($entry == "LL") $entry = "L";
					else if ($entry == "WC") $entry = "W";
					else if ($entry == "Alt" || $entry == "ALT") $entry = "A";
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
			}

			// 在RR里，还要遍历每场比赛，看看有没有退赛的
			if ($event["Type"]== "RR") {
				foreach ($event["Results"]["Round"] as $around) {
					if (!isset($around["Match"][0])) {
						$around["Match"] = [$around["Match"]];
					}
					foreach ($around["Match"] as $amatch) {
						if (isset($amatch["Players"]["PT"])) {
							foreach ($amatch["Players"]["PT"] as $ateam) {
								$pids = [];
								if (count($ateam["Player"]) > 2) {
									$ateam["Player"] = [$ateam["Player"]];
								}
								foreach ($ateam["Player"] as $aplayer) {
									$pid = $aplayer["id"];
									if (!$pid) continue;
									$pids[] = $pid;
								}
								if (!count($pids)) continue;
								if (isset($this->teams[$sextip . join("/", $pids)])) continue;

								foreach ($ateam["Player"] as $aplayer) {
									$pid = $aplayer["id"];
									if (isset($players[$pid])) continue;
									else {
										$gender = "F";
										$first = $aplayer["FirstName"];
										$last = $aplayer["SurName"];
										$ioc = $aplayer["Country"];
										$players[$pid] = [
											'p' => $pid,
											'g' => $gender,
											'f' => $first,
											'l' => $last,
											'i' => $ioc
										];
									
									}
								}

								$entry = $ateam["EntryType"];
								if ($entry == "LL") $entry = "L";
								else if ($entry == "WC") $entry = "W";
								else if ($entry == "Alt" || $entry == "ALT") $entry = "A";
								else if ($entry == "PR") $entry = "P";
								$seed = $ateam["Seed"];

								$seeds = [];
								if ($seed) $seeds[] = $seed;
								if ($entry) $seeds[] = $entry;

								$rank = isset($this->rank['s'][join("/", $pids)]) ? $this->rank['s'][join("/", $pids)] : '-';
								$uuid = $sextip . join("/", $pids);
		
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
						}
					}
				}			
			}

			$this->teams[$sextip . 'LIVE'] = ['uuid' => $sextip . 'LIVE', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'LIVE', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			$this->teams[$sextip . 'TBD'] = ['uuid' => $sextip . 'TBD', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'TBD', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			$this->teams[$sextip . 'QUAL'] = ['uuid' => $sextip . 'QUAL', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'QUAL', 'g' => '', 'f' => '', 'l' => 'Qualifier', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			$this->teams[$sextip . 'COMEUP'] = ['uuid' => $sextip . 'COMEUP', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'COMEUP', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
			$this->teams[$sextip . 'BYE'] = ['uuid' => $sextip . 'BYE', 's' => '', 'e' => '', 'r' => '', 'p' => [['p' => 'BYE', 'g' => '', 'f' => '', 'l' => 'Bye', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
		}
	}
	/*
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
				else if ($entry == "Alt" || $entry == "ALT") $entry = "A";
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
	*/

	protected function parseDraw() {

		$file = join("/", [DATA, 'tour', 'draw', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;

		if (!isset($xml["Draws"]["Events"]["Event"][0])) {
			$xml["Draws"]["Events"]["Event"] = [$xml["Draws"]["Events"]["Event"]];
		}

		foreach ($xml["Draws"]["Events"]["Event"] as $Event) {

			$this->tourname = $Event["TournamentTitle"];
			$this->city = $Event["Location"];
			$this->city = preg_replace('/[,_].*$/', '', $this->city);
			if (!$this->surface) {
				$this->surface = $Event["Surface"];
				if ($this->surface != 'Clay' && $this->surface != 'Grass' && $this->surface != 'Carpet') $this->surface = 'Hard';
			}
			$scoreCode = $Event["EventScorerCode"];
			$prize = $Event["Tfc"];
			if (strpos($prize, 'USD') !== false || strpos($prize, '$') !== false) $this->currency = "$";
			else if (strpos($prize, 'EUR') !== false || strpos($prize, '€') !== false) $this->currency = "€";
			else if (strpos($prize, 'GBP') !== false || strpos($prize, '￡') !== false) $this->currency = "￡";
			else if (strpos($prize, 'AUD') !== false || strpos($prize, 'A$') !== false) $this->currency = "A$";
			$prize = intval(preg_replace('/[^0-9]/', '', $prize));

			$event_raw = $Event["EventTypeCode"];
			$event = self::transSextip($event_raw, count($Event["Draw"]["DrawLine"][0]["Players"]["Player"]) == 2 ? 2 : 1);

			$f = substr($event, 0, 1);
			if ($f == 'M' || $f == 'Q') {
				$this->atpid = $scoreCode;
				$this->atpprize = $prize;
			} else if ($f == 'W' || $f == 'P') {
				$this->wtaid = $scoreCode;
				$this->wtaprize = $prize;
			}

			$event_size = intval($Event["DrawSize"]);
			$event_round = count($Event["Results"]["Round"]);
			$eventid = $this->web_const['grandslam']['type2id'][$event];
			$eventid4oop = $this->web_const['grandslam']['id2oopid'][$eventid];

			$ko_type = $Event["Type"];
			if (strpos($event, "D") !== false) {
				$sd = "D";
			} else {
				$sd = "S";
			}
			if (strpos($Event["DrawTypeTitle"], "Quali") !== false) {
				$qm = "Q";
			} else {
				$qm = "M";
			}
			$ct = 0;
			foreach ($Event["Results"]["Round"] as $r) {
				$ct += count($r["Match"]);
			}

			$eventSizeCeil = pow(2, ceil(log($event_size) / log(2)));
			$mode = 'normal';  // normal表示比赛序号按完整签表算的。无论签表是32还是24，第一轮都是从16到31。unnormal表示完全按签表大小算序号，比如24签的首轮是12到23
			if (isset($Event["Results"]["Round"][0]['Match'][0]['Id'])) {
				$s = intval(substr($Event["Results"]["Round"][0]['Match'][0]['Id'], 2));
				if ($s == $event_size / 2 && $s < $eventSizeCeil / 2) {
					$mode = 'unnormal';
				}
			}

			$this->draws[$event] = [
				'uuid' => $event_raw,
				'event' => $event,
				'eventid' => $eventid,
				'eventid2' => $eventid4oop,
				'total_round' => $event_round,
				'asso' => in_array($event, ['QS', 'QD', 'MS', 'MD']) ? 'ATP' : (in_array($event, ['PS', 'PD', 'WS', 'WD']) ? 'WTA' : ''),
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

			foreach ($Event["Breakdown"]["Place"] as $idx => $place) {
				$name = $place["Name"];
				$prize = $place["PrizeRound"];
				$point = intval(str_replace(",", "", $place["PointsRound"]));

				$placeid = $idx + 1;

				if ($name == "Winner") $round = "W";
				else if ($name == "Final") $round = "F";
				else if ($name == "Semifinals") $round = "SF";
				else if ($name == "Quarterfinals") $round = "QF";
				else if ($name == "Round of 16" || $name == "Round of 32" || $name == "Round of 64" || $name == "Round of 128") {
					if ($event_round >= $idx) {
						$round = "R" . ($event_round - $idx + 1);
					} else {
						continue;
					}
				}
				else if ($name == "Round 1") $round = "Q1";
				else if ($name == "Round 2") $round = "Q2";
				else if ($name == "Round 3") $round = "Q3";
				else if ($name == "Round 4") $round = "Q4";
				else if ($name == "Q1" || $name == "Q2" || $name == "Q3" || $name == "Q4") $round = $name;
				else if ($name == "Qualifier") $round = "Qualify";
				else if ($name == "Group Stage") $round = "RR";

				$prize = intval(preg_replace('/[^0-9]/', '', $prize));

				$_round = "";
				if (in_array($event, ['MS', 'WS', 'MD', 'WD'])) {
					if ($round == "R1" && $event_size > 8) {$_round = "R" . $event_size;}
					else if ($round == "R2" && $event_size > 16) {$_round = "R" . ($event_size / 2);}
					else if ($round == "R3" && $event_size > 32) {$_round = "R" . ($event_size / 4);}
					else if ($round == "R4" && $event_size > 64) {$_round = "R" . ($event_size / 8);}
				}
				if ($_round == "") $_round = $round;


				// 临时加的
				// if ($this->tour == "2028" && $round == "QF" && $event == "WS") $point = 100;
				

				// $round是规范化的轮次名
				// id：1代表Winner，2代表Final，3代表Semifinals，以此增加
				// alias表示按签位数的轮次名
				$this->draws[$event]['round'][$round] = [
					'id' => $placeid,
					'point' => $point,
					'prize' => $prize,
					'alias' => $_round,
				];
			}

			// 小组赛时获取一些基本信息
			if ($ko_type == "RR") {
				$this->draws[$event]['groups'] = count($Event["RoundRobinGroups"]["Group"]);
				$this->draws[$event]['playersPerGroup'] = count($Event["Draw"]["DrawLine"]) / $this->draws[$event]['groups'];
				$this->draws[$event]['matchesPerGroupPerRound'] = floor($this->draws[$event]['playersPerGroup'] / 2);
				$this->draws[$event]['maxRRRounds'] = $this->draws[$event]['playersPerGroup'] * ($this->draws[$event]['playersPerGroup'] - 1) / 2 / $this->draws[$event]['matchesPerGroupPerRound'];

				foreach ($Event["RoundRobinGroups"]["Group"] as $_gr) {
					$_gr_name = $_gr["Name"];
					$_gr_num = $_gr["Number"];
					$this->draws[$event]['group_name2id'][$_gr_name] = $_gr_num - 1;
					$this->draws[$event]['group_id2name'][$_gr_num] = $_gr_name;
				}

				$_group = &$this->draws[$event]['group'];
				for ($i = 0; $i < $this->draws[$event]['groups']; ++$i) {
					$_group[$i] = [];
				}
				foreach ($Event["Draw"]["DrawLine"] as $drawline) {
					$_gr_name = $drawline["GroupName"];
					$_gr_num = $this->draws[$event]['group_name2id'][$_gr_name];

					$pids = [];
					if (count($drawline["Players"]["Player"]) > 2) {
						$drawline["Players"]["Player"] = [$drawline["Players"]["Player"]];
					}
					foreach ($drawline["Players"]["Player"] as $p) {
						$pids[] = $p["id"];
					}
					
					$_group[$_gr_num][] = $event . join("/", $pids);
				}

				// 弥补某些已经退赛的选手
				foreach ($Event["Results"]["Round"]["Match"] as $amatch) {
					$_gr_name = $amatch["RRGroup"];
					if (!$_gr_name) continue;
					$_gr_num = $this->draws[$event]['group_name2id'][$_gr_name];
					foreach ($amatch["Players"]["PT"] as $team) {
						$pids = [];
						if (count($team["Player"]) > 2) {
							$team["Player"] = [$team["Player"]];
						}
						foreach ($team["Player"] as $p) {
							$pids[] = $p["id"];
						}
						
						$pid = $event . join("/", $pids);
						if (!in_array($pid, $_group[$_gr_num])) {
							 $_group[$_gr_num][] = $pid;
						}
					}
				}
				// 记下选手的位置，包括group，order;
				foreach ($this->draws[$event]['group'] as $x => $v1) {
					foreach ($v1 as $y => $v2) {
						$this->teams[$v2]['group'] = $x + 1;
						$this->teams[$v2]['pos'] = $y + 1;
					}   
				}

				// 遍历小组赛
				foreach ($Event["Results"]["Round"]["Match"] as $amatch) {
					$match_id = $amatch["Id"];
					$teams = [];
					if (isset($amatch["Players"])) {
						foreach ($amatch["Players"]["PT"] as $team) {
							$pids = [];
							if (count($team["Player"]) > 2) {
								$team["Player"] = [$team["Player"]];
							}
							foreach ($team["Player"] as $p) {
								$pids[] = $p["id"];
							}
							$teams[] = $event . join("/", $pids);
						}

						$pos1 = $this->teams[$teams[0]]['pos'];
						$pos2 = $this->teams[$teams[1]]['pos'];
						$group = $this->teams[$teams[0]]['group'];

						// 此处无需调换pos1与pos2，即使pos1比pos2大                  
						$x = $pos1; $y = $pos2;
										  
						$this->draws[$event]['draw']['RR'][$group][$x][$y] = $match_id;
					} else {
						$teams = [$event, $event];
						$group = 1;
						$x = $y = 0;
					}

					// 记录到match里
					$this->matches[$match_id] = [
						'uuid' => $match_id,
						'id' => $match_id,
						'event' => $event,
						'r' => 0,
						'r1' => "RR",
						'r2' => "RR",
						't1' => $teams[0],
						't2' => $teams[1],
						'bestof' => ($this->tour == "7696" ? 5 : 3),
						'mStatus' => "",
						'h2h' => '',
						'group' => $group,
						'group_name' => $this->draws[$event]['group_id2name'][$group],
						'x' => $x,
						'y' => $y,
						'type' => (!$group ? 'KO' : 'RR'),
					];
				} // end foreach match

			} // end if RR

			if ($ko_type == "KO") {
				// 遍历签位
				$drawlines = [];

				// 从drawlines读取签表
				$pre_pos = 0;
				foreach ($Event["Draw"]["DrawLine"] as $line) {
					$pids = [];
					$pos = $line["Pos"];
					for ($i = 0; $i < $pos - $pre_pos - 1; ++$i) {
						$drawlines[] = $event . "QUAL";
					}
					if (count($line["Players"]["Player"]) > 2) {
						$line["Players"]["Player"] = [$line["Players"]["Player"]];
					}
					foreach ($line["Players"]["Player"] as $p) {
						$pid = trim($p["id"]);
						if (!$pid && strpos($p["PlayerDisplayLine"], "Bye") !== false) {
							$pid = "BYE";
						} else if (!$pid && (strpos($p["PlayerDisplayLine"], "Quali") !== false || strpos($p["PlayerDisplayLine"], "Lucky") !== false || strpos($p["PlayerDisplayLine"], "Alter") !== false)) {
							$pid = "QUAL";
						}
						$pids[] = $pid;
					}
					if ($pids[0] == "BYE") $pids = ['BYE'];
					else if ($pids[0] == "QUAL") $pids = ['QUAL'];
					$drawlines[] = $event . join("/", $pids);
					$pre_pos = $pos;
				}

				// 再从result补读一次
				$pos = -1;
				foreach ($Event["Results"]["Round"][0]["Match"] as $amatch) {
					foreach ($amatch["Players"]["PT"] as $ateam) {
						++$pos;
						if (count($ateam["Player"]) > 2) $ateam["Player"] = [$ateam["Player"]];
						$pids = [];
						foreach ($ateam["Player"] as $p) {
							$pid = $p["id"];
							if (strpos($ateam["PTDisplayLine"], "Bye") !== false) {
								$pid = "BYE";
							} else if (strpos($ateam["PTDisplayLine"], "Quali") !== false || strpos($ateam["PTDisplayLine"], "Lucky") !== false || strpos($ateam["PTDisplayLine"], "Alter") !== false) {
								$pid = "QUAL";
							}
							$pids[] = $pid;
						}
						if ($pids[0] == "BYE") $pids = ['BYE'];
						else if ($pids[0] == "QUAL") $pids = ['QUAL'];
						if ($drawlines[$pos] == $event || $drawlines[$pos] == $event . "QUAL") {
							$drawlines[$pos] = $event . join("/", $pids);
						}
					}
				}

				// 组建首轮签表
				for ($i = 0; $i < count($drawlines); $i += 2) {
					$team1 = $drawlines[$i];
					$team2 = $drawlines[$i + 1];

					$r1 = 1;
					$order = $i / 2 + 1;

					if ($mode == "unnormal") {
						$match_seq = $event_size / pow(2, 1) + $i / 2;
					} else {
						$match_seq = pow(2, ceil(log($event_size) / log(2))) / 2 + $i / 2;
					}
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
						'bestof' => ($this->tour == "7696" ? 5 : 3),
						'mStatus' => "",
						'h2h' => '',
						'group' => $group,
						'x' => $x,
						'y' => $y,
						'type' => (!$group ? 'KO' : 'RR'),
					];
				} // end for 
			} else { // end if KO. if RR

				$event_size = count($Event["Results"]["Round"][1]["Match"]) * 2;
				$_ct = 0;
				foreach ($Event["Results"]["Round"][1]["Match"] as $amatch) {
					++$_ct;
					$ori_matchid = $amatch["Id"];
					$teams = [];

					if (isset($amatch["Players"])) {
						foreach ($amatch["Players"]["PT"] as $team) {
							$pids = [];
							if (count($team["Player"]) > 2) {
								$team["Player"] = [$team["Player"]];
							}
							foreach ($team["Player"] as $p) {
								$pids[] = $p["id"];
							}
							$teams[] = $event . join("/", $pids);
						}
					} else {
						$teams = [$event, $event];
					}
					$r1 = 1;
					$order = $_ct;

					$group = 0; $x = $r1; $y = $order;
					$this->draws[$event]['draw']['KO'][$group][$x][$y] = $ori_matchid;

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

					// 记录到match里
					$this->matches[$ori_matchid] = [
						'uuid' => $ori_matchid,
						'id' => $ori_matchid,
						'event' => $event,
						'r' => $r1,
						'r1' => $r2,
						'r2' => $r3,
						't1' => $teams[0],
						't2' => $teams[1],
						'bestof' => ($this->tour == "7696" ? 5 : 3),
						'mStatus' => "",
						'h2h' => '',
						'group' => $group,
						'x' => $x,
						'y' => $y,
						'type' => (!$group ? 'KO' : 'RR'),
					];
				} // end foreach 
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

					if ($mode == "unnormal") {
						$match_seq = $event_size / pow(2, $r1) + $j - 1;
					} else {
						$match_seq = pow(2, ceil(log($event_size) / log(2))) / pow(2, $r1) + $j - 1;
					}
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
						'bestof' => ($this->tour == "7696" ? 5 : 3),
						'mStatus' => "",
						'h2h' => '',
						'group' => $group,
						'x' => $x,
						'y' => $y,
						'type' => (!$group ? 'KO' : 'RR'),
					];
				}
			} // end for

			// 获取每场比赛结果
			foreach ($Event["Results"]["Round"] as $around) {
				if (!isset($around["Match"][0])) {
					$around["Match"] = [$around["Match"]];
				}

				foreach ($around["Match"] as $amatch) {
					$ori_matchid = $amatch["Id"];
					$match = &$this->matches[$ori_matchid];

					if (isset($amatch["Result"]) && isset($amatch["Result"]["winnerPTId"])) {
						$winner = $amatch["Result"]["winnerPTId"];
					} else {
						$winner = "";
					}

					if ($amatch["finished"] == 1 && isset($amatch["Result"]["Score"])) {
						$score1 = [];
						$score2 = [];
						if (isset($amatch["Result"]["Score"]["Set"])) {
							if (!isset($amatch["Result"]["Score"]["Set"][0])) {
								$amatch["Result"]["Score"]["Set"] = [$amatch["Result"]["Score"]["Set"]];
							}
							foreach ($amatch["Result"]["Score"]["Set"] as $set) {
								$sA = $set["sA"];
								$sB = $set["sB"];
								$tbA = $set["tbA"];
								$tbB = $set["tbB"];
								if ($tbA == 0 && $tbB == 0) $hasTB = false; else $hasTB = true;
								if ($sA > $sB) $set_winner = 1; else $set_winner = 2;

								$score1[] = [$sA, $set_winner == 1 ? 1 : -1, $hasTB ? $tbA : -1];
								$score2[] = [$sB, $set_winner == 2 ? 1 : -1, $hasTB ? $tbB : -1];
							}
						}
						$match['s1'] = $score1;
						$match['s2'] = $score2;
					}

					$mStatus = "";

					if ($winner) {
						$rsn = strtolower($amatch["Result"]["Score"]["rsn"]);
						if ($winner == "A") {
							$mStatus = "F";
							if (strpos($rsn, "ret") !== false) $mStatus = "H";
							else if (strpos($rsn, "def") !== false) $mStatus = "J";
							else if (strpos($rsn, "w/o") !== false) $mStatus = "L";
							$winner = $match['t1'];
						} else {
							$mStatus = "G";
							if (strpos($rsn, "ret") !== false) $mStatus = "I";
							else if (strpos($rsn, "def") !== false) $mStatus = "K";
							else if (strpos($rsn, "w/o") !== false) $mStatus = "M";
							$winner = $match['t2'];
						}
					}
					$match['mStatus'] = $mStatus;

					$_next_match = self::findNextMatchIdAndPos($ori_matchid, $event);
					if ($winner != "" && $_next_match !== null) {
						$next_match = &$this->matches[$_next_match[0]];
						$next_match['t' . $_next_match[1]] = $winner;
					}

				} // end foreach match
			} // end foreach round

		}
	}

	/*
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
	*/

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
		/*
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
		*/
	}

	protected function parseSchedule() {

		$file = join("/", [DATA, 'tour', 'oop', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;
	
		if (!isset($xml["OOP"]["Schedule"]["Day"])) return false;

		$Days = [];
		if (!isset($xml["OOP"]["Schedule"]["Day"][0])) {
			$Days[] = $xml["OOP"]["Schedule"]["Day"];
		} else {
			$Days = $xml["OOP"]["Schedule"]["Day"];
		}

		$maxDay = 0; // 当前已经出了几天了的oop了
		foreach ($Days as $aday) {
			$day = $aday["Seq"];
			$maxDay = $day;
			$isodate = $aday["ISODate"];
			$this->oop[$day] = [
				'date' => $isodate,
				'courts' => [],
			];

			if (!isset($aday["Court"][0])) {
				$aday["Court"] = [$aday["Court"]];
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

				if (!isset($acourt["Matches"]["Match"][0])) {
					$acourt["Matches"]["Match"] = [$acourt["Matches"]["Match"]];
				}

				$lastMatchSeq = 0; // 表示上一场比赛的序号
				foreach ($acourt["Matches"]["Match"] as $amatch) {
					$match_seq = $amatch["seq"]; // 当前比赛的序号

					if ($amatch["NotBeforeISOTime"] != "") {
						$time = strtotime($isodate . " " . $amatch["NotBeforeISOTime"]);
					} else {
						$time = $next_time + ($match_seq - $lastMatchSeq - 1) * 5400;
					}
					$next_time = $time + 5400;
					$lastMatchSeq = $match_seq;

					$matchid = $amatch["MatchId"];

					$event_raw = substr($matchid, 0, 2);
					$event = self::transSextip($event_raw, isset($amatch["Players"][0]["Player"][0]) ? 2 : 1);

					// 取一些选手数据
					$seq = 0;
					foreach ($amatch["Players"] as $team) {
						++$seq;
						if (count($team["Player"]) > 2) {
							$team["Player"] = [$team["Player"]];
						}
						$pids = array_map(function ($d) {
							return $d["id"];
						}, $team["Player"]);
						$teamID = $event . join("/", $pids);
						if (!isset($this->teams[$teamID])) {
							foreach ($team["Player"] as $p) {
								$pid = $p["id"];
								if (!isset($this->players[$pid])) {
									$gender = "F";
									$first = $p["FirstName"];
									$last = $p["SurName"];
									$ioc = $p["Country"];
									$short3 = substr(preg_replace('/[^A-Z]/', '', replace_letters(mb_strtoupper($last))), 0, 3); // 取姓的前3个字母，用于flashscore数据
									$last2 = substr(preg_replace('/[^A-Z]/', '', replace_letters(mb_strtoupper(preg_replace('/^.* /', '', str_replace("-", " ", $last))))), 0, 3); // 取名字最后一部分的前3个字母，用于bets数据
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
							}
							$seeds = [];
							$seed = $team["Seed"]; 
							if ($seed != "") $seeds[] = $seed; 
							$entry = $team["EntryType"]; 
							if ($entry == "DA") $entry = "";
							if ($entry == "LL") $entry = "L";
							else if ($entry == "WC") $entry = "W";
							else if ($entry == "Alt" || $entry == "ALT") $entry = "A";
							else if ($entry == "PR") $entry = "P";
							else if ($entry == "SE") $entry = "S";
							else if ($entry == "ITF") $entry = "I";
							else if ($entry == "JE") $entry = "J";
							if ($entry != "") $seeds[] = $entry;
							$rank = isset($this->rank['s'][join("/", $pids)]) ? $this->rank['s'][join("/", $pids)] : "";
							$this->teams[$teamID] = [
								'uuid' => $teamID,
								's' => $seed,
								'e' => $entry,
								'se' => join("/", $seeds),
								'r' => $rank,
								'p' => array_map(function ($d) {
									return $this->players[$d];
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

						if (!isset($this->matches[$matchid])) {
							$this->matches[$matchid] = [
								'uuid' => $matchid,
								'id' => $matchid,
								'event' => $event,
								'r' => 0,
								'r1' => "",
								'r2' => "",
								't1' => "",
								't2' => "",
								'bestof' => ($this->tour == "7696" ? 5 : 3),
								'mStatus' => "",
								'h2h' => '',
								'group' => 0,
								'x' => 0,
								'y' => 0,
								'type' => 'KO',
							];
						}
						if (in_array(substr($this->matches[$matchid]["t" . $seq], 2), ["", "COMEUP", "LIVE", "TBD", "QUAL"])) {
							$this->matches[$matchid]["t" . $seq] = $teamID;
						}
					}

					if (!isset($this->matches[$matchid])) continue; // 如果签表没有这场比赛就跳过
					$matches = &$this->oop[$day]['courts'][$order]['matches'];
					$matches[$match_seq] = [
						'id' => $matchid,
						'time' => $time,
						'event' => $event,
					];

					$match = &$this->matches[$matchid];
					$match['date'] = $isodate;
					if (isset($match['mStatus']) && $match['mStatus'] == "") { // 如果已经有结果了就不更改状态
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

					if (isset($amatch["Official"]) && isset($amatch["Official"]["OfficialItfId"]) && $amatch["Official"]["OfficialItfId"] != "") {
						$match["umpire"] = [
							'p' => $amatch["Official"]["OfficialItfId"],
							'f' => $amatch["Official"]["FirstName"],
							'l' => $amatch["Official"]["SurName"],
							'i' => $amatch["Official"]["Country"],
						];
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

		// 当oop中缺了最新的赛程时，从result里面补一些oop信息
		$file = join("/", [DATA, 'tour', 'result', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;
	
		if (!isset($this->oop[1])) return; // 第1天赛程都没有
		foreach ($xml["matches"] as $matchSeq => $amatch) {
			$matchid = $amatch["MatchID"];
			if (!isset($amatch["DateSeq"])) continue;
			$day = $amatch["DateSeq"];
			if ($day <= $maxDay) continue;

			$isodate = date('Y-m-d', strtotime($this->oop[1]["date"] . " +" . ($day - 1) . " days"));
			if (!isset($this->oop[$day])) {
				$this->oop[$day] = [
					'date' => $isodate,
					'courts' => [],
				];
			}

			$order = $amatch["CourtID"] + 100;
			$name = "Court " . $amatch["CourtID"];
			if (!isset($this->oop[$day]['courts'][$order])) {
				$this->oop[$day]['courts'][$order] = [
					'name' => $name,
					'matches' => [],
				];
			}

			$time = strtotime($amatch["MatchTimeStamp"]);

			$match_seq = $matchSeq;
			$matchid = $amatch["MatchID"];

			$event_raw = substr($matchid, 0, 2);
			$event = self::transSextip($event_raw, $amatch["PlayerIDA2"] != "" ? 2 : 1);

			$matches = &$this->oop[$day]['courts'][$order]['matches'];
			$matches[$match_seq] = [
				'id' => $matchid,
				'time' => $time,
				'event' => $event,
			];
			$match = &$this->matches[$matchid];
			$match['date'] = $isodate;

			if (isset($match['mStatus']) && $match['mStatus'] == "") { // 如果已经有结果了就不更改状态
				$match['mStatus'] = 'A';
				$match['s1'] = $time;
				$match['s2'] = $name;
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

	protected function parseLive() {

		$file = join("/", [SHARE, 'down_result', 'wta_live']);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;

		foreach ($xml as $amatch) {
			if ($amatch["EventID"] != $this->tour) continue;
			if ($amatch["MatchState"] == "P" || ($amatch["MatchState"] == "F" && time() - strtotime($amatch["LastUpdated"]) < 10 * 60)) {
				$matchid = $amatch["MatchID"];
				self::getResult($matchid, $amatch);

				$this->live_matches[] = $matchid;
			}
		}
	}

	protected function getResult($matchid, &$m, $match_time = "", $match_court = "") {
		$event_raw = substr($matchid, 0, 2);
		$event = self::transSextip($event_raw, $m["DrawMatchType"] == "D" ? 2 : 1);

		if (!isset($this->matches[$matchid])) {
			$this->matches[$matchid] = [];
			$this->matches[$matchid]['uuid'] = $matchid;
		}
		$match = &$this->matches[$matchid];
		$match['tipmsg'] = @$m["Message"];
		$match['bestof'] = 3;

		$state = $m["MatchState"];
		$winner = @$m["Winner"];
		$sScore = @$m["ScoreString"];

		$score1 = $score2 = [];

		$mStatus = @$match['mStatus'];
		if ($mStatus != "A") {
			$match['dura'] = @$m["MatchTimeTotal"];
		}
		if ($mStatus != "" && strpos("FGHIJKLM", $mStatus) !== false) return true;

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
			} else if ($state == "P") {
				$mStatus = "B";
			} else {
				fputs(STDERR, $matchid . "\t" . $sScore . "\t" . $winner . "\n");
			}
		}

		foreach ([1, 2, 3, 4, 5] as $set) {
			if ($set > $match["bestof"]) break;
			$a = trim(@$m["ScoreSet" . $set . "A"]);
			$b = trim(@$m["ScoreSet" . $set . "B"]);
			if ($a === '' || $b === '' || $a === null || $b === null) break;
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
			$match['serve'] = ($serve === "" ? "" : ($serve == "A" ? 1 : 2));
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
