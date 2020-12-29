<?php

require_once(APP . '/tool/decrypt.php');

class Bio {

	protected $bio_url = "http://ws.protennislive.com/LiveScoreSystem/F/Long/GetBioFullCrypt.aspx?p=";
	protected $itf_find_url = "https://www.itftennis.com/Umbraco/Api/PlayerApi/GetPlayerSearch?searchString=";
	protected $atp_find_url = "https://www.atptour.com/en/-/ajax/PredictiveContentSearch/GetPlayerResults/";
	protected $wta_find_url = "https://api.wtatennis.com/search/wta/?size=100&type=PLAYER&typeRestriction=&start=0&fullObjectResponse=true&terms=";

	public function __construct() {
	}

	public function down_bio($pid, $gender = 'atp', $redis = null) {

		if (!$pid) return [];

		$url = $this->bio_url . $pid;
		$res1 = http($url);

		$ret = [];

		if ($res1 === false) {
			fputs(STDERR, $pid . " down failed\n");
		} else {
			$xml = simplexml_load_string(Decrypt($res1));
			if (!$xml) {
				fputs(STDERR, $pid . " parse failed\n");
			} else {
				$array = json_decode(json_encode($xml), true);
				$a = $array['Bio']['@attributes'];
				$ret = [];
				$ret['first'] = $a['fNam'];
				$ret['last'] = $a['lNam'];
				$firsts[] = $ret['first'];
				$lasts[] = $ret['last'];

				$ret['ioc'] = replace_ioc($a['natl']); if ($ret['ioc'] == "N/A") $ret['ioc'] = "";

				$ret['l_en'] = self::rename2long($ret['first'], $ret['last'], $ret['ioc']);
				$ret['s_en'] = self::rename2short($ret['first'], $ret['last'], $ret['ioc']);

                $_birth = explode("/", str_replace(")", "", preg_replace('/^.*\(/', '', $a['age'])));
                $ret['birthday'] = date('Y-m-d', strtotime(join("-", [$_birth[2], $_birth[0], $_birth[1]])));
				if ($ret['birthday'] == "0000-00-00" || $ret['birthday'] == "1970-01-01" || $ret['birthday'] == "1753-01-01") $ret['birthday'] = "1970-01-01";
				$ret['birthplace'] = $a['bcity'];
				$ret['residence'] = $a['res'];
				$height = strtolower($a['h']);
				$ret['height_imp'] = "0'0\"";
				$ret['height'] = 0;
				if ($height != "n/a") {
					$b = explode("(", $height);
					foreach ($b as $bb) {
						if (strpos($bb, " cm") !== false) {
							$ret['height'] = intval($bb);
						} else if (strpos($bb, "m") !== false) {
							$ret['height'] = preg_replace('/[^0-9\.]/', '', $bb) * 100;
						} else {
							$ret['height_imp'] = trim($bb);
						}
					}
				}
				$weight = strtolower($a['w']);
				$ret['weight_imp'] = 0;
				$ret['weight'] = 0;
				if ($weight != "n/a") {
					$b = explode("(", $weight);
					foreach ($b as $bb) {
						if (strpos($bb, "lbs") !== false) {
							$ret['weight_imp'] = intval($bb);
						} else if (strpos($bb, "k") !== false) {
							$ret['weight'] = intval($bb);
						}
					}
				}
				if (preg_match('/right/i', $a['plays'])){
					$ret['hand'] = 1;
				} else if (preg_match('/left/i', $a['plays'])){
					$ret['hand'] = 2;
				} else if (preg_match('/ambidexterious/i', $a['plays'])){
					$ret['hand'] = 3;
				} else {
					$ret['hand'] = 0;
				}
				if ($a['backhand'] > 0) {
					$ret['backhand'] = $a['backhand'];
				} else if ($ret['hand'] > 0) {
					$ret['backhand'] = 2;
				} else {
					$ret['backhand'] = 0;
				}
				preg_match('/([1-2][0-9]{3})/', $a['yr_pro'], $match);
				if (isset($match[1])) {
					$ret['turnpro'] = $match[1];
				} else {
					$ret['turnpro'] = 0;
				}
				$ret['pronoun'] = $a['pronunciation'];
				$ret['website'] = $a['website'];

				$ret['prize_c'] = intval(preg_replace('/[\$,]/', "", $a['carpz']));
				$ret['prize_y'] = intval(preg_replace('/[\$,]/', "", $a['ytdpz']));

				$ret['rank_s'] = intval($a['sglrnk']); if (!$ret['rank_s']) $ret['rank_s'] = 9999;
				$ret['rank_s_hi'] = intval($a['shirank']); if (!$ret['rank_s_hi']) $ret['rank_s_hi'] = 9999;
				$ret['rank_s_hi_date'] = $a['shirankdate']; if (strpos($ret['rank_s_hi_date'], '1753') !== false) $ret['rank_s_hi_date'] = '1970-01-01'; else $ret['rank_s_hi_date'] = date('Y-m-d', strtotime($ret['rank_s_hi_date']));
				$ret['title_s_c'] = intval($a['scartitl']);
				$ret['title_s_y'] = intval($a['sytdtitl']);

				$b = explode("-", $a['scarwl']);
				$ret['win_s_c'] = $b[0];
				$ret['lose_s_c'] = $b[1];

				$b = explode("-", $a['sytdwl']);
				$ret['win_s_y'] = $b[0];
				$ret['lose_s_y'] = $b[1];

				$ret['rank_d'] = intval($a['dblrnk']); if (!$ret['rank_d']) $ret['rank_d'] = 9999;
				$ret['rank_d_hi'] = intval($a['dhirank']); if (!$ret['rank_d_hi']) $ret['rank_d_hi'] = 9999;
				$ret['rank_d_hi_date'] = $a['dhirankdate']; if (strpos($ret['rank_d_hi_date'], '1753') !== false) $ret['rank_d_hi_date'] = '1970-01-01'; else $ret['rank_d_hi_date'] = date('Y-m-d', strtotime($ret['rank_d_hi_date']));
				$ret['title_d_c'] = intval($a['dcartitl']);
				$ret['title_d_y'] = intval($a['dytdtitl']);

				$b = explode("-", $a['dcarwl']);
				$ret['win_d_c'] = $b[0];
				$ret['lose_d_c'] = $b[1];

				$b = explode("-", $a['dytdwl']);
				$ret['win_d_y'] = $b[0];
				$ret['lose_d_y'] = $b[1];
		
				if ($redis) {
					if ($redis->cmd('KEYS', $gender . '_profile_' . $pid)->get()) {
						$redis->cmd('HMSET', $gender . '_profile_' . $pid, 
							'first',  $ret['first'],
							'last', $ret['last'],
							'ioc', $ret['ioc'],
							'birthday', $ret['birthday'],
							'birthplace', $ret['birthplace'],
							'residence', $ret['residence'],
							'height_imp', $ret['height_imp'],
							'height', $ret['height'],
							'weight_imp', $ret['weight_imp'],
							'weight', $ret['weight'],
							'hand', $ret['hand'],
							'backhand', $ret['backhand'],
							'turnpro', $ret['turnpro'],
							'pronoun', $ret['pronoun'],
							'website', $ret['website'],
							'prize_c', $ret['prize_c'],
							'prize_y', $ret['prize_y'],
							'rank_s', $ret['rank_s'],
							'rank_s_hi', $ret['rank_s_hi'],
							'rank_s_hi_date', $ret['rank_s_hi_date'],
							'title_s_c', $ret['title_s_c'],
							'title_s_y', $ret['title_s_y'],
							'win_s_c', $ret['win_s_c'],
							'lose_s_c', $ret['lose_s_c'],
							'win_s_y', $ret['win_s_y'],
							'lose_s_y', $ret['lose_s_y'],
							'rank_d', $ret['rank_d'],
							'rank_d_hi', $ret['rank_d_hi'],
							'rank_d_hi_date', $ret['rank_d_hi_date'],
							'title_d_c', $ret['title_d_c'],
							'title_d_y', $ret['title_d_y'],
							'win_d_c', $ret['win_d_c'],
							'lose_d_c', $ret['lose_d_c'],
							'win_d_y', $ret['win_d_y'],
							'lose_d_y', $ret['lose_d_y']
						)->set();
					} else {
						$redis->cmd('HMSET', $gender . '_profile_' . $pid, 
							'first',  $ret['first'],
							'last', $ret['last'],
							'ioc', $ret['ioc'],
							'birthday', $ret['birthday'],
							'birthplace', $ret['birthplace'],
							'residence', $ret['residence'],
							'height_imp', $ret['height_imp'],
							'height', $ret['height'],
							'weight_imp', $ret['weight_imp'],
							'weight', $ret['weight'],
							'hand', $ret['hand'],
							'backhand', $ret['backhand'],
							'turnpro', $ret['turnpro'],
							'pronoun', $ret['pronoun'],
							'website', $ret['website'],
							'prize_c', $ret['prize_c'],
							'prize_y', $ret['prize_y'],
							'rank_s', $ret['rank_s'],
							'rank_s_hi', $ret['rank_s_hi'],
							'rank_s_hi_date', $ret['rank_s_hi_date'],
							'title_s_c', $ret['title_s_c'],
							'title_s_y', $ret['title_s_y'],
							'win_s_c', $ret['win_s_c'],
							'lose_s_c', $ret['lose_s_c'],
							'win_s_y', $ret['win_s_y'],
							'lose_s_y', $ret['lose_s_y'],
							'rank_d', $ret['rank_d'],
							'rank_d_hi', $ret['rank_d_hi'],
							'rank_d_hi_date', $ret['rank_d_hi_date'],
							'title_d_c', $ret['title_d_c'],
							'title_d_y', $ret['title_d_y'],
							'win_d_c', $ret['win_d_c'],
							'lose_d_c', $ret['lose_d_c'],
							'win_d_y', $ret['win_d_y'],
							'lose_d_y', $ret['lose_d_y'],
							'l_en', $ret['l_en'],
							's_en', $ret['s_en']
						)->set();
					}
					fputs(STDERR, $pid . " new added\n");
				} // if redis
			} // if xml
		} // if res1

		return $ret;
	} // function down

	public function query_itfpid() {

		$pid = $first = $last = null;

		$argv = func_get_args();
		if (count($argv) == 3) {
			$gender = get_param($argv, 0, 'atp', ' atp wta ');
			$pid = get_param($argv, 1, 0);
			$redis = get_param($argv, 2, null);
		} else if (count($argv) == 4) {
			$gender = get_param($argv, 0, 'atp', ' atp wta ');
			$first = get_param($argv, 1, 'unknown');
			$last = get_param($argv, 2, 'unknown');
			$redis = get_param($argv, 3, null);
		}

		if ($pid !== null) {
			$res = $redis->cmd('HMGET', join("_", [$gender, 'profile', $pid]), 'first', 'last', 'ioc')->get();
			$first = strtolower($res[0]);
			$last = strtolower($res[1]);
			$ioc = $res[2];
		} else {
			$first = strtolower($first);
			$last = strtolower($last); 
		}

		$name = rawurlencode($first . " " . $last);
		$url = $this->itf_find_url . $name;
		print_err($url);

		$html = http($url);
		sleep(3);

		$_itfpid = null;
		$_pid = $pid;

		if (!$html || strpos($html, "ROBOTS") !== false) {
			echo join("\t", ['ERROR_DOWNLOAD', $pid, $first, $last]) . "\n";
		} else {
			if (substr($html, 0, 1) == "{") {
				$json = json_decode($html, true);
				if (!$json || !isset($json['players'])) {
					echo join("\t", ['ERROR_PARSED', $pid, $first, $last]) . "\n";
				} else {
					if (count($json['players']) == 0) {
						echo join("\t", ['NO_MATCHED', $pid, $first, $last]) . "\n";
					} else {
						$psb = [];
						$cdd = [];
						foreach ($json['players'] as $p) {
							$first1 = strtolower($p['givenName']);
							$last1 = strtolower($p['familyName']);
							$link = $p['playerProfileLink'];
							$itfpid = $p['playerId'];
							$ioc1 = $p['playerNationalityCode'];
							if ($first1 == $first && $last1 == $last) {
								$psb[] = [$itfpid, $first1, $last1, $link];
							} else {
								if ((strpos($first1, $first) !== false || strpos($first, $first1) !== false) && $last1 == $last) {
									$cdd[] = [$itfpid, $first1, $last1, $link];
								} else if ((strpos($last1, $last) !== false || strpos($last, $last1) !== false) && $first1 == $first) {
									$cdd[] = [$itfpid, $first1, $last1, $link];
								}
							}
						}

						if (count($psb) == 1) {
							echo join("\t", ['GOT', $pid, $first, $last, $psb[0][0], $psb[0][3]]) . "\n";
							$_itfpid = $psb[0][0];
						} else if (count($psb) > 1) {
							foreach ($psb as $_p) {
								echo join("\t", ['CANDIDATE', $pid, $first, $last, $_p[0], $_p[3]]) . "\n";
							}
						} else if (count($cdd) > 0) {
							foreach ($cdd as $_p) {
								echo join("\t", ['POSSIBLE', $pid, $first, $last, $_p[0], $_p[1], $_p[2], $_p[3]]) . "\n";
							}
						} else {
							echo join("\t", ['NO_MATCHED', $pid, $first, $last]) . "\n";
						}
						unset($psb);
						unset($cdd);
					}
				}
			} else if (substr($html, 0, 1) == "<") {
				$xml = simplexml_load_string($html);
				if (!$xml || !isset($xml->Players)) {
					echo join("\t", ['ERROR_PARSED', $pid, $first, $last]) . "\n";
				} else {
					$players = json_decode(json_encode($xml->Players), true);
					print_r($players);
					if (!isset($players['PlayerSummaryApiModel'])) {
						echo join("\t", ['NO_MATCHED', $pid, $first, $last]) . "\n";
					} else {
						$psb = [];
						$cdd = [];
						foreach ($players['PlayerSummaryApiModel'] as $p) {
							$first1 = strtolower($p->GivenName . '');
							$last1 = strtolower($p->FamilyName . '');
							$link = $p->PlayerProfileLink . '';
							$itfpid = $p->Id . '';
							$ioc1 = $p->PlayerNationalityCode . '';
							if ($first1 == $first && $last1 == $last) {
								$psb[] = [$itfpid, $first1, $last1, $link];
							} else {
								if ((strpos($first1, $first) !== false || strpos($first, $first1) !== false) && $last1 == $last) {
									$cdd[] = [$itfpid, $first1, $last1, $link];
								} else if ((strpos($last1, $last) !== false || strpos($last, $last1) !== false) && $first1 == $first) {
									$cdd[] = [$itfpid, $first1, $last1, $link];
								}
							}
						}

						if (count($psb) == 1) {
							echo join("\t", ['GOT', $pid, $first, $last, $psb[0][0], $psb[0][3]]) . "\n";
							$_itfpid = $psb[0][0];
						} else if (count($psb) > 1) {
							foreach ($psb as $_p) {
								echo join("\t", ['CANDIDATE', $pid, $first, $last, $_p[0], $_p[3]]) . "\n";
							}
						} else if (count($cdd) > 0) {
							foreach ($cdd as $_p) {
								echo join("\t", ['POSSIBLE', $pid, $first, $last, $_p[0], $_p[1], $_p[2], $_p[3]]) . "\n";
							}
						} else {
							echo join("\t", ['NO_MATCHED', $pid, $first, $last]) . "\n";
						}
						unset($psb);
						unset($cdd);
					}
				}

			}
		}

		if ($redis && $_itfpid !== null && $_pid !== null) {
			$redis->cmd('HSET', 'itf_redirect', $_itfpid, join("_", [$gender, 'profile', $_pid]))->set();
			if ($redis->cmd('KEYS', 'itf_profile_' . $_itfpid)->get()) {
				$keys = $redis->cmd('HKEYS', 'itf_profile_' . $_itfpid)->get();
				$vals = $redis->cmd('HVALS', 'itf_profile_' . $_itfpid)->get();
				for ($i = 0; $i < count($keys); ++$i){
					$redis->cmd('HSET', join("_", [$gender, 'profile', $_pid]), $keys[$i], $vals[$i])->set();
				}
				$redis->cmd('DEL', 'itf_profile_' . $_itfpid)->set();
			}
		}
	}

	public function query_wtpid() { // 从名字查找wt pid，如果有itfpid，就链接上

		$pid = $first = $last = null;

		$argv = func_get_args();
		if (count($argv) > 3) {
			$gender = get_param($argv, 0, 'atp', ' atp wta ');
			$first = get_param($argv, 1, 'unknown');
			$last = get_param($argv, 2, 'unknown');
			$redis = get_param($argv, 3, null);
			$itfpid = get_param($argv, 4, null);
		} else {
			return null;
		}

		$_first = preg_replace('/[^a-z]/', '', strtolower($first));
		$_last = preg_replace('/[^a-z]/', '', strtolower($last)); 
		$name = join("-", [str_replace(' ', '-', strtolower($first)), str_replace(' ', '-', strtolower($last))]);

		$accurate = []; // 记录完全匹配的
		$possible = []; // 记录包含匹配的

		if ($gender == "atp") {
			$url = $this->atp_find_url . $name;
			$html = http($url);
			if (!$html) {
				fputs(STDERR, join("\t", ["ITFPID DOWN ERR", $itfpid, $gender, $first, $last]) . "\n");
				return null;
			} else {
				$json = json_decode($html, true);
				if (!$json || count($json['items']) == 0) {
					fputs(STDERR, join("\t", ["ITFPID NOTFOUND", $itfpid, $gender, $first, $last]) . "\n");
					return null;
				}
				foreach ($json['items'] as $item) {
					$f = preg_replace('/[^a-z]/', '', strtolower($item['FirstName']));
					$l = preg_replace('/[^a-z]/', '', strtolower($item['LastName']));
					$pid = strtoupper(explode("/", $item['ProfileUrl'])[4]);
					if ($_first == $f && $_last == $l) {
						if (!in_array($pid, $accurate)) {
							$accurate[] = $pid;
						}
					} else if ((strpos($_first, $f) !== false || strpos($f, $_first) !== false) && (strpos($_last, $l) !== false || strpos($l, $_last) !== false)) {
						$possible[] = [$pid, $f, $l];
					}
				}
			}
		} else {
			$url = $this->wta_find_url . $name;
			$html = http($url);
			if (!$html) {
				fputs(STDERR, join("\t", ["ITFPID DOWN ERR", $itfpid, $gender, $first, $last]) . "\n");
				return null;
			} else {
				$json = json_decode($html, true);
				if (!$json || $json['hits']['found'] == 0) {
					fputs(STDERR, join("\t", ["ITFPID NOTFOUND", $itfpid, $gender, $first, $last]) . "\n");
					return null;
				}
				foreach ($json['hits']['hit'] as $item) {
					$pid = $item['response']['id'];
					$f = preg_replace('/[^a-z]/', '', strtolower($item['response']['firstName']));
					$l = preg_replace('/[^a-z]/', '', strtolower($item['response']['lastName']));
					if ($_first == $f && $_last == $l) {
						if (!in_array($pid, $accurate)) {
							$accurate[] = $pid;
						}
					} else if ((strpos($_first, $f) !== false || strpos($f, $_first) !== false) && (strpos($_last, $l) !== false || strpos($l, $_last) !== false)) {
						$possible[] = [$pid, $f, $l];
					}
				}
			}
		}

		if (count($accurate) == 1) {
			$pid = $accurate[0];
			if ($redis) {
				if (!$redis->cmd('KEYS', $gender . '_profile_' . $pid)->get()) {
					$this->down_bio($pid, $gender, $redis);
				}
				if ($itfpid !== null) {
					$redis->cmd('HSET', 'itf_redirect', $itfpid, join("_", [$gender, 'profile', $pid]))->set();
					if ($redis->cmd('KEYS', 'itf_profile_' . $itfpid)->get()) {
						$keys = $redis->cmd('HKEYS', 'itf_profile_' . $itfpid)->get();
						$vals = $redis->cmd('HVALS', 'itf_profile_' . $itfpid)->get();
						for ($i = 0; $i < count($keys); ++$i){
							if ($keys[$i] == 'update_time') continue;
							$redis->cmd('HSET', join("_", [$gender, 'profile', $pid]), $keys[$i], $vals[$i])->set();
						}
						$redis->cmd('DEL', 'itf_profile_' . $itfpid)->set();
					}
					fputs(STDERR, join("\t", ["ITFPID  SUCCESS", $itfpid, $gender, $first, $last, $pid]) . "\n");
				}
			}
			return $pid;
		} else if (count($accurate) > 1) {
			foreach ($accurate as $pid) {
				if ($redis) {
					if (!$redis->cmd('KEYS', $gender . '_profile_' . $pid)->get()) {
						$this->down_bio($pid, $gender, $redis);
					}
				}
				fputs(STDERR, join("\t", ["ITFPID POSSIBLE", $itfpid, $gender, $first, $last, $pid]) . "\n");
			}
			return null;
		} else if (count($possible) > 0) {
			foreach ($possible as $it) {
				$pid = $it[0];
				$f = $it[1];
				$l = $it[2];
				if ($redis) {
					if (!$redis->cmd('KEYS', $gender . '_profile_' . $pid)->get()) {
						$this->down_bio($pid, $gender, $redis);
					}
				}
				fputs(STDERR, join("\t", ["ITFPID  SIMILAR", $itfpid, $gender, $first, $last, $pid, $f, $l]) . "\n");
			}
			return null;
		} else {
			fputs(STDERR, join("\t", ["ITFPID NOTFOUND", $itfpid, $gender, $first, $last]) . "\n");
		}

		return null;
	}

	public function rename2short($first, $last, $ioc) {
		if (strpos(strtolower($first . $last), "bye") !== false) return 'Bye';
		if (strpos(strtolower($first . $last), "qualifier") !== false) return 'Qualifier';
			  
		$last = replace_letters($last);
		$first = replace_letters($first);

		if ($ioc == "CHN") {
			$first = self::first_name_shortenlize($first);
			if (strpos($first, " ") !== false) {
				$first = ucwords(strtolower(preg_replace('/[ \.\'-]/', ' ', $first)));
				$first = trim(preg_replace('/[^A-Z]/', '', $first));
			}
		} else {
			$first = ucwords(strtolower(preg_replace('/[ \.\'-]/', ' ', $first)));
			$first = trim(preg_replace('/[^A-Z]/', '', $first));
		}

		if (in_array($ioc, ["CHN", "PRK", "KOR", "VIE", "TPE", "HKG", 'MAC'])) {
			return $last . ' ' . $first;
		} else {
			return $first . ' ' . $last;
		}     
	}

	public function rename2long($first, $last, $ioc) {
		if (strpos(strtolower($first . $last), "bye") !== false) return 'Bye';
		if (strpos(strtolower($first . $last), "qualifier") !== false) return 'Qualifier';
			  
		$last = replace_letters($last);
		$first = replace_letters($first);

		if ($ioc == "CHN") {
			$first = self::first_name_shortenlize($first);
		}

		$last = strtoupper($last);

		if (in_array($ioc, ["CHN", "PRK", "KOR", "VIE", "TPE", "HKG", 'MAC'])) {
			return $last . ' ' . $first;
		} else {
			return $first . ' ' . $last;
		}
	}

	public function first_name_shortenlize($first) { 
		$first = preg_replace('/([A-Z])/', ' \1', $first);  
		$first = preg_replace('/[^a-z ]/', ' ', strtolower($first));
		$first = preg_replace('/([bpmfdtlkjqxzcsyw])/', ' \1', $first); 
		$first = preg_replace('/n([aioeuv])/', ' n\1', $first); 
		$first = preg_replace('/([^n])g/', '\1 g', $first); 
		$first = preg_replace('/([^zcs])h/', '\1 h', $first);   
		$first = preg_replace('/([^ejs])r/', '\1 r', $first);   
		$first = preg_replace('/  */', ' ', trim($first));  
		return ucwords($first);              
	} 

}
