<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  App:  システム変数その他を保持するグローバルクラス
 */
//==============================================================================
class App {
    public static $SysVAR;        // URIROOT, WEBROOT, URI, QUERY 変数
    public static $AppName;         // アプリケーション名
    public static $DocRoot;         // DOCUMENT_ROOT 変数
    public static $Referer;         // HTTP_REFERER 変数
    public static $Query;           // urlのクエリー文字列の連想配列 = $_GET
    public static $Post;            // FORM POSTの連想配列 = $_POST
    public static $Filter;          // メソッドのフィルタ配列の先頭
    public static $Filters;         // メソッドのフィルタ配列
    public static $Params;          // メソッドの数値パラメータ配列
    public static $RawItems;        // フィルタ以降の生パラメータ
    public static $ParamCount;      // 引数の数
    public static $Controller;      // 実行コントローラ名
    public static $Method;    // 呼出しメソッド名
    public static $MethodExtention; // ファイルダイレクトURIの拡張子部分
    private static $appRoot;        // アプリケーションのルートパス
    private static $sysRoot;        // フレームワークのルートパス、／で終る
    private static $ReLocate;       // URLの書き換え
    public static $execURI;
//==============================================================================
// 静的クラスでのシステム変数初期化
	public static function __Init($appname,$app_uri,$module) {
        static::$AppName = $appname;
        list(static::$sysRoot,static::$appRoot) = $app_uri;
        list($controller,$method,$filters,$params, $rawpath) = $module;

        static::$RawItems = $rawpath;
        static::$DocRoot = (empty($_SERVER['DOCUMENT_ROOT'])) ? '' : $_SERVER['DOCUMENT_ROOT'];
        static::$Referer = (empty($_SERVER['HTTP_REFERER'])) ? '' : $_SERVER['HTTP_REFERER'];

		static::$Query	 = xchange_Boolean($_GET);				// same as query string
		static::$Post	 = xchange_Boolean($_POST);				// same as form POST string
		self::ChangeParams($params,false);
        // メソッドの書き換えによるアドレスバー操作用
        static::$execURI = [
			'root' => static::$appRoot,
    		'controller' => $controller,		// ResetModule()で書換える
    		'method' => $method,			// ResetModule()で書換える
			'filter' => NULL,
			'params' => static::$Params,
		];
		list($uri,$q) = fix_explode('?',$_SERVER['REQUEST_URI'],2);
        static::$SysVAR = array(
            'SERVER'	=> $_SERVER['SERVER_NAME'],
            'PORT'		=> $_SERVER['SERVER_PORT'],
            'SYSROOT'	=> static::$sysRoot,
            'APPROOT'	=> static::$appRoot,
            'APPNAME'	=> static::$AppName,
            'REFERER'	=> static::$Referer,
            'REQURI'	=> $uri . array_to_query(static::$Query),
            'URI'		=> $uri,
            'TODAY'		=> date('Y/m/d'),
            'controller'=> NULL,			// dummy , set by following ResetModule()
            'method'	=> NULL,			// dummy , set by following ResetModule()
            'extention' => NULL,			// dummy , set by following ResetModule()
            'filter'	=> NULL,			// dummy , set by following ResetModule()
            'params'	=> static::$Params,
        );
        // モジュール情報を書込む
		self::ResetModule($module);
    }
//==============================================================================
// モジュール情報を更新する
public static function ResetModule($module) { 
        list($controller,$method,$filters,$params) = $module;
		// フィルタ先頭だけ取り出しておく
        static::$Filter = empty($filters) ? '': $filters[0];
        static::$Filters= $filters;
        // メソッドの書き換えによるアドレスバー操作用
		self::SetModuleExecution($controller,$method,$filters,false);
		$method = static::$execURI['method'];
		static::$Controller = $controller;
		static::$Method		= ucfirst(str_replace('-','_',$method));
        $sysVAR = array(
            'controller'=> strtolower($controller),
            'method'	=> strtolower($method),
            'extention' => static::$MethodExtention,
            'filter'	=> static::$Filter,
        );
		static::$SysVAR = array_override(static::$SysVAR,$sysVAR);
        // リクエスト情報を更新
        MySession::setEnvVariables(['sysVAR'=>static::$SysVAR]);
}
//==============================================================================
// モジュール実行URIの書換え、数字パラメータは別に呼び出す
public static function SetModuleExecution($module,$method,$change_filter=[],$relocate = TRUE) { 
	if(strpos($method,'.')!==FALSE) {
		list($method,static::$MethodExtention) = extract_base_name($method);
	} else {
		static::$MethodExtention = FALSE;
	}
    if(!empty($module)) static::$execURI['controller'] = strtolower($module);
    static::$execURI['method'] = strtolower($method);
    if(is_array($change_filter)) static::$execURI['filter'] = $change_filter;
    static::$ReLocate = $relocate;
}
//==============================================================================
// フィルタパスの書き換え
public static function ChangeFilters($change_filter=[],$relocate = TRUE) { 
    if(is_array($change_filter)) static::$execURI['filter'] = $change_filter;
    static::$ReLocate = $relocate;
}
//==============================================================================
// パラメータパスの置換
public static function ChangeParams($params,$relocate = TRUE) { 
	static::$ParamCount = count($params);	// ZERO 充填前のパラメータ数
    $params = array_values( array_slice($params + array_fill(0,10,0), 0, 10));
    static::$execURI['params'] = static::$Params = $params;
    static::$ReLocate = $relocate;        	// URLの書き換えYes
}
//==============================================================================
// パラメータの消去
public static function CleareParams() {
	static::$execURI['params'] = static::$Params = array_fill(0,10,NULL);
//	for($i=0;$i < count(static::$Params);++$i) static::$Params[$i] = NULL;
}
//==============================================================================
// メソッドとクエリ文字列の置換後のURLを返す
private static function get_extensionURL() { 
	if(static::$MethodExtention !== FALSE) {
		$execurl = array_filter(static::$execURI,function($v,$k) { return ($k !== "method");},ARRAY_FILTER_USE_BOTH);
		$execurl[] = static::$execURI['method'] . "." . static::$MethodExtention;
	} else 	$execurl = static::$execURI;
    return array_to_URI($execurl);
}
//==============================================================================
// パラメータ無しのパス
public static function Get_PagingPath() { 
	$path_arr = array_keys_value(static::$execURI,['root','controller','method','filter']);
	return array_to_URI($path_arr,NULL);
}
//==============================================================================
// メソッドとクエリ文字列の置換後のURLを返す
public static function Get_RelocateURL($force=FALSE,$query=NULL) { 
    if(static::$ReLocate === FALSE && $force===FALSE) return NULL;
	$url = self::get_extensionURL();
	// クエリを付加
	if(!is_array($query)) $query = static::$Query;
    if(!empty($query)) $url = "{$url}?" . http_build_query($query);;
    return "/{$url}";
}
//==============================================================================
// パラメータのリセット
public static function ParamReset() { 
    static::$Params = array_fill(0, 10, 0);
}
//==============================================================================
// アプリケーションフォルダパスを取得
public static function Get_AppPath($path) {
    $appname = static::$AppName;
    return "app/{$appname}/{$path}";
}
//==============================================================================
// アプリケーションド共通アプロードフォルダ
public static function Get_shareFilesPath($path = '') {
    $docroot = dirname(__DIR__);        // Framework Folder
    if(!empty($path)) $path = "{$path}/";
    return  "{$docroot}/app/.share/files/{$path}";
}
//==============================================================================
// アプリケーションドキュメントフォルダ
public static function Get_UploadPath($path = '') {
    $docroot = dirname(__DIR__);        // Framework Folder
    $appname = static::$AppName;
    if(!empty($path)) $path = "{$path}/";
    return  "{$docroot}/app/{$appname}/upload_files/{$path}";
}
//==============================================================================
// appコントローラと付属モジュールファイルの読込
public static function LoadModuleFiles($controller) {  
    // モジュールファイルを読み込む
    $modulefiles = [
        'Controller',
        'Model',
        'View',
        'Helper'
    ];
    $modtop = getcwd() . "/" . self::Get_AppPath("modules/{$controller}"); 
    foreach($modulefiles as $files) {
        $reqfile = "{$modtop}/{$controller}{$files}.php";
        if(file_exists($reqfile)) {
            require_once $reqfile;
        }
    }
}
//==============================================================================
// フレームワークのトップパスに付加パスを付けた文字列
public static function Get_SysRoot($path = '',$lower = FALSE) { 
    if(mb_substr($path,0,1) === '/') $path = mb_substr($path,1);
    return static::$sysRoot . (($lower)?strtolower($path):$path);
}
//==============================================================================
// アプリケーションのトップパスに付加パスを付けた文字列
public static function Get_AppRoot($path = '',$lower = FALSE) {  
    if(mb_substr($path,0,1) === '/') $path = mb_substr($path,1);
    return static::$appRoot . (($lower)?strtolower($path):$path);
}
//==============================================================================
// コントローラーパスにアクションパスを付けた文字列
public static function Get_ActionRoot($path = '',$lower = FALSE) {  
	$URI = [
		'/'.static::$AppName,
		static::$Controller,
		trim((($lower)?strtolower($path):$path),'/'),
	];
	return implode('/',$URI);
}
//==============================================================================
// フィルターパスのリスト
public static function Get_FiltersList() {  
	return implode('/',array_filter(static::$Filters,'strlen'));
}
//==============================================================================
// カレントのURIを指定キーに保存する、既に保存されていれば何もしない
public static function Save_MyURL($save_key) {  
	$save_url = MySession::getAppData($save_key,false);
	if(empty($save_url)) MySession::setAppData($save_key,static::$SysVAR['URI']);
}
//==============================================================================
// POST/GET変数の要素名による取得
private static function query_element($arr,$filter,$default_value) {
	if(is_array($filter)) {
		$val = [];
		foreach($filter as $key) $val[] = (array_key_exists($key,$arr)) ? $arr[$key] : $default_value;
	} else {
		$val = (array_key_exists($filter,$arr)) ? $arr[$filter] : $default_value;
	}
	return $val;
}
//==============================================================================
// クエリ変数の要素名による取得
public static function QueryElements($filter,$default_value = NULL) {
	return self::query_element(static::$Query,$filter,$default_value);
}
//==============================================================================
// POST変数の要素名による取得
public static function PostElements($filter,$default_value = NULL) {
	return self::query_element(static::$Post,$filter,$default_value);
}
//==============================================================================
// POST変数に値をセット
static function setPostElements($arr) {
	foreach($arr as $key => $val) static::$Post[$key] = $val;
}
//==============================================================================
// GET/POST変数の要素名による取得
public static function GetPostElements($filter) {
	$val = array_filter_import($filter,static::$Query,static::$Post);
	return $val;
}
//==============================================================================
// setPostElements と同じだが、未定義キーだけを値セットする
// 冗長だが PHP5.6 でも動作する方法をとる
public static function setPost_if_empty($arr) {
	foreach($arr as $key => $val) {
		if(!array_key_exists($key,static::$Post)) static::$Post[$key] = $val;
	}
}
//==============================================================================
// POST変数から環境変数に移動する
static function preservReqData(...$keys) {
	foreach($keys as $nm) {
		if(array_key_exists($nm,static::$Post)) {
            MySession::setAppData("POST.{$nm}", static::$Post[$nm]);
			unset(static::$Post[$nm]);
		}
	}
}
//==============================================================================
// SESSION変数からPOSTに移動する
static function rollbackReqData(...$keys) {
	foreach($keys as $nm) {
		if(array_key_exists($nm,MySession::$EnvData[$envKey])) {
			static::$Post[$nm] = MySession::getAppData("POST.{$nm}");
		}
		MySession::unsetAppData('POST');
	}
}
//==============================================================================
// cdd/js/icoファイルの読込タグ出力（単独）
    private static function includeTag($tagfile) {
        if(is_array($tagfile)) {
            foreach($tagfile as $nm) self::includeTag($nm);
            return;
        }
        //separate query string if exist
		list($file,$q_str) = fix_explode('?',$tagfile,2);
		if(!empty($q_str)) $q_str = "?{$q_str}";
        $ext = substr($file,strrpos($file,'.') + 1);    // 拡張子を確認
        $path = make_hyperlink($file,static::$Controller).$q_str;
        switch($ext) {
    	case 'js':
            echo "<script src='{$path}' charset='UTF-8'></script>\n";
            break;
    	case 'css':
            echo "<link rel='stylesheet' href='{$path}' />\n";
            break;
        case 'ico':
            echo "<link rel='shortcut icon' href='{$path}' type='image/x-icon' />\n";
            break;
    	default:
	    }
    }
//==============================================================================
//  webrootファイルの読込タグ出力（単独・配列）
    public static function WebInclude($files) {
        if(is_array($files)) {
            foreach($files as $nm) self::includeTag($nm);
        } else self::includeTag($files);
    }
//==============================================================================
// imagesのインクルードタグ出力
    public static function ImageSRC($name, $attr) {
        $root = static::$appRoot;
        return "<img src=\"{$root}images/{$name}\" {$attr} />";
    }

}
