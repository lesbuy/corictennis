<?php
require_once('base.class.php');
require_once(APP . '/tool/decrypt.php');
require_once(APP . '/tool/simple_html_dom.php');
require_once(APP . '/conf/wt_bio.php');

class Down extends DownBase {

	protected function getTourList() {
		$year = date('Y', time());
		$file = join("/", [STORE, "calendar", $year, "WT"]);
		if (date('Y', time() + 10 * 86400) != $year) $file .= " " . join("/", [STORE, "calendar", date('Y', time() + 10 * 86400), "WT"]);
		if (date('Y', time() - 10 * 86400) != $year) $file .= " " . join("/", [STORE, "calendar", date('Y', time() - 10 * 86400), "WT"]);
		$cmd = "cat $file | awk -F\"\\t\" '$4 ~ /M/'";
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
			$url = "http://ws.protennislive.com/LiveScoreSystem/Medium/GetDrawXMLCrypt.aspx?year=$t->year&id=$t->eventID";
			$html = http($url, null, null, null);
			if (!$html) {
				print_line("download draw failed");
				continue;
			}
			$html_content = Decrypt($html);
			if (!$html_content) {
				print_line("parse draw failed");
				continue;
			}

			$XML = simplexml_load_string($html_content);
			if (!$XML) {
				print_line("parse draw failed");
				continue;
			}
			$XML_string = $XML->DrawWrapper->DRAWXML;

			$fp = fopen(join("/", [DATA, "tour", "draw", $t->year, $t->eventID]), "w");
			fputs($fp, $XML_string . "\n"); 
			fclose($fp);
			sleep(3);
		}
		return [true, ""];
	}

	protected function downOOPFile() {
		print_line("begin to down oop");
		foreach ($this->tourList as $t) {
			$t->printSelf();
			$url = "http://ws.protennislive.com/LiveScoreSystem/Medium/GetOOPXMLCrypt.aspx?year=$t->year&id=$t->eventID";
			$html = http($url, null, null, null);
			if (!$html) {
				print_line("download oop page failed");
				continue;
			}

			$html_content = Decrypt($html);
			if (!$html_content) {
				print_line("parse oop failed");
				continue;
			}

			$XML = simplexml_load_string($html_content);
			if (!$XML) {
				print_line("parse oop failed");
				continue;
			}
			$XML_string = $XML->OOPWrapper->OOPXML;

			$fp = fopen(join("/", [DATA, "tour", "oop", $t->year, $t->eventID]), "w");
			fputs($fp, $XML_string . "\n"); 
			fclose($fp);
			sleep(3);
		}
		return [true, ""];
	}

	protected function downResultFile() {
		print_line("begin to down result");
		foreach ($this->tourList as $t) {
			$t->printSelf();
			$url = "http://ws.protennislive.com/LiveScoreSystem/F/Medium/GetCompMatchesCrypt.aspx?y=$t->year&g=T&e=$t->eventID&dSq=-2";
			$html = http($url, null, null, null);
			if (!$html) {
				print_line("download result failed");
				continue;
			}

			$html_content = Decrypt($html);
			if (!$html_content) {
				print_line("parse result failed");
				continue;
			}

			$fp = fopen(join("/", [DATA, "tour", "result", $t->year, $t->eventID]), "w");
			fputs($fp, $html_content . "\n"); 
			fclose($fp);
			sleep(3);
		}
		return [true, ""];
	}

	protected function downLogo() {
		print_line("begin to down logo");
		foreach ($this->tourList as $t) {
			$t->printSelf();
			$url = "https://www.atptour.com/en/tournaments/delray-beach/" . intval($t->eventID) . "/overview";
			$html = http($url, null, null, null);
			if (!$html) {
				print_line("download homepage failed");
				continue;
			}
			$html_content = str_get_html($html);
			if (!$html_content) {
				print_line("parse homepage failed");
				continue;
			}
			$img = $html_content->find('.tournament-sponsor-logo img', 0);
			if (!$img) {
				print_line("no pic on homepage");
				continue;
			}
			$imgLink = preg_replace('/\?.*$/', '', $img->src);
			if ($imgLink != "") {
				$imgLink = "https://www.atptour.com" . $imgLink;
			}

			$fp = fopen(join("/", [STORE, "tourlogo", $t->year, "tourlogo"]), "a");
			fputs($fp, join("\t", [
				"ATP",
				$t->eventID,
				$t->eventID,
				"",
				$t->city,
				$imgLink,
			]) . "\n");
			fclose($fp);
			sleep(3);
		}
		return [true, ""];
	}

	protected function downBio() {
		$bio = new Bio();
		$redis = new_redis();
		$gender = "atp";

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
				'gender' => 1,
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

