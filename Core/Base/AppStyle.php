<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  AppStyle:   css, js ファイルの結合出力を可能にする
 */
require_once('Core/Class/Parser.php');

class AppStyle {
    const ContentList = [
        'css' => [
            'folder' => 'css',
            'head' => 'text/css',
            'extention' => '.css',
            'section' => 'stylesheet',
        ],
        'js' => [
            'folder' => 'js',
            'head' => 'text/javascript',
            'extention' => '.js',
            'section' => 'javascript',
        ],
        0 => [
            'folder' => '',
            'head' => '',
            'extention' => '',
            'section' => '',
        ],

    ];
    const BoolConst = [ 'yes' => TRUE, 'no' => FALSE, 'true' => TRUE, 'false' => FALSE, 'on' => TRUE, 'off' => FALSE ];
    const FunctionList = array(
        '@'    => [
            'compact'   => [ 'cmd_modeset','do_min' ],
            'comment'   => [ 'cmd_modeset','do_com' ],
            'message'   => [ 'cmd_modeset','do_msg' ],
            'charset'   => 'cmd_charset',
        ],
        '+'    => [
            'import'    => 'cmd_import',
            'section'   => 'cmd_section',
            'jquery'    => 'cmd_jquery',
        ],
        '*'  => 'do_comment',
    );
    private $ModuleName;        // モジュール名 or Res
    private $Template;          // ContentList のテンプレート
    private $Folders;           // ファイル探索フォルダ
    private $Filetype;          // サブフォルダ css/js
    private $do_min;            // コンパクト出力するかどうかのフラグ
    private $do_msg;            // インポートメッセージ出力のフラグ
    private $do_com;            // コメントの除去
    private $repVARS;           // 置換文字列
//==============================================================================
// コンストラクタ
    function __construct($appname, $sysRoot, $modname, $filename, $ext) {
        // モジュール名(res)は共通URIモジュールとして扱う
        $this->ModuleName = ($modname == 'Res') ? '' : $modname;  
        $this->Template = self::ContentList[$ext];
        $fldr = $this->Template['folder'];
        $this->Filetype = $fldr;
        $this->Folders = array(
            'モジュール固有' => "app/{$appname}/modules/{$this->ModuleName}/res",
            'アプリ共通' => "app/{$appname}/View/res",      // App共通のレイアウトテンプレートを探す
            'Libs' => "Core/Template/res",                  // ライブラリのテンプレートを探す
        );
        if($this->ModuleName == '') {   // リソースフォルダならモジュール固有を削除
            unset($this->Folders['モジュール固有']);
        }
        $myVARS = array(
            'appName' => $appname,
            'moduleName' => $this->ModuleName,
            'filename' => $filename,
            'extension' => $ext,
        );
        if(isset(MySession::$EnvData['sysVAR'])) {
            $sess = MySession::$EnvData['sysVAR'];              // セッションに記憶してあるシステム変数
            $this->repVARS = array_merge($sess, $myVARS);       // システム変数とパラメータをマージしておく
        } else {
            $this->repVARS = $myVARS;       // パラメータのみセット
        }
    }
//==============================================================================
// テンプレートファイルがビュークラスフォルダに存在しなければ共通のテンプレートを探す
// appname/modules/{module}/Layout/{name}
    private function get_exists_files($name) {
        $arr = array();
        foreach($this->Folders as $key => $file) {
            $fn ="{$file}/{$name}";
            if(file_exists($fn)) {
                $arr[$key] = $fn;
            }
        }
        return $arr;
    }
//==============================================================================
//　ヘッダ出力
public function ViewHeader() {
    global $on_server; 
	if($on_server === '' ) return;
    header("Content-Type: {$this->Template['head']};");
}
//==============================================================================
//　レイアウトテンプレート処理
public function ViewStyle($file_name) {
    // スタイルシートが存在するフォルダのみをリストアップする
    $temlatelist = $this->get_exists_files('template.mss');
    list($filename,$ext) = extract_base_name($file_name);
    $this->do_min = ($ext == 'min');           // コメント削除出力か
    $this->do_msg = TRUE ;                     // インポートコメント表示設定
    $this->do_com = TRUE ;                     // コメント表示設定
    // テンプレートセクション処理
    if($this->section_styles($temlatelist, $filename) === FALSE) {
        // テンプレートセクションに指定ファイル名が見つからないときは実ファイルを探索する
        foreach($this->Folders as $key => $file) {
            $fn ="{$file}/{$this->Filetype}/{$filename}{$this->Template['extention']}";
            if(file_exists($fn)) {
                $content = file_get_contents($fn);
                if($this->do_msg) echo "/* include({$filename}{$this->Template['extention']}) in {$key} */\n";
                $this->outputContents($content);
            }
        }
    }
}
//==============================================================================
//　セクションスタイルテンプレート処理
//  $templist   テンプレートフォルダのリスト
//  $secname    セクション名
    private function section_styles($tmplist, $secname) {
        $secType = $this->Template['section'];        // stylesheet/javascript
        foreach($tmplist as $category => $file) {     // テンプレートリストから探索する
            $parser = new SectionParser($file);
            $SecTemplate = array_change_key_case( $parser->getSectionDef(), CASE_LOWER);
            unset($parser);         // 解放
            if(array_key_exists($secType,$SecTemplate)) {   // stylesheet or javascript
                $secData = $SecTemplate[$secType];      // css/jsテンプレートセクションを取得
                $secParam = array($secname,$secData,$tmplist);
                // テンプレート外のコマンドを処理
                foreach($SecTemplate as $key => $val) { 
                    $this->function_Dispath($secParam, $key, $val); // 戻り値は無視してOK
                }
                // セクション外のコマンドを処理
                foreach($secData as $key => $val) {
                    $this->function_Dispath($secParam, $key, $val); // 戻り値は無視してOK
                }
                if(array_key_exists($secname,$secData)) {   // filename セクションがあるか
                    $this->sectionDispath($secParam,$secData[$secname]);
                    return TRUE;
                }
            }
        }
        return FALSE;
    }
//==============================================================================
// section セクション内をコマンド処理する
// コマンド以外はCSSフォーマットで直接出力する
    private function sectionDispath($secParam,$section) {
        foreach($section as $key => $sec) {     // セクション外のコマンドを処理
            if($this->function_Dispath($secParam, $key, $sec)===FALSE) {
                // 内部コマンドではない
                if($this->Filetype == 'css') {       // CSS直接出力
                    if(is_array($sec)) {
                        echo "{$key} {\n";
                        foreach($sec as $kk => $vv) {
                            if(!is_numeric($kk)) echo "{$kk}:";  // 属性指定がある
                            echo "{$vv};\n";
                        }
                        echo "}\n";
                    } else {        // スカラー出力
                        echo "{$key} \"{$sec}\"\n";
                    }
                }
            }
        }
    }
//==============================================================================
// key 文字列を元に処理関数へディスパッチする
// key => sec (vars)
    private function function_Dispath($secParam, $key,$sec) {
        if(is_numeric($key)) {
            if(!is_scalar($sec)) return FALSE;    // 単純配列は認めない
            $key = $sec;
        } else $key = tag_body_name($key);         // 重複回避用の文字を削除
        $top_char = $key[0];
        if(array_key_exists($top_char,self::FunctionList)) {
            $tag = mb_substr($key,1);      // 先頭文字を削除
            $func = self::FunctionList[$top_char];
            if(is_array($func)) {       // サブコマンドテーブルがある
                if(array_key_exists($tag,$func)) {
                    $def_func = $func[$tag];
                    // 配列ならパラメータ要素を取出す
                    list($cmd,$param) = (is_array($def_func)) ? $def_func:[$def_func,'']; 
                    if((method_exists($this, $cmd))) {
                        $this->$cmd($secParam,$param,$sec);
                    } else echo "+++ Method Not Found({$cmd})\n";
                } else echo "*** '{$tag}' is Feature Command...\n";
            } else if(method_exists($this, $func)) {
                $this->$func($sec);        // ダイレクトコマンド
            } else echo "CALL: {$func}({$tag},{$sec},vars)\n";  // 未定義のコマンド
            return TRUE;    // コマンド処理を実行
        } else {
            return FALSE;   // コマンド処理ではない
        }
    }
//------------------------------------------------------------------------------
// * comment コマンド
    private function do_comment($sec) {
        $vv = $this->expand_Strings($sec,$this->repVARS);
        $vv = trim(substr($vv,1));
        echo "/* {$vv} */\n";
    }
//------------------------------------------------------------------------------
// cmd_XXXXX メソッドにはパラメータが渡ってくる
// cmd_XXXX($tag, $param, $sec)
//------------------------------------------------------------------------------
// * charset コマンド
    private function cmd_charset($secParam, $param,$sec) {
        echo "@charset \"{$sec}\"\n";
    }
//------------------------------------------------------------------------------
// compact/comment/message コマンド
// パラメータはプロパティ変数名
    private function cmd_modeset($secParam, $param,$sec) {
        $val = strtolower($sec);                        // 設定値を取り出す
        $this->$param = self::BoolConst[$val];            // 指定プロパティ変数にセット
    }
//------------------------------------------------------------------------------
// jquery コマンド
// +jquery => [ files , ... ] or jquery => scalar
    private function cmd_jquery($secParam, $param,$sec) {
        if($this->Filetype == 'js') {
            list($secname,$secData,$tmplist) = $secParam;       // 配列要素を分解
            echo "$(function() { ";
                $this->filesImport($tmplist,$sec);
            echo "});\n";
        }
    }
//------------------------------------------------------------------------------
// import コマンド
// +import => [ files , ... ] or import => scalar
    private function cmd_import($secParam, $param,$sec) {
        list($secname,$secData,$tmplist) = $secParam;       // 配列要素を分解
        $this->filesImport($tmplist,$sec);
    }
//------------------------------------------------------------------------------
// section コマンド
// +section => [ files , ... ] or section => scalar
    private function cmd_section($secParam, $param,$sec) {
        $secval = (is_array($sec)) ? $sec : array($sec);    // 配列要素に統一
        list($secname,$secData,$tmplist) = $secParam;       // 配列要素を分解
        foreach($secval as $vsec) {
            if($vsec[0] == '@') {
                if(!DEBUGGER) continue;   // デバッグモードでなければスキップ
                $vsec = substr($vsec,1);
            }
            $force_parent = $vsec[0] == '^';
            if($force_parent) $vsec = substr($vsec,1);
            // 処理中のセクション名と同じか強制親セクション
            $secmsg = (($vsec == $secname) || $force_parent) ? "parent::{$vsec}" : $vsec;
            if($this->do_msg) echo "/* subsection: {$secmsg} in {$secname} */\n";
            if($force_parent) {         // Libsテンプレート指定
                // Libs フォルダだけを指定する
                $tmplist = array( 'Libs' => $tmplist['Libs']);
                $this->section_styles($tmplist, $vsec);     // Libsセクションを処理する
            } else if($vsec === $secname || !array_key_exists($vsec, $secData) ) {
                // 現セクションと同名か自セクションに要素がなければ親を呼び出す
                array_shift($tmplist);      // 先頭は自身のテンプレートがあるフォルダ
                $this->section_styles($tmplist, $vsec);     // 親のセクションを処理する
            } else {    // 自セクションに指定のセクションがある
                $this->sectionDispath($secParam,$secData[$vsec]);
            }
        }
    }
//------------------------------------------------------------------------------
// ファイルのインポート処理
    private function filesImport($tmplist, $sec) {
        $files = (is_array($sec)) ? $sec : array($sec);
        foreach($files as $vv) {
            list($filename,$v_str) = (strpos($vv,'?')!==FALSE)?explode('?',$vv):[$vv,''];  // クエリ文字列を変数セットとして扱う
            parse_str($v_str, $vars);
            $vars = is_array($vars) ? array_merge($this->repVARS,$vars) : $this->repVARS;
            $imported = FALSE;
            foreach($tmplist as $key => $file) {
                list($path,$tmp) = extract_path_filename($file);
                $fn ="{$path}{$this->Filetype}/{$filename}";
                if(file_exists($fn)) {
                    // @charset を削除して読み込む
                    $content = preg_replace('/(@charset.*;)/','/* $1 */',trim(file_get_contents($fn)) );
                    $content = $this->expand_Strings($content,$vars);
                    if($this->do_msg) echo "/* import({$filename}) in {$key} */\n";
                    $this->outputContents($content);
                    $imported = TRUE;
                    break;
                }
            }
            if(!$imported) {
                echo "/* NOT FOUND:= {$filename} */\n";
            }
        }
    }
//==============================================================================
//    ファイルをコンパクト化して出力
    private function outputContents($content) {
        if($this->do_min) {         // コメント・改行を削除して最小化して出力する
            $pat = '[:(){}\[\]<>\=\?;,]';    // 前後の空白を削除する文字
            $content = preg_replace("/\\s*({$pat})\\s+|\\s+({$pat})\\s*|(\\s)+/sm", '$1$2$3',
                    preg_replace('/\/\*[\s\S]*?\*\/|\/\/.*?\n/','',$content));       // コメント行を削除
            $content =trim($content);
        } else if(!$this->do_com) {         // コメントと不要な改行を削除して出力する
            $content = preg_replace('/([\r\n])+/s',"\n",                  // コメント削除でできた空行を削除
                    preg_replace('/\/\*[\s\S]*?\*\/|\s+\/\/.*|^\/\/.*/','',$content));  // コメント行を削除
            $content =trim($content);
        }
        echo "{$content}\n";
    }
//==============================================================================
//  変数を置換する
    private function expand_Walk(&$val, $key, $vars) {
        if($val[0] === '$') {           // 先頭の一文字が変数文字
            $var = mb_substr($val,1);
            $var = trim($var,'{}');                 // 変数の区切り文字{ } は無条件にトリミング
            if($var[0] == '#') {
                $var = mb_substr($var,1);     // 言語ファイルの参照
                $val = LangUI::get_value('core', $var, FALSE);
            } else if(isset($vars[$var])) {
                $val = $vars[$var];             // 環境変数で置換
            }
        } else if($val[0] === '{') {           // 先頭の一文字が変数文字
            $var = trim($val,'{}');                 // 変数の区切り文字{ } は無条件にトリミング
            if($var[0] == '$') {
                $var = trim($var,'$');        // システム変数値
                if(isset($vars[$var])) $val = $vars[$var];             // 環境変数で置換
            }
        }
    }
//==============================================================================
//  文字列の変数置換を行う
// $[@#%$]varname | ${[@#%$]varname} | {$SysVar$} | {%Params%}
    private function expand_Strings($str,$vars) {
        $p = '/(\${[^}]+?}|{\$[^\$]+?\$}|{%[^%]+?%})/'; // 変数リストの配列を取得
        preg_match_all($p, $str, $m);
        $varList = $m[0];
        if(empty($varList)) return $str;        // 変数が使われて無ければ置換不要
        $values = $varList = array_unique($varList);
        array_walk($values, array($this, 'expand_Walk'), $vars);
        // 配列が返ることもある
        $exvar = (is_array($values[0])) ? $values[0]:str_replace($varList,$values,$str);    // 置換配列を使って一気に置換
        return $exvar;
}


}
