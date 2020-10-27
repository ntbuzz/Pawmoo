<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  AppView:    View Template processing Engine.
 *              Handle of *.php, *.inc. *.tpl
 *              Having Chile Class AppHelper for HTML output
 */
class AppView extends AppObject {
    protected $Layout;
    private $doTrailer = FALSE;
    const Extensions = array("tpl","php","inc","html");
    const SectionCMD = '<@&+*%-.#{[';
    private $rep_array;
    private $env_vars;             // GLOBAL Variable in TEMPLATE
    private $inlineSection;
    const FunctionList = array(
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
            'radio'     => 'cmd_radio',
            'checkbox'  => 'cmd_checkbox',
            'table'     => 'cmd_table',
            'inline'    => 'cmd_inline',
            'markdown'  => 'cmd_markdown',
            'recordset' => 'cmd_recordset',
        ],
        '*'    => 'sec_comment',
        '%'    => 'sec_link',
        '-'    => 'sec_singletag',
    );
    //==========================================================================
    // Constructor: Import Model Class by Owner Class, Create Helper
    //==========================================================================
    function __construct($owner) {
        parent::__construct($owner);
        $this->Model = $owner->Model;       // import Owner Property
        $helper = "{$this->ModuleName}Helper";
        if(! class_exists($helper)) $helper = 'AppHelper';
        $this->Helper = new $helper($this);
        $this->Helper->MyModel = $this->Model;
        $this->__InitClass();
    }
    //==========================================================================
    // Class Initialized
    protected function __InitClass() {
        $this->Layout = 'Layout';
        $this->rep_array = array_merge(App::$SysVAR, App::$Params);   // Import SYSTEM VARIABLE
        $this->env_vars = [];
        parent::__InitClass();                    // CALL Parent Method
    }
//==============================================================================
// Output Default LAYOUT name.
//==============================================================================
public function SetLayout($layoutfile) {
    $tmplate = $this->get_TemplateName($layoutfile);
    if(!file_exists($tmplate)) {
        $layoutfile = 'Layout';
    }
    $this->Layout = $layoutfile;
}
//==============================================================================
// Output layout
//==============================================================================
public function PutLayout($layout = NULL) {
    if($layout === NULL) $layout = $this->Layout;
    debug_log(1, "\$Layout = {$layout}");
    $this->ViewTemplate($layout);
    $this->doTrailer = TRUE;
}
//==============================================================================
// Terminate Response,
public function __TerminateView() {
    if($this->doTrailer) {
        $tmplate = $this->get_TemplateName('Trailer');
        $Helper = $this->Helper;
        if($tmplate !== NULL) require_once ($tmplate);
        // Do Replacement ADDRESS-BAR in Browser
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
//　Template file OUTPUT
public function ViewTemplate($name,$vars = []) {
    $tmplate = $this->get_TemplateName($name);
    if(isset($tmplate)) {
        $ext = substr($tmplate,strrpos($tmplate,'.') + 1);
        $ix = array_search($ext, self::Extensions);
        switch($ix) {   //   [ .tpl, .php, .inc, .twg ]
        case 0:         // '.tpl'   div Section
            $parser = new SectionParser($tmplate);
            $divSection = $parser->getSectionDef();
            $this->inlineSection = [];         // Clear Inline-Section in this TEMPLATE
            debug_log(1,["SECTION @ {$name}" => $divSection,"SEC-VARS" => $vars]);
            $this->sectionAnalyze($divSection,$vars);
            break;
        case 1:         // 'php'     // PHP Template
            extract($vars);
            $Helper = $this->Helper;
            $MyModel = $this->Model;
            $View = $this;
            $RecData = $this->Model->RecData;    // One-Record Data
            $Records = $this->Model->Records;    // Record Data-List, column data is used HeaderSchema
            $Header = $this->Model->HeaderSchema;  // Filterling for Display Header
            $_ = function($id) { return $this->_($id); };   // shortcut LANG-ID Convert
            require_once ($tmplate);
            break;
        case 2:         // 'inc':     // HTML template
            $content = file_get_contents($tmplate);
            echo $this->expand_Strings($content, $vars);
            break;
        case 3:         // 'html':     // HTML
            echo file_get_contents($tmplate);
            break;
        }
    } else  // 404 ERROR PAGE Response
        error_response('page-404.php',App::$AppName,[ App::Get_SysRoot(),App::Get_AppRoot() ], [$this->ModuleName, $name,'']);
}
//==============================================================================
// search Template file in folder
    private function get_TemplateName($name) {
        $temlatelist = array(
            App::Get_AppPath("modules/{$this->ModuleName}/View/{$name}"),
            App::Get_AppPath("View/{$name}"),
            "Core/Template/View/{$name}"
        );
        foreach($temlatelist as $file) {
            foreach(self::Extensions as $ee) {
                $form = "{$file}.{$ee}";
                if(file_exists($form)) {    // found it!
                    return $form;
                }
            }
        }
        return NULL;
    }
//==============================================================================
//  EXPAND variable
    private function expand_Walk(&$val, $key, $vars) {
        if($val[0] === '$') {           // top char is variable mark
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
            case '#': $var = mb_substr($var,1);     // Language refer
                $allow = ($var[0] === '#');         // allow array
                if($allow) $var = mb_substr($var,1);
                $val = $this->_($var,$allow);       // get Language define
                break;
            case '%': if(substr($var,-1) === '%') {     // is parameter number
                    $var = trim($var,'%');
                    $val = App::$Params[$var];          // get value from Params[] property
                }
                break;
            case '$': if(substr($var,-1) === '$') {
                    $var = trim($var,'$');
                    $val = App::$SysVAR[$var];          // SysVAR[] property
                }
                break;
            case ':': $var = mb_substr($var,1);     // ModelClass Property
                    if(isset($this->Model->$var)) { // exist Property?
                        $val = $this->Model->$var;
                    }
                    break;
            case "'": if(substr($var,-1) === "'") {     // check end-char
                    $var = trim($var,"'");
                    $val = MySession::get_envIDs($var);// get SESSION ENV-VAR
                }
                break;
            default:
                if(isset($vars[$var])) {            // is LOCAL VAR-SET?
                    $val = $vars[$var];
                } else if(isset($this->env_vars[$var])) {   // is TEMPLATE GLOBAL?
                    $val = $this->env_vars[$var];
                } else if(isset($this->$var)) {     // is Class-Property?
                    $val = $this->$var;
                }
            }
        }
    }
//==============================================================================
//  variable format convert
// $[@#]varname | ${[@#]varname} | {$SysVar$} | {%Params%}
    private function expand_Strings($str,$vars) {
        if(empty($str) || is_numeric($str)) return $str;
        $p = '/\${[^}\s]+?}|\${[#%\'\$@][^}\s]+?}/';       // PARSE variable format
        preg_match_all($p, $str, $m);
        $varList = $m[0];
        if(empty($varList)) return $str;        // not use variable.
        $values = $varList = array_unique($varList);
        array_walk($values, array($this, 'expand_Walk'), $vars);
        debug_log(FALSE,[ "EXPAND" => [
            "STR" => $str,
            "VAR" => $varList,
            "REPLACE" => $values,
            ]]);
        $exvar = (is_array($values[0])) ? $values[0]:str_replace($varList,$values,$str);
        return $exvar;
    }
//==============================================================================
//  セクション要素内の変数を展開する
    private function expand_SectionVar($vv,$vars) {
        if(!is_array($vv)) {        // スカラー要素の場合
            return $this->expand_Strings($vv,$vars);   // 変数置換を行う
        }
        $new_vv = [];
        foreach($vv as $kk => $nm) {
            $new_kk = $this->expand_Strings($kk,$vars);
            if(!is_array($nm)) {        // 配列の子要素は後で展開する
                $nm = $this->expand_Strings($nm,$vars);   // 変数置換を行う
            }
            $new_vv[$new_kk] = $nm;
        }
        return $new_vv;
    }
//==============================================================================
// TAG section direct OUTPUT
    private function directOutput($beg_tag, $end_tag,$sec) {
        echo "{$beg_tag}\n";
        if(is_array($sec)) {
            foreach($sec as $vv) echo "{$vv}\n";
        } else echo "{$sec}\n";
        echo "{$end_tag}";
    }
//==============================================================================
// like array_merge()
    private function my_array_Merge($arr1,$arr2) {
        foreach($arr2 as $key => $val) {
            if($key[0] === '+') {
                $var = substr($key,1);                            // except '+' charactor
                if(isset($arr1[$var])) {
                    $arr1[$var] = array_merge($val,$arr1[$var]);
                } else {
                    $arr1[$var] = $val . $arr1[$var];
                }
            } else {
                $arr1[$key] = $val;
            }
        }
        return $arr1;
    }
//******************************************************************************
// NEW processing Template-Engine
//==============================================================================
// Replace IF-SELECTOR
    private function array_if_selector($arr,$vars) {
        if(is_scalar($arr)) return $arr;
        // analyze IF-SELECTOR and EXPAND KEY
        $if_selector = function($sec,$key) use(&$vars) {
                if(substr($key,0,1)==='?') {
                    $cmp_val = str_replace(["\n","\r"],'',$this->expand_Strings(mb_substr($key,1),$vars));
                    foreach($sec as $check => $value) {
                        if($check === '') $result = empty($cmp_val);            // is_empty ?
                        else if($check === '*') $result = !empty($cmp_val);     // is_notempty ?
                        else $result = ($cmp_val === $check);                   // match if key
                        if($result) return $value;
                    }
                    return [];
                }
                return [$key => $sec];
        };
        $wd = [];       // re-build array
        foreach($arr as $key => $val) {
            $ret = $if_selector($val,$key);
            if(is_array($ret)) {
                foreach($ret as $kk => $vv) {
                    if(isset($wd[$kk])) {
                        if(is_numeric($kk)) $wd[] = $vv;
                        else {
                            for($dd=0;isset($wd[$ks="{$kk}:{$dup}"]);++$dd) ;
                            $wd[$ks] = $vv;
                        }
                    } else $wd[$kk] = $vv;
                }
            } else $wd[$key] = $ret;
        }
        return $wd;
    }
//==============================================================================
// Analyzed Section, and Dispatch Command method
    private function sectionAnalyze($divSection,$vars) {
        foreach($divSection as $key => $sec) {
            // analyze IF-SELECTOR and EXPAND KEY
            $vv = $this->array_if_selector($sec, $vars);
            if(is_array($vv)) debug_log(9,[ "SEC" => $vv]);
            if($key === '+setvar') {        // set template GLOBAL VARIABLE
                $this->env_vars = $this->my_array_Merge($vv,$this->env_vars);
            } else if(strlen($key) > 2 && $key[0] === '$' && $key[1] !== '{') {  // set Local Variable
                $nm = mb_substr($key,1);      // 先頭文字を削除
                $vars[$nm] = $this->expand_SectionVar($vv,$vars);
            } else
                $this->sectionDispath($key,$vv,$vars);
        }
    }
//==============================================================================
// key command analyze and extract ATTRIBUTE, SUB-SECTION, INNER-TEXT for Command
// key => sec (vars)
    private function sectionDispath($key,$sec,$vars) {
        $num_key = is_numeric($key);
        if($num_key) {  // numeric element will recursive process
            if(is_array($sec)) {
                $this->sectionAnalyze($sec,$vars);
                return;
            }
            $key = $sec; $sec = [];
        } else { // delete duplicate to avoid key-name
            $key = tag_body_name($key);
        }
        $top_char = mb_substr($key,0,1);
        if(array_key_exists($top_char,self::FunctionList)) {
            $kkey = mb_substr($key,1);
            if($top_char === $kkey[0]) {    // dual command charctor will output 1 charactor
                echo $kkey; return;
            }
            $func = self::FunctionList[$top_char];
            list($tag,$text,$attrs,$subsec) = $this->tag_attr_Section($kkey,$sec,$vars);
            if(is_array($func)) {
                $cmd = $func[$tag];
                if(array_key_exists($tag,$func) && (method_exists($this, $cmd))) {
                    $this->$cmd($tag,$attrs,$subsec,$sec,$vars,$text);
                } else echo "***NOT FOUND({$cmd}): {$cmd}({$tag},\$attrs,\$sec,\$vars)\n";
            } else if(method_exists($this, $func)) {
                $this->$func($kkey,$sec,$vars);
            } else echo "CALL: {$func}({$kkey},{$sec},vars)\n";
        } else {
            list($tag,$text,$attrs,$subsec) = $this->tag_attr_Section($key,$sec,$vars);
            if($top_char === '<') {
                echo "{$tag}\n";
            } else {
                $attr = $this->gen_Attrs($attrs,$vars);
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
    // *************************************************************************
    // コマンド関数の引数
    //  $key    expand済
    //  $sec    スカラー：expand済
    //          配列: 直下のキー、値がexpand済、値が配列なら未展開
    //==========================================================================
    // ATTR属性のリスト,タグの直下にあるのでexpand済のものだけが渡ってくる
    private function gen_Attrs($attrs,$vars) {
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
        $attr = $this->gen_Attrs($attrs,$vars);
        if(!empty($subsec)) {  // サブセクションがあればリピート
            foreach($subsec as $kk => $vv) {
                list($tt,$txt,$at,$sub) = $this->tag_attr_Section($kk,$vv,$vars);
                $atr = $this->gen_Attrs($at,$vars);
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
        App::WebInclude($sec);
    }
    //--------------------------------------------------------------------------
    //  JQueryスクリプトの出力
    private function cmd_jquery($tag,$attrs,$subsec,$sec,$vars) {
        $this->directOutput("<script type='text/javascript'>\n$(function() {", "});\n</script>",$sec);
    }
    //--------------------------------------------------------------------------
    //  javascriptの出力
    private function cmd_script($tag,$attrs,$subsec,$sec,$vars) {
        $this->directOutput("<script type='text/javascript'>", "</script>",$sec);
    }
    //--------------------------------------------------------------------------
    //  スタイルシートの出力
    private function cmd_style($tag,$attrs,$subsec,$sec,$vars) {
        $this->directOutput('<style type="text/css">', "</style>",$sec);
    }
    //--------------------------------------------------------------------------
    //  イメージタグの出力
    private function cmd_image($tag,$attrs,$subsec,$sec,$vars) {
        if(is_array($sec)) { // 連想キーが無いスカラー値のみ抽出
            foreach($sec as $key => $val) {
                if(is_numeric($key) && is_scalar($val)) $src = $val;
            }
        } else $src = $sec;
        $attr = $this->gen_Attrs($attrs,$vars);
        $src = make_hyperlink($src,$this->ModuleName);
        echo "<img src='{$src}'{$attr} />";
    }
    //--------------------------------------------------------------------------
    //  単純エコー出力
    private function cmd_echo($tag,$attrs,$subsec,$sec,$vars) {
        $this->directOutput('', '',$sec);
    }
    //--------------------------------------------------------------------------
    //  インラインセクションの登録
    private function cmd_inline($tag,$attrs,$subsec,$sec,$vars) {
        $name = $attrs['class'];
        $this->inlineSection[$name] = $sec;
    }
    //--------------------------------------------------------------------------
    //  セクション配列をマークダウン変換
    // 連想配列ならキー名をクラス名として扱う
    private function cmd_markdown($tag,$attrs,$subsec,$sec,$vars) {
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
        $save_data = $this->Model->RecData;         // backup RecData
        foreach($this->Model->Records as $records) {
            $this->Model->RecData = $records;    // レコードデータ
            debug_log(FALSE,[ 
                "data" => $this->Model->RecData,
                'sec' => $sec,
                'var' => $vars,
            ]);
            $this->sectionAnalyze($sec,$vars);
        }
        $this->Model->RecData = $save_data;         // restore RecData
    }
    //--------------------------------------------------------------------------
    //  ul/ol リストの出力
    // +ul => [
    //   { li.class#id => } [   ]       
    // ]
    private function cmd_list($tag,$attrs,$subsec,$sec,$vars) {
        $attr = $this->gen_Attrs($attrs,$vars);
        echo "<{$tag}{$attr}>\n";
        // リスト要素の出力
        foreach($subsec as $kk => $vv) {
            // $key と $sec をタグと属性に分解する
            list($s_tag,$s_text,$s_attr,$s_sec) = $this->tag_attr_Section($kk,$vv,$vars);
            $attr = $this->gen_Attrs($s_attr,$vars);
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
        $attr = $this->gen_Attrs($attrs,$vars);
        echo "<{$tag}{$attr}>\n";
        // DTのリスト要素の出力
        foreach($subsec as $kk => $vv) {
            // $key と $sec をタグと属性に分解する
            list($dt_tag,$dt_text,$dt_attrs,$dd_sec) = $this->tag_attr_Section($kk,$vv,$vars);
            $attr = $this->gen_Attrs($dt_attrs,$vars);
            $dt_text = $this->expand_Strings($dt_text,$vars);   // 変数置換を行う
            echo "<dt{$attr}>{$dt_text}</dt>\n";
            if(!empty($dd_sec)) {  // DDセクションがあれば処理
                foreach($dd_sec as $dd_key => $dd_sub) {
                    list($dd_tag,$dd_text,$dd_attrs,$dd_child) = $this->tag_attr_Section($dd_key,$dd_sub,$vars);
                    $dd_attr = $this->gen_Attrs($dd_attrs,$vars);
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
        if(is_array($subsec)) {
            $attr = $this->gen_Attrs($attrs,$vars);
            echo "<{$tag}{$attr}>\n";
            list($opt_key, $opt_val) = array_first_item($subsec);    // 最初の要素を処理
            $sel_item = (is_numeric($opt_key)) ? $opt_key : $this->expand_Strings($opt_key,$vars);
            $opt_val = $this->expand_SectionVar($opt_val,$vars);
    debug_log(-999,["TAG"=>$tag,"ATTR"=>$attrs,"SUB"=>$subsec,"SEC"=>$sec,"OPT"=>$opt_val,"SEL"=>$sel_item,"KEY"=>$opt_key]);
            if(is_array($opt_val)) {
                $opt_val = array_flat_reduce($opt_val);
                foreach($opt_val as $opt => $val) {
                    $sel = ($val == $sel_item) ? ' selected':'';
                    echo "<OPTION value='{$val}'{$sel}>{$opt}</OPTION>\n";
                }
            } else echo "<OPTION value='{$opt_val}'>{$opt_val}</OPTION>\n";
            echo "</{$tag}>\n";
        }
    }
    //--------------------------------------------------------------------------
    //  TABLE リストの出力
    // +table => [
    //    tr [
    //          th=> [ 値 ]
    //    pre-text
    // ]
    private function cmd_table($tag,$attrs,$subsec,$sec,$vars,$txt) {
        $attr = $this->gen_Attrs($attrs,$vars);
        if(is_array($subsec)) {
            echo "<TABLE{$attr}>\n";
            foreach($subsec as $key => $val) {
                if(!is_numeric($key)) {
                    list($key,$attrs) = $this->tag_Separate($key);
                    $tr_attr = $this->gen_Attrs($attrs,$vars);
                    echo "<TR{$tr_attr}>";
                } else echo "<TR>";
                if(is_array($val)) {
                    foreach($val as $td_key => $td_val) {
                        list($td_key,$td_text,$td_attrs,$td_sec) = $this->tag_attr_Section($td_key,$td_val,$vars);
                        $td_attr = $this->gen_Attrs($td_attrs,$vars);
                        if($td_key === 'div') $td_key='td';
                        echo "<{$td_key}{$td_attr}>";
                        $this->sectionAnalyze($td_sec,$vars);
                        echo "</{$td_key}>";
                    }
                }
                echo "</TR>\n";
            }
            echo "</TABLE>";
        }
    }
    //--------------------------------------------------------------------------
    //  INPUT RADIO リストの出力
    // +radio => [
    //    radio_key = > [
    //      option_text => value
    //      ...
    //    ]
    // ]
    private function cmd_radio($tag,$attrs,$subsec,$sec,$vars) {
        if(is_array($subsec)) {
            $attr = $this->gen_Attrs($attrs,$vars);
            $tags = "<INPUT TYPE='radio'{$attr}";
            list($opt_key, $opt_val) = array_first_item($subsec);    // 最初の要素を処理
            $sel_item = (is_numeric($opt_key)) ? '' : $this->expand_Strings($opt_key,$vars);
            $opt_val = $this->expand_SectionVar($opt_val,$vars);
            if(is_array($opt_val)) {
                $opt_val = array_flat_reduce($opt_val);
                foreach($opt_val as $opt => $val) {
                    $sel = ($opt == $sel_item) ? ' checked':'';
                    echo "{$tags} value='{$opt}'{$sel}>{$val}\n";
                }
            } else echo "{$tags} value='{$opt_val}'>{$opt_val}\n";
        }
    }
    //--------------------------------------------------------------------------
    //  INPUT CHECKBOX の出力
    // FORMAT-I
    //  +checkbox[name] => [ 
    //        @値1 => テキスト  [ ${@published} => 't' ]
    //  ]
    //  FORMAT-II
    //  +checkbox => [ 
    //      name2 => [ @値2 => テキスト [ ${@published} => 't' ] ]
    //      name3 => [ @値3 => テキスト [ ${@published} => 't' ] ]
    //  ]
    private function cmd_checkbox($tag,$attrs,$subsec,$sec,$vars) {
        $attr = $this->gen_Attrs($attrs,$vars);
        $tags = "<INPUT TYPE='checkbox'{$attr}";
        if(is_array($sec)) {
            $check_item = function($arr) use(&$vars) {
                $check_func=function($if) {return ($if) ? ' checked':'';};
                $checked = $txt = $value = '';
                foreach($arr as $key => $val) {
                    $val = $this->expand_SectionVar($val,$vars);
                    if(is_numeric($key)) {
                        if(is_array($val)) {
                            list($cmp1, $cmp2) = array_first_item($val);    // 最初の要素を処理
                            $checked = $check_func($cmp1 === $cmp2);
                        } else $checked = $check_func(!empty($val));
                    } else if($key[0]==='@') {
                        $value = mb_substr($key,1);
                        $txt = $val;
                    }
                }
                return " value='{$value}'{$checked}>{$txt}";
            };
            $attr = $this->gen_Attrs($attrs,$vars);
            if(array_key_exists('name',$attrs)) {   // FORMAT-I
                $item = $check_item($sec);
                echo "{$tags}{$item}\n";
            } else {            // FORMAT-II
                foreach($sec as $key => $val) {
                    if(!is_numeric($key)) {
                        $item = $check_item($val);
                        echo "{$tags} name='{$key}'{$item}\n";
                    }
                }
            }
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
                if($sep === '{') {
                    $str = trim($str,'{}');
                    $kk = "{$key}-element";
                    $attrList[$kk] = $str;
                } else if($sep === '[') {
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
        if(empty($tag) || strlen($tag)===1) return FALSE;
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
                $val = $this->expand_SectionVar($val,$vars);
                if(is_numeric($key)) {  // 連想キーが無い場合
                    // 値が配列かセクション用コマンドならセクションデータ扱い
                    if(is_array($val)||$this->is_section_tag($val)) {
                        if(isset($secList[$key])) $secList[] = $val;
                        else $secList[$key] = $val;    // 数字キーのものがあるため
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
