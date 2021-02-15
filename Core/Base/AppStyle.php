<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  AppStyle:   css, js joined by template
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
    private $ModuleName;        // Module Name or Res
    private $Template;          // Content Template
    private $Folders;           // Search Folder List
    private $Filetype;          // File Type css/js
    private $do_min;            // Do it Compact mode output
    private $do_msg;            // Echo import messagte
    private $do_com;            // Delete Comment Line
    private $repVARS;           // Replace Variable
    private $importFiles;       // import-DEBUG
//==============================================================================
// Constructor
    function __construct($appname, $app_uri, $modname, $filename, $ext) {
        // Module(res) will be Common URI Modele
        $this->ModuleName = ($modname == 'Res') ? '' : $modname;  
        $this->Template = self::ContentList[$ext];
        $fldr = $this->Template['folder'];
        $this->Filetype = $fldr;
        $this->Folders = array(
            "{$modname}固有" => "app/{$appname}/modules/{$this->ModuleName}/res",
            "{$appname}共通" => "app/{$appname}/View/res",
            'Libs' => "Core/Template/res",
        );
        if(empty($this->ModuleName)) {
            array_shift($this->Folders);          // remove top eelment (Module Unique resource)
        }
        list($sysRoot,$appRoot) = $app_uri;
        $myVARS = array(
            'SERVER' => $_SERVER['SERVER_NAME'],
            'SYSROOT' => $sysRoot,
            'APPROOT' => $appRoot,
            'appName' => $appname,
            'controller' => $this->ModuleName,
            'filename' => $filename,
            'extension' => $ext,
        );
        if(isset(MySession::$EnvData['sysVAR'])) {
            $sess = MySession::$EnvData['sysVAR'];             // Session Variable
            $this->repVARS = array_merge($sess, $myVARS);
        } else {
            $this->repVARS = $myVARS;
        }
    }
//==============================================================================
// Search resource Folder, Module, Application, Framework
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
// Content Header Output
public function ViewHeader() {
	if(CLI_DEBUG) return;
    header("Content-Type: {$this->Template['head']};");
}
//==============================================================================
//　Style Template Output
public function ViewStyle($file_name) {
    $temlatelist = $this->get_exists_files('template.mss');
    list($filename,$ext) = extract_base_name($file_name);
    $this->do_min = ($ext == 'min');           // Is Compact Output?
    $this->do_msg = TRUE ;                     // Is Import Message?
    $this->do_com = TRUE ;                     // Is Comment Output?
    $this->importFiles = [];
    // Processing Template Style
    if($this->section_styles($temlatelist, $filename) === FALSE) {
        // not found in Template, then Real File Target Search.
        foreach($this->Folders as $key => $file) {
            $fn ="{$file}/{$this->Filetype}/{$filename}{$this->Template['extention']}";
            if(file_exists($fn)) {
                $content = file_get_contents($fn);
                if($this->do_msg) echo "/* include({$filename}{$this->Template['extention']}) in {$key} */\n";
                $this->outputContents($content);
            }
        }
    }
    sort($this->importFiles);
    $res = array_filter(array_count_values($this->importFiles).function($v) {return --$v;});
    if(!empty($res)) {
        echo "/* === duplicate-import files. ===\n";
        foreach($res as $ff) echo "{$ff}\n";
        echo "*/\n";
    }
}
//==============================================================================
//　Section Style Template Porcessing
//  $templist   List of Template Folders
//  $secname    Target SECTION name
    private function section_styles($tmplist, $secname) {
        $secType = $this->Template['section'];        // stylesheet/javascript
        foreach($tmplist as $category => $file) {
            $parser = new SectionParser($file);
            $SecTemplate = array_change_key_case( $parser->getSectionDef(FALSE), CASE_LOWER);
            unset($parser);         // 解放
            if(array_key_exists($secType,$SecTemplate)) {
                $secData = $SecTemplate[$secType];
                $secParam = array($secname,$secData,$tmplist);
                // テンプレート外のコマンドを処理
                foreach($SecTemplate as $key => $val) { 
                    $this->function_Dispath($secParam, $key, $val);
                }
                // セクション外のコマンドを処理
                foreach($secData as $key => $val) {
                    $this->function_Dispath($secParam, $key, $val);
                }
                if(array_key_exists($secname,$secData)) {
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
                $this->$func($tag,$sec);        // ダイレクトコマンド
            } else echo "CALL: {$func}({$tag},{$sec},vars)\n";  // 未定義のコマンド
            return TRUE;    // コマンド処理を実行
        } else {
            return FALSE;   // コマンド処理ではない
        }
    }
//------------------------------------------------------------------------------
// * comment Command
    private function do_comment($tag,$sec) {
        $vv = trim($this->expand_Strings($tag,$this->repVARS));
        echo "/* {$vv} */\n";
    }
//------------------------------------------------------------------------------
// cmd_XXXXX メソッドにはパラメータが渡ってくる
// cmd_XXXX($tag, $param, $sec)
//------------------------------------------------------------------------------
// * charset Command
    private function cmd_charset($secParam, $param,$sec) {
        echo "@charset \"{$sec}\"\n";
    }
//------------------------------------------------------------------------------
// compact/comment/message Command
// パラメータはプロパティ変数名
    private function cmd_modeset($secParam, $param,$sec) {
        $val = strtolower($sec);                        // 設定値を取り出す
        $this->$param = self::BoolConst[$val];            // 指定プロパティ変数にセット
    }
//------------------------------------------------------------------------------
// jquery Command
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
// import Command
// +import => [ files , ... ] or import => scalar
    private function cmd_import($secParam, $param,$sec) {
        list($secname,$secData,$tmplist) = $secParam;       // 配列要素を分解
        $this->filesImport($tmplist,$sec);
    }
//------------------------------------------------------------------------------
// section Command
// +section => [ files , ... ] or section => scalar
    private function cmd_section($secParam, $param,$sec) {
        $secval = (is_array($sec)) ? $sec : array($sec);    // 配列要素に統一
        list($secname,$secData,$tmplist) = $secParam;       // 配列要素を分解
        foreach($secval as $vsec) {
            $force_parent = TRUE;
            switch($vsec[0]) {
            case '^':                   // force Freamework Template
                $vsec = substr($vsec,1);
                $before = $tmplist;     // change before list
                $tmplist = array( 'Libs' => $tmplist['Libs']);
            debug_log(-999,["Libs::{$vsec}:{$secname}"=>['BEFORE'=>$before,'AFTER'=>$tmplist]]);
                break;
            case '!':               // force parent Template
                $vsec = substr($vsec,1);
                $before = $tmplist;     // change before list
                array_shift($tmplist);  // remove top element
            debug_log(-999,["Parent::{$vsec}:{$secname}"=>['BEFORE'=>$before,'AFTER'=>$tmplist]]);
                break;
            default:
                $before = $tmplist;     // change before list
                if($vsec === $secname || !array_key_exists($vsec, $secData) ) {
                    array_shift($tmplist);  // remove top element
                } else $force_parent = FALSE;
            debug_log(-999,["tmplist-{$vsec}:{$secname}"=>['BEFORE'=>$before,'AFTER'=>$tmplist]]);
            }
            list($key,$item) = array_first_item($tmplist);
            $secmsg = ($force_parent) ? "Invoke {$key}:{$vsec}" : "Subsection: {$vsec}";
            if($this->do_msg) echo "/* {$secmsg} in {$secname} */\n";
            if($force_parent) {
                $this->section_styles($tmplist, $vsec);     // Parent Section
            } else {      // exists SECTION in SELF template 
                $this->sectionDispath($secParam,$secData[$vsec]);
            }
        }
    }
//------------------------------------------------------------------------------
// ファイルのインポート処理
    private function filesImport($tmplist, $sec) {
        $files = (is_array($sec)) ? $sec : array($sec);
        foreach($files as $key=>$vv) {
            if(empty($vv)) {
                preg_match('/(?:@(\w+):)?(.+)/',$key,$m);
                list($tmp,$vars,$vv) = $m;
                $test_value = MySession::get_paramIDs($vars);
                if(is_bool_false($test_value)) continue;
            }
            if(get_protocol($vv) !== NULL) {    // IMPORT from INTERNET URL
                list($filename,$v_str) = (strpos($vv,';')!==FALSE)?explode(';',$vv):[$vv,''];  // 置換文字列
                parse_str($v_str, $vars);
                if($this->do_msg) echo "/* import from {$filename} */\n";
                $content = file_get_contents($filename);
                $replace_keys   = array_keys($vars);
                $replace_values = array_values($vars);
                if(!empty($replace_keys)) $content = str_replace($replace_keys,$replace_values, $content);
                echo "{$content}\n";
                $imported = TRUE;
                array_push($this->importFiles,$vv); // for-DEBUG
                continue;
            }
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
                    array_push($this->importFiles,$fn); // for-DEBUG
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
        if($val[0] === '$' && $val[1] === '{' ) {       // 先頭の2文字が変数文字
            $var = mb_substr($val,1);
            $var = trim($var,'{}');                 // 変数の区切り文字{ } は無条件にトリミング
            switch($var[0]) {
            case '#':
                $var = mb_substr($var,1);     // 言語ファイルの参照
                $val = LangUI::get_value('resource', $var);
                break;
            case '$': if(substr($var,-1) === '$') {     // 末尾文字を確かめる
                    $var = trim($var,'$');              // システム変数値
                    if(isset($this->repVARS[$var])) $val = $this->repVARS[$var];
                }
                break;
            case '^':       // ENV or REQ VAR
            case '"':       // REQ-VAR
            case "'":       // ENV-VAR
                if(substr($var,-1) === $var[0]) {     // check end-char
                    $tt = $var[0];
                    $var = trim($var,$tt);
                    if($tt === '^') {                   // ENV/REQ both check
                        $val = MySession::get_varIDs(true,$var);
                        if(!empty($val)) break;
                    }
                    $val = MySession::get_varIDs(($tt==="'"),$var);// get SESSION ENV or REQUEST
                }
                break;
            default:
                if(isset($vars[$var])) $val = $vars[$var];             // 環境変数で置換
            }
        }
    }
//==============================================================================
//  文字列の変数置換を行う
// $[@#%$]varname | ${[@#%$]varname} | {$SysVar$} | {%Params%}
    private function expand_Strings($str,$vars) {
        $p = '/(?:\${[^}\s]+?}|\${[#%\'"\$][^}\s]+?})/';          // 変数リストの配列を取得
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
