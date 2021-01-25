<?php
require_once('base.class.php');
require_once(APP . '/tool/simple_html_dom.php');
require_once(APP . '/conf/wt_bio.php');

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
			$end = $arr[6] + $arr[21] * 7 * 86400 + 2 * 86400;
			if (time() < $start || time() >= $end) continue;
			$t = new DownTour;
			$t->eventID = $arr[1];
			$t->year = $arr[4];
			$t->tourID = intval($arr[1]);
			$t->city = $arr[9];
			$t->monday = $arr[5];
			$this->tourList[] = $t;
		}
		return [true, ""];
	}

	protected function downPlayerFile() {
		return [true, ""];
	}

	protected function downDrawFile() {
		print_line("begin to down draws");
		foreach ($this->tourList as $t) {
			$t->printSelf();
			$drawInfo = [];
			$url = "https://api.wtatennis.com/tennis/tournaments/$t->tourID/$t->year/draw";
			$html = http($url, null, null, null);
			if (!$html) {
				print_line("download draw failed");
				continue;
			}
			$html_content = json_decode($html, true);
			if (!$html_content || !isset($html_content["drawInfo"][0])) {
				print_line("parse draw failed");
				continue;
			}

			$json_content = json_decode($html_content["drawInfo"][0], true);
			if (!$json_content) {
				print_line("parse draw failed");
				continue;
			}

			$json_content["tournament"] = $html_content["tournament"];

/*
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
*/
			$fp = fopen(join("/", [DATA, "tour", "draw", $t->year, $t->eventID]), "w");
			fputs($fp, json_encode($json_content) . "\n"); 
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

	protected function downPortrait() {
		$urlPrefix = "https://api.wtatennis.com/content/wta/photo/EN/?pageSize=100&tagNames=player-headshot&referenceExpression=";

		$file = join("/", [DATA, "rank", "wta", "*", "current"]);
		$cmd = "cat $file | cut -f1,2 | sort -u";
		$pids = [];
		$count = 0;
		$info = [];
		$pid2name = [];
		unset($r); exec($cmd, $r);
		foreach ($r as $line) {
			$arr = explode("\t", $line);
			$pid = $arr[0];
			$name = $arr[1];
			$pid2name[$pid] = $name;
			$pids[] = $pid;
			++$count;
			if ($count == 20) {
				$param = join(" or ", array_map(function ($d) {
					return "TENNIS_PLAYER:" . $d;
				}, $pids));
				$pids = [];
				$count = 0;

				$html = http($urlPrefix . $param, null, null, null);
				$json_content = json_decode($html, true);
				foreach ($json_content["content"] as $p) {
					$pid = $p["references"][0]["id"];
					$title = $p["title"];
					if (strpos($title, "Full-body") === false && strpos($title, "Hero") === false) continue;
					$img = $p["onDemandUrl"] . "?height=603";
					if (strpos($title, "Hero") !== false) {
						$info[$pid] = [$title, $img, $pid2name[$pid]];
					} else if (strpos($title, "Full-body") !== false && !isset($info[$pid])) {
						$info[$pid] = [$title, $img, $pid2name[$pid]];
					}
				}
				sleep(1);
			}
		}

		foreach ($info as $pid => $i) {
			echo join("\t", [$pid, $i[1], $i[2], $i[0]]) . "\n";
		}

		return [true, ""];
	}

	protected function updatePortrait() {
		$file = join("/", [APP, "download", "bin", "wta_portrait"]);

		$fp = fopen($file, "r");
		$fp2 = fopen(join("/", [APP, "redis_script", "portrait"]), "a");
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);
			$pid = $arr[0];
			$name = $arr[1];
			$img = $arr[2];
			$suffix = "";
			if (strpos($img, ".png") !== false) {
				$suffix = ".png";
			} else if (strpos($img, ".jpg") !== false) {
				$suffix = ".jpg";
			}
			$imgFile = str_replace(" ", "-", replace_letters(mb_strtolower($name)));
			$desFile = STORE . "/images/wta_portrait/compressed/" . $imgFile . $suffix;
			$data = [
				"source" => [
					"url" => $img
				]
			];
			$res = http("https://api.tinify.com/shrink", json_encode($data), null, ["Content-Type: application/json"], "api:k44v850Kdkpnn9VdpHKRsYbVG2txGkxs");
			sleep(1);
			$resJson = json_decode($res, true);
			if (!$resJson) continue;
			$compressUrl = $resJson["output"]["url"];
			echo "curl \"" . $compressUrl . "\" > " . $desFile . "\n";
			fputs($fp2, join("\t", [
				"wta",
				$pid,
				$name,
				$imgFile . $suffix
			]) . "\n");
		}
		fclose($fp2);
		fclose($fp);

		return [true, ""];
	}

	protected function downBio() {
		$bio = new Bio();
		$redis = new_redis();
		$gender = "wta";

		$fp = fopen(join("/", [DATA, $gender . "_bio_down_list"]), "r");
		$nation_short2long = [];
		$nodes = []; // 原来的all_name_xxx 表, 即将废弃
		$new_nodes = []; // 新的names表
		$tic = tic();
		while ($line = trim(fgets($fp))) {
			$pid = $line;
			$bio->down_bio($pid, $gender, $redis);
			$res = $redis->cmd("hmget", join("_", [$gender, "profile", $pid]), "first", "last", "ioc", "rank_s", "rank_d", "rank_s_hi", "rank_d_hi")->get();
			$first = $res[0];
			$last = $res[1];
			$name = $first . " " . $last;
			$ioc = $res[2];
			if (!isset($nation_short2long[$ioc])) {
				$nation_short2long[$ioc] = $redis->cmd("hget", "nation_short2long", $ioc)->get();
			}
			$nation = $nation_short2long[$ioc];
			$rank_s = $res[3];
			$rank_d = $res[4];
			$rank_s_hi = $res[5];
			$rank_d_hi = $res[6];
			if ($rank_s <= 100 || $rank_d <= 30) $priority = 1;
			else $priority = 2;

			$rank_s = intval($rank_s);
			if ($rank_s == 0) $rank_s = 9999;

			$node = [
				'id' => $pid,
				'name' => $name,
				'highest' => $rank_s_hi,
				'priority' => $priority,
				'nat' => $ioc,
				'nation' => $nation,
				'first' => $first,
				'last' => $last
			];
			$new_node = [
				'pid' => $pid,
				'name' => $name,
				'highest' => $rank_s_hi,
				'priority' => $priority,
				'ioc' => $ioc,
				'first' => $first,
				'last' => $last,
				'rank' => $rank_s,
				'gender' => 2,
			];

			$nodes[] = $node;
			$new_nodes[] = $new_node;
			print_err($pid, $first, $last);
			sleep(3);
		}
		fclose($fp);
		print_err("download and insert bio ", toc($tic));

		$db = new_db("test");
		$tbname = "all_name_" . $gender;
		$schema = array_keys($nodes[0]);
		$tic = tic();
		$db->multi_insert($tbname, $nodes, $schema);
		print_err("insert into db `all_name_$gender` ", toc($tic));

		$db = new_db("test");
		$tbname = "names";
		$schema = array_keys($nodes[0]);
		$tic = tic();
		$db->multi_insert($tbname, $new_nodes, $schema);
		print_err("insert into db `names` ", toc($tic));

		return [true, ""];
	}

}

