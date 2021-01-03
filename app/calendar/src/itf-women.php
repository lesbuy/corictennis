<?php

require_once('base.class.php');
require_once(APP . '/tool/simple_html_dom.php');

class Calendar extends CalendarBase {
	// local模式下，源文件使用本地的
	private $mode = "";
	private $cookie = "visid_incap_178373=8IbgfA5SR7OiRD/nlOa36wvJyV8AAAAAQUIPAAAAAADIVvdeWQ3i2tO/itCmXmih; ARRAffinity=82e1d29ea0a45b1e248ce0cf31f64d1b7aedea015c1d3ab41be21933fdc403ce; ARRAffinitySameSite=82e1d29ea0a45b1e248ce0cf31f64d1b7aedea015c1d3ab41be21933fdc403ce; incap_ses_637_178373=uobfcNjHaFxZXBrtUBTXCMzw418AAAAAeZA8NCFSjvqt6jPp7v9P8w==; incap_ses_577_178373=YMjPZ2xoKGnU0RH6puoBCAmk6V8AAAAA2qUET3GXcmgjhYMnS+xG5w==;";
	private $cookie2 = "visid_incap_178373=1tqvUklTQG26vgGI+Iq2zfna6V8AAAAAQUIPAAAAAADSOstKRy8N6r3fgPc8sxoM; incap_ses_577_178373=lcc5VRAr20tN9EL6puoBCBL66V8AAAAAqPXG6rIYJQd5qvqBOi9L6w==; ARRAffinity=82e1d29ea0a45b1e248ce0cf31f64d1b7aedea015c1d3ab41be21933fdc403ce; ARRAffinitySameSite=82e1d29ea0a45b1e248ce0cf31f64d1b7aedea015c1d3ab41be21933fdc403ce; incap_ses_893_178373=YI4+Pwzff3CnX9Fh85JkDPz66V8AAAAALfw2mrepDCfUMlI1qVwaIw==; incap_ses_795_178373=qjXBJOAwyBsI7Nv7dWgIC/0A6l8AAAAAeyj04dTC5GhNfTHqqpjmEg==";

	protected function preProcessSelf() {
		return [true, ""];
	}

	protected function download() {
		if ($this->mode != "local") {
			$this->url = "https://www.itftennis.com/Umbraco/Api/TournamentApi/GetCalendar?circuitCode=WT&searchString=&skip=0&take=100&nationCodes=&zoneCodes=&dateFrom=$this->start&dateTo=$this->end&indoorOutdoor=&categories=&isOrderAscending=true&orderField=startDate&surfaceCodes=";
			$content = http($this->url, null, $this->cookie2, null);
			if ($content == "") return [false, "download failed"];
			$this->content = $content;
		} else {
			$this->content = file_get_contents("itf_men_calendar");
		}
		return [true, ""];
	}

	protected function parse() {
		//echo $this->content . "\n"; return [true, ""];

		$json_content = json_decode($this->content, true);
		if (!$json_content || !isset($json_content["items"]) || !is_array($json_content["items"])) {
			echo $this->content . "\n";
			return [false, "json parse failed"];
		}

		foreach ($json_content["items"] as $tour) {
			if ($tour['tourStatusCode'] == "CN" || $tour['tourStatusCode'] == "PP" || strpos($tour['tournamentLink'], "closed") !== false) continue;
			$t = new TournamentInfo;
			$t->asso = $this->asso;
			preg_match('/^(.*)-(\d{4})$/', $tour['tournamentKey'], $m);
			$t->eventID = $m[1];
			$t->year = $m[2];
			$t->liveID = 0;
			$t->gender = "W";
			$t->level = $tour['category'];
			$t->start = date('Y-m-d', strtotime($tour['startDate']));
			$t->end = date('Y-m-d', strtotime($tour['endDate']));
			$t->monday = get_monday($t->start);
			$t->monday_unix = strtotime($t->monday);
			if (strtotime($t->end) - strtotime($t->start) > 10 * 86400) $t->weeks = 2;
			$t->title = $tour['tournamentName'];
			if (strpos($t->title, "+H") !== false) $t->hospital = true;
			$t->city = preg_replace('/,.*$/', '', $tour['location']);
			$t->nation = $tour["hostNation"];
			$t->nation3 = $tour["hostNationCode"];
			$t->inOutdoor = substr($tour['indoorOrOutDoor'], 0, 1);
			$t->surface = $tour['surfaceDesc'];
			$fin = $tour['prizeMoney'];
			$t->totalPrize = (int)preg_replace('/[^\d]/', '', $fin);
			if (strpos($fin, "€") !== false) $t->currency = "€";

			/*
			$url = "https://www.itftennis.com/Umbraco/Api/TournamentApi/GetEventFilters?tournamentKey=" . strtolower($tour['tournamentKey']);
			echo $url . "\n";
			$content = http($url, null, $this->cookie2, null);
			echo $content . "\n";
			if (preg_match('/(1[01]\d{8})/', $content, $m)) {
				$t->liveID = $m[1];
			}
			sleep(7);
			*/

			$this->tournaments[] = $t;
		}

		return [true, ""];
	}

}

$start = "2021-01-01";
$end = "2021-01-31";

$calendar = new Calendar("itf-women", $start, $end);
