<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

class DownTour {
	public $eventID; // 系统内的赛事id，要么是join的，要么是4位数字
	public $year;
	public $city;
	public $tourID; // 下载用的赛事id，就是纯数字，不一定是4位
	public $monday;
	public function printSelf() {
		print_line($this->eventID, $this->year, $this->city, $this->monday);
	}
}

abstract class DownBase {
	protected $asso;
	protected $tourList = []; // []DownTour
	protected $mondays = [];

	public function __construct($asso = null) {
		$this->asso = $asso;

		$res = $this->getTourList();
		if (!$res[0]) {print_line($res[1]); exit;}

		$res = $this->downPlayerFile();
		if (!$res[0]) {print_line($res[1]); exit;}

		$res = $this->downDrawFile();
		if (!$res[0]) {print_line($res[1]); exit;}

		$res = $this->downOOPFile();
		if (!$res[0]) {print_line($res[1]); exit;}

		$res = $this->downResultFile();
		if (!$res[0]) {print_line($res[1]); exit;}
	}
	public function printTourList() {
		foreach ($this->tourList as $t) {
			print_line($t->eventID, $t->year, $t->city, $t->monday);
		}
	}
	abstract protected function getTourList();
	abstract protected function downPlayerFile();
	abstract protected function downDrawFile();
	abstract protected function downOOPFile();
	abstract protected function downResultFile();
}
