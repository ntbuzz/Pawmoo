<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	MySession:	Management SESSION variable, POST/GET variables
 */
//セッションの有効期限を5分に設定
$session_time = (60 * 5);			// SESSION KEEP 5-min
ini_set('session.gc_divisor',1);
ini_set('session.gc_maxlifetime',$session_time);
if(CLI_DEBUG) $_SESSION = [];
else session_start();

class MySession {
	public static $EnvData;
	public static $ReqData;
	public static $MY_SESSION_ID;
//==============================================================================
// static クラスにおける初期化処理
static function InitSession($appname = 'default') {
	if(!defined('DEFAULT_USER')) define('DEFAULT_USER',['login_user' => 'ntak']);
	$session_id = "_minimvc_waffle_map_{$appname}";
	static::$MY_SESSION_ID = $session_id;
	// セッションキーがあれば読み込む
	static::$EnvData = (array_key_exists($session_id,$_SESSION)) ? $_SESSION[$session_id] : [];
	// for Login skip on CLI debug.php processing
	if(DEBUGGER && CLI_DEBUG) {
		static::$EnvData['Login'] = DEFAULT_USER;
	}
	// overwrite real POST/GET variables
	static::$ReqData = [];
	$bool_value = [ 'on' => TRUE,'off' => FALSE,'t' => TRUE,'f' => FALSE,'1' => TRUE,'0' => FALSE];
	foreach($_REQUEST as $key => $val) {
		if(array_key_exists($key,$bool_value)) $val = $bool_value[$key];
		if(ctype_alnum(str_replace(['-','_'],'', $key))) static::$ReqData[$key] = $val;
	}
}
//==============================================================================
// セッションに保存する
static function CloseSession() {
	$_SESSION[static::$MY_SESSION_ID] = static::$EnvData;
	debug_log(FALSE, [
		"CLOSE" => $_SESSION,
	]);
}
//---------------------------- 新しいインタフェース ----------------------------
//==============================================================================
// REQUEST変数から環境変数に移動する
static function preservReqData(...$keys) {
	foreach($keys as $nm) {
		if(array_key_exists($nm,static::$ReqData)) {
			static::$EnvData[$nm] = static::$ReqData[$nm];
			unset(static::$ReqData[$nm]);
		}
	}
}
//==============================================================================
// SESSION変数からREQUESTに移動する
static function rollbackReqData(...$keys) {
	foreach($keys as $nm) {
		if(array_key_exists($nm,static::$EnvData)) {
			static::$ReqData[$nm] = static::$EnvData[$nm];
			unset(static::$EnvData[$nm]);
		}
	}
}
//==============================================================================
// ENV(tt=TRUE) または REQ(tt=FALSE) 変数から値を取得した配列で返す
static function getVariables($tt,...$arr) {
	$varData = ($tt) ? static::$EnvData : static::$ReqData;
	$result = [];
	foreach($arr as $nm) {
		$result[] = (array_key_exists($nm,$varData)) ? $varData[$nm] : '';
	}
	return $result;
}
//==============================================================================
// ENV(tt=TRUE) または REQ(tt=FALSE) 変数に値をセット
// ENV変数を取り出す
static function setVariables($tt,$arr) {
	$varData = ($tt) ? 'EnvData' : 'ReqData';
	$result = [];
	foreach($arr as $key => $val) {
		static::$$varData[$key] = $val;
	}
}
//==============================================================================
// setVariables と同じだが、未定義キーだけを値セットする
static function set_if_empty($tt,$arr) {
	$varData = ($tt) ? 'EnvData' : 'ReqData';
	$result = [];
	foreach($arr as $key => $val) {
		if(array_key_exists($key,static::$$varData)) static::$$varData[$key] = $val;
	}
}
//==============================================================================
// ENV変数を識別子指定で取得する
static function get_envIDs($names) {
	$vset = (mb_strpos($names,'.') !== FALSE) ? explode(".", $names) : [ $names ];
	$nVal = static::$EnvData;
	foreach($vset as $nm) {
		$nVal = (array_key_exists($nm,$nVal)) ? $nVal[$nm] : '';
	}
	return (is_array($nVal)) ? array_to_text($nVal,',') : $nVal;
}
//==============================================================================
// ENV変数をクリア
static function rm_EnvData(...$arr) {
	foreach($arr as $key) unset(static::$EnvData[$key]);
}
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// ログイン情報を取得
static function get_LoginValue($id = NULL) {
	$LoginData = static::$EnvData['Login'];
	if($id === NULL) return $LoginData;
	return (array_key_exists($id,$LoginData)) ? $LoginData[$id] : '';
}
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// ログイン情報に書込
static function set_LoginValue($arr) {
	$LoginData = static::$EnvData['Login'];
	foreach($arr as $key => $val) $LoginData[$key] = $val;
	self::setup_Login($LoginData);
}
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// ログイン情報を置換
static function setup_Login($login=NULL) {
	if($login === NULL) unset(static::$EnvData['Login']);
	else static::$EnvData['Login'] = $login;
	// SESSION 変数に即時反映させる
	$_SESSION[static::$MY_SESSION_ID] = static::$EnvData;
}

/*
//==============================================================================
// POST変数から環境変数に移動する
static function PostToEnv($keys) {
	foreach($keys as $nm) {
		if(array_key_exists($nm,static::$ReqData)) {
			static::$EnvData[$nm] = static::$ReqData[$nm];
		}
	}
}
//==============================================================================
// POST変数を取り出す
static function PostVars(...$arr) {
	$result = [];
	foreach($arr as $nm) {
		$result[] = (isset(static::$ReqData[$nm])) ? static::$ReqData[$nm] : '';
	}
	return $result;
}
//==============================================================================
// ENV変数を取り出す
static function EnvVars(...$arr) {
	$result = [];
	foreach($arr as $nm) {
		$result[] = (isset(static::$EnvData[$nm])) ? static::$EnvData[$nm] : '';
	}
	return $result;
}
//==============================================================================
// ENV変数を取り出す
static function get_envVars($names) {
	$vset = (mb_strpos($names,'.') !== FALSE) ? explode(".", $names) : [ $names ];
	$nVal = static::$EnvData;
	foreach($vset as $nm) {
		$nVal = (isset($nVal[$nm])) ? $nVal[$nm] : '';
	}
	return (is_array($nVal)) ? array_to_text($nVal,',') : $nVal;
}
//==============================================================================
// POST変数に値が無ければ、デフォルト値をセット
static function SetDefault($nm,$val) {
	if(!isset(static::$EnvData[$nm])) static::$EnvData[$nm] = $val;
}
//==============================================================================
// POST変数に値をセット
static function SetEnvVar($nm,$val) {
	static::$EnvData[$nm] = $val;
	static::$ReqData[$nm] = $val;
}
//==============================================================================
// POST変数に値をセット
static function SetPostVars($arr) {
	foreach($arr as $key => $val) static::$ReqData[$key] = $val;
}
//==============================================================================
// ENV変数をクリア
static function UnsetEnvData($arr) {
	foreach($arr as $key) unset(static::$ReqData[$key]);
}
//==============================================================================
// デバッグ用ダンプ
static function Dump() {
	debug_log(-1,[
		"SESSION" => $_SESSION,
		"ENV" => static::$EnvData,
		"POST" => static::$ReqData
	]);
}
//==============================================================================
// ログイン情報を保持
static function getLoginValue($id) {
	return isset(static::$EnvData['Login'][$id])?static::$EnvData['Login'][$id]:NULL;
}
static function setLoginValue($id,$val) {
	static::$EnvData['Login'][$id] = $val;
}

static function getLoginInfo() {
	return isset(static::$EnvData['Login'])?static::$EnvData['Login']:[];
}
static function SetLogin($login) {
	static::$EnvData['Login'] = $login;
}
static function ClearLogin() {
	unset(static::$EnvData['Login']);
	unset($_SESSION[static::$MY_SESSION_ID]['Login']);
}
*/

}
