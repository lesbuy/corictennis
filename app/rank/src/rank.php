<?php

if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 
require_once(APP . '/tool/simple_html_dom.php');
require_once('wt_bio.php');

class Rank {

	protected $atp_s_url = "https://www.atptour.com/en/rankings/singles?rankDate={date}&rankRange=1-5000&ajax=true";
	protected $atp_d_url = "https://www.atptour.com/en/rankings/doubles?rankDate={date}&rankRange=1-5000&ajax=true";
	protected $atp_race_url = "https://www.atptour.com/en/rankings/singles-race-to-london?rankDate={date}&rankRange=1-5000&ajax=true";
	protected $atp_drace_url = "https://www.atptour.com/en/rankings/doubles-race-to-london?rankDate={date}&rankRange=1-5000&ajax=true";
	protected $atp_u21_url = "https://www.atptour.com/en/rankings/race-to-milan?rankDate={date}&rankRange=1-5000&ajax=true";
	protected $wta_s_url = "https://api.wtatennis.com/tennis/players/ranked?page={page}&pageSize=100&type=rankSingles&sort=asc&name=&metric=SINGLES&at={date}&nationality=";
	protected $wta_d_url = "https://api.wtatennis.com/tennis/players/ranked?page={page}&pageSize=100&type=rankDoubles&sort=asc&name=&metric=DOUBLES&at={date}&nationality=";
	protected $wta_race_url = "https://api.wtatennis.com/tennis/players/ranked?page={page}&pageSize=100&type=RankChampSingles&sort=asc&name=&metric=CHAMPSINGLES&at={date}&nationality=";
	protected $wta_drace_url = "https://api.wtatennis.com/tennis/players/ranked/champDoubles?page={page}&pageSize=100&type=rank&sort=asc";
	protected $max_page = 1;

	protected $gender;
	protected $type;
	protected $date;
	protected $ori_url = null;
	protected $url = null;
	protected $rank_table = [];
	protected $redis = null;

	public function __construct() {
		$argv = func_get_args();
		$gender = get_param($argv, 0, 'atp', ' atp wta ');
		$type = get_param($argv, 1, 's', ' s d race drace u21 ');
		$date = get_param($argv, 2, null);

		$this->gender = $gender;
		$this->type = $type;
		$this->date = $date;

		$this->ori_url = str_replace("{date}", $date, $this->{join('_', [$gender, $type, 'url'])});

		if ($gender == 'wta') $this->max_page = 20;

		$ret = self::down();
		exit($ret);
	}

	protected function down() {

		$this->redis = new redis_cli('127.0.0.1', 6379);
		
		$ret = 0;
		for ($i = 0; $i < $this->max_page && !$ret; ++$i) {
			$this->url = str_replace("{page}", $i, $this->ori_url);

			$retry_time = 0;
			$ret = false;
			while ($retry_time < 5) {
				++$retry_time;
				$response = http($this->url);
				if ($response !== false) break;
				sleep(3);
			}
			if ($response === false) {
				fputs(STDERR, $this->url . ' failed\n');
				continue;
			}
			$ret = self::parse($response);
		}
//		self::parse(file_get_contents('2'));

		unset($this->redis); $this->redis = null;

		if ($ret == -2) $ret = 0; // -2表示下下来是空文件，并不代表失败

		return $ret;
	}

	protected function parse($ori) {

		$content = null;
		$bio = new Bio();

		if ($this->gender == 'atp') {
			$content = str_get_html($ori);
			if (!$content) return -1;
			if (!trim($content->find('table.mega-table', 0)->children()[1]->innertext)) {
				return -1;
			}

			foreach ($content->find('.mega-table tr') as $tr) {
				if (!$tr->find('td')) continue;
				$rank = intval(trim($tr->find('td.rank-cell', 0)->innertext));
				$iocs = array_map(function ($d) {return $d->alt;}, $tr->find('td.country-cell .country-item img'));
				$pids = array_map(function ($d) {return strtoupper(explode('/', $d->href)[4]);}, $tr->find('td.player-cell a'));
				$fulls = array_map(function ($d) {return $d->{'data-ga-label'};}, $tr->find('td.player-cell a'));
				$pts = str_replace(',', '', preg_replace('/<[^>]*>/', '', trim($tr->find('td.points-cell', 0)->innertext)));
				$tours = str_replace(',', '', preg_replace('/<[^>]*>/', '', trim($tr->find('td.tourn-cell', 0)->innertext)));
				$firsts = [];
				$lasts = [];
				foreach ($pids as $pid) {
					$res = $this->redis->cmd('HMGET', join('_', [$this->gender, 'profile', $pid]), 'first', 'last')->get();
					if ($res[0]) {
						$firsts[] = $res[0];
						$lasts[] = $res[1];
					} else {
						$ret = $bio->down_bio($pid, $this->gender, $this->redis);
						if (count($ret) > 0) {
							$firsts[] = $ret['first'];
							$lasts[] = $ret['last'];
						} else {
							$firsts[] = "";
							$lasts[] = "";
						}
					} // if
				} //foreach pid

				echo join("\t", [
					join('/', $pids),
					join('/', $fulls),
					$rank,
					$pts,
					$tours,
					$this->date,
					'',
					join('/', $iocs),
					strtotime($this->date),
					join('/', $firsts),
					join('/', $lasts),
				]) . "\n";
					
			} // foreach tr

		} else if  ($this->gender == 'wta') {

			if (!$ori) return -1;
			$content = json_decode($ori, true);
			if (!is_array($content)) return -1;
			if (count($content) == 0) return -2;

			$valid = false;
			foreach ($content as $line) {
				if (isset($line['rankedAt']) && date('Y-m-d', strtotime($line['rankedAt'])) != $this->date) {
					fputs(STDERR, json_encode($line) . "\n");
					continue;
				}
				$valid = true;

				$rank = $line['ranking'];
				$pts = $line['points'];
				$tours = $line['tournamentsPlayed'];
				$iocs = $pids = $fulls = $firsts = $lasts = [];
				foreach (['player', 'player1', 'player2'] as $p) {
					if (!isset($line[$p])) continue;
					$pids[] = $line[$p]['id'];
					$firsts[] = $line[$p]['firstName'];
					$lasts[] = $line[$p]['lastName'];
					$fulls[] = $line[$p]['fullName'];
					$iocs[] = $line[$p]['countryCode'];
				}

				foreach ($pids as $pid) {
					$res = $this->redis->cmd('HMGET', join('_', [$this->gender, 'profile', $pid]), 'first', 'last')->get();
					if (!$res[0]) {
						$ret = $bio->down_bio($pid, $this->gender, $this->redis);
					} // if
				} //foreach pid

				echo join("\t", [
					join('/', $pids),
					join('/', $fulls),
					$rank,
					$pts,
					$tours,
					$this->date,
					'',
					join('/', $iocs),
					strtotime($this->date),
					join('/', $firsts),
					join('/', $lasts),
				]) . "\n";
			}

			if (!$valid) {
				return -1;
			}
		} // if gender

		unset($bio);
	} // function parse

}

$gender = get_param($argv, 1, 'atp', ' atp wta ');
$type = get_param($argv, 2, 's', ' s d race drace u21 ');
$date = get_param($argv, 3, null);
if (!$date) {
	$date = date('Y-m-d', time() + 86400);
	$date = date('Y-m-d', strtotime($date . " last Monday"));
}
$date = date('Y-m-d', strtotime($date));

$rank = new Rank($gender, $type, $date);
