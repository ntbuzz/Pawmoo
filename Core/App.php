<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  App:  システム変数その他を保持するグローバルクラス
 */
 //==================================================================================================
class App {
    public static $SysVAR;        // URIROOT, WEBROOT, URI, QUERY 変数
    public static $AppName;         // アプリケーション名
    private static $sysRoot;        // フレームワークのルートパス
    public static $DocRoot;         // DOCUMENT_ROOT 変数
    public static $Referer;         // HTTP_REFERER 変数
    public static $Query;           // urlのクエリー文字列の連想配列
    public static $Filter;          // メソッドのフィルタ指示
    public static $Params;          // メソッドの数値パラメータ配列
    public static $argv;            // メソッド以下のURIを/で分割した文字列
    public static $argc;            // 引数の数
    public static $ActionClass;     // 実行コントローラ名
    public static $ActionMethod;    // 呼出しメソッド名
    public static $RunTime;         // コントローラを呼び出した時間
//===============================================================================
// 静的クラスでのシステム変数初期化
	public static function __Init($rootURI,$appname, $uri,$params,$q_str) {
        self::$sysRoot = $rootURI;
        self::$AppName = $appname;
        self::$DocRoot = $_SERVER['DOCUMENT_ROOT'];
        self::$Referer = $_SERVER['HTTP_REFERER'];
        parse_str($q_str, $query);

        $uri_array = explode('/',$uri);     // decode パス補正は上位で処理済み
        // 先頭と最後に '/ ' があるので空要素がある前提
        if(!empty($uri_array[4])) {
            $n = is_numeric($uri_array[4]) ? 3 : 4;         // 4番目の要素(フィルタ)が数値ならフィルタ指定なし
            self::$Filter = ($n > 3) ? $uri_array[4]:'';    // メソッドとパラメータの間に指定がある
            self::$Params = array_slice($uri_array,$n+1,9); // 数値パラメータ配列
        } else {
            self::$Filter = '';
            self::$Params = [];
            $n = 3;
        }
   		// 0 〜 9 の不足する要素を補填する
        $k = count(self::$Params);
		self::$Params += array_fill($k, 10 - $k, '');
        self::$SysVAR = array(
            'APPNAME' => self::$AppName,
            'URIROOT' => self::$sysRoot,
            'WEBROOT' => self::$sysRoot . 'webroot/',
            'URI' => $uri,
            'QUERY' => $q_str,
            'REFERER' => self::$Referer,
            'CONTROLLER' => implode('/',array_slice($uri_array,0,3)),
            'METHOD' => implode('/',array_slice($uri_array,0,4)),
            'FILTER' => implode('/',array_slice($uri_array,0,$n+1)),
            'PARAMS' => implode('/',array_slice($uri_array,$n+1,9)),
            'controller' => $uri_array[2],  //ucfirst($uri_array[2]),
            'method' => $uri_array[3],  //ucfirst($uri_array[3]),
            'filter' => self::$Filter,  // ucfirst(self::$Filter),
        );
        self::$argv = $params;
        self::$argc = count($params);
        self::$Query = $query;
	}
//==================================================================================================
// デバッグメッセージ
    private static function DEBUG($lvl,$msg) { 
        if(DEBUG || ($lvl >= DEBUG_LEVEL)) {
            echo "<pre>\n";
            if(is_scalar($msg)) {
                echo "{$msg}\n";
            } else {
                echo "msg obj dump\n";
                echo "=======================================================\n";
                print_r($msg);
            }
            echo "</pre>\n";
        }
    }
//==================================================================================================
// アプリケーションフォルダパスを取得
    public static function AppPath($path) {
        $appname = self::$AppName;
        return "app/{$appname}/{$path}";
    }
//==================================================================================================
// appモジュールファイルの読込
    public static function appUses($cat,$modname) {
        $mod = explode('/',$modname);
        $tagfile = ($mod[0] == 'modules') ? "modules/{$mod[1]}/{$mod[1]}{$cat}" : "{$modname}/{$cat}";
        $reqfile = self::AppPath("{$tagfile}.php");
        if(file_exists ($reqfile)) {
            require_once $reqfile;
            return 1;
        }
        self::DEBUG(92," FAIL:" . getcwd() . '/' . $reqfile);
        return 0;
    }
//==================================================================================================
// appコントローラと付属モジュールファイルの読込
    public static function appController($controller) {  
        // モジュールファイルを読み込む
        $modulefiles = [
            'Controller',
            'Model',
            'View',
            'Helper'
        ];
        $modtop = getcwd() . "/" . self::AppPath("modules/{$controller}"); 
        foreach($modulefiles as $files) {
            $reqfile = "{$modtop}/{$controller}{$files}.php";
            if(file_exists($reqfile)) {
                require_once $reqfile;
            }
        }
    }
//==================================================================================================
// appName/Models モジュールファイルの読込
    public static function appModels($modname) {  
        $reqfile = self::AppPath("Models/{$modname}Model.php");
        if(file_exists ($reqfile)) {
            require_once $reqfile;
            return 1;
        }
        self::DEBUG(92," FAIL:" . getcwd() . '/' . $reqfile);
        return 0;
    }
//==================================================================================================
// webrootファイルのパスに付加パスを付けた文字列
	public static function getRoot($path) {  
        if($path[0] == '/') $path = mb_substr($path,1);
        return self::$sysRoot . strtolower($path);
    }
//==================================================================================================
// webrootファイルの読込タグ出力（単独）
    private static function IncludeTag($tagfile) {
        if(is_array($tagfile)) {
            foreach($tagfile as $nm) self::IncludeTag($nm);
            return;
        }
        list($file,$q_str) = explode('?',$tagfile);     // クエリ文字列が付加されている時に備える
        $ext = substr($file,strrpos($file,'.') + 1);    // 拡張子を確認
        if(substr($file,0,7) == 'http://' || substr($file,0,8) == 'https://') {                 // ROOTパスかを確認
            $path = $file;                             // 外部サイト
        } else if($file[0] == '/') {         // ROOTパスかを確認
            $path = '/common' . $file;                 // 共通フォルダパス
        } else {
            // カレントフォルダの指定ならモジュール名で置換
            if(substr($file,0,2) == './') $file = substr_replace($file, strtolower(self::$ActionClass), 0, 1);
            $path = self::$sysRoot . $file;             // 固有フォルダパス
        }
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
//==================================================================================================
//  webrootファイルの読込タグ出力（単独・配列）
    public static function WebInclude($files) {
        if(is_array($files)) {
            foreach($files as $nm) self::IncludeTag($nm);
        } else self::IncludeTag($files);
    }
//==================================================================================================
// imagesのインクルードタグ出力
    public static function ImageSRC($name, $attr) {
        $root = self::$sysRoot;
        return "<img src=\"{$root}images/{$name}\" {$attr} />";
    }

}
