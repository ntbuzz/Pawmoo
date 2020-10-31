<?php
/* -------------------------------------------------------------
 * WaffleMap Object-orientation PHP mini_framework
 *   Main: routing and redirection process
 *      method naming
 *      void = camel case (first upper is 'PUBLIC')
 *          PascalCase      Public method/function, void function
 *              PublicMethod
 *          camelCase       private/protected method/function,void
 *              privateFunction
 *          Pascal_snake    public void function
 *              Set_on_void
 *      return value = snake_case (first upper, all lower is 'PUBLIC')
 *          Camel_Snake     Public method/function, return value
 *              Get_Default
 *          snake_Camel     private/protected method/function with return value
 *              private_Function
 *          snake_case      Public function with return value
 *              all_public_function
 */
// デバッグ用のクラス
require_once('AppDebug.php');

// このファイルが依存している関数定義ファイル
require_once('Config/appConfig.php');
require_once('Common/coreLibs.php');
require_once('Common/appLibs.php');
// オートローダーの初期化前に必要、または命名規則から外れたクラス
require_once('Class/session.php');
require_once('Class/fileclass.php');
require_once('Class/Parser.php');
require_once('Base/LangUI.php');
// 以下のクラスはオートロードできるが速度低下を防ぐためここでは使わない
require_once('App.php');
require_once('Base/AppObject.php');
require_once('Base/AppController.php');
require_once('Base/AppModel.php');
require_once('Base/AppFilesModel.php');
require_once('Base/AppView.php');
require_once('Base/AppHelper.php');

// Setup TIMEZONE
date_default_timezone_set(TIME_ZONE);
// コマンドライン起動ならデバッグ情報を出力
if(CLI_DEBUG) {
	$ln = str_repeat("=", 50);
	print_r($argv);
	echo "{$ln} START HERE ${ln}\n";
}

$redirect = false;      // Redirect flag

// REQUEST_URIを分解
list($appname,$app_uri,$module,$q_str) = get_routing_params(__DIR__);
list($fwroot,$approot) = $app_uri;
list($controller,$method,$filter,$params) = $module;
parse_str($q_str, $query);
if(!empty($q_str)) $q_str = "?{$q_str}";     // GETパラメータに戻す
debug_log(DBMSG_SYSTEM,[ "Routing module" => $module]);

// アプリ名が有効かどうか確認する
if(empty($appname) || !file_exists("app/$appname")) {
//  header("Location:/index.html");exit;
    // 404エラーページを送信する時はこっち
    error_response('app-404.php',$appname,$app_uri,$module);
}
if($controller === 'Error') {       // ERROR PAGE
    $code = $params[0];
    error_response("page-{$code}.php",$appname,$app_uri,$module);
}
// ここでは App クラスの準備ができていないので直接フォルダ指定する
require_once("app/{$appname}/Config/config.php");
// Check URI-Redirect direction
if(!defined('FORCE_REDIRECT')) define('FORCE_REDIRECT', FALSE);
MySession::InitSession($appname);

if(!is_extst_module($appname,$controller,'Controller')) {
    // if BAD controller name, try DEFAULT CONTROLLER and shift follows
    $cont = (DEFAULT_CONTROLLER === '') ? $appname : DEFAULT_CONTROLLER;
    array_unshift($params,$filter);
    $module[0] = ucfirst(strtolower($cont));
    $module[1] = $controller;
    $module[2] = strtolower($method);
    $module[3] = $params;
    list($controller,$method,$filter) = $module;
    // RE-TRY DEFAULT CONTROLLER,if FAILED,then NOT FOUND
    if(!is_extst_module($appname,$controller,'Controller')) {
        error_response('page-404.php',$appname,$app_uri,$module);
    }
}
// リダイレクトする時はコントローラーが書換わっているので調整する
if($redirect) $module[0] = $controller;
// ReBuild URI
$ReqCont = [
    'root' => $approot,
    'module' => $module,
    'query' => $q_str,
];
$requrl = array_to_URI($ReqCont);
// コントローラ名やアクション名が書き換えられてリダイレクトが必要なら終了
if($redirect) {
    if(CLI_DEBUG) {
        echo "Location:{$requrl}\n";
    } else {
        header("Location:{$requrl}");
    }
    exit;
}
// 拡張子を考慮する
if(mb_strpos($method,'.') !== FALSE) {  // have a extension
    list($method,$filter) = extract_base_name($method);
    $module[1] = $method;
    $module[2] = $filter;
}
// アプリ固有クラスをオートロードできるようにする
require_once('Class/ClassLoader.php');
ClassLoader::Setup($appname,$controller);
// アプリケーション変数を初期化する
App::__Init($appname,$app_uri,$module,$query,$requrl);
App::$Controller  = $controller;    // コントローラー名

// 共通サブルーチンライブラリを読み込む
$libs = get_php_files(App::Get_AppPath("common/"));
foreach($libs as $files) {
    require_once $files;
}
// 言語ファイルの対応
if(array_key_exists('lang', $query)) {
    $lang = $query['lang'];
    MySession::set_LoginValue(['LANG' => $lang]);
} else {
    $lang = MySession::get_LoginValue('LANG');
    if($lang === NULL) $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
}
if(empty($lang)) $lang = DEFAULT_LANG;
// コントローラ用の言語ファイルを読み込む
LangUI::construct($lang,App::Get_AppPath("View/lang/"),['#common',$controller]);
// モジュールファイルを読み込む
App::LoadModuleFiles($controller);

// コントローラ/メソッドをクラス名/アクションメソッドに変換
$ContClass = "{$controller}Controller";
$ContAction= "{$method}Action";
// コントローラインスタンス生成
$controllerInstance = new $ContClass();
// Method Existance Check
if(!$controllerInstance->is_enable_action($method)) {
    if(FORCE_REDIRECT || $method==='') {
        $method = $controllerInstance->defaultAction;               // クラスのデフォルトメソッド
        $ContAction = "{$method}Action";                            // アクション名に変換
    } else {
        $module[0] = $controller;       // may-be rewrited
        $module[1] = $method;           // may-be rewrited
        error_response('page-404.php',$appname,$app_uri,$module);
    }
}
if(strcasecmp($appname,$controller) === 0) {
    App::ChangeMethod('',$method,TRUE);     // コントローラーを隠す
} else {
    App::ChangeMethod($controller,$method,FALSE);     // メソッドの書換えはリダイレクトしない
}
App::$ActionMethod= $ContAction;    // アクションメソッド名
//=================================
// デバッグ用の情報ダンプ
debug_log(DBMSG_SYSTEM, [
    '#DebugInfo' => [
        "Application"=> $appname,
        "Controller"=> $controller,
        "Class"     => $ContClass,
        "Method"    => $method,
        "Filter"    => $filter,
        "URI"       => $requrl,
        "QUERY"     => $q_str,
        "Controller"=> App::$Controller,
        "Action"    => App::$ActionMethod,
    ],
    "QUERY" => App::$Query,
    "SESSION" => [
        "SESSION_ID"=> MySession::$MY_SESSION_ID,
        "ENV"       => MySession::$EnvData,
        "REQUEST"   => MySession::$ReqData,
    ],
    '#PathInfo' => [
        "REFERER" => $_SERVER['HTTP_REFERER'],
        "SERVER" => $_SERVER['REQUEST_URI'],
        "sysRoot"=> App::$SysVAR['SYSROOT'],
        "appRoot"=> App::$SysVAR['APPROOT'],
        "appname"=> $appname,
        "Controller"=> $controller,
        "Action"    => $ContAction,
        "Param"    => $params,
    ],
    "ReqCont" => $ReqCont,
    "Location" => App::Get_RelocateURL(),

]);

debug_run_start();
// ログイン不要または成功ならTRUEが返る
if($controllerInstance->is_authorised()) {
    $controllerInstance->$ContAction();
}

debug_run_time(0);
MySession::CloseSession();
debug_log(DBMSG_SYSTEM, [
    "#SessionClose" => [
        "ENVDATA" => MySession::$EnvData,
    ]
]);
// クローズメソッドを呼び出して終了
$controllerInstance->__TerminateApp();

DatabaseHandler::CloseConnection();
