<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	MySession:	Management SESSION variable, POST/GET variables
 */
//セッションの有効期限を5分に設定
$session_time = (60 * 60);			// SESSION KEEP 60-min
if(CLI_DEBUG) $_SESSION = [];
else {
	ini_set('session.gc_divisor',1);
	ini_set('session.gc_maxlifetime',$session_time);
	session_start();
}
define('PARAMS_NAME','AppData');
define('SYSLOG_ID','Syslog');
define('RESOURCE_ID','Resource');

class MySession {
	public static $EnvData;
	public static $ReqData;
	public static $MY_SESSION_ID;
	private static $SYS_SESSION_ID;
	public static $SysData;			// for SysLog, Resource Push
//==============================================================================
// static クラスにおける初期化処理
static function InitSession($appname = 'default',$controller='',$unset_param = FALSE) {
	$appname = strtolower($appname);
	$session_id = SESSION_PREFIX . "_{$appname}";
	$session_sys="{$session_id}_sys";
//	unset($_SESSION[$session_id]);
	static::$MY_SESSION_ID = $session_id;
	static::$SYS_SESSION_ID = $session_sys;
	// セッションキーがあれば読み込む
	static::$EnvData = (array_key_exists($session_id,$_SESSION)) ? $_SESSION[$session_id] : [];
	static::$SysData = (array_key_exists($session_sys,$_SESSION)) ? $_SESSION[$session_sys] : [];
	// for Login skip on CLI debug.php processing
	if(CLI_DEBUG) {
		static::$EnvData['Login'] = ['username' => 'ntak'];
	}
	// overwrite real POST/GET variables
	static::$ReqData = [];
	$bool_value = [ 'on' => TRUE,'off' => FALSE,'t' => TRUE,'f' => FALSE,'1' => TRUE,'0' => FALSE];
	foreach($_POST as $key => $val) {		// GET parameter will be check query
		if(array_key_exists($key,$bool_value)) $val = $bool_value[$key];
		if(ctype_alnum(str_replace(['-','_'],'', $key))) static::$ReqData[$key] = $val;
	}
	static::$ReqData = array_intval_recursive(static::$ReqData);
	static::$EnvData = array_intval_recursive(static::$EnvData);
	if($unset_param) {
		unset(static::$EnvData[PARAMS_NAME]);           // Delete Style Parameter for AppStyle
		unset(static::$SysData[SYSLOG_ID]);				// delete system Log
//		unset(static::$SysData[RESOURCE_ID][$controller]);	// delete controller resource
//		static::$SysData = [];
	}
}
//==============================================================================
// セッションに保存する
static function CloseSession() {
//	log_dump(['SESSION'=>static::$EnvData,'SYSTEM'=>static::$SysData]);
	$_SESSION[static::$MY_SESSION_ID] = static::$EnvData;
	$_SESSION[static::$SYS_SESSION_ID] = static::$SysData;
}
//---------------------------- 新しいインタフェース ----------------------------
//==============================================================================
// REQUEST変数から環境変数に移動する
static function preservReqData($envKey,...$keys) {
	foreach($keys as $nm) {
		if(array_key_exists($nm,static::$ReqData)) {
			static::$EnvData[$envKey][$nm] = static::$ReqData[$nm];
			unset(static::$ReqData[$nm]);
		}
	}
}
//==============================================================================
// SESSION変数からREQUESTに移動する
static function rollbackReqData($envKey,...$keys) {
	foreach($keys as $nm) {
		if(array_key_exists($nm,static::$EnvData[$envKey])) {
			static::$ReqData[$nm] = static::$EnvData[$envKey][$nm];
//			unset(static::$EnvData[$envKey][$nm]);
		}
		unset(static::$EnvData[$envKey]);
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
// 冗長だが PHP5.6 でも動作する方法をとる
static function setVariables($tt,$arr) {
	foreach($arr as $key => $val) {
		if($tt) static::$EnvData[$key] = $val;
		else static::$ReqData[$key] = $val;
	}
}
//==============================================================================
// setVariables と同じだが、未定義キーだけを値セットする
// 冗長だが PHP5.6 でも動作する方法をとる
static function set_if_empty($tt,$arr) {
	foreach($arr as $key => $val) {
		if($tt) {
			if(!array_key_exists($key,static::$EnvData)) static::$EnvData[$key] = $val;
		} else {
			if(!array_key_exists($key,static::$ReqData)) static::$ReqData[$key] = $val;
		}
	}
}
//==============================================================================
// ENV/REQUEST変数からドット識別子指定でスカラー取得する
static function get_varIDs($tt,$names) {
	$nVal = array_member_value(($tt)?static::$EnvData:static::$ReqData, $names);
	return (is_array($nVal)) ? array_to_text($nVal,',') : $nVal;
}
//==============================================================================
// ENV変数からドット識別子指定で取得する
static function get_envIDs($nameID) {
	return array_member_value(static::$EnvData, $nameID);
}
//==============================================================================
// ENV変数にドット識別子指定で保存する
static function set_envIDs($nameID,$val,$append = FALSE) {
    $ee = &static::$EnvData;
    $mem_arr = explode('.',$nameID);
    foreach($mem_arr as $key) {
        if(!isset($ee[$key])) $ee[$key] = [];
        $ee = &$ee[$key];
    }
	if($append) {
		$prev = (empty($ee)) ? '':"{$ee}\n";
		$ee = "{$prev}{$val}";
	} else $ee = $val;
}
//==============================================================================
// ENV変数にアプリケーションパラメータを識別子指定で値を設定する
static function set_paramIDs($names,$val,$append = FALSE) {
	static::set_envIDs(PARAMS_NAME.".{$names}",$val,$append);
}
//==============================================================================
// ENV変数からアプリケーションパラメータを識別子指定で値を取得
static function get_paramIDs($names) {
	$nVal = array_member_value(static::$EnvData, PARAMS_NAME.".{$names}");
	return $nVal;
}
//==============================================================================
// システムログを格納
static function syslog_SetData($names,$val,$append = FALSE,$resource = FALSE) {
	$kid = ($resource) ? RESOURCE_ID : SYSLOG_ID;
	$ee = &static::$SysData[$kid];
	$mem_arr = array_filter(explode('.',$names),'strlen');
	foreach($mem_arr as $key) {
		if(!isset($ee[$key])) $ee[$key] = [];
		$ee = &$ee[$key];
	}
	if($append) {
		$prev = (empty($ee)) ? '':"{$ee}\n";
		$ee = "{$prev}{$val}";
	} else $ee = $val;
//log_dump(['ID'=>"{$kid}.{$names}",'SYS'=>static::$SysData,'VAL'=>$nVal]);
}
//==============================================================================
// システムログを取得
static function syslog_GetData($names,$resource = FALSE) {
	$kid = ($resource) ? RESOURCE_ID : SYSLOG_ID;
	$nVal = array_member_value(static::$SysData, "{$kid}.{$names}");
//log_dump(['ID'=>"{$kid}.{$names}",'VAL'=>$nVal]);
	return $nVal;
}
//==============================================================================
// システムログのID名変更
static function syslog_RenameID($trans) {
//log_dump(static::$EnvData);
	$rename_member = function($arr) use(&$rename_member,&$trans) {
		if(is_array($arr)) {
			foreach($arr as $key => $val) {
				if(array_key_exists($key,$trans)) {
					$arr[$trans[$key]] = $rename_member($val);
					unset($arr[$key]);
				} else $arr[$key] = $rename_member($val);
			}
		}
		return $arr;
	};
	static::$SysData[SYSLOG_ID] = $rename_member(static::$SysData[SYSLOG_ID]);
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
static function get_envVars($names) => get_varIDs($tt,$names)
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
