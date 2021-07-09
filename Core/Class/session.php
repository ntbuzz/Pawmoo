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
		unset(static::$EnvData[PARAMS_NAME]);           	// Delete Style Parameter for AppStyle
		unset(static::$SysData[SYSLOG_ID][$controller]);	// delete contoller LOG
//		static::$EnvData = [];		// for DEBUG
//		static::$SysData = [];		// for DEBUG
	}
}
//==============================================================================
// セッションに保存する
static function CloseSession() {
//	sysLog::dump(['SESSION'=>static::$EnvData,'SYSTEM'=>static::$SysData]);
	$_SESSION[static::$MY_SESSION_ID] = static::$EnvData;
	$_SESSION[static::$SYS_SESSION_ID] = static::$SysData;
}
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
// ENV変数値を返す
static function getEnvValues(...$keys) {
	if(count($keys)===1) $keys = $keys[0];
	return self::getVariables2(static::$EnvData,$keys);
}
//==============================================================================
// ENV変数に値をセット
static function setEnvVariables($arr) {
	self::setVariables2(static::$EnvData,$arr);
}
//==============================================================================
// setVariables と同じだが、未定義キーだけを値セットする
static function setEnv_if_empty($arr) {
	self::set_if_empty2(static::$EnvData,$arr);
}
//==============================================================================
// ドット識別子指定はENV変数のみ
static function getEnvIDs($id_name,$scalar=TRUE) {
	$nVal = array_member_value(static::$EnvData, $id_name);
	return ($scalar && is_array($nVal)) ? array_to_text($nVal,',') : $nVal;
}
//==============================================================================
// ENV変数にドット識別子指定で保存する
static function setEnvIDs($nameID,$val,$append = FALSE) {
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
// REQ変数操作、REQ変数はフラットな連想配列構造なので、ドット識別子IFは不要
//==============================================================================
// POST変数値を返す
static function getPostValues(...$keys) {
	if(count($keys)===1) $keys = $keys[0];
	return self::getVariables2(static::$ReqData,$keys);
}
//==============================================================================
// POST変数に値をセット
static function setPostVariables($arr) {
	self::setVariables2(static::$ReqData,$arr);
}
//==============================================================================
// setVariables と同じだが、未定義キーだけを値セットする
static function setPost_if_empty($arr) {
	self::set_if_empty2(static::$ReqData,$arr);
}
//==============================================================================
// 連想配列から変数値を返す
private static function getValue2($varData,$key) {
	return (array_key_exists($key,$varData)) ? $varData[$key] : NULL;
}
//==============================================================================
// 連想配列から変数配列を返す
private static function getVariables2($varData,$arr) {
	$result = is_array($arr) ? [] : NULL;
	if(is_array($arr))  {
		foreach($arr as $nm) $result[] = (array_key_exists($nm,$varData)) ? $varData[$nm] : '';
		return $result;
	} else 	return (array_key_exists($arr,$varData)) ? $varData[$arr] : NULL;
}
//==============================================================================
// 連想配列に値をセット
private static function setVariables2(&$varData,$arr) {
	foreach($arr as $key => $val) $varData[$key] = $val;
}
//==============================================================================
// ENV変数にアプリケーションパラメータを識別子指定で設定する
static function set_paramIDs($names,$val) {
	static::setEnvIDs(PARAMS_NAME.".{$names}",$val);
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
//	sysLog::dump(['ID'=>"{$kid}.{$names}",'SYS'=>static::$SysData,'VAL'=>$nVal]);
}
//==============================================================================
// システムログを取得
static function syslog_GetData($names,$resource = FALSE) {
	$kid = ($resource) ? RESOURCE_ID : SYSLOG_ID;
	$nVal = array_member_value(static::$SysData, "{$kid}.{$names}");
//	sysLog::dump(['ID'=>"{$kid}.{$names}",'VAL'=>$nVal]);
	return $nVal;
}
//==============================================================================
// システムログのID名変更
static function syslog_RenameID($trans) {
//	sysLog::dump(static::$EnvData);
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

}
