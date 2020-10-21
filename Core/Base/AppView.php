<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  AppView:    ビュー生成用のテンプレート処理、
 *              PHPテンプレート(*.tpl)とセクションテンプレート(*.ptl)を読み込んでHTMLを出力する
 *              HTML出力のため AppHelper を所有する
 */
class AppView extends AppObject {
    protected $Layout;        // デフォルトのレイアウト
    private $doTrailer = FALSE;
    const Extensions = array("tpl","php","inc","html");  // テンプレート拡張子
    const SectionCMD = '<@&+*%-.#{[';  // 単独セクション処理コマンド文字
    private $rep_array;
    private $env_vars;              // テンプレート内のグローバル変数
    private $inlineSection;        // インラインのセクション
    const FunctionList = array(
//        '<'   => 'sec_html',
        '@'    => 'sec_import',
        '&'    => 'sec_helper',
        '+'    => [
            'include'   => 'cmd_include',
            'style'     => 'cmd_style',
            'img'       => 'cmd_image',
            'echo'      => 'cmd_echo',
            'jquery'    => 'cmd_jquery',
            'script'    => 'cmd_script',
            'ul'        => 'cmd_list',
            'ol'        => 'cmd_list',
            'dl'        => 'cmd_dl',
            'select'    => 'cmd_select',
            'inline'    => 'cmd_inline',
            'markdown'  => 'cmd_markdown',
            'recordset' => 'cmd_recordset',
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
public function SetLayout($layoutfile) {
    $tmplate = $this->get_TemplateName($layoutfile);   // ビューフォルダのテンプレート
    if(!file_exists($tmplate)) {                // 存在しないなら共通のテンプレートを探す
        $layoutfile = 'Layout';
    }
    $this->Layout = $layoutfile;
}
//==============================================================================
// レイアウト出力
//==============================================================================
public function PutLayout($layout = NULL) {
    if($layout === NULL) $layout = $this->Layout;
    debug_log(1, "\$Layout = {$layout}");
    $this->ViewTemplate($layout);
    $this->doTrailer = TRUE;
}
//==============================================================================
// ページ出力完了
public function __TerminateView() {
    if($this->doTrailer) {
        $tmplate = $this->get_TemplateName('Trailer');   // ビューフォルダのテンプレート
        $Helper = $this->Helper;
        if($tmplate !== NULL) require_once ($tmplate);
        // リクエストURLと処理メソッドが違っていたときはRelocateフラグが立つ
        $url = App::Get_RelocateURL();
        if(isset($url)) {
            debug_log(1,"RedirectURL: {$url}\n");
            echo "<script type='text/javascript'>\n$(function() { history.replaceState(null, null, \"{$url}\"); });\n</script>\n";
        }
        if(DEBUGGER) {
            $this->ViewTemplate('debugbar');
        }
    }
}
//==============================================================================
//　レイアウトテンプレート処理
public function ViewTemplate($name,$vars = []) {
    $tmplate = $this->get_TemplateName($name);   // ビューフォルダのテンプレート
    if(isset($tmplate)) {
        $ext = substr($tmplate,strrpos($tmplate,'.') + 1);    // 拡張子を確認
        $ix = array_search($ext, self::Extensions);
        switch($ix) {       //   [ .tpl, .php, .inc, .twg ]
        case 0:     // '.tpl'   div Section
            $parser = new SectionParser($tmplate);
            $divSection = $parser->getSectionDef();
            $this->inlineSection = [];         // インラインセクション定義をクリア
            debug_log(1,["SECTION @ {$name}" => $divSection,"SEC-VARS" => $vars]);
            $this->sectionAnalyze($divSection,$vars);
            break;
        case 1:     // 'php'     // PHP Template
            extract($vars);             // 変数展開
            $Helper = $this->Helper;    // ヘルパークラス
            $MyModel = $this->Model;    // モデルクラス
            $View = $this;              // ビュークラス自身
            $RecData = $this->Model->RecData;    // レコードデータ
            $Records = $this->Model->Records;    // レコードリスト
            $Header = $this->Model->HeaderSchema;    // スキーマヘッダ
            $_ = function($id) { return $this->_($id); };   // shortcut LANG-ID Convert
            require_once ($tmplate);
            break;
        case 2:     // 'inc':     // HTML template
            $content = file_get_contents($tmplate);
            // システム変数＋URIパラメータへの置換処理
            echo $this->expand_Strings($content, $vars);
            break;
        case 3:     // 'html':     // HTML
            echo file_get_contents($tmplate);
            break;
        }
    } else error_response('page-404.php',App::$AppName, [$this->ModuleName, $name,'']);     // 404 ERROR PAGE Response
}
//==============================================================================
// テンプレートファイルがビュークラスフォルダに存在しなければ共通のテンプレートを探す
    private function get_TemplateName($name) {
        $temlatelist = array(
            App::Get_AppPath("modules/{$this->ModuleName}/View/{$name}"),   // モジュールのビューレイアウト
            App::Get_AppPath("View/{$name}"),                               // App共通のレイアウトテンプレートを探す
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
        return NULL;
    }
//==============================================================================
//  変数を置換する
    private function expand_Walk(&$val, $key, $vars) {
        if($val[0] === '$') {           // 先頭の一文字が変数文字
            $var = mb_substr($val,1);
            $var = trim($var,'{}');                 // triming of delimitter { }
            switch($var[0]) {
            case '@': $var = mb_substr($var,1);     // refer to RECORD DATA
                list($val,$alt) = (mb_strpos($var,':') !== FALSE) ? explode(':',$var) : [$var,''];
                $is_row = ($var[0] === '@');            // is RAW DATA
                if($is_row) $var = mb_substr($var,1);   // clip first @ char
                $val = $this->Model->RecData[$var];     // get FIELD DATA
                if(empty($val) && !empty($alt)) {       // FILED is EMPTY and ALTERNATIVE exist
                    $val = ($alt[0] === "'") ? trim($alt,"'")       // is CONSTANT
                                             : $this->Model->RecData[$alt]; // alternate FIELD
                }
                // not RAW will be HTML convert
                if($is_row === FALSE) $val = str_replace("\n",'',text_to_html($val));
                break;
            case '#': $var = mb_substr($var,1);     // 言語ファイルの参照
                $allow = ($var[0] === '#');         // 配列を許可する
                if($allow) $var = mb_substr($var,1);
                $val = $this->_($var,$allow);       // 言語ファイルの定義配列から文字列または配列を取り出す
                break;
            case '%': if(substr($var,-1) === '%') {     // 末尾文字を確かめる
                    $var = trim($var,'%');              // URLの引数番号
                    $val = App::$Params[$var];          // Params[] プロパティから取得
                }
                break;
            case '$': if(substr($var,-1) === '$') {     // 末尾文字を確かめる
                    $var = trim($var,'$');              // システム変数値
                    $val = App::$SysVAR[$var];          // SysVAR[] プロパティから取得
                }
                break;
            case "'": if(substr($var,-1) === "'") {     // 末尾文字を確かめる
                    $var = trim($var,"'");              // セッション変数
                    $val = MySession::get_envIDs($var);// EnvData[] プロパティから取得
                }
                break;
            default:
                if(isset($vars[$var])) {            // ローカル変数に存在
                    $val = $vars[$var];
                } else if(isset($this->env_vars[$var])) {   // グローバル変数に存在
                    $val = $this->env_vars[$var];
                } else if(isset($this->$var)) {     // プロパティ変数に存在
                    $val = $this->$var;
                }
            }
        }
    }
//==============================================================================
//  文字列の変数置換を行う
// $[@#]varname | ${[@#]varname} | {$SysVar$} | {%Params%}
    private function expand_Strings($str,$vars) {
        $p = '/\${[^}\s]+?}|\${[#%\'\$@][^}\s]+?}/';          // 変数リストの配列を取得
        preg_match_all($p, $str, $m);
        $varList = $m[0];
        if(empty($varList)) return $str;        // 変数が使われて無ければ置換不要
        $values = $varList = array_unique($varList);
        array_walk($values, array($this, 'expand_Walk'), $vars);
        debug_log(FALSE,[ "EXPAND" => [
            "STR" => $str,
            "変換" => $varList,
            "置換" => $values,
            ]]);
        // 配列が返ることもある
        $exvar = (is_array($values[0])) ? $values[0]:str_replace($varList,$values,$str);    // 置換配列を使って一気に置換
        return $exvar;
    }
//==============================================================================
//  セクション要素内の変数を展開する
    private function expand_SectionVar($vv,$vars) {
        if(!is_array($vv)) {        // スカラー要素の場合
            return $this->expand_Strings($vv,$vars);   // 変数置換を行う
        }
        foreach($vv as $kk => $nm) {
            if(!is_array($nm)) {        // 配列の子要素は後で展開する
                $exvar = $this->expand_Strings($nm,$vars);   // 変数置換を行う
                if(is_array($exvar)) {
                    $vv[$kk] =$exvar;     // 配列に置換する
                } else if(isset($exvar)) {
                     $vv[$kk] = $exvar;     // 変数値は引数 vars[] 配列内に変数名をキー名として格納されている
                }  else {
                    unset($vv[$kk]);            // 見つからなければ変数を削除
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
    private function my_array_Merge($arr1,$arr2) {
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
//==============================================================================
// セクション配列を解析して処理関数へディスパッチする
    private function sectionAnalyze($divSection,$vars) {
        foreach($divSection as $key => $sec) {
            if($key === '+setvar') {        // グローバル変数に登録
                $vv = $this->expand_SectionVar($sec,$vars);
                $this->env_vars = $this->my_array_Merge($vv,$this->env_vars);
            } else if($key[0] === '$' && $key[1] !== '{') {     // ローカル変数に登録
                $nm = mb_substr($key,1);      // 先頭文字を削除
                $vars[$nm] = $this->expand_SectionVar($sec,$vars);
            } else
                $this->sectionDispath($key,$sec,$vars);
        }
    }
//==============================================================================
// key 文字列を元に処理関数へディスパッチする
// key => sec (vars)
    private function sectionDispath($key,$sec,$vars) {
//        if($key !== '+recordset') $sec = $this->expand_SectionVar($sec,$vars);
        $num_key = is_numeric($key);
        if($num_key) {  // 連想キーでなければ値を解析する
            if(is_array($sec)) {
                $this->sectionAnalyze($sec,$vars);
                return;
            }
            $key = $sec; $sec = [];
        } else { // キー名重複回避用の文字を削除
            $key = tag_body_name($key);
        }
        $top_char = $key[0];
        if(array_key_exists($top_char,self::FunctionList)) {
            $kkey = mb_substr($key,1);      // 先頭文字を削除
            if($top_char === $kkey[0]) {    // コマンド文字が2個続いたら文字列出力
                echo $kkey; return;
            }
            $func = self::FunctionList[$top_char];
            // + コマンドには属性が付いている
            list($tag,$text,$attrs,$subsec) = $this->tag_attr_Section($kkey,$sec,$vars);
            if(is_array($func)) {       // サブコマンドテーブル
                $cmd = $func[$tag];
                if(array_key_exists($tag,$func) && (method_exists($this, $cmd))) {
                    $this->$cmd($tag,$attrs,$subsec,$sec,$vars);
                } else echo "***NOT FOUND({$cmd}): {$cmd}({$tag},\$attrs,\$sec,\$vars)\n";
            } else if(method_exists($this, $func)) {
                $sec = $this->expand_SectionVar($sec,$vars);
                $this->$func($kkey,$sec,$vars);
            } else echo "CALL: {$func}({$kkey},{$sec},vars)\n";
        } else {
            $sec = $this->expand_SectionVar($sec,$vars);
            list($tag,$text,$attrs,$subsec) = $this->tag_attr_Section($key,$sec,$vars);
            if($top_char === '<') {
                echo "{$tag}\n";
            } else {
                $attr = $this->gen_Attrs($attrs);
                if(is_array($sec)) {
                    echo "<{$tag}{$attr}>{$text}";
                    $this->sectionAnalyze($subsec,$vars);
                    echo "</{$tag}>\n";
                } else {
                    echo "<{$tag}{$attr}>{$text}</{$tag}>\n";
                }
            }
        }
    }
    //==========================================================================
    // 先頭の < 文字が削除されているので補填する
    private function gen_Attrs($attrs) {
        $attr = "";
        if($attrs !== array()) {
            foreach($attrs as $name => $val) {
                $attr = "{$attr} {$name}=\"{$val}\"";
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
        $is_inline = ($key[0] === '.');
        if($is_inline) $key = substr($key,1);
        // 引数を変数リストに追加
        $mergevars = (is_array($sec)) ? array_merge($vars, $sec) : $vars;
        if($is_inline && array_key_exists($key,$this->inlineSection)) {
            $this->sectionAnalyze($this->inlineSection[$key],$mergevars);
        } else {
            $this->ViewTemplate($key,$mergevars);
        }
    }
    //==========================================================================
    //  属性のみの単独タグ要素の処理
    private function sec_singletag($key,$sec,$vars) {
        // $key と $sec をタグと属性に分解する
        list($tag,$text,$attrs,$subsec) = $this->tag_attr_Section($key,$sec,$vars);
        $attr = $this->gen_Attrs($attrs);
        if(!empty($subsec)) {  // サブセクションがあればリピート
            foreach($subsec as $kk => $vv) {
                list($tt,$txt,$at,$sub) = $this->tag_attr_Section($kk,$vv,$vars);
                $atr = $this->gen_Attrs($at);
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
            $this->expand_Walk($key,'', $vars);     // 変数展開する
            $this->Helper->ALink($sec,$key);
        } else echo "tag '{$tagname}' not for feature.\n";
    }
    //==========================================================================
    // Helper関数の呼出
    private function sec_helper($key,$sec,$vars) {
        // ヘルパーに指定メソッドが存在するかチェック
        if(method_exists($this->Helper,$key)) {
            (is_numeric($key)) 
                ? $this->Helper->$key(is_scalar($sec)?$sec:'')
                : $this->Helper->$key($sec);
        } else if(method_exists('App',$key)) {
            (is_numeric($key)) 
                ? App::$key()
                : App::$key($sec);
        } else {
            echo "Helper Method:'{$key}' not found. Please Create this method.\n";
        }
    }
    //--------------------------------------------------------------------------
    // cmd_xxxx の関数は引数が少し異なる
    //  cmd_xxx(タグ名,属性リスト,セクション,環境変数)
    //--------------------------------------------------------------------------
    //  外部ファイルのインクルード
    private function cmd_include($tag,$attrs,$subsec,$sec,$vars) {
        $sec = $this->expand_SectionVar($sec,$vars);
        App::WebInclude($sec);
    }
    //--------------------------------------------------------------------------
    //  JQueryスクリプトの出力
    private function cmd_jquery($tag,$attrs,$subsec,$sec,$vars) {
        $sec = $this->expand_SectionVar($sec,$vars);
        $this->directOutput("<script type='text/javascript'>\n$(function() {", "});\n</script>",$sec);
    }
    //--------------------------------------------------------------------------
    //  javascriptの出力
    private function cmd_script($tag,$attrs,$subsec,$sec,$vars) {
        $sec = $this->expand_SectionVar($sec,$vars);
        $this->directOutput("<script type='text/javascript'>", "</script>",$sec);
    }
    //--------------------------------------------------------------------------
    //  スタイルシートの出力
    private function cmd_style($tag,$attrs,$subsec,$sec,$vars) {
        $sec = $this->expand_SectionVar($sec,$vars);
        $this->directOutput('<style type="text/css">', "</style>",$sec);
    }
    //--------------------------------------------------------------------------
    //  イメージタグの出力
    private function cmd_image($tag,$attrs,$subsec,$sec,$vars) {
        $sec = $this->expand_SectionVar($sec,$vars);
        if(is_array($sec)) { // 連想キーが無いスカラー値のみ抽出
            foreach($sec as $key => $val) {
                if(is_numeric($key) && is_scalar($val)) $src = $val;
            }
        } else $src = $sec;
        $attr = $this->gen_Attrs($attrs);
        $src = make_hyperlink($src,$this->ModuleName);
        echo "<img src='{$src}'{$attr} />";
    }
    //--------------------------------------------------------------------------
    //  単純エコー出力
    private function cmd_echo($tag,$attrs,$subsec,$sec,$vars) {
        $sec = $this->expand_SectionVar($sec,$vars);
        $this->directOutput('', '',$sec);
    }
    //--------------------------------------------------------------------------
    //  インラインセクションの登録
    private function cmd_inline($tag,$attrs,$subsec,$sec,$vars) {
        $sec = $this->expand_SectionVar($sec,$vars);
        $name = $attrs['class'];
        $this->inlineSection[$name] = $sec;
    }
    //--------------------------------------------------------------------------
    //  セクション配列をマークダウン変換
    // 連想配列ならキー名をクラス名として扱う
    private function cmd_markdown($tag,$attrs,$subsec,$sec,$vars) {
        $sec = $this->expand_SectionVar($sec,$vars);
        $atext = array_to_text($sec,"\n",FALSE);   // array to Text convert
        $key = is_array($sec) ? array_key_first($sec) : 0;
        $mtext =(is_numeric($key))
                ? pseudo_markdown( $atext )
                : pseudo_markdown( $atext,$key);
    debug_log(FALSE,[ 
        "SEC" => $sec,
        "KEY" => $key,
        "STRING" => $atext,
        "MARKDOWN" => $mtext,
    ]);
        echo $mtext;
    }
    //--------------------------------------------------------------------------
    //  レコードリストを元にループする
    // 特別に $sec は変数置換しないで渡される
    private function cmd_recordset($tag,$attrs,$subsec,$sec,$vars) {
      foreach($this->Model->Records as $records) {
            $this->Model->RecData = $records;    // レコードデータ
            debug_log(FALSE,[ 
                "data" => $this->Model->RecData,
                'sec' => $sec,
                'var' => $vars,
            ]);
            $this->sectionAnalyze($sec,$vars);
        }
    }
    //--------------------------------------------------------------------------
    //  ul/ol リストの出力
    // +ul => [
    //   { li.class#id => } [   ]       
    // ]
    private function cmd_list($tag,$attrs,$subsec,$sec,$vars) {
        $subsec = $this->expand_SectionVar($subsec,$vars);
        $attr = $this->gen_Attrs($attrs);
        echo "<{$tag}{$attr}>\n";
        // リスト要素の出力
        foreach($subsec as $kk => $vv) {
            // $key と $sec をタグと属性に分解する
            list($s_tag,$s_text,$s_attr,$s_sec) = $this->tag_attr_Section($kk,$vv,$vars);
            $attr = $this->gen_Attrs($s_attr);
            $s_text = $this->expand_Strings($s_text,$vars);   // 変数置換を行う
            if(!empty($s_sec)) {  // サブセクションがあればセクション処理
                echo "<li{$attr}>{$s_text}\n";
                $this->sectionAnalyze($s_sec,$vars);
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
    // ]
    private function cmd_dl($tag,$attrs,$subsec,$sec,$vars) {
        $subsec = $this->expand_SectionVar($subsec,$vars);
        $attr = $this->gen_Attrs($attrs);
        echo "<{$tag}{$attr}>\n";
        // DTのリスト要素の出力
        foreach($subsec as $kk => $vv) {
            // $key と $sec をタグと属性に分解する
            list($dt_tag,$dt_text,$dt_attrs,$dd_sec) = $this->tag_attr_Section($kk,$vv,$vars);
            $attr = $this->gen_Attrs($dt_attrs);
            $dt_text = $this->expand_Strings($dt_text,$vars);   // 変数置換を行う
            echo "<dt{$attr}>{$dt_text}</dt>\n";
            if(!empty($dd_sec)) {  // DDセクションがあれば処理
                foreach($dd_sec as $dd_key => $dd_sub) {
                    list($dd_tag,$dd_text,$dd_attrs,$dd_child) = $this->tag_attr_Section($dd_key,$dd_sub,$vars);
                    $dd_attr = $this->gen_Attrs($dd_attrs);
                    echo "<dd{$dd_attr}>{$dd_text}\n";
                    $this->sectionAnalyze($dd_child,$vars);
                    echo "</dd>\n";
                }
            } else {
                echo "<dd></dd>\n";
            }
        }
        echo "</{$tag}>\n";
    } 
    //--------------------------------------------------------------------------
    //  select リストの出力
    // +select => [
    //    selected_key = > [
    //      option_text => value
    //      ...
    //    ]
    // ]
    private function cmd_select($tag,$attrs,$subsec,$sec,$vars) {
        $subsec = $this->expand_SectionVar($subsec,$vars);
        if(is_array($subsec)) {
            $attr = $this->gen_Attrs($attrs);
            echo "<{$tag}{$attr}>\n";
            list($opt_key, $opt_val) = array_first_item($subsec);    // 最初の要素を処理
            $sel_item = (is_numeric($opt_key)) ? '' : $this->expand_Strings($opt_key,$vars);
            if(is_array($opt_val)) {
                foreach($subsec[$opt_key] as $opt => $val) {
                    $sel = ($opt === $sel_item) ? ' selected':'';
                    echo "<OPTION value='{$val}'{$sel}>{$opt}</OPTION>\n";
                }
            } else echo "<OPTION value='{$opt_val}'>{$opt_val}</OPTION>\n";
            echo "</{$tag}>\n";
        }
    }
    //==========================================================================
    // タグ文字列の分解
    private function tag_Separate($tag) {
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
    private function is_section_tag($tag) {
        if(empty($tag)) return FALSE;
        return (strpos(self::SectionCMD,$tag[0]) !== FALSE);
    }
    //==========================================================================
    // タグ文字列の分解
    private function tag_attr_Section($tag,$sec,$vars) {
        $innerText = '';
        $secList = [];
        list($tag,$attrList) = $this->tag_Separate($tag);
        // $sec の中から innerText と attr を取り出す
        if(is_array($sec)) {
            foreach($sec as $key => $val) {
                if(is_numeric($key)) {  // 連想キーが無い場合
                    // 値が配列かセクション用コマンドならセクションデータ扱い
                    if(is_array($val)||$this->is_section_tag($val)) {
                        $secList[] = $val;    // 配列かコマンド名ならセクション
                    } else {
                        $innerText .= $val;   // スカラー値ならインナーテキスト
                    }
                } else {
                    list($vv,$attrs) = $this->tag_Separate($key);   // タグ分解
                    // $val が配列かセクションコマンド、$key が属性付きならセクション扱い
                    if(is_array($val) || !empty($attrs) || $this->is_section_tag($key)) $secList[$key] = $val;
                    else $attrList[$key] = $val;    // それ以外は属性指定
                }
            }
        } else {
            $innerText .= $sec;        // スカラーならテキスト
        }
        $innerText = $this->expand_Strings($innerText,$vars);   // 変数置換を行う
        return array($tag,$innerText,$attrList,$secList);
    }

}
