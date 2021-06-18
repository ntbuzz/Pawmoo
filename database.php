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

$in = explode('/',"{$cmd_arg}//");
// lower-case convert,except '$defs' parameter.
// command-line: php database.php appname/model/[renew |view | test]
// renew = re-create table & view
// view  = re-create view (default)
// test  = echo SQL, not-execute
list($appname,$defs,$cmd) = array_map(
		function($in,$low) { return ($low) ? strtolower($in) : $in;},
		$in, [true, false, true]);

$config_path = "app/{$appname}/Config";
$data_path = "{$config_path}/db_data/";
$defsfile = "{$config_path}/Setup/Config.php";

require_once($defsfile);

SetupLoader::Setup($appname,AliasMap);

$setup_class = "{$defs}Setup";

foreach(AliasMap as $fname => $classes) {
	if(in_array($setup_class,$classes)) {
		$db = new $setup_class($data_path);
		$db->execute($cmd);
		exit;
	}
}
echo "'{$setup_class}' NOT FOUND!\n";
