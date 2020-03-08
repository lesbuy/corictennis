<?php

require_once('base.class.php');

class Event extends Base{

	protected $eventCodeReset = [];
	protected $otherSextip = 0;

	public function  process() {
		$this->preprocess();
		$this->parsePlayer();
		$this->parseDraw();
		$this->parseResult();
		$this->parseSchedule();
		$this->parseLive();
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

	}

	protected function parsePlayer() {
		$file = join("/", [SHARE, 'down_result', 'draw', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = simplexml_load_file($file);
		if (!$xml) return false;

		$players = [];

		foreach ($xml->Events->Event as $event) {
			$sextip = $event->EventTypeCode . "";
			$sextip = self::transSextip($sextip, count($event->Draw->DrawLine[0]->Players->Player));

			foreach ($event->Draw->DrawLine as $team) {
				$pids = [];

				foreach ($team->Players->Player as $p) {
					$pid = $p->attributes()->id . "";
					if (!$pid) continue;
					$pids[] = $pid;
					if (preg_match('/^[A-Z0-9]{4}$/', $pid)) $gender = "M"; else $gender = "F";
					$first = $p->FirstName . "";
					$last = $p->SurName . "";
					$ioc = $p->Country . "";
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

				$entry = $team->EntryType . "";
				if ($entry == "LL") $entry = "L";
				else if ($entry == "WC") $entry = "W";
				else if ($entry == "Alt") $entry = "A";
				else if ($entry == "PR") $entry = "P";
				else if ($entry == "SE") $entry = "S";
				else if ($entry == "ITF") $entry = "I";
				else if ($entry == "JE") $entry = "J";
				$seed = $team->Seed . "";

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

			// 在RR里，还要遍历每场比赛，看看有没有退赛的
			if ($event->attributes()->Type . "" == "RR") {
				foreach ($event->Results->Round as $around) {
					foreach ($around->Match as $amatch) {
						if (isset($amatch->Players->PT)) {
							foreach ($amatch->Players->PT as $ateam) {
								$pids = [];
								foreach ($ateam->Player as $aplayer) {
									$pid = $aplayer->attributes()->id . "";
									if (!$pid) continue;
									$pids[] = $pid;
								}
								if (!count($pids)) continue;
								if (isset($this->teams[$sextip . join("/", $pids)])) continue;

								foreach ($ateam->Player as $aplayer) {
									$pid = $aplayer->attributes()->id . "";
									if (isset($players[$pid])) continue;
									else {
										if (preg_match('/^[A-Z0-9]{4}$/', $pid)) $gender = "M"; else $gender = "F";
										$first = $aplayer->FirstName . "";
										$last = $aplayer->SurName . "";
										$ioc = $aplayer->Country . "";
										$players[$pid] = [
											'p' => $pid,
											'g' => $gender,
											'f' => $first,
											'l' => $last,
											'i' => $ioc
										];
									
									}
								}

								$entry = $ateam->attributes()->eType . "";
								if ($entry == "LL") $entry = "L";
								else if ($entry == "WC") $entry = "W";
								else if ($entry == "Alt") $entry = "A";
								else if ($entry == "PR") $entry = "P";
								$seed = $ateam->attributes()->seed . "";

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

	protected function parseDraw() {

		$file = join("/", [SHARE, 'down_result', 'draw', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = simplexml_load_file($file);
		if (!$xml) return false;

		$web_const = require_once(join("/", [WEB, 'config', 'const.php']));

		foreach ($xml->Events->Event as $Event) {

			$this->tourname = $Event->TournamentTitle . '';
			$this->city = $Event->Location . '';
			$this->city = preg_replace('/,.*$/', '', $this->city);
			if (!$this->surface) {
				$this->surface = $Event->Surface . '';
				if ($this->surface != 'Clay' && $this->surface != 'Grass' && $this->surface != 'Carpet') $this->surface = 'Hard';
			}
			$scoreCode = $Event->EventScorerCode . '';
			$prize = $Event->Tfc . '';
			if (strpos($prize, 'USD') !== false || strpos($prize, '$') !== false) $this->currency = "$";
			else if (strpos($prize, 'EUR') !== false || strpos($prize, '€') !== false) $this->currency = "€";
			else if (strpos($prize, 'GBP') !== false || strpos($prize, '￡') !== false) $this->currency = "￡";
			else if (strpos($prize, 'AUD') !== false || strpos($prize, 'A$') !== false) $this->currency = "A$";
			$prize = intval(preg_replace('/[^0-9]/', '', $prize));

			$event_raw = $Event->EventTypeCode . "";
			$event = self::transSextip($event_raw, count($Event->Draw->DrawLine[0]->Players->Player));

			$f = substr($event, 0, 1);
			if ($f == 'M' || $f == 'Q') {
				$this->atpid = $scoreCode;
				$this->atpprize = $prize;
			} else if ($f == 'W' || $f == 'P') {
				$this->wtaid = $scoreCode;
				$this->wtaprize = $prize;
			}

			$event_size = intval($Event->DrawSize);
			$event_round = count($Event->Results->Round);
			$eventid = $web_const['grandslam']['type2id'][$event];
			$eventid4oop = $web_const['grandslam']['id2oopid'][$eventid];

			$ko_type = $Event->attributes()->Type . "";
			if (strpos($event, "D") !== false) {
				$sd = "D";
			} else {
				$sd = "S";
			}
			if (strpos($Event->DrawTypeTitle, "Quali") !== false) {
				$qm = "Q";
			} else {
				$qm = "M";
			}
			$ct = 0;
			foreach ($Event->Results->Round as $r) {
				$ct += count($r->Match);
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

			foreach ($Event->Breakdown->Place as $place) {
				$name = $place->Name . '';
				$prize = $place->PrizeRound . '';
				$point = intval($place->PointsRound . '');
				$placeid = intval($place->attributes()->id . '');

				if ($name == "Winner") $round = "W";
				else if ($name == "Final") $round = "F";
				else if ($name == "Semifinals") $round = "SF";
				else if ($name == "Quarterfinals") $round = "QF";
				else if ($name == "First Round") $round = "R1";
				else if ($name == "Second Round") $round = "R2";
				else if ($name == "Third Round") $round = "R3";
				else if ($name == "Fourth Round") $round = "R4";
				else if ($name == "Round 1") $round = "Q1";
				else if ($name == "Round 2") $round = "Q2";
				else if ($name == "Round 3") $round = "Q3";
				else if ($name == "Round 4") $round = "Q4";
				else if ($name == "Qualifiers") $round = "Qualify";
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

				$this->draws[$event]['round'][$round] = [
					'id' => $placeid,
					'point' => $point,
					'prize' => $prize,
					'alias' => $_round,
				];
			}

			// 小组赛时获取一些基本信息
			if ($ko_type == "RR") {
				$this->draws[$event]['groups'] = count($Event->RoundRobinGroups->Group);
				$this->draws[$event]['playersPerGroup'] = count($Event->Draw->DrawLine) / $this->draws[$event]['groups'];
				$this->draws[$event]['matchesPerGroupPerRound'] = floor($this->draws[$event]['playersPerGroup'] / 2);
				$this->draws[$event]['maxRRRounds'] = $this->draws[$event]['playersPerGroup'] * ($this->draws[$event]['playersPerGroup'] - 1) / 2 / $this->draws[$event]['matchesPerGroupPerRound'];

				foreach ($Event->RoundRobinGroups->Group as $_gr) {
					$_gr_name = $_gr->attributes()->Name . "";
					$_gr_num = $_gr->attributes()->Number . "";
					$this->draws[$event]['group_name2id'][$_gr_name] = $_gr_num - 1;
					$this->draws[$event]['group_id2name'][$_gr_num] = $_gr_name;
				}

				$_group = &$this->draws[$event]['group'];
				for ($i = 0; $i < $this->draws[$event]['groups']; ++$i) {
					$_group[$i] = [];
				}
				foreach ($Event->Draw->DrawLine as $drawline) {
					$_gr_name = $drawline->attributes()->GroupName . "";
					$_gr_num = $this->draws[$event]['group_name2id'][$_gr_name];

					$pids = [];
					foreach ($drawline->Players->Player as $p) {
						$pids[] = $p->attributes()->id . "";
					}
					$_group[$_gr_num][] = $event . join("/", $pids);
				}

				// 弥补某些已经退赛的选手
				foreach ($Event->Results->Round->Match as $amatch) {
					$_gr_name = $amatch->attributes()->RRGroup . "";
					if (!$_gr_name) continue;
					$_gr_num = $this->draws[$event]['group_name2id'][$_gr_name];
					foreach ($amatch->Players->PT as $team) {
						$pids = [];
						foreach ($team->Player as $p) {
							$pids[] = $p->attributes()->id . "";
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
				foreach ($Event->Results->Round->Match as $amatch) {
					$match_id = $amatch->attributes()->Id . "";
					$teams = [];
					if (isset($amatch->Players)) {
						foreach ($amatch->Players->PT as $team) {
							$pids = [];
							foreach ($team->Player as $p) {
								$pids[] = $p->attributes()->id . "";
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
				foreach ($Event->Draw->DrawLine as $line) {
					$pids = [];
					foreach ($line->Players->Player as $p) {
						$pid = trim($p->attributes()->id . "");
						if (!$pid && strpos($p->PlayerDisplayLine, "Bye") !== false) {
							$pid = "BYE";
						} else if (!$pid && (strpos($p->PlayerDisplayLine, "Quali") !== false || strpos($p->PlayerDisplayLine, "Lucky") !== false || strpos($p->PlayerDisplayLine, "Alter") !== false)) {
							$pid = "QUAL";
						}
						$pids[] = $pid;
					}
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

				$event_size = count($Event->Results->Round[1]->Match) * 2;
				$_ct = 0;
				foreach ($Event->Results->Round[1]->Match as $amatch) {
					++$_ct;
					$ori_matchid = $amatch->attributes()->Id . "";
					$teams = [];

					if (isset($amatch->Players)) {
						foreach ($amatch->Players->PT as $team) {
							$pids = [];
							foreach ($team->Player as $p) {
								$pids[] = $p->attributes()->id . "";
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
			foreach ($Event->Results->Round as $around) {
				foreach ($around->Match as $amatch) {
					$ori_matchid = $amatch->attributes()->Id . "";
					$match = &$this->matches[$ori_matchid];

					if (isset($amatch->Result) && isset($amatch->Result->attributes()->winnerPTId)) {
						$winner = $amatch->Result->attributes()->winnerPTId . "";
					} else {
						$winner = "";
					}

					if ($amatch->attributes()->finished . "" == "1" && isset($amatch->Result->Score)) {
						$score1 = [];
						$score2 = [];
						foreach ($amatch->Result->Score->Set as $set) {
							$sA = intval($set->attributes()->sA);
							$sB = intval($set->attributes()->sB);
							$tbA = intval($set->attributes()->tbA);
							$tbB = intval($set->attributes()->tbB);
							if ($tbA == 0 && $tbB == 0) $hasTB = false; else $hasTB = true;
							if ($sA > $sB) $set_winner = 1; else $set_winner = 2;

							$score1[] = [$sA, $set_winner == 1 ? 1 : -1, $hasTB ? $tbA : -1];
							$score2[] = [$sB, $set_winner == 2 ? 1 : -1, $hasTB ? $tbB : -1];
						}
						$match['s1'] = $score1;
						$match['s2'] = $score2;
					}

					$mStatus = "";

					if ($winner) {
						if ($winner == "A") {
							$mStatus = "F";
							if (strpos($amatch->Result->Score->attributes()->rsn, "Ret") !== false) $mStatus = "H";
							else if (strpos($amatch->Result->Score->attributes()->rsn, "Def") !== false) $mStatus = "J";
							else if (strpos($amatch->Result->Score->attributes()->rsn, "W/O") !== false) $mStatus = "L";
							$winner = $match['t1'];
						} else {
							$mStatus = "G";
							if (strpos($amatch->Result->Score->attributes()->rsn, "Ret") !== false) $mStatus = "I";
							else if (strpos($amatch->Result->Score->attributes()->rsn, "Def") !== false) $mStatus = "K";
							else if (strpos($amatch->Result->Score->attributes()->rsn, "W/O") !== false) $mStatus = "M";
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

}
