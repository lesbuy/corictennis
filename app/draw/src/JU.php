<?php

require_once('base.class.php');
require_once(APP . '/conf/wt_bio.php');

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
	}

	public function processLive() {
	}

	protected function parsePlayer() {
		$file = join("/", [DATA, 'tour', 'draw', $this->year, $this->tour]);
		if (!file_exists($file)) return false;

		$xml = json_decode(file_get_contents($file), true);
		if (!$xml) return false;

		$players = [];

		$bio = new Bio();
		$this->redis = new redis_cli('127.0.0.1', 6379);

		foreach ($xml as $sextip => $event) {
			$sex = substr($sextip, 0, 1);

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

							// 如果没找到wt pid
							if ($wtpid === null) {
								fputs(STDERR, join("\t", ["TO SEEK WTPID", $itfpid, $first, $last]). "\n");

								// 如果itf_profile也找不到，那就set一次itf_profile，并且有效时间1周。去找一次wt pid，找不到就休息一周再找
								if (!$this->redis->cmd('KEYS', 'itf_profile_' . $itfpid)->get()) {
									$this->redis->cmd('HMSET', 'itf_profile_' . $itfpid, 'first', $first, 'last', $last, 'ioc', $ioc)->set();
									$this->redis->cmd('EXPIRE', 'itf_profile_' . $itfpid, 86400 * 9)->set();

									if ($sex == "B"){
										$wtpid = $bio->query_wtpid("atp", $first, $last, $this->redis, $itfpid);
									} else if ($sex == "G") {
										$wtpid = $bio->query_wtpid("wta", $first, $last, $this->redis, $itfpid);
									}
								}
							}

							$pid = $itfpid;
							// 如果能找到wtpid，那么就查找他的名字
							if ($wtpid !== null) {
								$find_wtpid = $this->redis->cmd('HMGET', join("_", [$sex == "B" ? "atp" : "wta", 'profile', $wtpid]), 'first', 'last')->get();
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
						else if ($entry == "Lucky loser") $entry = "L";
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
						$rank = "";

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

		$file = join("/", [DATA, 'tour', 'draw', $this->year, $this->tour]);
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
			if (substr($event, 1, 1) == "Q") {
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
				'asso' => in_array($event, ['BS', 'BD', 'BQ']) ? 'BOY' : 'GIRL',
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
							
							if ($sd == "D") { // 双打比赛，当player2不为null才加入
								$ap = $ateam['players'][1];
								if ($ap) {
									$pids[] = $ap['playerId'];
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
				} // end foreach round 
			} // end if KO
		}
	}

	protected function parseResult() {

	}

	protected function parseExtra() {}

	protected function parseSchedule() {

	}

	protected function parseLive() {

	}

	protected function getResult($matchid, &$m, $match_time = "", $match_court = "") {

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
