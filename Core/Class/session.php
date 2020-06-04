<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	MySession:	セッション変数の管理
 */
//if($on_server) {
	//セッションの有効期限を5分に設定
	session_set_cookie_params(60 * 5);
	session_start();
//	$_SESSION = array();
//	session_destroy();
//}

class MySession {
	const SESSION_NAME = "_minimvc_biscuit";	// セッションに保存するキー
//	public $Post;
	public static $EnvData;
	public static $PostEnv;

//===============================================================================
// static クラスにおける初期化処理
static function InitSession() {
	debug_dump(0,[
		"SESSION" => $_SESSION,
		"REQUEST" => $_REQUEST,
		"POST" => $_POST,
		"GET" => $_GET,
		]);
	self::$EnvData = $_SESSION[self::SESSION_NAME];
	// セッションに保存した値を戻す
	if(isset(self::$EnvData)) {
		foreach(self::$EnvData as $key => $val) {
			self::$PostEnv[$key] = $val;
		}
	}
	// POST/GET されてきた変数を取り出しセットする(SESSION値は上書き)
	foreach($_REQUEST as $key => $val) {
		if($val == "on") $val = 1; elseif($val==="off") $val = 0;
		self::$PostEnv[$key] = $val;
	}
	if(!isset(self::$PostEnv['cc'])) self::$PostEnv['cc'] = '';		// 一時的
}
//===============================================================================
// セッションに保存する
static function CloseSession() {
	$_SESSION[self::SESSION_NAME] = self::$EnvData;
	debug_dump(0, [
		"CLOSE" => $_SESSION,
	]);
}
//===============================================================================
// POST変数から環境変数に移動する
static function PostToEnv($keys) {
	foreach($keys as $nm) {
		if(array_key_exists($nm,self::$PostEnv)) {
			self::$EnvData[$nm] = self::$PostEnv[$nm];
//			unset(self::$PostEnv[$nm]);
		}
	}
}
//===============================================================================
// POST変数を取り出す
static function PostVars(...$arr) {
	$result = [];
	foreach($arr as $nm) {
		$result[] = (isset(self::$PostEnv[$nm])) ? self::$PostEnv[$nm] : '';
	}
	return $result;
}
//===============================================================================
// ENV変数を取り出す
static function EnvVars(...$arr) {
	$result = [];
	foreach($arr as $nm) {
		$result[] = (isset(self::$EnvData[$nm])) ? self::$EnvData[$nm] : '';
	}
	return $result;
}
//===============================================================================
// POST変数に値が無ければ、デフォルト値をセット
static function SetDefault($nm,$val) {
	if(!isset(self::$EnvData[$nm])) self::$EnvData[$nm] = $val;
}
//===============================================================================
// POST変数に値をセット
static function SetEnvVar($nm,$val) {
	self::$EnvData[$nm] = $val;
	self::$PostEnv[$nm] = $val;
}
//===============================================================================
// デバッグ用ダンプ
static function Dump() {
	print "<pre>\n";
	print "SESSION\n";
	print_r($_SESSION);
	print "ENV\n";
	print_r(self::$EnvData);
	print "POST\n";
	print_r(self::$PostEnv);
	print "</pre>\n";
}
//===============================================================================
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
MySession::InitSession();

debug_dump(0,[
	"REQUEST" => $_REQUEST,
	"POST" => $_POST,
	"GET" => $_GET,
	]);
