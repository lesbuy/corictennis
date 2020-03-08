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

		if (!$json['matches']) return false;

		foreach ($json['matches'] as $amatch) {

			foreach (['team1', 'team2'] as $aside) {
				$team_uuid = "";
				$pids = "";
				foreach (['A', 'B'] as $aplayer) {

					if ($amatch[$aside]['lastName' . $aplayer] === null) continue;

					$last = ucwords(strtolower($amatch[$aside]['lastName' . $aplayer]));

					if ($last == "Qualifier") {
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
						$ioc = $amatch[$aside]['nation' . $aplayer];
						$first = $amatch[$aside]['firstName' . $aplayer];
						$uuid = $amatch[$aside]['id' . $aplayer];
						$pid = strtoupper(preg_replace('/atp|wta0*|itf/', '', $uuid));
						if (substr($uuid, 0, 3) == "atp" || strpos($amatch['eventName'], 'Gentlemen') !== false || strpos($amatch['eventName'], 'Boy') !== false) {
							$gender = "M";
						} else {
							$gender = "F";
						}
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
				if ($team_uuid == '/') $team_uuid = '';
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
		$eventName = $json['eventName'];

		if (strpos($eventName, "ualif") !== false) {
			$qm = "Q";
		}
		if (strpos($eventName, "ouble") !== false) {
			$sd = "D";
		}

		$this->draws[$event]['type'] = $draw_type;
		$this->draws[$event]['sd'] = $sd;
		$this->draws[$event]['qm'] = $qm;

		if (!$json['matches']) return false;
		$this->draws[$event]['ct'] = count($json['matches']);
		$match_first_round = $json['drawSize'] / 2;

		if ($draw_type == "RR") {
			$this->draws[$event]['groups'] = 2;
			$this->draws[$event]['playersPerGroup'] = $json['drawSize'] / $this->draws[$event]['groups'];
			$this->draws[$event]['maxRRRounds'] = $this->draws[$event]['playersPerGroup'] - 1;
			$this->draws[$event]['matchesPerGroup'] = $this->draws[$event]['playersPerGroup'] * ($this->draws[$event]['playersPerGroup'] - 1) / 2;
		}

		$RRperson2seq = [];

		foreach ($json['matches'] as $key => $amatch) {
			$match_uuid = $amatch['match_id'];
			if ($draw_type == "KO") {
				$r1 = floor(($match_uuid % 1000) / 100);
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
				$key = $match_uuid % 100;
			} else {
				$seq = floor(($match_uuid % 1000) / 100);
				if ($seq <= $this->draws[$event]['maxRRRounds']) {
					$r1 = 0; $r2 = $r3 = "RR";
				} else {
					$r1 = 1; $r2 = $r3 = "F";
				}

				$key = $match_uuid % 100;
			}

			foreach (['A', 'B'] as $aside) {
				$team = $amatch['team' . (ord($aside) - 64)];
				$last = ucwords(strtolower($team['lastNameA']));
				if ($last == "Qualifier") {$aid = -1;} 
				else if ($last == "Bye") {$aid = -2;}
				else if ($last == "Alternates") {$aid = -3;}
				else {$aid = $team['idA'];}
				${'team' . $aside . '_uuid'} = $event . $aid;

				if ($sd == 'D') {
					$last = ucwords(strtolower($team['lastNameB']));
					if ($last == "Qualifier") {$aid = -1;} 
					else if ($last == "Bye") {$aid = -2;}
					else if ($last == "Alternates") {$aid = -3;}
					else {$aid = $team['idB'];}
					${'team' . $aside . '_uuid'} .= '/' . $aid;
				}
			}

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

			if ($draw_type == "RR") {
				if ($seq == 1) {
					if ($key == 1 || $key == 3) {
						$RRperson2seq[$teamA_uuid] = 1;
						$RRperson2seq[$teamB_uuid] = 2;
					} else {
						$RRperson2seq[$teamA_uuid] = 3;
						$RRperson2seq[$teamB_uuid] = 4;
					}
				}

				if ($key == 1 || $key == 2) {
					$group = 0;
				} else {
					$group = 1;
				}

				$seq1 = $RRperson2seq[$teamA_uuid];
				$seq2 = $RRperson2seq[$teamB_uuid];
				if ($seq2 < $seq1) {
					$tmp = $seq1; $seq1 = $seq2; $seq2 = $tmp;
					$tmp = $teamA_uuid; $teamA_uuid = $teamB_uuid; $teamB_uuid = $tmp;
				}
				$key = 5.5 * $seq1 - 0.5 * $seq1 * $seq1 - 6 + $seq2 + $group * 20;

				if ($r1 == 1) {
					$key = 1;
				}
			}			

			$matchid = $event . sprintf("%03d", $r1 * 100 + $key);

			$this->draws[$event]['matches'][$r1][$key] = [
				'uuid' => $match_uuid,
				'id' => $matchid,
				'r' => $r1,
				'r1' => $r2,
				'r2' => $r3,
				't1' => $teamA_uuid,
				't2' => $teamB_uuid,
				'bestof' => $event == "MS" || $event == "MD" || ($event == "QS" && $r1 == 3) ? 5 : 3,
				'mStatus' => "",
				'h2h' => $h2h,
			];

			$this->match_uuid2matchid[$match_uuid] = $matchid;
		}

/*
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
*/
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
			if (!isset($this->match_uuid2matchid[$m['match_id']])) continue;
			$matchid = $this->match_uuid2matchid[$m['match_id']];
			$this->getResult($matchid, $m);
		}

		return true;
	}

	protected function parseExtra($day) {

/*
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
*/
	}

	protected function parseSchedule($day) {

		$file = join("/", [$this->root, 'ori', $this->year, $this->tour, 'schedule', $day]);
		if (!file_exists($file)) return false;

		$html = file_get_contents($file);
		if (!$html) return false;

		$json = json_decode($html, true);
		if (!$json) return false;

		if (!isset($json['courts']) || count($json['courts']) == 0) return false;

		$date = date('Y-m-d', strtotime($this->quali_first_day . " +$day days") - 86400);
		$this->oop[$day] = [
			'date' => $date,
			'courts' => [],
		];

		$court_count = [];
		$courtId = 0;
		$courtSeq = 0;

		foreach ($json['courts'] as $court) {
			$start_time = $court['startEpoch'];
			if (!$start_time) {
				$start_time = strtotime($date . " 18:00");
			}
			$next_time = 0;

			foreach ($court['matches'] as $amatch) {
				if ($amatch['status'] == "Canceled") continue;
				if (!isset($this->match_uuid2matchid[$amatch['match_id']])) continue;

				$courtName = $amatch['courtName'];

				if (strpos($courtName, 'Centre') !== false) {
					$courtId = 1;
				} else if (strpos($courtName, 'No.1') !== false) {
					$courtId = 2;
				} else if (strpos($courtName, 'No.2') !== false) {
					$courtId = 3;
				} else if (strpos($courtName, 'No.3') !== false) {
					$courtId = 4;
				} else if ($courtName == 'Court 12') {
					$courtId = 5;
				} else if ($courtName == 'Court 18') {
					$courtId = 6;
				} else if (preg_match('/^Court /', $courtName)) {
					$courtId = str_replace("Court ", "", $courtName) + 5;
				} else if ($courtName == "R: Court A") {
					$courtId = 60;
				} else if ($courtName == "R: Court B") {
					$courtId = 61;
				} else if (preg_match('/^R: Court /', $courtName)) {
					$courtId = str_replace("R: Court ", "", $courtName) + 40;
				} else if (preg_match('/^To be Arranged /', $courtName)) {
					$courtId = str_replace("To be Arranged ", "", $courtName) + 70;
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

				$matchid = $this->match_uuid2matchid[$amatch['match_id']];

				if ($next_time == 0) {
					$time = $start_time;
				} else {
					$time = $next_time;
				}

				if (substr($matchid, 0, 2) == "MS" || substr($matchid, 0, 3) == "QS3") {
					$next_time = $time + 7200;
				} else {
					$next_time = $time + 5400;
				}

				$this->oop[$day]['courts'][$courtId]['matches'][$courtSeq] = [
					'id' => $matchid,
					'time' => $time,
				];
			}
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

//			if ($m['matchData']['status'] != "IN_PROGRESS" && $m['matchData']['status'] != "FINISHED") continue;

			if (!isset($this->match_uuid2matchid[$m['match_id']])) continue;
			$matchid = $this->match_uuid2matchid[$m['match_id']];
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
		$match['dura'] = $m['duration'] === null ? "" : date("H:i:s", strtotime($m['duration']));

		// status, score
		$statusCode = $m['statusCode'];
		$status = $m['status'];

		if ($statusCode == 'A') {
			$mStatus = "A";
			$score1 = [];
			$score2 = [];
		} else {
			if ($statusCode == 'B' || $statusCode == 'Y' || $statusCode == 'X') {
				$mStatus = "B";
			} else if ($statusCode == 'D' || $statusCode == 'E' || $statusCode == 'F' || $statusCode == 'G') {
				if ($m['winner'] == 1) {
					if ($statusCode == 'D') $mStatus = 'F';
					else if ($statusCode == 'E') $mStatus = 'H';
					else if ($statusCode == 'F') $mStatus = 'L';
					else if ($statusCode == 'G') $mStatus = 'F';
					else $mStatus = 'F';
				} else if ($m['winner'] == 2) {
					if ($statusCode == 'D') $mStatus = 'G';
					else if ($statusCode == 'E') $mStatus = 'I';
					else if ($statusCode == 'F') $mStatus = 'M';
					else if ($statusCode == 'G') $mStatus = 'G';
					else $mStatus = 'G';
				}
			} else if ($statusCode == 'K') {
				$mStatus = "C";
			} else if ($status == "Canceled") {
				$mStatus = "";
			} else {
				fputs(STDERR, "Wrong status: " . $status . '/' . $statusCode . " in " . $matchid . "\n");
				$mStatus = "";
			}

			$score1 = $score2 = [];

			foreach ($m['scores']['sets'] as $setNum => $set) {
				$score1[] = [$set[0]['score'], $m['scores']['setsWon'][$setNum+1] == 1 ? 1 : ($m['scores']['setsWon'][$setNum+1] == 2 ? -1 : 0), $set[0]['tiebreak'] === null ? -1 : $set[0]['tiebreak']];
				$score2[] = [$set[1]['score'], $m['scores']['setsWon'][$setNum+1] == 2 ? 1 : ($m['scores']['setsWon'][$setNum+1] == 1 ? -1 : 0), $set[1]['tiebreak'] === null ? -1 : $set[1]['tiebreak']];
			}
		}

		// live matches
		if ($mStatus == "B") {
			if (!in_array($matchid, $this->live_matches)) $this->live_matches[] = $matchid;
		} else if (isset($m['epoch']) && $m['epoch'] !== null) {
			if (time() - intval($m['epoch'] / 1000) < 100) {
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
			$p1 = @$m['scores']['gameScore'][0];
			$p2 = @$m['scores']['gameScore'][1];
			if ($p1 == "AD" || $p1 == "A") {$p1 = "A"; $p2 = "";}
			if ($p2 == "AD" || $p2 == "A") {$p1 = ""; $p2 = "A";}
			if ($p1 == "" && $p2 == "") {$p1 = $p2 = 0;}

			if ($m['server'] == "A" || $m['server'] == "B") {
				$service = 1;
			} else if ($m['server'] == "C" || $m['server'] == "D") {
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
		$_a = $a['match_id'] % 1000;
		$_b = $b['match_id'] % 1000;
		return $_a < $_b ? -1 : ($_a == $_b ? 0 : 1);
	}

}
