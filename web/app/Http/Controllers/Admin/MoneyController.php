<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Cost;
use App\Scripts\Ssp;
use DB;
use Config;
use Auth;

class MoneyController extends Controller
{
    //

	protected $infoKey;

	protected $primaryKey = 'id';

	protected $table;

	protected $columns;

	protected $sql_details;

	protected $userid;

	public function __construct() {

		$this->table = 'costs';

		$this->sql_details = [
			'user' => env('DB_USERNAME'),
			'pass' => env('DB_PASSWORD'),
			'db' => env('DB_DATABASE'),
			'host' => env('DB_HOST') . ':' . env('DB_PORT'),
		];

	}

	public function index() {

		return view('money.index');

	}

	public function query(Request $req) {

		$this->userid = Auth::check() ? Auth::id() : 0;
		$this->columns = [

			[ 'db' => 'id', 'dt' => 'id' ],
			[ 'db' => 'stamp', 'dt' => 'stamp' ],
			[ 'db' => 'type', 'dt' => 'type' ],
			[ 'db' => 'c1', 'dt' => 'c1' ],
			[ 'db' => 'c2', 'dt' => 'c2' ],
			[ 'db' => 'a1', 'dt' => 'a1' ],
			[ 'db' => 'a2', 'dt' => 'a2' ],
			[ 'db' => 'price', 'dt' => 'price' ],
			[ 'db' => 'city', 'dt' => 'city' ],
			[ 'db' => 'road', 'dt' => 'road' ],
			[ 'db' => 'company', 'dt' => 'company' ],
			[ 'db' => 'project', 'dt' => 'project' ],
			[ 'db' => 'more', 'dt' => 'more' ],
			[ 'db' => 'ajoin', 'dt' => 'ajoin' ],
			[ 'db' => 'fact_time', 'dt' => 'fact_time' ],

		];

		return json_encode(
			Ssp::simple($req->all(), $this->sql_details, $this->table, $this->primaryKey, $this->columns, $this->userid)
		);

	}

	public function select(Request $req, $column) {

		$this->userid = Auth::check() ? Auth::id() : 0;
		if ($column == "account") {

			$one1 = Cost::where('userid', $this->userid)->pluck('a1')->unique()->values()->toArray();
			$one2 = Cost::where('userid', $this->userid)->pluck('a2')->unique()->values()->toArray();
			$ones = array_values(array_unique(array_merge($one1, $one2)));

		} else if ($column != "stamp") {
			$ones = Cost::where('userid', $this->userid)->where($column, "!=", "")->pluck($column)->unique()->sortBy(function ($item) {return iconv('UTF-8', 'GBK', $item);})->values()->all();
		} else {
			$ones = Cost::where('userid', $this->userid)->where($column, "!=", "")->pluck($column)->map(
				function ($item) {
					return date('Y-m', strtotime($item));
				}
			)->unique()->values()->toArray();
			rsort($ones);
		}

		return json_encode($ones);

	}

	public function month() {

		$this->userid = Auth::check() ? Auth::id() : 0;	
		$ret = [];

		$costs = Cost::where('userid', $this->userid)->where('type', '支出')->where('a1', '<>', '')->groupBy('month')->select(DB::raw('date_format(stamp, "%Y-%m") as month'), DB::raw('round(sum(price)) as price'))->get()->toArray();
		$incomes = Cost::where('userid', $this->userid)->where('type', '收入')->where('a2', '<>', '')->groupBy('month')->select(DB::raw('date_format(stamp, "%Y-%m") as month'), DB::raw('round(sum(price)) as price'))->get()->toArray();

		foreach ($costs as $k) {
			if (!isset($ret[$k['month']])) {
				$ret[$k['month']] = [0, 0];
			}
			$ret[$k['month']][0] = $k['price'];
		}
		foreach ($incomes as $k) {
			if (!isset($ret[$k['month']])) {
				$ret[$k['month']] = [0, 0];
			}
			$ret[$k['month']][1] = $k['price'];
		}

		krsort($ret);

		return json_encode($ret);
	}

	public function sum($account) {

		$this->userid = Auth::check() ? Auth::id() : 0;
		$ret = [];
		if ($account == "account" || $account == "all") {
			$cost = Cost::where('userid', $this->userid)->where('type', '<>', '转账')->where('a1', '<>', '')->sum('price');
			$income = Cost::where('userid', $this->userid)->where('type', '<>', '转账')->where('a2', '<>', '')->sum('price');
			$ret["全部"] = ["全部", round($cost, 2), round($income, 2), round($income - $cost, 2)];

			$costs = Cost::where('userid', $this->userid)->where('a1', '<>', '')->groupBy('a1')->select('a1 as account', DB::raw('sum(price) as cost'))->get()->toArray();
			$incomes = Cost::where('userid', $this->userid)->where('a2', '<>', '')->groupBy('a2')->select('a2 as account', DB::raw('sum(price) as income'))->get()->toArray();
			foreach (array_merge($costs, $incomes) as $k) {
				if (!isset($ret[$k['account']])) {
					$ret[$k['account']] = [$k['account'], 0, 0, 0];
				}
				if (isset($k['cost'])) {
					$ret[$k['account']][1] = round($k['cost'], 2);
				}
				if (isset($k['income'])) {
					$ret[$k['account']][2] = round($k['income'], 2);
				}
			}
		} else if ($account == "category") {
			$costs = Cost::where('userid', $this->userid)->where('type', '支出')->where('c1', '<>', '')->groupBy('c1')->select('c1 as category', DB::raw('sum(price) as cost'))->get()->toArray();
			$incomes = Cost::where('userid', $this->userid)->where('type', '收入')->where('c1', '<>', '')->groupBy('c1')->select('c1 as category', DB::raw('sum(price) as income'))->get()->toArray();
			foreach (array_merge($costs, $incomes) as $k) {
				if (!isset($ret[$k['category']])) {
					$ret[$k['category']] = [$k['category'], 0, 0, 0];
				}
				if (isset($k['cost'])) {
					$ret[$k['category']][1] = round($k['cost'], 2);
				}
				if (isset($k['income'])) {
					$ret[$k['category']][2] = round($k['income'], 2);
				}
			}
		} else if ($account == "category2") {
			$costs = Cost::where('userid', $this->userid)->where('type', '支出')->where('c1', '<>', '')->where('c2', '<>', '')->groupBy(['c1', 'c2'])->select(DB::raw('concat(c1, "/", c2) as category'), DB::raw('sum(price) as cost'))->get()->toArray();
			$incomes = Cost::where('userid', $this->userid)->where('type', '收入')->where('c1', '<>', '')->where('c2', '<>', '')->groupBy(['c1', 'c2'])->select(DB::raw('concat(c1, "/", c2) as category'), DB::raw('sum(price) as income'))->get()->toArray();
			foreach (array_merge($costs, $incomes) as $k) {
				if (!isset($ret[$k['category']])) {
					$ret[$k['category']] = [$k['category'], 0, 0, 0];
				}
				if (isset($k['cost'])) {
					$ret[$k['category']][1] = round($k['cost'], 2);
				}
				if (isset($k['income'])) {
					$ret[$k['category']][2] = round($k['income'], 2);
				}
			}
		} else if ($account == "month") {
			$costs = Cost::where('userid', $this->userid)->where('type', '支出')->where('a1', '<>', '')->groupBy('month')->select(DB::raw('date_format(stamp, "%Y-%m") as month'), DB::raw('sum(price) as price'))->get()->toArray();
			$incomes = Cost::where('userid', $this->userid)->where('type', '收入')->where('a2', '<>', '')->groupBy('month')->select(DB::raw('date_format(stamp, "%Y-%m") as month'), DB::raw('sum(price) as price'))->get()->toArray();

			foreach ($costs as $k) {
				if (!isset($ret[$k['month']])) {
					$ret[$k['month']] = [$k['month'], 0, 0, 0];
				}
				$ret[$k['month']][1] = round($k['price'], 2);
			}
			foreach ($incomes as $k) {
				if (!isset($ret[$k['month']])) {
					$ret[$k['month']] = [$k['month'], 0, 0, 0];
				}
				$ret[$k['month']][2] = round($k['price'], 2);
			}
		} else if ($account != "000") {
			$cost = Cost::where('userid', $this->userid)->where('a1', $account)->sum('price');
			$income = Cost::where('userid', $this->userid)->where('a2', $account)->sum('price');
			$ret[$account] = [$account, $cost, $income, $income - $cost];
		} else {
			$cost = Cost::where('userid', $this->userid)->where('type', '<>', '转账')->where('a1', '<>', '')->sum('price');
			$income = Cost::where('userid', $this->userid)->where('type', '<>', '转账')->where('a2', '<>', '')->sum('price');
			$ret["000"] = ["000", $cost, $income, $income - $cost];
		}

		foreach ($ret as $k => $v) {
			$ret[$k][3] = round($ret[$k][2] - $ret[$k][1], 2);
		}

		if ($account == "month") {
			usort($ret, 'self::key_sort');
		} else {
			usort($ret, 'self::sum_sort');
		}
		return json_encode($ret);

	}

	public function save(Request $req) {

		$input = $req->all();

		if (isset($input['wfd'])) {
			$one = Cost::find($input['wfd']);
		} else {
			$one = new Cost;
		}
		$one->stamp = isset($input['time']) ? $input['time'] : '2010-1-1';
		$one->type = isset($input['type']) ? $input['type'] : '';
		$one->c1 = isset($input['category1']) ? $input['category1'] : '';
		$one->c2 = isset($input['category2']) ? $input['category2'] : '';
		$one->a1 = isset($input['account1']) ? $input['account1'] : '';
		$one->a2 = isset($input['account2']) ? $input['account2'] : '';
		$one->price = isset($input['price']) ? $input['price'] : '0';
		$one->city = isset($input['city']) ? $input['city'] : '';
		$one->road = isset($input['road']) ? $input['road'] : '';
		$one->company = isset($input['company']) ? $input['company'] : '';
		$one->project = isset($input['project']) ? $input['project'] : '';
		$one->more = isset($input['more']) ? $input['more'] : '';
		$one->ajoin = join("#", ['', $one->a1, $one->a2, '']);
		$one->fact_time = isset($input['fact']) && $input['fact'] ? $input['fact'] : NULL;
		$one->userid = Auth::id();
		$one->load_label = time();

		$one->save();

		return 0;

	}

	public function patch_save() {

		$now = time();
		$cmd = 'cat ' . join("/", [Config::get('const.root'), 'money']);
		unset($r); exec($cmd, $r);
		$count = 0;
		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				$one = new Cost;
				$one->stamp = date('Y-m-d H:i:s', strtotime($arr[0]));
				$one->type = $arr[1] . "";
				$one->c1 = $arr[2] . "";
				$one->c2 = $arr[3] . "";
				$one->a1 = $arr[4] . "";
				$one->a2 = $arr[5] . "";
				$one->price = $arr[6] + 0;
				$one->city = @$arr[7] . "";
				$one->road = @$arr[8] . "";
				$one->company = @$arr[9] . "";
				$one->project = @$arr[10] . "";
				$one->more = @$arr[11] . "";
				$one->ajoin = join("#", ['', $one->a1, $one->a2, '']);
				$one->fact_time = @$arr[12] ? @$arr[12] : NULL;
				$one->userid = Auth::id();
				$one->load_label = $now;
				$one->save();
				++$count;
				unset($one);
			}
		}

		return "更新了" . $count . "条记录。请勿刷新此页面";
	}

	public function delete(Request $req) {

		$this->userid = Auth::check() ? Auth::id() : 0;
		$input = $req->all();

		if (!isset($input['id'])) {
			return -1;
		}

		Cost::where('userid', $this->userid)->find($input['id'])->delete();

		return 0;

	}

	private function sum_sort($a, $b) {
		// 按结余倒序
		return $a[3] >= $b[3] ? -1 : 1;
	}

	private function key_sort($a, $b) {
		// 按key倒序
		return $a[0] >= $b[0] ? -1 : 1;
	}

}
