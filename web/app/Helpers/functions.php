<?php
use Illuminate\Support\Facades\Redis;

function is_test_account() {
//	return Auth::check() && in_array(Auth::id(), [2999, 24205, 1579, 2432, 2370, 24194]);
//	return Auth::check() && in_array(Auth::id(), [2999]);
	return false;
}

function getIP(){
	global $ip;
	if (getenv("HTTP_CLIENT_IP"))
		$ip = getenv("HTTP_CLIENT_IP");
	else if(getenv("HTTP_X_FORWARDED_FOR"))
		$ip = getenv("HTTP_X_FORWARDED_FOR");
	else if(getenv("REMOTE_ADDR"))
		$ip = getenv("REMOTE_ADDR");
	else 
		$ip = "Unknown";
	return preg_replace('/,.*$/', '', $ip);
}

function name_to_lower($name) {
	return strtolower(preg_replace('/[-\'\.\(\) ]/', "", $name));
}

function swap(&$a, &$b) {
	$tmp = $a;
	$a = $b;
	$b = $tmp;
}

function translate($type, $idx, $uc = false) {

	if ($type == "longname" || $type == "shortname") {
		$name = __($type . '.' . name_to_lower(str_replace(".", "", $idx)));
	} else {
		$name = __($type . '.' . str_replace(".", "", $idx));
	}

	if (strpos($name, $type) === 0) {
		if ($uc)
			return mb_strtoupper($idx);
		else
			return $idx;
	} else {
		return $name;
	}
}

function transfer2symbol($str, $type = "square_neg") {
	return join("", array_map(function ($d) use($type) {return Config::get(join(".", ['symbol', $type, $d]));}, str_split($str)));
}

function reviseSurfaceWithIndoor($sfc) {
	$sfc = str_replace(" (Indoor)", "(I)", $sfc);
	$sfc = preg_replace('/ \(.*\).*$/', '', $sfc);
	return trim($sfc);
}

function reviseSurfaceWithoutIndoor($sfc) {
	$sfc = preg_replace('/\(.*\).*$/', '', $sfc);
	return trim($sfc);
}

function translate_tour($tour, $level = null) {

	$match = NULL;
	preg_match('/\/([^\/]*K|FU)|( Q)?( Alt#[0-9]+)?$/', $tour, $match);
	$tour = preg_replace('/\/([^\/]*K|FU)|( Q)?( Alt#[0-9]+)?$/', '', $tour);
	if ($level != "FC" && $level != "ITF") {
		$tour = trim(preg_replace('/[\d]*$/', '', $tour));
	}
	$name = __('tourname.' . str_replace(".", "", strtolower($tour)));
	if (strpos($name, 'tourname.') === 0) {
		$name = ucwords($tour);
	}
	$name .= @$match[0];
	return $name;

}

function slash($url) {
	return preg_replace('/https?:\/\/coric\.top/', "", $url);
}

function get_tour_logo_by_id_type_name($id, $type, $city = "", $title = "", $year = 2018) {

	if ($id == "M993" || $id == "AO" || $id == "0580" || strtolower($city) == "australian open") {
		return Config::get('const.logo')('GS-AO');
	} else if ($id == "M996" || $id == "RG" || $id == "0520" || strtolower($city) == "french open" || strtolower($city) == "roland garros") {
		return Config::get('const.logo')('GS-RG');
	} else if ($id == "M995" || $id == "WC" || $id == "0540" || strtolower($city) == "wimbledon") {
		return Config::get('const.logo')('GS-WC');
	} else if ($id == "M994" || $id == "UO" || $id == "0560" || strtolower($city) == "us open") {
		return Config::get('const.logo')('GS-UO');
	} else if ($id == "M990" || $id == "DC" || $type == "DC") {
		return Config::get('const.logo')('ITF-Daviscup');
	} else if ($id == "M991" || $id == "FC" || $type == "FC") {
		return Config::get('const.logo')('ITF-Fedcup');
	} else if ($id == "M998" || $id == "OL" || $type == "OL") {
		return Config::get('const.logo')('ITF-OL');
	} else if ($id == "0605") {
		return Config::get('const.logosvg')('ATP-Final-2019');
	} else if ($id == "7696") {
		return Config::get('const.logosvg')('ATP-Nextgen-2019');
	} else if ($id == "9210") {
		return Config::get('const.logo')('ATP-LVR');
	} else if ($id == "8888") {
		return Config::get('const.logo')('ATP-CUP');
	} else if ($id == "0808") {
		return Config::get('const.logo')('WTA-Final');
	} else if ($id == "1081") {
		return Config::get('const.logo')('WTA-Elite');
	} else if (strpos($type, "ATP Challengers") !== false || strpos($type, "CH") !== false) {
		return Config::get('const.logo')('ATP-Challenger-2019');
	} else if ($type == "WTA 125K Series" || $type == "125K") {
		return Config::get('const.logo')('WTA-125K');
	} else if (preg_match('/^J[AB1-5][1-5]?$/', $type)) {
		return Config::get('const.logo')('ITF-junior-2019');
	} else if (strpos($type, "ITF") !== false) {
		if (preg_match('/M-FU-/', $id) || preg_match('/M-ITF-/', $id) || preg_match('/ F[0-9]{1,2}$/', $title) || preg_match('/ M-FU-/', $title) || preg_match('/ M-ITF-/', $title)) {
			return Config::get('const.logo')('ITF-men-2019');
		} else {
			return Config::get('const.logo')('ITF-women-2019');
		}
	} else if ($type == "W100" || $type == "W80" || $type == "W60" || $type == "W50" || $type == "W25" || $type == "W15") {
		return Config::get('const.logo')('ITF-women-2019');
	} else if ($type == "M25" || $type == "M15" || $type == "FU") {
		return Config::get('const.logo')('ITF-men-2019');
	} else {
		if ($type == "WTA1000") return Config::get('const.logo')('WTA-1000');
		else if ($type == "WTA500") return Config::get('const.logo')('WTA-500');
		else if ($type == "WTA250") return Config::get('const.logo')('WTA-250');
		else if ($type == "WTA125") return Config::get('const.logo')('WTA-125');
		else if (preg_match('/1000/', $type)) return Config::get('const.logo')('ATP-1000-2019');
		else if (preg_match('/500/', $type)) return Config::get('const.logo')('ATP-500-2019');
		else if (preg_match('/250/', $type)) return Config::get('const.logo')('ATP-250-2019');
		else if (preg_match('/P(remier )?M(andatory)?/', $type)) return Config::get('const.logo')('WTA-PM');
		else if (preg_match('/P(remier )?5/', $type)) return Config::get('const.logo')('WTA-P5');
		else if (preg_match('/P(remier|700)/', $type)) return Config::get('const.logo')('WTA-P700');
		else if (preg_match('/Int(ernational)?/', $type)) return Config::get('const.logo')('WTA-Int');
		else if ($type == 'T1') return Config::get('const.logo')('WTA-T1');
		else if ($type == 'T2') return Config::get('const.logo')('WTA-T2');
		else if ($type == 'T3') return Config::get('const.logo')('WTA-T3');
		else if ($type == 'T4') return Config::get('const.logo')('WTA-T4');
		else if ($type == 'T5') return Config::get('const.logo')('WTA-T5');
		else if ($type == 'CSS') return Config::get('const.logo')('ATP-1000-2019'); // 1000, 90~99
		else if ($type == 'MS') return Config::get('const.logo')('ATP-1000-2019');	// 1000, 00~08
		else if ($type == 'ISG') return Config::get('const.logo')('ATP-500-2019'); // 500, 04~08
		else if ($type == 'CS') return Config::get('const.logo')('ATP-500-2019'); // 500, 00~03
		else if ($type == 'CSD') return Config::get('const.logo')('ATP-500-2019'); // 500, 90~99
		else if ($type == 'IS') return Config::get('const.logo')('ATP-250-2019'); // 250, 04~08
		else if ($type == 'WS') return Config::get('const.logo')('ATP-250-2019'); // 250, 93~03
		else if ($type == 'WSD') return Config::get('const.logo')('ATP-250-2019'); // 250, 90-92
		else if ($type == 'WSF') return Config::get('const.logo')('ATP-250-2019'); // 250, 90-92
		else if ($type == 'GP') return Config::get('const.logosvg')('ATP-2019'); // atp
		else if ($type == 'ATP') return Config::get('const.logosvg')('ATP-2019'); // atp
		else if ($type == 'WCT') return Config::get('const.logo')('WCT'); // wct
		else if ($type == 'WT' || $type == 'YEC') return Config::get('const.logo')('WTA'); // wta
		else if ($type == 'WTA') return Config::get('const.logo')('WTA'); // wta
		else if ($type == 'GSC') return Config::get('const.logo')('ITF-GSC'); 
		else return '';
	}
}

function get_flag($ioc) {
	if ($ioc === "" || $ioc === NULL || strpos($ioc, "|") !== false) return '';
	else if (preg_match('/^\d+$/', $ioc)) return '<img class=cImgPlayerFlag data-original="' . url(env('CDN') . '/images/login/' . Config::get('const.TYPE2STRING.' . $ioc) . '.png') . '" />';
	else return '<img class=cImgPlayerFlag data-original="' . url(env('CDN') . '/images/flag_svg/' . $ioc . '.svg') . '" />';
}
function get_flag_url($ioc) {
	if ($ioc === "" || $ioc === NULL) return '';
	else if (preg_match('/^\d+$/', $ioc)) return url(env('CDN') . '/images/login/' . Config::get('const.TYPE2STRING.' . $ioc) . '.png');
	else return url(env('CDN') . '/images/flag_svg/' . $ioc . '.svg');
}

function get_headshot($sex, $file) {
	if (!$file) return url(env('CDN') . '/images/' . $sex . '_headshot/' . $sex. 'player.jpg');
	else if (strpos($file, "http") === 0) return $file;
	else return url(env('CDN') . '/images/' . $sex . '_headshot/' . $file);
}

function supper_score($score) {
	return $score;
//	return str_replace('(', '<sup>', str_replace(')', '</sup>', $score));
}

function first_name_shortenlize($first) { 
	$first = preg_replace('/([A-Z])/', ' \1', $first);
    $first = preg_replace('/[^a-z ]/', ' ', strtolower($first));
	$first = preg_replace('/([bpmfdtlkjqxzcsyw])/', ' \1', $first);
	$first = preg_replace('/n([aioeuv])/', ' n\1', $first);
	$first = preg_replace('/([^n])g/', '\1 g', $first);
	$first = preg_replace('/([^zcs])h/', '\1 h', $first);
	$first = preg_replace('/([^ejs])r/', '\1 r', $first);
	$first = preg_replace('/  */', ' ', trim($first));
	return ucwords($first);
} 
function rename2short($first, $last, $ioc = NULL) {

	if (strpos(strtolower($first . $last), "bye") !== false) return translate('shortname', 'Bye');
	if (strpos(strtolower($first . $last), "qualifier") !== false) return translate('shortname', 'Qualifier');
	if (strpos(strtolower($first . $last), "virus") !== false) return translate('shortname', 'Corona Virus');

	$last = replace_letters($last);
	$first = replace_letters($first);
	$name = __('shortname.' . name_to_lower($first . ' ' . $last));
	if (strpos($name, 'shortname.') === false) return $name;

	if (in_array($last, ["Pliskova", "Rodionova", "Plipuech", "Ratiwatana", "Gullikson"])) {
		$first = substr($first, 0, 2);
	} else if (in_array($last, ["Lu"])) {
		$first = preg_replace('/[a-z]+$/', '', $first);
	} else if ($last == "Wang" && strpos($first, "Xi") !== false) {
		$first = ucwords(str_replace(" ", "", $first));
	} else if ($last == "Wang" && strpos($first, "Ya") !== false) {
		$first = substr($first, 0, 3);
	} else if ($last == "Yang" && substr($first, 0, 1) == "Z") {
		$first = substr($first, 0, 2);
	} else {
		if ($ioc == "CHN") {
			$first = first_name_shortenlize($first);
		}
		$first = preg_replace('/[ \.\'-]/', '', $first);
		$first = preg_replace('/[^A-Z]/', '', $first);
		$first = preg_replace('/\.\.*/', '.', $first);
	}

	if (in_array($ioc, ["CHN", "PRK", "KOR", "VIE", "TPE", "HKG"])) {
		return $last . ' ' . $first;
	} else {
		return $first . ' ' . $last;
	}
}

function translate2short($pid, $first = null, $last = null, $ioc = null, $lang = null) {
	if (strtoupper($pid) == "BYE") return translate('shortname', 'Bye');
	else if (strtoupper($pid) == "QUAL") return translate('shortname', 'Qualifier');

	if (preg_match('/^[A-Z0-9]{4}$/', $pid)) {
		$gender = "atp";
	} else if (preg_match('/^[0-9]{5,6}$/', $pid)) {
		$gender = "wta";
	} else {
		$gender = "itf";
	}
	$key = join("_", [$gender, 'profile', $pid]);

	if (!$lang) $lang = App::getLocale();
	$ret = Redis::hmget($key, ['s_en', 's_' . $lang]);
	if (!$ret) return null;

	if ($pid !== null && $ret[1]) {return $ret[1];}
	else if ($pid !== null && $ret[0]) {return $ret[0];}
	else {
		$first = preg_replace('/[ \.\'-]/', '', $first);
		$first = preg_replace('/[^A-Z]/', '', $first);
		if (in_array($ioc, ["CHN", "PRK", "KOR", "VIE", "TPE", "HKG"])) {
			return $last . ' ' . $first;
		} else {
			return $first . ' ' . $last;
		}
	}
}

function rename2long($first, $last, $ioc = NULL) {

	if (strpos(strtolower($first . $last), "bye") !== false) return translate('longname', 'Bye');
	if (strpos(strtolower($first . $last), "qualifier") !== false) return translate('longname', 'Qualifier');

	$last = replace_letters($last);
	$first = replace_letters($first);
	$name = __('longname.' . name_to_lower($first . ' ' . $last));
	if (strpos($name, 'longname.') === false) return $name;

	if (in_array($ioc, ["CHN", "PRK", "KOR", "VIE", "TPE", "HKG"])) {
		return strtoupper($last) . ' ' . $first;
	} else {
		return $first . ' ' . strtoupper($last);
	}
}

function translate2long($pid, $first = null, $last = null, $ioc = null, $lang = null) {
	if (strtoupper($pid) == "BYE") return translate('shortname', 'Bye');
	else if (strtoupper($pid) == "QUAL") return translate('shortname', 'Qualifier');

	if (preg_match('/^[A-Z0-9]{4}$/', $pid)) {
		$gender = "atp";
	} else if (preg_match('/^[0-9]{5,6}$/', $pid)) {
		$gender = "wta";
	} else {
		$gender = "itf";
	}
	$key = join("_", [$gender, 'profile', $pid]);

	if (!$lang) $lang = App::getLocale();
	$ret = Redis::hmget($key, ['l_en', 'l_' . $lang]);
	if (!$ret) return null;

	if ($pid !== null && $ret[1]) {return $ret[1];}
	else if ($pid !== null && $ret[0]) {return $ret[0];}
	else {
		if (in_array($ioc, ["CHN", "PRK", "KOR", "VIE", "TPE", "HKG"])) {
			return strtoupper($last) . ' ' . $first;
		} else {
			return $first . ' ' . strtoupper($last);
		}
	}
}

function resetParam($param, $default) {
	if (!isset($param) || !$param) {
		return $default;
	} else {
		return $param;
	}
}

function replace_letters($str){
	$replace = array(
		'&lt;' => '', '&gt;' => '', '&#039;' => '', '&amp;' => '',
		'&quot;' => '', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'Ae',
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
		'ю' => 'yu', 'я' => 'ya'
	);

	return str_replace(array_keys($replace), $replace, $str);
}

function get_channel_from_id($id) {
	if ($id == 0) {
		return '贴吧';
	} else if ($id == 1) {
		return '微博';
	} else if ($id == 2) {
		return '网球派';
	} else if ($id == 3) {
		return '微信';
	} else if ($id == 4) {
		return '微信(haolan)';
	} else if ($id == 5) {
		return '微信(pie)';
	} else if ($id == 6) {
		return '微信(wh)';
	} else if ($id == 7) {
		return '脸书';
	} else if ($id == 8) {
		return '谷歌';
	} else if ($id == 9) {
		return '微信(wilson)';
	} else if ($id == 10) {
		return '微信(wilson)';
	} else {
		return '暂无';
	}
	return '';
}

function phpencrypt($data, $key = "我要入肉哭帅哥")  
{  
    $key    =   md5($key);  
    $x      =   0;  
    $len    =   strlen($data);  
    $l      =   strlen($key);  
	$char = $str = "";
    for ($i = 0; $i < $len; $i++)  
    {  
        if ($x == $l)   
        {  
            $x = 0;  
        }  
        $char .= $key{$x};  
        $x++;  
    }  
    for ($i = 0; $i < $len; $i++)  
    {  
        $str .= chr(ord($data{$i}) + (ord($char{$i})) % 256);  
    }  
    return base64_encode($str);  
}  

function phpdecrypt($data, $key = "我要入肉哭帅哥")  
{  
    $key = md5($key);  
    $x = 0;  
    $data = base64_decode($data);  
    $len = strlen($data);  
    $l = strlen($key);  
	$char = $str = "";
    for ($i = 0; $i < $len; $i++)  
    {  
        if ($x == $l)   
        {  
            $x = 0;  
        }  
        $char .= substr($key, $x, 1);  
        $x++;  
    }  
    for ($i = 0; $i < $len; $i++)  
    {  
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1)))  
        {  
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));  
        }  
        else  
        {  
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));  
        }  
    }  
    return $str;  
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

function Rand_IP(){
    $ip2id= round(rand(600000, 2550000) / 10000); //第一种方法，直接生成
    $ip3id= round(rand(600000, 2550000) / 10000);
    $ip4id= round(rand(600000, 2550000) / 10000);
    //下面是第二种方法，在以下数据中随机抽取
    $arr_1 = array("218","218","66","66","218","218","60","60","202","204","66","66","66","59","61","60","222","221","66","59","60","60","66","218","218","62","63","64","66","66","122","211");
    $randarr= mt_rand(0,count($arr_1)-1);
    $ip1id = $arr_1[$randarr];
    return $ip1id.".".$ip2id.".".$ip3id.".".$ip4id;
}

function http($url, $post = NULL, $cookie = NULL){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_HEADER, 0); 
	if ($cookie) {
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

function revise_gs_score($status, $s1, $s2, $reverse = false, $is_dcpk = false) {
	// reverse表示产出比分的顺序。false表示正常顺序，true表示反过来，负者在前

	if (!$status) return '';
	if (in_array($status, ['L', 'M'])) {
		return 'W/O';
	} else if (in_array($status, ['H', 'I'])) {
		$suffix = 'Ret.';
	} else if (in_array($status, ['J', 'K'])) {
		$suffix = 'Def.';
	} else {
		$suffix = '';
	}
	if ((in_array($status, ['G', 'I', 'K']) && !$reverse) || (in_array($status, ['F', 'H', 'J']) && $reverse)) {
		$stmp = $s1;
		$s1 = $s2;
		$s2 = $stmp;
	}

	$score = [];
	if (!$is_dcpk) {
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

function clear_content($content){
	return preg_replace('/[\\\\\'\"]/', '', $content);
}

function return_content($openid, $me, $create_time, $ret_content){
	return "<xml>
 <ToUserName><![CDATA[". $openid ."]]></ToUserName>
 <FromUserName><![CDATA[". $me ."]]></FromUserName> 
 <CreateTime>". time(NULL) . "</CreateTime>
 <MsgType><![CDATA[text]]></MsgType>
 <Content><![CDATA[". $ret_content ."]]></Content>
 </xml>";
}

function BKDRHash($str) {
    $seed = 131; // 31 131 1313 13131 131313 etc.. 
    $hash = 0;  
    $cnt = strlen($str); 
    for ($i = 0; $i < $cnt; $i++) {
        $hash = ((floatval($hash * $seed) & 0x7FFFFFFF) + ord($str[$i])) & 0x7FFFFFFF;
    }   
    return ($hash & 0x7FFFFFFF); 
}

function _strlen($str){
	preg_match_all("/./us", $str, $matches);  
	return count(current($matches));
}

function get_icon($name) {
	return "<i class=\"iconfont\">" . Config::get('iconfont.' . $name) . "</i>";
}

function get_ori_id($id) {
	return strtoupper(str_replace('itf', '', str_replace('atp', '', preg_replace('/wta0*/', '', strtolower($id)))));
}

// 此函数返回值一定为数组，id到头像的映射
function get_patch_headshots($gender, $id_arr) {
	if (!in_array($gender, ['atp', 'wta'])) return null;

	if (!is_array($id_arr)) {
		$cmd = "grep \"^$id_arr	\" " . join("/", [Config::get('const.root'), $gender, "player_headshot"]) . " | head -1 | cut -f3";
		unset($r); exec($cmd, $r);
		if ($r) {
			return [$id_arr => get_headshot($gender, $r[0])];
		} else {
			return [$id_arr => get_headshot($gender, $gender . "player.jpg")];
		}
	} else {
		$cmd = "grep -E \"" . join("|", $id_arr) . "\" " . join("/", [Config::get('const.root'), $gender, "player_headshot"]) . " | cut -f1,3";
		unset($r); exec($cmd, $r);
		$ret = [];
		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				if (in_array($arr[0], $id_arr)) {
					$ret[$arr[0]] = get_headshot($gender, $arr[1]);
				}
			}
		}
		foreach ($id_arr as $k) {
			if (!isset($ret[$k])) {
				$ret[$k] = get_headshot($gender, $gender . "player.jpg");
			}
		}
		return $ret;
	}
}

// 此函数返回值一定为数组，id到信息的映射
function get_patch_infos($gender, $id_arr) {
	if (!in_array($gender, ['atp', 'wta'])) return null;

	if (!is_array($id_arr)) {
		$cmd = "grep \"^$id_arr	\" " . join("/", [Config::get('const.root'), $gender, "player_bio"]) . " | head -1 | cut -f5,7,39,40";
		unset($r); exec($cmd, $r);
		if ($r) {
			$arr = explode("\t", $r[0]);
			return [$id_arr => [@$arr[2], @$arr[3], @$arr[0], @$arr[1]]];
		} else {
			return [$id_arr => ["", "", "", ""]];
		}
	} else {
		$cmd = "grep -E \"" . join("|", $id_arr) . "\" " . join("/", [Config::get('const.root'), $gender, "player_bio"]) . " | cut -f1,5,7,39,40";
		unset($r); exec($cmd, $r);
		$ret = [];
		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				if (in_array($arr[0], $id_arr)) {
					$ret[$arr[0]] = [@$arr[3], @$arr[4], @$arr[1], @$arr[2]];
				}
			}
		}
		foreach ($id_arr as $k) {
			if (!isset($ret[$k])) {
				$ret[$k] = ["", "", "", ""];
			}
		}
		return $ret;
	}
}

// 获取portrait
function fetch_player_image($gender, $pid, $size, $default = false) {
	if ($default) {
		if ($size == "portrait") {
			return url(env('CDN') . '/images/' . $gender . '_' . $size . '/' . $gender . 'player.png');
		} else {
			return url(env('CDN') . '/images/' . $gender . '_' . $size . '/' . $gender . 'player.jpg');
		}
	} else {
		$cmd = "grep '^$pid\t' " . join('/', [Config::get('const.root'), $gender, "player_" . $size]) . " | cut -f3";
		unset($r); exec($cmd, $r);
		if ($r && isset($r[0])) {
			if (strpos($r[0], "http") === 0) {
				return $r[0];
			} else {   
				return url(env('CDN') . '/images/' . $gender . '_' . $size . '/' . preg_replace('/^.*\//', '', $r[0]));
			}
		} else {
			return null;
		}
	}
}

/* ----------------------头像处理系列-------------------------*/
function fetch_portrait($pid, $gender = "atp") {
	// gender = atp or wta
	// 返回值 [0] 表示是否是自己头像，[1]表示头像url
	$default = url(env('CDN') . '/images/' . $gender . '_portrait/' . $gender . 'player.png');
	if (!$pid) return [false, $default];
	$pt = Redis::hmget(join("_", [$gender, "profile", $pid]), 'pt');
	if (!$pt || !$pt[0]) return [false, $default];
	if (strpos($pt[0], "http") !== false) return [true, $pt[0]];
	else return [true, url(env('CDN') . '/images/' . $gender . '_portrait/' . $pt[0])];
}

function fetch_headshot($pid, $gender = "atp") {
	// gender = atp or wta
	// 返回值 [0] 表示是否是自己头像，[1]表示头像url
	$default = url(env('CDN') . '/images/' . $gender . '_headshot/' . $gender . 'player.jpg');
	if (!$pid) return [false, $default];
	$hs = Redis::hmget(join("_", [$gender, "profile", $pid]), 'hs');
	if (!$hs || !$hs[0]) return [false, $default];
	if (strpos($hs[0], "http") !== false) return [true, $hs[0]];
	else return [true, url(env('CDN') . '/images/' . $gender . '_headshot/' . $hs[0])];
}

/* ----------------------个人信息系列-------------------------*/
function fetch_player_info($pid, $gender = "atp") {
	// gender = atp or wta
	if (!$pid) return null;
	$info = Redis::hmget(join("_", [preg_match('/^\d{7,10}$/', $pid) ? "itf" : $gender, "profile", $pid]), 'first', 'last', 'ioc', 'birthday', 'hs', 'pt', 'birthplace', 'residence', 'hand', 'backhand', 'turnpro', 'height', 'height_imp', 'weight', 'weight_imp', 'prize_c', 'prize_y', 'title_s_c', 'title_s_y', 'title_d_c', 'title_d_y');
	if (!$info) return null;

	$headshot = $info[4];
	$portrait = $info[5];
	$has_hs = true;
	$has_pt = true;
	if (!$headshot) {
		$has_hs = false;
		$headshot = url(env('CDN') . '/images/' . $gender . '_headshot/' . $gender . 'player.jpg');
	} else if (strpos($headshot, "http") === false) {
		$headshot = url(env('CDN') . '/images/' . $gender . '_headshot/' . $headshot);
	}
	if (!$portrait) {
		$has_pt = false;
		$portrait = url(env('CDN') . '/images/' . $gender . '_portrait/' . $gender . 'player.png');
	} else if (strpos($portrait, "http") === false) {
		$portrait = url(env('CDN') . '/images/' . $gender . '_portrait/' . $portrait);
	}
	return [
		"pid" => $pid,
		"first" => $info[0],
		"last" => $info[1],
		"ioc" => $info[2],
		"birthday" => $info[3],
		"headshot" => $headshot,
		"portrait" => $portrait,
		"hasHeadshot" => $has_hs,
		"hasPortrait" => $has_pt,
		"birthplace" => $info[6],
		"residence" => $info[7],
		"hand" => [$info[8], $info[9]],
		"turnpro" => $info[10],
		"height" => [$info[11], $info[12]], // 前者是公制，后者是英制
		"weight" => [$info[13], $info[14]], // 前者是公制，后者是英制
		"prize" => [$info[15], $info[16]],
		"titleS" => [$info[17], $info[18]],
		"titleD" => [$info[19], $info[20]],
	];
}

function fetch_rank($pid, $gender = "atp", $sd = "s") {
	if (!$pid) return "-";
	$cmd = "grep \"^$pid	\" " . join("/", [Config::get('const.root'), "data", "rank", $gender, $sd, "current"]) . " | cut -f3";
	unset($r); exec($cmd, $r);
	if ($r) return $r[0]; else return "-";
}
