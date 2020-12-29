<?php

if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 
require_once(APP . '/tool/simple_html_dom.php');
require_once(APP . '/conf/wt_bio.php');

class Activity {

	protected $wta_url = "https://api.wtatennis.com/tennis/players/{ID}/matches/?page={PAGE}&pageSize=100&id={ID}&year={YEAR}&type={SD}&sort=desc";
	protected $atp_url = "https://www.atptour.com/en/players/borna-coric/{ID}/player-activity?matchType={SD}&year={YEAR}&ajax=true";
	protected $max_page = 1;

	protected $gender;
	protected $sd;
	protected $pid;
	protected $year;

	protected $first = null;
	protected $last = null;
	protected $ioc = null;
	protected $st;
	protected $weeks;
	protected $recordday;
	protected $eid;
	protected $join_eid;
	protected $loc;
	protected $city;
	protected $title;
	protected $level;
	protected $sfc;
	protected $total_prize;
	protected $currency;
	protected $rank;
	protected $seed;
	protected $entry;
	protected $partner_id;
	protected $partner_first;
	protected $partner_last;
	protected $partner_ioc;
	protected $prize;
	protected $point;
	protected $award_point;
	protected $matches = [];

	protected $pre_eid = null;
	protected $pre_year = null;

	protected $ori_url = null;
	protected $url = null;
	protected $redis = null;
	protected $bio = null;

	protected function clear() {
		$this->st = $this->recordday = $this->year = $this->total_prize = $this->prize = $this->point = $this->award_point = $this->weeks = 0;
		$this->eid = $this->join_eid = $this->loc = $this->city = $this->title = $this->level = $this->sfc = $this->currency = $this->rank = $this->seed = $this->entry = $this->partner_id = $this->partner_first = $this->partner_last = $this->partner_ioc = "";
		unset($this->matches); $this->matches = [];
	}

	protected function output() {

		usort($this->matches, "self::sortBySeq");

		$win = $loss = $streak = 0;
		$final_round = "";
		foreach ($this->matches as $amatch) {
			$final_round = $amatch[1];
			if ($amatch[3] != "" && $amatch[3] != "W/O" && $amatch[3] != "UNP") {
				if ($amatch[2] == "W") {
					++$win;
					if ($streak < 0) $streak = 0;
					++$streak;
				} else if ($amatch[2] == "L") {
					++$loss;
					if ($streak > 0) $streak = 0;
					--$streak;
				}
			}
			if ($final_round == "F" && $amatch[2] == "W") $final_round = "W";
		}

		echo join("\t", [
			$this->pid,
			$this->ioc,
			$this->pre_eid,
			$this->join_eid,
			$this->pre_year,
			$this->st,
			$this->weeks,
			$this->recordday,
			$this->city,
			$this->loc,
			$this->level,
			$this->sfc,
			$this->currency,
			$this->total_prize,
			$this->sd,
			$this->rank,
			$this->seed,
			$this->entry,
			$this->partner_id,
			$this->partner_ioc,
			$this->prize,
			$this->point,
			$this->award_point,
			$final_round,
			$win,
			$loss,
			$streak,
			join("@", array_map(function ($d) {return '!' . join("!", $d) . '!';}, $this->matches)),
		]) . "\n";
	}

	public function __construct() {
		$argv = func_get_args();
		$gender = get_param($argv, 0, 'atp', ' atp wta ');
		$sd = get_param($argv, 1, 's', ' s d ');
		$pid = get_param($argv, 2, null);
		$year = get_param($argv, 3, 'all');
		$page = get_param($argv, 4, 'all');

		$this->gender = $gender;
		$this->sd = $sd;
		$this->pid = $pid;
		$this->year = $year;

		$this->redis = new redis_cli('127.0.0.1', 6379);
		$this->bio = new Bio();

		if ($gender == "wta" && $year == "all") $year = 0;
		if ($sd == "s") {
			if ($gender == "atp") $sd = "Singles"; else $sd = "S";
		} else if ($sd == "d") {
			if ($gender == "atp") $sd = "Doubles"; else $sd = "D";
		}

		$this->ori_url = str_replace("{ID}", $pid, str_replace("{SD}", $sd, str_replace("{YEAR}", $year, $this->{join('_', [$gender, 'url'])})));

		if ($page == 'all' && $gender == "wta") $this->max_page = 18;

		$names = $this->redis->cmd('HMGET', join("_", [$this->gender, 'profile', $this->pid]), 'first', 'last', 'ioc')->get();
		if ($names[0] && $names[1]) {
			$this->first = $names[0];
			$this->last = $names[1];
			$this->ioc = $names[2];
		} else {
			$profile = $this->bio->down_bio($this->pid, $this->gender, $this->redis);
			if ($profile) {
				$this->first = $profile['first'];
				$this->last = $profile['last'];
				$this->ioc = $profile['ioc'];
			}
		}

		$ret = self::down();
		exit($ret);
	}

	protected function down() {

		$ret = 0;
		for ($i = 0; $i < $this->max_page && !$ret; ++$i) {
			$this->url = str_replace("{PAGE}", $i, $this->ori_url);

			$retry_time = 0;
			$ret = false;
			while ($retry_time < 5) {
				++$retry_time;
				$response = http($this->url);
//				fputs(STDERR, $this->url . "\n");
				if ($response !== false) break;
				sleep(3);
			}
			file_put_contents(join("/", [TEMP, 'activity', $this->gender, $this->sd, join('_', [$this->pid, $this->year, $i])]), $response);
			if ($response === false || strpos($response, "Error while rendering the view [Player Activity]") !== false) {
				fputs(STDERR, join("\t", ["ERROR_DOWNLOAD", $this->pid, $this->sd, "PAGE" . $i]) . "\n");
				continue;
			}
			$ret = self::parse($response);
		}
		if (count($this->matches) > 0) {
			self::output();
		}
		if ($ret == -1) {
			fputs(STDERR, join("\t", ["ERROR_PARSE", $this->pid, $this->sd, "PAGE" . ($i - 1)]) . "\n");
		}
//		self::parse(file_get_contents('2'));

		if ($ret == -2) $ret = 0; // -2表示下下来是空文件，并不代表失败

		return $ret;
	}

	protected function parse($ori) {

		$content = null;

		if ($this->gender == 'atp') {
			$content = str_get_html($ori);
			if (!$content) return -1;
			if (!$content->find('div.activity-tournament-table')) {
				return -2; // 没有table，那就是没数，不是错误
			}

			foreach ($content->find('div.activity-tournament-table') as $tournament) {
				$table_content = trim($tournament->children(1)->find('thead', 0)->nextSibling()->innertext);
				if (!$table_content) continue;

				$infos = $tournament->find('tr.tourney-result', 0);
				$imgurl = preg_replace("/^.*\/|\?.*$/", "", @$infos->find('.tourney-badge-wrapper img', 0)->src);
				$imgalt = @$infos->find('.tourney-badge-wrapper img', 0)->alt;
				$this->title = strtoupper(trim($infos->find('.tourney-title', 0)->innertext));
				$eid = str_replace("/en/tournaments/", "", $infos->find('.tourney-title', 0)->href);
				$arr = explode("/", $eid);
				if (count($arr) == 3) {
					$eid = sprintf("%04d", $arr[1]);
				} else {
					$eid = "";
				}
				$this->eid = $eid;
				
				$location = trim($infos->find('.tourney-location', 0)->innertext);
				$this->loc = strtoupper(trim(preg_replace('/^.*,/', '', $location)));

				$arr = explode(",", $location);
				if (count($arr) >= 2) {
					$this->city = strtoupper(trim($arr[0]));
				} else {
					$this->city = $this->title;
				}

				$dc_match = null; // 匹配DC的标题用的
				if (strpos($this->title, " WCT") !== false) {
					$this->level = "WCT";
					$this->title = str_replace(" WCT", "", $this->title);
				} else if (strpos($this->title, "LYMPIC") !== false) {
					$this->level = "OL";
				} else if (strpos($this->title, "GRAND SLAM CUP") !== false){
					$this->level = "GC";
				} else if (preg_match('/([A-Z]{3}) +(V\.?S?[\. ]+)([A-Z]{3})( .*)?$/', $this->title, $dc_match) || strpos($this->title, "DAVIS CUP") !== false) {
					$this->level = "DC";
					$this->eid = "9990";
					if ($dc_match) {
						$this->city = "DC" . @$dc_match[4] . ' (' . @$dc_match[1] . ' V ' . @$dc_match[3] . ')';
					} else {
						$this->city = $this->title;
					}
				} else if (strpos($imgurl, "_grandslam_") !== false) {
					$this->level = "GS";
					$this->city = $this->title;
				} else if (strpos($imgurl, "atp_finals") !== false) {
					$this->level = "WC";
					$this->city = $this->title;
				} else if (strpos($imgurl, "atp_cup") !== false) {
					$this->level = "AC";
					$this->city = $this->title;
				} else if (strpos($imgurl, "next-gen") !== false) {
					$this->level = "XXI";
					$this->city = $this->title;
				} else if (strpos($imgurl, "lavercup") !== false) {
					$this->level = "LC";
					$this->city = $this->title;
				} else if (strpos($imgurl, "_1000s_") !== false) {
					$this->level = "1000";
				} else if (strpos($imgurl, "_500_") !== false) {
					$this->level = "500";
				} else if (strpos($imgurl, "_250_") !== false) {
					$this->level = "250";
				} else if (strpos($imgurl, "_challenger_") !== false) {
					$this->level = "CH";
				} else if (strpos($imgurl, "_itf_") !== false) {
					$this->level = "ITF";
				} else {
					$this->level = "ATP";
				}
				if ($this->eid == "0311") $this->city = $this->title;

				$this->currency = "$"; $this->total_prize = 0;
				if ($infos->find('.prize-money')) {
					$total_prize = str_replace(",", "", trim($infos->find('.prize-money .info-area .item-value', 0)->innertext));
					preg_match('/^([^0-9]*)([0-9]+)$/', $total_prize, $m);
					if (isset($m[1])) {
						$this->currency = $m[1];
					}
					if (isset($m[2])) {
						$this->total_prize = $m[2];
					}
				}

				$tdates = explode("-", $infos->find('.tourney-dates', 0)->innertext);
				$st = trim(str_replace(".", "", $tdates[0]));
				$st = date('Ymd', strtotime(date('Y-m-d', strtotime($st . " +4 days")) . " last Monday"));
				$this->st = $st;
				$this->weeks = 1;
				$et_unix = 0;
				if (count($tdates) == 2) {
					$et_unix = strtotime(trim(str_replace(".", "", $tdates[1])));
					$st_unix = strtotime($this->st);
					if ($et_unix - $st_unix > 86400 * 8) {
						$this->weeks = 2;
					}
				}

				$sfc = $infos->find('.tourney-details', 1)->find('.info-area .item-value', 0);
				$this->sfc = trim($sfc->innertext);
				if (strpos($sfc->parent()->innertext, "I") !== false) {
					$this->sfc .= "(I)";
				}
					
				// recordday 表示实际记上的周一
				$this->recordday = date('Ymd', strtotime($this->st) + $this->weeks * 7 * 86400);
				if ($this->level == "ITF") { // 如果是ITF低级别，再延一周
					$this->recordday = date('Ymd', strtotime($this->recordday) + 7 * 86400);
				}
				if ($this->year >= 2014 && $this->level == "WC") {
					$this->recordday = date('Ymd', strtotime($this->recordday) - 2 * 7 * 86400);
				}
				if ($this->level == "XXI") {
					$this->recordday = date('Ymd', strtotime($this->recordday) - 1 * 7 * 86400);
				}

				$extra = trim($tournament->find('.activity-tournament-caption', 0)->innertext);
				$arr = explode(", ", $extra);
				foreach ($arr as $item) {
					if (strpos($item, "Points") !== false) {
						$this->point = intval(preg_replace('/[^0-9]/', '', $item));
					} else if (strpos($item, "Ranking") !== false) {
						$this->rank = intval(preg_replace('/[^0-9]/', '', $item));
						if (in_array($this->rank, ['', '0', '9999', '-'])) $this->rank = '';
					} else if (strpos($item, "Prize") !== false) {
						$this->prize = intval(preg_replace('/[^0-9]/', '', $item));
					} else if (strpos($item, "Partner") !== false) {
						preg_match('/\/en\/players\/.*\/([a-z0-9]{4})\/overview/', $item, $m);
						if (isset($m[1])) {
							$this->partner_id = strtoupper($m[1]);
							if (!$this->redis->cmd('KEYS', join("_", [$this->gender, 'profile', $this->partner_id]))->get()) {
								$this->bio->down_bio($this->partner_id, $this->gender, $this->redis);
							}
							$this->partner_ioc = $this->redis->cmd('HGET', join("_", [$this->gender, 'profile', $this->partner_id]), 'ioc')->get();
						}
					}
				}

				$thead = $tournament->children(1)->find('thead', 0);
				$tbody = $thead->nextSibling();
				$is_atp_cup_doubles = false;

				if (strpos($thead->find('th', 1)->innertext, "Partner") !== false) {
					$is_atp_cup_doubles = true;
				}

				foreach ($tbody->find('tr') as $tr) {

					$round = $round_seq = $wl = $games = $oppo_id = $oppo_ioc = $partner_id = $partner_ioc = $oppo_partner_id = $oppo_partner_ioc = $oppo_seed = $oppo_entry = $oppo_rank = "";

					// 1st td
					$round = trim($tr->find('td', 0)->innertext);
					if ($round == "Finals") {$round = "F"; $round_seq = 28;}
					else if (strpos($round, "Semi") !== false) {$round = "SF"; $round_seq = 26;}
					else if (strpos($round, "Quarter") !== false) {$round = "QF"; $round_seq = 25;}
					else if (strpos($round, "Round of 16") !== false) {$round = "R16"; $round_seq = 24;}
					else if (strpos($round, "Round of 32") !== false) {$round = "R32"; $round_seq = 23;}
					else if (strpos($round, "Round of 64") !== false) {$round = "R64"; $round_seq = 22;}
					else if (strpos($round, "Round of 128") !== false) {$round = "R128"; $round_seq = 21;}
					else if (strpos($round, "1st Round Qualifying") !== false) {$round = "Q1"; $round_seq = 1;}
					else if (strpos($round, "2nd Round Qualifying") !== false) {$round = "Q2"; $round_seq = 2;}
					else if (strpos($round, "3rd Round Qualifying") !== false) {$round = "Q3"; $round_seq = 3;}
					else if (strpos($round, "4th Round Qualifying") !== false) {$round = "Q4"; $round_seq = 4;}
					else if (strpos($round, "Bronze") !== false) {$round = "3/4 PO"; $round_seq = 27;}
					else if (strpos($round, "Round Robin") !== false) {$round = "RR"; $round_seq = 20;}
					else if (strpos($round, "3rd/4th") !== false) {$round = "3/4 PO"; $round_seq = 27;}
					else {fputs(STDERR, "new round name: " . $round . "\n");}

					// 2nd td
					if ($this->sd == 's') {
						$oppo_rank = trim($tr->find('td', 1)->innertext);
					} else {
						if (!$is_atp_cup_doubles) {
							$oppo_rank = join("/", array_map(function ($d) {return trim($d);}, explode("<br/>", $tr->find('td', 1)->innertext)));
						} else {
							$td = $tr->find('td', 1)->find('.day-table-flag a', 0);
							$partner_id = strtoupper(explode("/", $td->href)[4]);
							if (!$this->redis->cmd('KEYS', join("_", [$this->gender, 'profile', $partner_id]))->get()) {
								$this->bio->down_bio($partner_id, $this->gender, $this->redis);
							}
							$partner_ioc = $this->redis->cmd('HGET', join("_", [$this->gender, 'profile', $partner_id]), 'ioc')->get();
						}
					}

					// 3rd td
					$td = $tr->find('td', 2)->find('.day-table-flag a', 0);
					if (isset($td->href)) {
						$oppo_id = strtoupper(explode("/", $td->href)[4]);
						if (substr($oppo_id, 0, 3) == "AAA") $oppo_id = 0;
						if (!$this->redis->cmd('KEYS', join("_", [$this->gender, 'profile', $oppo_id]))->get()) {
							$this->bio->down_bio($oppo_id, $this->gender, $this->redis);
						}
						$oppo_ioc = $this->redis->cmd('HGET', join("_", [$this->gender, 'profile', $oppo_id]), 'ioc')->get();
					}

					if ($this->sd == "d") {
						$td = $tr->find('td', 2)->find('.day-table-flag a', 1);
						if (isset($td->href)) {
							$oppo_partner_id = strtoupper(explode("/", $td->href)[4]);
							if (substr($oppo_partner_id, 0, 3) == "AAA") $oppo_partner_id = 0;
							if (!$this->redis->cmd('KEYS', join("_", [$this->gender, 'profile', $oppo_partner_id]))->get()) {
								$this->bio->down_bio($oppo_partner_id, $this->gender, $this->redis);
							}
							$oppo_partner_ioc = $this->redis->cmd('HGET', join("_", [$this->gender, 'profile', $oppo_partner_id]), 'ioc')->get();
						}

						if ($is_atp_cup_doubles) {
							$oppo_rank = join("/", array_map(function ($d) {return trim($d->innertext);}, $tr->find('td', 2)->find('.day-table-name .rank')));
						}
					}

					// 4th td
					$wl = trim($tr->find('td', 3)->innertext);

					// 5th td
					if ($tr->find('td', 4)->find('a', 0)) {
						$games = trim($tr->find('td', 4)->find('a', 0)->innertext);
						$games = self::process_atp_score($games, $wl);
					}

					if ($games == "UNP") $wl = "";

					if (in_array($oppo_rank, ['', '0', '9999', '-'])) $oppo_rank = '';

					$this->matches[] = [
						$round_seq,
						$round,
						$wl,
						$games,	
						$oppo_rank,
						$oppo_seed,
						$oppo_entry,
						$oppo_id,
						$oppo_ioc,
						$oppo_partner_id,
						$oppo_partner_ioc,
						$partner_id,
						$partner_ioc,
					];
				}

				$this->pre_eid = $this->eid;
				$this->pre_year = $et_unix ? date('Y', $et_unix) : date('Y', strtotime($this->st) + 4 * 86400);
				self::output();
				self::clear();
			}

		} else if  ($this->gender == 'wta') {

			// 不符合格式全部返回-1，后面不再下载
			if (!$ori) return -1;
			$content = json_decode($ori, true);
			if (!is_array($content)) return -1;
			if (!isset($content['matches']) || !is_array($content['matches'])) return -1;

			$ct = 0;

			if (!$this->first && !$this->last) {
				$this->first = @$content['player']['firstName'];
				$this->last = @$content['player']['lastName'];
				$this->ioc =  @$content['player']['countryCode'];
			}

			foreach ($content['matches'] as $line) {
				++$ct;

				$this->eid = sprintf('%04d', intval($line['tourn_nbr']));
				$this->year = intval($line['tourn_year']);

				if (($this->eid != $this->pre_eid || $this->year != $this->pre_year) && ($this->pre_eid !== null && $this->pre_year !== null)) {
					if (count($this->matches) > 0) {
						self::output();
					}
					self::clear();
				}

				if ($this->level == "") {
					$this->eid = sprintf('%04d', intval($line['tourn_nbr']));
					$this->year = intval($line['tourn_year']);
					if (isset($line['tournament']['liveScoringId'])) {
						$this->join_eid = $line['tournament']['liveScoringId'];
					} else {
						$this->join_eid = "";
					}

					$this->loc = strtoupper($line['Country']);
					if (strpos($this->loc, "USA") !== false || strpos($this->loc, "U.S.A") !== false || strpos($this->loc, "UNITED STATES") !== false) $this->loc = "USA";

					$this->currency = "$";
					$this->total_prize = intval($line['PrizeMoney']);
					$this->city = trim(strtoupper($line['TournamentName']));
					if (strpos($this->city, 'FED CUP') === false) {
						$this->city = preg_replace('/ [0-9]*$/', '', $this->city);
					} else {
						$this->city = str_replace("FED CUP ", "", $this->city);
					}
					$this->title = strtoupper($line['tournament']['title']);
					$this->sfc = ucwords(strtolower($line['Surface']));
					if ($line['tournament']['inOutdoor'] == "I") $this->sfc .= '(I)';

					$ttype = $line['TournamentType'];
					$tlevel = $line['TournamentLevel'];

					if ($ttype == "FC") {
						$this->level = "FC";
					} else if ($tlevel == "ITF") {
						$this->level = "ITF";
					} else if ($tlevel == "CH") {
						$this->level = "YEC";
					} else if (strpos($this->city, "OLYMPICS") !== false) {
						$this->level = "OL";
					} else if ($tlevel == "P") {
						$this->level = "P700";
					} else if ($tlevel == "C") {
						$this->level = "125K";
					} else if ($tlevel == "IS") {
						$this->level = "Int";
					} else if ($tlevel == "I") {
						$this->level = "T1";
					} else if ($tlevel == "II") {
						$this->level = "T2";
					} else if ($tlevel == "III") {
						$this->level = "T3";
					} else if ($tlevel == "IV") {
						$this->level = "T4";
					} else if ($tlevel == "V") {
						$this->level = "T5";
					} else if ($tlevel == "IVA") {
						$this->level = "T4A";
					} else if ($tlevel == "IVB") {
						$this->level = "T4B";
					} else if ($tlevel == "") {
						$this->level = "WTA";
					} else {
						$this->level = $tlevel;
					}

					$this->rank = $line['rank_1'];
					if (in_array($this->rank, ['', '0', '9999', '-'])) $this->rank = '';
					$this->seed = $line['seed_1']; if (!$this->seed) $this->seed = '';
					$this->entry = $line['entry_type_1']; if (!$this->entry) $this->entry = '';

					if ($this->sd == 'd') {
						$this->partner_id = $line['partner']['id'];
						$this->partner_first = $line['partner']['firstName'];
						$this->partner_last = $line['partner']['lastName'];
						$this->partner_ioc = $line['partner']['countryCode'];

						if (!$this->redis->cmd('KEYS', join("_", [$this->gender, 'profile', $this->partner_id]))->get()) {
							$this->bio->down_bio($this->partner_id, $this->gender, $this->redis);
						}

					}

					$st = substr($line['StartDate'], 0, 10);
					if ($this->level == "FC") {
						$st = date('Ymd', strtotime($st . " last Monday"));
					} else {
						$st = date('Ymd', strtotime(date('Y-m-d', strtotime($st . " +4 days")) . " last Monday"));
					}
					$this->st = $st;

					$this->weeks = 1;
					if (isset($line['tournament']['endDate'])) {
						$et_unix = strtotime($line['tournament']['endDate']);
						$st_unix = strtotime($this->st);
						if ($et_unix - $st_unix > 86400 * 8) {
							$this->weeks = 2;
						}
					}
					// recordday 表示实际记上的周一
					$this->recordday = date('Ymd', strtotime($this->st) + $this->weeks * 7 * 86400);
					if ($this->level == "ITF" && $this->total_prize < 40000) { // 如果是ITF低级别，再延一周
						$this->recordday = date('Ymd', strtotime($this->recordday) + 7 * 86400);
					}
					if ($this->eid == 1081 && $this->year >= 2019) { // 2019年之后的小年终隔周再计
						$this->recordday = date('Ymd', strtotime($this->recordday) + 7 * 86400);
					}
					if ($this->year >= 2019 && $this->level == "YEC") {
						$this->recordday = date('Ymd', strtotime($this->recordday) - 2 * 7 * 86400);
					}

					$this->point = $line['points_1'];
					if ($line['points_bonus_1']) {
						$this->award_point = $line['points_bonus_1'];
					}
					$this->prize = intval($line['PrizeWon']);
				}

				$qpm = $line['qpm_flag'];
				$round_num = $line['tourn_round'];
				$round_name = $line['round_name'];
				if ($qpm == "Q") {
					$round_seq = $round_num;
					$round = "Q" . $round_num;
				} else if ($qpm == "M") {
					if ($round_name == "PRE") {
						$round_num = 10;
						$round_name = "3/4 PO";
					}

					if (!isset($round_seq) || !$round_seq) $round_seq = 20;

					if ($round_name == "Q") $round_name = "QF";
					if ($round_name == "S") $round_name = "SF";
					if ($line['tournament']['tournamentGroup']['level'] == "Finals" && $round_num == 1) {
						$round_name = "RR";
						--$round_seq;
					} else {
						$round_seq = 20 + $round_num;
					}
					$round = $round_name;
				}

				$games = str_replace("  ", " ", $line['scores']);
				if ($line['reason_code'] == "D") $games = "W/O";
				else if ($line['reason_code'] == "B") $games = "";
				if (!$games) $games = "-";

				if ($line['winner'] == 1) {
					$wl = "W";
				} else if ($line['winner'] == 2) {
					$wl = "L";
				} else {
					$wl = "";
				}
				if ($line['reason_code'] == "B") $wl = "";

				$oppo_seed = $line['seed_2']; if (!$oppo_seed) $oppo_seed = '';
				$oppo_entry = $line['entry_type_2']; if (!$oppo_entry) $oppo_entry = '';
				$oppo_rank = $line['rank_2']; if (!$oppo_rank) $oppo_rank = '';
				if (in_array($oppo_rank, ['', '0', '9999', '-'])) $oppo_rank = '';

				if (!$line['opponent']) {
					$oppo_id = 0;
					$oppo_first = "";
					$oppo_last = "Bye";
					$oppo_ioc = "";
					$oppo_partner_id = $oppo_partner_first = $oppo_partner_last = $oppo_partner_ioc = "";
				} else {
					$oppo_id = $line['opponent']['id'];
					$oppo_first = $line['opponent']['firstName'];
					$oppo_last = $line['opponent']['lastName'];
					$oppo_ioc = $line['opponent']['countryCode'];

					if (!$this->redis->cmd('KEYS', join("_", [$this->gender, 'profile', $oppo_id]))->get()) {
						$this->bio->down_bio($oppo_id, $this->gender, $this->redis);
					}

					if ($this->sd == "d" && $line['opponent_partner']) {
						$oppo_partner_id = $line['opponent_partner']['id'];
						$oppo_partner_first = $line['opponent_partner']['firstName'];
						$oppo_partner_last = $line['opponent_partner']['lastName'];
						$oppo_partner_ioc = $line['opponent_partner']['countryCode'];

						if (!$this->redis->cmd('KEYS', join("_", [$this->gender, 'profile', $oppo_partner_id]))->get()) {
							$this->bio->down_bio($oppo_partner_id, $this->gender, $this->redis);
						}

					} else {
						$oppo_partner_id = $oppo_partner_first = $oppo_partner_last = $oppo_partner_ioc = "";
					}
				}

				$this->matches[] = [
					$round_seq,
					$round,
					$wl,
					$games,
					$oppo_rank,
					$oppo_seed,
					$oppo_entry,
					$oppo_id,
					$oppo_ioc,
					$oppo_partner_id,
					$oppo_partner_ioc,
				];

				$this->pre_eid = $this->eid;
				$this->pre_year = $this->year;
			
			} // foreach match

			if ($ct < 100) {
				return -2;
			}

		} // if gender

	} // function parse

	protected function sortBySeq($a, $b) {
		return $a[0] < $b[0] ? -1 : 1;
	}

	protected function process_atp_score($games, $wl) {
		if (strpos($games, "W/O") !== false) {
			return "W/O";
		} else if (strpos($games, "UNP") !== false) { // 比赛没打
			return "UNP";
		}

		$aff = "";
		if (strpos($games, "RET") !== false) {
			$aff = " Ret.";
		} else if (strpos($games, "DEF") !== false) {
			$aff = " Def.";
		}
		$games = preg_replace('/\(.*\)/', '', $games);

		$sets = explode(" ", trim($games));
		$scores = [];
		foreach ($sets as $set) {
			preg_match('/^(\d{1,2})-?(\d{1,2})(<sup>(\d+)<\/sup>)?/', $set, $_m);
			if (!isset($_m[1], $_m[2])) continue;
			$s1 = $_m[1];
			$s2 = $_m[2];
			if (isset($_m[4])) {
				$tb = $_m[4];
			} else {
				$tb = null;
			}
			if ($wl == "L") {
				$tmp = $s1; $s1 = $s2; $s2 = $tmp;
			}
			$scores[] = $s1 . "-" . $s2 . ($tb !== null ? '(' . $tb . ')' : '');
		}

		return join(" ", $scores) . $aff;
	}
}

$gender = get_param($argv, 1, 'atp', ' atp wta ');
$sd = get_param($argv, 2, 's', ' s d ');
$pid = get_param($argv, 3, null);
$year = get_param($argv, 4, 'all');
$page = get_param($argv, 5, 'all');

if (!$pid) exit(-1);

$activity = new Activity($gender, $sd, $pid, $year, $page);
