<?php

namespace App\Http\Controllers\H2H;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Config;
use App;
use DB;

class H2HController extends Controller
{
	public function index($lang) {

		App::setLocale($lang);

		$view = 'h2h.index';
		if (is_test_account()) $view = 'h2h.index_new';

		return view($view, [
			'pageTitle' => __('frame.menu.h2h'),
			'title' => __('frame.menu.h2h'),
			'pagetype1' => 'h2h',
			'pagetype2' => 'index',
		]);
	}

	public function query(Request $req, $lang) {

		App::setLocale($lang);

		$ret = [];

		$status = $req->input('status', 'wrongP1');
		$ajax = $req->input('ajax', false);

		if ($status != 'ok') {

			$ret['status'] = -1;

			$ret['errmsg'] = __('h2h.warning.' . $status);

		} else {

			$ret['status'] = 0;

			$ret['filter'] = '';

			$type = $req->input('type', 'atp');
//			$file = join('/', [Config::get('const.root'), $type, "all_h2h_3"]);
//			$file = join('/', [Config::get('const.root'), 'store', 'h2h', $type . "_detail"]);
			$file = join('/', [Config::get('const.root'), 'data', 'h2h', $type . "_detail"]);

			$p1 = $req->input('p1id');
			$p2 = $req->input('p2id');
			$method = $req->input('method', 'p');
			$ret['method'] = $method;
			$sd = $req->input('sd', 's');

			if ($sd == "s") {
				if ($method == 'p' || $method == 'm') {
					$basic_exp = "(^S\t($p1)\t\t($p2)\t\t)|(^S\t($p2)\t\t($p1)\t\t)";
				} else if ($method == 'c') {
					$basic_exp = "^S\t([^\t]+\t\t)?($p1)\t.+\t$p2\t.+$";
				} else if ($method == 't') {
					$basic_exp = "^S\t([^\t]+\t\t)?($p1)\t";
				}
			} else {
				if ($method == 'p' || $method == 'm') {
					if (strpos($p1, "/") === false && strpos($p2, "/") === false) {
						$basic_exp = "(^D\t$p1\t[^\t]+\t$p2\t[^\t]+\t)|(^D\t[^\t]+\t$p1\t$p2\t[^\t]+\t)|(^D\t$p1\t[^\t]+\t[^\t]+\t$p2\t)|(^D\t[^\t]+\t$p1\t[^\t]+\t$p2\t)|(^D\t$p2\t[^\t]+\t$p1\t[^\t]+\t)|(^D\t[^\t]+\t$p2\t$p1\t[^\t]+\t)|(^D\t$p2\t[^\t]+\t[^\t]+\t$p1\t)|(^D\t[^\t]+\t$p2\t[^\t]+\t$p1\t)";
					} else {
						$ar = explode("/", $p1);
						if (count($ar) == 2) {
							if ($ar[0] > $ar[1]) swap($ar[0], $ar[1]);
						}
						$p11 = $ar[0];
						$p12 = @$ar[1];

						$ar = explode("/", $p2);
						if (count($ar) == 2) {
							if ($ar[0] > $ar[1]) swap($ar[0], $ar[1]);
						}
						$p21 = $ar[0];
						$p22 = @$ar[1];

						$basic_exp = "(^D\t$p11\t$p12\t$p21\t$p22\t)|(^D\t$p21\t$p22\t$p11\t$p12\t)";
					}

				} else if ($method == 'c') {
					$basic_exp = "^D.*\t($p1)\t.+\t$p2\t.+$";
				} else if ($method == 't') {
					$basic_exp = "^D.*\t($p1)\t";
				}
			}

			$cmd = "grep -E '$basic_exp' $file";

			if ($method == 'c') {
				if ($sd == "s") {
					$cmd .= " | awk -F\"\\t\" '($2 == \"$p1\" && $16 == \"$p2\") || ($4 == \"$p1\" && $14 == \"$p2\")' ";
				} else {
					$cmd .= " | awk -F\"\\t\" '($2 == \"$p1\" && $16 == \"$p2\") || ($3 == \"$p1\" && $16 == \"$p2\") || ($2 == \"$p1\" && $17 == \"$p2\") || ($3 == \"$p1\" && $17 == \"$p2\") || ($4 == \"$p1\" && $14 == \"$p2\") || ($5 == \"$p1\" && $14 == \"$p2\") || ($4 == \"$p1\" && $15 == \"$p2\") || ($5 == \"$p1\" && $15 == \"$p2\")' ";
				}
			} else if ($method == 't') {
				$cmd .=  " | awk -F\"\\t\" '(\" " . str_replace("|", " ", $p1) . " \" ~ \" \"$2\" \"  && $29 <= $p2 && $29 != \"\" && $29 != \"-\" && $29 != \"0\") || (\" " . str_replace("|", " ", $p1) . " \" ~ \" \"$4\" \" && $27 <= $p2 && $27 != \"\" && $27 != \"-\" && $27 != \"0\")' ";
			}

			$level = $req->input('level', 'a');
			$sfc = $req->input('surface', 'a');
			$md = $req->input('onlyMD', '');
			$final = $req->input('onlyFinal', '');

			if ($sd == "d") {
				$ret['filter'] .= "\t" . __('h2h.selectBar.sd.d');
			}

			if ($md == 'y') {
				$cmd .= " | awk -F\"\\t\" '$21 !~ /^Q[1-9R-]/' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.md.y');
			}

			if ($final == 'y') {
				$cmd .= " | awk -F\"\\t\" '$21 == \"F\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.final.y');
			}

			if ($level == 'g') {
				$cmd .= " | awk -F\"\\t\" '$24 == \"GS\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.g');
			} else if ($level == 'm') {
				$cmd .= " | awk -F\"\\t\" '$24 == \"1000\" || $24 == \"PM\" || $24 == \"P5\" || $24 == \"SU\" || $24 == \"T1\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.m');
			} else if ($level == 't') {
				$cmd .= " | awk -F\"\\t\" '$24 != \"ITF\" && $24 != \"FU\" && $24 != \"CH\" && $24 != \"125K\"' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.level.t');
			}

			if ($sfc == 'h') {
				$cmd .= " | awk -F\"\\t\" '$26 ~ /^Hard/' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.sfc.h');
			} else if ($sfc == 'c') {
				$cmd .= " | awk -F\"\\t\" '$26 ~ /^Clay/' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.sfc.c');
			} else if ($sfc == 'g') {
				$cmd .= " | awk -F\"\\t\" '$26 ~ /^Grass/' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.sfc.g');
			} else if ($sfc == 'p') {
				$cmd .= " | awk -F\"\\t\" '$26 ~ /^Carpet/' ";
				$ret['filter'] .= "\t" . __('h2h.selectBar.sfc.p');
			}

			$ret['filter'] = trim($ret['filter']);
			if ($ret['filter']) {
				$ret['filter'] = __('h2h.selectBar.filter.desc') . str_replace("\t", __('h2h.selectBar.filter.comma'), $ret['filter']);
			}

			$cmd .= " | sort -k20r,20 -t$'\t' ";

			unset($r); exec($cmd, $r);

			$ret['win'] = $ret['lose'] = 0;
			$ret['matches'] = [];

			if ($r) {
				foreach ($r as $row) {
					$arr = explode("\t", $row);
					$winnerId = $arr[1] . ($arr[2] !== "" ? "/" . $arr[2] : "");
					$loserId = $arr[3] . ($arr[4] !== "" ? "/" . $arr[4] : "");
					$winner = rename2short($arr[5], $arr[9], $arr[13]);
					$loser = rename2short($arr[7], $arr[11], $arr[15]);
					$rank1 = self::reviseRank(@$arr[26]);
					$rank2 = self::reviseRank(@$arr[28]);
					$W = $rank1 . $winner;
					$L = $rank2 . $loser;

					if ($sd == "d") {
						$winner = rename2short($arr[6], $arr[10], $arr[14]);
						$loser = rename2short($arr[8], $arr[12], $arr[16]);
						$rank1 = self::reviseRank(@$arr[27]);
						$rank2 = self::reviseRank(@$arr[29]);
						$W .= "/" . $rank1 . $winner;
						$L .= "/" . $rank2 . $loser;
					}
						
					$round = $arr[20];
					$sortBase = $arr[19] * 100 + Config::get('const.round2id.' . $round);
					$round = translate('roundname', $round);
					$tour = strtoupper(translate_tour(replace_letters(mb_strtolower($arr[22]))));
					$year = $arr[18];
					$grade = $arr[23];
					$ground = __('frame.ground.' . reviseSurfaceWithIndoor($arr[25]));
					$game = $arr[17];
					if (strpos($game, 'W/O') !== false || strpos($game, 'w/o') !== false || strpos($game, 'w.o') !== false || strpos($game, 'W.O') !== false) {
						$game = 'W/O';
						$color = 'Third';
					} else {
						if (strpos($p1, $winnerId) !== false || strpos($winnerId, $p1) !== false || (isset($p11) && strpos($winnerId, $p11) !== false) || (isset($p12) && strpos($winnerId, $p12) !== false)) {
							++$ret['win'];
							$color = 'Home';
						} else {
							++$ret['lose'];
							$color = 'Away';
						}
					}
					$ret['matches'][] = [$year, $grade, $ground, $tour, $round, $W . " d. " . $L, $game, $color, $sortBase];
				}
			}

			usort($ret['matches'], function ($a, $b) {
				return $a[8] > $b[8] ? -1 : 1;
			});

			$ret['double'] = false;

			// 获取头像。优先找双方大头像，如果有一方没有大头像，则找双方小头像，如果有一方没有小头像，则回到大头像
			if ($method == 'p' || $method == 'm') {
				if (strpos($p1, "/") !== false || strpos($p2, "/") !== false) {
					$ret['p1head'] = $ret['p2head'] = "";
					$ret['size'] = 'Portrait';
					$ret['double'] = true;
				} else {
					$pt1 = fetch_player_image($type, $p1, "portrait", false);
					$pt2 = fetch_player_image($type, $p2, "portrait", false);
					$ptd = fetch_player_image($type, "", "portrait", true);
					$hs1 = fetch_player_image($type, $p1, "headshot", false);
					$hs2 = fetch_player_image($type, $p2, "headshot", false);
					$hsd = fetch_player_image($type, "", "headshot", true);

					if ($pt1 && $pt2) {
						$ret['p1head'] = $pt1?$pt1:$ptd;
						$ret['p2head'] = $pt2?$pt2:$ptd;
						$ret['size'] = 'Portrait';
					} else if ($pt1 || $pt2) {
						if ($hs1 && $hs2) {
							$ret['p1head'] = $hs1?$hs1:$hsd; 
							$ret['p2head'] = $hs2?$hs2:$hsd;
							$ret['size'] = 'Headshot';
						} else {
							$ret['p1head'] = $pt1?$pt1:$ptd;
							$ret['p2head'] = $pt2?$pt2:$ptd;
							$ret['size'] = 'Portrait';
						}
					} else {
						if ($hs1 || $hs2) {
							$ret['p1head'] = $hs1?$hs1:$hsd;
							$ret['p2head'] = $hs2?$hs2:$hsd;
							$ret['size'] = 'Headshot';
						} else {
							$ret['p1head'] = $pt1?$pt1:$ptd;
							$ret['p2head'] = $pt2?$pt2:$ptd;
							$ret['size'] = 'Portrait';
						}
					}
				}
			} else if ($method == 'c') {
				$pt1 = fetch_player_image($type, $p1, "portrait", false);
				$ptd = fetch_player_image($type, "", "portrait", true);
				$hs1 = fetch_player_image($type, $p1, "headshot", false);
				$hsd = fetch_player_image($type, "", "headshot", true);
				$pt2 = get_flag_url($p2);

				if (!$pt1 && $hs1) {
					$ret['p1head'] = $hs1; 
					$ret['p2head'] = $pt2;
					$ret['size'] = 'Headshot';
				} else {
					$ret['p1head'] = $pt1?$pt1:$ptd; 
					$ret['p2head'] = $pt2;
					$ret['size'] = 'Portrait';
				}
			} else if ($method == 't') {
				$pt1 = fetch_player_image($type, $p1, "portrait", false);
				$ptd = fetch_player_image($type, "", "portrait", true);
				$hs1 = fetch_player_image($type, $p1, "headshot", false);
				$hsd = fetch_player_image($type, "", "headshot", true);

				if (!$pt1 && $hs1) {
					$ret['p1head'] = $hs1; 
					$ret['p2head'] = $hsd;
					$ret['size'] = 'Headshot';
				} else {
					$ret['p1head'] = $pt1?$pt1:$ptd; 
					$ret['p2head'] = $ptd;
					$ret['size'] = 'Portrait';
				}
			} else {
				$ret['p1head'] = ''; 
				$ret['p2head'] = '';
				$ret['size'] = 'Portrait';
			}

			if (strpos($p1, "|") !== false) $ret['multi1'] = true; else $ret['multi1'] = false;
			if (strpos($p2, "|") !== false) $ret['multi2'] = true; else $ret['multi2'] = false;

			if ($req->input('p1') && $req->input('p2')) {

				$name1 = urldecode($req->input('p1'));
				$name2 = urldecode($req->input('p2'));

			} else {

				$tbname = "all_name_" . $type;

				if (strpos($p1, "/") !== false || strpos($p1, "|") === false) {
					$arr = explode("/", $p1);
					foreach ($arr as $k => $v) {
						$row = DB::table($tbname)->where('id', $v)->first();
						if ($row) {
							$arr[$k] = rename2short($row->first, $row->last, $row->nat);
						} else {
							$arr[$k] = '';
						}
					}
					$name1 = "<br>" . join("<br>", $arr);
				} else {
					$arr = explode('|', $p1);
					$rows = DB::table($tbname)->whereIn('id', $arr)->get();

					$name1 = "";
					foreach ($rows as $row) {
						$name1 .= rename2short($row->first, $row->last, $row->nat) . "<br>";
					}
				}

				if ($method == 'p' || $method == 'm') {
					if (strpos($p2, "/") !== false || strpos($p2, "|") === false) {
						$arr = explode("/", $p2);
						foreach ($arr as $k => $v) {
							$row = DB::table($tbname)->where('id', $v)->first();
							if ($row) {
								$arr[$k] = rename2short($row->first, $row->last, $row->nat);
							} else {
								$arr[$k] = '';
							}
						}
						$name2 = "<br>" . join("<br>", $arr);
					} else {
						$arr = explode('|', $p2);
						$rows = DB::table($tbname)->whereIn('id', $arr)->get();

						$name2 = "";
						foreach ($rows as $row) {
							$name2 .= rename2short($row->first, $row->last, $row->nat) . "<br>";
						}
					}
				} else if ($method == 'c') {
					$name2 = "<br>" . translate('nationname', $p2);
				} else if ($method == 't') {
					$name2 = "<br>" . "Top $p2";
				} else {
					$name2 = '';
				}

			}
			$ret['name1'] = $name1;
			$ret['name2'] = $name2;

			$cmd = "grep '^$p1\t' " . join('/', [Config::get('const.root'), $type, "player_rank"]) . " | cut -f3 | head -1";
			unset($r); exec($cmd, $r);
			if ($r && isset($r[0])) {
				$ret['rank1'] = $r[0];
			} else {
				$ret['rank1'] = "";
			}

			if ($method == 'p' || $method == 'm') {
				$cmd = "grep '^$p2\t' " . join('/', [Config::get('const.root'), $type, "player_rank"]) . " | cut -f3 | head -1";
				unset($r); exec($cmd, $r);
				if ($r && isset($r[0])) {
					$ret['rank2'] = $r[0];
				} else {
					$ret['rank2'] = "";
				}
			} else {
				$ret['rank2'] = "";
			}

			$one = DB::table('profile_' . $type)->where('longid', $p1)->first();
			if ($one) {
				$ret['ioc1'] = $one->nation3;
			} else {
				$ret['ioc1'] = "";
			}
			$one = DB::table('profile_' . $type)->where('longid', $p2)->first();
			if ($one) {
				$ret['ioc2'] = $one->nation3;
			} else {
				$ret['ioc2'] = "";
			}

		}

		if (is_test_account()) {
//			return view('h2h.query_new', ['ret' => $ret]);
		}

		if ($ajax) {
			return $ret;
		} else {
			return view('h2h.query', ['ret' => $ret]);
		}
	}

	protected function renameTo5($oriname) {

		$arr = explode(",", $oriname);
		if (count($arr) != 2) {
			return $oriname;
		} else {
			return $arr[1] . substr($arr[0], 0, 5); 
		}                                    

	}

	protected function reviseRank($rank) {
		if ($rank == "" || $rank == "-") return '';
		else if ($rank >= 1 && $rank < 9999) return '(' . $rank . ') ';
		else return '';
	}

}
