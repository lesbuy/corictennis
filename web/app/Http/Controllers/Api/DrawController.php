<?php

namespace App\Http\Controllers\Draw;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DcpkWinner;
use App\User;
use Config;
use DB;
use App;

class DrawController extends Controller
{
    protected $eid;
	protected $year;
	protected $width;
	protected $height;
	protected $is_dcpk = false;

	protected $gs_type_trans;
	protected $schema;

	protected $root = '/home/ubuntu';

	public function __construct() {

		$this->gs_type_trans = ['MS','WS','MD','WD','XD','BS','GS','BD','GD','QS','PS','QD','PD','BQ','GQ','1S','2S','3S','4S','5S','1D','2D','3D','4D','5D','JD','ED','LD','ZD','SD','CS','DS','CD','DD','US','UD'];
		$this->schema = ['sextip', 'id', 'round', 'mStatus', 'score1', 'score2', 'P1A', 'P1B', 'P2A', 'P2B', 'Seed1', 'Seed2', 'P1ANation', 'P1BNation', 'P2ANation', 'P2BNation', 'P1AFirst', 'P1BFirst', 'P2AFirst', 'P2BFirst', 'P1ALast', 'P1BLast', 'P2ALast', 'P2BLast']; 

	}

	public function index($lang, $eid, $year) {

		App::setLocale($lang);
		$this->eid = $eid;
		$this->year = $year;
		$tmp = [];
		self::get_tour_info($tmp);
		if (!isset($tmp['city'])) $tmp['city'] = [];
		$title = __('frame.menu.draw') . '(' . $year . " " . join("/", array_map(function ($d) {return translate('tourname', strtolower($d));}, $tmp['city'])) . ')';

		return view('draw.index', [
			'lang' => $lang, 
			'eid' => $eid, 
			'year' => $year, 
			'pageTitle' => __('frame.menu.draw'), 
			'title' => $title, 
			'pagetype1' => 'draw',
			'pagetype2' => join("_", [$year, $eid]),
		]);

	}

	public function query(Request $req, $lang, $eid, $year) {

		App::setLocale($lang);

		$this->eid = $eid;
		$this->year = $year;

		$device = $req->input('device', 0);  // 0->pc, 1->wise

		$this->width = $req->input('screen_width', 1280);
		$this->height = $req->input('screen_height', 800);

		$ret['eid'] = $eid;
		$ret['year'] = $year;

		self::get_tour_info($ret);

		foreach ($this->gs_type_trans as $v) {
			self::process_gs($ret, $v, $device);
		}

		self::process_prize($ret);

		if (isset($ret['part'][0])) {
			$ret['status'] = 0;
			$ret['errmsg'] = '';
		}

		return json_encode($ret, JSON_UNESCAPED_UNICODE);
		//return view('draw.content', ['ret' => $ret]);

	}

	protected function process_prize(&$ret) {

		$prefix = substr($this->eid, 0, 2);
		if ($prefix == "M-" || $prefix == "W-") {
			$file = join('/', [$this->root, 'store/calendar', $this->year, 'ITF']);
			$cmd = "grep " . $this->eid . " $file | cut -f12";
			unset($r); exec($cmd, $r);
			if ($r) {
				if ($this->year <= 2018) {
					$file = $prefix . substr($r[0], 1);
				} else {
					$file = "WTT-" . $prefix . ($prefix == "M-" ? "ATP-" : "WTA-") . substr($r[0], 1);
				}
			}
		} else {
			$file = $this->eid;
		}

		$cmd = "sort -k1,1 -k2g,2 " . join('/', [$this->root, 'store/round', $this->year, $file]);
		unset($r); exec($cmd, $r);

		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				if (count($arr) < 4) continue;
				$sextip = @$arr[0];
				$round = @$arr[2];
				$point = @$arr[3];
				$prize = @$arr[4];
				$ret['round'][$sextip][$round] = [$point, $prize];
			}
		}
	}

	protected function process_gs(&$ret, $type, $device) {

		$tour = $this->eid;

		$draw_file = join('/', [$this->root, 'store/draw', $this->year, $tour]);
		if (file_exists($draw_file)) {
			$tic = self::tic();

			$XML = ['match' => []];

			$cmd = "grep \"^$type\" $draw_file";
			unset($r); exec($cmd, $r);

			if (!$r) return;

			foreach ($r as $line) {

				$arr = explode("\t", $line);
				$row = [];
				foreach ($this->schema as $k => $v) {
					$row[$v] = @$arr[$k];
				}
				$XML['match'][] = $row;
			}


			//echo "\t\t\t\tload $file draw " . self::tac($tic) . "\n";
			// 计算签位数与轮次数
			$drawCount1 = 0;
			$drawCount2 = 0;
			$round = 0;

			if (!isset($XML['match'])) return;

			$maxRRRound = false;

			foreach ($XML['match'] as $match) {
				$sextip_arr = explode("/", $match['sextip']);
				if (isset($sextip_arr[1]) && $sextip_arr[1] == "RR") $maxRRRound = true;
				$match['sextip'] = $sextip_arr[0];

				$matchId = intval($match['id']);
				$round = floor(($matchId % 1000) / 100);

				if (!(isset($sextip_arr[1]) && $sextip_arr[1] == "RR") && $round == 1) {
					$drawCount1 = $matchId % 100;
				} else if (!(isset($sextip_arr[1]) && $sextip_arr[1] == "RR") && $round == 2) {
					$drawCount2 = $matchId % 100;
				} else if ($round == 0) {
					$maxRRRound = true;
				}
					
			}

			$drawCount = max($drawCount1, $drawCount2 * 2);

			$p_info = [];

			$tic = self::tic();
			self::process_id_info_gs($XML, $p_info);
			//echo "\t\t\t\tload $file id " . self::tac($tic) . "\n";

			if ($maxRRRound) {
				self::process_rounds_grid_rr($ret, $p_info, $XML, $type);
			}

			if (preg_match('/[PQ]/', $type)) {
				self::process_rounds_grid_gs($ret, $p_info, $XML, $type, $drawCount, 0, $round, $round, 2); //资格赛都展现单边
			} else if ($this->width >= $this->height) { // 横屏
				if ($round > 5) {
					self::process_rounds_grid_gs($ret, $p_info, $XML, $type, $drawCount, $round - 4, 4, $round, 2); // 16强签表
					self::process_rounds_grid_gs($ret, $p_info, $XML, $type, $drawCount, 0, $round - 2, $round, 1); // 分区4强签表
				} else {
					self::process_rounds_grid_gs($ret, $p_info, $XML, $type, $drawCount, 0, $round, $round, 1); // 完整签表
				}
			} else { // 竖屏
				if ($round < 5) {
					self::process_rounds_grid_gs($ret, $p_info, $XML, $type, $drawCount, 0, $round, $round, 2); // 完整签表
				} else {
					self::process_rounds_grid_gs($ret, $p_info, $XML, $type, $drawCount, $round - 3, 3, $round, 2); // 8强签表
					self::process_rounds_grid_gs($ret, $p_info, $XML, $type, $drawCount, 0, $round - 3, $round, 2); // 分区8强签表
				}
			}

			unset($p_info);
		}

	}

	protected function process_id_info_gs(&$XML, &$p_info) {
		foreach ($XML['match'] as $match) {
			$matchId = intval($match['id']);
			//if (floor(($matchId % 1000) / 100) > 3) continue;

			foreach ([1, 2] as $p) {
				foreach (['A', 'B'] as $q) {
					if ($match['P' . $p . $q]) {
						$pid = $match['P' . $p . $q] . '';
						if (isset($p_info[$pid])) continue;
						if (strpos($pid, 'atp') !== false) {
							$pid = strtoupper(substr($pid, 3));
						//	$all_atp_id[] = $pid;
						} else if (strpos($pid, 'wta') !== false) {
							$pid = preg_replace('/^wta0*/', '', $pid);
						//	$all_wta_id[] = $pid;
						} else if (strpos($pid, 'coric') !== false) {
							$pid = substr($pid, 5);
						} else if ($pid == 'unknown' || $pid == "BYE") {
							$pid = 0;
						}

						$entry = $match['Seed' . $p] . '';
						if ($entry) $entry = '[' . $entry . ']';

						$ioc = $match['P' . $p . $q . 'Nation'] . '';
						if ($pid === 0) {$nameShort =  __('draw.seq.Bye'); $nameLong = "Bye";}
						else if ($pid === "QUAL") {$nameShort = __('draw.seq.Qualifier'); $nameLong = "Qualifier";}
						else {
						//	$nameLong = replace_letters($match['P' . $p . $q . 'First'] . ' ' . $match['P' . $p . $q . 'Last']);
							$nameLong = translate2long($pid, $match['P' . $p . $q . 'First'], $match['P' . $p . $q . 'Last'], $ioc, 'en');
						//	$nameShort = rename2short($match['P' . $p . $q . 'First'], $match['P' . $p . $q . 'Last'], $ioc);
							$nameShort = translate2short($pid, $match['P' . $p . $q . 'First'], $match['P' . $p . $q . 'Last'], $ioc);
						}

						$p_info[$pid] = [$entry, $ioc, $nameLong, $nameShort];
					}
				}
						
			}
		}

		$p_info['LIVE'] = ['', 'LIVE', __('draw.notice.LIVE'), __('draw.notice.LIVE')];
		$p_info['TBD'] = ['', 'LIVE', __('draw.notice.TBD'), __('draw.notice.TBD')];
		$p_info['COMEUP'] = ['', 'LIVE', __('draw.notice.COMEUP'), __('draw.notice.COMEUP')];
	}

	protected function process_rounds_grid_rr(&$ret, &$p_info, &$XML, $type) {

		$maxRRRound = [];
		$display_rr = false; // 新的小组赛标记

		foreach ($XML['match'] as $match) {
			$sextip_arr = explode("/", $match['sextip']);
			if (isset($sextip_arr[1]) && $sextip_arr[1] == "RR") $display_rr = true; else $display_rr = false;
			$match['sextip'] = $sextip_arr[0];

			$matchId = intval($match['id']);
			$round = floor(($matchId % 1000) / 100);
			if ($round > 0 && !$display_rr) continue;  // round == 0是旧的小组赛标记

			if ($display_rr) {
				if (!isset($maxRRRound[$round - 1])) $maxRRRound[$round - 1] = 0;
				$maxRRRound[$round - 1] = max($maxRRRound[$round - 1], ($matchId % 10) * 100, floor(($matchId % 100) / 10) * 100); // 最后2位取大，作为每组总人数。为了区别，把它乘100
			} else {
				$maxRRRound[floor(($matchId % 100) / 20)] = ($matchId % 100) % 20;
			}
		}

		$position = [];
		$position_style = [];

		$block_capacity = count($maxRRRound);
		$player_num = [];
		foreach ($maxRRRound as $block => $maxGroupSeq) {
			// 根据最大序号判定这个小组有几个人
			if ($maxGroupSeq > 100) {  // 说明是新小组赛标记
				$player_num[$block] = $maxGroupSeq / 100;
			} else {
				if ($maxGroupSeq == 1) {
					$player_num[$block] = 2;
				} else if ($maxGroupSeq == 6) {
					$player_num[$block] = 3;
				} else if ($maxGroupSeq == 10) {
					$player_num[$block] = 4;
				} else if ($maxGroupSeq == 13) {
					$player_num[$block] = 5;
				} else if ($maxGroupSeq == 15) {
					$player_num[$block] = 6;
				} else {
					return;
				}
			}
			$position[$block] = [];
			$position_style[$block] = [];
		}

		$matchIdTrans = [];
		foreach ($XML['match'] as $match) {
			$sextip_arr = explode("/", $match['sextip']);
			if (isset($sextip_arr[1]) && $sextip_arr[1] == "RR") {
				$display_rr = true; 
				if (isset($sextip_arr[2])) {
					$_group_name = $sextip_arr[2];
				} else {
					$_group_name = '';
				}
			} else {
				$display_rr = false;
			}

			$matchId = intval($match['id']);
			$round = floor(($matchId % 1000) / 100);
			if ($round > 0 && !$display_rr) continue;  // round == 0是旧的小组赛标记

			if ($display_rr) {
				$block = $round - 1;
			} else {
				$block = floor(($matchId % 100) / 20);
			}

			$matchIdTrans[$matchId] = preg_replace('/^.*\//', '', $match['id']);

			if ($display_rr) { // 新标记下，最后两位就是横纵坐标，无需转换
				$i = floor(($matchId % 100) / 10);
				$j = $matchId % 10;
			} else {
				$seq = ($matchId % 100) % 20;
				$seq = $seq+11+6*floor(($seq+4)/10)+7*floor($seq/10)+8*floor($seq/13)+9*floor($seq/15);

				$i = floor($seq / 10);
				$j = $seq % 10;
			}

			$p1 = []; $p2 = [];
			if ($match['P1A']) $p1[] = self::revise_gs_pid($match['P1A']);
			if ($match['P2A']) $p2[] = self::revise_gs_pid($match['P2A']);
			if ($match['P1B']) $p1[] = self::revise_gs_pid($match['P1B']);
			if ($match['P2B']) $p2[] = self::revise_gs_pid($match['P2B']);

			if ($display_rr) $position[$block][0][0] = $_group_name;
			$position[$block][$i][0] = $p1;
			$position[$block][0][$i] = $p1;
			$position[$block][$j][0] = $p2;
			$position[$block][0][$j] = $p2;

			if (strpos($p_info[$p1[0]][0], "X") !== false) {
				$position_style[$block][$i][0][] = 'text-delete';
				$position_style[$block][0][$i][] = 'text-delete';
			}
			if (strpos($p_info[$p2[0]][0], "X") !== false) {
				$position_style[$block][$j][0][] = 'text-delete';
				$position_style[$block][0][$j][] = 'text-delete';
			}

			$mStatus = $match['mStatus'] . '';
			$score1 = $match['score1'] . '';
			$score2 = $match['score2'] . '';
			$score = self::revise_gs_score($mStatus, $score1, $score2);
			$reverse_score = self::revise_gs_score($mStatus, $score1, $score2, true);

			if (preg_match('/^[FHJL]$/', $mStatus)) {
				$position[$block][$i][$j] = $score;
				$position_style[$block][$i][$j][] = 'DataIn';
				$position[$block][$j][$i] = $reverse_score;
				$position_style[$block][$j][$i][] = 'DataOut';
			} else if (preg_match('/^[GIKM]$/', $mStatus)) {
				$position[$block][$i][$j] = $reverse_score;
				$position_style[$block][$i][$j][] = 'DataOut';
				$position[$block][$j][$i] = $score;
				$position_style[$block][$j][$i][] = 'DataIn';
			} else if ($mStatus == "A") {
				$position[$block][$j][$i] = $score;
				$position[$block][$i][$j] = $score;
			} else if (preg_match('/^[BC]$/', $mStatus)) {
				$position[$block][$i][$j] = $score;
				$position[$block][$j][$i] = $reverse_score;
			} else if ($mStatus == "") {
				$position[$block][$j][$i] = $score;
			}
		}

		// 判断当前part的轮次或区号

		$round_title = 'rr';
		$block_style = 'cDrawBlockRR';

		$ret['part'][] = [
			'KO' => false,
			'type' => $type,
			'title' => $round_title,
			'position' => $position,
			'position_style' => $position_style,
			'p_info' => $p_info,
			'block_capacity' => $block_capacity,
			'rounds' => 1,
			'blockStyle' => $block_style,
			'playerNum' => $player_num,
		];

	}

	protected function process_rounds_grid_gs(&$ret, &$p_info, &$XML, $type, $drawCount, $startRound, $rounds, $round, $display_style) {
		//																MS					start	roundnum	total	1:LR, 2:L
		$tic = self::tic();

		if ($startRound < 0 || $rounds <= 0) return;

		$position = [];
		$position_style = [];
		$block_capacity = 0;
		$endRound = $startRound + $rounds;

		if ($display_style == 1) {
			$block_num = $drawCount / (1 << $endRound) * 2;  // 每个block切成左右2个
		} else {
			$block_num = $drawCount / (1 << $endRound);
		}

		// 初始化N个block
		for ($i = 1; $i <= $block_num; ++$i) {
			$position[$i] = [];
			$position_style[$i] = [];

			// 每个block里都有从第0轮到第rounds - 1轮
			for ($j = 0; $j < $rounds; ++$j) {
				$position[$i][$j] = [];
				$position_style[$i][$j] = [];
			}
		}
		
		if ($display_style == 1) {
			$block_capacity = 1 << ($rounds - 1); // 每个block容量等于切割后的签位数除以block数
		} else {
			$block_capacity = 1 << $rounds; // 每个block容量等于切割后的签位数除以block数
		}

		$matchIdTrans = [];
		foreach ($XML['match'] as $match) {

			//$match = $match['@attributes'];
			if (strpos($match['sextip'], 'RR') !== false) continue;

			$matchId = intval($match['id']);

			// 巡回赛的matchid体系不太一样
			$matchIdTrans[$matchId] = preg_replace('/^.*\//', '', $match['id']);

			$cur_round = floor(($matchId % 1000) / 100); // 第3位
			if ($cur_round <= $startRound) continue;
			if ($cur_round > $endRound) continue;
			$cur_round = $cur_round - $startRound - 1; // 该轮在本张签表中的列数
			$cur_mid = $matchId % 100; // 比赛在本轮中的序号

			$p1 = []; $p2 = [];
			if ($match['P1A']) $p1[] = self::revise_gs_pid($match['P1A']);
			if ($match['P2A']) $p2[] = self::revise_gs_pid($match['P2A']);
			if ($match['P1B']) $p1[] = self::revise_gs_pid($match['P1B']);
			if ($match['P2B']) $p2[] = self::revise_gs_pid($match['P2B']);

			if (self::revise_gs_pid($match['P1A']) === 0 || self::revise_gs_pid($match['P2A']) === 0 || self::revise_gs_pid($match['P1B']) === 0 || self::revise_gs_pid($match['P2B']) === 0) continue;

			$cur_mid = $cur_mid * (1 << $cur_round); // 根据该轮在本张签表中的列数，决定把序号扩大多少倍

			foreach ([1, 2] as $k) {

				$count = $cur_mid * 2 - (2 - $k) * (1 << $cur_round); // 根据当前序号计算实际的格位
				$i = $cur_round;
				$block = ceil($count / $block_capacity);
				
				// $count2grid 表示当前格实际显示的位置
				if ($i == 0) {
					$count2grid = $count; // 首列就在count位置
				} else {
					$count2grid = $count - (1 << ($i - 1)); // 非首列的，grid位置在count基础上向上微调
				}

				$position[$block][$i][$count2grid] = ${'p' . $k};

				// 给每个有效格都打上Grid样式，首列奇偶行样式不同。首页默认有侧边线样式
				$position_style[$block][$i][$count2grid][] = 'cDrawGrid';
				if ($i == 0) {
					if ($count % 2 == 0) {
						$position_style[$block][$i][$count2grid][] = 'cDrawGridEven';
					} else {
						$position_style[$block][$i][$count2grid][] = 'cDrawGridOdd';
					}
					$position_style[$block][$i][$count2grid][] = 'cDrawGridSideBorder';
				}

				if ($i > 0 && $i < ($display_style == 1 ? $rounds - 1 : $rounds) && $k == 2) {

					// 当签位数在当前轮次处于两格中的下半部分时，往上移半格设置侧边线样式
					for ($j = $count2grid - (1 << $i) + 1; $j <= $count2grid; ++$j) {
						$position_style[$block][$i][$j][] = 'cDrawGridSideBorder';
					}
				}

			}

			if ($i == $rounds - 1) {  // 已经是最后一列，需要新加一列

				$_i = $rounds;
				$count = $cur_mid * 2;
				$block = ceil($count / $block_capacity);
				$count2grid = $count - (1 << ($_i - 1));

				if (in_array($match['mStatus'], ['F', 'H', 'J', 'L'])) {
					$win = $p1;
				} else if (in_array($match['mStatus'], ['G', 'I', 'K', 'M'])) {
					$win = $p2;
				} else if ($match['mStatus'] == "B") {
					$win = ["LIVE"];
				} else if ($match['mStatus'] == "A") {
					$win = ["COMEUP"];
				} else if ($match['mStatus'] == "C") {
					$win = ["TBD"];
				} else {
					$win = [];
				}

				if ($display_style == 1) {
					$position[$block][$_i][0] = $win;
				} else {
					$position[$block][$_i][$count2grid] = $win;
					$position_style[$block][$_i][$count2grid] = ['cDrawGrid'];
				}

			}

			$mStatus = $match['mStatus'] . '';
			$score1 = $match['score1'] . '';
			$score2 = $match['score2'] . '';

			$score = self::revise_gs_score($mStatus, $score1, $score2);

			$count2grid = $cur_mid * 2 - (1 << $i) + 1;
			if ($display_style == 1 && $i == $rounds - 1) {
				$position[$block][$i + 1][1] = $score;
			} else {
				$position[$block][$i + 1][$count2grid] = $score;
				$position_style[$block][$i + 1][$count2grid][] = 'cDrawGridScore';
			}
		}

		// 判断当前part的轮次或区号

		$round_title = '';
		if ($round == $rounds && $startRound == 0) {
			$round_title = 'whole';
			$block_style = 'cDrawBlockLR';
			if ($round == 1 && $drawCount == 1) {
				$round_title = 'final';
			} else if ($round == 2 && $drawCount == 2) {
				$round_title = 'sf';
			} else if ($round == 3 && $drawCount == 4) {
				$round_title = 'qf';
			}
			if ($display_style == 2) $block_style = 'cDrawBlockL2R';
		} else if ($rounds == 3 && $rounds + $startRound == $round) {
			$round_title = 'qf';
			$block_style = 'cDrawBlockQf';
		} else if ($rounds == 4 && $rounds + $startRound == $round && $display_style == 2) {
			$round_title = 'eighth';
			$block_style = 'cDrawBlockEighth';
		} else {
			$round_title = 'sections';
			$block_style = 'cDrawBlockLR';
			if ($display_style == 2) $block_style = 'cDrawBlockL2R';
		}

		$first_column_size = 0;
		foreach ($position as $block => $a) {
			if (!isset($a[0])) $a[0] = [];
			foreach (@$a[0] as $k => $v) {
				if (isset($v) && !empty($v) && is_array($v) && count($v) >= 1 && $v[0] !== "") {
					++$first_column_size;
				}

			}
		}

		if ($display_style == 1) $show_round = true; else $show_round = false;

		if ($startRound == 0 || $first_column_size >= 0.85 * ($drawCount >> $startRound)) {

			$ret['part'][] = [
				'KO' => true,
				'type' => $type,
				'title' => $round_title,
				'position' => $position,
				'position_style' => $position_style,
				'p_info' => $p_info,
				'block_capacity' => $block_capacity,
				'rounds' => $rounds,
				'blockStyle' => $block_style,
				'displayStyle' => $display_style,
				'showRound' => $show_round,
			];

		}

		//echo "\t\t\t\tprocess time " . self::tac($tic) . "\n";
	}

	public function road(Request $req, $lang, $eid, $year, $sextip, $pid) {

		App::setLocale($lang);

		if (preg_match('/^D\d+$/', $eid)) $this->is_dcpk = true;

		$sextips = [];
		if ($sextip == "MS" || $sextip == "QS") {
			$sextips = ["QS", "MS"];
			$st = "MS";
		} else if ($sextip == "WS" || $sextip == "PS") {
			$sextips = ["PS", "WS"];
			$st = "WS";
		} else if ($sextip == "MD" || $sextip == "QD") {
			$sextips = ["QD", "MD"];
			$st = "MD";
		} else if ($sextip == "WD" || $sextip == "PD") {
			$sextips = ["PD", "WD"];
			$st = "WD";
		} else if ($sextip == "BS" || $sextip == "BQ") {
			$sextips = ["BQ", "BS"];
			$st = "BS";
		} else if ($sextip == "GS" || $sextip == "GQ") {
			$sextips = ["GQ", "GS"];
			$st = "GS";
		} else {
			$sextips = [$sextip];
			$st = $sextip;
		}

		$lines = [];
		foreach ($sextips as $st) {
			$draw_file = join('/', [$this->root, 'store/draw', $year, $eid]);
			$cmd = "grep '^$st' $draw_file | grep -i '$pid'";
			unset($r); exec($cmd, $r);
			$lines = array_merge($lines, $r);
		}

		$cmd = "awk -F\"\\t\" '$2 == \"$eid\"' " . join('/', [$this->root, 'store', 'calendar', $year, "*"]) . " | cut -f10";
		unset($r); exec($cmd, $r);
		$tourname = replace_letters(mb_strtolower(@$r[0]));

		if (in_array(substr($sextip, 0, 1), ['M', 'Q', 'B', 'X'])) {
			$sex = 'atp';
			if ($this->is_dcpk) $sex = 'coric';
			
		} else {
			$sex = 'wta';
		} 

		// 获取id和头像
		$atp_id = [];
		$wta_id = [];
		$coric_id = [];
		$pid = strtoupper($pid);
		$partner = $me = "";
		$oppoA = $oppoB = "";
		$result = [];
		$head = [];
		$name = [];

		if ($lines) {
			$rr_count = 0;
			foreach ($lines as $line) {
				$arr = explode("\t", $line);
				$row = [];
				foreach ($this->schema as $k => $v) {
					$row[$v] = @$arr[$k];
				}
				foreach (['P1A', 'P1B', 'P2A', 'P2B'] as $k) {
					$id = strtoupper(trim(str_replace('atp', '', str_replace('wta', '', $row[$k]))));
					if ($id && $id != 'QUAL' && $id != 'BYE' && $id != "LIVE" && $id != "TBD") {
						if (preg_match('/^[A-Za-z0-9]{4}$/', $id)) {
							$atp_id[] = $id;
						} else if (preg_match('/^[0-9]{5,6}$/', $id)) {
							$wta_id[] = $id;
						} else if (preg_match('/^CORIC/', $id)) {
							$id = substr($id, 5);
							$coric_id[] = $id;
						}
					}
					$row[$k] = $id;

					//$name[$row[$k]] = rename2short($row[$k . 'First'], $row[$k . 'Last'], $row[$k . 'Nation']);
					$name[$row[$k]] = translate2short($id, $row[$k . 'First'], $row[$k . 'Last'], $row[$k . 'Nation']);
				}

				$pos = 1;
				$oppoSeed = '';
				if ($pid == $row['P1A']) {$me = $pid; $seed = $row['Seed1']; $partner = $row['P1B']; $oppoA = $row['P2A']; $oppoB = $row['P2B']; $oppoSeed = $row['Seed2'];}
				else if ($pid == $row['P1B']) {$me = $row['P1A']; $seed = $row['Seed1']; $partner = $pid; $oppoA = $row['P2A']; $oppoB = $row['P2B']; $oppoSeed = $row['Seed2'];}
				else if ($pid == $row['P2B']) {$me = $row['P2A']; $seed = $row['Seed2']; $partner = $pid; $oppoA = $row['P1A']; $oppoB = $row['P1B']; $pos = 2; $oppoSeed = $row['Seed1'];}
				else if ($pid == $row['P2A']) {$me = $pid; $seed = $row['Seed2']; $partner = $row['P2B']; $oppoA = $row['P1A']; $oppoB = $row['P1B']; $pos = 2; $oppoSeed = $row['Seed1'];}
				else continue;
				if ($oppoA == "BYE") continue;

				$wlFlag = 'vs';
				$score = '';
				if ($row['mStatus'] != '') {
					if ((in_array($row['mStatus'], ['F', 'H', 'J', 'L']) && $pos == 1) || (in_array($row['mStatus'], ['G', 'I', 'K', 'M']) && $pos == 2)) {
						$wlFlag = 'win';
						$score = self::revise_gs_score($row['mStatus'], $row['score1'], $row['score2']);
					} else if ((in_array($row['mStatus'], ['F', 'H', 'J', 'L']) && $pos == 2) || (in_array($row['mStatus'], ['G', 'I', 'K', 'M']) && $pos == 1)) {
						$wlFlag = 'lose';
						$score = self::revise_gs_score($row['mStatus'], $row['score1'], $row['score2'], true);
					}
				}

				if ($row['round'] == 'RR') {
					if ($wlFlag != 'vs') {
						++$rr_count;
						$result[$row['round'] . $rr_count] = [$partner, $oppoA, $oppoB, $oppoSeed, $wlFlag, $score];
					}
				} else {
					$result[$row['round']] = [$partner, $oppoA, $oppoB, $oppoSeed, $wlFlag, $score];
				}

				if ($oppoA == '') {
					$head[''] = get_headshot($sex, $sex . 'playerunknown.jpg');
				}
			}
		}

		$head['0000'] = get_headshot($sex, $sex . 'player.jpg');

		foreach (['atp', 'wta'] as $type) {

			if (count(${$type . '_id'}) > 0) {

				/*
				$profile_table = 'profile_' . $type;

				if (App::isLocale('zh')) {
					$rows = DB::table($profile_table)->select('longid', 'shortchn')->whereIn('longid', ${$type . '_id'})->get();
					foreach ($rows as $row) {
						$name[strtoupper($row->longid)] = self::rename_eng_to_short($row->shortchn);
					}
				} else {
					$rows = DB::table($profile_table)->select('longid', 'name2')->whereIn('longid', ${$type . '_id'})->get();
					foreach ($rows as $row) {
						$name[strtoupper($row->longid)] = self::rename_eng_to_short($row->name2);
					}
				}
				*/

				$headshot_table = 'headshot_' . $type;
				$rows = DB::table($headshot_table)->select('id', 'headshot')->whereIn('id', ${$type . '_id'})->get();
				foreach ($rows as $row) {
					$head[strtoupper($row->id)] = get_headshot($type, $row->headshot);
				}
			}
		}

		if (count($coric_id) > 0) {
			$rows = User::whereIn('id', $coric_id)->get();
			foreach ($rows as $row) {
				$head[$row->id] = $row->avatar;
			}
		}

		if (preg_match('/D/', $sextip)) $double = true;
		else $double = false;

		return view('draw.road', [
			'result' => $result, 
			'name' => $name, 
			'head' => $head, 
			'me' => $me, 
			'partner' => $partner, 
			'seed' => $seed,
			'double' => $double,
			'year' => $year,
			'st' => $st,
			'tourname' => $tourname,
		]);
	}

	protected function get_tour_info(&$ret) {

		if (preg_match('/^D[0-9]{2}$/', $this->eid)) $this->is_dcpk = true; 

		if ($this->is_dcpk) {
			$r = DcpkWinner::where('eid', $this->eid)->whereIn('level', ['1000', 'GS'])->get();
		} else {
			$file = join('/', [$this->root, 'store', 'calendar', '*', '*']);
			$cmd = "awk -F\"\\t\" '$2 == \"" . $this->eid . "\"' $file | sort -k5gr,5";
			unset($r); exec($cmd, $r);
		}

		if (!$r) {
			$ret['status'] = -3;
			$ret['errmsg'] = __('draw.notice.tourNotExist');
			return;
		}

		$history = [];
		$ret['status'] = -2;
		$ret['errmsg'] = __('draw.notice.tourNotExistYear');
		$ret['levels'] = [];
		$ret['date'] = [];
		$ret['title'] = [];
		$ret['city'] = [];
		$ret['surface'] = [];

		$cityData = [['City']];

		if (!$this->is_dcpk) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				$level = preg_replace('/^.*:/', '', $arr[0]);
				$levels = explode('/', $level);
				$city = $arr[9];
				$year = $arr[4];
				$history[] = [$year, $levels, $city];

				if ($year == $this->year) {
					$ret['status'] = -1;
					$ret['errmsg'] = __('draw.notice.tourNoDraw');
					if (!in_array($arr[7], $ret['title'])) $ret['title'][] = $arr[7];
					if (!in_array($arr[8], $ret['surface'])) $ret['surface'][] = $arr[8];
					self::get_display_country($arr[10], $ret['country'], $ret['region']);
					$ret['levels'] = array_merge($ret['levels'], $levels);
					if (!in_array($city, $ret['city'])) {
						$ret['city'][] = $city;
						$cityData[] = [$city];
					}
					$ret['date'][] = $arr[5];
				}
			}
		} else {
			foreach ($r as $row) {
				$levels = [$row->level];
				$city = $row->tour;
				$year = $row->year;
				$history[] = [$year, $levels, $city];

				if ($year == $this->year) {
					$ret['status'] = -1;
					$ret['errmsg'] = __('draw.notice.tourNoDraw');
					$ret['city'][] = $row->tour;
					$ret['date'][] = $row->date;
				}
			}
		}

		$ret['date'] = join('/', $ret['date']);
		$ret['title'] = join('/', $ret['title']);

		$ret['cityData'] = $cityData;

		$ret['history'] = $history;
	}

	protected function get_display_country($ioc, &$chartData, &$region) {
		if (!$ioc) {
			$chartData = [];
			$region = '';
			return;
		}

		if ($ioc == "CHN") {
			$countries = ['CHN', 'TPE'];
		} else if ($ioc == 'TCH') {
			$countries = ['CZE', 'SVK'];
		} else if ($ioc == 'URS') {
			$countries = ['RUS', 'UKR', 'BLR', 'UZB', 'KAZ', 'GEO', 'AZE', 'LAT', 'LTU', 'MDA', 'KGZ', 'TJK', 'ARM', 'TKM', 'EST'];
		} else if ($ioc == 'CIS') {
			$countries = ['RUS', 'UKR', 'BLR', 'UZB', 'KAZ', 'GEO', 'AZE', 'MDA', 'KGZ', 'TJK', 'ARM', 'TKM'];
		} else if ($ioc == 'YUG') {
			$countries = ['SRB', 'CRO', 'BIH', 'MNE', 'MKD', 'SLO', 'KOS'];
		} else if ($ioc == 'SCG') {
			$countries = ['SRB', 'MNE', 'KOS'];
		} else {
			$countries = [$ioc];
		}

		$chartData = [ ['Country', ''] ];
		foreach ($countries as $i) {
			$chartData[] = [Config::get('const.iso2.' . $i), translate('nationname', $ioc)];
		}

		if (in_array($ioc, ['TCH', 'YUG', 'SCG'])) {
			$region = Config::get('const.iso2ToSubContinent.' . $chartData[1][0]);
		} else {
			$region = Config::get('const.iso2.' . $countries[0]);
		}
	}

	protected function revise_gs_pid($pid) {
		if (strpos($pid, 'atp') === 0) {
			return strtoupper(substr($pid, 3)). '';
		} else if (strpos($pid, 'wta') === 0) {
			return preg_replace('/^wta0*/', '', $pid);
		} else if (strpos($pid, 'coric') === 0) {
			return substr($pid, 5);
		} else if ($pid == 'unknown' || $pid == 'BYE') {
			return 0;
		} else {
			return $pid . '';
		}
	}

	protected function revise_gs_score($status, $s1, $s2, $reverse = false) {
		// reverse 为 true表示负者分在前, false表示胜者分在前

		if (!$status) return '';

		if ($status == "A") { // 如果状态是未开始，那么s1表示比赛的unixtime，s2表示球场并翻译
			return "<span class=unixtime>" . $s1 . "</span><br>" . translate('courtname', strtolower($s2));
		}

		if (in_array($status, ['L', 'M'])) {
			return 'W/O';
		} else if (in_array($status, ['H', 'I'])) {
			$suffix = 'Ret.';
		} else if (in_array($status, ['J', 'K'])) {
			$suffix = 'Def.';
		} else {
			$suffix = '';
		}
		if ((in_array($status, ['G', 'I', 'K']) && !$reverse) || (in_array($status, ['F', 'H', 'J']) && $reverse) || ($status == "B" && $reverse)) {
			$stmp = $s1;
			$s1 = $s2;
			$s2 = $stmp;
		}

		$score = [];
		if (!$this->is_dcpk) {
			for ($i = 1; $i <= 5; ++$i){
				if (substr($s1, 2 * ($i - 1), 1)){ 
					$a = ord(substr($s1, 2 * ($i - 1), 1)) - ord("A");
					$b = ord(substr($s2, 2 * ($i - 1), 1)) - ord("A");
					if ($a < 10 && $b < 10) {
						$tmp = $a . '' . $b;
					} else {
						$tmp = $a . '-' . $b;
					}
					$c = ord(substr($s1, 2 * ($i - 1) + 1, 1)) - ord("A");
					$d = ord(substr($s2, 2 * ($i - 1) + 1, 1)) - ord("A");
					if ($c > 0 || $d > 0) {
						$tmp .= '(' .  min($c, $d) . ')';
					}
					$score[] = $tmp;
				}
			}
		} else {
			$score[] = $s1 . '-' . $s2;
		}

		if ($suffix) {
			$score[] = $suffix;
		}

		return join(' ', $score);
	}

	protected function tic() {
		$sec = microtime();
		$arr = explode(" ", $sec);
		$tic = sprintf("%.3f", $arr[1] + $arr[0]);
		//echo "tic = " . $tic . "\n";
		return $tic;
	}

	protected function tac($tic) {
		$sec = microtime();
		$arr = explode(" ", $sec);
		$tac = sprintf("%.3f", $arr[1] + $arr[0]);
		//echo "tac = " . $tac . "\n";
		return sprintf("%.3f", $tac - $tic);
	}

}
