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
    private static $execURI;
//==============================================================================
// 静的クラスでのシステム変数初期化
	public static function __Init($appname,$app_uri,$module,$query,$uri) {
        static::$AppName = $appname;
        list(static::$sysRoot,static::$appRoot) = $app_uri;
        list($controller,$method,$filters,$params) = $module;

        static::$DocRoot = (empty($_SERVER['DOCUMENT_ROOT'])) ? '' : $_SERVER['DOCUMENT_ROOT'];
        static::$Referer = (empty($_SERVER['HTTP_REFERER'])) ? '' : $_SERVER['HTTP_REFERER'];
		static::$Query	 = $query;

        if(strpos($method,'.')!==FALSE) {
            list($method,static::$MethodExtention) = extract_base_name($method);
            $method = ucfirst(strtolower($method));
        } else {
            static::$MethodExtention = FALSE;
        }
        static::$Filters= $filters;
        static::$Filter = empty($filters) ? '': $filters[0];
   		// 0 ～ 9 の不足する要素を補填する
        $k = count($params);
		$params = $params + array_fill($k, 10 - $k, 0);

        static::$ParamCount = $k;
        static::$Params = $params;  //array_intval_recursive($params);
        static::$SysVAR = array(
            'SERVER' => $_SERVER['SERVER_NAME'],
            'REFERER' => static::$Referer,
            'REQURI' => $uri . array_to_query($query),
            'URI' => $uri,
            'SYSROOT' => static::$sysRoot,
            'APPNAME' => static::$AppName,
            'APPROOT' => static::$appRoot,
            'controller' => strtolower($controller),  //ucfirst($uri_array[2]),
            'method' => strtolower($method),  //ucfirst($uri_array[3]),
            'extention' => static::$MethodExtention,
            'filter' => static::$Filter,  // ucfirst(static::$Filter),
            'params' => static::$Params,
        );
		static::$Controller  = $controller;
		static::$Method= $method;
//        static::$Query = array_intval_recursive($query);
        // メソッドの書き換えによるアドレスバー操作用
        static::$ReLocate = FALSE;        // URLの書き換え
        static::$execURI = array(
            'root' => static::$appRoot,
            'controller' => strtolower($controller),  //ucfirst($uri_array[2]),
            'method' => strtolower($method),  //ucfirst($uri_array[3]),
            'filter' => $filters,
            'params' => static::$Params,
            );
        // リクエスト情報を記憶
        MySession::setEnvVariables([
			'sysVAR'	=> static::$SysVAR,
//			'QUERY'		=> static::$Query,
//			'POST'		=> MySession::$ReqData,
		]);
    }
//==============================================================================
// メソッドの置換
public static function ChangeMethod($module,$method,$change_filter=[], $relocate = TRUE) { 
    static::$execURI['controller'] = $module;
    static::$execURI['method'] = strtolower($method);
    if(is_array($change_filter)) static::$execURI['filter'] = $change_filter;
    static::$ReLocate = $relocate;        // URLの書き換え
}
//==============================================================================
// パラメータパスの置換
public static function ChangeParams($params,$relocate = TRUE) { 
    static::$execURI['params'] = $params;
    static::$ReLocate = $relocate;        // URLの書き換え
}
//==============================================================================
// パラメータの消去
public static function CleareParams() {
	for($i=0;$i < count(static::$Params);++$i) static::$Params[$i] = NULL;
}
//==============================================================================
// パラメータ無しのパス
public static function Get_PagingPath() { 
	return array_to_URI([
			static::$execURI['root'],
			static::$execURI['controller'],
			static::$execURI['method'],
			static::$execURI['filter']
		],
		array_key_value(static::$Query,'&')
		);
}
//==============================================================================
// メソッドとクエリ文字列の置換後のURLを返す
public static function Get_RelocateURL($force=FALSE,$query=NULL) { 
    if(static::$ReLocate === FALSE && $force===FALSE) return NULL;
	if(static::$MethodExtention !== FALSE) {
		$execurl = array_filter(static::$execURI,function($v,$k) { return ($k !== "method");},ARRAY_FILTER_USE_BOTH);
		$execurl[] = static::$execURI['method'] . "." . static::$MethodExtention;
	} else 	$execurl = static::$execURI;
    $url = array_to_URI($execurl);
	if(!is_array($query)) $query = static::$Query;
    if(!empty($query)) {                  // exists QUERY strings
        $q = http_build_query($query);
        $url = "{$url}?{$q}";
    }
//    debug_log(DBMSG_SYSTEM, ["RE-LOCATE-JMP" => static::$execURI,'URI'=>$url]);
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
// アプリケーションのトップパスに付加パスを付けた文字列
public static function Get_ActionRoot($path = '',$lower = FALSE) {  
	$URI = [
		'/'.static::$AppName,
		static::$Controller,
		trim((($lower)?strtolower($path):$path),'/'),
	];
	return implode('/',$URI);
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
