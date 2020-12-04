<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	MySession:	Management SESSION variable, POST/GET variables
 */
//セッションの有効期限を5分に設定
$session_time = (60 * 60);			// SESSION KEEP 60-min
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
	$session_id = SESSION_PREFIX . "_{$appname}";
	static::$MY_SESSION_ID = $session_id;
	// セッションキーがあれば読み込む
	static::$EnvData = (array_key_exists($session_id,$_SESSION)) ? $_SESSION[$session_id] : [];
	// for Login skip on CLI debug.php processing
	if(DEBUGGER && CLI_DEBUG) {
		static::$EnvData['Login'] = ['username' => 'ntak'];
	}
	// overwrite real POST/GET variables
	static::$ReqData = [];
	$bool_value = [ 'on' => TRUE,'off' => FALSE,'t' => TRUE,'f' => FALSE,'1' => TRUE,'0' => FALSE];
	foreach($_REQUEST as $key => $val) {
		if(array_key_exists($key,$bool_value)) $val = $bool_value[$key];
		if(ctype_alnum(str_replace(['-','_'],'', $key))) static::$ReqData[$key] = $val;
	}
	static::$ReqData = array_intval_recursive(static::$ReqData);
	static::$EnvData = array_intval_recursive(static::$EnvData);
}
//==============================================================================
// セッションに保存する
static function CloseSession() {
	$_SESSION[static::$MY_SESSION_ID] = static::$EnvData;
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
// ENV(tt=TRUE) または REQ(tt=FALSE) 変数値を返す
static function getValue($tt,$key) {
	$varData = ($tt) ? static::$EnvData : static::$ReqData;
	return (array_key_exists($key,$varData)) ? $varData[$key] : NULL;
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
		if(!array_key_exists($key,static::$$varData)) static::$$varData[$key] = $val;
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
	if($id === NULL || empty($LoginData)) return $LoginData;
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
==============================================================================
	旧メソッドから新メソッドへの読替え
static function PostToEnv($keys)	=> preservReqData() , rollbackReqData(...$keys)
static function PostVars(...$arr)	=> getVariables($tt,...$arr)
static function EnvVars(...$arr)	=> getVariables($tt,...$arr)
static function get_envVars($names) => get_envIDs($names)
static function SetDefault($nm,$val)=> set_if_empty($tt,$arr)
static function SetEnvVar($nm,$val) => setVariables($tt,$arr)
static function SetPostVars($arr)  	=> setVariables($tt,$arr)
static function UnsetEnvData($arr) 	=> rm_EnvData(...$arr)
static function getLoginValue($id) 	=> get_LoginValue($id)
static function setLoginValue($id,$val) => set_LoginValue($array)
static function getLoginInfo() 		=> get_LoginValue(NULL)
static function SetLogin($login) 	=> setup_Login($login)
static function ClearLogin()  		=> setup_Login(NULL)
*/

}
