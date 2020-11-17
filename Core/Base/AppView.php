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
    private $currentTemplate;
    private $rep_array;
    private $env_vars;             // GLOBAL Variable in TEMPLATE
    private $inlineSection;
    const FunctionList = array(
        '<'    => 'sec_html',
        '@'    => 'sec_import',
        '&'    => 'sec_helper',
        '+'    => [
            'setvar'    => 'cmd_setvar',
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
            'tabset'    => 'cmd_tabset',
            'php'       => 'cmd_php',
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
    debug_log(DBMSG_VIEW, "\$Layout = {$layout}");
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
            debug_log(DBMSG_VIEW,"RedirectURL: {$url}\n");
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
        $this->currentTemplate = $tmplate;
        $ext = substr($tmplate,strrpos($tmplate,'.') + 1);
        $ix = array_search($ext, self::Extensions);
        switch($ix) {   //   [ .tpl, .php, .inc, .twg ]
        case 0:         // '.tpl'   div Section
            $parser = new SectionParser($tmplate);
            $divSection = $parser->getSectionDef();
            $this->inlineSection = [];         // Clear Inline-Section in this TEMPLATE
            debug_log(DBMSG_VIEW,["SECTION @ {$name}" => $divSection,"SEC-VARS" => $vars]);
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
                if($var[0]==='@') {                 // AUTO Transfer
                    $var = mb_substr($var,1);
                    $var = 'Transfer.'.trim($this->Model->RecData[$var]);
                    $allow = FALSE;
                } else {
                    $allow = ($var[0] === '#');         // allow array
                    if($allow) $var = mb_substr($var,1);
                }
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
            case ':': 
                   	$p = '/:(\w+)(?:\[(\w+)\])*/';
                    preg_match($p,$var,$m);
                    list($match,$var,$mem) = $m;
                    if(isset($this->Model->$var)) { // exist Property?
                        if(!empty($mem) && isset($this->Model->$var[$mem]))
                            $val = $this->Model->$var[$mem];
                        else $val = $this->Model->$var;
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
    public function expand_Strings($str,$vars) {
        if(empty($str) || is_numeric($str)) return $str;
        $p = '/\${[^}\s]+?}|\${[#%\'\$@][^}\s]+?}/';       // PARSE variable format
        preg_match_all($p, $str, $m);
        $varList = $m[0];
        if(empty($varList)) return $str;        // not use variable.
        $values = $varList = array_unique($varList);
        array_walk($values, array($this, 'expand_Walk'), $vars);
        $exvar = (is_array($values[0])) ? $values[0]:str_replace($varList,$values,$str);
        return $exvar;
    }
//==============================================================================
//  EXPAND SECTION variable
    private function expand_SectionVar($vv,$vars) {
        if(is_scalar($vv)) return $this->expand_Strings($vv,$vars);
        $new_vv = [];
        foreach($vv as $kk => $nm) {
            $new_kk = $this->expand_Strings($kk,$vars);
            if(is_scalar($nm)) $nm = $this->expand_Strings($nm,$vars);
            $new_vv[$new_kk] = $nm;
        }
        return $new_vv;
    }
//==============================================================================
//  EXPAND SECTION at RECURSIVE
    private function expand_Recursive($vv,$vars) {
        if(is_scalar($vv)) return $this->expand_Strings($vv,$vars);
        $new_vv = [];
        foreach($vv as $kk => $nm) {
            $new_kk = $this->expand_Strings($kk,$vars);
            $nm = $this->expand_Recursive($nm,$vars);   // EXPAND CHILD
            $new_vv[$new_kk] = $nm;
        }
        return $new_vv;
    }
//==============================================================================
// TAG section direct OUTPUT
    private function directOutput($beg_tag, $end_tag,$sec,$vars) {
        echo "{$beg_tag}\n";
        if(is_array($sec)) {
            $sec = $this->expand_Recursive($sec,$vars);
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
                if(substr($key,0,2)==='?&') {
                    $tag = mb_substr($key,2);
                    $sec = $this->expand_Recursive($sec,$vars);
                    if(method_exists($this->Helper,$tag)) {
                        return $this->Helper->$tag($sec);
                    }
                    echo "Helper Method:'{$tag}' not found. Please Create this method.\n";
                    return [];
                }
                if(substr($key,0,1)==='?') {
                    $cmp_val = str_replace(["\n","\r"],'',$this->expand_Strings(mb_substr($key,1),$vars));
                    foreach($sec as $check => $value) {
                        if($check === '') $result = empty($cmp_val);            // is_empty ?
                        else if($check === '*') $result = !empty($cmp_val);     // is_notempty ?
                        else {
                            $chk_arr = explode('|',$check);
                            $result = FALSE;
                            foreach($chk_arr as $cmp_chk) {
                                $result = fnmatch($cmp_chk,$cmp_val);       // compare wild-char
                                if($result) break;
                            }
                        }
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
        if(is_scalar($divSection)) {
            echo $divSection;
            return;
        }
        foreach($divSection as $key => $sec) {
            // analyze IF-SELECTOR and EXPAND KEY
            $vv = $this->array_if_selector($sec, $vars);
            if(strlen($key) > 2 && $key[0] === '$' && $key[1] !== '{') {  // set Local Variable
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
            if($this->is_section_tag($sec,$sec) === FALSE) {
                echo "{$sec}\n";
                return;
            }
            $key = $sec; $sec = [];
        } else { // delete duplicate to avoid key-name
            $key = tag_body_name($key);
        }
        $key = $this->expand_Strings($key,$vars);        
        $top_char = mb_substr($key,0,1);
        if(array_key_exists($top_char,self::FunctionList)) {
            $kkey = mb_substr($key,1);
            if($top_char === $kkey[0]) {    // dual command charctor will output 1 charactor
                echo $kkey; return;
            }
            $func = self::FunctionList[$top_char];
            list($tag,$text,$attrs,$subsec) = $this->tag_attr_Section($key,$sec,$vars);
            if(is_array($func)) {
                $cmd = $func[$tag];
                if(array_key_exists($tag,$func) && (method_exists($this, $cmd))) {
                    $this->$cmd($tag,$attrs,$subsec,$sec,$vars,$text);
                } else echo "***NOT FOUND({$cmd}): {$cmd}({$tag},\$attrs,\$sec,\$vars) IN {$this->currentTemplate}\n";
            } else if(method_exists($this, $func)) {
                if(is_array($sec)) $sec = $this->expand_SectionVar($sec,$vars);
                $this->$func($tag,$attrs,$subsec,$sec,$vars,$text);
            } else echo "CALL: {$func}({$kkey},{$sec},vars)\n";
        } else {
            list($tag,$text,$attrs,$subsec) = $this->tag_attr_Section($key,$sec,$vars);
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
    // *************************************************************************
    // SECTION-TAG PROCESSIOG
    //==========================================================================
    // Convert ATTRIBUTE-LIST ARRAY to tag attribute strings
    private function gen_Attrs($attrs,$vars) {
        $attr = "";
        if($attrs !== array()) {
            foreach($attrs as $name => $val) {
                if(is_array($val)) $val = implode('',$val);
                $attr .= (is_numeric($name)) ? " {$val}" : " {$name}=\"{$val}\"";
            }
        }
        return $attr;
    }
    //==========================================================================
    // DIRECT HTML TAG
    private function sec_html($tag,$attrs,$subsec,$sec,$vars,$text) {
        $attr = $this->gen_Attrs($attrs,$vars);
        echo (empty($text)) ? "<{$tag}{$attr}>\n" : "<{$tag}{$attr}>{$text}</{$tag}>\n" ;
    }
    //==========================================================================
    // HTML Comment TAG
    private function sec_comment($tag,$attrs,$subsec,$sec,$vars,$text) {
        echo "<!-- $tag" . (empty($sec) ? " " : "\n");
        foreach($sec as $kk => $vv) echo "{$vv}\n";
        echo "-->\n";
    }
    //==========================================================================
    // IMPORT external TEMPLATE, or INLINE SECTION
    private function sec_import($tag,$attrs,$subsec,$sec,$vars,$text) {
        $is_inline = ($tag[0] === '.');
        if($is_inline) $tag = substr($tag,1);
        $mergevars = (is_array($sec)) ? array_merge($vars, $sec) : $vars;
        if($is_inline && array_key_exists($tag,$this->inlineSection)) {
            $this->sectionAnalyze($this->inlineSection[$tag],$mergevars);
        } else {
            $this->ViewTemplate($tag,$mergevars);
        }
    }
    //==========================================================================
    //  single tag for attribute only
    private function sec_singletag($tag,$attrs,$subsec,$sec,$vars,$text) {
        $attr = $this->gen_Attrs($attrs,$vars);
        if(!empty($subsec)) {  // have repeat-section
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
    // ALink Hyperlink
    private function sec_link($tag,$attrs,$subsec,$sec,$vars,$text) {
        if($tag === 'link') {
            if(is_array($sec)) {
                foreach($sec as $kk => $vv) $this->Helper->ALink($vv,$kk);
            } else echo "{$tagname} bad argument.\n";
        } else if(is_scalar($sec)) {
            $this->Helper->ALink($sec,$tag);
        } else echo "tag '{$tagname}' not for feature.\n";
    }
    //==========================================================================
    // CALL Helper-Method
    private function sec_helper($tag,$attrs,$subsec,$sec,$vars,$text) {
        // EXPAND CHILD for Helper-Method
        $sec = $this->expand_Recursive($sec,$vars);
        if(method_exists($this->Helper,$tag)) {
            $this->Helper->$tag($sec);
        } else if(method_exists('App',$tag)) {
            App::$tag($sec);
        } else {
            echo "Helper Method:'{$tag}' not found. Please Create this method.\n";
        }
    }
    //--------------------------------------------------------------------------
    // cmd_xxxx method
    //--------------------------------------------------------------------------
    //  iinclude external file, for CSS/JS/...
    private function cmd_include($tag,$attrs,$subsec,$sec,$vars,$text) {
        $sec = $this->expand_Recursive($sec,$vars);   // EXPAND CHILD
        App::WebInclude($sec);
    }
    //--------------------------------------------------------------------------
    //  SET GLOBAL VARIABLE
    private function cmd_setvar($tag,$attrs,$subsec,$sec,$vars,$text) {
        $sec = $this->expand_Recursive($sec,$vars);   // EXPAND CHILD
        $this->env_vars = $this->my_array_Merge($sec,$this->env_vars);
    }
    //--------------------------------------------------------------------------
    //  output JQuery function
    private function cmd_jquery($tag,$attrs,$subsec,$sec,$vars,$text) {
        $this->directOutput("<script type='text/javascript'>\n$(function() {", "});\n</script>",$sec,$vars);
    }
    //--------------------------------------------------------------------------
    //  javascript output
    private function cmd_script($tag,$attrs,$subsec,$sec,$vars,$text) {
        $this->directOutput("<script type='text/javascript'>", "</script>",$sec,$vars);
    }
    //--------------------------------------------------------------------------
    //  output STYLE tag
    private function cmd_style($tag,$attrs,$subsec,$sec,$vars,$text) {
        $this->directOutput('<style type="text/css">', "</style>",$sec,$vars);
    }
    //--------------------------------------------------------------------------
    //  output IMAGE-TAG
    private function cmd_image($tag,$attrs,$subsec,$sec,$vars,$text) {
        if(is_array($sec)) { // 連想キーが無いスカラー値のみ抽出
            $sec = $this->expand_SectionVar($sec,$vars);
            foreach($sec as $key => $val) {
                if(is_numeric($key) && is_scalar($val)) $src = $val;
            }
        } else $src = $sec;
        $attr = $this->gen_Attrs($attrs,$vars);
        $src = make_hyperlink($src,$this->ModuleName);
        echo "<img src='{$src}'{$attr} />";
    }
    //--------------------------------------------------------------------------
    //  echo string
    private function cmd_echo($tag,$attrs,$subsec,$sec,$vars,$text) {
        $this->directOutput('', '',$text,$vars);
    }
    //--------------------------------------------------------------------------
    //  PHP eval, Danger Section!
    private function cmd_php($tag,$attrs,$subsec,$sec,$vars,$text) {
        $atext = array_to_text($sec,"\n",FALSE);   // array to Text convert
        $atext = $this->expand_Strings($atext,$vars);
        $Helper = $this->Helper;
        $RecData = $this->Model->RecData;    // One-Record Data
        $Records = $this->Model->Records;    // Record Data-List, column data is used HeaderSchema
        $_ = function($id) { return $this->_($id); };   // shortcut LANG-ID Convert
        extract($vars);
        eval($atext);
    }
    //--------------------------------------------------------------------------
    //  Define INLINE Section, for use after section
    private function cmd_inline($tag,$attrs,$subsec,$sec,$vars,$text) {
        $name = $attrs['class'];
        $this->inlineSection[$name] = $sec;
    }
    //--------------------------------------------------------------------------
    // MARKDOWN OUTPUT
    //  if Associative ARRAY, KEY-NAME use to MARKDOWN Class
    private function cmd_markdown($tag,$attrs,$subsec,$sec,$vars,$text) {
        $atext = array_to_text($sec,"\n",FALSE);   // array to Text convert
        $atext = $this->expand_Strings($atext,$vars);
        if(is_array($sec)) $atext = "\n{$atext}\n\n";
        $cls = (isset($attrs['class'])) ? $attrs['class'] : '';
        $mtext = pseudo_markdown( $atext,$cls);
        echo $mtext;
    }
    //--------------------------------------------------------------------------
    // repeat Recode Data List (fetch Multi Record set)
    // $sec variable expand on repeat RecData
    private function cmd_recordset($tag,$attrs,$subsec,$sec,$vars,$text) {
        $save_data = $this->Model->RecData;         // backup RecData
        $props = 'Records';
        if(isset($attrs['name'])) {
            $nm = mb_substr($attrs['name'],1);     // except ':' char
            if(isset($this->Model->$nm)) $props = $nm;
        }
        foreach($this->Model->$props as $records) {
            $this->Model->RecData = $records;    // replace RecData
            $v_sec = $this->expand_SectionVar($sec,$vars);
            $this->sectionAnalyze($v_sec,$vars);
        }
        $this->Model->RecData = $save_data;         // restore RecData
    }
    //--------------------------------------------------------------------------
    //  ul/ol List OUTPUT
    // +ul => [
    //   { li.class#id => } [   ]
    // ]
    private function cmd_list($tag,$attrs,$subsec,$sec,$vars,$text) {
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
    //  dl List OUTPUT
    // +dl => [
    //    [ DT-Text 
    //      { DD-ATTR => } [ SECTION ]
    //    ]
    // ]
    private function cmd_dl($tag,$attrs,$subsec,$sec,$vars,$text) {
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
    //  select OUTPUT
    // +select => [
    //    selected_key = > [
    //      option_text => value
    //      ...
    //    ]
    // ]
    private function cmd_select($tag,$attrs,$subsec,$sec,$vars,$text) {
        if(is_array($subsec)) {
            $attr = $this->gen_Attrs($attrs,$vars);
            echo "<{$tag}{$attr}>\n";
            list($opt_key, $opt_val) = array_first_item($subsec);
            $sel_item = (is_numeric($opt_key)) ? $opt_key : $this->expand_Strings($opt_key,$vars);
            $opt_val = $this->expand_SectionVar($opt_val,$vars);
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
    // +tabset.classname => [
    //      Menu1.selected => [ Contents1 ]
    //      Menu2 => [ Contents2 ]
    //      Menu3 => [ Contents3 ]
    //      Menu4 => [ Contents4 ]
    //  ]
    private function cmd_tabset($tag,$attrs,$subsec,$sec,$vars,$text) {
        $mycls = (isset($attrs['class']))? $attrs['class'] :'';
        $attrs['class'] = rtrim("tabControll {$mycls}");
        $attr = $this->gen_Attrs($attrs,$vars);
        if(is_array($subsec)) {
            echo "<div{$attr}>\n";
            // create tabset
            echo "<ul class='tabmenu'>\n";
            foreach($subsec as $key => $val) {
                list($tag,$attrs) = $this->tag_Separate($key);
                $attr = $this->gen_Attrs($attrs,$vars);
                echo "<li{$attr}>{$tag}</li>\n";
            }
            echo "</ul>\n";
            // create tab-contents block
            echo "<ul class='tabcontents'>\n";
            foreach($subsec as $key => $val) {
                list($tag,$attrs) = $this->tag_Separate($key);
                if(array_key_exists('class',$attrs)) {
                    if(!preg_match('/hide|selected/', $attrs['class'])) {
                        $attrs['class'] .= ' hide';
                    }
                } else $attrs['class'] = 'hide';
                $attr = $this->gen_Attrs($attrs,$vars);
                echo "<li{$attr}>";
                $this->sectionAnalyze($val,$vars);
                echo "</li>\n";
            }
            echo "</ul>\n";
            echo "</div>";
        }
    }
    //--------------------------------------------------------------------------
    //  TABLE OUTPUT
    // +table => [
    //    [  th=>[ TH-CELL ] .td_attr=>[ TD-CELL ]  [ TD-CELL ] ]
    // ]
    private function cmd_table($tag,$attrs,$subsec,$sec,$vars,$text) {
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
                        if($td_key === 'div') $td_key='td';     // omitted TAG is DIV tag setting
                        echo "<{$td_key}{$td_attr}>{$td_text}";
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
    //  INPUT RADIO OUTPUT
    // +radio[name] => [
    //    select_option_value = > [
    //      option_text => option_value
    //      ...
    //    ]
    // ]
    private function cmd_radio($tag,$attrs,$subsec,$sec,$vars,$text) {
        if(is_array($subsec)) {
            $subsec = $this->expand_Recursive($subsec,$vars);   // EXPAND ALL-CHILD
            $attr = $this->gen_Attrs($attrs,$vars);
            $tags = "<INPUT TYPE='radio'{$attr}";
            list($opt_key, $opt_val) = array_first_item($subsec);
            $sel_item = (is_numeric($opt_key)) ? '' : $opt_key;
            $opt_val = $this->expand_SectionVar($opt_val,$vars);
            if(is_array($opt_val)) {
                $opt_val = array_flat_reduce($opt_val);
                foreach($opt_val as $opt => $val) {
                    $sel = ($val == $sel_item) ? ' checked':'';
                    echo "{$tags} value='{$val}'{$sel}>{$opt}\n";
                }
            } else echo "{$tags} value='{$opt_val}'>{$opt_val}\n";
        }
    }
    //--------------------------------------------------------------------------
    //  INPUT CHECKBOX OUTPUT
    // FORMAT-I
    //  +checkbox[name] => [ 
    //        @Value => TEXT  [ ${@published} => 't' ]
    //  ]
    //  FORMAT-II
    //  +checkbox => [ 
    //      name1 => [ @VALUE1 => TEXT [ ${@published} => 't' ] ]
    //      name2 => [ @VALUE2 => TEXT [ ${@published} => 't' ] ]
    //  ]
    private function cmd_checkbox($tag,$attrs,$subsec,$sec,$vars,$text) {
        $attr = $this->gen_Attrs($attrs,$vars);
        $tags = "<INPUT TYPE='checkbox'{$attr}";
        if(is_array($sec)) {
            $sec = $this->expand_Recursive($sec,$vars);   // EXPAND ALL-CHILD
            $check_item = function($arr) use(&$vars) {
                $check_func=function($if) {return ($if) ? ' checked':'';};
                $checked = $txt = $value = '';
                foreach($arr as $key => $val) {
                    if(is_numeric($key)) {
                        if(is_array($val)) {
                            list($cmp1, $cmp2) = array_first_item($val);
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
    // TAG string SEPARATE
    private function tag_Separate($tag) {
        $attrList = [];
        if (is_numeric($tag)) return ['div',$attrList];
        if($tag[0]==='\\') $tag = mb_substr($tag,1);
        $tag = tag_body_name( $tag );       // delete dup-escapt char ':'
        $top_ch = mb_substr($tag,0,1);
        if(strpos(SECTION_TOKEN,$top_ch) !== FALSE) {
            if($top_ch === '<') {
                $tags = explode(' ',trim($tag,'<>'));
                $tag = array_shift($tags);
                foreach($tags as $val) {
                    $attr = explode('=',trim($val));
                    if(count($attr) === 2) {
                        $attrList[$attr[0]] = trim($attr[1],"\"'");
                    } else {
                        $attrList[] = trim($val,"\"'");
                    }
                }
            } else {
                $tag = mb_substr($tag,1);
                if($top_ch === '*') return [$tag,''];
            }
        }
        // allow multi attribute, and separater not space
        foreach(['data-element' => '{}', 'name' => '[]', 'id' => '##', 'class' => '..'] as $key => $seps) {
            list($sep,$tsep) = str_split($seps);
            $n = strrpos($tag,$sep);
            while( $n !== FALSE) {
                $m = strrpos($tag,$tsep);
                $str = ($m === FALSE || $m === $n) ? mb_strcut($tag,$n+1) : mb_strcut($tag,$n+1,$m-$n-1);
                $tag = substr($tag,0,$n);
                $attrList[$key] = (array_key_exists($key,$attrList)) ? "{$str} {$attrList[$key]}" : $str;
                $n = strrpos($tag,$sep);
            }
		}
        if(empty($tag)) $tag = 'div';
        return array($tag,$attrList);
    }
    //==========================================================================
    // Check $key will be SECTION.
    private function is_section_tag($key,$val) {
        if(is_array($val)) return TRUE;
        if(strlen($key) <= 1) return FALSE;
        return (strpos(SECTION_TOKEN.'.#\\',$key[0]) !== FALSE);
    }
    //==========================================================================
    // TAG SEPARATE,naked TAG, attribute list in $tag and $sec, sub-section array, and inner-text
    private function tag_attr_Section($tag,$sec,$vars) {
        $innerText = '';
        $secList = [];
        list($tag,$attrList) = $this->tag_Separate($tag);
        if(is_array($sec)) {
            $sec = $this->expand_SectionVar($sec,$vars);
            foreach($sec as $key => $val) {
                if(is_numeric($key)) {
                    // if $val will be ARRAY,SECTION-COMMAND, then SET SUB-SECTION
                    if($this->is_section_tag($val,$val)) {
                        if(isset($secList[$key])) $secList[] = $val;
                        else $secList[$key] = $val;
                    } else {
                        $innerText .= $val;
                    }
                } else if($key[0]==='!') {      // FORCE tag attribute
                    $key = mb_substr($key,1);
                    $attrList[$key] = $val;     // allow ARRAY attribute
                } else {
                    list($vv,$attrs) = $this->tag_Separate($key);
                    // if $val is ARRAY,or $key is SECTION-COMMAND, and $key have ATTRIBUTE, then SET SUB-SECTION
                    if(!empty($attrs) || $this->is_section_tag($key,$val)) $secList[$key] = $val;
                    else $attrList[$key] = $val;
                }
            }
        } else {
            $innerText .= $sec;     // scalar is innertext
        }
        $innerText = implode( "\n" ,text_line_split("\n",$this->expand_Strings($innerText,$vars),TRUE));
        return array($tag,$innerText,$attrList,$secList);
    }

}
