<?php
require_once('base.class.php');

class Down extends DownBase {
	private $eventID = "AO";
	private $year = 2021;
	private $config;
	private $start_date; // 此日为第1日，后面的start,end 都是偏移量
	private $eventConf;

	protected function getTourList() {
		$this->config = json_decode(file_get_contents(join("/", [APP, "draw", "conf", "GS", $this->year, $this->eventID . ".json"])), true);
		$this->start_date = $this->config["startDate"];
		$this->eventConf = $this->config["eventConf"];
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
		foreach ($this->eventConf as $eventUUID => $eventInfo) {
			$start_unix = strtotime($this->start_date . " " . $eventInfo["start"] . " days");
			$end_unix = strtotime($this->start_date . " " . $eventInfo["end"] . " days");
			if (time() < $start_unix || time() > $end_unix) continue;

			print_line("down draw", $eventInfo["event"]);
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
			if ($day <= -30 || ($day >= -26 && $day < 1) || $day > 14) continue;
			$originalDay = $day + 30;
			if ($day < 15) {
				$day += 30;
				$qm = "Q";
			}

			$url = "https://prod-scores-api.ausopen.com/year/$this->year/period/$qm/day/$day/schedule";
			$html = http($url, null, null, null);
			if (!$html) {
				print_line("download $qm $day oop page failed");
				continue;
			}
			$html_content = json_decode($html, true);
			if (!$html_content || isset($html_content["error"])) {
				print_line("parse $qm $day oop page failed");
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
			if ($day <= -30 || ($day >= -26 && $day < 1) || $day > 14) continue;
			$originalDay = $day + 30;
			if ($day < 15) {
				$day += 30;
				$qm = "Q";
			}

			$url = "https://prod-scores-api.ausopen.com/year/$this->year/period/$qm/day/$day/results";
			$html = http($url, null, null, null);
			if (!$html) {
				print_line("download $qm $day result page failed");
				continue;
			}
			$html_content = json_decode($html, true);
			if (!$html_content || isset($html_content["error"])) {
				print_line("parse $qm $day result page failed");
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


