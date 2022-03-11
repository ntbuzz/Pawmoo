<?php
define('ROOT_DIR', realpath(__DIR__ . '/../..'));

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
$_SERVER['HTTP_HOST'] = 'localhost';
// framework config
require_once(ROOT_DIR . '/site-config.php');
require_once(ROOT_DIR . '/Core/Config/appConfig.php');
if(!defined('CLI_DEBUG')) define('CLI_DEBUG',FALSE);

require_once(ROOT_DIR . '/Core/AppDebug.php');
require_once(ROOT_DIR . '/Core/Common/appLibs.php');
require_once(ROOT_DIR . '/Core/Common/coreLibs.php');
require_once(ROOT_DIR . '/Core/Handler/DatabaseHandler.php');
require_once(ROOT_DIR . '/Core/Base/AppDatabase.php');

date_default_timezone_set('Asia/Tokyo');

$in = explode('/',"{$cmd_arg}//");
// lower-case convert,except '$defs' parameter.
// command-line: php database.php appname/[renew |view | test]/model1/model2/...
// renew = re-create table & view
// view  = re-create view (default)
// test  = echo SQL, not-execute
$appname = strtolower(array_shift($in));
$cmd = strtolower(array_shift($in));
$list = array_filter($in,'strlen');

$config_path = ROOT_DIR . "/app/{$appname}/Config";
$data_path = "{$config_path}/db_data/";
$defsfile = "{$config_path}/Setup/Config.php";

require_once($defsfile);

$config->Setup(GlobalConfig,SITE_PRODUCTION);

SetupLoader::Setup($appname,AliasMap);

$ln = str_repeat("=", 50);
print_r($argv);
echo "{$ln} START HERE ${ln}\n";

if($cmd === 'remake') {
	$list = RemakeView;
	$cmd = 'self';
}
foreach($list as $defs) {
	$setup_class = "{$defs}Setup";
	$not_found = true;
	foreach(AliasMap as $fname => $classes) {
		if(in_array($setup_class,$classes)) {
			$db = new $setup_class($data_path);
			$db->execute($cmd);
			$not_found = false;
			break;
		}
	}
	if($not_found) echo "'{$setup_class}' NOT FOUND!\n";
}
