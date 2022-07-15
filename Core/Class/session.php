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
	public static $ShmData;		// for app shared memry
	public static $SESSION_LIFE;	// session data alived limit time
	public static $MY_SESSION_ID;	// public prop is DEBUG for Main.php
	private static $SYS_SESSION_ID;
	private static $SHM_SESSION_ID;	// shared memory session env
	private static $Controller;
//==============================================================================
// static クラスにおける初期化処理
static function InitSession($appname = 'default',$controller='',$flags = 0) {
	$env_unset_param = ($flags & SESSION_ENV_UNSET_PARAMS) !== 0;
	$env_life_limit  = ($flags & SESSION_ENV_LIFE_LIMIT) !== 0;
	$config = $GLOBALS['config'];

	$appname = strtolower($appname);
	static::$Controller = (empty($controller)) ? 'Res' : $controller;
	$session_id = SESSION_PREFIX . "_{$appname}";
	$session_id_set = [
		$session_id,
		"{$session_id}_sys",
		"{$session_id}_life",
		SESSION_PREFIX . "_share_mem",
	];
	list(static::$MY_SESSION_ID,static::$SYS_SESSION_ID,$session_life,static::$SHM_SESSION_ID) = $session_id_set;
	list($env,$sys,$s_limit,$shm) = array_keys_value($_SESSION,$session_id_set,[[],[],0,[]]);
	static::$SysData = $sys;
	static::$ShmData = $shm;
	static::$EnvData = array_intval_recursive($env);
	// call from Main.php must be application session limit refresh
	if($env_life_limit) {
		$limit_time = (isset($config->SESSION_LIMIT)) ?$config->SESSION_LIMIT : SESSION_DEFAULT_LIMIT;
		$session_limit_time = strtotime($limit_time);
		$now_time = time();
		if($s_limit <= $now_time) self::ClearSession();
		$_SESSION[$session_life] = static::$SESSION_LIFE = $session_limit_time;
	}
	// for Login skip on CLI debug.php processing
	if(!isset(static::$EnvData['Login'])) static::$EnvData['Login'] = [];
	// POST variables moved App CLASS
	if($env_unset_param) {
		unset(static::$EnvData[SYSDATA_NAME]);           	// Delete Enviroment
		unset(static::$SysData[SYSLOG_ID][$controller]);	// delete contoller LOG
	}
}
//==============================================================================
// セッション保存の変数を破棄
static function ClearSession() {
	static::$EnvData = static::$SysData = [];	// static::$ShmData = [];
}
//==============================================================================
// セッションに保存する
static function SaveSession() {
	sysLog::debug(['ENV'=>static::$EnvData,'SYS'=>static::$SysData,'SHM'=>static::$ShmData]);
	$_SESSION[static::$MY_SESSION_ID] = static::$EnvData;
	$_SESSION[static::$SYS_SESSION_ID] = static::$SysData;
	$_SESSION[static::$SHM_SESSION_ID] = static::$ShmData;
	session_write_close();
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
	self::setIDs(static::$EnvData,$nameID,$val,$append);
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
    $key_arr = str_explode('.',$names);
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
		unset($nVal[$tag]);           	// DDelete Enviroment
	}
}
//==============================================================================
// アプリケーション共通データ(AppShm)
static function getShmData($names,$unset=false) {
	$nVal = array_member_value(static::$ShmData, $names);
	if($unset) self::unsetShmData($names);
	return $nVal;
}
//==============================================================================
// 配列にドット識別子指定で保存する
private static function setIDs(&$ee,$nameID,$val,$append = FALSE) {
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
static function setShmData($names,$val) {
	self::setIDs(static::$ShmData,$names,$val);
}
//==============================================================================
static function unsetShmData($names='') {
    $key_arr = str_explode('.',$names);
	$tag = array_pop($key_arr);
	if(empty($tag)) {
		static::$ShmData = [];
	} else {
		$nVal = &static::$ShmData;
		if(empty($nVal)) return;
		foreach($key_arr as $nm) {
			if(!array_key_exists($nm,$nVal)) return;
			$nVal = &$nVal[$nm];
			if(!is_array($nVal)) return;
		}
		unset($nVal[$tag]);           	// Delete Enviroment
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
// PATH　から リソースID を生成
static private function get_resouceID($path) {
	$pval = explode('/',$path);
	if($pval[1]==='res') $pval[2] = 'core';
	$pval[1] = '^^ResFile';
	$pval = array_map(function($v) { return str_replace('.','_',$v);},array_slice($pval,1));
	return implode('.',$pval);
}
//==============================================================================
// リソースを格納
static function resource_SetData($path,$resource) {
	$id = self::get_resouceID($path);
	self::setEnvIDs($id,$resource);
	$fullpath = "resource_files/{$path}";
	list($target,$file) = extract_path_filename($fullpath);
	if(!is_dir($target)) mkdir($target,0777,true);
	file_put_contents($fullpath,$resource);
}
//==============================================================================
// システムログを取得
static function resource_GetData($path) {
	$id = self::get_resouceID($path);
	$nVal = array_member_value(static::$EnvData, $id);
	return $nVal;
}
//==============================================================================
// システムログを格納
static function syslog_SetData($names,$val,$append = FALSE,$resource = FALSE) {
	$kid = ($resource) ? RESOURCE_ID : SYSLOG_ID;
	$ee = &static::$SysData[$kid];
	$mem_arr = str_explode('.',$names);
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
