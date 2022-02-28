<?php
/*
	プロトタイプ・テストクラス
*/
define('ROOT_DIR', realpath(__DIR__ . '/../..'));
define('IND_DIR', realpath(__DIR__ . '/../../Core'));
// デバッグ用のクラス
require_once(ROOT_DIR . '/Core/AppDebug.php');
require_once(ROOT_DIR . '/Core/Config/appConfig.php');
require_once(ROOT_DIR . '/Core/Common/coreLibs.php');
require_once(ROOT_DIR . '/Core/Common/appLibs.php');
require_once(ROOT_DIR . '/Core/Common/arrayLibs.php');
require_once(ROOT_DIR . '/Core/Handler/DatabaseHandler.php');
require_once('AppBase.php');
require_once('AppSchema.php');
require_once('AppSetup.php');
//require_once('database.php');

date_default_timezone_set('Asia/Tokyo');

//if(!defined('DEFAULT_LANG'))	 define('DEFAULT_LANG', 'ja');				// Language
//if(!defined('DEFAULT_REGION'))	 define('DEFAULT_REGION', 'jp');			// Region code

$ln = str_repeat("=", 50);
print_r($argv);
echo "{$ln} START HERE ${ln}\n";

list($self,$cmd,$appname,$model) = array_alternative($argv,4);

SetupLoader::Setup($appname);

$usrconfig = ROOT_DIR ."/app/{$appname}/Config/config.php";
if(is_file($usrconfig)) {
	require_once($usrconfig);
	$config->Setup(GlobalConfig,'development');
}

$pawmoo = new AppSetup($appname);
$pawmoo->execute($cmd,$model);

