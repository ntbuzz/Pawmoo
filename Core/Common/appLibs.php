<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  appLibs: 共通関数群
 *
 */
define('DEBUG_DUMP_NONE',   0);
define('DEBUG_DUMP_DOIT',   1);
define('DEBUG_DUMP_EXIT',   2);

// コア共通ファンクション
// URI を取得して、フレームワークRoot、アプリ名、コントローラ名、メソッド＋パラメータ、クエリ文字列に分解して返す
function getFrameworkParameter($dir) {
    $root = basename(dirname($dir));
    $vv = $_SERVER['REQUEST_URI'];
    list($requrl,$q_str) = (strpos($vv,'?')!==FALSE)?explode('?',$vv):[$vv,''];
    $param = trim(urldecode($requrl),'/');
    $params = ($param == '') ? array() : explode('/', $param);            // パラメーターを / で分割
    // フレームワークルートから始まるURIか?
    if(count($params) < 3) array_push($params,'','');   // list() 用に数を補填
    if($params[0] === $root) {
        list($fwroot,$appname,$modname) = $params;
        $args = array_slice($params,3);
        $rootURI ="/{$fwroot}/{$appname}/";
        $fwroot = "/{$fwroot}/";
    } else {
        list($appname,$modname) = $params;
        $args = array_slice($params,2);
        $rootURI ="/{$appname}/";
        $fwroot = '/';
    }
    debug_dump(0, [
        'フレームワーク情報' => [
            "SERVER" => $_SERVER['REQUEST_URI'],
            "fwroot"=> $fwroot,
            "RootURI"=> $rootURI,
            "appname"=> $appname,
            "modname"=> $modname,
            "query"=> $q_str,
            "args"=> $args,
        ],
    ]);
    // モジュール名はキャメルケースに変換
    $modname = ucfirst(strtolower($modname));
    $ret = [$fwroot,$rootURI,$appname,$modname,$args,$q_str];
    return $ret;
}
//================================================
// コントローラーが存在するかチェックする
function is_extst_module($appname,$modname,$classname) {
    if($modname == NULL) return FALSE;      // そもそも名前が無い
//    $modname = ucfirst(strtolower($modname));
    // ファイルが存在するかチェック
    $modtop = getcwd() . "/" . "app/{$appname}/modules/{$modname}";
    $reqfile = "{$modtop}/{$modname}{$classname}.php";
    return file_exists($reqfile);           // ファイルが存在するか
}
//===============================================================================
// 指定フォルダ内のフォルダ名リストを取得する
function GetFoloders($dirtop) {
    $drc=dir($dirtop);
    $folders = array();
	while(false !== ($fl=$drc->read())) {
        if(! in_array($fl,IgnoreFiles,FALSE)) {
            $path = "{$dirtop}{$fl}";
            if(is_dir($path)) {
                $folders[] = $fl;
            }
        }
    }
    $drc->close();
    return $folders;
}
//===============================================================================
// ミリセカンド取得
function getUnixTimeMillSecond(){
    //microtimeを.で分割
    $arrTime = explode('.',microtime(true));
    //時＋ミリ秒
    return date('H:i:s', $arrTime[0]) . '.' .$arrTime[1];
}
//===============================================================================
// フォルダ内のPHPファイルを探査する
function GetPHPFiles($dirtop) {
    $drc=dir($dirtop);
    $files = array();
	while(false !== ($fl=$drc->read())) {
        if(! in_array($fl,IgnoreFiles,FALSE)) {
            $path = "{$dirtop}{$fl}";
            $ext = substr($fl,strrpos($fl,'.') + 1);    // 拡張子を確認
            if(!is_dir($path) && ($ext == 'php')) {
                $files[] = $path;
            }
        }
    }
    $drc->close();
    return $files;
}
//===============================================================================
// ファイルパスを / で終わるようにする
function pathcomplete($path) {
    if(mb_substr($path,-1) !== '/') $path .= '/';     // 最後は /で終わらせる
    return $path;
}
//===============================================================================
// 重複文字列の除去
function tag_body_name($key) {
    $n = strrpos($key,':');
    return ($n !== FALSE) ? substr($key,0,$n) : $key;
}
//===============================================================================
// ファイルパスを分解する
// 返り値は array(ファイル名,拡張子)
function extractBaseName($fn) {
    $nn = strrpos($fn,'.');                     // ファイル名の拡張子を確認
    if($nn === FALSE) $nn = strlen($fn);        // 拡張子が無いときに備える
    $ext = substr($fn,$nn+1);                   // 拡張子を分離
    $fn = substr($fn,0,$nn);                    // ファイル名を切り落とす
    return array($fn,$ext);
}
//===============================================================================
// ファイルパスを分解する
// 返り値は array(パス,ファイル名,拡張子)
function extractPath($path) {
    list($path,$fn) = extractFileName($path);    // パスとファイル名に分解
    list($fn,$ext) = extractBaseName($fn);    // ファイル名と拡張子に分解
    return array($path,$fn,$ext);
}
//===============================================================================
// ファイルパスを分解する
// 返り値は array(パス,ファイル名)
function extractFileName($path) {
    if(mb_substr($path,-1) == '/') {
        $path = substr($path,0,strlen($path)-1);     // 末尾の /  は削除
    }
    $nn = strrpos($path,'/');    // ファイル名を確認
//echo "FILE:{$path}\n";
    if($nn === FALSE) {
        $fn = $path;
        $path = '';
    } else {
        $fn = substr($path,$nn+1);     // 拡張子を分離
        $path = substr($path,0,$nn+1);
    }
    if(mb_substr($path,-1) !== '/') $path .= '/';     // パスの最後は /で終わらせる
    return array($path,$fn);
}
//===============================================================================
// 文字列の固定長サイズ
// $w > 0 = 左側から固定長, $w < 0 = 右側から固定長
function str_fixwidth($exp,$pad,$w) {
    if($w < 0) {
        $pp = str_repeat ($pad,-$w);
        return substr("{$pp}{$exp}",$w);
    }
    $pp = str_repeat ($pad,$w);
    return substr("{$exp}{$pp}",0,$w);
}
//===============================================================================
// 移動先のフォルダ存在を確かめてファイル移動
function file_move($src,$dest){
    list($path,$fn) = extractFileName($dest);       // 移動先をパスとファイル名に分解
    if(!file_exists($path)) mkdir($path);           // 移動先のフォルダがなければ作成する
    return rename($src,$dest);                      // ファイル移動
}
//===============================================================================
// ファイルサイズ単位
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
//===============================================================================
// 文字コード変換
function SysCharset($str) {
    return (OS_CODEPAGE == 'SJIS') ?
            mb_convert_encoding($str,"UTF-8","sjis-win") : $str;
}
function LocalCharset($str) {
    return (OS_CODEPAGE == 'SJIS') ?
            mb_convert_encoding($str,"sjis-win","UTF-8") : $str;
}
//===============================================================================
// 配列内の文字列チェック
function instr_array($str,$hayz) {
    foreach($hays as $val) {
        if(strpos($str,val) !== false) return TRUE;
    }
    return FALSE;
}
//===============================================================================
// 配列の深さ計算、短さ優先1行バージョン
function array_depth($a, $c = 0) {
    return (is_array($a) && count($a))
          ? max(array_map("array_depth", $a, array_fill(0, count($a), ++$c)))
          : $c;
  }
//===============================================================================
// 配列の結合 array_merge() の代替え、無名要素も上書きする
function array_override($a, $b) {
//    APPDEBUG::debug_dump(5,["a:" => $a,"b:" => $b],-1);
    foreach($b as $key => $val) {
        $a[$key] = $val;            // 要素を上書きするか追加する
    }
//    APPDEBUG::MSG(-5, $a, "マージ完了");
    return $a;          //  結合した配列を返す
  }
//===============================================================================
// テキストの改行を変換
function Text2HTML($atext) {
    return nl2br(htmlspecialchars($atext));
}
//===============================================================================
// HTMLタグの出力
function echo_safe($atext,$is_safe = TRUE) {
    echo ($is_safe) ? htmlspecialchars($atext) : $atext;
}
//===============================================================================
function trim_delsp($a) {
    return trim(preg_replace('/\s+/', ' ', str_replace('　',' ',$a)));
}
//===============================================================================
function replace_newline($a) {
    $vv = str_replace(["\r\n", "\r", "\n","\""],["\\n","\\n","\\n","\\\""], $a);
    return $vv;
}
//===============================================================================
// テキストを分割した配列
function text_line_split($del,$txt) {
    $array = array_values(              // これはキーを連番に振りなおしてるだけ
        array_filter(                   // 文字数が0の行を取り除く
            array_map('trim_delsp',     // 各行にtrim()をかける
            explode($del, $txt)         // とりあえず行に分割
            ), 'strlen'));  // array_filter
    return $array;
}
//===============================================================================
// 自動ハイパーリンク生成
// XXXXXX.{URL} 形式の文字列をハイパーリンクに変換する
function auto_hyperlink($atext) {
	$ln = explode("\n", $atext);	// とりあえず行に分割
	$ret = array();
	foreach($ln as $ll) {
    	$ll = preg_replace("/([^\.\s]+)\.{([-_.!~*\'()a-z0-9;\/?:\@&=+\$,%#]+)}/i",'<a target="_blank" href="\\2">\\1</a>', $ll);
		$ret[] = $ll;
    }
//    return implode("<br />\n",$ret);
	return implode("\n",$ret);
}
//===============================================================================
// ハイパーリンク生成
//  http〜  直接指定
// :...     http://localhost/...
// /..      sysRoot/...
// ./...    appRoot/modname/...
// ...      appRoot/...
function make_hyperlink($lnk,$modname) {
    $http = array('http:/','https:');
	if(!in_array(mb_substr($lnk,0,6),$http)) {
		if($lnk[0] === ':') {
            $lnk[0] = '/';
            $host = getHostName();
            $lnk= "http://{$host}{$lnk}";
        } else if($lnk[0] === '/') {
//			$lnk = App::getSysRoot("/common{$lnk}");
			$lnk = App::getSysRoot("{$lnk}");
        } else if(mb_substr($lnk,0,2) === './') {
            $lnk = substr_replace($lnk, strtolower($modname), 0, 1);
            $lnk = App::getAppRoot($lnk);
        } else {
            $lnk = App::getAppRoot($lnk);
		}
    }
    return $lnk;
}
//===============================================================================
// デバッグダンプ
function check_cwd($here) {
    $cwd = getcwd();
    echo "{$here} CWD:{$cwd}\n";
}
//===============================================================================
// デバッグダンプ
function debug_dump($flag, $arr = []) {
    if($flag === 0) return;
    $danger = ($flag < 4);
    // バックトレースから呼び出し元の情報を取得
    $dbinfo = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,2);    // 呼び出し元のスタックまでの数
//var_dump($dbinfo);exit;
    $dbpath = str_replace('\\','/',$dbinfo[0]['file']);             // Windowsパス対策
    list($pp,$fn) = extractFileName($dbpath);
    $fn .= "(".$dbinfo[0]['line'].")";
    if(isset($dbinfo[1]['object'])) {
        $pp = get_class($dbinfo[1]['object']);  // 呼出し元のクラス名
        if(substr($fn,0,strlen($pp)) !== $pp) $fn = "{$pp}::{$fn}";
    }
    $str = "{$fn}->" . $dbinfo[1]['function'];
    // 変数出力
    if(is_scalar($arr)) {
        echo_safe("{$arr}\n",$danger);
    } else {
        if($flag < 3) echo "<pre>\n{$str}\n";
        foreach($arr as $msg => $obj) {
            if(empty($obj)) echo "{$msg}:EMPTY\n";
            else if(is_scalar($obj)) {
                echo "------- {$msg} -----\n";
                echo_safe("{$obj}\n",$danger);
            } else {
                echo "------- {$msg} -----\n";
                dumpobj($obj,0);
            }
        }
        if($flag < 3) echo "</pre>\n";
    }
    if($flag === 2) { 
//        echo "TYPE:".get_class($dbinfo[1]['object'])."\n";
//        var_dump($dbinfo[0]['object']);
        exit;
    }
}
//===============================================================================
// デバッグダンプ
function dumpobj($obj,$indent){
    foreach($obj as $key => $val) {
        echo str_repeat(' ',$indent*2) . "[{$key}] = ";
        if(is_array($val)) {
            echo "array(" . count($val) . ")\n";
            dumpobj($val,$indent+1);
        } else echo_safe("'{$val}'\n");
    }
}
