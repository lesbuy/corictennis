<?php

namespace App\Http\Controllers\History;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App;
use Config;

class GSRecordController extends Controller
{
    //
	private $tour2name = [
		'0404' => 'Indian Wells',
		'0609' => 'Indian Wells',
		'0403' => 'Miami',
		'0902' => 'Miami',
		'0410' => 'Monte Carlo',
		'1536' => 'Madrid',
		'1038' => 'Madrid',
		'0416' => 'Rome',
		'0709' => 'Rome',
		'0422' => 'Cincinnati',
		'1017' => 'Cincinnati',
		'0421' => 'Rogers Cup',
		'0806' => 'Rogers Cup',
		'5014' => 'Shanghai',
		'0352' => 'Paris',
		'0357' => 'Stuttgart',
		'0430' => 'Essen',
		'0429' => 'Stockholm',
		'0414' => 'Hamburg',
		'1003' => 'Doha',
		'0718' => 'Dubai',
		'1075' => 'Wuhan',
		'1020' => 'Beijing',
		'1056' => 'Tokyo',
		'0730' => 'Moscow',
		'0804' => 'Charleston',
		'10642' => 'Berlin',
		'10342' => 'San Diego',
		'10542' => 'Zurich',
		'10468' => 'Philadelphia',
		'10735' => 'Boca Raton',
		'10650' => 'Chicago',
	];

	private $t2020_atp = ['0422', '0416', '0352'];
	private $t2011_atp = ['0404', '0403', '0410', '1536', '0416', '0421', '0422', '5014', '0352'];
	private $t2009_atp = ['0404', '0403', '0410', '0416', '1536', '0421', '0422', '5014', '0352'];
	private $t2002_atp = ['0404', '0403', '0410', '0416', '0414', '0421', '0422', '1536', '0352'];
	private $t2000_atp = ['0404', '0403', '0410', '0416', '0414', '0421', '0422', '0357', '0352'];
	private $t1996_atp = ['0404', '0403', '0410', '0414', '0416', '0421', '0422', '0357', '0352'];
	private $t1995_atp = ['0404', '0403', '0410', '0414', '0416', '0421', '0422', '0430', '0352'];
	private $t1990_atp = ['0404', '0403', '0410', '0414', '0416', '0421', '0422', '0429', '0352'];

	private $t2020_wta = ['1003', '1017', '0709'];
	private $t2019_wta = ['0718', '0609', '0902', '1038', '0709', '0806', '1017', '1075', '1020'];
	private $t2018_wta = ['1003', '0609', '0902', '1038', '0709', '0806', '1017', '1075', '1020'];
	private $t2017_wta = ['0718', '0609', '0902', '1038', '0709', '0806', '1017', '1075', '1020'];
	private $t2016_wta = ['1003', '0609', '0902', '1038', '0709', '0806', '1017', '1075', '1020'];
	private $t2015_wta = ['0718', '0609', '0902', '1038', '0709', '0806', '1017', '1075', '1020'];
	private $t2014_wta = ['1003', '0609', '0902', '1038', '0709', '0806', '1017', '1075', '1020'];
	private $t2012_wta = ['1003', '0609', '0902', '1038', '0709', '0806', '1017', '1056', '1020'];
	private $t2011_wta = ['0718', '0609', '0902', '1038', '0709', '0806', '1017', '1056', '1020'];
	private $t2009_wta = ['0718', '0609', '0902', '0709', '1038', '0806', '1017', '1056', '1020'];
	private $t2008_wta = ['1003', '0609', '0902', '0804', '10642', '0709', '0806', '1056', '0730'];
	private $t2004_wta = ['1056', '0609', '0902', '0804', '10642', '0709', '10342', '0806', '0730', '10542'];
	private $t2001_wta = ['1056', '0609', '0902', '0804', '10642', '0709', '0806', '0730', '10542'];
	private $t2000_wta = ['1056', '0609', '0902', '0804', '10642', '0709', '0806', '10542', '0730'];
	private $t1997_wta = ['1056', '0609', '0902', '0804', '0709', '10642', '0806', '10542', '0730'];
	private $t1996_wta = ['1056', '0902', '0804', '0709', '10642', '0806', '10542'];
	private $t1993_wta = ['1056', '0902', '0804', '0709', '10642', '0806', '10542', '10468'];
	private $t1991_wta = ['10735', '0902', '0804', '0709', '10642', '0806'];
	private $t1990_wta = ['10650', '0902', '0804', '0709', '10642', '0806'];

	public function index($lang) {

		App::setLocale($lang);

		return view('gs.index', [
			'pageTitle' => __('frame.menu.tourquery'),
			'title' => __('frame.menu.tourquery'),
			'pagetype1' => 'gst1',
			'pagetype2' => 'index',
		]);
	}

	public function gender(Request $req, $lang, $sex, $round) {

		App::setLocale($lang);
		if (!in_array($round, ['W', 'F', 'SF', 'QF'])) exit;

		$file = join('/', [Config::get('const.root'), 'store', 'draw', '*', '[ARWU][OGC]']);
		$cmd = "awk -F\"\\t\" 'BEGIN{a[\"AO\"]=1;a[\"RG\"]=2;a[\"WC\"]=3;a[\"UO\"]=4} $1 == \"" . $sex . "\" && $3 == \"" . ($round == 'W' ? 'F' : $round) . "\"{eid=a[substr(FILENAME, length(FILENAME)-1)];year=substr(FILENAME, length(FILENAME)-6,4); print year\"\\t\"eid\"\\t\"$0}' " . $file . " | sort -k1g,1 -k2g,2";
		unset($r); exec($cmd, $r);

		$ret = [];
		$counts = [];
		if ($r) {

			$eids = ["", "AO", "RG", "WC", "UO"];
			foreach ($r as $row) {

				$kvmap = [];
				$row_arr = explode("\t", $row);
				$year = $row_arr[0];
				$eid = $eids[$row_arr[1]];

				foreach (Config::get('const.schema_drawsheet') as $k => $v) {
					$kvmap[$v] = @$row_arr[$k + 2];
				}

				$score1 = $kvmap['score1'];
				$score2 = $kvmap['score2'];
				$status = $kvmap['mStatus'];
//				$score = revise_gs_score($status, $score1, $score2);

				$P1A = $P1B = $P2A = $P2B = NULL;
				$P1 = $P2 = [];

				foreach ([1, 2] as $i) {
					if ($round == "W" && ((in_array($status, ['F', 'H', 'J', 'L', '', '', 'A', 'B', 'C']) && $i == 2) || (in_array($status, ['G', 'I', 'K', 'M', '', '', 'A', 'B', 'C']) && $i == 1))) continue;
					foreach (['A', 'B'] as $j) {
						if ($kvmap['P'.$i.$j] !== "") {
							$counts[$kvmap['P'.$i.$j]] = @$counts[$kvmap['P'.$i.$j]] + 1;
							${'P'.$i.$j} = get_flag($kvmap['P'.$i.$j.'Nation']) . rename2long($kvmap['P'.$i.$j.'First'], $kvmap['P'.$i.$j.'Last'], $kvmap['P'.$i.$j.'Nation']) . " " . $counts[$kvmap['P'.$i.$j]] . "";
							${'P'.$i}[] = ${'P'.$i.$j};
							$id_name_map[$kvmap['P'.$i.$j]] = get_flag($kvmap['P'.$i.$j.'Nation']) . rename2short($kvmap['P'.$i.$j.'First'], $kvmap['P'.$i.$j.'Last'], $kvmap['P'.$i.$j.'Nation']);
						}
					}
					$is_win = "";
					if ($round != "W") {
						if ((in_array($status, ['F', 'H', 'J', 'L']) && $i == 1) || (in_array($status, ['G', 'I', 'K', 'M']) && $i == 2)) {
							$is_win = "<span class=DotWin></span>";
						} else if (in_array($status, ['F', 'H', 'J', 'L', 'G', 'I', 'K', 'M'])) {
							$is_win = "<span class=DotLose></span>";
						}
					}
					$pid = preg_replace('/wta0*/', "", str_replace("itf", "", str_replace("atp", "", $kvmap['P' . $i . 'A'])));
					$altname = $kvmap['P' . $i . 'AFirst'] . " " . $kvmap['P' . $i . 'ALast'];
					${'P'.$i} = join("<br>", array_map(function ($d) use ($is_win) { return $is_win . " " . $d; }, ${'P'.$i}));
					$ret[$year][$eid][] = [${'P'.$i}, $pid, $altname];
				}

			}
		}

		krsort($ret);

		$count = [];
		foreach ($counts as $person => $number) {
			$count[$number][] = $id_name_map[$person];
		}
		krsort($count);
		return view('gs.gender', [
			'ret' => $ret,
			'sex' => $sex,
			'counts' => $count,
		]);

	}

	public function t1gender(Request $req, $lang, $sex, $round) {

		App::setLocale($lang);
		if (!in_array($round, ['W', 'F', 'SF', 'QF'])) exit;
		if (!in_array($sex, ['WS', 'MS', 'MD', 'WD'])) exit;

		if (in_array($sex, ['WS', 'WD'])) {
			$cmd = "awk -F\"\\t\" '$1 ~ /PM/ || $1 ~ /P5/ || $1 ~ /T1/{print $5\"\\t\"$2}' " . join('/', [Config::get('const.root'), 'store', 'calendar', '*', 'WT']);
		} else {
			$cmd = "awk -F\"\\t\" '$1 == \"1000\" || $1 ~ /MS/ || $1 ~ /CSS/{print $5\"\\t\"$2}' " . join('/', [Config::get('const.root'), 'store', 'calendar', '*', 'WT']);
		}
		unset($tour_r); exec($cmd, $tour_r);

		$ret = [];
		$counts = [];
		foreach ($tour_r as $tour_row) {
			$tour_arr = explode("\t", $tour_row);
			$year = $tour_arr[0];
			$eid = $tour_arr[1];
			$seq = self::get_seq($year, $sex, $eid);

			$file = join('/', [Config::get('const.root'), 'store', 'draw', $year, $eid]);
			$cmd = "awk -F\"\\t\" '$1 == \"" . $sex . "\" && $3 == \"" . ($round == 'W' ? 'F' : $round) . "\"' " . $file . " | sort -k1g,1 -k2g,2";
			unset($r); exec($cmd, $r);

			if ($r) {

				foreach ($r as $row) {

					$kvmap = [];
					$row_arr = explode("\t", $row);

					foreach (Config::get('const.schema_drawsheet') as $k => $v) {
						$kvmap[$v] = @$row_arr[$k];
					}

					$score1 = $kvmap['score1'];
					$score2 = $kvmap['score2'];
					$status = $kvmap['mStatus'];
	//				$score = revise_gs_score($status, $score1, $score2);

					$P1A = $P1B = $P2A = $P2B = NULL;
					$P1 = $P2 = [];

					foreach ([1, 2] as $i) {
						if ($round == "W" && ((in_array($status, ['F', 'H', 'J', 'L', '', 'A', 'B', 'C']) && $i == 2) || (in_array($status, ['G', 'I', 'K', 'M', '', 'A', 'B', 'C']) && $i == 1))) continue;
						foreach (['A', 'B'] as $j) {
							if ($kvmap['P'.$i.$j] !== "") {
								$counts[$kvmap['P'.$i.$j]] = @$counts[$kvmap['P'.$i.$j]] + 1;
								${'P'.$i.$j} = get_flag($kvmap['P'.$i.$j.'Nation']) . rename2short($kvmap['P'.$i.$j.'First'], $kvmap['P'.$i.$j.'Last'], $kvmap['P'.$i.$j.'Nation']) . " " . $counts[$kvmap['P'.$i.$j]] . "";
								${'P'.$i}[] = ${'P'.$i.$j};
								$id_name_map[$kvmap['P'.$i.$j]] = get_flag($kvmap['P'.$i.$j.'Nation']) . rename2short($kvmap['P'.$i.$j.'First'], $kvmap['P'.$i.$j.'Last'], $kvmap['P'.$i.$j.'Nation']);
							}
						}
						$is_win = "";
						if ($round != "W") {
							if ((in_array($status, ['F', 'H', 'J', 'L']) && $i == 1) || (in_array($status, ['G', 'I', 'K', 'M']) && $i == 2)) {
								$is_win = "<span class=DotWin></span>";
							} else if (in_array($status, ['F', 'H', 'J', 'L', 'G', 'I', 'K', 'M'])) {
								$is_win = "<span class=DotLose></span>";
							}
						}
						$pid = preg_replace('/wta0*/', "", str_replace("itf", "", str_replace("atp", "", $kvmap['P' . $i . 'A'])));
						$altname = $kvmap['P' . $i . 'AFirst'] . " " . $kvmap['P' . $i . 'ALast'];
						${'P'.$i} = join("<br>", array_map(function ($d) use ($is_win) { return $is_win . " " . $d; }, ${'P'.$i}));
						$ret[$year][$seq][] = [${'P'.$i}, $pid, $altname, $eid];
					}

				}
			}
		}

		krsort($ret);

		$count = [];
		foreach ($counts as $person => $number) {
			$count[$number][] = $id_name_map[$person];
		}
		krsort($count);

		//return json_encode($ret);
		return view('gs.t1gender', [
			'ret' => $ret,
			'sex' => $sex,
			'counts' => $count,
			'tour_title' => [
				't2020_M' => $this->t2020_atp, 
				't2019_M' => $this->t2011_atp, 
				't2010_M' => $this->t2009_atp, 
				't2008_M' => $this->t2002_atp, 
				't2001_M' => $this->t2000_atp, 
				't1999_M' => $this->t1996_atp, 
				't1995_M' => $this->t1995_atp, 
				't1994_M' => $this->t1990_atp, 

				't2020_W' => $this->t2020_wta,
				't2019_W' => $this->t2019_wta,
				't2018_W' => $this->t2018_wta,
				't2017_W' => $this->t2017_wta,
				't2016_W' => $this->t2016_wta,
				't2015_W' => $this->t2015_wta,
				't2014_W' => $this->t2014_wta,
				't2013_W' => $this->t2012_wta,
				't2011_W' => $this->t2011_wta,
				't2010_W' => $this->t2009_wta,
				't2008_W' => $this->t2008_wta,
				't2007_W' => $this->t2004_wta,
				't2003_W' => $this->t2001_wta,
				't2000_W' => $this->t2000_wta,
				't1999_W' => $this->t1997_wta,
				't1996_W' => $this->t1996_wta,
				't1995_W' => $this->t1993_wta,
				't1992_W' => $this->t1991_wta,
				't1990_W' => $this->t1990_wta,
			],
			'tour2name' => $this->tour2name,
		]);

	}

	private function get_seq($year, $sex, $eid) {
		if ($sex == "MS" || $sex == "MD") {
			if ($year >= 2020) {
				return array_search($eid, $this->t2020_atp);
			} else if ($year >= 2011) {
				return array_search($eid, $this->t2011_atp);
			} else if ($year >= 2009) {
				return array_search($eid, $this->t2009_atp);
			} else if ($year >= 2002) {
				return array_search($eid, $this->t2002_atp);
			} else if ($year >= 2000) {
				return array_search($eid, $this->t2000_atp);
			} else if ($year >= 1996) {
				return array_search($eid, $this->t1996_atp);
			} else if ($year >= 1995) {
				return array_search($eid, $this->t1995_atp);
			} else if ($year >= 1990) {
				return array_search($eid, $this->t1990_atp);
			}
		} else if ($sex == "WS" || $sex == "WD") {
			if (in_array($year, [2020])) return array_search($eid, $this->t2020_wta);
			else if (in_array($year, [2018, 2016, 2014])) return array_search($eid, $this->t2018_wta);
			else if (in_array($year, [2019, 2017, 2015])) return array_search($eid, $this->t2017_wta);
			else if ($year >= 2012) return array_search($eid, $this->t2012_wta);
			else if ($year >= 2011) return array_search($eid, $this->t2011_wta);
			else if ($year >= 2009) return array_search($eid, $this->t2009_wta);
			else if ($year >= 2008) return array_search($eid, $this->t2008_wta);
			else if ($year >= 2004) return array_search($eid, $this->t2004_wta);
			else if ($year >= 2001) return array_search($eid, $this->t2001_wta);
			else if ($year >= 2000) return array_search($eid, $this->t2000_wta);
			else if ($year >= 1997) return array_search($eid, $this->t1997_wta);
			else if ($year >= 1996) return array_search($eid, $this->t1996_wta);
			else if ($year >= 1993) return array_search($eid, $this->t1993_wta);
			else if ($year >= 1991) return array_search($eid, $this->t1991_wta);
			else if ($year >= 1990) return array_search($eid, $this->t1990_wta);
		}
	}
}
