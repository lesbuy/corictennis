<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php');

require_once(APP . '/tool/medoo.php');
require_once(APP . '/tool/redis.php');

function new_db($str){
	if (!in_array($str, ["atp", "wta", "general", "dcpk_rank_day", "dcpk_sign_week", "dcpk", "dc", "dcpk_fill", "test"])) return null;
	return new medoo(
		array(
			// 必须配置项
			'database_type' => 'mysql',
			'database_name' => $str,
			'server' => 'localhost',
			'username' => 'root',
			'password' => 'Coric518@@',
			'charset' => 'utf8',
			'port' => 3306,
		)
	);
}

function new_redis() {
	$redis = new redis_cli('127.0.0.1', 6379);
	return $redis;
}

function in_string($string, $str) {
	return strpos($string, $str) !== false;
}

function swap(&$a, &$b) {
	$tmp = $a;
	$a = $b;
	$b = $tmp;
}

function ceil_power($draw) {
	if ($draw == 0) return 0;
	return exp(ceil(log($draw, 2)) * log(2));
}

function tic() {
	return microtime();
}

function toc($tic) {
	$s = explode(" ", $tic);
	if (count($s) != 2) return 0;
	$t = explode(" ", microtime());
	return intval($t[1]) - intval($s[1]) + floatval($t[0]) - floatval($s[0]);
}

function http($url, $post = NULL, $cookie = NULL, $headers = NULL){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_HEADER, 0); 
	if ($headers != NULL) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	if(!empty($cookie)){
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
	if ($post != NULL){
		curl_setopt($ch, CURLOPT_POST, 1);
		if (is_array($post)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, set_var_field($post));
		} else {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
	}
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}

function print_err() {
	$argv = func_get_args();
	fputs(STDERR, join("\t", $argv) . "\n");
}

function print_line() {
	$argv = func_get_args();
	echo join("\t", $argv) . "\n";
}

function output_content($content, $fp = STDOUT) {
	// content 需要自己带\n
	fputs($fp, $content);
}

function set_var_field($post) {
	if (!is_array($post)) {
		return $post;
	} else {
		$params = array();
		foreach ($post as $k => $v) {
			$params[] = urlencode($k)."=".urlencode($v);
		}
		$param = join("&", $params);
		return $param;
	}
}

function get_var_field($params) {
	$ret = [];
	$arr = explode("&", $params);
	foreach ($arr as $item) {
		$ar = explode('=', $item);
		if (count($ar) == 1) {
			$ret[$ar[0]] = "";
		} else if (count($ar) >= 2) {
			$ret[$ar[0]] = $ar[1];
		}
	}
	return $ret;
}

function get_param($ARR, $param, $default, $param_str = null){
	if ($param_str !== null){
		if (!isset($ARR[$param]) || strpos($param_str, " ".$ARR[$param]." ") === false){
			return $default;
		}
	}else{
		if (!isset($ARR[$param])){
			return $default;
		}
	}
	return $ARR[$param];
}

function replace_ioc($str){
    $replace = array(
        'MSH' => 'MHL', 'FRG' => 'GER', 'LIB' => 'LBN', 'SIN' => 'SGP', 'RHO' => 'ZIM',
        'MAL' => 'MAS', 'ROM' => 'ROU', 'TRI' => 'TTO', 'GUF' => 'FGU', 'MNP' => 'NMI',
    );    
    return str_replace(array_keys($replace), $replace, $str);
}

function replace_letters($str){
$replace = array(
    '&lt;' => '<', '&gt;' => '>', '&#039;' => '', '&amp;' => '&',
    '&quot;' => '"', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae',
    '&Auml;' => 'A', 'Å' => 'A', 'Ā' => 'A', 'Ą' => 'A', 'Ă' => 'A', 'Æ' => 'Ae',
    'Ç' => 'C', 'Ć' => 'C', 'Č' => 'C', 'Ĉ' => 'C', 'Ċ' => 'C', 'Ď' => 'D', 'Đ' => 'D',
    'Ð' => 'D', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ē' => 'E',
    'Ę' => 'E', 'Ě' => 'E', 'Ĕ' => 'E', 'Ė' => 'E', 'Ĝ' => 'G', 'Ğ' => 'G',
    'Ġ' => 'G', 'Ģ' => 'G', 'Ĥ' => 'H', 'Ħ' => 'H', 'Ì' => 'I', 'Í' => 'I',
    'Î' => 'I', 'Ï' => 'I', 'Ī' => 'I', 'Ĩ' => 'I', 'Ĭ' => 'I', 'Į' => 'I',
    'İ' => 'I', 'Ĳ' => 'IJ', 'Ĵ' => 'J', 'Ķ' => 'K', 'Ł' => 'K', 'Ľ' => 'K',
    'Ĺ' => 'K', 'Ļ' => 'K', 'Ŀ' => 'K', 'Ñ' => 'N', 'Ń' => 'N', 'Ň' => 'N',
    'Ņ' => 'N', 'Ŋ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O',
    'Ö' => 'Oe', '&Ouml;' => 'Oe', 'Ø' => 'O', 'Ō' => 'O', 'Ő' => 'O', 'Ŏ' => 'O',
    'Œ' => 'OE', 'Ŕ' => 'R', 'Ř' => 'R', 'Ŗ' => 'R', 'Ś' => 'S', 'Š' => 'S',
    'Ş' => 'S', 'Ŝ' => 'S', 'Ș' => 'S', 'Ť' => 'T', 'Ţ' => 'T', 'Ŧ' => 'T',
    'Ț' => 'T', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue', 'Ū' => 'U',
    '&Uuml;' => 'Ue', 'Ů' => 'U', 'Ű' => 'U', 'Ŭ' => 'U', 'Ũ' => 'U', 'Ų' => 'U',
    'Ŵ' => 'W', 'Ý' => 'Y', 'Ŷ' => 'Y', 'Ÿ' => 'Y', 'Ź' => 'Z', 'Ž' => 'Z',
    'Ż' => 'Z', 'Þ' => 'T', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
   'ä' => 'ae', '&auml;' => 'ae', 'å' => 'a', 'ā' => 'a', 'ą' => 'a', 'ă' => 'a',
    'æ' => 'ae', 'ç' => 'c', 'ć' => 'c', 'č' => 'c', 'ĉ' => 'c', 'ċ' => 'c',
    'ď' => 'd', 'đ' => 'd', 'ð' => 'd', 'è' => 'e', 'é' => 'e', 'ê' => 'e',
    'ë' => 'e', 'ē' => 'e', 'ę' => 'e', 'ě' => 'e', 'ĕ' => 'e', 'ė' => 'e',
    'ƒ' => 'f', 'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g', 'ĥ' => 'h',
    'ħ' => 'h', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i',
    'ĩ' => 'i', 'ĭ' => 'i', 'į' => 'i', 'ı' => 'i', 'ĳ' => 'ij', 'ĵ' => 'j',
    'ķ' => 'k', 'ĸ' => 'k', 'ł' => 'l', 'ľ' => 'l', 'ĺ' => 'l', 'ļ' => 'l',
    'ŀ' => 'l', 'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ŉ' => 'n',
    'ŋ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe',
    '&ouml;' => 'oe', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o', 'ŏ' => 'o', 'œ' => 'oe',
    'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r', 'š' => 's', 'ù' => 'u', 'ú' => 'u',
    'û' => 'u', 'ü' => 'ue', 'ū' => 'u', '&uuml;' => 'ue', 'ů' => 'u', 'ű' => 'u',
    'ŭ' => 'u', 'ũ' => 'u', 'ų' => 'u', 'ŵ' => 'w', 'ý' => 'y', 'ÿ' => 'y',
    'ŷ' => 'y', 'ž' => 'z', 'ż' => 'z', 'ź' => 'z', 'þ' => 't', 'ß' => 'ss',
    'ſ' => 'ss', 'ый' => 'iy', 'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G',
    'Д' => 'D', 'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I',
    'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
    'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F',
    'Х' => 'H', 'Ц' => 'C', 'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SCH', 'Ъ' => '',
    'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA', 'а' => 'a',
    'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo',
    'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l',
    'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's',
    'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
    'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e',
    'ю' => 'yu', 'я' => 'ya', 'ț' => 't', 'ţ' => 't',
);

	return str_replace(array_keys($replace), $replace, $str);
}

function get_resp($info) {
    if (!isset($info['params']) || count($info['params']) == 0) {
        $url = $info['base_url'];
        fputs(STDERR, $url . "\n");
        return http($url);
    } else {
        if (!isset($info['method']) || $info['method'] == "GET" || $info['method'] == "get") {
            array_walk($info['params'], function (&$v, $k) {
                $v = $k . "=" . $v; 
            }); 
            $param = join("&", array_values($info['params']));
            $url = $info['base_url'] . "?" . $param;
            fputs(STDERR, $url . "\n");
            return http($url);
        } else if($info['method'] == "POST" || $info['method'] == "post") {
            fputs(STDERR, $info['base_url'] . "?" . json_encode($info['params']) . "\n");
            return http($info['base_url'], $info['params']);
        }   
    }   
}

// 获取该日期最近一个星期一。如果是周六、日算到下周一
function get_monday($date) {
	$date = date('Y-m-d', strtotime($date . " +3 days"));
	$date = date('Y-m-d', strtotime($date . " last Monday"));
	return $date;
}
