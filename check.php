<?php

$cwd = basename(__DIR__);
$_SERVER['REQUEST_URI'] = "/help/index.html";	// getcwd() . 
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);	// 
$_SERVER['SERVER_NAME'] = getHostByName(getHostName());
$_SERVER['LOCAL_ADDR'] = $_SERVER['SERVER_NAME'];
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ja;en';		// 言語受け入れリスト
$_SERVER['HTTP_REFERER'] = "localhost";
$_SERVER['SERVER_PORT'] = '';
//	print_r($_SERVER);
$uri = $_SERVER['REQUEST_URI'];
list($url,$q_str) = (strpos($uri,'?')!==FALSE)?explode('?',$uri):[$uri,''];
parse_str($q_str, $query);
$_REQUEST =  $query;
//================================================================================
// アプリ固有クラスをオートロードできるようにする
require_once('Core/AppDebug.php');
require_once('Core/Common/appLibs.php');
require_once('Core/Common/coreLibs.php');
require_once('Core/Class/Parser.php');
require_once('Core/Base/LangUI.php');
LangUI::construct('en','');

// REQUEST_URIを分解
list($appname,$app_uri,$module,$q_str) = get_routing_params(__DIR__);
list($fwroot,$approot) = $app_uri;
list($controller,$method,$filter,$params) = $module;
parse_str($q_str, $query);
if(!empty($q_str)) $q_str = "?{$q_str}";     // GETパラメータに戻す

// アプリ名が有効かどうか確認する
if(empty($appname) || !file_exists("app/$appname")) {
    // 404エラーページを送信する
    $content = file_get_contents('Core/error/404.html');
    echo str_replace('{{appname}}',$appname,$content);
    exit;
}
$pp ="日本語!=";
echo mb_substr($pp,-2)."\n";
echo mb_substr($pp,0,-2)."\n";

//=================================
// デバッグ用の情報ダンプ
echo "FALSE=".FALSE."\n";
debug_log(-1100, [
    '#DebugInfo' => [
        "Application"=> $appname,
        "Controller"=> $controller,
        "Method"    => $method,
        "QUERY"     => $q_str,
    ],
    '#PathInfo' => [
        "SERVER" => $_SERVER['REQUEST_URI'],
        "RootURI"=> $approot,
        "appname"=> $appname,
        "Controller"=> $controller,
        "Param"    => $params,
    ],
]);
echo "Finishe.\n";
