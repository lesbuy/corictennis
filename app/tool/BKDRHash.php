<?php

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
