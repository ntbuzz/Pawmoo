<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 * 	MySession:	Management SESSION variable, POST/GET variables
 */
define('SESSION_DEFAULT_LIMIT','tomorrow 05:00:00');

if(CLI_DEBUG) $_SESSION = [];
else {
	// GLOBAL SESSION LIFE LIMIT
	if(defined(SESSION_INI_MODIFIED)) {
		$global_limit_time = strtotime(SESSION_DEFAULT_LIMIT);	// tomorrow AM 3:00
		$global_session_time = $global_limit_time - time();	// SESSION KEEP as NOW - AM 3:00
		ini_set('session.gc_maxlifetime',"{$global_session_time}");
		ini_set('session.gc_probability','1');
		ini_set('session.gc_divisor','1');
	}
//	session_cache_limiter('none');				// 
//	session_save_path('c:/Windows/temp/pawmoo');		// for windows
	session_start();
}
define('PARAMS_NAME','AppData');
define('SYSLOG_ID','Syslog');
define('RESOURCE_ID','Resource');

class MySession {
	public static $EnvData;
	public static $ReqData;
	public static $SysData;			// for SysLog, Resource Push
	public static $is_EmptyData;	// is empty GET and POST
	public static $SESSION_LIFE;	// session data alived limit time
	public static $MY_SESSION_ID;	// public prop is DEBUG for Main.php
	private static $SYS_SESSION_ID;
	private static $Controller;
//==============================================================================
// static クラスにおける初期化処理
static function InitSession($appname = 'default',$controller='',$flags = 0) {
	$env_unset_param = ($flags & SESSION_ENV_UNSET_PARAMS) !== 0;
	$env_life_limit  = ($flags & SESSION_ENV_LIFE_LIMIT) !== 0;
	$env_post_data  = ($flags & SESSION_ENV_PICKUP_POST) !== 0;

	$appname = strtolower($appname);
	static::$Controller = (empty($controller)) ? 'Res' : $controller;
	static::$MY_SESSION_ID = $session_id = SESSION_PREFIX . "_{$appname}";
	static::$SYS_SESSION_ID= $session_sys="{$session_id}_sys";
	$session_life = "{$session_id}_life";
	list($s_limit,$env,$sys) = array_filter_values($_SESSION,[$session_life,$session_id,$session_sys],[0,[],[]]);
	static::$EnvData = array_intval_recursive($env);
	static::$SysData = $sys;
	static::$ReqData = [];
	// for Login skip on CLI debug.php processing
	if(CLI_DEBUG) static::$EnvData['Login'] = [];
	// call from Main.php must be application session limit refresh
	if($env_life_limit) {
		$limit_time = (defined('SESSION_LIMIT')) ? SESSION_LIMIT : SESSION_DEFAULT_LIMIT;
		$session_limit_time = strtotime($limit_time);
		$now_time = time();
		if($s_limit <= $now_time) self::ClearSession();
		$_SESSION[$session_life] = static::$SESSION_LIFE = $session_limit_time;
	}
	// POST variables pickup to $ReqData
	if($env_post_data) {
		$bool_value = [ 'on' => TRUE,'off' => FALSE,'t' => TRUE,'f' => FALSE,'1' => TRUE,'0' => FALSE];
		foreach($_POST as $key => $val) {		// GET parameter is set to App::$Query by App class initializ
			if(array_key_exists($key,$bool_value)) $val = $bool_value[$key];
			if(ctype_alnum(str_replace(['-','_'],'', $key))) static::$ReqData[$key] = $val;
		}
		static::$ReqData = array_intval_recursive(static::$ReqData);
	}
	if($env_unset_param) {
		unset(static::$EnvData[PARAMS_NAME]);           	// Delete Style Parameter for AppStyle
		unset(static::$SysData[SYSLOG_ID][$controller]);	// delete contoller LOG
	}
	static::$is_EmptyData = empty($_POST) && empty($_GET);
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
static function getEnvData($keys) {
	return self::getValue2(static::$EnvData,$keys);
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
static function assignPagingIDs($id,$val) {
	$id_name = self::PagingIDs($id);
	if($val==='') return array_member_value(static::$EnvData,$id_name);
	self::setPagingIDs($id,$val);
	return $val;
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
//==============================================================================
// REQ変数操作、REQ変数はフラットな連想配列構造なので、ドット識別子IFは不要
//==============================================================================
// POST変数値を返す
static function getPostValues(...$keys) {
	if(count($keys)===1) $keys = $keys[0];
	return self::getVariables2(static::$ReqData,$keys);
}
static function getPostData($keys) {
	return self::getValue2(static::$ReqData,$keys);
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
// setVariables と同じだが、未定義キーだけを値セットする
// 冗長だが PHP5.6 でも動作する方法をとる
private static function set_if_empty2(&$data,$arr) {
	foreach($arr as $key => $val) {
		if(!array_key_exists($key,$data)) $data[$key] = $val;
	}
}
//==============================================================================
// ENV変数にアプリケーションパラメータを識別子指定で設定する
static function set_paramIDs($names,$val) {
	self::setEnvIDs(PARAMS_NAME.".{$names}",$val);
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
	if(array_key_exists('Login',static::$EnvData)) {
		$LoginData = static::$EnvData['Login'];
		if($id === NULL) return $LoginData;
		if(is_array($id)) return array_filter_values($LoginData,$id);
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
	旧メソッドから新メソッドへの読替え
PostToEnv($keys)	=>	preservReqData($envKey,...$keys)	// REQUEST変数から環境変数に移動する
						rollbackReqData($envKey,...$keys)	// SESSION変数からREQUESTに移動する
PostVars(...$arr)	=> 	getPostValues(...$keys)				// 複数のPOST変数値を返す
						getPostData($keys)					// POST変数値
SetPostVars($arr)  	=> 	setPostVariables($arr)				// POST変数に値をセット
						setPost_if_empty($arr)				// 未定義キーだけを値セットする
EnvVars(...$arr)	=> 	getEnvValues(...$keys)				// 複数のENV変数値を返す
						getEnvData($keys)					// ENV変数値
SetEnvVar($nm,$val) =>	setEnvVariables($arr)				// 複数のENV変数に値をセット
SetDefault($nm,$val)=> 	setEnv_if_empty($arr)				// 未定義キーだけを値セットする
get_envVars($names) =>	getEnvIDs($id_name,$scalar)			// ドット識別子指定でENV変数を取得
						setEnvIDs($nameID,$val,$append )	// ENV変数にドット識別子指定で保存する
						set_paramIDs($names,$val)			// AddData変数にnames識別子指定で設定する
						get_paramIDs($names)				// AppData変数からname識別子指定で値を取得
UnsetEnvData($arr) 	=> 	rm_EnvData(...$arr)					// ENV変数をクリア
						rmEnvIDs($nameID)					// ドット識別子指定でENV変数を削除
getLoginValue($id)		=> get_LoginValue($id)				// ログイン情報を取得
setLoginValue($id,$val) => get_LoginValue($arr)				// ログイン情報に書込
getLoginInfo() 			=> get_LoginValue(NULL)				// ログイン配列を取得
SetLogin($login) 		=> setup_Login($login)				// ログイン情報を置換
ClearLogin()  			=> setup_Login(NULL)				// ログイン情報を消去
=================================================================
以下は新メソッドのみ
	ページング情報のR/W
		assignPagingIDs($id,$val)
		getPagingIDs($id)
		setPagingIDs($id,$val)
	システムログの操作
		syslog_SetData($names,$val,$append,$resource)		// システムログを格納
		syslog_GetData($names,$resource)					// システムログを取得
		syslog_RenameID($trans)								// システムログのID名変更
*/

