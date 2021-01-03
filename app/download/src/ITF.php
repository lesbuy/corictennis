<?php
require_once('base.class.php');
require_once(APP . '/conf/wt_bio.php');

class Down extends DownBase {

	private $redis = null;
	private $rank;
	private $players;
	private $teams;

	protected function getTourList() {
		$year = date('Y', time());
		$file = join("/", [STORE, "calendar", $year, "ITF"]);
		if (date('Y', time() + 10 * 86400) != $year) $file .= " " . join("/", [STORE, "calendar", date('Y', time() + 10 * 86400), "ITF"]);
		if (date('Y', time() - 10 * 86400) != $year) $file .= " " . join("/", [STORE, "calendar", date('Y', time() - 10 * 86400), "ITF"]);
		if ($this->asso == "itf-men") {
			$cmd = "cat $file | awk -F\"\\t\" '$4 == \"M\"'";
		} else if ($this->asso == "itf-women") {
			$cmd = "cat $file | awk -F\"\\t\" '$4 == \"W\"'";
		}
		unset($r); exec($cmd, $r);
		foreach ($r as $row) {
			$arr = explode("\t", $row);
			// 前一周周五开始算，一直到下一周周三0点结束
			$start = $arr[6] - 3 * 86400;
//			$end = $arr[6] + $arr[21] * 7 * 86400 + 2 * 86400 + 50 * 86400;
			$end = $arr[6] + $arr[21] * 7 * 86400 + 2 * 86400;
			if (time() < $start || time() >= $end) continue;
			$t = new DownTour;
			$t->eventID = $arr[1];
			$t->year = $arr[4];
			$t->tourID = $arr[2];
			$t->city = $arr[9];
			$t->monday = $arr[5];
			$this->tourList[] = $t;
		}
		return [true, ""];
	}

	protected function downPlayerFile() {
		print_line("begin to down players");
		$this->redis = new_redis();
		$bio = new Bio();
		$this->iso3_to_ioc = require_once(APP . '/draw/conf/iso3_to_ioc.php');
		$this->itf_point_prize = require_once(APP . '/draw/conf/itf_conf.php');

		// 获取官方排名
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


		foreach ($this->tourList as $t) {
			$t->printSelf();
			$url = "https://live.itftennis.com//feeds/d/drawsheets.php/en/$t->eventID-$t->year";
			$html = http($url, null, null, null);
			if (!$html) {
				print_line("download players failed");
				continue;
			}

			$json_content = json_decode($html, true);
			if (!$json_content || !isset($json_content['hash'])) {
				print_line("players parsed failed");
				continue;
			}

			foreach ($json_content as $k => $Event) {
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
								$ioc = $this->iso3_to_ioc[$ioc];

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

									// 如果itf_profile也找不到，那就set一次itf_profile，并记下当前时间。去找一次wt pid，找不到就休息9天再找
									if (!$this->redis->cmd('KEYS', 'itf_profile_' . $itfpid)->get()
										|| $this->redis->cmd('HGET', 'itf_profile_' . $itfpid, 'update_time')->get() - time() > 86400 * 9) {
										$l_en = $bio->rename2long($first, $last, $ioc);
										$s_en = $bio->rename2short($first, $last, $ioc);
										$this->redis->cmd('HMSET', 'itf_profile_' . $itfpid, 'first', $first, 'last', $last, 'ioc', $ioc, 'l_en', $l_en, 's_en', $s_en, 'update_time', time())->set();

										if ($sex == "M") {
											$wtpid = $bio->query_wtpid("atp", $first, $last, $this->redis, $itfpid);
										} else if ($sex == "F") {
											$wtpid = $bio->query_wtpid("wta", $first, $last, $this->redis, $itfpid);
										}
									}
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
							$seed = intval(substr($note, 1));
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
						];
					} // foreach side
				} // foreach match
			} // foreach $k => $Event




			$fp = fopen(join("/", [DATA, "tour", "player", $t->year, $t->eventID]), "w");
			fputs($fp, $html . "\n");
			fclose($fp);
		}
		return [true, ""];
	}

	protected function downDrawFile() {
		return [true, ""];
	}

	protected function downOOPFile() {
		return [true, ""];
	}

	protected function downResultFile() {
		return [true, ""];
	}

}


