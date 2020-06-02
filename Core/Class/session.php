<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	MySession:	セッション変数の管理
 */
if($on_server) {
	//セッションの有効期限を5分に設定
	session_set_cookie_params(60 * 5);
	session_start();
}

class MySession {
	const SESSION_NAME = "_minimvc_biscuit";	// セッションに保存するキー
//	public $Post;
	public static $PostEnv;

//===============================================================================
// static クラスにおける初期化処理
static function InitSession() {
	global $on_server; 
//	if($on_server === '' ) return;
	self::$PostEnv = array();		// 配列を初期化
	$my_session_data = $_SESSION[self::SESSION_NAME];
	// セッションに保存した値を戻す
	if(isset($my_session_data)) {
		foreach($my_session_data as $key => $val) {
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
	global $on_server; 
	if($on_server === '' ) return;
	$_SESSION[self::SESSION_NAME] = self::$PostEnv;
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
// POST変数に値が無ければ、デフォルト値をセット
static function SetDefault($nm,$val) {
	if(!isset(self::$PostEnv[$nm])) self::$PostEnv[$nm] = $val;
}
//===============================================================================
// POST変数に値をセット
static function SetVars($nm,$val) {
	self::$PostEnv[$nm] = $val;
}
//===============================================================================
// デバッグ用ダンプ
static function Dump() {
	print "<pre>\n";
	print "SESSION\n";
	print_r($_SESSION);
	print "POST\n";
	print_r(self::$PostEnv);
	print "</pre>\n";
}

}
// セッション変数を初期化
MySession::InitSession();
/*
APPDEBUG::arraydump(0, [
    "Initセッション" => MySession::$PostEnv,
]);
*/
