<?php

/* -------------------------------------------------------------
 * Biscuits(MAP)ミニフレームワーク
 *   Main: メイン処理
 */
global $on_server;
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

// REQUEST_URIを分解
list($appname,$app_uri,$module,$q_str) = getRoutingParams(__DIR__);
list($fwroot,$approot) = $app_uri;
list($controller,$method,$filter,$params) = $module;
parse_str($q_str, $query);
if(!empty($q_str)) $q_str = "?{$q_str}";     // GETパラメータに戻す

// アプリ名が有効かどうか確認する
if(empty($appname) || !file_exists("app/$appname")) {
    $applist = GetFoloders("app/");     // アプリケーションフォルダ名を取得
    $appname = $applist[0];             // 最初に見つかったアプリケーションを指定
    $approot = "{$fwroot}{$appname}";   // アプリURIを生成
    $controller = ucfirst(strtolower($appname)); // 指定がなければ 
    $redirect = true;
}
// ここでは App クラスの準備ができていないので直接フォルダ指定する
require_once("app/{$appname}/Config/config.php");
// コントローラーファイルが存在するか確認する
if(!is_extst_module($appname,$controller,'Controller')) {
    debug_dump(0, [
        'モジュールチェック' => [
            "appname"=> $appname,
            "Controller" => $controller,
        ],
    ]);
    $controller = ucfirst(strtolower(DEFAULT_CONTROLLER)); // 指定がなければ 
//    $module[0] = $controller;
    $redirect = true;
}
// リダイレクトする時はコントローラーが書換わっているので調整する
if($redirect) $module[0] = $controller;
// URLを再構成する
$ReqCont = [
    'root' => $approot,
    'module' => $module,
    'query' => $q_str,
];
$requrl = array_to_URI($ReqCont);
// コントローラ名やアクション名が書き換えられてリダイレクトが必要なら終了
if($redirect) {
    debug_dump(0, [
        'リダイレクト情報' => [
            "SERVER" => $_SERVER['REQUEST_URI'],
            "AppROOT"=> $approot,
            "appname"=> $appname,
            "Module" => $module,
            "Query"    => $q_str,
        ],
        "ReqCont" => $ReqCont,
        "Location" => $requrl,
    ]);
    if($on_server) {
        header("Location:{$requrl}");
    } else {
        echo "Location:{$requrl}\n";
    }
    exit;
}
// アプリケーション変数を初期化する
App::__Init($appname,$app_uri,$module,$query,$requrl);

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
LangUI::construct($lang,App::AppPath("View/lang/"));
LangUI::LangFiles(['#common',$controller]);
// データベースハンドラを初期化する */
DatabaseHandler::InitConnection();

// モジュールファイルを読み込む
App::appController($controller);

// コントローラ/メソッドをクラス名/アクションメソッドに変換
$ContClass = "{$controller}Controller";
$ContAction= "{$method}Action";
// コントローラインスタンス生成
$controllerInstance = new $ContClass();
// 指定メソッドが存在するか、無視アクションかをチェック
if(!method_exists($controllerInstance,$ContAction) || 
   in_array($method,$controllerInstance->disableAction) ) {
    // クラスのデフォルトメソッド
    $method = $controllerInstance->defaultAction;
    $ContAction = "{$method}Action";
    if(strcasecmp($appname,$controller) === 0) {
        App::ChangeMTHOD('','');     // メソッドの書換えはリダイレクトしない
    } else {
        App::ChangeMTHOD($controller,$method);     // メソッドの書換えはリダイレクトしない
    }
}
App::$Controller  = $controller;    // コントローラー名
App::$ActionMethod= $ContAction;    // アクションメソッド名
// =================================
debug_dump(0, [
    'デバッグ情報' => [
        "Application"=> $appname,
        "Controller"=> $controller,
        "Class"     => $ContClass,
        "Method"    => $method,
        "URI"       => $requrl,
        "QUERY"     => $q_str,
        "Controller"=> App::$Controller,
        "Action"    => App::$ActionMethod,
    ],
    "QUERY" => App::$Query,
    "SESSION" => MySession::$PostEnv,
    'パス情報' => [
        "SERVER" => $_SERVER['REQUEST_URI'],
        "RootURI"=> $approot,
        "appname"=> $appname,
        "Controller"=> $controller,
        "Action"    => $ContAction,
        "Param"    => $params,
    ],
    "ReqCont" => $ReqCont,
    "Location" => App::getRelocateURL(),

]);
// セッション変数を初期化
MySession::InitSession();
APPDEBUG::arraydump(0, [
    "Initセッション" => MySession::$PostEnv,
]);
APPDEBUG::RUN_START();

$controllerInstance->$ContAction();

APPDEBUG::RUN_FINISH(0);
// リクエスト情報を記憶
MySession::SetVars('sysVAR',App::$SysVAR);
APPDEBUG::arraydump(0, [
    "Closeセッション" => MySession::$PostEnv,
]);
// クローズメソッドを呼び出して終了
$controllerInstance->__TerminateApp();

MySession::CloseSession();
DatabaseHandler::CloseConnection();
