<?php

/* -------------------------------------------------------------
 * Biscuit(MAP)ミニフレームワーク
 *   Main: メイン処理
 */
// デバッグ用のクラス
require_once('AppDebug.php');
APPDEBUG::INIT(10);

require_once('App.php');
require_once('Class/fileclass.php');
require_once('Class/session.php');
require_once('Config/appConfig.php');
require_once('Common/appLibs.php');
require_once('Base/AppObject.php');
require_once('Base/AppController.php');
require_once('Base/AppModel.php');
require_once('Base/AppFilesModel.php');
require_once('Base/AppView.php');
require_once('Base/AppHelper.php');
require_once('Base/LangUI.php');           // static class

// タイムゾーンの設定
date_default_timezone_set('Asia/Tokyo');
//echo setlocale(LC_ALL,0);

$redirect = false;      // リダイレクトフラグ

list($fwroot,$rootURI,$appname,$controller,$params,$q_str) = getFrameworkParameter(__DIR__);
parse_str($q_str, $query);
$scriptname = $_SERVER['SCRIPT_NAME'];
// アプリケーションのコンフィグを読込む
if(!file_exists("app/$appname")) {
    $applist = GetFoloders("app/");     // アプリケーションフォルダ名を取得
    $appname = $applist[0];             // 最初に見つかったアプリケーションを指定
    $rootURI = (strpos($rootURI,"/{$fwroot}/") !== FALSE) ? "/{$fwroot}/{$appname}/" : "/{$appname}/";
    $redirect = true;
}
// ここでは App クラスの準備ができていないので直接フォルダ指定する
require_once("app/{$appname}/Config/config.php");

$action = array_shift($params);         // パラメータ先頭はメソッドアクション
if(is_numeric($action) ) {              // アクション名が数値ならパラメータに戻す
    array_unshift($params, $action);
    $action = 'list';      // 指定がなければ list
    $redirect = true;
}
// アクションのキャメルケース化とURIの再構築
$action = ucfirst(strtolower($action));
// コントローラーファイルが存在するか確認する
if(!is_extst_module($appname,$controller,'Controller')) {
    $controller = ucfirst(strtolower(DEFAULT_CONTROLLER));     // 指定がなければ 
    $redirect = true;
}
// URLを再構成する
$ReqCont = [
    'root' => $rootURI,
    'controller' => strtolower($controller),
    'action' => strtolower($action ),
    'query' => implode('/',$params)
];
// コントローラー、アクションのキャメルケース化とURIの再構築
$requrl = str_replace('//','/',implode('/',$ReqCont));
// フレームワーク直接
dump_debug(DEBUG_DUMP_NONE,"MAIN", [
    'デバッグ情報' => [
        "SERVER" => $_SERVER['REQUEST_URI'],
        "RootURI"=> $rootURI,
        "fwroot"=> $fwroot,
        "appname"=> $appname,
        "Controller"=> $controller,
        "Action"    => $action,
        "Param"    => $params,
    ],
    "ReqCont" => $ReqCont,
    "Location" => $requrl,
]);
// コントローラ名やアクション名が書き換えられてリダイレクトが必要なら終了
if($redirect) {
    header("Location:{$requrl}");
    exit;
}
// アプリケーション変数を初期化する
App::__Init($rootURI, $appname, $requrl, $params, $q_str);

// コントローラ名/アクションをクラス名/メソッドに変換
$className = "{$controller}Controller";
$method = "{$action}Action";

// 共通サブルーチンライブラリを読み込む
$libs = GetPHPFiles(App::AppPath("common/"));
foreach($libs as $files) {
    require_once $files;
}
// コアクラスのアプリ固有の拡張クラス
$libs = GetPHPFiles(App::AppPath("extends/"));
foreach($libs as $files) {
    require_once $files;
}
// 言語ファイルの対応
$lang = (isset($query['lang'])) ? $query['lang'] : $_SERVER['HTTP_ACCEPT_LANGUAGE'];

// コントローラ用の言語ファイルを読み込む
LangUI::construct($lang);
LangUI::LangFiles(['#common',$controller]);
// データベースハンドラを初期化する */
DatabaseHandler::InitConnection();

// モジュールファイルを読み込む
App::appController($controller);
// コントローラインスタンス生成
$controllerInstance = new $className();
// 指定メソッドが存在するか、無視アクションかをチェック
if(!method_exists($controllerInstance,$method) || 
    in_array($action,$controllerInstance->disableAction) ) {
    // クラスのデフォルトメソッド
    $action = $controllerInstance->defaultAction;
    $method = "{$action}Action";
    App::$SysVAR['method'] = strtolower($action);
}
// 残りの引数を与え メソッド実行
App::$ActionClass = $controller;
App::$ActionMethod= $action;
APPDEBUG::debug_dump(1, [
    'システム変数情報' => App::$SysVAR,
    'パラメータ情報' => App::$Params,
],1);

// =================================
APPDEBUG::debug_dump(1, [
    'デバッグ情報' => [
        "Controller"=> $controller,
        "Class"     => $className,
        "Method"    => $method,
        "URI"       => $requrl,
        "SCRIPT"    => $scriptname,
        "QUERY"     => $q_str,
        "Module"    => App::$ActionClass,
        "Action"    => App::$ActionMethod,
    ],
    "QUERY" => App::$Query,
    "SESSION" => MySession::$PostEnv,
]);
// セッション変数を初期化
MySession::InitSession();
APPDEBUG::RUN_START();

$controllerInstance->$method();

APPDEBUG::RUN_FINISH(0);
// リクエスト情報を記憶
MySession::SetVars('sysVAR',App::$SysVAR);
APPDEBUG::arraydump(1, [
    "クローズセッション" => MySession::$PostEnv,
]);
// クローズメソッドを呼び出して終了
$controllerInstance->__TerminateApp();

MySession::CloseSession();
DatabaseHandler::CloseConnection();
