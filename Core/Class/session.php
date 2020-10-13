<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	MySession:	Management SESSION variable, POST/GET variables
 */
//セッションの有効期限を5分に設定
$session_time = (60 * 5);			// SESSION KEEP 5-min
ini_set('session.gc_divisor',1);
ini_set('session.gc_maxlifetime',$session_time);
if(!CLI_DEBUG) session_start();
if(!defined('DEFAULT_USER')) define('DEFAULT_USER',['user' => 'ntak']);

class MySession {
	public static $EnvData;
	public static $PostEnv;
	public static $LoginInfo;
	public static $MY_SESSION_ID;
//==============================================================================
// static クラスにおける初期化処理
static function InitSession($appname = 'default') {
	self::$MY_SESSION_ID = "_minimvc_waffle_map_{$appname}";
	// for Login skip on CLI debug.php processing
	if(DEBUGGER && CLI_DEBUG) {
		$_SESSION[self::$MY_SESSION_ID]['Login'] = DEFAULT_USER;
	}
	debug_log(FALSE,[
		"SESSION" => $_SESSION,
		"REQUEST" => $_REQUEST,
		"POST" => $_POST,
		"GET" => $_GET,
		]);
	// セッションキーがあれば読み込む
	self::$EnvData = (array_key_exists(self::$MY_SESSION_ID,$_SESSION)) ? $_SESSION[self::$MY_SESSION_ID]:array();
	// copy from  SESSION variable to POST variable
	if(isset(self::$EnvData)) {
		foreach(self::$EnvData as $key => $val) {
			self::$PostEnv[$key] = $val;
		}
	}
	// overwrite real POST/GET variables
	foreach($_REQUEST as $key => $val) {
		if($val == "on") $val = 1; elseif($val==="off") $val = 0;
		if(ctype_alnum(str_replace(['-','_'],'', $key))) self::$PostEnv[$key] = $val;
	}
	self::$LoginInfo = (empty(self::$EnvData['Login']) ) ? [] : self::$EnvData['Login'];
}
//==============================================================================
// セッションに保存する
static function CloseSession() {
	$_SESSION[self::$MY_SESSION_ID] = self::$EnvData;
	debug_log(FALSE, [
		"CLOSE" => $_SESSION,
	]);
}
//==============================================================================
// POST変数から環境変数に移動する
static function PostToEnv($keys) {
	foreach($keys as $nm) {
		if(array_key_exists($nm,self::$PostEnv)) {
			self::$EnvData[$nm] = self::$PostEnv[$nm];
		}
	}
}
//==============================================================================
// POST変数を取り出す
static function PostVars(...$arr) {
	$result = [];
	foreach($arr as $nm) {
		$result[] = (isset(self::$PostEnv[$nm])) ? self::$PostEnv[$nm] : '';
	}
	return $result;
}
//==============================================================================
// ENV変数を取り出す
static function EnvVars(...$arr) {
	$result = [];
	foreach($arr as $nm) {
		$result[] = (isset(self::$EnvData[$nm])) ? self::$EnvData[$nm] : '';
	}
	return $result;
}
//==============================================================================
// ENV変数を取り出す
static function get_envVars($names) {
	$vset = (mb_strpos($names,'.') !== FALSE) ? explode(".", $names) : [ $names ];
	$nVal = self::$EnvData;
	foreach($vset as $nm) {
		$nVal = (isset($nVal[$nm])) ? $nVal[$nm] : '';
	}
	return (is_array($nVal)) ? array_to_text($nVal,',') : $nVal;
}
//==============================================================================
// POST変数に値が無ければ、デフォルト値をセット
static function SetDefault($nm,$val) {
	if(!isset(self::$EnvData[$nm])) self::$EnvData[$nm] = $val;
}
//==============================================================================
// POST変数に値をセット
static function SetEnvVar($nm,$val) {
	self::$EnvData[$nm] = $val;
	self::$PostEnv[$nm] = $val;
}
//==============================================================================
// POST変数に値をセット
static function SetPostVars($arr) {
	foreach($arr as $key => $val) self::$PostEnv[$key] = $val;
}
//==============================================================================
// ENV変数をクリア
static function UnsetEnvData($arr) {
	foreach($arr as $key) unset(self::$PostEnv[$key]);
}
//==============================================================================
// デバッグ用ダンプ
static function Dump() {
	debug_log(-1,[
		"SESSION" => $_SESSION,
		"ENV" => self::$EnvData,
		"POST" => self::$PostEnv
	]);
}
//==============================================================================
// ログイン情報を保持
static function getLoginInfo() {
	return isset(self::$EnvData['Login'])?self::$EnvData['Login']:[];
}
static function SetLogin($login) {
	self::$EnvData['Login'] = $login;
}
static function ClearLogin() {
	unset(self::$EnvData['Login']);
	unset($_SESSION[self::$MY_SESSION_ID]['Login']);
}

}
