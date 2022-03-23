<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	MySession:	Management SESSION variable, POST/GET variables
 */
define('SESSION_DEFAULT_LIMIT','tomorrow 05:00:00');


if(CLI_DEBUG) $_SESSION = [];
else {
	session_cache_limiter('nocache');		// no-chace
	session_save_path(SESSION_SAVE_PATH);	// session data save path in this system
	if (!is_writable(session_save_path())) {
		debug_die(['NOT-WRITABLE'=>session_save_path(),'STAT'=>stat(session_save_path())]);
	}
	// GLOBAL SESSION LIFE LIMIT
	if(defined('SESSION_INI_MODIFIED')) {
		$global_limit_time = strtotime(SESSION_DEFAULT_LIMIT);	// tomorrow AM 3:00
		$global_session_time = $global_limit_time - time();	// SESSION KEEP as NOW - AM 3:00
		ini_set('session.gc_maxlifetime',$global_session_time);
		ini_set('session.gc_probability',1);
		ini_set('session.gc_divisor',1);
	}
	session_start();
	session_regenerate_id( false );		// session security
}
define('APPDATA_NAME','AppData');
define('SYSDATA_NAME','SysData');
define('SYSLOG_ID','Syslog');
define('RESOURCE_ID','Resource');

class MySession {
	public static $EnvData;
	public static $SysData;			// for SysLog, Resource Push
	public static $SESSION_LIFE;	// session data alived limit time
	public static $MY_SESSION_ID;	// public prop is DEBUG for Main.php
	private static $SYS_SESSION_ID;
	private static $Controller;
//==============================================================================
// static クラスにおける初期化処理
static function InitSession($appname = 'default',$controller='',$flags = 0) {
	$env_unset_param = ($flags & SESSION_ENV_UNSET_PARAMS) !== 0;
	$env_life_limit  = ($flags & SESSION_ENV_LIFE_LIMIT) !== 0;
	$config = $GLOBALS['config'];

	$appname = strtolower($appname);
	static::$Controller = (empty($controller)) ? 'Res' : $controller;
	static::$MY_SESSION_ID = $session_id = SESSION_PREFIX . "_{$appname}";
	static::$SYS_SESSION_ID= $session_sys="{$session_id}_sys";
	$session_life = "{$session_id}_life";
	list($s_limit,$env,$sys) = array_keys_value($_SESSION,[$session_life,$session_id,$session_sys],[0,[],[]]);
	static::$EnvData = array_intval_recursive($env);
	static::$SysData = $sys;
	// call from Main.php must be application session limit refresh
	if($env_life_limit) {
		$limit_time = (isset($config->SESSION_LIMIT)) ?$config->SESSION_LIMIT : SESSION_DEFAULT_LIMIT;
//		$limit_time = defined('SESSION_LIMIT')) ? SESSION_LIMIT : SESSION_DEFAULT_LIMIT;
		$session_limit_time = strtotime($limit_time);
		$now_time = time();
		if($s_limit <= $now_time) self::ClearSession();
		$_SESSION[$session_life] = static::$SESSION_LIFE = $session_limit_time;
	}
	// for Login skip on CLI debug.php processing
	if(!isset(static::$EnvData['Login'])) static::$EnvData['Login'] = [];
	// POST variables moved App CLASS
	if($env_unset_param) {
		unset(static::$EnvData[SYSDATA_NAME]);           	// Delete Style Parameter for AppStyle
		unset(static::$SysData[SYSLOG_ID][$controller]);	// delete contoller LOG
	}
}
//==============================================================================
// セッション保存の変数を破棄
static function ClearSession() {
	static::$EnvData = static::$SysData = [];
}
//==============================================================================
// セッションに保存する
static function CloseSession() {
//	sysLog::dump(['SESSION'=>static::$EnvData,'SYSTEM'=>static::$SysData]);
	$_SESSION[static::$MY_SESSION_ID] = static::$EnvData;
	$_SESSION[static::$SYS_SESSION_ID] = static::$SysData;
}
//==============================================================================
// ENV変数値を返す
static function getEnvValues(...$keys) {
	if(count($keys)===1) {
		$keys = $keys[0];
		return (array_key_exists($keys,static::$EnvData)) ? static::$EnvData[$keys] : NULL;
	}
	$result = [];
	foreach($keys as $nm) $result[] = (array_key_exists($nm,static::$EnvData)) ? static::$EnvData[$nm] : '';
	return $result;
}
// 廃止 → getEnvValues() の引数ひとつで代用可
// static function getEnvData($keys) {
// 	return (array_key_exists($key,static::$EnvData)) ? static::$EnvData[$key] : NULL;
// }
//==============================================================================
// ENV変数に値をセット
static function setEnvVariables($arr) {
	foreach($arr as $key => $val) static::$EnvData[$key] = $val;
}
//==============================================================================
// setVariables と同じだが、未定義キーだけを値セットする
// 冗長だが PHP5.6 でも動作する方法をとる
static function setEnv_if_empty($arr) {
	foreach($arr as $key => $val) {
		if(!array_key_exists($key,static::$EnvData)) static::$EnvData[$key] = $val;
	}
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
// ドット識別子指定でENV変数を削除
static function rmEnvIDs($nameID) {
    $ee = &static::$EnvData;
    $mem_arr = explode('.',$nameID);
	$rm = array_pop($mem_arr);
    foreach($mem_arr as $key) {
        if(!isset($ee[$key])) return;
        $ee = &$ee[$key];
    }
	unset($ee[$rm]);
	return false;
}
//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// ページング情報のR/W
private static function PagingIDs($id) {
	$mod = static::$Controller;
	return "Paging.{$mod}.{$id}";
}
static function getPagingIDs($id) {
	$id_name = self::PagingIDs($id);
	return array_member_value(static::$EnvData,$id_name);
}
static function setPagingIDs($id,$val) {
	$mod = "Paging." . static::$Controller;
	$page = array_member_value(static::$EnvData,$mod);
	unset(static::$EnvData['Paging']);	// 他モジュールのパラメータを消す
    $ee = &$page;
    $mem_arr = explode('.',$id);
    foreach($mem_arr as $key) {
        if(!isset($ee[$key])) $ee[$key] = [];
        $ee = &$ee[$key];
    }
	$ee = $val;
	static::$EnvData['Paging'][static::$Controller] = $page;
}
// 値の取得(value===false) または設定
static function assignPagingIDs($id,$val) {
	$id_name = self::PagingIDs($id);
	if($val === false ) return array_member_value(static::$EnvData,$id_name);
	self::setPagingIDs($id,$val);
	return $val;
}
//==============================================================================
// アプリケーション用保存データ(AppData)
static function getAppData($names,$unset=false) {
	$nVal = array_member_value(static::$EnvData, APPDATA_NAME.".{$names}");
	if($unset) self::unsetAppData($names);
	return $nVal;
}
static function setAppData($names,$val) {
	self::setEnvIDs(APPDATA_NAME.".{$names}",$val);
}
static function unsetAppData($names='') {
    $key_arr = array_filter(explode('.',$names),'strlen');
	$tag = array_pop($key_arr);
	if(empty($tag)) {
		unset(static::$EnvData[APPDATA_NAME]);
	} else {
		$nVal = &static::$EnvData[APPDATA_NAME];
		if(empty($nVal)) return;
		foreach($key_arr as $nm) {
			if(!array_key_exists($nm,$nVal)) return;
			$nVal = &$nVal[$nm];
			if(!is_array($nVal)) return;
		}
		unset($nVal[$tag]);           	// Delete Style Parameter for AppStyle
	}
}
//==============================================================================
// 自動消去のシステム保存データ(SysData)へ格納
static function setSysData($names,$val) {
	self::setEnvIDs(SYSDATA_NAME.".{$names}",$val);
}
//==============================================================================
// システム保存データ(SysData)から取得
static function getSysData($names) {
	$nVal = array_member_value(static::$EnvData, SYSDATA_NAME.".{$names}");
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
	if(array_key_exists('Login',static::$EnvData)) {
		$LoginData = static::$EnvData['Login'];
		if($id === NULL) return $LoginData;
		if(is_array($id)) return array_keys_value($LoginData,$id);
		else if(array_key_exists($id,$LoginData)) return $LoginData[$id];
	}
	return NULL;
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
/*
==============================================================================
POST変数値の操作は App クラスへ移動
==============================================================================
		App::PostElements($filter)				getPostValues(...$keys)
												getPostData($keys)
		App::setPostElements($arr)				setPostVariables($arr)
		App::set_if_empty($arr)					setPost_if_empty($arr)
		App::preservReqData($envKey,...$keys)	preservReqData(,...$keys)
		App::rollbackReqData($envKey,...$keys)	rollbackReqData(...$keys)
-------------------------
	旧メソッド読み替えコメントは終了
==============================================================================
*/

