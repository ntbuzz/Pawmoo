<?php
/* -------------------------------------------------------------
 * Object-orientation PHP mini_framework
 *   Main: routing and redirection process
 *      method naming basic rule.
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
// for DEBUG utility
require_once('AppDebug.php');
// follow is DEPEND ON this program.
require_once('Config/appConfig.php');
require_once('Common/coreLibs.php');
// also autoload enabled, but not use for performance up
require_once('App.php');
require_once('Base/AppObject.php');
require_once('Base/AppController.php');
require_once('Base/AppModel.php');
require_once('Base/AppFilesModel.php');
require_once('Base/AppView.php');
require_once('Base/AppHelper.php');
require_once('Base/LangUI.php');

// Setup TIMEZONE
date_default_timezone_set(TIME_ZONE);
// for CLI DEBUG
if(CLI_DEBUG) {
	$ln = str_repeat("=", 50);
	print_r($argv);
	echo "{$ln} START HERE ${ln}\n";
}

$redirect = false;      // Redirect flag
$root = basename(dirname(__DIR__));        // Framework Folder
// REQUEST_URI analyze
list($appname,$app_uri,$module,$q_str) = get_routing_path($root);
list($fwroot,$approot) = $app_uri;
list($controller,$method,$filters,$params) = $module;

if(strpos($method,'.')!==FALSE) {
    list($method,$filter) = extract_base_name($method);
    $method = ucfirst(strtolower($method));
} else $filter = empty($filters) ? '': $filters[0];
parse_str($q_str, $query);
if(!empty($q_str)) $q_str = "?{$q_str}";

// is enabled application name
if(empty($appname) || !file_exists("app/$appname")) {
    // 404 not found page
    error_response('app-404.php',$appname,$app_uri,$module);
}
if($controller === 'Error') {       // ERROR PAGE
    $code = $params[0];
    error_response("page-{$code}.php",$appname,$app_uri,$module);
}
require_once("app/{$appname}/Config/config.php");
// Check URI-Redirect direction
if(!defined('FORCE_REDIRECT')) define('FORCE_REDIRECT', FALSE);

if(!is_extst_module($appname,$controller,'Controller')) {
    // if BAD controller name, try DEFAULT CONTROLLER and shift follows
    $cont = (DEFAULT_CONTROLLER === '') ? $appname : DEFAULT_CONTROLLER;
    array_unshift($filters,strtolower($method));    // move method to 0filters
    $module[0] = ucfirst(strtolower($cont));
    $module[1] = $controller;
    $module[2] = $filters;
//    $module[3] = $params;
    list($controller,$method) = $module;
    // RE-TRY DEFAULT CONTROLLER,if FAILED,then NOT FOUND
    if(!is_extst_module($appname,$controller,'Controller')) {
        error_response('page-404.php',$appname,$app_uri,$module);
    }
}
// need REDIRECT, will be Controller name changed.
if($redirect) $module[0] = $controller;
$ReqCont = [
    'root' => $approot,
    'module' => $module,
    'query' => $q_str,
];
$requrl = array_to_URI($ReqCont);
if($redirect) {
    if(CLI_DEBUG) {
        echo "Location:{$requrl}\n";
    } else {
        header("Location:{$requrl}");
    }
    exit;
}
require_once('Class/ClassLoader.php');
ClassLoader::Setup($appname,$controller);   // AutoLoader for Application folder
MySession::InitSession($appname);           // Session Variable SETUP
// INITIALIZED App static class.
App::__Init($appname,$app_uri,$module,$query,$requrl);
// Load Application Common library
$libs = get_php_files(App::Get_AppPath("common/"));
foreach($libs as $files) {
    require_once $files;
}
// Locale parameter in URL query.
if(array_key_exists('lang', $query)) {
    $lang = $query['lang'];
    MySession::set_LoginValue(['LANG' => $lang]);
} else {
    $lang = MySession::get_LoginValue('LANG');
    if($lang === NULL) $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
}
if(empty($lang)) $lang = DEFAULT_LANG;
// Load Common Locale tranlate parameter
LangUI::construct($lang,App::Get_AppPath("View/lang/"),['#common',$controller]);
// Load Application MODULE files. (Controller,Model,View,Helpe)
App::LoadModuleFiles($controller);
$ContClass = "{$controller}Controller";
// Create Controller CLASS
$controllerInstance = new $ContClass();
// Method existance Check
if(!$controllerInstance->is_enable_action($method)) {
    if(FORCE_REDIRECT || $method==='') {
        $method = $controllerInstance->defaultAction;   // get DEFAULT method
    } else {
        $module[0] = $controller;       // may-be rewrited
        $module[1] = $method;           // may-be rewrited
        error_response('page-404.php',$appname,$app_uri,$module);
    }
}
if(strcasecmp($appname,$controller) === 0) {
    App::ChangeMethod('',$method);     // hide controller in URI
} else {
    App::ChangeMethod($controller,$method);
}
// remind Controller, Method name in App class
App::$Controller  = $controller;
App::$Method= $method;
//=================================
// デバッグ用の情報ダンプ
debug_log(DBMSG_SYSTEM, [
    '#DebugInfo' => [
        "Application"=> $appname,
        "Controller"=> $controller,
        "Class"     => $ContClass,
        "Method"    => $method,
        "Filters"   => $filters,
        "URI"       => $requrl,
        "QUERY"     => $q_str,
        "Controller"=> App::$Controller,
        "Action"    => App::$Method,
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
        "Action"    => $method,
        "Param"    => $params,
    ],
    "ReqCont" => $ReqCont,
    "Location" => App::Get_RelocateURL(),
]);

debug_run_start();
// Login unnecessary, or Login success returned TRUE.
if($controllerInstance->is_authorised()) {
    // Controller Method Dispacher
    $controllerInstance->ActionDispatch($method);
}

debug_run_time(DBMSG_SYSTEM);
MySession::CloseSession();
debug_log(DBMSG_SYSTEM, [
    "#SessionClose" => [
        "ENVDATA" => MySession::$EnvData,
    ]
]);
// call OUTPUT terminate
$controllerInstance->__TerminateApp();
// Database connection closed.
DatabaseHandler::CloseConnection();
