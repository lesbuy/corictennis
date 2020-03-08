<?php

require_once('GS.class.php');

class eGS extends GS{

	protected function parsePlayer($event) {
		$file = join("/", [$this->root, 'ori', $this->year, $this->tour, 'draw', $event]);
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
				$pids = "";
				foreach ($amatch[$aside]['players'] as $aplayer) {
					$last = ucwords(strtolower($aplayer['lastName']));

					if ($last == "Qualifie" || $last == "Qualifiee") {
						$pid = "QUAL";
						$first = "";
						$last = "Qualifier";
						$ioc = "";
						$gender = "M";
						$uuid = -1;
					} else if ($last == "Bye") {
						$pid = "BYE";
						$first = "";
						$last = "Bye";
						$ioc = "";
						$gender = "M";
						$uuid = -2;
					} else if ($last == "Alternates") {
						$pid = "ALT";
						$first = "";
						$last = "Alternate";
						$ioc = "";
						$gender = "M";
						$uuid = -3;
					} else {
						$ioc = $aplayer['country'];
						$first = $aplayer['firstName'];
						$gender = $aplayer['sex'] == "M" ? "M" : "F";
						$uuid = intval(preg_replace('/^.*\//', '', $aplayer['playerCardUrl']));
						$pid = $this->uuid2id[$uuid];
					}

					$team_uuid = $team_uuid == "" ? $uuid : $team_uuid . "/" . $uuid;
					$pids .= $pid;
					$players[$uuid] = [
						'p' => $pid, 
						'g' => $gender, 
						'f' => $first,
						'l' => $last, 
						'i' => $ioc
					];
				}
				$team_uuid = $event . $team_uuid;

				$seed = $amatch[$aside]['seed'] !== null ? $amatch[$aside]['seed'] : "";
				if (isset($this->wclist[$event][$pids])) {
					if ($seed == "") $seed = $this->wclist[$event][$pids];
					else $seed .= "/" . $this->wclist[$event][$pids];
				}
				$rank = isset($this->rank[$pids]) ? $this->rank[$pids] : "";

				$this->teams[$team_uuid] = [
					'uuid' => $team_uuid,
					's' => $seed,
					'r' => $rank,
					'p' => array_map(function ($d) use ($players) {
						return $players[$d];
					}, explode("/", substr($team_uuid, 2))),
				];
			}
		}

		$this->teams[$event . 'LIVE'] = ['uuid' => $event . 'LIVE', 's' => '', 'r' => '', 'p' => [['p' => 'LIVE', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],];
		$this->teams[$event . 'TBD'] = ['uuid' => $event . 'TBD', 's' => '', 'r' => '', 'p' => [['p' => 'TBD', 'g' => '', 'f' => '', 'l' => '', 'i' => '',],],];
	}

	protected function parseDraw($event, $event_size, $event_round, $event_raw) {

		$file = join("/", [$this->root, 'ori', $this->year, $this->tour, 'draw', $event]);
		if (!file_exists($file)) return false;

		$html = file_get_contents($file);
		if (!$html) return false;

		$json = json_decode($html, true);
		if (!$json) return false;

		if ($event_round > 0) $draw_type = "KO"; else $draw_type = "RR";

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
		}

		$column = $json['tournamentEvent']['oneColumn'];
		if ($draw_type == "KO") {
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
			}
			$matchid = $event . sprintf("%03d", $r1 * 100 + $key + 1);

			$teamA_uuid = $event . join("/", array_map(function ($d) {
				$last = ucwords(strtolower($d['lastName']));
				if ($last == "Qualifie" || $last == "Qualifiee") {return -1;}
				else if ($last == "Bye") {return -2;}
				else if ($last == "Alternates") {return -3;}
				else {return intval(preg_replace('/^.*\//', '', $d['playerCardUrl']));}
			}, $amatch['teamA']['players']));
			$teamB_uuid = $event . join("/", array_map(function ($d) {
				$last = ucwords(strtolower($d['lastName']));
				if ($last == "Qualifie" || $last == "Qualifiee") {return -1;}
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
			
			$this->draws[$event]['matches'][$r1][$key + 1] = [
				'uuid' => $match_uuid,
				'id' => $matchid,
				'r' => $r1,
				'r1' => $r2,
				'r2' => $r3,
				't1' => $teamA_uuid,
				't2' => $teamB_uuid,
				'bestof' => $event == "MS" ? 5 : 3,
				'mStatus' => "",
				'h2h' => $h2h,
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
			}

			$round_matches = $match_first_round >> ($r1 - 1);

			for ($order = 1; $order <= $round_matches; ++$order) {
				$matchid = $event . $r1 . ($order < 10 ? '0' . $order : $order);
				$match_uuid = floor(intval(substr($this->draws[$event]['matches'][$r1 - 1][$order * 2]['uuid'], 2)) / 2);
				$match_uuid = $event_raw . "0" . ($match_uuid < 10 ? '0' . $match_uuid : $match_uuid);

				$this->draws[$event]['matches'][$r1][$order] = [
					'uuid' => $match_uuid,
					'id' => $matchid,
					'r' => $r1,
					'r1' => $r2,
					'r2' => $r3,
					't1' => $event,
					't2' => $event,
					'bestof' => $event == "MS" ? 5 : 3,
					'mStatus' => "",
					'h2h' => "",
				];

				$this->match_uuid2matchid[$match_uuid] = $matchid;
			}
		}

		return true;
	}

	protected function parseResult($day) {

		$file = join("/", [$this->root, 'ori', $this->year, $this->tour, 'result', $day]);
		if (!file_exists($file)) return false;

		$html = file_get_contents($file);
		if (!$html) return false;

		$json = json_decode($html, true);
		if (!$json) return false;

		if (!isset($json['matches']) || count($json['matches']) == 0) return false;

		usort($json['matches'], 'self::id_sort');
		foreach ($json['matches'] as $m) {
			if (!isset($this->match_uuid2matchid[$m['id']])) continue;
			$matchid = $this->match_uuid2matchid[$m['id']];
			$this->getResult($matchid, $m);
		}

		return true;
	}

	protected function parseExtra($day) {

		if ($day == 11) {
			$extra_matches = [
				['id' => 'SD037', 'winner' => 1,],
				['id' => 'DD047', 'winner' => 2,],
			];
		} else if ($day == 13) {
			$extra_matches = [
				['id' => 'DD026', 'winner' => 1,],
			];
		} else {
			$extra_matches = [];
		}

		foreach ($extra_matches as $m) {
			$matchid = $this->match_uuid2matchid[$m['id']];
			$m['matchData'] = ['durationInMinutes' => 0, 'endTimestamp' => 1, 'status' => 'FINISHED',];
			$m['teamA']['sets'] = [];
			$m['teamB']['sets'] = [];
			if ($m['winner'] == 1) {
				$m['teamA']['winner'] = true;
				$m['teamB']['winner'] = false;
				$m['teamB']['endCause'] = 'w/o.';
			} else if ($m['winner'] == 2) {
				$m['teamB']['winner'] = true;
				$m['teamA']['winner'] = false;
				$m['teamA']['endCause'] = 'w/o.';
			}
			$this->getResult($matchid, $m);
		}
	}

	protected function parseSchedule($day) {

		$file = join("/", [$this->root, 'ori', $this->year, $this->tour, 'schedule', $day]);
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

			$matchid = $this->match_uuid2matchid[$amatch['id']];

			$time = $start_time + ($courtSeq - 1) * 3600 * 1.5;

			$this->oop[$day]['courts'][$courtId]['matches'][$courtSeq] = [
				'id' => $matchid,
				'time' => $time,
			];
		}

		ksort($this->oop[$day]['courts']);

		return true;
	}

	protected function parseLive() {

		$file = join("/", [$this->root, 'ori', $this->year, $this->tour, 'live']);
		if (!file_exists($file)) return false;

		$html = file_get_contents($file);
		if (!$html) return false;

		$json = json_decode($html, true);
		if (!$json) return false;

		if (isset($json['error'])) return false;

		foreach ($json['matches'] as $m) {

			if ($m['matchData']['status'] != "IN_PROGRESS" && $m['matchData']['status'] != "FINISHED") continue;

			if (!isset($this->match_uuid2matchid[$m['id']])) continue;
			$matchid = $this->match_uuid2matchid[$m['id']];
			$this->getResult($matchid, $m);

			if (!in_array($matchid, $this->live_matches)) $this->live_matches[] = $matchid;
		}
	}

	protected function getResult($matchid, &$m) {

		$arr = $this->splitMatchid($matchid);
		$event = $arr[0];
		if (!isset($this->draws[$event])) return;
		$r1 = $arr[1];
		$order = $arr[2];

		if (!isset($this->draws[$event]['matches'][$r1][$order])) return false;
		$match = &$this->draws[$event]['matches'][$r1][$order];

		// dura
		$match['dura'] = $m['matchData']['durationInMinutes'] === null ? "" : date("H:i:s", strtotime("00:00:00 +" . $m['matchData']['durationInMinutes'] . " minutes"));

		// status, score
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
				$score1[] = [$set['score'], $set['winner'] === true ? 1 : ($set['winner'] === false && $set['inProgress'] === false ? -1 : 0), $set['tieBreak'] === null ? -1 : $set['tieBreak']];
			}
			foreach ($m['teamB']['sets'] as $set) {
				$score2[] = [$set['score'], $set['winner'] === true ? 1 : ($set['winner'] === false && $set['inProgress'] === false ? -1 : 0), $set['tieBreak'] === null ? -1 : $set['tieBreak']];
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

		if (!in_array($match['mStatus'], ['F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'])) {
			$match['mStatus'] = $mStatus;
			$match['s1'] = $score1;
			$match['s2'] = $score2;
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
		if ($this->draws[$event]['type'] == "KO") {

			$winner = "";
			if (in_array($match['mStatus'], ['F', 'H', 'J', 'L'])) $winner = $match['t1'];
			else if (in_array($match['mStatus'], ['G', 'I', 'K', 'M'])) $winner = $match['t2'];
			else if ($match['mStatus'] == "B") $winner = $event . "LIVE";
			else if ($match['mStatus'] == "C") $winner = $event . "TBD";

			if ($winner != "" && isset($this->draws[$event]['matches'][$r1 + 1])) {
				if ($order % 2 == 1) {
					$next_match = &$this->draws[$event]['matches'][$r1 + 1][($order + 1) / 2];
					$next_match['t1'] = $winner;
				} else {
					$next_match = &$this->draws[$event]['matches'][$r1 + 1][$order / 2];
					$next_match['t2'] = $winner;
				}
				$teamA_uuid = $next_match['t1'];
				$teamB_uuid = $next_match['t2'];

				if ($teamA_uuid != $event && $teamB_uuid != $event && $next_match['h2h'] == "") {
					$pid1 = join("/", array_map(function ($d) {return $d['p'];}, $this->teams[$teamA_uuid]['p']));
					$pid2 = join("/", array_map(function ($d) {return $d['p'];}, $this->teams[$teamB_uuid]['p']));
					if (isset($this->h2h[$pid1 . "\t" . $pid2])) {
						$next_match['h2h'] = $this->h2h[$pid1 . "\t" . $pid2];
					} else {
						$next_match['h2h'] = "0:0";
					}
				}
			}
		}

		// put Q and LL into list if quali
		if (in_array($event, ['QS', 'PS', 'QD', 'PD']) && $mStatus != "" && strpos('FGHIJKLM', $mStatus) !== false && $r1 > 1) {
			$new_event = str_replace("Q", "M", str_replace("P", "W", $event));
			if (!isset($this->llist[$new_event])) $this->llist[$new_event] = [];
			$team1 = substr($match['t1'], 2);
			$team2 = substr($match['t2'], 2);
			$this->llist[$new_event][$team1] = 1;
			$this->llist[$new_event][$team2] = 1;
			if ($r1 == 3) {
				if (!isset($this->qlist[$new_event])) $this->qlist[$new_event] = [];
				if (strpos('FHJL', $mStatus) !== false) {
					$this->qlist[$new_event][$team1] = 1;
				} else if (strpos('GIKM', $mStatus) !== false) {
					$this->qlist[$new_event][$team2] = 1;
				}
			}
		}

		return true;

	}

	private function id_sort($a, $b) {
		$_a = intval(substr($a['id'], 2));
		$_b = intval(substr($b['id'], 2));
		return $_a > $_b ? -1 : ($_a == $_b ? 0 : 1);
	}

}
