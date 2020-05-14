<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  AppStyle:   css, js ファイルの結合出力を可能にする
 *              テンプレートのサンプルは *.cpl を参照
 * テンプレート書式：
 * Section = {                              'Section' が拡張子を除いたファイル名
 *  *Comment            		    		* コメントタグ
 *  import => [ value, ... ]                CSS/JS ファイルのインクルード、スカラー値でも可
 *  jquery => [ value, ... ]                JSファイルの時のみ、JQuery関数 ファイルのインクルード
 *  section => subsection                   別のセクション定義を読み込む、自ファイルに存在しなければ上位ファイルで探索する
 *  tag => value                            タグの出力 => tag "value";
 *  tag => [ attr , ... ]                   属性リストの出力 => tag { attr; ... }
 * }
 * 
 * セクション外にあるコマンドはセクション指定にかかわらず処理される
 * テンプレートファイル( stylesheet.cpl or javascript.cpl) の探索は以下の順で行う
 * モジュールフォルダ → アプリ共通フォルダ → wwwroot フォルダ → Libテンプレートフォルダ
 * 
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

    ];
    const BoolConst = [ 'yes' => TRUE, 'no' => FALSE, 'true' => TRUE, 'false' => FALSE, 'on' => TRUE, 'off' => FALSE ];
    const ExtendCommand = [
        '@compact' => 'do_min',         // コンパクト設定
        '@comment' => 'do_com',         // コメント除去
        '@message' => 'do_msg',         // デバッグメッセージ設定
    ];
    private $ModuleName;        // モジュール名 or Res
    private $Template;          // ContentList のテンプレート
    private $Folders;           // ファイル探索フォルダ
    private $TempSection;       // 探索結果のフォルダリスト、再帰利用でスタックする
    private $Section;           // テンプレートセクション配列
    private $IncludePath;       // インクルードパス
    private $Filetype;          // サブフォルダ css/js
    private $do_min;            // コンパクト出力するかどうかのフラグ
    private $do_msg;            // インポートメッセージ出力のフラグ
    private $do_com;            // コメントの除去
    private $TmpStack;          // テンプレートスタック
    private $repVARS;           // 置換文字列
//===============================================================================
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
            'filename' => $filename,
            'extension' => $ext,
        );
        if(isset(MySession::$PostEnv['sysVAR'])) {
            $sess = MySession::$PostEnv['sysVAR'];              // セッションに記憶してあるシステム変数
            $this->repVARS = array_merge($sess, $myVARS);       // システム変数とパラメータをマージしておく
        } else {
            $this->repVARS = $myVARS;       // パラメータのみセット
        }
    }
//===============================================================================
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
//===============================================================================
// テンプレートフォルダの位置からファイル探索フォルダのリストを作成する
    private function get_search_folders($category) {
        $arr = $this->Folders;
        reset($arr);        // 配列ポインタを先頭に
        while ($category !== key($arr)) {   // 先頭のキーを判別
            array_shift($arr);              // 先頭を削除
            if(empty($arr)) break;          // 配列が空っぽになった
        }
        return $arr;
    }
//===============================================================================
//　ヘッダ出力
public function ViewHeader() {
    header("Content-Type: {$this->Template['head']};");
}
//===============================================================================
//　レイアウトテンプレート処理
public function ViewStyle($filename) {
    // スタイルシートが存在するフォルダのみをリストアップする
    $temlatelist = $this->get_exists_files('template.mss');
    list($filename,$ext) = extractBaseName($filename);
    $this->do_min = ($ext == 'min');           // コメント削除出力か
    $this->do_msg = TRUE ;                     // インポートコメント表示設定
    $this->do_com = TRUE ;                     // コメント表示設定
    $this->TempSection = $temlatelist;          // セクション探索対象
    $this->TmpStack = [];                       // スタックリセット
    if($this->SectionStyleSet($temlatelist, $filename, FALSE)) return;      // セクション処理が完了しているなら終了
    // テンプレートセクションに指定ファイル名が見つからないときは実ファイルを探索する
    foreach($this->Folders as $key => $file) {
        $fn ="{$file}/{$this->Filetype}/{$filename}{$this->Template['extention']}";
        if(file_exists($fn)) {
            $content = file_get_contents($fn);
            if($this->do_msg) echo "/* include({$filename}{$this->Template['extention']}) in {$key} */\n";
            $this->output_content($content);
        }
    }
}
//===============================================================================
//　セクションスタイルテンプレート処理
//  TRUE: セクション処理済
//  FALSE: セクションNOT FOUND
    private function SectionStyleSet($tmplist, $secname, $dotag) {
        foreach($tmplist as $category => $file) {      // テンプレートファイルの残りから探索する
            $parser = new SectionParser($file);
            // js/css 統合バージョン
            $SecTemplate = array_change_key_case( $parser->getSectionDef(), CASE_LOWER);
            $secType = $this->Template['section'];              // section タイプ
            $this->Section = (array_key_exists($secType,$SecTemplate)) ? $SecTemplate[$secType] : [];
            foreach(self::ExtendCommand as $cmd => $var) {
                if(array_key_exists($cmd,$SecTemplate)) {           // セクション外にコマンドがある時に備える
                    $val = strtolower($SecTemplate[$cmd]);          // 設定値を取り出す
                    $this->$var = self::BoolConst[$val];            // 指定プロパティ変数にセット
                }
            }
            unset($this->TempSection[$category]);                 // 調べ終わったファイルは削除
            if(array_key_exists($secname,$this->Section)) {
                if($this->do_msg) echo "/* {$category}テンプレート: {$secname} */\n";
                $this->IncludePath = $this->get_search_folders($category);  // テンプレートの位置からインクルードパスを生成
                // セクション呼び出しは直接要素を渡す
                $secTag = ($dotag) ? $this->Section[$secname] : $this->Section;  // タグ処理をするならサブセクションだけを渡す
                $this->SectionItemOutput($secTag, $secname, $dotag);
                return TRUE;      // 見つかったらテンプレート処理を終了
            }
            unset($parser);         // 解放
            unset($this->Section);
        }
        return FALSE;      // セクションが見つからなかったら実ファイルを探索する
    }
//===============================================================================
//    コメント出力かチェック
//      TRUE: コメント出力した
//      FALSE: 未出力
    private function check_commentout($vv) {
        if($vv[0] == '*') {
            $vv = trim(substr($vv,1));
            echo "/* {$vv} */\n";
            return TRUE;
        }
        return FALSE;
    }
//===============================================================================
//    タグ出力
    private function dump_tag($key, $vv) {
        if(is_array($vv)) {
            echo "{$key} {\n";
            $this->SectionItemOutput($vv,$key,FALSE);
            echo "}\n";
        } else {
            echo "{$key} \"{$val}\";\n";
        }
    }
//===============================================================================
//    ファイルをコンパクト化して出力
    private function output_content($content) {
        if($this->do_min) {         // コメント・改行を削除して最小化して出力する
            $pat = '[:()}\[\]<>\=\?;,]';    // 前後の空白を削除する文字
            $content = preg_replace("/\\s*({$pat})\\s+|\\s+({$pat})\\s*|(\\s)+/", '$1$2$3',
                    preg_replace('/\/\*[\s\S]*?\*\/|\/\/.*?\n/','',$content));       // コメント行を削除
            $content =trim($content);
        } else if(!$this->do_com) {         // コメントだけを削除して出力する
            $content = preg_replace('/( )+|([\r\n])+/','$1$2',                  // 2個以上の空白または改行を1個に圧縮
                    preg_replace('/\/\*[\s\S]*?\*\/|\/\/.*?\n/','',$content));  // コメント行を削除
            $content =trim($content);
        }
        echo "{$content}\n";
    }
//===============================================================================
// システム変数＋URIパラメータへの置換処理
    private function replaceArrays($vars, $content) {
        // あらかじめマージしたシステム変数と環境変数をマージし置換配列を生成する
        $vals = is_array($vars) ? array_merge($this->repVARS,$vars) : $this->repVARS;
        $keyset = array_map(function($a) { return (is_numeric($a))?"{%{$a}%}":"{\${$a}\$}";}, array_keys($vals));
        return str_replace ( $keyset, $vals , $content );    // 置換配列を使って一気に置換
    }
//===============================================================================
//    ファイルの読み込み
    private function import_files($val) {
        $files = (is_array($val)) ? $val : array($val);
        foreach($files as $vv) {
            list($filename,$v_str) = (strpos($vv,'?')!==FALSE)?explode('?',$vv):[$vv,''];  // クエリ文字列を変数セットとして扱う
            parse_str($v_str, $vars);
            $imported = FALSE;
            if(!$this->check_commentout($filename)) {
                foreach($this->IncludePath as $key => $file) {
                    $fn ="{$file}/{$this->Filetype}/{$filename}";
                    if(file_exists($fn)) {
                        // @charset を削除して読み込む
                        $content = preg_replace('/(@charset.*;)/','/* $1 */',trim(file_get_contents($fn)) );
                        $content = $this->replaceArrays($vars, $content);
                        if($this->do_msg) echo "/* import({$filename}) in {$key} */\n";
                        $this->output_content($content);
                        $imported = TRUE;
                        break;
                    }
                }
                if(!$imported) echo "/* NOT FOUND: {$filename} */\n";
            }
        }
    }
//===============================================================================
//    セクションレイアウト出力
    private function SectionItemOutput($secArray,$secname,$doit) {
        array_push($this->TmpStack,$this->TempSection,$this->Section,$this->IncludePath);         // 探索パスとセクション要素を退避
        $sec = (is_array($secArray)) ? $secArray : array($secArray);
        foreach($sec as $Akey => $val) {
            $key = tag_body_name($Akey);
            if($key == 'section') {                        // 別のセクションをインクルード
                $secval = (is_array($val)) ? $val : array($val);    // 配列要素に統一
                foreach($secval as $vsec) {
                    if($vsec[0] == '@') {
                        if(!DEBUGGER) continue;   // デバッグモードでなければスキップ
                        $vsec = substr($vsec,1);
                    }
                    $force_parent = $vsec[0] == '^';
                    if($force_parent) $vsec = substr($vsec,1);
                    if(!$this->check_commentout($vsec)) {                           // コメント出力を許す
                        $secmsg = (($vsec == $secname) || $force_parent) ? "parent::{$vsec}" : $vsec;
                        if($this->do_msg) echo "/* subsection: {$secmsg} in {$secname} */\n";
                        if((!$force_parent) && ($secname !== $vsec) && array_key_exists($vsec, $this->Section)) {  // 同じセクション名は上位を探索
                            $this->SectionItemOutput($this->Section[$vsec],$vsec,TRUE);    // 同一テンプレート内の別のセクションを処理
                        } else {
                            if($force_parent) {     // ライブラリテンプレートにジャンプ
                                foreach($this->TempSection as $key => $val) {
                                    if($key !== 'Libs') unset($this->TempSection[$key]);     // Libs以外を削除
                                }
                            }
                            $this->SectionStyleSet($this->TempSection, $vsec, TRUE);     // 上位テンプレートからセクション名を探索する
                        }
                    }
                }
            } else if(array_key_exists($key,self::ExtendCommand)) {     // 拡張コマンドに一致するか
                $var = self::ExtendCommand[$key];               // プロパティ変数名の取得
                $val = strtolower($val);                        // 設定値を取vり出す
                $this->$var = self::BoolConst[$val];            // 指定プロパティ変数にセット
            } else if($key == 'jquery' && $this->Filetype == 'js') {                 // JQuery関数をインクルード
                echo "$(function() { ";
                $this->import_files($val);
                echo "});\n";
            } else if($key == 'import') {                  // ファイルを読み込んでそのまま出力
                $this->import_files($val);
            } else if(is_numeric($key)) {          // 直接出力
                if(!$this->check_commentout($val)) echo "{$val};\n";
            } else if(is_array($val)) {
                if(($key == $secname)) {
                    if($this->do_msg) echo "/* section: {$secname} */\n";
                    $this->SectionItemOutput($val,$secname,TRUE);
                } else if($doit) {      // タグとして出力
                    $this->dump_tag($key,$val);
                }
            } else {
                echo "{$key} \"{$val}\";\n";
            }
        }
        $this->IncludePath = array_pop($this->TmpStack);         // インクルードパスを回復
        $this->Section = array_pop($this->TmpStack);         // セクション情報を回復
        $this->TempSection = array_pop($this->TmpStack);         // 探索パスを回復
    }

}
