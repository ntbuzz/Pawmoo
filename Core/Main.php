<?php
/* -------------------------------------------------------------
 * Biscuits(MAP) Object-orientation PHP mini_framework
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
global $on_server;
// デバッグ用のクラス
require_once('AppDebug.php');
require_once('Common/appLibs.php');
require_once('Class/session.php');
require_once('Config/appConfig.php');

require_once('App.php');
require_once('Class/fileclass.php');
require_once('Base/AppObject.php');
require_once('Base/AppController.php');
require_once('Base/AppModel.php');
require_once('Base/AppFilesModel.php');
require_once('Base/AppView.php');
require_once('Base/AppHelper.php');
require_once('Base/LangUI.php');

APPDEBUG::INIT(DEBUG_LEVEL);
// Setup TIMEZONE
date_default_timezone_set(TIME_ZONE);

$redirect = false;      // Redirect flag

// REQUEST_URIを分解
list($appname,$app_uri,$module,$q_str) = get_routing_params(__DIR__);
list($fwroot,$approot) = $app_uri;
list($controller,$method,$filter,$params) = $module;
parse_str($q_str, $query);
if(!empty($q_str)) $q_str = "?{$q_str}";     // GETパラメータに戻す

// アプリ名が有効かどうか確認する
if(empty($appname) || !file_exists("app/$appname")) {
    $applist = get_folder_lists("app/");     // アプリケーションフォルダ名を取得
    $appname = $applist[0];             // 最初に見つかったアプリケーションを指定
    $approot = "{$fwroot}{$appname}";   // アプリURIを生成
    $controller = ucfirst(strtolower($appname)); // 指定がなければ 
    $redirect = true;
}
// ここでは App クラスの準備ができていないので直接フォルダ指定する
require_once("app/{$appname}/Config/config.php");
// コントローラーファイルが存在するか確認する
if(!is_extst_module($appname,$controller,'Controller')) {
    $controller = ucfirst(strtolower(DEFAULT_CONTROLLER)); // 指定がなければ 
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
    if($on_server) {
        header("Location:{$requrl}");
    } else {
        echo "Location:{$requrl}\n";
    }
    exit;
}
// アプリケーション変数を初期化する
App::__Init($appname,$app_uri,$module,$query,$requrl);
App::$Controller  = $controller;    // コントローラー名

// 共通サブルーチンライブラリを読み込む
$libs = get_php_files(App::Get_AppPath("common/"));
foreach($libs as $files) {
    require_once $files;
}
// コアクラスのアプリ固有の拡張クラス
$libs = get_php_files(App::Get_AppPath("extends/"));
foreach($libs as $files) {
    require_once $files;
}
// 言語ファイルの対応
$lang = (isset($query['lang'])) ? $query['lang'] : $_SERVER['HTTP_ACCEPT_LANGUAGE'];

// コントローラ用の言語ファイルを読み込む
LangUI::construct($lang,App::Get_AppPath("View/lang/"));
LangUI::LangFiles(['#common',$controller]);
// アプリにログイン要求が必要で、未ログイン状態ならコントローラーを切替える
if(defined('LOGIN_NEED')) {
    $login = MySession::getLoginInfo();
    if(empty($login)) {     // ログイン状態ではない
        $controller = 'Login';      // ログインコントローラーに制御を渡す
        $method = 'Login';
    } else if($controller === 'Login' && $method !== 'Logout') {
        // Logoutメソッドでなければログインの繰返しを回避する
        $url = App::Get_AppRoot(DEFAULT_CONTROLLER);
        header("Location:{$url}");
    }
}
// モジュールファイルを読み込む
App::LoadModuleFiles($controller);

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
        App::ChangeMethod('','');     // メソッドの書換えはリダイレクトしない
    } else {
        App::ChangeMethod($controller,$method);     // メソッドの書換えはリダイレクトしない
    }
}
App::$ActionMethod= $ContAction;    // アクションメソッド名
//=================================
// デバッグ用の情報ダンプ
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
    "SESSION" => [
        "POST" => MySession::$PostEnv,
        "ENV" => MySession::$PostEnv,
    ],
    'パス情報' => [
        "SERVER" => $_SERVER['REQUEST_URI'],
        "RootURI"=> $approot,
        "appname"=> $appname,
        "Controller"=> $controller,
        "Action"    => $ContAction,
        "Param"    => $params,
    ],
    "ReqCont" => $ReqCont,
    "Location" => App::Get_RelocateURL(),

]);

APPDEBUG::RUN_START();
// コントローラーの実行
$controllerInstance->$ContAction();

APPDEBUG::RUN_FINISH(0);
MySession::CloseSession();
APPDEBUG::DebugDump(0, [
    "セッションクローズ" => [
        "ENVDATA" => MySession::$EnvData,
        "POSTENV" => MySession::$PostEnv,
    ]
]);
// クローズメソッドを呼び出して終了
$controllerInstance->__TerminateApp();

DatabaseHandler::CloseConnection();
