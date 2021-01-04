<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Cost;
use App\Scripts\Ssp;
use DB;
use Auth;
use Config;

class MoneyController extends Controller
{
    //
	protected $infoKey;

	protected $primaryKey = 'id';

	protected $table;

	protected $columns;

	protected $sql_details;

	private $userid;

	public function __construct() {

		$this->userid = 1579;
		$this->table = 'costs';

		$this->sql_details = [
			'user' => env('DB_USERNAME'),
			'pass' => env('DB_PASSWORD'),
			'db' => env('DB_DATABASE'),
			'host' => env('DB_HOST') . ':' . env('DB_PORT'),
		];

	}

	public function query(Request $req) {

		if ($req->input('userid')) $this->userid = $req->input('userid');

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

		if ($req->input('userid')) $this->userid = $req->input('userid');

		if ($column == "account") {

			$one1 = Cost::where('userid', $this->userid)->where("a1", "!=", "")->pluck('a1')->unique()->values()->toArray();
			$one2 = Cost::where('userid', $this->userid)->where("a2", "!=", "")->pluck('a2')->unique()->values()->toArray();
			$ones = array_values(array_unique(array_merge($one1, $one2)));
			usort($ones, 'self::sortByF');

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

	public function select_all(Request $req) {

		if ($req->input('userid')) $this->userid = $req->input('userid');

		$ret = [];

		foreach (['c1', 'c2', 'account', 'city', 'road', 'company', 'project'] as $column) {
			if ($column == "account") {

				$one1 = Cost::where('userid', $this->userid)->where("a1", "!=", "")->pluck('a1')->unique()->values()->toArray();
				$one2 = Cost::where('userid', $this->userid)->where("a2", "!=", "")->pluck('a2')->unique()->values()->toArray();
				$ones = array_values(array_unique(array_merge($one1, $one2)));
				usort($ones, 'self::sortByF');

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
			$ret[$column] = $ones;
		}

		return json_encode($ret);

	}

    public function save(Request $req) {
   
		if ($req->input('userid')) $this->userid = $req->input('userid');

        $input = $req->all();
   
        if (isset($input['wfd'])) {
            $one = Cost::find($input['wfd']);
        } else {
            $one = new Cost;
        }

		$mode = "normal";
		if (isset($input['mode2']) && in_array($input['mode2'], ['aa', 'month_record', 'month_pay'])) {
			$mode = $input['mode2'];
		}
 
        $one->stamp = isset($input['time']) ? $input['time'] : '2010-1-1';
        $one->type = isset($input['type']) ? $input['type'] : ''; 
        $one->c1 = isset($input['category1']) ? $input['category1'] : ''; 
        $one->c2 = isset($input['category2']) ? $input['category2'] : ''; 
        $one->a1 = isset($input['account1']) ? $input['account1'] : ''; 
        $one->a2 = isset($input['account2']) ? $input['account2'] : ''; 
        $one->price = isset($input['price']) ? $input['price'] : 0;
        $one->city = isset($input['city']) ? $input['city'] : ''; 
        $one->road = isset($input['road']) ? $input['road'] : ''; 
        $one->company = isset($input['company']) ? $input['company'] : ''; 
        $one->project = isset($input['project']) ? $input['project'] : ''; 
        $one->more = isset($input['more']) ? $input['more'] : ''; 
        $one->ajoin = join("#", ['', $one->a1, $one->a2, '']);
        $one->fact_time = isset($input['fact']) && $input['fact'] ? $input['fact'] : NULL;
        $one->userid = $this->userid;
        $one->load_label = time();

		$c1 = $one->c1; // 保留一下
		$c2 = $one->c2;
   
		// AA模式与月记模式都先记一条转账
		if ($mode == "aa") { // AA模式下
			$return = isset($input['return']) ? $input['return'] : 0;
			$one->price = min($one->price, $return); // 实际转账数额取较小的一个
		}
		if ($mode == "month_record") {
			$one->type = '转账';
			$one->a2 = '虚拟-分月';
			$one->ajoin = join("#", ['', $one->a1, $one->a2, '']);
		}

		// 所有转账和变更的大小类清空
		if ($one->type == '转账' || $one->type == '变更') {
			$one->c1 = $one->c2 = '';
		}

        $one->save();

		// AA模式下，生成一条新记录，根据金额差异决定是支出还是收入，只记差额部分
		if ($mode == "aa") {
			$price = isset($input['price']) ? $input['price'] + 0 : 0;
			$return = isset($input['return']) ? $input['return'] + 0 : 0;
			if ($price != $return) {

				$another_one = new Cost;
				$another_one->c1 = $c1;
				$another_one->c2 = $c2;
				if ($price > $return) {
					$another_one->stamp = date('Y-m-d H:i:s', strtotime($one->stamp) - 1); 
					$another_one->type = '支出';
					$another_one->a1 = $one->a1;
					$another_one->a2 = '';
					$another_one->price = $price - $return;
				} else {
					$another_one->stamp = date('Y-m-d H:i:s', strtotime($one->stamp) + 1); 
					$another_one->type = '收入';
					$another_one->a1 = '';
					$another_one->a2 = $one->a2;
					$another_one->price = $return - $price;
				}
				$another_one->city = $one->city;
				$another_one->road = $one->road;
				$another_one->company = $one->company;
				$another_one->project = $one->project;
				$another_one->more = $one->more;
				$another_one->ajoin = join("#", ['', $another_one->a1, $another_one->a2, '']);
				$another_one->fact_time = $one->fact_time;
				$another_one->userid = $one->userid;
				$another_one->load_label = time();

				$another_one->save();
			}
		}

		if ($mode == "month_record" || $mode == "month_pay") {
			$months = isset($input['months']) ? $input['months'] : 0;
			if ($months > 0) {
				$total = $one->price;
				if ($mode == "month_record") { // 时间变化，类型为支出，流出账户为虚拟，金额为月均数
					for ($i = 0; $i < $months; ++$i) {
						$another_one = new Cost;
						$another_one->stamp = date('Y-m-d H:i:s', strtotime($one->stamp . " +" . $i . " month") + 1);
						$another_one->type = '支出';
						$another_one->c1 = $c1;
						$another_one->c2 = $c2;
						$another_one->a1 = '虚拟-分月';
						$another_one->a2 = '';
						$another_one->price = $months - $i == 1 ? $total : floor($total / ($months - $i)); // 每月记1/n的整数，最后一个月记剩下的全部
						$another_one->city = $one->city;
						$another_one->road = $one->road;
						$another_one->company = $one->company;
						$another_one->project = $one->project;
						$another_one->more = $one->more;
						$another_one->ajoin = join("#", ['', $another_one->a1, $another_one->a2, '']);
						$another_one->fact_time = $one->fact_time;
						$another_one->userid = $one->userid;
						$another_one->load_label = time();

						$another_one->save();
						$total -= $another_one->price;
					}
				} else if ($mode == "month_pay") { // 除了时间变化外，其他内容均不变
					for ($i = 1; $i < $months; ++$i) {
						$another_one = new Cost;
						$another_one->stamp = date('Y-m-d H:i:s', strtotime($one->stamp . " +" . $i . " month"));
						$another_one->type = $one->type;
						$another_one->c1 = $one->c1;
						$another_one->c2 = $one->c2;
						$another_one->a1 = $one->a1;
						$another_one->a2 = $one->a2;
						$another_one->price = $one->price;
						$another_one->city = $one->city;
						$another_one->road = $one->road;
						$another_one->company = $one->company;
						$another_one->project = $one->project;
						$another_one->more = $one->more;
						$another_one->ajoin = join("#", ['', $another_one->a1, $another_one->a2, '']);
						$another_one->fact_time = $one->fact_time;
						$another_one->userid = $one->userid;
						$another_one->load_label = time();

						$another_one->save();
					}
				}
			}
		}
   
        return 0;
   
    }

	public function delete(Request $req) {

		if ($req->input('userid')) $this->userid = $req->input('userid');

		$input = $req->input('data');
		if (!$input) return -1;

		if (!is_array($input)) {
			$input = [$input];
		}

		Cost::where('userid', $this->userid)->whereIn('id', $input)->delete();

		return 0;

	}

	public function patch_edit(Request $req) {

		if ($req->input('userid')) $this->userid = $req->input('userid');

		$col = $req->input('col', '');
		$des = $req->input('des', '');
		$id = $req->input('id', []);

		if (!in_array($col, ['c1', 'c2', 'price', 'city', 'road', 'company', 'project', 'more']) || $des === '' || count($id) == 0) {
			return -1;
		}

		Cost::where('userid', $this->userid)->whereIn('id', $id)->update([$col => $des]);

		return 0;
	}

	public function modify(Request $req) {

		if ($req->input('userid')) $this->userid = $req->input('userid');

		$col = $req->input('col', '');
		$ori = $req->input('ori', '');
		$des = $req->input('des', '');
		$mandatory = $req->input('mandatory', 'false');
		if (!$col || !$ori || !$des) return json_encode(['errcode' => -1, 'errmsg' => '数据不能为空']);

		if (!in_array($col, ['c1', 'c2', 'ajoin', 'city', 'road', 'company', 'project'])) {
			return json_encode(['errcode' => -2, 'errmsg' => '列名不合法']);
		}

		if ($col != 'ajoin') {
			$records = Cost::where('userid', $this->userid)->where($col, $ori);
			$records2 = Cost::where('userid', $this->userid)->where($col, $des);
		} else {
			$records = Cost::where('userid', $this->userid)->where($col, 'like', '%#' . $ori . '#%');
			$records2 = Cost::where('userid', $this->userid)->where($col, 'like', '%#' . $des . '#%');
		}
		if ($records->count() <= 0) {
			return json_encode(['errcode' => -3, 'errmsg' => '旧名称不存在']);
		}
		if ($records2->count() > 0 && $mandatory == 'false') {
			return json_encode(['errcode' => -4, 'errmsg' => '新名称已存在，若仍需修改，请打开强制覆盖按钮']);
		}

		if ($col == 'ajoin' && strpos($des, '作废-') === 0) {
			$cost = Cost::where('userid', $this->userid)->where('a1', $ori)->sum('price');
			$income = Cost::where('userid', $this->userid)->where('a2', $ori)->sum('price');
			if (round($income - $cost, 2) != 0) {
				return json_encode(['errcode' => -5, 'errmsg' => '原账户余额不为0，不可作废。请先调整为0']);
			}
		}

		$cnt = 0;
		if ($col != 'ajoin') {
			$cnt = $records->update([$col => $des]);
		} else {
			DB::transaction(function () use ($ori, $des) {
				DB::table('costs')->where('userid', $this->userid)->where('a1', $ori)->update(['a1' => $des]);
				DB::table('costs')->where('userid', $this->userid)->where('a2', $ori)->update(['a2' => $des]);
				$cnt = DB::update('update costs set ajoin = concat("#", a1, "#", a2, "#") where ajoin like ?', ['%#' . $ori . '#%']);
			});
		}

		return json_encode(['errcode' => 0, 'errmsg' => $cnt]);
	}

	public function sum(Request $req, $account) {

		if ($req->input('userid')) $this->userid = $req->input('userid');

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
		} else if ($account == "year") {
			$costs = Cost::where('userid', $this->userid)->where('type', '支出')->where('a1', '<>', '')->groupBy('year')->select(DB::raw('date_format(stamp, "%Y") as year'), DB::raw('sum(price) as price'))->get()->toArray();
			$incomes = Cost::where('userid', $this->userid)->where('type', '收入')->where('a2', '<>', '')->groupBy('year')->select(DB::raw('date_format(stamp, "%Y") as year'), DB::raw('sum(price) as price'))->get()->toArray();

			foreach ($costs as $k) {
				if (!isset($ret[$k['year']])) {
					$ret[$k['year']] = [$k['year'], 0, 0, 0];
				}
				$ret[$k['year']][1] = round($k['price'], 2);
			}
			foreach ($incomes as $k) {
				if (!isset($ret[$k['year']])) {
					$ret[$k['year']] = [$k['year'], 0, 0, 0];
				}
				$ret[$k['year']][2] = round($k['price'], 2);
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

		if ($account == "month" || $account == "year") {
			usort($ret, 'self::key_sort');
		} else {
			usort($ret, 'self::sum_sort');
		}
		return json_encode($ret);

	}

	public function sum_all(Request $req) {

		if ($req->input('userid')) $this->userid = $req->input('userid');

		$rets = [];

		foreach (['month', 'year', 'account', 'category', 'category2'] as $account) {
			if (!in_array($account, ['month', 'year'])) {
				$min_date = $req->input('min_date') ? $req->input('min_date') : '1970-1-1';
				$max_date = $req->input('max_date') ? $req->input('max_date') : date('Y-m-d', time());
				$DB = Cost::where('userid', $this->userid)->whereBetween('stamp', [$min_date, $max_date . " 23:59:59"]);
			} else {
				$DB = Cost::where('userid', $this->userid);
			}
			$DB1 = clone $DB;
			$DB2 = clone $DB;
			$DB3 = clone $DB;
			$DB4 = clone $DB;
			$ret = [];
			if ($account == "account") {
				$cost = $DB1->where('type', '<>', '转账')->where('a1', '<>', '')->sum('price');
				$income = $DB2->where('type', '<>', '转账')->where('a2', '<>', '')->sum('price');
				$ret["全部"] = ["全部", round($cost, 2), round($income, 2), round($income - $cost, 2)];

				$costs = $DB3->where('a1', '<>', '')->groupBy('a1')->select('a1 as account', DB::raw('sum(price) as cost'))->get()->toArray();
				$incomes = $DB4->where('a2', '<>', '')->groupBy('a2')->select('a2 as account', DB::raw('sum(price) as income'))->get()->toArray();
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
				$costs = $DB1->where('type', '支出')->where('c1', '<>', '')->groupBy('c1')->select('c1 as category', DB::raw('sum(price) as cost'))->get()->toArray();
				$incomes = $DB2->where('type', '收入')->where('c1', '<>', '')->groupBy('c1')->select('c1 as category', DB::raw('sum(price) as income'))->get()->toArray();
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
				$costs = $DB1->where('type', '支出')->where('c1', '<>', '')->where('c2', '<>', '')->groupBy(['c1', 'c2'])->select(DB::raw('concat(c1, "/", c2) as category'), DB::raw('sum(price) as cost'))->get()->toArray();
				$incomes = $DB2->where('type', '收入')->where('c1', '<>', '')->where('c2', '<>', '')->groupBy(['c1', 'c2'])->select(DB::raw('concat(c1, "/", c2) as category'), DB::raw('sum(price) as income'))->get()->toArray();
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
				$costs = $DB1->where('type', '支出')->where('a1', '<>', '')->groupBy('month')->select(DB::raw('date_format(stamp, "%Y-%m") as month'), DB::raw('sum(price) as price'))->get()->toArray();
				$incomes = $DB2->where('type', '收入')->where('a2', '<>', '')->groupBy('month')->select(DB::raw('date_format(stamp, "%Y-%m") as month'), DB::raw('sum(price) as price'))->get()->toArray();

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
			} else if ($account == "year") {
				$costs = $DB1->where('type', '支出')->where('a1', '<>', '')->groupBy('year')->select(DB::raw('date_format(stamp, "%Y") as year'), DB::raw('sum(price) as price'))->get()->toArray();
				$incomes = $DB2->where('type', '收入')->where('a2', '<>', '')->groupBy('year')->select(DB::raw('date_format(stamp, "%Y") as year'), DB::raw('sum(price) as price'))->get()->toArray();

				foreach ($costs as $k) {
					if (!isset($ret[$k['year']])) {
						$ret[$k['year']] = [$k['year'], 0, 0, 0];
					}
					$ret[$k['year']][1] = round($k['price'], 2);
				}
				foreach ($incomes as $k) {
					if (!isset($ret[$k['year']])) {
						$ret[$k['year']] = [$k['year'], 0, 0, 0];
					}
					$ret[$k['year']][2] = round($k['price'], 2);
				}
			}

			foreach ($ret as $k => $v) {
				$ret[$k][3] = round($ret[$k][2] - $ret[$k][1], 2);
			}

			if ($account == "month" || $account == "year") {
				usort($ret, 'self::key_sort');
			} else {
				usort($ret, 'self::sum_sort');
			}

			$rets[$account] = $ret;
		}

		return json_encode($rets);

	}

	private function sortByF($a, $b) {
		return iconv('UTF-8', 'GBK', $a) < iconv('UTF-8', 'GBK', $b) ? -1 : 1;
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
