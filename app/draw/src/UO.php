<?php

require_once('base.class.php');

class Event extends Base{

	protected $round_points = [
		'MS' => ['R1' => 10, 'R2' => 45, 'R3' => 90, 'R4' => 180, 'QF' => 360, 'SF' => 720, 'F' => 1200, 'W' => 2000],
		'WS' => ['R1' => 10, 'R2' => 70, 'R3' => 130, 'R4' => 240, 'QF' => 430, 'SF' => 780, 'F' => 1300, 'W' => 2000],
		'MD' => ['R1' => 0, 'R2' => 90, 'QF' => 180, 'SF' => 360, 'F' => 600, 'W' => 1000],
	];

	public function  process() {
		$file = join("/", [SCRIPT, 'gs', 'ori', $this->year, $this->tour, 'type']);
		if (!file_exists($file)) return false;

		$web_const = require_once(join("/", [WEB, 'config', 'const.php']));

		$this->preprocess();

		$fp = fopen($file, "r");
		while ($line = fgets($fp)) {
			$line_arr = explode("\t", trim($line));
			$event_raw = $line_arr[0];
			$event = $line_arr[1];
			$event_size = $line_arr[2];
			$event_round = $line_arr[3];
			$eventid4oop = $line_arr[4];
			$eventid = $web_const['grandslam']['type2id'][$event];

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

		for ($i = 1; $i <= 14; ++$i) {
			$this->parseSchedule($i);
			$this->parseResult($i);
			//$this->parseExtra($i);
		}
		$this->parseLive();
		//$this->reviseEntry();
		$this->appendH2HandFS();
	}

	public function preprocess() {
		$file = join("/", [SCRIPT, 'gs', 'etl', $this->year, $this->tour, 'players']);
		$fp = fopen($file, "r");
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);
			$uuid = $arr[0];
			$id = $arr[1];
			$rank = @$arr[2];
			$this->uuid2id[$uuid] = $id;
			$this->rank[$id] = $rank;
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

		foreach ($json['matches'] as $m) {
			$m_id = $m['match_id'];
			$r1 = floor(($m_id % 1000) / 100);
			if ($r1 > 1) break;
			foreach ([1, 2] as $side) {
				$pid_arr = [];
				foreach (["A", "B"] as $pp) {
					$uuid = $m["team" . $side]['id' . $pp];
					if ($uuid === null) continue;

					$pid = self::getPid($uuid);
					$first = $m["team" . $side]['firstName' . $pp];
					$last = $m["team" . $side]['lastName' . $pp];
					$ioc = $m["team" . $side]['nation' . $pp];
					$gender = substr($event, 0, 1) == "M" ? "M" : "F";
					$short3 = substr(preg_replace('/[^A-Z]/', '', replace_letters(mb_strtoupper($last . $first))), 0, 3); // 取姓的前3个字母，用于flashscore数据
					$last2 = substr(preg_replace('/[^A-Z]/', '', replace_letters(mb_strtoupper(preg_replace('/^.* /', '', str_replace("-", " ", $last))))), 0, 3); // 取名字最后一部分的前3个字母，用于bets数据


					$players[$uuid] = [
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
					$pid_arr[] = $pid;
				}

				$uuid = $event . $m["team" . $side]['idA'] . $m["team" . $side]['idB'];

				$pids = join("/", $pid_arr);

				$entry = "";
				if (isset($this->wclist[$event][$pids])) {
					$entry = $this->wclist[$event][$pids];
				}
				if ($entry == "LL") $entry = "L";
				else if ($entry == "WC") $entry = "W";
				$seed = $m["team" . $side]['seed'] . "";

				$seeds = [];
				if ($seed != "") $seeds[] = $seed;
				if ($entry != "") $seeds[] = $entry;

				$rank = isset($this->rank[$pids]) ? $this->rank[$pids] : "";

				$this->teams[$uuid] = [
					'uuid' => $uuid,
					's' => $seed,
					'e' => $entry,
					'se' => join("/", $seeds),
					'r' => $rank,
					'p' => array_map(function ($d) {
						return $this->players[$d];
					}, $pid_arr),
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

		//if ($json['event']['draw_availability'] == false) return false;

		$draw_type = "KO";
		if ($event_round == 0) $draw_type = "RR";

		$name = $json['eventName'];
		if (strpos($name, 'Single') !== false) $sd = "S"; else $sd = "D";
		if (strpos($name, 'Qualify') !== false) $qm = 'Q'; else $qm = "M";

		$this->draws[$event]['event'] = $event;
		//$this->draws[$event]['type'] = $draw_type;
		$this->draws[$event]['sd'] = $sd;
		$this->draws[$event]['qm'] = $qm;

		$players = [];

		$this->draws[$event]['ct'] = count(@$json['matches']);

		// 以上为获取该type的基本信息
		// 以下为RR获取一些组别信息，RRteam2pos记录了每一个team所在的group和pos，返回结果为 group * 10 + pos

		$RRteam2pos = [];

		// 处理每一轮奖金
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
			

		if ($draw_type == "RR") {
			$this->draws[$event]['groups'] = count($json['groups']);
			$this->draws[$event]['playersPerGroup'] = count($json['groups'][0]['teams']);
			$this->draws[$event]['maxRRRounds'] = $this->draws[$event]['playersPerGroup'] - 1;
			$this->draws[$event]['matchesPerGroupPerRound'] = $this->draws[$event]['playersPerGroup'] / 2;

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

		// unavailable 记录了首轮无效人数，当无效人数高于总签位的一半时，认为签表是无效的
		$unavailable = 0;
		$RRperson2seq = [];
		
		//usort($json['matches'], function ($a, $b) {return intval(substr($a['match_id'], 2)) < intval(substr($b['match_id'], 2)) ? -1 : 1;});

		// 遍历所有的比赛
		foreach ($json['matches'] as $m) {
			$uuid = $m['match_id'];
			$matchid = $m['match_id'];
			$r1 = floor(($matchid % 1000) / 100);
			$order = $matchid % 100;

			$team1 = $event . $m["team1"]['idA'] . $m["team1"]['idB'];
			$team2 = $event . $m["team2"]['idA'] . $m["team2"]['idB'];

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

			$this->draws[$event]['round'][$r2]['alias'] = $r3;

			// 对于RR轮次，将team的group和pos，记录在draw里（拉成二维）。RR比赛的KO轮次不变，只需要调小r1
			if ($draw_type == "RR") {
				if ($r1 <= $this->draws[$event]['maxRRRounds']) {
					$pos1 = $this->teams[$team1]['pos'];
					$pos2 = $this->teams[$team2]['pos'];
					$group = $this->teams[$team2]['group'];
				
					if ($pos1 < $pos2) {$x = $pos1; $y = $pos2;}
					else {$y = $pos1; $x = $pos2;}

					$this->draws[$event]['draw']['RR'][$group][$x][$y] = $m['match_id'];
				} else {
					$group = 0;
					$x = $r1 - $this->draws[$event]['maxRRRounds'];
					$y = $order;
					$this->draws[$event]['draw']['KO'][$group][$x][$y] = $m['match_id'];
				}
			} else {
				$group = 0;
				$x = $r1;
				$y = $order;
				$this->draws[$event]['draw']['KO'][$group][$x][$y] = $m['match_id'];
			}

			// 检验签表有效性
			if ($r1 == 1 && $draw_type == "KO") {
				if ($team1 == $event || $team1 == $event . "QUAL") {
					++$unavailable;
				}
				if ($team2 == $event || $team2 == $event . "QUAL") {
					++$unavailable;
				}
			}

			$h2h = "";

			// 记录到match里
			$this->matches[$m['match_id']] = [
				'uuid' => $uuid,
				'id' => $matchid,
				'event' => $event,
				'r' => $r1,
				'r1' => $r2,
				'r2' => $r3,
				't1' => $team1,
				't2' => $team2,
				'bestof' => $event == "MS" ? 5 : 3,
				'mStatus' => "",
				'h2h' => $h2h,
				'group' => $group,
				'x' => $x,
				'y' => $y,
				'type' => (!$group ? 'KO' : 'RR'),
			];

		} // end foreach 

		if ($unavailable > $event_size / 2) {
			$this->draws[$event]['status'] = -1;
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
			$matchid = $m['match_id'];
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

		if (!isset($json['courts']) || count($json['courts']) == 0) return false;

		$date = date('Y-m-d', strtotime($this->first_day . " +" . ($day - 1) . " days"));
		$this->oop[$day] = [
			'date' => $date,
			'courts' => [],
		];

		foreach ($json['courts'] as $idx => $court) {
			$uuid = $court['courtId'];
			if ($court['session'] == 1) $order = $idx;
			$name = $court['courtName'];
			if (!isset($this->oop[$day]['courts'][$order])) {
				$this->oop[$day]['courts'][$order] = [
					'name' => $name,
					'matches' => [],
				];
			}
		};

		foreach ($json['courts'] as $idx => $court) {
			if ($court['session'] == 1) $order = $idx;
			$matches = &$this->oop[$day]['courts'][$order]['matches'];

			$next_time = strtotime($date . " " . $court['time']) + 3600 * 12;

			$session = $court['session'];
			foreach ($court["matches"] as $m) {
				if (!isset($m['match_id'])) {
					continue;
				}

				if (isset($m['status']) && ($m['status'] == "Cancelled" || $m['status'] == "Postponed"))
					continue;

				$matchid = $m['match_id'];

				if (isset($m['notBefore']) && trim($m['notBefore'])) {
					$next_time = strtotime($date . " " . str_replace("Not before ", "", $m['notBefore'])) + 3600 * 12;
				}

				$time = $next_time;

				if (substr($matchid, 0, 2) == "MS") {
					$next_time = $time + 7200;
				} else {
					$next_time = $time + 5400;
				}
			
				if (isset($this->matches[$matchid])) {
					$matches[$session * 10 + $m['order']] = [
						'id' => $matchid,
						'time' => $time,
						'event' => $this->matches[$matchid]['event'],
					];
					$this->matches[$matchid]['date'] = $date;
				}

				self::getResult($matchid, $m, $time, $this->oop[$day]['courts'][$order]['name']);
			}
		}
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

			$matchid = $m['match_id'];
			self::getResult($matchid, $m);

			$this->live_matches[] = $matchid;
		}

	}

	protected function getResult($matchid, &$m, $match_time = "", $match_court = "") {

		$match = &$this->matches[$matchid];
		$event = $match["event"];
		$r1 = $match['x'];
		$order = $match['y'];

		$match['tipmsg'] = "";

		$status = $m['status'];

		if ($status == "") {
			$mStatus = "A";
			$score1 = [];
			$score2 = [];
		} else if ($status == "Walkover") {
			$score1 = $score2 = [];
			if (@$m['winner'] == 1) $mStatus = "L";
			else $mStatus = "M";
		} else {
			if ($status == "Retired") {
				if (@$m['winner'] == 1) $mStatus = "H";
				else $mStatus = "I";
			} else if ($status == "Default") {
				if (@$m['winner'] == 1) $mStatus = "J";
				else $mStatus = "K";
			} else if ($status == "Completed") {
				if (@$m['winner'] == 1) $mStatus = "F";
				else $mStatus = "G";
			} else if ($status == "Postponed") {
				$mStatus = "E";
			} else if ($status == "In Progress" || $status == "Players Warming Up" || $status == "On Court" || $status == "Players Called") {
				$mStatus = "B";
			} else if ($status == "Suspended") {
				$mStatus = "C";
				$match['tipmsg'] = 19;
			} else {
				fputs(STDERR, "Wrong status: " . $status . "\n");
				$mStatus = "";
			}

			$score1 = $score2 = [];

			if (isset($m['scores'])) {
				foreach ($m['scores']['sets'] as $k => $set) {
					$score1[] = [
						$set[0]['score'],
						$m['scores']['setsWon'][$k + 1] == 1 ? 1 : ($m['scores']['setsWon'][$k + 1] == 2 ? -1 : 0),
						$set[0]['tiebreak'] === null ? -1 : $set[0]['tiebreak']
					];
					$score2[] = [
						$set[1]['score'],
						$m['scores']['setsWon'][$k + 1] == 2 ? 1 : ($m['scores']['setsWon'][$k + 1] == 1 ? -1 : 0),
						$set[1]['tiebreak'] === null ? -1 : $set[1]['tiebreak']
					];
				}
			}

			if ($status == "Suspended" && strpos($m["shortScore"], "To Finish") !== false) {
				foreach (explode("|", str_replace("To Finish", "", $m["shortScore"])) as $set) {
					$setScore = trim($set);
					//fputs(STDERR, $setScore . "\n");
					preg_match('/^(\d+)-(\d+)(\((\d+)\))?$/', $setScore, $matched);
					//fputs(STDERR, json_encode($matched) . "\n");
					if ($matched) {
						$s1 = $matched[1];
						$s2 = $matched[2];
						if (isset($matched[4])) {
							$t2 = $matched[4];
							$t1 = max($t2 + 2, 7);
						} else {
							$t1 = $t2 = -1;
						}
						if ($s1 < $s2) {$tmp = $t1; $t1 = $t2; $t2 = $tmp;}
						$score1[] = [
							$s1,
							$s1 > $s2 ? 1 : -1,
							$t1
						];
						$score2[] = [
							$s2,
							$s2 > $s1 ? 1 : -1,
							$t2
						];
					}
				}
			}
		}

		$match['mStatus'] = $mStatus;
		if ($mStatus != "A") {
			$match['dura'] = @$m['duration'];
			$match['s1'] = $score1;
			$match['s2'] = $score2;
		} else {
			$match['s1'] = $match_time;
			$match['s2'] = $match_court;
		}

		if ($mStatus == "B") {
			$p1 = $p2 = "";
			if (isset($m['scores'])) {
				$p1 = $m['scores']['gameScore'][0] . "";
				$p2 = $m['scores']['gameScore'][1] . "";
				if ($p1 == "AD" || $p1 == "A") {$p1 = "A"; $p2 = "";}
				if ($p2 == "AD" || $p2 == "A") {$p1 = ""; $p2 = "A";}
				if ($p1 == "" && $p2 == "") {$p1 = $p2 = 0;}
			}
			$service = "";
			if (isset($m["server"])) {
				if ($m["server"] == "A" || $m["server"] == "B") $service = 1;
				else if ($m["server"] == "C" || $m["server"] == "D") $service = 2;
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

}
