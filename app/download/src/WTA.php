<?php
require_once('base.class.php');
require_once(APP . '/tool/simple_html_dom.php');

class Down extends DownBase {

	protected function getTourList() {
		$year = date('Y', time());
		$file = join("/", [STORE, "calendar", $year, "WT"]);
		if (date('Y', time() + 10 * 86400) != $year) $file .= " " . join("/", [STORE, "calendar", date('Y', time() + 10 * 86400), "WT"]);
		if (date('Y', time() - 10 * 86400) != $year) $file .= " " . join("/", [STORE, "calendar", date('Y', time() - 10 * 86400), "WT"]);
		$cmd = "cat $file | awk -F\"\\t\" '$4 ~ /W/'";
		unset($r); exec($cmd, $r);
		foreach ($r as $row) {
			$arr = explode("\t", $row);
			// 前一周周五开始算，一直到下一周周三0点结束
			$start = $arr[6] - 3 * 86400;
			$end = $arr[6] + $arr[21] * 7 * 86400 + 2 * 86400 + 50 * 86400;
//			$end = $arr[6] + $arr[21] * 7 * 86400 + 2 * 86400;
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
		foreach ($this->tourList as $t) {
			$t->printSelf();
			$url = "https://api.wtatennis.com/tennis/tournaments/$t->tourID/$t->year/players/";
			$html = http($url, null, null, null);
			if (!$html) {
				print_line("download players failed");
				continue;
			}

			$json_content = json_decode($html, true);
			if (!$json_content || !isset($json_content['events'])) {
				print_line("players parsed failed");
				continue;
			}

			$fp = fopen(join("/", [DATA, "tour", "player", $t->year, $t->eventID]), "w");
			fputs($fp, $html . "\n");
			fclose($fp);
			sleep(3);
		}
		return [true, ""];
	}

	protected function downDrawFile() {
		print_line("begin to down draws");
		foreach ($this->tourList as $t) {
			$t->printSelf();
			$drawInfo = [];
			$url = "https://www.wtatennis.com/tournament/$t->tourID/beijing/$t->year/draws";
			$html = http($url, null, null, null);
			if (!$html) return [false, "download draw failed"];

			$html_content = str_get_html($html);
			if (!$html_content) return [false, "draw parse failed"];

			foreach ($html_content->find('.tournament-draw__tab') as $eventDiv) {
				$event = $eventDiv->{"data-event-type"};
				$roundNum = 0;
				foreach ($eventDiv->find('.tournament-draw__round-title-container') as $div) {
					++$roundNum;
					$round = $div->find('.tournament-draw__round-title', 0)->innertext;
					$prize = $div->find('.tournament-draw__round-prize strong', 0)->innertext;
					$currency = "$";
					$prize = preg_replace('/[^\d]/', '', $prize);
					$drawInfo[$event]['round'][] = [
						'roundNum' => $roundNum, 
						'round' => $round, 
						'currency' => $currency, 
						'prize' => $prize
					];
				}

				foreach ($eventDiv->find('.tournament-draw__round-container--0 .match-table__row') as $drawLine) {
					if ($drawLine->{"data-player-row-id"} == "player") {
						if (strpos($drawLine->innertext, "Bye") !== false) {
							$pids = ["BYE"];
						} else if (strpos($drawLine->innertext, "Quali") !== false || strpos($drawLine->innertext, "Lucky") !== false || strpos($drawLine->innertext, "Alter") !== false) {
							$pids = ["QUAL"];
						}
					} else {
						$pids = explode("-", str_replace("player-", "", $drawLine->{"data-player-row-id"}));
					}
					$drawInfo[$event]['draw'][] = $pids;
				}

				// 从draw中记录w/o的比赛
				foreach ($eventDiv->find('table.match-table') as $matchTable) {
					if (strpos($matchTable->innertext, "W.O.") === false && strpos($matchTable->innertext, "Walk over") === false) continue;
					$winnerClass = $matchTable->{"data-winner-class"};
					$p1 = $matchTable->find('tr', 0)->{"data-player-row-id"};
					$p2 = $matchTable->find('tr', 1)->{"data-player-row-id"};
					if ($p1 == $winnerClass) $mStatus = "L";
					else $mStatus = "M";
					$key = str_replace("player-", "", $p1) . "-" . str_replace("player-", "", $p2);
					$drawInfo[$event]['wo'][$key] = $mStatus;
				}
			}
			$fp = fopen(join("/", [DATA, "tour", "draw", $t->year, $t->eventID]), "w");
			fputs($fp, json_encode($drawInfo) . "\n"); 
			fclose($fp);
			sleep(3);
		}
		return [true, ""];
	}

	protected function downOOPFile() {
		print_line("begin to down oop");
		foreach ($this->tourList as $t) {
			$t->printSelf();
			$seqMap = [];

			$url = "https://api.wtatennis.com/tennis/tournaments/$t->tourID/$t->year/oop/";
			$html = http($url, null, null, null);
			if (!$html) {
				print_line("download oop page failed");
				continue;
			}
			$html_content = json_decode($html, true);
			if (!isset($html_content["orderOfPlay"][0])) {
				print_line("parse oop page failed");
				continue;
			}

			$json_content = json_decode($html_content["orderOfPlay"][0], true);
			if (!$json_content) {
				print_line("parse oop page failed");
				continue;
			}

/*
			// 先下载oop首页，拿到dateSeq与日期的对应的关系
			$url = "https://www.wtatennis.com/tournament/$t->tourID/beijing/$t->year/order-of-play";
			$html = http($url, null, null, null);
			if (!$html) {
				print_line("download oop page failed");
				continue;
			}
			$html = str_replace("<!--", "", $html);
			$html = str_replace("-->", "", $html);
			$html_content = str_get_html($html);
			if (!$html_content) {
				print_line("oop page parse failed");
				continue;
			}
			foreach ($html_content->find('.day-navigation__button') as $dayButton) {
				$date = $dayButton->{"data-date"};
				$seq = str_replace("Day ", "", $dayButton->{"aria-label"});
				$seqMap[$seq] = $date;
			}

			// 再下载真正的oop
			$url = "https://api.wtatennis.com/tennis/tournaments/$t->tourID/$t->year/matches/";
			$html = http($url, null, null, null);
			if (!$html) {
				print_line("download oop failed");
				continue;
			}
			$json_content = json_decode($html, true);
			if (!$json_content || !isset($json_content["matches"])) {
				print_line("oop parsed failed");
				continue;
			}
			$json_content["seq"] = $seqMap;
*/

			$fp = fopen(join("/", [DATA, "tour", "oop", $t->year, $t->eventID]), "w");
			fputs($fp, json_encode($json_content) . "\n"); 
			fclose($fp);
			sleep(3);
		}
		return [true, ""];
	}

	protected function downResultFile() {
		print_line("begin to down result");
		foreach ($this->tourList as $t) {
			$t->printSelf();

			$url = "https://api.wtatennis.com/tennis/tournaments/$t->tourID/$t->year/matches/";
			$html = http($url, null, null, null);
			if (!$html) {
				print_line("download oop failed");
				continue;
			}
			$json_content = json_decode($html, true);
			if (!$json_content || !isset($json_content["matches"])) {
				print_line("oop parsed failed");
				continue;
			}

			$fp = fopen(join("/", [DATA, "tour", "result", $t->year, $t->eventID]), "w");
			fputs($fp, json_encode($json_content) . "\n"); 
			fclose($fp);
			sleep(3);
		}
		return [true, ""];
	}

}


