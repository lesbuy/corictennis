<?php

if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

define('WORK', APP . '/calendar');

class TournamentInfo {
	// asso协会与性别并不完全绑定，比如itf-junior既有男也有女
	public $asso;
	public $level;
	public $eventID; // 可能是joint ID，比如M开头的
	public $liveID; // 一般是itf下载数据用的id
	public $gender; // 一般是M，W，J三种
	public $year = 0;
	public $start;
	public $end;
	public $monday; // 规范化到周一
	public $monday_unix = 0;
	public $weeks = 1; // 1或2
	public $title;
	public $surface;
	public $inOutdoor = "O";
	public $city;
	public $nation;
	public $nation3; // 国家三字码
	public $totalPrize = 0;
	public $currency = "$";
	public $hasHospital = false; // 是否+H，只在ITF比赛中
	public $drawMaleSingles = 0;
	public $drawFemaleSingles = 0;
	public $drawMaleDoubles = 0;
	public $drawFemaleDoubles = 0;
	public $hospital = false;

	public function dump() {
		print_line(
			$this->level,
			$this->eventID,
			$this->eventID, // $this->liveID,
			$this->gender,
			$this->year,
			$this->monday,
			$this->monday_unix,
			$this->title,
			$this->surface . ($this->inOutdoor == "I" ? "(I)" : ""),
			$this->city,
			$this->nation3 == "" ? $this->nation : $this->nation3,
			$this->currency . $this->totalPrize . ($this->hospital ? "+" : ""),
			$this->drawMaleSingles,
			$this->drawMaleDoubles,
			0,
			0,
			$this->drawFemaleSingles,
			$this->drawFemaleDoubles,
			0,
			0,
			$this->totalPrize,
			$this->weeks
		);
	}
}

abstract class CalendarBase {

	// $asso 协会名称：atp, ch, wta, 125k, itf-men, itf-women, itf-junior
	protected $asso;
	protected $start;
	protected $end;
	protected $url;
	protected $content;
	protected $tournaments = [];

	public function __construct ($asso, $start = null, $end = null) {
		$this->asso = $asso;
		if ($start == null) {
			$this->start = date("Y-m-d", time());
		} else {
			$this->start = date("Y-m-d", strtotime($start));
		}
		if ($end == null) {
			$this->end = date("Y-m-d", time() + 30 * 86400);
		} else {
			$this->end = date("Y-m-d", strtotime($end));
		}

		$res = $this->preProcess();
		if (!$res[0]) {print_line($res[1]); exit;}

		$res = $this->preProcessSelf();
		if (!$res[0]) {print_line($res[1]); exit;}

		$res = $this->download();
		if (!$res[0]) {print_line($res[1]); exit;}

		$res = $this->parse();
		if (!$res[0]) {print_line($res[1]); exit;}

		$res = $this->output();
		if (!$res[0]) {print_line($res[1]); exit;}
	}

	// 公共预处理 
	protected function preProcess() {
		return [true, ""];
	}

	// 各自的预处理
	abstract protected function preProcessSelf();

	// 下载数据
	abstract protected function download();

	// 从下载的数据中parse出所需的字段
	abstract protected function parse();

	// 输出
	protected function output() {
		foreach ($this->tournaments as $t) {
			$t->dump();
		}
		return [true, ""];
	}
}
