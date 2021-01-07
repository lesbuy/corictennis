<?php
require_once('base.class.php');
require_once(APP . '/tool/decrypt.php');
require_once(APP . '/tool/simple_html_dom.php');

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
//			$end = $arr[6] + $arr[21] * 7 * 86400 + 2 * 86400 + 50 * 86400;
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
}

