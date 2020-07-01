<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  App:  システム変数その他を保持するグローバルクラス
 */
//==============================================================================
class App {
    public static $SysVAR;        // URIROOT, WEBROOT, URI, QUERY 変数
    public static $AppName;         // アプリケーション名
    private static $appRoot;        // アプリケーションのルートパス
    private static $sysRoot;        // フレームワークのルートパス、／で終る
    public static $DocRoot;         // DOCUMENT_ROOT 変数
    public static $Referer;         // HTTP_REFERER 変数
    public static $Query;           // urlのクエリー文字列の連想配列
    public static $Filter;          // メソッドのフィルタ指示
    public static $Params;          // メソッドの数値パラメータ配列
    public static $ParamCount;      // 引数の数
    public static $Controller;      // 実行コントローラ名
    public static $ActionMethod;    // 呼出しメソッド名
    private static $ReLocate;        // URLの書き換え
    private static $execURI;
//==============================================================================
// 静的クラスでのシステム変数初期化
	public static function __Init($appname,$app_uri,$module,$query,$uri) {
        self::$AppName = $appname;
        list(self::$sysRoot,self::$appRoot) = $app_uri;
        list($controller,$method,$filter,$params) = $module;

        self::$DocRoot = (empty($_SERVER['DOCUMENT_ROOT'])) ? '' : $_SERVER['DOCUMENT_ROOT'];
        self::$Referer = (empty($_SERVER['HTTP_REFERER'])) ? '' : $_SERVER['HTTP_REFERER'];

        self::$Filter = $filter;
        self::$Params = $params;
   		// 0 〜 9 の不足する要素を補填する
        $k = count($params);
        self::$ParamCount = $k;
		self::$Params += array_fill($k, 10 - $k, '');
        self::$SysVAR = array(
            'SYSROOT' => self::$sysRoot,
            'APPNAME' => self::$AppName,
            'URIROOT' => self::$appRoot,
            'URI' => $uri,
            'REFERER' => self::$Referer,
            'controller' => $controller,  //ucfirst($uri_array[2]),
            'method' => $method,  //ucfirst($uri_array[3]),
            'filter' => $filter,  // ucfirst(self::$Filter),
        );
        self::$Query = $query;
        // メソッドの書き換えによるアドレスバー操作用
        self::$ReLocate = FALSE;        // URLの書き換え
        self::$execURI = array(
            'root' => self::$appRoot,
            'controller' => $controller,
            'method' => $method,
            'filter' => $filter,
            'params' => $params,
            );
            // リクエスト情報を記憶
        MySession::SetEnvVar('sysVAR',self::$SysVAR);
    }
//==============================================================================
// メソッドの置換
public static function ChangeMethod($module,$method,$relocate = TRUE) { 
    self::$execURI['controller'] = $module;
    self::$execURI['method'] = $method;
    self::$ReLocate = $relocate;        // URLの書き換え
}
//==============================================================================
// メソッドの置換
public static function Get_RelocateURL() { 
    if(self::$ReLocate === FALSE) return NULL;
    APPDEBUG::DebugDump(1, self::$execURI);
    $url = array_to_URI(self::$execURI);
    if(!empty(self::$Query)) {                  // exists QUERY strings
        $q = http_build_query(self::$Query);
        $url = "{$url}?{$q}";
    }
    return $url;
}
//==============================================================================
// デバッグメッセージ
    private static function DEBUG($lvl,$msg) { 
        if(DEBUG || ($lvl >= DEBUG_LEVEL)) {
            echo "<pre>\n";
            if(is_scalar($msg)) {
                echo "{$msg}\n";
            } else {
                echo "msg obj dump\n";
                echo str_repeat("=", 50)."\n";
                print_r($msg);
            }
            echo "</pre>\n";
        }
    }
//==============================================================================
// アプリケーションフォルダパスを取得
public static function Get_AppPath($path) {
    $appname = self::$AppName;
    return "app/{$appname}/{$path}";
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
public static function Get_SysRoot($path = '') {  
    if($path[0] == '/') $path = mb_substr($path,1);
    return self::$sysRoot . strtolower($path);
}
//==============================================================================
// アプリケーションのトップパスに付加パスを付けた文字列
public static function Get_AppRoot($path = '') {  
    if($path[0] !== '/') $path = "/{$path}";
//echo "appRoot:".self::$appRoot."\n"."Path]{$path}\n";exit;
    return self::$appRoot . strtolower($path);
}
//==============================================================================
// cdd/js/icoファイルの読込タグ出力（単独）
    private static function includeTag($tagfile) {
        if(is_array($tagfile)) {
            foreach($tagfile as $nm) self::includeTag($nm);
            return;
        }
        list($file,$q_str) = explode('?',$tagfile);     // クエリ文字列が付加されている時に備える
        $ext = substr($file,strrpos($file,'.') + 1);    // 拡張子を確認
        $path = make_hyperlink($file,self::$Controller);
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
        $root = self::$appRoot;
        return "<img src=\"{$root}/images/{$name}\" {$attr} />";
    }

}
