<?php


class Bio {

	protected $bio_url = "http://ws.protennislive.com/LiveScoreSystem/F/Long/GetBioFullCrypt.aspx?p=";
	protected $itf_find_url = "https://www.itftennis.com/Umbraco/Api/PlayerApi/GetPlayerSearch?searchString=";

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
				$ret['birthday'] = date('Y-m-d', strtotime(join("-", array_reverse(explode("/", str_replace(")", "", preg_replace('/^.*\(/', '', $a['age'])))))));
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
		
				print_r($ret);

				if ($redis) {
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

		$html = http($url);
		sleep(3);

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
	}

}

