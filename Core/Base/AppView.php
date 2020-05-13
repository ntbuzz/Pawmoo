<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  AppView:    ビュー生成用のテンプレート処理、
 *              PHPテンプレート(*.tpl)とセクションテンプレート(*.ptl)を読み込んでHTMLを出力する
 *              HTML出力のため AppHelper を所有する
 */
require_once('Core/Class/Parser.php');

class AppView extends AppObject {
    protected $Layout;        // デフォルトのレイアウト
    private $doTrailer = FALSE;
    const Extensions = array("tpl","php","inc","twg");  // テンプレート拡張子
    const SectionCMD = '<@&+*%-.#{[';  // 単独セクション処理コマンド文字
    private $rep_array;
    private $env_vars;
    private $inlineSection;        // インラインのセクション
    const FunctionList = array(
        '<'   => 'sec_html',
        '@'    => 'sec_import',
        '&'    => 'sec_helper',
        '+'    => [
            'include'   => 'cmd_include',
            'style'     => 'cmd_style',
            'echo'      => 'cmd_echo',
            'jquery'    => 'cmd_jquery',
            'script'    => 'cmd_script',
            'ul'        => 'cmd_list',
            'ol'        => 'cmd_list',
            'dl'        => 'cmd_dl',
            'inline'    => 'cmd_inline',
//            'setvar'    => 'cmd_setvar',
        ],
        '*'    => 'sec_comment',
        '%'    => 'sec_link',
        '-'    => 'sec_singletag',
    );
    //==========================================================================
    // コンストラクタで他のデータベースに接続
    //==========================================================================
    function __construct($owner) {
        parent::__construct($owner);
        $this->Model = $owner->Model;       // ViewのオーナーはControllerなのでModelを参照する
        $helper = "{$this->ModuleName}Helper";
        if(! class_exists($helper)) $helper = 'AppHelper';
        $this->Helper = new $helper($this);
        $this->Helper->MyModel = $this->Model;
        $this->__InitClass();                       // クラス固有の初期化メソッド
    }
    //==========================================================================
    // クラス固有の初期化
    protected function __InitClass() {
        $this->Layout = 'Layout';
        $this->rep_array = array_merge(App::$SysVAR, App::$Params);       // システム変数とパラメータをマージしておく
        $this->env_vars = [];
        parent::__InitClass();                    // 継承元クラスのメソッドを呼ぶ
    }
//==============================================================================
// デフォルトレイアウト変更
//==============================================================================
public function SetEnv($key,$val) {
    $this->env_vars[$key] = $val;
}
//==============================================================================
// デフォルトレイアウト変更
//==============================================================================
public function SetLayout($layoutfile) {
    $tmplate = $this->getTemplateName($layoutfile);   // ビューフォルダのテンプレート
    if(!file_exists($tmplate)) {                // 存在しないなら共通のテンプレートを探す
        $layoutfile = 'Layout';
    }
    $this->Layout = $layoutfile;
}
//==============================================================================
// レイアウト出力
//==============================================================================
public function PutLayout() {
    APPDEBUG::MSG(0, $this->Layout,'$Layout');
    $this->ViewTemplate($this->Layout);
    $this->doTrailer = TRUE;
}
//==============================================================================
// ページ出力完了
public function __TerminateView() {
    APPDEBUG::MSG(10, $this);
    if($this->doTrailer) {
        $tmplate = $this->getTemplateName('Trailer');   // ビューフォルダのテンプレート
        $Helper = $this->Helper;
        if($tmplate !== NULL) require_once ($tmplate);
        // リクエストURLと処理メソッドが違っていたとき
        $req = App::$SysVAR['METHOD'];
        $act = App::$SysVAR['CONTROLLER']."/". App::$SysVAR['method'];
        if($req !== $act) {
            APPDEBUG::MSG(1,$act, "URL書換");
            $url = "{$act}/" . App::$SysVAR['PARAMS'];
            echo "<script type='text/javascript'>\n$(function() { history.replaceState(null, null, \"{$url}\"); });\n</script>\n";
        }
        if(DEBUGGER) {
            APPDEBUG::MSG_SORT();                   // メッセージ要素の並べ替え
            $this->ViewTemplate('debugbar');
        }
    }
}
//===============================================================================
//　レイアウトテンプレート処理
public function ViewTemplate($name,$vars = []) {
    $tmplate = $this->getTemplateName($name);   // ビューフォルダのテンプレート
    if(isset($tmplate)) {
        $ext = substr($tmplate,strrpos($tmplate,'.') + 1);    // 拡張子を確認
        $ix = array_search($ext, self::Extensions);
        switch($ix) {       //   [ .tpl, .php, .inc, .twg ]
        case 0:     // '.tpl'   div Section
            $parser = new SectionParser($tmplate);
            $divSection = $parser->getSectionDef();
            $this->inlineSection = [];         // インラインセクション定義をクリア
            APPDEBUG::arraydump(0,["SECTION @ {$name}" => $divSection,"SEC-VARS" => $vars]);
            $this->section_analyze($divSection,$vars);
            break;
        case 1:     // 'php'     // PHP Template
            extract($vars);             // 変数展開
            $Helper = $this->Helper;    // ヘルパークラス
            $RecData = $this->Model->RecData;    // レコードデータ
            $Records = $this->Model->Records;    // レコードリスト
            $Header = $this->Model->Header;    // スキーマヘッダ
            require_once ($tmplate);
            break;
        case 2:     // 'inc':     // HTML template
            $content = file_get_contents($tmplate);
            // システム変数＋URIパラメータへの置換処理
            echo $this->replaceArrays($vars, $content);
            break;
        }
    }
}
//==============================================================================
// テンプレートファイルがビュークラスフォルダに存在しなければ共通のテンプレートを探す
    private function getTemplateName($name) {
        $temlatelist = array(
            App::AppPath("modules/{$this->ModuleName}/View/{$name}"),   // モジュールのビューレイアウト
            App::AppPath("View/{$name}"),                               // App共通のレイアウトテンプレートを探す
            "Core/Template/View/{$name}"                               // ライブラリのテンプレートを探す
        );
        foreach($temlatelist as $file) {
            foreach(self::Extensions as $ee) {
                $form = "{$file}.{$ee}";
                if(file_exists($form)) {                // レイアウトファイルが見つかった
                    return $form;
                }
            }
        }
        check_cwd(get_class($this)."@{$name} in {$this->ModuleName}");
        return NULL;
    }
//==============================================================================
// システム変数＋URIパラメータへの置換処理
    private function replaceArrays($vars, $content) {
        // あらかじめマージしたシステム変数と環境変数をマージし置換配列を生成する
        $vals = array_merge($vars,$this->rep_array,$this->env_vars);
        $keyset = array_map(function($a) { return (is_numeric($a))?"{%{$a}%}":"{\${$a}\$}";}, array_keys($vals));
        // デバッグ情報
        APPDEBUG::arraydump(11,["Replace" => array_combine($keyset, $vals)]);
        return str_replace ( $keyset, $vals , $content );    // 置換配列を使って一気に置換
    }
//==============================================================================
//  変数を置換する
    private function expand_walk(&$val, $key, $vars) {
        if($val[0] === '$') {           // 先頭の一文字が変数文字
            $var = mb_substr($val,1);
            $var = trim($var,'{}');                 // 変数の区切り文字{ } は無条件にトリミング
            switch($var[0]) {
            case '@': $var = mb_substr($var,1);     // レコードデータの参照指定
                $val = $this->Model->RecData[$var]; // レコードのフィールド値で置換
                break;
            case '#': $var = mb_substr($var,1);     // 言語ファイルの参照
                $val = $this->_($var);              // 言語ファイルの定義配列から文字列を取り出す
                break;
            case '%': $var = mb_substr($var,1);     // URLの引数番号
                $val = App::$Params[$var];          // Params[] プロパティから取得
                break;
            case '$': $var = mb_substr($var,1);     // システム変数値
                $val = App::$SysVAR[$var];          // SysVAR[] プロパティから取得
                break;
            default:
                if(isset($vars[$var])) {
                    $val = $vars[$var];             // 環境変数で置換
                } else if(isset($this->$var)) {
                    $val = $this->$var;             // プロパティ変数で置換
                }
            }
        }
    }
//==============================================================================
//  セクション要素内の変数を展開する
    private function expandSectionVar($vv,$vars) {
        if(!is_array($vv)) {        // スカラー要素の場合
            return $this->replaceArrays($vars, $vv);   // 変数置換を行う
        }
        foreach($vv as $kk => $nm) {
            if(is_array($nm)) {
                $vv[$kk] = $this->expandSectionVar($nm,$vars);
            } else {
                $p = '/(?:[^\$]+)|(?:\${[^}]+})|(?:\$[^\$]+)/';     // 複数の配列名を含む場合に備える
                preg_match_all($p, $nm, $m);
                $exvar = $m[0];
                array_walk($exvar, array($this, 'expand_walk'), $vars);
                if(is_array($exvar[0])) {
                    $vv[$kk] =$exvar[0];     // 配列に置換する
                } else {
                    $vvar = implode($exvar,'');   // 展開した変数値を結合する
                    if(isset($vvar)) {
                        $vv[$kk] = $vvar;     // 変数値は引数 vars[] 配列内に変数名をキー名として格納されている
                    }  else {
                        unset($vv[$kk]);            // 見つからなければ変数を削除
                    }
                }
            }
        }
        return $vv;
    }
//==============================================================================
// タグセクションレ出力
    private function directOutput($beg_tag, $end_tag,$sec) {
        echo "{$beg_tag}\n";
        if(is_array($sec)) {
            foreach($sec as $vv) echo "{$vv}\n";
        } else echo "{$sec}\n";
        echo "{$end_tag}\n";
    }
//==============================================================================
// 配列をマージ
    private function my_array_merge($arr1,$arr2) {
        foreach($arr2 as $key => $val) {
            if($key[0] === '+') {
                $var = substr($key,1);                            // + 記号を取り除いた名前
                if(isset($arr1[$var])) {
                    $arr1[$var] = array_merge($val,$arr1[$var]);  // 既存配列にマージ
                } else {
                    $arr1[$var] = $val . $arr1[$var];             // 既存文字列にマージ
                }
            } else {
                $arr1[$key] = $val;               // 既存配列を置換
            }
        }
        return $arr1;
    }
//******************************************************************************
// ここから新しいセクション解析処理
//******************************************************************************
//==============================================================================
// セクション配列を解析して処理関数へディスパッチする
    private function section_analyze($divSection,$vars) {
        foreach($divSection as $key => $sec) {
            if($key === '+setvar') {
                $vv = $this->expandSectionVar($sec,$vars);
                $vars = $this->my_array_merge($vv,$vars);  // 環境変数にマージ、引数側を優先
            } else
                $this->section_dispath($key,$sec,$vars);
        }
    }
//==============================================================================
// key 文字列を元に処理関数へディスパッチする
// key => sec (vars)
    private function section_dispath($key,$sec,$vars) {
        $sec = $this->expandSectionVar($sec,$vars);
        $num_key = is_numeric($key);
        if($num_key) {
            if(is_array($sec)) {
                $this->section_analyze($sec,$vars);
                return;
            }
            $key = $sec;
            $sec = [];
        }
        // 重複回避用の文字を削除
        $key = tag_body_name($key);

        $top_char = $key[0];
        if(array_key_exists($top_char,self::FunctionList)) {
            $kkey = mb_substr($key,1);      // 先頭文字を削除
            $func = self::FunctionList[$top_char];
            // + コマンドには属性が付いている
            list($tag,$text,$attrs,$subsec) = $this->tag_attr_sec($kkey,$sec);
            if(is_array($func)) {       // サブコマンドテーブル
                $cmd = $func[$tag];
                if(array_key_exists($tag,$func) && (method_exists($this, $cmd))) {
                    $this->$cmd($tag,$attrs,$sec,$vars);
                } else echo "***NOT FOUND({$cmd}): {$cmd}({$tag},\$attrs,\$sec,\$vars)\n";
            } else if(method_exists($this, $func)) {
                $this->$func($kkey,$sec,$vars);
            } else echo "CALL: {$func}({$kkey},{$sec},vars)\n";
        } else {
debug_dump(0,"ANALYZ:'{$key}'");
            list($tag,$text,$attrs,$subsec) = $this->tag_attr_sec($key,$sec);
            $attr = $this->gen_attrs($attrs);
debug_dump(0, ["tag" => $tag,"attrs" => $attrs,"attr" => $attr,"text" => $text,"sec" => $subsec]);
            if(is_array($sec)) {
                echo "<{$tag}{$attr}>{$text}\n";
                $this->section_analyze($subsec,$vars);
                echo "</{$tag}>\n";
            } else {
                echo "<{$tag}{$attr}>{$text}</tag>\n";
            }
        }
    }
    //==========================================================================
    // 先頭の < 文字が削除されているので補填する
    private function gen_attrs($attrs) {
        $attr = "";
        if($attrs !== array()) {
            foreach($attrs as $name => $val) {
                $attr = " {$name}=\"{$val}\"{$attr}";
            }
        }
        return $attr;
    }
    //==========================================================================
    // 先頭の < 文字が削除されているので補填する
    private function sec_html($key,$sec,$vars) {
        echo "<{$key}\n";
    }
    //==========================================================================
    // 先頭の < 文字が削除されているので補填する
    private function sec_comment($key,$sec,$vars) {
        echo "<!-- $key" . (empty($sec) ? " " : "\n");
        foreach($sec as $kk => $vv) echo "{$vv}\n";
        echo "-->\n";
    }
    //==========================================================================
    private function sec_import($key,$sec,$vars) {
        if($key[0] === '.') $key = substr($key,1);
        // 引数を変数リストに追加
        $mergevars = (is_array($sec)) ? array_merge($vars, $sec) : $vars;
        if(array_key_exists($key,$this->inlineSection)) {
            $this->section_analyze($this->inlineSection[$key],$mergevars);
        } else {
            $this->ViewTemplate($key,$mergevars);
        }
    }
    //==========================================================================
    //  属性のみの単独タグ要素の処理
    private function sec_singletag($key,$sec,$vars) {
        // $key と $sec をタグと属性に分解する
        list($tag,$text,$attrs,$subsec) = $this->tag_attr_sec($key,$sec);
        $attr = $this->gen_attrs($attrs);
        if(!empty($subsec)) {  // サブセクションがあればリピート
            foreach($subsec as $kk => $vv) {
                list($tt,$txt,$at,$sub) = $this->tag_attr_sec($kk,$vv);
                $atr = $this->gen_attrs($at);
                echo "<{$tag}{$attr}{$atr}>\n";
            }
        } else {
            echo "<{$tag}{$attr}>\n";
        }
    }
    //==========================================================================
    // ALink ハイパーリンク
    private function sec_link($key,$sec,$vars) {
        if($key === 'link') {
            if(is_array($sec)) {
                foreach($sec as $kk => $vv) $this->Helper->ALink($vv,$kk);
            } else echo "{$tagname} bad argument.\n";
        } else if(is_scalar($sec)) {
            $this->expand_walk($key,'', $vars);     // 変数展開する
            $this->Helper->ALink($sec,$key);
        } else echo "tag '{$tagname}' not for feature.\n";
    }
    //==========================================================================
    // Helper関数の呼出
    private function sec_helper($key,$sec,$vars) {
        // ヘルパーに指定メソッドが存在するかチェック
        if(method_exists($this->Helper,$key)) {
            (is_numeric($key)) 
                ? $this->Helper->$key()
                : $this->Helper->$key($sec);
        } else if(method_exists('App',$key)) {
            (is_numeric($key)) 
                ? App::$key()
                : App::$key($sec);
        } else {
            echo "Helper Method:'{$key}' not found. Please Create this method.\n";
//            die("Helper Method:{$key} not found.");
        }
    }
    //--------------------------------------------------------------------------
    // cmd_xxxx の関数は引数が少し異なる
    //--------------------------------------------------------------------------
    //  外部ファイルのインクルード
    private function cmd_include($tag,$attrs,$sec,$vars) {
        App::WebInclude($sec);
    }
    //--------------------------------------------------------------------------
    //  JQueryスクリプトの出力
    private function cmd_jquery($tag,$attrs,$sec,$vars) {
        $this->directOutput("<script type='text/javascript'>\n$(function() {", "});\n</script>",$sec);
    }
    //--------------------------------------------------------------------------
    //  javascriptの出力
    private function cmd_script($tag,$attrs,$sec,$vars) {
        $this->directOutput("<script type='text/javascript'>", "</script>",$sec);
    }
    //--------------------------------------------------------------------------
    //  スタイルシートの出力
    private function cmd_style($tag,$attrs,$sec,$vars) {
        $this->directOutput('<style type="text/css">', "</style>",$sec);
    }
    //--------------------------------------------------------------------------
    //  単純エコー出力
    private function cmd_echo($tag,$attrs,$sec,$vars) {
        $this->directOutput('', '',$sec);
    }
    //--------------------------------------------------------------------------
    //  インラインセクションの登録
    private function cmd_inline($tag,$attrs,$sec,$vars) {
        $name = $attrs['class'];
        $this->inlineSection[$name] = $sec;
    }
    //--------------------------------------------------------------------------
    //  ul/ol リストの出力
    // +ul => [
    //   { li.class#id => } [   ]       
    // ]
    private function cmd_list($tag,$attrs,$sec,$vars) {
        $attr = $this->gen_attrs($attrs);
        echo "<{$tag}{$attr}>\n";
        // リスト要素の出力
        foreach($sec as $kk => $vv) {
            // $key と $sec をタグと属性に分解する
            list($s_tag,$s_text,$s_attr,$s_sec) = $this->tag_attr_sec($kk,$vv);
            $attr = $this->gen_attrs($s_attr);
            if(!empty($s_sec)) {  // サブセクションがあればセクション処理
                echo "<li{$attr}>{$s_text}\n";
                $this->section_analyze($s_sec,$vars);
                echo "</li>\n";
            } else {
                echo "<li{$attr}>{$s_text}</li>\n";
            }
        }
        echo "</{$tag}>\n";
    }
    //--------------------------------------------------------------------------
    //  dl リストの出力
    // +dl => [
    //    [ DT-Text 
    //      { DD-ATTR => } [ SECTION ]
    //    ]
    //      ...
    // ]
    private function cmd_dl($tag,$attrs,$sec,$vars) {
        $attr = $this->gen_attrs($attrs);
        echo "<{$tag}{$attr}>\n";
        // DTのリスト要素の出力
        foreach($sec as $kk => $vv) {
            // $key と $sec をタグと属性に分解する
            list($dt_tag,$dt_text,$dt_attrs,$dd_sec) = $this->tag_attr_sec($kk,$vv);
            $attr = $this->gen_attrs($dt_attrs);
            echo "<dt{$attr}>{$dt_text}</dt>\n";
            if(!empty($dd_sec)) {  // DDセクションがあれば処理
                foreach($dd_sec as $dd_key => $dd_sub) {
                    list($dd_tag,$dd_text,$dd_attrs,$dd_child) = $this->tag_attr_sec($dd_key,$dd_sub);
                    $dd_attr = $this->gen_attrs($dd_attrs);
                    echo "<dd{$dd_attr}>{$dd_text}\n";
                    $this->section_analyze($dd_child,$vars);
                    echo "</dd>\n";
                }
            } else {
                echo "<dd></dd>\n";
            }
        }
        echo "</{$tag}>\n";
    }
    //==========================================================================
    // タグ文字列の分解
    private function tag_separate($tag) {
        $attrList = [];
        // $tag に含まれる属性を取り出す
        foreach(['data' => '{', 'name' => '[', 'id' => '#', 'class' => '.'] as $key => $sep) {
            $n = strrpos($tag,$sep);
            if( $n !== FALSE) {
                $str = tag_body_name( substr($tag,$n + 1) );  // 重複回避文字列があれば除去
                $tag = substr($tag,0, $n);    // 残りの文字列
                if($sep[0] == '{') {            // data- 属性
                    $str = trim($str,'{}');
                    $kk = "{$key}-element";
                    $attrList[$kk] = $str;
                } else if($sep[0] == '[') { // name属性
                    $str = trim($str,'[]');
                    $attrList[$key] = $str;
                } else {
                    $attrList[$key] = $str;
                }
            }
        }
        if(empty($tag)) $tag = 'div';
        return array($tag,$attrList);
    }
    //==========================================================================
    // タグ文字列の分解
    private function tag_attr_sec($tag,$sec) {
        $innerText = '';
        $secList = [];
        list($tag,$attrList) = $this->tag_separate($tag);
        // $sec の中から innerText と attr を取り出す
        if(is_array($sec)) {
            foreach($sec as $key => $val) {
                if(is_numeric($key)) {  // 連想キーが無い場合
                    // 値が配列かセクション用コマンドならセクションデータ扱い
                    if(is_array($val)||(strpos(self::SectionCMD,$val[0]) !== FALSE)) {
                        debug_dump(0,["SEC:"=>$val]);
                        $secList[] = $val;    // 配列かコマンド名ならセクション
                    } else {
                        debug_dump(0,"TXT:{$val}");
                        $innerText .= $val;    // スカラー値ならテキスト
                    }
                } else {
                    list($vv,$attrs) = $this->tag_separate($key);   // タグ分解
                    // $val が配列か、$key が属性付きならセクション扱い
                    if(is_array($val) || !empty($attrs)) $secList[$key] = $val;
                    else $attrList[$key] = $val;    // それ以外は属性指定
                }
            }
        } else {
            $innerText .= $sec;        // スカラーならテキスト
        }
        return array($tag,$innerText,$attrList,$secList);
    }
/*
//******************************************************************************
// 古くさいセクション処理
//===============================================================================
// タグ文字列の分解
    private function TagInfo($val) {
        $val = 　TagBodyName($val);
        $row = $val;
        $tag = [];
        $attr = '';
        foreach(['data' => '{', 'name' => '[', 'id' => '#', 'class' => '.'] as $key => $sep) {
            $n = strrpos($val,$sep);
            if( $n !== FALSE) {
                $str = substr($val,$n + 1);   // 文字列抽出
                $val = substr($val,0, $n);    // 残りの文字列
                if($sep[0] == '{') {            // data- 属性
                    $str = trim($str,'{}');
                    $kk = "{$key}-element";
                    $tag[$kk] = $str;
                    $attr = " {$kk}=\"{$str}\"{$attr}";
                } else if($sep[0] == '[') { // name属性
                    $str = trim($str,'[]');
                    $tag[$key] = $str;
                    $attr = " {$key}=\"{$str}\"{$attr}";
                } else {
                    $tag[$key] = $str;
                    $attr = " {$key}=\"{$str}\"{$attr}";
                }
            }
        }
        if(!empty($val) && strpos(self::SectionCMD,$val[0]) !== FALSE) {
            $tag['type'] = $val[0];
            $val = substr($val,1);
            $row = substr($row,1);
        } else $tag['type'] = '!';
        $tag['tagname'] = ($val === '') ? 'div' : $val;
        $tag['tag'] = "{$tag['tagname']}{$attr}";
        $tag['attr'] = $attr;
        $tag['str'] = $row;
        return $tag;
    }
//===============================================================================
//  タグセクションかどうかを判定する
    private function isTagSection($kk,$vv) {
        $ret = is_array($vv);       // 配列要素を持つ
        if( $ret ) {
            $ret = !is_numeric($kk);     // 連想キー名を持つならタグセクション
        } else {
            $top = (is_numeric($kk)) ? $vv[0] : $kk[0];     // 先頭の1文字
            $ret = (empty($top))? FALSE : (strpos(self::SectionCMD,$top) !== FALSE);   // タグ識別子がある
        }
        return $ret;
    }
//===============================================================================
//  配列要素を持つタグ要素の処理(テンプレート用)
    private function SectionLayout($divSection, $vars = []) {
        APPDEBUG::arraydump(0,["SEC-VARS" => $vars,"SECTION" => $divSection]);
        foreach($divSection as $key => $sec) {
            if($key === '+setvar') {
                $vv = $this->expandSectionVar($sec,$vars);
                $vars = $this->my_array_merge($vv,$vars);             // 環境変数にマージ、引数側を優先
            } else
                $this->SectionItemOutput($key,$sec,$vars);
        }
    }
//===============================================================================
//  配列要素を持つタグ要素の処理
    private function TagSectionItem($key, $attr, $sec,$vars) {
        $tag = $this->TagInfo($key);
        $tagname = $tag['tagname'];
        $htmltag = $tag['tag'];
        // 空要素を持つセクション要素は開始・終了タグをそのまま出力して終了する
        if(empty($sec)) {
            echo "<{$htmltag}></{$tagname}>\n";
            return;
        }
        $repCount = 0;
        $innerCount = 0;
        foreach($sec as $kk => $vv) {
            if($this->isTagSection($kk,$vv)) {
                $innerCount++;                  // サブセクションを数える
            } else if( is_array($vv) ) {        // タグセクションでなく配列要素を持つのはタグリピート
                $repCount++;                    // タグリピートを数える
            } else if( is_numeric($kk) ) {      // キー名が無いものはインナーテキスト
                $innerCount++;                  // インナーテキストを数える
            } else {                            // これまでの条件に一致しなければ属性
                $tt = $this->TagInfo($kk);
                if(empty($tt['attr'])) $attr .= " {$kk}=\"{$vv}\"";   // 要素全てを属性指定に変換
                else $innerCount++;
//                $attr .= " {$kk}=\"{$vv}\"";   // 要素全てを属性指定に変換
            }
        }
        // タグリピートが０、または、サブセクション・インナーテキストが１以上あるときは開始タグを出力
        if(!$repCount || $innerCount) echo "<{$htmltag}{$attr}>";
        // リピートタグ、タグセクション、インナーテキストの処理
        foreach($sec as $kk => $vv) {
            if($this->isTagSection($kk,$vv)) {                  // タグセクションならサブセクションを呼び出す
                $this->SectionItemOutput($kk,$vv,$vars);
            } else if( is_array($vv) ) {                        // タグセクションでなく配列要素を持つのはタグリピート
                $this->TagSectionItem($key,$attr,$vv,$vars);
            } else if( is_numeric($kk) ) {                      // スカラー要素で連想キー名が無いものはインナーテキスト
                echo "{$vv}";            // innerText として出力
            } else {
                $tt = $this->TagInfo($kk);
                echo "<$tt[tag]>{$vv}</$tt[tagname]>";    // ul/ol タグ
            }
        }
        // 開始タグを出力していれば、終了タグも出力する
    //    echo "REP::{$repCount}/IN::{$innerCount}\n";
        if(!$repCount || $innerCount)  echo "</{$tagname}>\n";
    }
//===============================================================================
//  属性のみの単独タグ要素の処理
    private function SingleTagItem($htmltag, $sec) {
        $attr = '';
        $doRep = FALSE;
        foreach($sec as $kk => $vv) {
            if(is_array($vv)) {             // リピートタグの処理、トップの属性を付加して出力する
                $this->SingleTagItem("{$htmltag}{$attr}",$vv);
                $doRep = TRUE;
            } else {
                $attr .= " {$kk}=\"{$vv}\"";   // 要素全てを属性指定に変換
            }
        }
        // リピートタグ出力していないときだけ
        if(!$doRep) echo "<{$htmltag}{$attr}>\n";
    }
//===============================================================================
// スカラー要素をピックアップして属性値を返す
    private function getAttribute($sec) {
        $attr = '';  // キー名のある要素かつ配列値でない要素のみ属性値指定として取出す
        foreach($sec as $kk => $vv) {
            if( !is_numeric($kk) && !is_array($vv) && empty($tt['attr'])) $attr .= " {$kk}=\"{$vv}\"";
        }
        return $attr;
    }
//===============================================================================
// セクションレイアウト出力
    private function SectionItemOutput($key, $sec,$vars) {
        $sec = $this->expandSectionVar($sec,$vars);
        $call = (is_numeric($key)) ? $sec : $key;
        $tag = $this->TagInfo($call);
        $tagname = $tag['tagname'];
        $htmltag = $tag['tag'];
        switch($tag['type']) {
        case '<':  // 直接出力
                echo "<{$tag['str']}\n";
                break;
        case '@':       // 別のテンプレート呼び出し、配列が指定されていれば環境変数とマージしておく
                $mergevars = (is_array($sec)) ? array_merge($vars, $sec) : $vars;
                $target = $tag['class'];
                if(isset($target) && array_key_exists($target,$this->inlineSection)) {
                    $this->SectionLayout($this->inlineSection[$target],$mergevars);
                } else $this->ViewTemplate($tagname,$mergevars);
                break;
        case '&':  // ヘルパーに指定メソッドが存在するかチェック
                if(method_exists($this->Helper,$tagname)) {
                    (is_numeric($key)) 
                        ? $this->Helper->$tagname()
                        : $this->Helper->$tagname($sec);
                } else if(method_exists('App',$tagname)) {
                    (is_numeric($key)) 
                        ? App::$tagname()
                        : App::$tagname($sec);
                } else {
                    die("Helper Method:{$tagname} not found.");
                }
                break;
        case '+':  // 外部CSS/Javascript呼び出し
                $close_tag = "";
                switch($tagname) {
                case 'include':            // 外部ファイルのインクルード'
                    App::WebInclude($sec);
                    break;
                case 'style':              // スタイル出力
                    $this->directOutput('<style type="text/css">', "</style>",$sec);
                    break;
                case 'echo':                // ダイレクト出力
                    $this->directOutput('', '',$sec);
                    break;
                case 'jquery':              // JQuery 出力
                    $this->directOutput("<script type='text/javascript'>\n$(function() {", "});\n</script>",$sec);
                    break;
                case 'script':              // javascript 出力
                    $this->directOutput("<script type='text/javascript'>\n", "</script>",$sec);
                    break;
                case 'ul':      // リスト要素
                case 'ol':      // リスト要素
                    $attr = $this->getAttribute($sec);  // キー名のある要素かつ配列値でない要素のみ属性値指定として取出す
                    echo "<{$htmltag}{$attr}>\n";    // ul/ol タグ
                    // リスト要素の出力
                    foreach($sec as $kk => $vv) {
                        // 配列要素はキー名の有無に関わらずリスト項目のセクションタグとして扱う
                        if( is_array($vv)) {     //  配列要素はセクション
                            $tt = (in_array($kk[0],['.','#'])) ? "li{$kk}" : 'li';
                            $this->SectionItemOutput($tt, $vv,$vars);
                        } else if( is_numeric($kk) ) { // キー名を持たないスカラー要素は単純リスト
                            echo "<li>{$vv}</li>\n";
                        } else {    // キー名を持つスカラー
                            echo "<li>\n";
                            $this->SectionItemOutput($kk, $vv,$vars);
                            echo "</li>\n";
                        }
                    }
                    echo "</{$tagname}>\n";
                    break;
                case 'dl':      // 定義リスト要素
                    $attr = $this->getAttribute($sec);  // キー名のある要素かつ配列値でない要素のみ属性値指定として取出す
                    echo "<{$htmltag}{$attr}>\n";    // dl タグ
                    foreach($sec as $dtkey => $dtsec) {
                        if( is_array($dtsec) ) {        //  連想配列要素だけを処理する
                            $dt_text = "";          // DTのインナーテキスト
                            $dt_attr = "";
                            foreach($dtsec as $kk => $vv) {
                                if(!is_array($vv)) {        // スカラー要素＝インナーテキスト or DT Attribute
                                    if(is_numeric($kk)) $dt_text .= $vv;
                                    else  $dt_attr .= " {$kk}=\"{$vv}\"";
                                }
                            }
                            if(!is_numeric($dtkey)) {
                                $dttag = $this->TagInfo($dtkey);    // DTのクラス属性
                                $$dt_attr .= $dttag[attr];       // タグ名がインナーテキストになる
                            }
                            echo "<dt{$dt_attr}>$dt_text</dt>\n";  // タグ名がインナーテキストになる
                            foreach($dtsec as $kk => $vv) {
                                if(is_array($vv)) {        // スカラー要素＝インナーテキスト or DT Attribute
                                    $dd_attr = (is_numeric($kk)) ? '' : $kk;
                                    $this->SectionItemOutput("dd{$dd_attr}", $vv,$vars);      // DDセクションは通常出力
                                }
                            }
                        } else if( is_numeric($kk) ) { // キー名を持たないスカラー要素は単純リスト
                            echo "<dt></dt><dd>{$vv}</dd>\n";
                        }
                    }
                    echo "</{$tagname}>\n";
                    break;
                case 'inline':
                    $this->inlineSection[$tag['class']] = $sec;
                    break;
                default: echo "tag '{$tagname}' not for feature.\n";
                }
                break;
        case '*':  // コメントタグ
                echo "\n<!-- {$tag['str']} -->\n";
                break;
        case '%':  // ハイパーリンク
                if($tagname === 'link') {
                    if(is_array($sec)) {
                        foreach($sec as $kk => $vv) $this->Helper->ALink($vv,$kk);
                    } else echo "{$tagname} bad argument.\n";
                } else if(is_scalar($sec)) {
                    $tagname = $tag['str'];
                    $this->expand_walk($tagname,'', $vars);     // 変数展開する
                    $this->Helper->ALink($sec,$tagname);
                } else echo "tag '{$tagname}' not for feature.\n";
                break;
        case '-':   // 単独の属性付きタグ出力
                if(is_array($sec)) {
                    $this->SingleTagItem($htmltag,$sec);          // リピートタグはこちらで処理
                }
                break;
        default:    // タグの出力 Tagname.class#id
                // 配列要素を持つときは追加属性・サブセクションの処理
                if(is_array($sec)) {
                    $this->TagSectionItem($key, '', $sec,$vars);
                } else { // スカラー要素は単独タグか
                    if(empty($tag['attr'])) {
                        echo "<{$htmltag}>\n";           // キー名を持たないスカラー要素
                    } else {
                        echo "<{$htmltag}>{$sec}</{$tagname}>";    // ul/ol タグ
                    }
                }
        }
    }
*/
}
