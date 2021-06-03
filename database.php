<?php
/*
 * Object Oriented PHP MVC Framework
 *  Database Creator for command-line
 */
$cmd_arg = $argv[1];
$_SERVER['REQUEST_URI'] = urldecode("/{$cmd_arg}");	// getcwd() . 
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);	// 
$_SERVER['SERVER_NAME'] = getHostByName(getHostName());
$_SERVER['LOCAL_ADDR'] = $_SERVER['SERVER_NAME'];
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ja;en;ja';		// 言語受け入れリスト
$_SERVER['HTTP_REFERER'] = "localhost";
$_SERVER['SERVER_PORT'] = '';

require_once('Core/Config/appConfig.php');
if(!defined('CLI_DEBUG')) define('CLI_DEBUG',FALSE);

require_once('Core/AppDebug.php');
require_once('Core/Common/appLibs.php');
//require_once('Core/Class/session.php');
require_once('Core/Common/coreLibs.php');
require_once('Core/Handler/DatabaseHandler.php');
require_once('Core/Base/AppDatabase.php');

date_default_timezone_set('Asia/Tokyo');
setlocale( LC_ALL, 'ja_JP' );
list($appname,$defs,$exec,$prop) = explode('/',strtolower("{$cmd_arg}///"));
$defs = ucfirst($defs);

$defsfile = "app/{$appname}/Config/Setup/Config.php";

require_once($defsfile);

SetupLoader::Setup($appname,AliasMap);

$setup_class = "{$defs}Setup";

$db = new $setup_class();
$db->execute($exec==='run',$prop);

//MySession::CloseSession();
//DatabaseHandler::CloseConnection();
