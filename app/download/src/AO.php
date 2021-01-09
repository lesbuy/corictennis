<?php
require_once('base.class.php');

class Down extends DownBase {
	private $eventID = "AO";
	private $year = 2021;
	private $start_date = "2021-02-08 05:00"; // 此日为第1日，后面的start,end 都是偏移量
	private $quali_start_date = "2021-01-10 05:00"; // 此日为资格赛第1天
	private $eventConf = [
		"156126" => ["event" => "QS", "draw" => 128, "round" => 3, "eventid2" => 0, "start" => -30, "end" => -25],
		"156131" => ["event" => "PS", "draw" => 128, "round" => 3, "eventid2" => 1, "start" => -30, "end" => -25],
		"156216" => ["event" => "MS", "draw" => 128, "round" => 7, "eventid2" => 0, "start" => -3, "end" => 14],
		"156236" => ["event" => "WS", "draw" => 128, "round" => 7, "eventid2" => 1, "start" => -3, "end" => 14],
		"156211" => ["event" => "MD", "draw" => 64, "round" => 6, "eventid2" => 2, "start" => -3, "end" => 14],
		"156231" => ["event" => "WD", "draw" => 64, "round" => 6, "eventid2" => 3, "start" => -3, "end" => 14],
		"156256" => ["event" => "XD", "draw" => 32, "round" => 5, "eventid2" => 4, "start" => 0, "end" => 14],
	];

	protected function getTourList() {
		return [true, ""];
	}

	protected function downPlayerFile() {
		print_line("begin to down players");
		$url = "https://ausopen.com/event/all/players";
		$html = http($url, null, null, null);
		if (!$html) {
			print_line("download players failed");
			return [false, "download players failed"];
		}
		$fp = fopen(join("/", [DATA, "tour", "player", $this->year, $this->eventID, "players"]), "w");
		output_content($html, $fp); 
		fclose($fp);
		sleep(2);
		return [true, ""];
	}

	protected function downDrawFile() {
		print_line("begin to down draws");
		foreach ($this->$eventConf as $eventUUID => $eventInfo) {
			print_line("down draw", $eventInfo["event"]);
			$start_unix = strtotime($this->start_date . " " . $eventInfo["start"] . " days");
			$end_unix = strtotime($this->start_date . " " . $eventInfo["end"] . " days");
			if (time() < $start_unix || time() > $end_unix) continue;

			$url = "https://prod-scores-api.ausopen.com/event/" . $eventUUID . "/draws";
			$html = http($url, null, null, null);
			if (!$html) {
				print_line("download draw failed");
				continue;
			}
			$html_content = json_decode($html, true);
			if (!$html_content || !isset($html_content["event"]["draw_availability"]) || !$html_content["event"]["draw_availability"]) {
				print_line("draw not exist");
				continue;
			}

			$fp = fopen(join("/", [DATA, "tour", "draw", $this->year, $this->eventID, $eventInfo["event"]]), "w");
			output_content($html, $fp); 
			fclose($fp);
			sleep(2);
		}
		return [true, ""];
	}

	protected function downOOPFile() {
		print_line("begin to down oop");
		$qm = "MD";
		$day = ceil((time() - strtotime($this->start_date)) / 86400);
		foreach ([$day, $day + 1, $day + 2, $day + 3] as $day) {
			if ($day < -30 || ($day >= -26 && $day < 1) || $day > 14) continue;
			$originalDay = $day;
			if ($day < 15) {
				$day += 30;
				$qm = "Q";
			}

			$url = "https://prod-scores-api.ausopen.com/year/$this->year/period/$qm/day/$day/schedule";
			$html = http($url, null, null, null);
			if (!$html) {
				print_line("download $day oop page failed");
				continue;
			}
			$html_content = json_decode($html, true);
			if (!$html_content || isset($html_content["error"])) {
				print_line("parse $day oop page failed");
				continue;
			}

			$fp = fopen(join("/", [DATA, "tour", "oop", $this->year, $this->eventID, $originalDay]), "w");
			output_content($html, $fp); 
			fclose($fp);
			sleep(2);
		}
		return [true, ""];
	}

	protected function downResultFile() {
		print_line("begin to down result");
		$qm = "MD";
		$day = ceil((time() - strtotime($this->start_date)) / 86400);
		foreach ([$day - 1, $day] as $day) {
			if ($day < -30 || ($day >= -26 && $day < 1) || $day > 14) continue;
			$originalDay = $day;
			if ($day < 15) {
				$day += 30;
				$qm = "Q";
			}

			$url = "https://prod-scores-api.ausopen.com/year/$this->year/period/$qm/day/$day/results";
			$html = http($url, null, null, null);
			if (!$html) {
				print_line("download $day result page failed");
				continue;
			}
			$html_content = json_decode($html, true);
			if (!$html_content || isset($html_content["error"])) {
				print_line("parse $day result page failed");
				continue;
			}

			$fp = fopen(join("/", [DATA, "tour", "result", $this->year, $this->eventID, $originalDay]), "w");
			output_content($html, $fp); 
			fclose($fp);
			sleep(2);
		}
		return [true, ""];
	}
}


