<?php

require_once('base.class.php');

class Calendar extends CalendarBase {

	private $mapLevel = [
		"Finals" => "YEC",
		"International" => "WTA250",
		"Premier" => "WTA500",
		"Premier 5" => "WTA1000",
		"Premier Mandatory" => "WTA1000",
	];

	protected function preProcessSelf() {
		return [true, ""];
	}

	protected function download() {
		$this->url = "https://api.wtatennis.com/tennis/tournaments/?page=0&pageSize=200&excludeLevels=ITF,Grand%20Slam,125K&from=$this->start&to=$this->end";
		$content = http($this->url, null, null, ["account: wta"]);
		if ($content == "") return [false, "download failed"];

		$this->content = $content;
		return [true, ""];
	}

	protected function parse() {

		$json = json_decode($this->content, true);
		if (!$json || !isset($json["content"]) || !is_array($json["content"])) return [false, "parse content failed"];

		$redis = new_redis();

		foreach ($json["content"] as $tour) {
			$t = new TournamentInfo;
			$t->asso = $this->asso;
			$t->level = $this->mapLevel[$tour['tournamentGroup']['level']];
			$t->eventID = $tour['liveScoringId'];
			$t->liveID = $tour['tournamentGroup']['id'];
			$t->year = $tour['year'];
			if (isset($tour['tournamentGroup']['customStatus' . $t->year]) && $tour['tournamentGroup']['customStatus' . $t->year] == "CANCELLED") continue;
			$t->gender = "W";
			$t->start = $tour['startDate'];
			$t->end = $tour['endDate'];
			$t->monday = get_monday($t->start);
			$t->monday_unix = strtotime($t->monday);
			if (strtotime($t->end) - strtotime($t->start) > 10 * 86400) $t->weeks = 2;
			$t->title = preg_replace('/ - .*$/', "", $tour['title']);
			$t->surface = $tour['surface'];
			$t->inOutdoor = $tour['inOutdoor'];
			$t->city = ucwords(strtolower($tour['city']));
			$t->nation = ucwords(strtolower($tour['country']));
			if (strpos($t->nation, "United States") !== false) $t->nation = "United States";
			if (preg_match('/^[A-Z]{3}$/', $t->nation)) {
				$t->nation3 = $t->nation;
			} else {
				$t->nation3 = $redis->cmd('HGET', 'nation_long2short', $t->nation)->get();
			}
			$t->totalPrize = $tour['prizeMoney'];
			$t->drawFemaleSingles = $tour['singlesDrawSize'];
			$t->drawFemaleDoubles = $tour['doublesDrawSize'];
			$this->tournaments[] = $t;
		}
		return [true, ""];
	}

}

$start = "2021-01-01";
$end = "2021-03-31";

$calendar = new Calendar("wta", $start, $end);
