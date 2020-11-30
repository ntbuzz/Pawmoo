<?php
//==============================================================================
// ARRAY first key , before PHP 7.3
if (!function_exists('array_key_first')) {
    function array_key_first(array $arr) {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}
//==============================================================================
// Divid by INTEGER, before PHP 7.0
if (!function_exists('intdiv')) {
    function intdiv($var,$base) {
        return ($var - ($var % $base)) / $base;
    }
}
//==============================================================================
// first key-value pair for associative arrays
function array_first_item($arr) {
    if(!empty($arr)) {
        foreach($arr as $key => $val) {
            return [$key,$val];
        }
    }
    return ['',''];
}
//==============================================================================
//  To compensate array, fixed count
function array_alternative($a,$max = 0, $b = []) {
    $n = count($b);
    if($max === 0) $max = $n;
    else if($n < $max) $b += array_fill($n,$max - $n,NULL);
    else $b = array_slice($b,0,$max);
    foreach($b as $key => $val) {
        if(empty($a[$key])) $a[$key] = $val;
    }
    return $a;
}
//==============================================================================
// get file extention, return string is ".ext"
function extract_extension($fn) {
    $nn = strrpos($fn,'.');
    return ($nn === FALSE) ? '' :
            substr($fn,$nn+1);
}
//==============================================================================
// extract file name, returned file array [ filename, extention ]
function extract_base_name($fn) {
    $nn = strrpos($fn,'.');
    if($nn === FALSE) $nn = strlen($fn);
    $ext = substr($fn,$nn+1);
    $fn = substr($fn,0,$nn);
    return array($fn,$ext);
}
//==============================================================================
// extract file path, returned path array [ path, filename, extention ]
function extract_path_file_ext($path) {
    list($path,$fn) = extract_path_filename($path);
    list($fn,$ext) = extract_base_name($fn);
    return array($path,$fn,$ext);
}
//==============================================================================
// extract file path, returned path array [ path, filename ]
function extract_path_filename($path) {
    if(mb_substr($path,-1) == '/') {
        $path = substr($path,0,strlen($path)-1);
    }
    $nn = strrpos($path,'/');
    if($nn === FALSE) {
        $fn = $path;
        $path = '';
    } else {
        $fn = substr($path,$nn+1);
        $path = substr($path,0,$nn+1);
    }
    if(mb_substr($path,-1) !== '/') $path .= '/';
    return array($path,$fn);
}
//==============================================================================
// string fixed lenght 
// $w > 0 = left fixed, $w < 0 = right fixed
function str_fixwidth($exp,$pad,$w) {
    if($w < 0) {
        $pp = str_repeat ($pad,-$w);
        return substr("{$pp}{$exp}",$w);
    }
    $pp = str_repeat ($pad,$w);
    return substr("{$exp}{$pp}",0,$w);
}
//==============================================================================
// Check the existence of the destination folder and move the file
function file_move($src,$dest){
    list($path,$fn) = extract_path_filename($dest);
    if(!file_exists($path)) mkdir($path);
    return rename($src,$dest);
}
//==============================================================================
// convert file size string
function byte_format($size){
	$units = array(' KB', ' MB', ' GB', ' TB', ' PB');
	$digits = ($size == 0) ? 0 : floor( log($size, 1000) );
	$over = false;
	$max_digit = count($units) -1 ;
	if($digits == 0){
		$num = $size;
	} else if(!isset($units[$digits])) {
		$num = $size / (pow(1000, $max_digit));
		$over = true;
	} else {
		$num = $size / (pow(1000, $digits));
	}
	$num = round($num,1);
	return ($over) ? $num . $units[$max_digit] : $num . $units[$digits];
}

//==============================================================================
// strpos for array version
function instr_array($str,$hayz) {
    foreach($hays as $val) {
        if(strpos($str,val) !== false) return TRUE;
    }
    return FALSE;
}
//==============================================================================
// Array depth calculation, short priority 1-row version
function array_depth($a, $c = 0) {
    return (is_array($a) && count($a))
          ? max(array_map("array_depth", $a, array_fill(0, count($a), ++$c)))
          : $c;
  }
//==============================================================================
// alternative array_merge(), Overwrite existing index elements
function array_override($a, $b) {
    foreach($b as $key => $val) $a[$key] = $val;
    return $a;
  }
//==============================================================================
// Convert text to HTML
function text_to_html($atext) {
    return nl2br(htmlspecialchars($atext));
}
//==============================================================================
// HTML tag output, by special char will be escaped.
function echo_safe($atext,$is_safe = TRUE) {
    echo ($is_safe) ? htmlspecialchars($atext) : $atext;
}
//==============================================================================
// escape char for JSON value
function json_escape($a) {
    $vv = str_replace(["\\","/","\r\n", "\r", "\n","\"","\t"],["\\\\","\\/","\\n","\\n","\\n","\\\"","\\t"], $a);
    return $vv;
}
//==============================================================================
// escape for controll char
function control_escape($a) {
    if($a === TRUE) return 'TRUE';
    if($a === FALSE) return 'FALSE';
    return str_replace(["\r\n", "\r", "\n","\t"],["\\r\\n","\\r","\\n","\\t"], $a);
}
//==============================================================================
// text line split by NL char, and trim-space each line
function text_line_split($del,$txt,$trim = FALSE) {
    $array = array_values(
            array_filter(
                array_map(function($a) {return trim(preg_replace('/\s+/', ' ', str_replace('　',' ',$a)));},
                    explode($del, $txt)
            ), ($trim) ? 'strlen' : function($a) { return TRUE;}
        ));
    return $array;
}
//==============================================================================
// array value concatinate to TEXT
function array_to_text($array,$sep = "\n", $in_key = TRUE) {
    $dump_text = function ($indent, $items)  use (&$dump_text,&$sep,&$in_key)  {
        $txt = ''; $spc = str_repeat(' ', $indent);
        foreach($items as $key => $val) {
            if(is_array($val)) {
                $txt .= $dump_text($indent+2, $val);
            } else if(is_numeric($key) || $in_key === FALSE) {
                $txt .= "{$spc}{$val}{$sep}";
            } else {
                $txt .= "{$spc}{$key}={$val}{$sep}" ;
            }
        }
        return trim($txt,$sep);
    };
    return (is_array($array)) ? $dump_text(0,$array) : $array;
}
//==============================================================================
// check for protocol
function get_protocol($href) {
    $n = strpos($href,':');
    if($n === FALSE) return NULL;
    return ($n > 3) ? mb_substr($href,0,$n) : NULL;
}
//==============================================================================
// Generate HYPER_LINK string
//  http〜  Direct URL
// :...     /...
// /..     sysRoot/...
// ./...    appRoot/modname/...
// ...      appRoot/...
// !!...    http://SERVER/...
// !:...    https://SERVER/...
function make_hyperlink($lnk,$modname) {
    if(get_protocol($lnk) === NULL) {
		if($lnk[0] === ':') {
            $lnk[0] = '/';
        } else if($lnk[0] === '/') {
			$lnk = App::Get_SysRoot("{$lnk}");
        } else {
            $prf = mb_substr($lnk,0,2);
            $ref = mb_substr($lnk,2);
            if($lnk[0] === '!') {
                $protocols = [ '!!' => 'https://', '!:' => ' http://' ];
                if(array_key_exists($prf,$protocols)) {
                    $lnk = $protocols[$prf] . App::$SysVAR['SERVER'] . $ref;
                } else $lnk = $ref;
            } else if($prf === './') {
                $lnk = substr_replace($lnk, strtolower($modname), 0, 1);
                $lnk = App::Get_AppRoot($lnk);
            } else  $lnk = App::Get_AppRoot($lnk);
		}
    }
    return $lnk;
}
//==============================================================================
// MARKING WORD by SPAN CLASS
function mark_active_words($atext,$word,$class) {
	$ln = array_values(array_map('trim', explode("\n", $atext)));
	$ret = array();
	foreach($ln as $ll) {
    	$ll = preg_replace("/(${word})/i","<span class='{$class}'>\\1</span>", $ll);
		$ret[] = $ll;
	}
	return implode("\n",$ret);
}
//==============================================================================
// password encryption
function passwd_encrypt($str) {
    $method_name = 'AES-256-CBC';
    $key_string = '_minimvc_waffle_map';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method_name));
    return openssl_encrypt($str,$method_name,$key_string,0,$iv);
}
