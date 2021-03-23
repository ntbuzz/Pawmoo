<?php
// separated from appLibs, require in here.
require_once('markdown.php');
//==============================================================================
// Divid by INTEGER, before PHP 7.0
if (!function_exists('intdiv')) {
    function intdiv($var,$base) {
        return ($var - ($var % $base)) / $base;
    }
}
//==============================================================================
// get file extention, return string is ".ext"
function extract_extension($fn) {
    $nn = strrpos($fn,'.');
    return ($nn === FALSE) ? '' :
            strtolower(substr($fn,$nn+1));
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
    $path = path_simplify($path);
    $nn = strrpos($path,'/');
    if($nn === FALSE) {
        $fn = $path;
        $path = '/';
    } else {
        $fn = substr($path,$nn+1);
        $path = substr($path,0,$nn+1);
    }
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
// string length limitation
function strlen_limit($str,$maxlen) {
    return (mb_strlen($str) > $maxlen) ? mb_substr($str,0,$maxlen)."..." : $str;
}
//==============================================================================
// Check the existence of the destination folder and move the file
function file_move($src,$dest){
    list($path,$fn) = extract_path_filename($dest);
    if(!file_exists($path)) mkdir($path,0777,true);     // recursive mkdir
    if(rename($src,$dest)) {
        chmod($dest,0664);      // permission change
        return true;
    } else return false;
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
// judgement boolean FALSE
function is_bool_false($bool) {
    $bool = strtolower(trim($bool,"'"));
    foreach(['','0','f','false',0,NULL] as $val) {
        if($bool === $val) return TRUE;
    }
    return FALSE;
}
//==============================================================================
// check for protocol or label or query
function get_protocol($href) {
    foreach(['http://','https://','ftp://','file://'] as $pp) {
        if(mb_substr($href,0,strlen($pp))===$pp) return $pp;
    }
    return NULL;
}
//==============================================================================
// Generate HYPER_LINK string
//  http～  Direct URL
// :xxx     /xxx
// /xxx     sysRoot/xxx
// ./xxx    appRoot/modname/xxx
// xxx      appRoot/xxx
// !!xxx    http://SERVER/xxx
// !:xxx    https://SERVER/xxx
function make_hyperlink($lnk,$modname) {
    if(get_protocol($lnk) === NULL) {
        // check on TOP-CHAR
        switch(mb_substr($lnk,0,1)) {
        case '#':
        case '?': break;        // label or query
        case ':': $lnk[0] = '/'; break;
        case '/': $lnk = App::Get_SysRoot($lnk); break;
        case '!':
            $protocols = [ '!!' => 'https://', '!:' => ' http://' ];
            $prf = mb_substr($lnk,0,2);
            $ref = mb_substr($lnk,2);
            if(array_key_exists($prf,$protocols)) {
                $lnk = $protocols[$prf] . App::$SysVAR['SERVER'] . $ref;
            } else $lnk = $ref;
            break;
        default:
            if(mb_substr($lnk,0,2) === './') {
                $lnk = substr_replace($lnk, strtolower($modname), 0, 1);
                $lnk = App::Get_AppRoot($lnk);
            } else  {
                $lnk = App::Get_AppRoot($lnk);
            }
        }
    }
    return str_replace(['%','"',"'",'+'], ['%25','%22','%27','%2B'], trim($lnk));
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
    $key_string = SESSION_PREFIX;
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method_name));
    return openssl_encrypt($str,$method_name,$key_string,0,$iv);
}
