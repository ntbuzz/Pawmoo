<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	MySession:	セッション変数の管理
 */
if($on_server) {
	session_start();
}

class MySession {

//	public $Post;
	public static $PostEnv;

//===============================================================================
// static クラスにおける初期化処理
static function InitSession() {
	global $on_server; 
	if($on_server === '' ) return;
	$ignoreId = array("PHPXSESSID","_minimvc_session");
	self::$PostEnv = array();		// 配列を初期化
	// セッションに保存した値を戻す
	foreach($_SESSION as $key => $val) {
		if(! in_array($key,$ignoreId)) {
			self::$PostEnv[$key] = $val;
		}
	}
	// POST/GET されてきた変数を取り出しセットする(SESSION値は上書き)
	foreach($_REQUEST as $key => $val) {
		if($val == "on") $val = 1; elseif($val==="off") $val = 0;
		if(! in_array($key,$ignoreId)) {
			self::$PostEnv[$key] = $val;
		}
	}
	if(!isset(self::$PostEnv['cc'])) self::$PostEnv['cc'] = '';		// 一時的
}
//===============================================================================
// セッションに保存する
static function CloseSession() {
	global $on_server; 
	if($on_server === '' ) return;
	foreach(self::$PostEnv as $key => $val) {
		$_SESSION[$key] = $val;
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
static function Dump() {
	print "<pre>\n";
	print "SESSION\n";
	print_r($_SESSION);
	print "POST\n";
	print_r(self::$PostEnv);
	print "</pre>\n";
}
/*
//===============================================================================
// コンストラクタでセッション接続
// 日付変換 gmdate('Y-m-d',($this->EntDate - 25569) * 60 * 60 *24);
	function __construct(){
		$ignoreId = array("PHPXSESSID","_minimvc_session");
		foreach($_REQUEST as $key => $val) {
			if($val == "on") $val = 1; elseif($val==="off") $val = 0;
			$this->$key = $val;
			if(! in_array($key,$ignoreId)) {
				$_SESSION[$key] = $val;
			}
		}
		unset($this->Post);
		foreach($_POST as $key => $val) {
			$this->Post[$key] = $val;
		}
		if(!isset($this->Post['cc'])) $this->Post['cc'] = '';		// 一時的
		// 共通環境
		if(!isset($this->Env['LAYOUT'])) $this->Setup['LAYOUT'] = 'Layout.tpl';
	}
//===============================================================================
// デストラクタでセッション切断
	function __destruct() {
	//	$_SESSION['DEBUGGING'] = App::$DebugMessage;
	//	$this->Dump();
	}
//===============================================================================
	function setPostValue($key,$val='') {
		if(isset($this->Post[$key]) && ($this->Post[$key] !== '')) return;
		$this->Post[$key] = $val;
		// セッション変数にポスト変数のキーがあれば書き換える
		if(isset($_SESSION[$key])) $this->Post[$key] = $_SESSION[$key];
	}
//===============================================================================
	function getValue($nm) {
		return $_SESSION["db_".$nm];
	}
//===============================================================================
	function unsetValue($nm) {
		unset($_SESSION["db_".$nm]);
	}
//===============================================================================
	function ClearSession() {
		session_unset();
	}
//===============================================================================
	function EndSession() {
		$_SESSION = array();
		session_unset();
		session_destroy();
	}
//===============================================================================
// セッション変数のチェック
	function IsChecked($env,$nm) {
		$str = explode(",", $_SESSION["db_".$env]);
		foreach($str as $val) {
			if($val == $nm ) {
				return " checked";
			}
		}
		return "";
	}
*/

}
