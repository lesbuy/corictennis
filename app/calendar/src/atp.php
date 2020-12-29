<?php

require_once('base.class.php');
require_once(APP . '/tool/simple_html_dom.php');

class Calendar extends CalendarBase {
	// local模式下，源文件使用本地的
	private $mode = "local";

	private $mapLevel = [
		"categorystamps_250.png" => "ATP250",
		"categorystamps_atpcup.svg" => "AC",
		"categorystamps_grandslam.png" => "GS",
		"categorystamps_500.png" => "ATP500",
		"categorystamps_1000.png" => "ATP1000",
		"categorystamps_lvr.png" => "LC",
		"categorystamps_finals.svg" => "WC",
		"categorystamps_nextgen.svg" => "XXI",
	];

	protected function preProcessSelf() {
		return [true, ""];
	}

	protected function download() {
		if ($this->mode != "local") {
			$this->url = "https://www.atptour.com/en/tournaments";
			$content = http($this->url, null, null, null);
			if ($content == "") return [false, "download failed"];
			$this->content = $content;
		} else {
			$this->content = file_get_contents("atp_calendar");
		}
		return [true, ""];
	}

	protected function parse() {
		$html = str_get_html($this->content);
		if (!$html) return [false, "dom parse failed"];

		$redis = new_redis();

		foreach ($html->find('.tourney-result') as $tr) {
			$json_content = trim($tr->find('script', 0)->innertext);
			if (!$json_content) return [false, "no json content in tourney result"];
			$content = json_decode($json_content, true);
			if (!$content) return [false, "json content parsed error in tourney result"];

			$t = new TournamentInfo;
			$t->asso = $this->asso;
			$t->level = preg_replace('/^.*\//', "", $content["image"][0]);
			$t->level = $this->mapLevel[$t->level];
			if ($t->level == "GS") continue;	
			$urlArr = explode("/", $content["organizer"]["url"]);
			$t->liveID = $urlArr[4];
			$t->eventID = sprintf("%04d", $t->liveID);
			$t->year = 2021;
			$t->gender = "M";
			$t->start = date('Y-m-d', strtotime($content['startDate']));
			$t->end = date('Y-m-d', strtotime($content['endDate']));
			$t->monday = get_monday($t->start);
			$t->monday_unix = strtotime($t->monday);
			if (strtotime($t->end) - strtotime($t->start) > 10 * 86400) $this->weeks = 2;
			$t->title = $content["organizer"]["name"];
			$t->city = $content["location"]["name"];
			if ($t->title == "ATP Cup") $t->city = $t->title;
			$t->nation = trim(preg_replace('/^.*,/', "", $content["location"]["address"]["addressCountry"]));
			if (preg_match('/^[A-Z]{3}$/', $t->nation)) {
				$t->nation3 = $t->nation;
			} else {
				$t->nation3 = $redis->cmd('HGET', 'nation_long2short', $t->nation)->get();
			}

			$details = array_map(
				function ($d) {
					return trim(preg_replace('/  */', " ", preg_replace('/<[^>]*>/', "", $d->innertext)));
				}, 
				$tr->find('.tourney-details-table-wrapper .tourney-details .info-area .item-details')
			);
			if (count($details) >= 1) {
				$detailArr = explode(" ", $details[0]);
				$t->drawMaleSingles = (int)@$detailArr[1];
				$t->drawMaleDoubles = (int)@$detailArr[3];
			}
			if (count($details) >= 2) {
				$detailArr = explode(" ", $details[1]);
				$io = @$detailArr[0];
				$sfc = @$detailArr[1];
			} else {
				$io = $sfc = "";
			}
			if (count($details) >= 3) {
				$detailArr = explode(" ", $details[2]);
				$fin = @$detailArr[0];
			} else {
				$fin = "";
			}
			if ($t->level != "AC") { // atp cup不用修改签位
				$t->drawMaleSingles = ceil_power($t->drawMaleSingles);
				$t->drawMaleDoubles = ceil_power($t->drawMaleDoubles);
			}
			$t->inOutdoor = substr($io, 0, 1);
			$t->surface = $sfc;
			$t->totalPrize = (int)preg_replace('/[^\d]/', '', $fin);
			if (strpos($fin, "€") !== false) $t->currency = "€";
			
			$this->tournaments[] = $t;
		}

		return [true, ""];
	}

}

$start = "2020-08-01";
$end = "2021-03-31";

$calendar = new Calendar("atp", $start, $end);
