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
// get int value, and default set
function int_value($digit,$default) {
	$sz = intval($digit);
	return ($sz===0) ? $default : $sz;
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
        chmod($dest,0666);      // permission change
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
// convert file size string
function date_custom($fmt,$tm){
	list($yy,$y,$mm,$dd,$hh,$ii,$ss,$ww) = explode(',',date('Y,y,m,d,H,i,s,w',$tm));
	--$mm;		// array is ZERO origin
	$dateNames = LangUI::get_value('core','.dateNames',true);
	foreach($dateNames as $nm => $str) $$nm = explode(',',$str);
	// dateNames => shortMonth, oldMonth, shortWeek, weekNames
	return str_replace(
	[ '%Y','%y','%m','%M','%O','%d','%H','%i','%s','%w','%W'],
	[ $yy, $y, $mm, $shortMonth[$mm],$oldMonth[$mm], $dd,$hh,$ii,$ss,$shortWeek[$ww],$weekNames[$ww]],
	$fmt);
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
	// controll-code change
    $vv = preg_replace_callback_array([
        "/\\\\+/"				=> function($m) {		// back-slash
            $n = strlen($m[0]);
			return str_repeat("\\", $n*2);
        },
        "/(\r\n|\r|\n|\t|\f)/"		=> function($m) {		// controll-char \r\n\t
			$ctrl = ["\r\n"=>'\n', "\r"=>'\n', "\n"=>'\n', "\t"=>'\t', "\f"=>'\f', "\b"=>'\b'];
			return $ctrl[$m[1]];
        },
        "/[\"\/]/"	=> function($m) {		// not escape " , / 
            return "\\{$m[0]}";
        },
	],$a);
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
// /xxx     sysRoot/xxx			applistion(xxx) exchange
// :xxx     appRoot/xxx			controller(xxx) exchange
// ./xxx    appRoot/modname/xxx	method(xxx) exchange
// xxx      xxx					current path
// !!xxx    https://SERVER/xxx
// !:xxx    http://SERVER/xxx
function make_hyperlink($lnk,$modname=NULL) {
    if(get_protocol($lnk) === NULL) {
        // check on TOP-CHAR
        switch(mb_substr($lnk,0,1)) {
        case '#':
        case '?': break;        // label or query
        case ':': $lnk = App::Get_AppRoot(mb_substr($lnk,1)); break;
        case '/': $lnk = App::Get_SysRoot($lnk); break;
        case '!':
            $protocols = [ '!!' => 'https://', '!:' => ' http://' ];
            $prf = mb_substr($lnk,0,2);
            $ref = mb_substr($lnk,2);
            if(array_key_exists($prf,$protocols)) {
				$srv = MySession::getEnvIDs('sysVAR.SERVER');
                $lnk = $protocols[$prf] . "{$srv}{$ref}";
            } else $lnk = $ref;
            break;
        default:
            if(mb_substr($lnk,0,2) === './') {
				if($modname === NULL) $modname = App::$Controller;
                $lnk = substr_replace($lnk, strtolower($modname), 0, 1);
                $lnk = App::Get_AppRoot($lnk);
            }
        }
    }
    return str_replace(['%','"',"'",'+'], ['%25','%22','%27','%2B'], trim($lnk));
}
//==============================================================================
// MARKING WORD by SPAN CLASS
function mark_active_words($atext,$word,$class) {
 	$reg = implode('|',str_explode(['　',' '],$word));
	return preg_replace("/({$reg})/i","<span class='{$class}'>\\1</span>", $atext);
}
//==============================================================================
// password encryption
function passwd_encrypt($str) {
    $method_name = 'AES-256-CBC';
    $key_string = SESSION_PREFIX;
//    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method_name));
	$init = (defined('ENCRYPT_INIT')) ? ENCRYPT_INIT:DEFAULT_ENCRYPT_INIT;
	$iv = substr($init,0,openssl_cipher_iv_length($method_name));
    return openssl_encrypt($str,$method_name,$key_string,0,$iv);
}
// $original_text = openssl_decrypt($str,$method_name,$key_string,0,$iv);
//==============================================================================
// random password create
function passwd_random($n = 8) {
    return substr(base_convert(bin2hex(openssl_random_pseudo_bytes($n)),16,36),0,$n);
}
//==============================================================================
// ドロップダウンメニューを作成
// menu:		キー名 => 値(未使用)
//				ネストするときは3次元連想配列
//				メニューキー名 => [	グループ名 => [ キー名 => 値, ... ]	]
// label		TRUE: グループ名をタイトルとして表示
function menu_box($menu,$label=true) {
	// flexリストを出力
	$select_list = function($title,$list,$label) use(&$select_list) {
		echo "<ul>\n";
		if(!empty($title)) echo "<li class='label'><span>{$title}</span></li>\n";
		foreach($list as $key => $val) {
			if(is_array($val)) {
				echo "<li><a class='label'>{$key}</a>\n";
				echo "<div class='navi-sub'>\n";
				// 通常メニューを先に処理
				$sub = array_filter($val, function($v) { return is_scalar($v);});
				if(!empty($sub)) $select_list('',$sub,$label);
				// グループメニューを処理
				$sub = array_filter($val, function($v) { return is_array($v);});
				if(!empty($sub)) {
					foreach($sub as $kk => $vv) $select_list(($label)  ? $kk : '',$vv,$label);
				}
				echo "</div></li>\n";
			} else if(is_numeric($key)) {
				echo "<li><a class='item'>{$val}</a></li>\n";
			} else {
				if($val === -1) echo "<li class='label'><span>{$key}</span></li>\n";
				else echo "<li><a class='item'>{$key}</a></li>\n";
			}
		}
		echo "</ul>\n";
	};
	$select_list('',$menu,$label);
};
//==============================================================================
// チェックリスト・ラジオボタンのメニュー作成
// menu:		キー名 => 値(未使用)
//				タブを使うときは２次元連想配列
//				タブ名 => [	キー名 => 値, ...	]
// item_name	ラジオボタンのときは必須
// item_type	checkbox | radio
// label_val	false | true(ラベル名を値に使う)
// split		分割個数 0=分割なし
function check_boxmenu($menu,$item_name='',$item_type='checkbox',$label_val=false,$split=0) {
	if(!empty($item_name)) $item_name = " name='{$item_name}'";
	$tab_contents = function($subsec,$split) use(&$item_name,&$item_type,&$label_val) {
		echo "<div class='check-itemset'>";
		echo "<div><ul>\n";
		$cnt = 0;
		foreach($subsec as $label => $val) {
			$brk = 0;
			if(is_int($label)) {
				if($val === '---' || $val === -2) $brk = 2;
				else if($val === '-' || $val === -1) $brk = 1;
			}
			$label = tag_body_name($label);
			if($brk === 2 || ($split > 0 && ++$cnt > $split)) {
				echo "</ul></div><div><ul>\n";		//  break
				$cnt = 0;
			} else {
				echo "<li>";
				$item_val = ($label_val)?$val:$label;
				if($brk === 1) echo "<hr>";
				else echo "<label><input type='{$item_type}' class='check-item' value='{$item_val}'{$item_name} />{$label}</label>";;
				echo "</li>\n";
			}
		}
		echo "</ul></div>\n";
		echo "</div>";
	};
	$tabset = array_filter($menu,function($v) {return is_array($v);});
	if(empty($tabset)) {			// 単一タブ＝タブなし
		$tab_contents($menu,$split);
	} else {	// 複数タブ
		echo "<ul class='tabmenu'>\n";
		foreach($tabset as $label => $subsec) {
			$label = tag_body_name($label);
			list($label,$sel) = fix_explode('.',$label,2);
			$attr = ($sel === 'selected') ? ' class="selected"':'';
			echo "<li{$attr}>{$label}</li>\n";
		}
		echo "</ul>\n";
		// タブコンテンツを表示
		echo "<ul class='tabcontents'>\n";
		foreach($tabset as $label => $value) {
			$label = tag_body_name($label);
			list($label,$sel) = fix_explode('.',$label,2);
			$attr = ($sel === 'selected') ? ' class="selected"':'';
			echo "<li{$attr}>\n";
			$tab_contents($value,$split);
			echo "</li>\n";
		}
		echo "</ul>\n";
	}
};
