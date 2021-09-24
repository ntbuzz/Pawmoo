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
	public static $emptyRequest;	// GET/POST のデータ無しフラグ
    public static $Filter;          // メソッドのフィルタ配列の先頭
    public static $Filters;         // メソッドのフィルタ配列
    public static $Params;          // メソッドの数値パラメータ配列
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
        list($controller,$method,$filters,$params) = $module;
		$uri = array_to_URI([static::$appRoot,$module]);

        static::$DocRoot = (empty($_SERVER['DOCUMENT_ROOT'])) ? '' : $_SERVER['DOCUMENT_ROOT'];
        static::$Referer = (empty($_SERVER['HTTP_REFERER'])) ? '' : $_SERVER['HTTP_REFERER'];

		static::$Query	 = xchange_Boolean($_GET);				// same as query string
		static::$Post	 = xchange_Boolean($_POST);				// same as form POST string
		static::$emptyRequest = empty($_POST) && empty($_GET);
		// フィルタ先頭だけ取り出しておく
        static::$Filter = empty($filters) ? '': $filters[0];
        // メソッドの書き換えによるアドレスバー操作用
        static::$execURI = [ 'root' => static::$appRoot ];
		self::SetModuleExecution($controller,$method,$filters,false);
		self::ChangeParams($params,false);

		$method = static::$execURI['method'];
		static::$Controller = $controller;
		static::$Method		= ucfirst(str_replace('-','_',$method));
        static::$SysVAR = array(
            'SERVER'	=> $_SERVER['SERVER_NAME'],
            'SYSROOT'	=> static::$sysRoot,
            'APPROOT'	=> static::$appRoot,
            'APPNAME'	=> static::$AppName,
            'REFERER'	=> static::$Referer,
            'REQURI'	=> $uri . array_to_query(static::$Query),
            'URI'		=> $uri,
            'controller'=> strtolower($controller),  //ucfirst($uri_array[2]),
            'method'	=> strtolower($method),  //ucfirst($uri_array[3]),
            'extention' => static::$MethodExtention,
            'filter'	=> static::$Filter,  // ucfirst(static::$Filter),
            'params'	=> static::$Params,
        );
        // リクエスト情報を記憶
        MySession::setEnvVariables([
			// 'AppProperty' => [
			// 	'QUERY' => static::$Query,
			// 	'POST' => static::$Post,
			// 	'CONTROLLER' => static::$Controller,
			// 	'METHOD' => static::$Method,
			// ],
			'sysVAR'	=> static::$SysVAR,
		]);
    }
//==============================================================================
// モジュール実行URIの書換え、数字パラメータは別に呼び出す
public static function SetModuleExecution($module,$method,$change_filter=[],$relocate = TRUE) { 
	if(strpos($method,'.')!==FALSE) {
		list($method,static::$MethodExtention) = extract_base_name($method);
	} else {
		static::$MethodExtention = FALSE;
	}
    static::$execURI['controller'] = strtolower($module);
    static::$execURI['method'] = strtolower($method);
    if(is_array($change_filter)) static::$execURI['filter'] = $change_filter;
    static::$ReLocate = $relocate;
}
// //==============================================================================
// // メソッドの置換
// public static function ChangeMethod($module,$method,$change_filter=[], $relocate = TRUE) { 
// 	if(strpos($method,'.')!==FALSE) {
// 		list($method,static::$MethodExtention) = extract_base_name($method);
// 	} else {
// 		static::$MethodExtention = FALSE;
// 	}
//     static::$execURI['controller'] = strtolower($module);
//     static::$execURI['method'] = strtolower($method);
//     if(is_array($change_filter)) static::$execURI['filter'] = $change_filter;
//     static::$ReLocate = $relocate;        // URLの書き換え
// }
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
	$path_arr = [
			static::$execURI['root'],
			static::$execURI['controller'],
			static::$execURI['method'],
			static::$execURI['filter'],
		];
	// if(!empty(static::$MethodExtention)) {
	// 	$method = $path_arr[2];
	// 	$path_arr[2] = $path_arr[3];
	// 	$path_arr[2] = "{$method}." . static::$MethodExtention;
	// }
	return array_to_URI($path_arr,NULL);
//		,array_key_value(static::$Query,'&');
}
//==============================================================================
// メソッドとクエリ文字列の置換後のURLを返す
public static function Get_RelocateURL($force=FALSE,$query=NULL) { 
    if(static::$ReLocate === FALSE && $force===FALSE) return NULL;
	$url = self::get_extensionURL();
	// クエリを付加
	if(!is_array($query)) $query = static::$Query;
    if(!empty($query)) $url = "{$url}?" . http_build_query($query);;
    debug_xdump(["RE-LOCATE-JMP" => static::$execURI,'URI'=>$url]);
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
// POST/GET変数の要素名による取得
private static function query_element($arr,$filter) {
	if(is_array($filter)) {
		$val = [];
		foreach($filter as $key) $val[] = (array_key_exists($key,$arr)) ? $arr[$key] : NULL;
	} else {
		$val = (array_key_exists($filter,$arr)) ? $arr[$filter] : NULL;
	}
	return $val;
}
//==============================================================================
// クエリ変数の要素名による取得
public static function QueryElements($filter) {
	return self::query_element(static::$Query,$filter);
}
//==============================================================================
// POST変数の要素名による取得
public static function PostElements($filter) {
	return self::query_element(static::$Post,$filter);
}
//==============================================================================
// POST変数に値をセット
static function setPostElements($arr) {
	foreach($arr as $key => $val) static::$Post[$key] = $val;
}
//==============================================================================
// setPostElements と同じだが、未定義キーだけを値セットする
// 冗長だが PHP5.6 でも動作する方法をとる
private static function set_if_empty($arr) {
	foreach($arr as $key => $val) {
		if(!array_key_exists($key,static::$Post)) static::$Post[$key] = $val;
	}
}
//==============================================================================
// POST変数から環境変数に移動する
static function preservReqData($envKey,...$keys) {
	foreach($keys as $nm) {
		if(array_key_exists($nm,static::$Post)) {
			MySession::$EnvData[$envKey][$nm] = static::$Post[$nm];
			unset(static::$Post[$nm]);
		}
	}
}
//==============================================================================
// SESSION変数からPOSTに移動する
static function rollbackReqData($envKey,...$keys) {
	foreach($keys as $nm) {
		if(array_key_exists($nm,MySession::$EnvData[$envKey])) {
			static::$Post[$nm] = MySession::$EnvData[$envKey][$nm];
			unset(MySession::$EnvData[$envKey][$nm]);
		}
//		unset(MySession::$EnvData[$envKey]);
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
        list($file,$q_str) = (strpos($tagfile,'?') !== FALSE) ? explode('?',$tagfile):[$tagfile,'']; 
        $ext = substr($file,strrpos($file,'.') + 1);    // 拡張子を確認
        $path = make_hyperlink($file,static::$Controller);
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
    debug_log(FALSE, ["WebINCLUDE" => $files]);
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
