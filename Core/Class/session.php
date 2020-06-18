<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	MySession:	Management SESSION variable, POST/GET variables
 */
	//セッションの有効期限を5分に設定
	session_set_cookie_params(60 * 5);
	session_start();

class MySession {
	public static $EnvData;
	public static $PostEnv;
	public static $LoginInfo;
	public static $MY_SESSION_ID;

//==============================================================================
// static クラスにおける初期化処理
static function InitSession($appname = 'default') {
	self::$MY_SESSION_ID = "_minimvc_biscuit_{$appname}";
	// for Login skip on CLI debug.php processing
	if(DEBUGGER && empty($_SERVER['SERVER_PORT'])) {
		$_SESSION[self::$MY_SESSION_ID]['Login'] = ['user' => 'aaa'];
	}
	debug_dump(0,[
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
		self::$PostEnv[$key] = $val;
	}
	self::$LoginInfo = (empty(self::$EnvData['Login']) ) ? [] : self::$EnvData['Login'];
}
//==============================================================================
// セッションに保存する
static function CloseSession() {
	$_SESSION[self::$MY_SESSION_ID] = self::$EnvData;
	debug_dump(0, [
		"CLOSE" => $_SESSION,
	]);
}
//==============================================================================
// POST変数から環境変数に移動する
static function PostToEnv($keys) {
	foreach($keys as $nm) {
		if(array_key_exists($nm,self::$PostEnv)) {
			self::$EnvData[$nm] = self::$PostEnv[$nm];
//			unset(self::$PostEnv[$nm]);
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
	debug_dump(1,[
		"SESSION" => $_SESSION,
		"ENV" => self::$EnvData,
		"POST" => self::$PostEnv
	]);
}
//==============================================================================
// ログイン情報を保持
static function getLoginInfo() {
	return self::$EnvData['Login'];
}
static function SetLogin($login) {
	self::$EnvData['Login'] = $login;
}
static function ClearLogin() {
	unset(self::$EnvData['Login']);
}

}
// セッション変数を初期化
//MySession::InitSession();
