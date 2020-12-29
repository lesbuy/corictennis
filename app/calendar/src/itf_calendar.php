<?php

if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php');
require_once(ROOT . "/tools/simple_html_dom.php");

$sex = get_param($argv, 1, 'MT');
$st = get_param($argv, 2, date('Y-m-01', strtotime("+10 days")));
$et = get_param($argv, 3, date('Y-m-d', strtotime(date('Y-m-01', strtotime($st . " +35 days"))) - 86400));

//$cookie = file_get_contents($root_path . '/cookie/itf_curl_cookie');
$cookie = "ARRAffinity=9027cc01602a77f2d2d43ef4f924cd969300186ff9c3202425c3ed52b05be0c0; ARRAffinitySameSite=9027cc01602a77f2d2d43ef4f924cd969300186ff9c3202425c3ed52b05be0c0; visid_incap_178373=8IbgfA5SR7OiRD/nlOa36wvJyV8AAAAAQUIPAAAAAADIVvdeWQ3i2tO/itCmXmih; incap_ses_931_178373=nIIJAz6Fww88z7Ql0JPrDAzJyV8AAAAAcB1nHTcJcooBtIJxsRFLcA==; _ga=GA1.2.1621265410.1607059731; _gid=GA1.2.434060618.1607059731; __gads=ID=963a6ee667dcc237-224439cefcc4008d:T=1607059731:RT=1607059731:S=ALNI_MZqYBPSWYOBHAYr3wXt0lSlCR3TFw; _fbp=fb.1.1607059734166.377218978; _gat_gtag_UA_337765_1=1";
$headers = [
	'authority: www.itftennis.com',
	'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.193 Safari/537.36',
	'content-type: application/json',
	'accept: */*',
	'sec-fetch-site: same-origin',
	'sec-fetch-mode: cors',
	'sec-fetch-dest: empty',
	'referer: https://www.itftennis.com/en/tournament/w15-cairo/egy/2020/w-itf-egy-26a-2020/draws-and-results/',
	'accept-language: zh-CN,zh;q=0.9,en;q=0.8,und;q=0.7',
];

#$list_url = "https://www.itftennis.com/Umbraco/Api/TournamentApi/GetCalendarFilterSearch?circuitCode=$sex&searchString=&skip=0&take=100&dateFrom=$st&dateTo=$et";
$list_url = "https://www.itftennis.com/Umbraco/Api/TournamentApi/GetCalendar?circuitCode=$sex&searchString=&skip=0&take=100&nationCodes=&zoneCodes=&dateFrom=$st&dateTo=$et&indoorOutdoor=&categories=&isOrderAscending=true&orderField=startDate&surfaceCodes=";
fputs(STDERR, $list_url . "\n");
$html = http($list_url, NULL, $cookie);
fputs(STDERR, "down url done\n");
sleep(3);
if (!$html) exit;

$json = json_decode($html, true);

if ($json) {
	foreach ($json['items'] as $event) {

		if (isset($event['tourStatusCode']) && $event['tourStatusCode'] == "CN") continue;
		$tourKey = $event['tournamentKey'];
		$arr = explode("-", $tourKey);
		$year = $arr[4];
		$eid = join("-", [$arr[0], $arr[1], $arr[2], $arr[3]]);
		$level = $arr[1];

		$name = $event['tournamentName'];
		$sfc = $event['surfaceDesc'];
		if ($event['indoorOrOutDoor'] == "Indoor") {
			$sfc .= "(I)";
		}
		$city = preg_replace('/, [A-Z][A-Z]$/', '', $event['location']);
		$ioc = $event['hostNationCode'];

		$st = preg_replace('/T.*$/', '', $event['startDate']);
		$st = date('Y-m-d', strtotime($st . " -4 days"));
		$st = date('Y-m-d', strtotime($st . " next Monday"));
		$st_unix = strtotime($st);

		$prize = $event['prizeMoney'];
		$prizeNum = intval(str_replace("$", "", $prize));

		if (strpos($event['tournamentName'], "+H ") !== false) {
			$prize .= "+";
		}
		$weeks = 1;
		
		$tourid = "";
		$try = 0;
		while (true) {
			++$try;
			fputs(STDERR, $try . " times for filter " . $tourKey . "\n");
			$event_filter_url = "https://www.itftennis.com/Umbraco/Api/TournamentApi/GetEventFilters?tournamentKey=" . strtolower($tourKey);
			$event_filter_html = http($event_filter_url, NULL, $cookie, $headers);
			echo $event_filter_html . "\n";
			sleep(5);
			$event_filter_json = json_decode($event_filter_html, true);
			if ($event_filter_json) {
				$tourid = $event_filter_json['tournamentId'];
			} else {
				$event_filter_json = simplexml_load_string($event_filter_html);
				if ($event_filter_json) {
					$tourid = $event_filter_json->TournamentId . "";
				}
			}

			if ($tourid != "" || $try == 10) break;
		}

		$link = $event['tournamentLink'];
		$overview_url = "https://www.itftennis.com" . $link . "overview";
		fputs(STDERR, $overview_url . "\n");
		$overview_html = http($overview_url, NULL, $cookie, $headers);
		echo $overview_html . "\n";
		sleep(5);
		$overview_dom = str_get_html($overview_html);
		if (!$overview_dom) continue;

		$ms = $md = $qs = $qd = $ws = $wd = $ps = $pd = 0;

		if ($overview_dom->find('.tournament-hero__title h2', 0)) {
			$name = html_entity_decode($overview_dom->find('.tournament-hero__title h2', 0)->innertext, ENT_QUOTES);
		}

		foreach ($overview_dom->find('.tournament-info__details-item--draws span') as $span) {
			if (strpos($span->innertext, "Boys") !== false || $sex == "MT") {
				$span_str = str_replace(" ", "&", str_replace(":", "=", str_replace("Boys - ", "", $span->innertext)));
				$span_arr = get_var_field($span_str);
				$ms = intval(@$span_arr['MS']);
				$md = intval(@$span_arr['MD']);
				$qs = intval(@$span_arr['QS']);
				$qd = intval(@$span_arr['QD']);
			} else {
				$span_str = str_replace(" ", "&", str_replace(":", "=", str_replace("Girls - ", "", $span->innertext)));
				$span_arr = get_var_field($span_str);
				$ws = intval(@$span_arr['MS']);
				$wd = intval(@$span_arr['MD']);
				$ps = intval(@$span_arr['QS']);
				$pd = intval(@$span_arr['QD']);
			}
		}

		echo join("\t", [
			$level,
			$eid,
			$tourid,
			substr($sex, 0, 1),
			$year,
			$st,
			$st_unix,
			trim($name),
			$sfc,
			$city,
			$ioc,
			$prize,
			$ms,
			$md,
			$qs,
			$qd,
			$ws,
			$wd,
			$ps,
			$pd,
			$prizeNum,
			$weeks,
		]) . "\n";		

		sleep(10);
	}
}
