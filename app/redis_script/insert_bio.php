<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$gender = get_param($argv, 1, 'all', ' atp wta all ');

$redis = new redis_cli('127.0.0.1', 6379);

if ($gender == 'all') {
	$genders = ['atp', 'wta'];
} else {
	$genders = [$gender];
}

$schema = [
	"id",
	"longid",
	"name",
	"name2",
	"ioc",
	"nationfull",
	"birthday",
	"birthplace",
	"residence",
	"height_imp",
	"height",
	"weight_imp",
	"weight",
	"hand",
	"backhand",
	"turnpro",
	"pronoun",
	"website",
	"prize_c",
	"prize_y",
	"rank_s",
	"rank_s_hi",
	"rank_s_hi_date",
	"title_s_c",
	"title_s_y",
	"win_s_c",
	"lose_s_c",
	"win_s_y",
	"lose_s_y",
	"rank_d",
	"rank_d_hi",
	"rank_d_hi_date",
	"title_d_c",
	"title_d_y",
	"win_d_c",
	"lose_d_c",
	"win_d_y",
	"lose_d_y",
	"first",
	"last",
];

foreach ($genders as $gender) {
	$fp = fopen(ROOT . '/' . $gender . '/player_bio', 'r');
	while ($line = trim(fgets($fp))) {
		$arr = explode("\t", $line);
		$ret = [];
		foreach ($schema as $key => $value) {
			$ret[$value] = $arr[$key];
		}

		$redis->cmd('HMSET', $gender . '_profile_' . $ret['id'], 
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
			'lose_d_y', $ret['lose_d_y'])->set();
	}
}
