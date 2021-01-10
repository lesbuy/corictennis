<?php

require_once('base.class.php');

class Event extends Base{

	protected $currency = "A$";
	private $config;
	private $start_date; // 此日为第1日，并不一定是星期一，后面的start,end 都是偏移量
	private $eventConf;
	private $roundConf;

	public function process() {
		$web_const = require_once(join("/", [WEB, 'config', 'const.php']));

		$this->preprocess();

		foreach ($this->eventConf as $eventUUID => $eventInfo) {
			$event_raw = $eventUUID;
			$event = $eventInfo["event"];
			$event_size = $eventInfo["draw"];
			$event_round = $eventInfo["round"];
			$eventid4oop = $eventInfo["eventid2"];
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

		for ($i = 1; $i <= 4; ++$i) {
			$this->parseResult($i);
			$this->parseExtra($i);
			$this->parseSchedule($i);
		}
		for ($i = 31; $i <= 44; ++$i) {
			$this->parseResult($i);
			$this->parseExtra($i);
			$this->parseSchedule($i);
		}
		$this->parseLive();
		$this->appendH2HandFS();
		$this->calaTeamFinal();

		//$this->reviseEntry();
	}

	public function processLive() {
		$this->parseLive();
	}

	public function preprocess() {

		$this->config = json_decode(file_get_contents(join("/", [APP, "draw", "conf", "GS", $this->year, $this->tour . ".json"])), true);
		$this->quali_first_day = $this->config["qualiStartDate"];
		$this->start_date = $this->config["startDate"];
		$this->eventConf = $this->config["eventConf"];
		$this->roundConf = $this->config["roundConf"];
		/*
		$file = join("/", [WORK, 'etl', $this->year, $this->tour, 'wclist']);
		$fp = fopen($file, "r");
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);
			if (!isset($this->wclist[$arr[0]])) $this->wclist[$arr[0]] = [];
			$this->wclist[$arr[0]][$arr[2]] = $arr[1];
		}
		fclose($fp);
		*/
	}

	protected function parsePlayer() {}

	protected function parseDraw() {
		$args = func_get_args();
		$event = $args[0];
		$event_size = $args[1];
		$event_round = $args[2];
		$event_raw = $args[3];

		$file = join("/", [DATA, 'tour', "draw", $this->year, $this->tour, $event]);
		if (!file_exists($file)) return false;

		$html = file_get_contents($file);
		if (!$html) return false;

		$json = json_decode($html, true);
		if (!$json) return false;

		if ($json['event']['draw_availability'] == false) return false;

		// 先解析player 
		$players = [];

		foreach ($json['players'] as $p) {
			$uuid = $p['uuid'];
			$pid = self::getPid($p['player_id']);
			$first = $p['first_name'];
			$last = $p['last_name'];
			$ioc = $p['nationality']['code'];
			$gender = $p['gender'] == "M" ? "M" : "F";
			$short3 = substr(preg_replace('/[^A-Z]/', '', replace_letters(mb_strtoupper($last . $first))), 0, 3); // 取姓的前3个字母，用于flashscore数据
			$last2 = substr(preg_replace('/[^A-Z]/', '', replace_letters(mb_strtoupper(preg_replace('/^.* /', '', str_replace("-", " ", $last))))), 0, 3); // 取名字最后一部分的前3个字母，用于bets数据
			$rank_s = $rank_d = "";
			foreach ($p["rankings"] as $rankInfo) {
				if ($rankInfo["event"] == "cb9599e0-4478-4b4e-98aa-996a54313df6" || $rankInfo["event"] == "e529b2d6-8793-4a7c-8ca3-200d07ada2a0") {
					$rank_s = $rankInfo["ranking"];
				} else if ($rankInfo["event"] == "7639a625-a364-40e7-b958-5dac8a23d3f8" || $rankInfo["event"] == "81b68b69-97ba-4951-9dab-353b94b5acee") {
					$rank_d = $rankInfo["ranking"];
				}
			}
			$players[$uuid] = [
				'p' => $pid, 
				'g' => $gender, 
				'f' => $first,
				'l' => $last, 
				'i' => $ioc,
				's' => $short3,
				's2' => $last2,
				'rs' => $rank_s,
				'rd' => $rank_d,
				
			];
			$this->players[$pid] = [
				'p' => $pid,
				'g' => $gender,
				'f' => $first,
				'l' => $last,
				'i' => $ioc,
				's' => $short3,
				's2' => $last2,
				'rs' => $rank_s,
				'rd' => $rank_d,
			];
		}

		foreach ($json['teams'] as $t) {
			$uuid = $event . $t['uuid'];
			$entry = @$t['entry_status']['abbr'] . "";
			if ($entry == "LL") $entry = "L";
			else if ($entry == "WC") $entry = "W";
			$seed = $t['seed'] . "";

			$seeds = [];
			if ($seed != "") $seeds[] = $seed;
			if ($entry != "") $seeds[] = $entry;

			$pids = join("/", array_map(function ($d) use ($players) {
				return $players[$d]['p'];
			}, $t['players']));
			$rank = count($t['players']) > 1 ? "" : $players[$t['players'][0]]['rs'];

			$this->teams[$uuid] = [
				'uuid' => $uuid,
				's' => $seed,
				'e' => $entry,
				'se' => join("/", $seeds),
				'r' => $rank,
				'p' => array_map(function ($d) use ($players) {
					return $players[$d];
				}, $t['players']),
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

		$this->teams[$event . 'LIVE'] = ['uuid' => $event . 'LIVE', 's' => '', 'r' => '', 'p' => [['p' => 'LIVE', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
		$this->teams[$event . 'TBD'] = ['uuid' => $event . 'TBD', 's' => '', 'r' => '', 'p' => [['p' => 'TBD', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
		$this->teams[$event . 'QUAL'] = ['uuid' => $event . 'QUAL', 's' => '', 'r' => '', 'p' => [['p' => 'QUAL', 'g' => '', 'f' => '', 'l' => 'Qualifier', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
		$this->teams[$event . 'COMEUP'] = ['uuid' => $event . 'COMEUP', 's' => '', 'r' => '', 'p' => [['p' => 'COMEUP', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];
		$this->teams[$event . 'BYE'] = ['uuid' => $event . 'BYE', 's' => '', 'r' => '', 'p' => [['p' => 'BYE', 'g' => '', 'f' => '', 'l' => 'Bye', 'i' => '',],],'round'=>'','point'=>0,'prize'=>0];

		// event的基本信息
		$draw_type = $json['event']['draw_type'];
		$name = $json['event']['name'];
		if (strpos($name, 'Single') !== false) $sd = "S"; else $sd = "D";
		if (strpos($name, 'Qualify') !== false) $qm = 'Q'; else $qm = "M";

		$this->draws[$event]['event'] = $event;
		$this->draws[$event]['type'] = $draw_type;
		$this->draws[$event]['sd'] = $sd;
		$this->draws[$event]['qm'] = $qm;
		$this->draws[$event]['ct'] = count(@$json['matches']);

		$this->draws[$event]['round'] = $this->roundConf[$event];

		// 以上为获取该type的基本信息
		// 以下为RR获取一些组别信息，RRteam2pos记录了每一个team所在的group和pos，返回结果为 group * 10 + pos

		$RRteam2pos = [];

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
		
		// matches顺序是乱的，按后3位数字排序
		usort($json['matches'], function ($a, $b) {return intval(substr($a['match_id'], 2)) < intval(substr($b['match_id'], 2)) ? -1 : 1;});

		// 遍历所有的比赛
		foreach ($json['matches'] as $m) {
			$uuid = $m['uuid'];
			$matchid = $m['match_id'];
			$r1 = intval(substr($matchid, 2, 1));
			$order = intval(substr($matchid, 3, 2));

			$team1 = $event . ($m['teams'][0] !== null ? $m['teams'][0]['team_id'] . "" : ($qm == "M" && $draw_type == "KO" && $r1 == 1 ? 'QUAL' : ''));
			$team2 = $event . ($m['teams'][1] !== null ? $m['teams'][1]['team_id'] . "" : ($qm == "M" && $draw_type == "KO" && $r1 == 1 ? 'QUAL' : ''));

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
			$this->matches[$matchid] = [  // key是该系统里认可的matchid
				'uuid' => $matchid,  // 原系统里的唯一id
				'id' => $event . substr($matchid, 2), // 我系统里规范化matchid
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

		$file = join("/", [DATA, 'tour', "result", $this->year, $this->tour, $day]);
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

		$file = join("/", [DATA, 'tour', "oop", $this->year, $this->tour, $day]);
		if (!file_exists($file)) return false;

		$html = file_get_contents($file);
		if (!$html) return false;

		$json = json_decode($html, true);
		if (!$json) return false;

		if (isset($json['error']) || !isset($json['schedule'])) return false;

		$date = date('Y-m-d', strtotime($json['schedule']['date']));
		$this->oop[$day] = [
			'date' => $date,
			'courts' => [],
		];

		foreach ($json['courts'] as $court) {
			$uuid = $court['uuid'];
			$order = $court['order'];
			$name = $court['name'];
			$this->oop[$day]['courts'][$order] = [
				'name' => $name,
				'matches' => [],
			];
		};

		foreach ($json['schedule']['courts'] as $court) {
			$order = $court['order'];
			$matches = &$this->oop[$day]['courts'][$order]['matches'];

			foreach ($court['sessions'] as $k => $session) {
				$next_time = strtotime($date . " " . str_replace("From ", "", $session['session_start_time']));

				foreach ($session['activities'] as $m) {
					if (!isset($m['match_id'])) {
						continue;
					}

					if (isset($m['activity_status']) && ($m['activity_status'] == "Cancelled" || $m['activity_status'] == "Postponed"))
						continue;

					$matchid = $m['match_id'];

					if (isset($m['restricted_start_time']) && trim($m['restricted_start_time'])) {
						$next_time = strtotime($date . " " . str_replace("Not before ", "", $m['restricted_start_time']));
					}

					$time = $next_time;

					if (substr($matchid, 0, 2) == "MS") {
						$next_time = $time + 7200;
					} else {
						$next_time = $time + 5400;
					}
				
					if (isset($this->matches[$matchid])) {
						$matches[$k * 10 + $m['activity_order']] = [
							'id' => $matchid,
							'time' => $time,
							'event' => $this->matches[$matchid]['event'],
						];
						$this->matches[$matchid]["date"] = $date;
					}

					self::getResult($matchid, $m, $time, $this->oop[$day]['courts'][$order]['name']);
				}
			}
		}
		return true;
	}

	protected function parseLive() {

		$file = join("/", [SHARE, 'down_result', strtolower($this->tour) . '_live']);
		if (!file_exists($file)) return false;

		$html = file_get_contents($file);
		if (!$html) return false;

		$json = json_decode($html, true);
		if (!$json) return false;

		if (isset($json['error'])) return false;

		foreach ($json['matches'] as $m) {

			if ($m['match_state'] != "In Progress" && $m['match_state'] != "Complete") continue;

			$matchid = $m['match_id'];
			self::getResult($matchid, $m);

			$this->live_matches[] = $matchid;
		}

	}

	protected function getResult($matchid, &$m, $match_time = "", $match_court = "") {

		//if (!isset($this->draws[$event])) return false;
		$r1 = substr($matchid, 2, 1) + 0;
		$order = substr($matchid, 3, 2) + 0;

		if (!isset($this->matches[$matchid])) {
			$this->matches[$matchid] = [];
			$this->matches[$matchid]['uuid'] = $matchid;
			$event_raw = substr($matchid, 0, 2);
			$this->matches[$matchid]["bestof"] = $event_raw == "MS" ? 5 : 3;
		}

		$match = &$this->matches[$matchid];
		$match['tipmsg'] = "";
		$event = @$match["event"];

		$status = $m['match_status']['name'];

		if ($m['match_status'] === null || $status == "Scheduled") {
			$mStatus = "A";
			$score1 = [];
			$score2 = [];
		} else if ($status == "Walk-Over") {
			$score1 = $score2 = [];
			if (isset($m['teams'][0]['status']) && $m['teams'][0]['status'] == "Winner") $mStatus = "L";
			else $mStatus = "M";
		} else {
			if ($status == "Retired") {
				if (isset($m['teams'][0]['status']) && $m['teams'][0]['status'] == "Winner") $mStatus = "H";
				else $mStatus = "I";
			} else if ($status == "Default") {
				if (isset($m['teams'][0]['status']) && $m['teams'][0]['status'] == "Winner") $mStatus = "J";
				else $mStatus = "K";
			} else if ($status == "Complete") {
				if (isset($m['teams'][0]['status']) && $m['teams'][0]['status'] == "Winner") $mStatus = "F";
				else $mStatus = "G";
			} else if ($status == "Postponed" || (isset($m['activity_status']) && $m['activity_status'] == "Postponed")) {
				$mStatus = "E";
			} else if ($status == "Live" || $status == "Warm-up" || $status == "On Court" || $status == "Players Called" || $status == "Suspended") {
				$mStatus = "B";
				if ($status == "Suspended") {
					$match['tipmsg'] = 19;
				}
			} else {
				fputs(STDERR, "Wrong status: " . $status . "\n");
				$mStatus = "";
			}

			$score1 = $score2 = [];

			foreach ($m['teams'][0]['score'] as $k => $set) {
				$score1[] = [$set['game'] + 0, $set['winner'] === true ? 1 : ($m['teams'][1]['score'][$k]['winner'] === false ? 0 : -1), !isset($set['tie_break']) ? -1 : $set['tie_break'] + 0];
			}
			foreach ($m['teams'][1]['score'] as $k => $set) {
				$score2[] = [$set['game'] + 0, $set['winner'] === true ? 1 : ($m['teams'][0]['score'][$k]['winner'] === false ? 0 : -1), !isset($set['tie_break']) ? -1 : $set['tie_break'] + 0];
			}

		}

		$match['mStatus'] = $mStatus;
		if ($mStatus != "A") {
			$match['dura'] = date('H:i:s', strtotime($m['duration']));
			$match['s1'] = $score1;
			$match['s2'] = $score2;
		} else {
			$match['s1'] = $match_time;
			$match['s2'] = $match_court;
		}

		if ($mStatus == "B") {
			$p1 = @$m['teams'][0]['point'] . "";
			$p2 = @$m['teams'][1]['point'] . "";
			if ($p1 == "AD" || $p1 == "A") {$p1 = "A"; $p2 = "";}
			if ($p2 == "AD" || $p2 == "A") {$p1 = ""; $p2 = "A";}
			if ($p1 == "" && $p2 == "") {$p1 = $p2 = 0;}
			if (isset($m['teams'][0]['is_serving']) && $m['teams'][0]['is_serving'] == true) {
				$service = 1;
			} else if (isset($m['teams'][1]['is_serving']) && $m['teams'][1]['is_serving'] == true) {
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

}
