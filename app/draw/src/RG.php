<?php

require_once('base.class.php');

class Event extends Base{

	protected $round_points = [
		'MS' => ['R1' => 10, 'R2' => 45, 'R3' => 90, 'R4' => 180, 'QF' => 360, 'SF' => 720, 'F' => 1200, 'W' => 2000],
		'WS' => ['R1' => 10, 'R2' => 70, 'R3' => 130, 'R4' => 240, 'QF' => 430, 'SF' => 780, 'F' => 1300, 'W' => 2000],
		'MD' => ['R1' => 0, 'R2' => 90, 'R3' => 180, 'QF' => 360, 'SF' => 720, 'F' => 1200, 'W' => 2000],
		'WD' => ['R1' => 10, 'R2' => 130, 'R3' => 240, 'QF' => 430, 'SF' => 780, 'F' => 1300, 'W' => 2000],
		'QS' => ['Q1' => 0, 'Q2' => 8, 'Q3' => 16, 'Qualify' => 25],
		'PS' => ['Q1' => 2, 'Q2' => 20, 'Q3' => 30, 'Qualify' => 40],
	];

	public function  process() {
		$this->atpid = "0520";
		$this->wtaid = "0903";

		$file = join("/", [SCRIPT, 'gs', 'ori', $this->year, $this->tour, 'type']);
		if (!file_exists($file)) return false;

		$this->preprocess();

		$fp = fopen($file, "r");
		while ($line = fgets($fp)) {
			$line_arr = explode("\t", trim($line));
			$event_raw = $line_arr[0];
			$event = $line_arr[1];
			$event_size = $line_arr[2];
			$event_round = $line_arr[3];
			$eventid4oop = $line_arr[4];
			$eventid = $this->web_const['grandslam']['type2id'][$event];

			$this->draws[$event] = [
				'uuid' => $event_raw,
				'event' => $event,
				'eventid' => $eventid,
				'eventid2' => $eventid4oop,
				'total_round' => $event_round,
				'asso' => in_array($event, ['QS', 'QD', 'MS', 'MD']) ? 'ATP' : (in_array($event, ['PS', 'PD', 'WS', 'WD']) ? 'WTA' : ''),
				'status' => 0,
				'type' => 'KO',
				'sd' => 'S',
				'qm' => 'M',
				'ct' => 0,
				'groups' => 0,
				'playersPerGroup' => 0,
				'maxRRRounds' => 0,
				'matchesPerGroupPerRound' => 0,
				'group' => [],
				'draw' => [],
				'round' => [],
			];

			$this->parsePlayer($event);
			$this->parseDraw($event, $event_size, $event_round, $event_raw);

		}
		fclose($fp);

		for ($i = 1; $i <= 21; ++$i) {
			$this->parseSchedule($i);
			$this->parseResult($i);
			//$this->parseExtra($i);
		}
		$this->parseLive();
		//$this->reviseEntry();
		$this->appendH2HandFS();
		$this->calaTeamFinal();
	}

	public function processLive() {
	}

	public function preprocess() {
		$file = join("/", [SCRIPT, 'gs', 'etl', $this->year, $this->tour, 'players']);
		$fp = fopen($file, "r");
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);
			$uuid = $arr[0];
			$pid = $arr[1];
			$rank = $arr[2];
			$rankd = $arr[3];
			$this->uuid2id[$uuid] = $pid;
			$this->rank['s'][$pid] = $rank;
			$this->rank['d'][$pid] = $rankd;
			$first = $arr[6];
			$last = $arr[7];
			$gender = $arr[4];
			$ioc = $arr[5];
			$short3 = substr(preg_replace('/[^A-Z]/', '', replace_letters(mb_strtoupper($last . $first))), 0, 3); // 取姓的前3个字母，用于flashscore数据
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
		fclose($fp);

		$file = join("/", [SCRIPT, 'gs', 'etl', $this->year, $this->tour, 'h2hs']);
		$fp = fopen($file, "r");
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);
			$this->h2h[$arr[0] . "\t" . $arr[1]] = $arr[2];
		}
		fclose($fp);

		$file = join("/", [SCRIPT, 'gs', 'ori', $this->year, $this->tour, 'conf']);
		$fp = fopen($file, "r");
		while ($line = trim(fgets($fp))) {
			$arr = explode("=", $line);
			if (count($arr) != 2 || substr($arr[0], 0, 1) == "#") continue;
			if ($arr[0] == "qualifying_first_day") {
				$this->quali_first_day = str_replace("\"", "", $arr[1]);
				break;
			}
		}
		fclose($fp);

		$file = join("/", [SCRIPT, 'gs', 'etl', $this->year, $this->tour, 'wclist']);
		$fp = fopen($file, "r");
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);
			if (!isset($this->wclist[$arr[0]])) $this->wclist[$arr[0]] = [];
			$this->wclist[$arr[0]][$arr[2]] = $arr[1];
		}
		fclose($fp);

		$this->atpprize = $this->wtaprize = 30000000;
	}

	protected function parsePlayer() {
		$args = func_get_args();
		$event = $args[0];

		$file = join("/", [SCRIPT, 'gs', 'ori', $this->year, $this->tour, 'draw', $event]);
		if (!file_exists($file)) return false;

		$html = file_get_contents($file);
		if (!$html) return false;

		$json = json_decode($html, true);
		if (!$json) return false;

		$players = [];

		if (!$json['tournamentEvent']['oneColumn']) return false;

		foreach ($json['tournamentEvent']['oneColumn']['matches'] as $amatch) {

			foreach (['teamA', 'teamB'] as $aside) {
				$team_uuid = "";
				$pids = [];
				foreach ($amatch[$aside]['players'] as $aplayer) {
					$last = ucwords(strtolower($aplayer['lastName']));

					if (in_array($last, ["Qualifié", "Qualifiée", "Bye", "Alternates"])) {
						$gender = "M";
						if (in_array($last, ["Qualifié", "Qualifiée"])) {
							$pid = "QUAL";
							$last = "Qualifier";
							$uuid = -1;
						} else if (in_array($last, ["Bye"])) {
							$pid = "BYE";
							$last = "Bye";
							$uuid = -2;
						} else if (in_array($last, ["Alternates"])) {
							$pid = "ALT";
							$last = "Alternate";
							$uuid = -3;
						}
						$this->players[$pid] = [
							'p' => $pid, 
							'g' => $gender, 
							'f' => '',
							'l' => $last, 
							'i' => '',
							's' => '',
							's2' => '',
							'rs' => '',
							'rd' => '',
						];
					} else {
						$ioc = $aplayer['country'];
						$first = $aplayer['firstName'];
						$gender = $aplayer['sex'] == "M" ? "M" : "F";
						$uuid = intval(preg_replace('/^.*\//', '', $aplayer['playerCardUrl']));
						$pid = $this->uuid2id[$uuid];
					}

					$pids[] = $pid;
					$team_uuid = $team_uuid == "" ? $uuid : $team_uuid . "/" . $uuid;
				}
				$team_uuid = $event . $team_uuid;

				$seed = $amatch[$aside]['seed'] !== null ? $amatch[$aside]['seed'] : "";
				$entry = $amatch[$aside]['entryStatus'] !== null ? $amatch[$aside]['entryStatus'] : "";
				if (isset($this->wclist[$event][join("/", $pids)])) {
					$entry = $this->wclist[$event][join("/", $pids)];
				}
				$seeds = [];
				if ($seed) $seeds[] = $seed; if ($entry) $seeds[] = $entry;
				$rank = isset($this->rank['s'][join("/", $pids)]) ? $this->rank['s'][join("/", $pids)] : "";

				$this->teams[$team_uuid] = [
					'uuid' => $team_uuid,
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
		}

		$this->teams[$event . 'LIVE'] = ['uuid' => $event . 'LIVE', 's' => '', 'r' => '', 'p' => [['p' => 'LIVE', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
		$this->teams[$event . 'TBD'] = ['uuid' => $event . 'TBD', 's' => '', 'r' => '', 'p' => [['p' => 'TBD', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
		$this->teams[$event . 'QUAL'] = ['uuid' => $event . 'QUAL', 's' => '', 'r' => '', 'p' => [['p' => 'QUAL', 'g' => '', 'f' => '', 'l' => 'Qualifier', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
		$this->teams[$event . 'COMEUP'] = ['uuid' => $event . 'COMEUP', 's' => '', 'r' => '', 'p' => [['p' => 'COMEUP', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
		$this->teams[$event . 'BYE'] = ['uuid' => $event . 'BYE', 's' => '', 'r' => '', 'p' => [['p' => 'BYE', 'g' => '', 'f' => '', 'l' => 'Bye', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];

	}

	protected function parseDraw() {
		$args = func_get_args();
		$event = $args[0];
		$event_size = $args[1];
		$event_round = $args[2];
		$event_raw = $args[3];

		$file = join("/", [SCRIPT, 'gs', 'ori', $this->year, $this->tour, 'draw', $event]);
		if (!file_exists($file)) return false;

		$html = file_get_contents($file);
		if (!$html) return false;

		$json = json_decode($html, true);
		if (!$json) return false;

		$draw_type = "KO";
		if ($event_round == 0) $draw_type = "RR";

		$sd = "S";
		$qm = "M";
		foreach ($json['types'] as $atype) {
			if ($atype['code'] == $event_raw) {
				if (strpos($atype['label'], "quali") !== false) {
					$qm = "Q";
				}
				if (strpos($atype['label'], "ouble") !== false || strpos($atype['label'], "egend") !== false) {
					$sd = "D";
				}
				break;
			} else {
				continue;
			}
		}

		$this->draws[$event]['event'] = $event;
		$this->draws[$event]['type'] = $draw_type;
		$this->draws[$event]['sd'] = $sd;
		$this->draws[$event]['qm'] = $qm;

		if (!$json['tournamentEvent']['oneColumn']) return false;
		$this->draws[$event]['ct'] = 0;
		$match_first_round = count($json['tournamentEvent']['oneColumn']['matches']);
		for ($i = 0; $i < $event_round; ++$i) {
			$this->draws[$event]['ct'] += $match_first_round >> $i;
		}

		if ($draw_type == "RR") {
			$this->draws[$event]['groups'] = $json['groupNum'];
			$this->draws[$event]['playersPerGroup'] = $json['playersPerGroup'];
			$this->draws[$event]['maxRRRounds'] = $this->draws[$event]['playersPerGroup'] - 1;
			$this->draws[$event]['matchesPerGroup'] = $this->draws[$event]['playersPerGroup'] * ($this->draws[$event]['playersPerGroup'] - 1) / 2;

			$_groups = [];
			foreach($json['groups'] as $group) {
				$_teams = [];
				foreach ($group['teams'] as $team) {
					$_teams[] = $event . $team;
				}
				$_groups[] = $_teams;
			}
			$this->draws[$event]['group'] = $_groups;

			foreach ($this->draws[$event]['group'] as $x => $v1) {
				foreach ($v1 as $y => $v2) {
					$this->teams[$v2]['group'] = $x + 1;
					$this->teams[$v2]['pos'] = $y + 1;
				}
			}
		}

		// 处理每一轮奖金
		/*
		foreach ($json['prizeMoney'] as $k => $r) {
			$id = $json['totalRounds'] - $k + 1;
			$prize = str_replace(",", "", $r['money']) + 0;
			if ($id == 1) $round = "W";
			else if ($id == 2) $round = "F";
			else if ($id == 3) $round = "SF";
			else if ($id == 4) $round = "QF";
			else $round = "R" . ($k + 1);

			$this->draws[$event]['round'][$round] = [
				'id' => $id,
				'prize' => $prize,
				'point' => isset($this->round_points[$event]) ? $this->round_points[$event][$round] : 0,
				'alias' => $round,
			];
		}
		*/

		// 处理首轮
		$column = $json['tournamentEvent']['oneColumn'];

		foreach ($column['matches'] as $key => $amatch) {
			$match_uuid = $amatch['id'];
			if ($draw_type == "RR") {
				$seq = intval(substr($match_uuid, 2));
				if ($seq >= 8) {
					$r1 = 0; $r2 = $r3 = "RR";
				} else {
					$r1 = 1; $r2 = $r3 = "F";
				}

				$key = $seq == 1 ? 0 : floor(($seq - 8) / 3) * 20 + ($seq - 8) % 3 + 1 + !(($seq - 7) % 3) * 3 - 1;
			} else {
				$r1 = $column['roundNumber'];
				if ($qm == "Q") {
					$r2 = $r3 = "Q" . $r1;
				} else {
					if ($r1 == $event_round) {
						$r2 = $r3 = "F";
					} else if ($r1 == $event_round - 1) {
						$r2 = $r3 = "SF";
					} else if ($r1 == $event_round - 2) {
						$r2 = $r3 = "QF";
					} else {
						$r2 = "R" . $r1;
						$r3 = "R" . round($event_size / exp(log(2) * ($r1 - 1)));
					}
				}
			}

			$matchid = $event . sprintf("%03d", $r1 * 100 + $key + 1);

			$teamA_uuid = $event . join("/", array_map(function ($d) {
				$last = ucwords(strtolower($d['lastName']));
				if ($last == "Qualifié" || $last == "Qualifiée") {return -1;}
				else if ($last == "Bye") {return -2;}
				else if ($last == "Alternates") {return -3;}
				else {return intval(preg_replace('/^.*\//', '', $d['playerCardUrl']));}
			}, $amatch['teamA']['players']));
			$teamB_uuid = $event . join("/", array_map(function ($d) {
				$last = ucwords(strtolower($d['lastName']));
				if ($last == "Qualifié" || $last == "Qualifiée") {return -1;}
				else if ($last == "Bye") {return -2;}
				else if ($last == "Alternates") {return -3;}
				else {return intval(preg_replace('/^.*\//', '', $d['playerCardUrl']));}	
			}, $amatch['teamB']['players']));

			if ($teamA_uuid != $event && $teamB_uuid != $event) {
				$pid1 = join("/", array_map(function ($d) {return $d['p'];}, $this->teams[$teamA_uuid]['p']));
				$pid2 = join("/", array_map(function ($d) {return $d['p'];}, $this->teams[$teamB_uuid]['p']));
				if (isset($this->h2h[$pid1 . "\t" . $pid2])) {
					$h2h = $this->h2h[$pid1 . "\t" . $pid2];
				} else {
					$h2h = "0:0";
				}
			} else {
				$h2h = "";
			}

			$group = $x = $y = 0;
			// 获取轮次信息。r1是纯数字轮次，r2形如R128,R64,QF，r3形如R1,R2,QF
			if ($draw_type == "KO") {
				if ($qm == "Q") {
					$r2 = $r3 = "Q" . $r1;
				} else {
					if ($r1 == $event_round) {
						$r2 = $r3 = "F";
					} else if ($r1 == $event_round - 1) {
						$r2 = $r3 = "SF";
					} else if ($r1 == $event_round - 2) {
						$r2 = $r3 = "QF";
					} else {
						$r2 = "R" . $r1;
						$r3 = "R" . round($event_size / exp(log(2) * ($r1 - 1)));
					}
				}
			} else {
				if ($r1 > $this->draws[$event]['maxRRRounds']) {
					$r2 = $r3 = "F";
				} else {
					$r2 = $r3 = "RR";
				}
			}

			if (isset($this->round_points[$event])) { 
				$this->draws[$event]['round'][$r2] = [
					'id' => count($this->round_points[$event]) - array_search($r2, array_keys($this->round_points[$event])),
					'point' => $this->round_points[$event][$r2],
					'prize' => 0,
					'alias' => $r3,
				];
			}

			// 对于RR轮次，将team的group和pos，记录在draw里（拉成二维）。RR比赛的KO轮次不变，只需要调小r1
			if ($draw_type == "RR") {
				if ($r1 <= $this->draws[$event]['maxRRRounds']) {
					$pos1 = $this->teams[$team1]['pos'];
					$pos2 = $this->teams[$team2]['pos'];
					$group = $this->teams[$team2]['group'];
				
					if ($pos1 < $pos2) {$x = $pos1; $y = $pos2;}
					else {$y = $pos1; $x = $pos2;}

					$this->draws[$event]['draw']['RR'][$group][$x][$y] = $match_uuid;
				} else {
					$group = 0;
					$x = $r1 - $this->draws[$event]['maxRRRounds'];
					$y = $order;
					$this->draws[$event]['draw']['KO'][$group][$x][$y] = $match_uuid;
				}
			} else {
				$group = 0;
				$x = $r1;
				$y = $key + 1;
				$this->draws[$event]['draw']['KO'][$group][$x][$y] = $match_uuid;
			}
			
			$this->matches[$match_uuid] = [
				'uuid' => $match_uuid,
				'id' => $matchid,
				'event' => $event,
				'r' => $r1,
				'r1' => $r2,
				'r2' => $r3,
				't1' => $teamA_uuid,
				't2' => $teamB_uuid,
				'bestof' => $event == "MS" ? 5 : 3,
				'mStatus' => "",
				'h2h' => $h2h,
				'group' => $group,
				'x' => $x,
				'y' => $y,
				'type' => (!$group ? 'KO' : 'RR'),
			];

			$this->match_uuid2matchid[$match_uuid] = $matchid;
		}

		for ($r1 = 2; $r1 <= $event_round; ++$r1) {
			if ($draw_type == "KO") {
				if ($qm == "Q") {
					$r2 = $r3 = "Q" . $r1;
				} else {
					if ($r1 == $event_round) {
						$r2 = $r3 = "F";
					} else if ($r1 == $event_round - 1) {
						$r2 = $r3 = "SF";
					} else if ($r1 == $event_round - 2) {
						$r2 = $r3 = "QF";
					} else {
						$r2 = "R" . $r1;
						$r3 = "R" . round($event_size / exp(log(2) * ($r1 - 1)));
					}
				}

				// 处理轮次信息
				if (isset($this->round_points[$event])) { 
					$this->draws[$event]['round'][$r2] = [
						'id' => count($this->round_points[$event]) - array_search($r2, array_keys($this->round_points[$event])),
						'point' => $this->round_points[$event][$r2],
						'prize' => 0,
						'alias' => $r3,
					];

					if ($r2 == "F") {
						$this->draws[$event]['round']["W"] = [
							'id' => count($this->round_points[$event]) - array_search("W", array_keys($this->round_points[$event])),
							'point' => $this->round_points[$event]["W"],
							'prize' => 0,
							'alias' => "W",
						];
					} else if ($r2 == "Q3") {
						$this->draws[$event]['round']["Qualify"] = [
							'id' => count($this->round_points[$event]) - array_search("Qualify", array_keys($this->round_points[$event])),
							'point' => $this->round_points[$event]["Qualify"],
							'prize' => 0,
							'alias' => "Qualify",
						];
					}

					uasort($this->draws[$event]['round'], 'self::sortByRoundId');
				}

			}

			$round_matches = $match_first_round >> ($r1 - 1);

			$group = $x = $y = 0;

			for ($order = 1; $order <= $round_matches; ++$order) {
				$matchid = $event . $r1 . ($order < 10 ? '0' . $order : $order);
				$match_uuid = floor(intval(substr($this->matches[$this->draws[$event]['draw']['KO'][$group][$r1 - 1][$order * 2]]['uuid'], 2)) / 2);
				$match_uuid = $event_raw . "0" . ($match_uuid < 10 ? '0' . $match_uuid : $match_uuid);

				$this->draws[$event]['draw']['KO'][$group][$r1][$order] = $match_uuid;

				$this->matches[$match_uuid] = [
					'uuid' => $match_uuid,
					'id' => $matchid,
					'event' => $event,
					'r' => $r1,
					'r1' => $r2,
					'r2' => $r3,
					't1' => $event,
					't2' => $event,
					'bestof' => $event == "MS" ? 5 : 3,
					'mStatus' => "",
					'h2h' => "",
					'group' => $group,
					'x' => $r1,
					'y' => $order,
					'type' => (!$group ? 'KO' : 'RR'),
				];

				$this->match_uuid2matchid[$match_uuid] = $matchid;
			}
		}

		return true;
	}

	protected function parseResult() {
		$args = func_get_args();
		$day = $args[0];

		$file = join("/", [SCRIPT, 'gs', 'ori', $this->year, $this->tour, 'result', $day]);
		if (!file_exists($file)) return false;

		$html = file_get_contents($file);
		if (!$html) return false;

		$json = json_decode($html, true);
		if (!$json) return false;

		if (isset($json['error'])) return false;

		foreach ($json['matches'] as $m) {
			$matchid = $m['id'];
			self::getResult($matchid, $m);
		}

		return true;
	}

	protected function parseExtra() {
	}

	protected function parseSchedule() {
		$args = func_get_args();
		$day = $args[0];

		$file = join("/", [SCRIPT, 'gs', 'ori', $this->year, $this->tour, 'schedule', $day]);
		if (!file_exists($file)) return false;

		$html = file_get_contents($file);
		if (!$html) return false;

		$json = json_decode($html, true);
		if (!$json) return false;

		if (!isset($json['matches']) || count($json['matches']) == 0) return false;

		$date = date('Y-m-d', strtotime($this->quali_first_day . " +$day days") - 86400);
		$this->oop[$day] = [
			'date' => $date,
			'courts' => [],
		];

		$court_count = [];
		$courtId = 0;
		$courtSeq = 0;

		$start_time = strtotime($date . " 17:00:00");

		foreach ($json['matches'] as $amatch) {
			if ($amatch['matchData']['status'] == "CANCELED") continue;
			if (!isset($this->match_uuid2matchid[$amatch['id']])) continue;

			$courtName = $amatch['matchData']['courtName'];

			if (strpos($courtName, 'Philippe') !== false) {
				$courtId = 1;
			} else if (strpos($courtName, 'Suzanne') !== false) {
				$courtId = 2;
			} else if (strpos($courtName, 'Simonne') !== false) {
				$courtId = 3;
			} else if ($courtName == "Court 1") {
				$courtId = 4;
			} else if ($courtName == "Court 14") {
				$courtId = 5;
			} else if ($courtName == "Court 7") {
				$courtId = 6;
			} else if ($courtName == "Court 6") {
				$courtId = 7;
			} else if ($courtName == "Court TBA 1") {
				$courtId = 50;
			} else if (strpos($courtName, 'Court') === 0) {
				$courtId = intval(str_replace("Court ", "", $courtName)) + 10;
			} else {
				$courtId = 51;
			}

			if (!in_array($courtName, $court_count)) {
				$court_count[] = $courtName;
				$courtSeq = 0;
			}
			
			if (!isset($this->oop[$day]['courts'][$courtId])) {
				$this->oop[$day]['courts'][$courtId] = [
					'name' => $courtName,
					'matches' => [],
				];
			}

			++$courtSeq;

			$matchid = $amatch['id'];

			$time = $start_time + ($courtSeq - 1) * 3600 * 1.5;

			$this->oop[$day]['courts'][$courtId]['matches'][$courtSeq] = [
				'id' => $matchid,
				'time' => $time,
				'event' => $this->matches[$matchid]['event'],
			];

			$this->matches[$matchid]['date'] = $date;

			self::getResult($matchid, $amatch, $time, $this->oop[$day]['courts'][$courtId]['name']);
		}

		ksort($this->oop[$day]['courts']);
		return true;
	}

	protected function parseLive() {

		$file = join("/", [SCRIPT, 'gs', 'ori', $this->year, $this->tour, 'live']);
		if (!file_exists($file)) return false;

		$html = file_get_contents($file);
		if (!$html) return false;

		$json = json_decode($html, true);
		if (!$json) return false;

		if (isset($json['error'])) return false;

		foreach ($json['matches'] as $m) {
			if ($m['matchData']['status'] != "IN_PROGRESS" && $m['matchData']['status'] != "FINISHED") continue;

			if (!isset($this->match_uuid2matchid[$m['id']])) continue;
			$matchid = $m['id'];
			self::getResult($matchid, $m);

			if (!in_array($matchid, $this->live_matches)) $this->live_matches[] = $matchid;
		}

	}

	protected function getResult($matchid, &$m, $match_time = "", $match_court = "") {

		$match = &$this->matches[$matchid];
		$event = $match["event"];
		$r1 = $match['x'];
		$order = $match['y'];

		$match['tipmsg'] = "";

		$match['dura'] = $m['matchData']['durationInMinutes'] === null ? "" : date("H:i:s", strtotime("00:00:00 +" . $m['matchData']['durationInMinutes'] . " minutes"));

		$status = $m['matchData']['status'];

		if ($status == "NOT_STARTED") {
			$mStatus = "A";
			$score1 = [];
			$score2 = [];
		} else {
			if ($status == "IN_PROGRESS" && $m['matchData']['endTimestamp'] === null) {
				$mStatus = "B";
			} else if ($status == "FINISHED" || ($status == "IN_PROGRESS" && $m['matchData']['endTimestamp'] !== null)) {
				if ($m['teamA']['winner'] === true && $m['teamB']['winner'] === false) {
					if ($m['teamB']['endCause'] == "") $mStatus = "F";
					else if ($m['teamB']['endCause'] == "r.") $mStatus = "H";
					else if ($m['teamB']['endCause'] == "def.") $mStatus = "J";
					else if ($m['teamB']['endCause'] == "w/o.") $mStatus = "L";
				} else if ($m['teamB']['winner'] === true && $m['teamA']['winner'] === false) {
					if ($m['teamA']['endCause'] == "") $mStatus = "G";
					else if ($m['teamA']['endCause'] == "r.") $mStatus = "I";
					else if ($m['teamA']['endCause'] == "def.") $mStatus = "K";
					else if ($m['teamA']['endCause'] == "w/o.") $mStatus = "M";
				} else {
					if ($m['teamA']['sets'][count($m['teamA']['sets']) - 1]['score'] > $m['teamB']['sets'][count($m['teamB']['sets']) - 1]['score']) $mStatus = "F";
					else if ($m['teamA']['sets'][count($m['teamA']['sets']) - 1]['score'] < $m['teamB']['sets'][count($m['teamB']['sets']) - 1]['score']) $mStatus = "G";
					else $mStatus = "";
				}
			} else if ($status == "TO_FINISH" || $status == "INTERRUPTED") {
				$mStatus = "C";
			} else if ($status == "CANCELED") {
				$mStatus = "";
			} else {
				fputs(STDERR, "Wrong status: " . $status . "\n");
				$mStatus = "";
			}

			$score1 = $score2 = [];

			foreach ($m['teamA']['sets'] as $set) {
				$score1[] = [
					$set['score'], 
					$set['winner'] === true ? 1 : ($set['winner'] === false && $set['inProgress'] === false ? -1 : 0), 
					$set['tieBreak'] === null ? -1 : $set['tieBreak']
				];
			}
			foreach ($m['teamB']['sets'] as $set) {
				$score2[] = [
					$set['score'], 
					$set['winner'] === true ? 1 : ($set['winner'] === false && $set['inProgress'] === false ? -1 : 0), 
					$set['tieBreak'] === null ? -1 : $set['tieBreak']
				];
			}

		}

		// live matches
		if ($mStatus == "B") {
			if (!in_array($matchid, $this->live_matches)) $this->live_matches[] = $matchid;
		} else if ($m['matchData']['endTimestamp'] !== null) {
			if (time() - intval($m['matchData']['endTimestamp'] / 1000) < 100) {
				if (!in_array($matchid, $this->live_matches)) $this->live_matches[] = $matchid;
			}
		}

		$match['mStatus'] = $mStatus;
		if ($mStatus != "A") {
			$match['s1'] = $score1;
			$match['s2'] = $score2;
		} else {
			if (!isset($match['s1']) || !$match['s1']) $match['s1'] = $match_time;
			if (!isset($match['s2']) || !$match['s2']) $match['s2'] = $match_court;
		}

		// point, service
		if ($mStatus == "B") {
			$p1 = @$m['teamA']['points'] . "";
			$p2 = @$m['teamB']['points'] . "";
			if ($p1 == "AD" || $p1 == "A") {$p1 = "A"; $p2 = "";}
			if ($p2 == "AD" || $p2 == "A") {$p1 = ""; $p2 = "A";}
			if ($p1 == "" && $p2 == "") {$p1 = $p2 = 0;}
			if (isset($m['teamA']['hasService']) && $m['teamA']['hasService'] === true) {
				$service = 1;
			} else if (isset($m['teamB']['hasService']) && $m['teamB']['hasService'] === true) {
				$service = 2;
			} else {
				$service = "";
			}

			$match['p1'] = $p1;
			$match['p2'] = $p2;
			$match['serve'] = $service;
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

	private function sortByRoundId($a, $b) {
		if ($a['id'] < $b['id']) return -1;
		else return 1;
	}
}
