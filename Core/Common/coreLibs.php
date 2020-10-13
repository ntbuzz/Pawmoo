<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  coreLibs: コアクラス内で呼び出す共通関数群
 */
//==============================================================================
// REQUEST_URI を分解しルーティングに必要なアプリ名、コントローラー名を抽出する
function get_routing_params($dir) {
    $root = basename(dirname($dir));        // FWフォルダ名
    $vv = $_SERVER['REQUEST_URI'];
    list($requrl,$q_str) = (strpos($vv,'?')!==FALSE)?explode('?',$vv):[$vv,''];
    $param = trim(urldecode($requrl),'/');  // 先頭と末尾の / を除去
    $args = (empty($param)) ? array() : explode('/', $param);
    $args = array_values(array_filter($args, 'strlen'));  // 文字数が0の行を取り除く
    $appname = array_shift($args);          // 先頭の要素を取り出す
    if($appname === $root) {                // URIがFWフォルダ名から始まる
        $appname = array_shift($args);      // アプリ名を取り直す
        $fwroot = "/{$root}/";              // FWから始まるURI
    } else {
        $fwroot = "/";                      // アプリ名から始まるURI
    }
    if(is_numeric($appname)) {              // アプリ名が数字でないことを確認
        array_unshift($args,$appname);      // 数字はアプリ名でないので配列に戻す
        $appname = '';
    }
    $app_uri = [ $fwroot, "{$fwroot}{$appname}" ];      // URIセットを生成
    debug_log(FALSE, [
        'URI' => $_SERVER['REQUEST_URI'],
        "app_uri"=> $app_uri,
        "args"=> $args,
    ]);
    // コントローラー名以降のパラメータを分解する
    $params = array();
    for($n=0;$n < count($args);$n++) {
        if(is_numeric($args[$n]) || $n >= 3) {
            $params = array_slice($args,$n);    // パラメータを取り出す
            array_splice($args,$n);             // 取り出したパラメータを削除
            break;
        }
    }
    $args += array_fill(count($args),3,NULL);     // filter要素までを補填
    list($controller,$method,$filter) = $args;
    if(empty($controller)) $controller = $appname; // コントローラが空ならアプリ名と同じにする
    else if(mb_strpos($controller,'.') !== FALSE) {
        $method = $controller;      // put-off method analyzed 
        $controller = $appname;     // same as appname
    }
    if(mb_strpos($method,'.') !== FALSE) {  // have a extension
        list($filename,$ext) = extract_base_name($method);
    }
    $module = array(
        ucfirst(strtolower($controller)),    // コントローラー名キャメルケースに変換
        ucfirst(strtolower($method)),        // メソッドもキャメルケースに変換
        $filter,                             // フィルター
        $params                              // パラメータ
    );
    $ret = [$appname,$app_uri,$module,$q_str];
    debug_log(FALSE, [
        'フレームワーク情報' => [
            "SERVER" => $_SERVER['REQUEST_URI'],
            "app_uri"=> $app_uri,
            "appname"=> $appname,
            "Module"=> $module,
            "query"=> $q_str,
        ],
        "RET" => $ret,
    ]);
    return $ret;
}
//==============================================================================
// Output 404 ERROR PAGE
// enabled of PHP VARIABLE:
//      $app_name   Applicatiopn Name
//      $app_root   Application Top URI
//      $app_module Controller Name
//      $page_name  Rquest ERROR PAGE
function error_response($error_page,$app_name, $module) {
    list($app_module,$page_name) = array_map(function($a) {
        return (gettype($a) === 'string')?strtolower($a):'';},$module);
    $app_root = "/{$app_name}/";
    require_once("Core/error/{$error_page}");
    exit;
}
//==============================================================================
// コントローラーが存在するかチェックする
function is_extst_module($appname,$modname,$classname) {
    if($modname == NULL) return FALSE;      // そもそも名前が無い
    // ファイルが存在するかチェック
    $modtop = getcwd() . "/" . "app/{$appname}/modules/{$modname}";
    $reqfile = "{$modtop}/{$modname}{$classname}.php";
    return file_exists($reqfile);           // ファイルが存在するか
}
//==============================================================================
// 指定フォルダ内のフォルダ名リストを取得する
function get_folder_lists($dirtop) {
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
//==============================================================================
// ミリセカンド取得
function get_UnixTime_MillSecond(){
    //microtimeを.で分割
    $arrTime = explode('.',microtime(true));
    //時＋ミリ秒
    return date('H:i:s', $arrTime[0]) . '.' .$arrTime[1];
}
//==============================================================================
// フォルダ内のPHPファイルを探査する
function get_php_files($dirtop) {
    $files = array();
    if(file_exists($dirtop)) {
        $drc=dir($dirtop);
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
    }
    return $files;
}
//==============================================================================
// 配列からURIを生成する、要素内に配列があるときにも対応する
function array_to_URI($arr) {
    // 無名関数を定義して配列内の探索を行う
    $array_builder = function ($lst) {
        $ret = [];
        foreach($lst as $val) {
            $uri = (is_array($val)) ? array_to_URI($val) : strtolower($val);
            if(!empty($uri)) $ret[] = $uri;
        }
        return $ret;
    };
    $ret = $array_builder($arr);
    return implode('/',$ret);
}
//==============================================================================
// 配列からURIを生成する、要素内に配列があるときにも対応する
function array_concat_keys($arr,$keys) {
    $ss = ''; $trim_sep = ' ';
    foreach($keys as $kk => $val) {
        $sep = (is_numeric($kk)) ? ' ' : $kk;
        if(strpos($trim_sep,$sep) === FALSE) $trim_sep .= $sep;
        $ss .= $sep . $arr[$val];
    }
    return trim($ss,$trim_sep);
}
//==============================================================================
// ファイルパスを / で終わるようにする
function path_complete($path) {
    if(mb_substr($path,-1) !== '/') $path .= '/';     // 最後は /で終わらせる
    return $path;
}
//==============================================================================
// 文字コード変換
function SysCharset($str) {
    return (OS_CODEPAGE == 'SJIS') ?
            mb_convert_encoding($str,"UTF-8","sjis-win") : $str;
}
function LocalCharset($str) {
    return (OS_CODEPAGE == 'SJIS') ?
            mb_convert_encoding($str,"sjis-win","UTF-8") : $str;
}
//==============================================================================
// 重複文字列の除去
function tag_body_name($key) {
    $n = strrpos($key,':');
    return ($n !== FALSE) ? substr($key,0,$n) : $key;
}
//==============================================================================
// SQL Compare operator separate
function keystr_opr($str) {
    $opr_set = ['=='=>NULL, '<>'=>NULL, '>='=>NULL, '<='=>NULL, '=>'=>'>=', '=<'=>'<=', '!='=>'<>', '='=>NULL, '>'=>NULL, '<'=>NULL, '@'=>NULL];
    foreach([-2,-1] as $nn) {
        $opr = mb_substr($str,$nn);      // last-2char
        if(array_key_exists($opr,$opr_set)) {
            $key = mb_substr($str,0,$nn);    // exclude last 2-char
            if(isset($opr_set[$opr])) $opr = $opr_set[$opr];    // Replace OPR string for SQL
            return array($key,$opr);
        }
    }
    return array($str,'');
}
//==============================================================================
// array_key_exists の再帰呼出し
function array_key_exists_recursive($key,$arr) {
    foreach($arr as $kk => $vv) {
        if($kk === $key) return TRUE;
        if(is_array($vv)) {
            if(array_key_exists_recursive($key,$vv)) return TRUE;
        }
    }
    return FALSE;
}
