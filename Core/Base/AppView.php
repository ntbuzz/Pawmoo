<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  AppView:    View Template processing Engine.
 *              Handle of *.php, *.inc. *.tpl
 *              Having Child Class AppHelper for Detail HTML output
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
        '*'    => 'sec_comment',
        '%'    => 'sec_link',
        '-'    => 'sec_singletag',
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
            'floatwin'  => 'cmd_floatwin',
            'textbox'   => 'cmd_textbox',
            'php'       => 'cmd_php',
        ],
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
            debug_log(DBMSG_VIEW,"RedirectURL: {$url}");
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
            $divSection = $parser->getSectionDef(true);
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
            case '@':
                // field value, or alternate field or strings
                // @field-name=compare-value!TRUE-VALUE:FALSE-VALUE
                $p = '/(@{1,2})([\w_]+)(?:=([^:!]+))?(?:!([^:\n]*))?(?:\:([^\n]+))?/';
                preg_match($p,$var,$m);
                debug_log(-999,[ "PREG" => $m]);
                $get_field_data = function($nm) {
                    return (mb_substr($nm,0,1)==='@') ? $this->Model->RecData[mb_substr($nm,1)]:$nm;
                };
                list($pat,$raw,$fn) = $m;
                $var = $this->Model->RecData[$fn];     // get FIELD DATA
                if(count($m) !== 3) {
                    list(,,,$cmp,$val_true) = $m;
                    $val_true  = (empty($val_true) && !empty($cmp)) ? $var : $get_field_data($val_true);
                    $val_false = (count($m)===6 && !empty($m[5]))   ? $get_field_data($m[5]) : $var;
                    if(empty($cmp)) {          // compare empty,then empty or bool_false
                        $var = (empty($val_true)) ?
                            ( (empty($var)) ? $val_false : $var ) :
                            ( (is_bool_false($var)) ? $val_false : $val_true );
                    } else $var = fnmatch($cmp,$var) ? $val_true : $val_false;       // compare wild-char
                }
                if($raw==='@') $var = str_replace("\n",'',text_to_html($var));
                $val = $var;
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
                    if(is_numeric($var)) $val = App::$Params[intval($var)];          // get value from Params[] property
                    else {
                        $n = strpos('abcdefghijklmnopqrstuvwxyz',$var);
                        $val = (isset(App::$Filters[$n])) ? App::$Filters[$n] : '';
                    }
                }
                break;
            case '$': if(substr($var,-1) === '$') {
                    $var = trim($var,'$');
                    $val = App::$SysVAR[$var];          // SysVAR[] property
                }
                break;
            case ':':
                   	$p = '/(:{1,2})(\w+)(?:\[([\w_\'"]+)\])*/';
                    preg_match($p,$var,$m);
                    list($match,$cls,$var,$mem) = $m;
                    $mem = trim($mem,"\"'");        // allow quote char
                    $clsVar = ($cls === '::') ? $this->Helper : $this->Model;
                    if(isset($clsVar->$var)) { // exist Property?
                        $val = $clsVar->$var;
                        if(!empty($mem) && isset($val[$mem])) {
                            $val = $val[$mem];
                        }
                    }
                    break;
            case '^':       // both ENV or REQ VAR
            case '"':       // REQ-VAR
            case "'":       // ENV-VAR
                if(substr($var,-1) === $var[0]) {     // check end-char
                    $tt = $var[0];
                    $var = trim($var,$tt);
                    if($tt === '^') {
                        $val = MySession::get_varIDs(true,$var);
                        if(!empty($val)) break;
                    }
                    $val = MySession::get_varIDs(($tt==="'"),$var);// get SESSION ENV or REQUEST
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
        $p = '/\${[^}\s]+?}|\${[#%\'"\$@][^}\s]+?}/';       // PARSE variable format
        preg_match_all($p, $str, $m);
        $varList = $m[0];
        if(empty($varList)) return $str;        // not use variable.
        $values = $varList = array_unique($varList);
        array_walk($values, array($this, 'expand_Walk'), $vars);
        $exvar = (is_array($values[0])) ? $values[0]:str_replace($varList,$values,$str);
        return $exvar;
    }
//==============================================================================
//  EXPAND SECTION variable, $all = TRUE will recursive expand
    private function expand_SectionVar($vv,$vars,$all = FALSE) {
        if(is_scalar($vv)) return $this->expand_Strings($vv,$vars);
        $new_vv = [];
        foreach($vv as $kk => $nm) {
            $new_kk = $this->expand_Strings($kk,$vars);
            if(is_scalar($nm)) $nm = $this->expand_Strings($nm,$vars);
            else if(is_array($nm) && $all) $nm = $this->expand_SectionVar($nm,$vars,TRUE);   // EXPAND CHILD
            $new_vv[$new_kk] = $nm;
        }
        return $new_vv;
    }
//******************************************************************************
// NEW processing Template-Engine
//==============================================================================
// Replace IF-SELECTOR
    private function array_if_selector($arr,$vars) {
        if(is_scalar($arr)) return $arr;
        // analyze IF-SELECTOR and EXPAND KEY
        $if_selector = function($sec,$key) use(&$vars) {
            if($key[0]==='&') {
                $tag = mb_substr($key,1);
                $sec = $this->expand_SectionVar($sec,$vars,TRUE);
                if(method_exists($this->Helper,$tag)) {
                    return $this->Helper->$tag($sec);
                }
                echo "Helper Method:'{$tag}' not found. Please Create this method.\n";
                return [];
            }
            $key = $this->expand_Strings($key,$vars);
            $cmp_val = str_replace(["\n","\r"],'',$key);
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
        };
        $wd = [];       // re-build array
        foreach($arr as $key => $val) {
            if($key[0]==='?') {
                $ret = $if_selector($val,mb_substr($key,1));
                if(is_scalar($ret)) $ret = [$key => $ret];
                foreach($ret as $kk => $vv) {
                    if(is_numeric($kk)) $wd[] = $vv;
                    else {
                        set_array_key_unique($wd,$kk,$vv);
                    }
                }
            } else $wd[$key] = $val;
        }
        return $wd;
    }
//==============================================================================
// Analyzed Token-Section NEW VERSION
    private function sectionAnalyze($divSection,$vars) {
        if(is_scalar($divSection)) {
            echo $divSection;
            echo "DIE!!!!!!!!!!!!\n";
            return;
        }
        debug_log(-999,['SEC' =>$divSection, 'VARS'=>$vars]);
        foreach($divSection as $token => $sec) {
            $sec = $this->array_if_selector($sec, $vars);
            if(is_numeric($token)) {
                if(is_array($sec)) $this->sectionAnalyze($sec,$vars);
                else echo $sec;
            } else {
                list($tag,$attrs) = $this->tag_Separate($token,$vars);
                switch(is_tag_identifier($token)) {
                case 3:     // set local variable
                        $token = mb_substr($token,1);   // delete '$' top-char
                        $vars[$token] = $sec;
                        break;
                case 0: if(empty($sec)) break;
                case 1:         // tag-section
                        list($attrs,$innerText,$subsec) = $this->subsec_separate($sec,$attrs,$vars);
                        $attr = $this->gen_Attrs($attrs,$vars);
                        if($subsec === [])  echo "<{$tag}{$attr}>{$innerText}</{$tag}>\n";
                        else {
                            echo "<{$tag}{$attr}>{$innerText}\n";
                            $this->sectionAnalyze($subsec,$vars);
                            echo "</{$tag}>\n";
                        }
                        break;
                case 2:         // command-token
                        $top_char = mb_substr($tag,0,1);
                        $tag = mb_substr($tag,1);
                        $func = self::FunctionList[$top_char];
                        if(is_array($func)) {
                            $cmd = $func[$tag];
                            if(array_key_exists($tag,$func) && (method_exists($this, $cmd))) {
                                $this->$cmd($tag,$attrs,$sec,$vars);
                            } else echo "***NOT FOUND({$cmd}): {$cmd}({$tag},\$attrs,\$sec,\$vars) IN {$this->currentTemplate}\n";
                        } else if(method_exists($this, $func)) {
                            $this->$func($tag,$attrs,$sec,$vars);
                        } else echo "CALL: {$func}({$tag},{$sec},vars)\n";
                }
            }
        }
    }
    //==========================================================================
    // TAG string SEPARATE
    private function tag_Separate($tag,$vars) {
        $tag = $this->expand_Strings(tag_body_name($tag),$vars);
        $attrList = [];
        if($tag[0]==='<') return array($tag,$attrList); // html tag will be not separate
        // allow multi attribute, and separater not space
        foreach(['data-element' => '{}', 'value' => '()', 'name' => '[]', 'size' => '::', 'id' => '##', 'class' => '..'] as $key => $seps) {
            list($sep,$tsep) = str_split($seps);
            $n = strrpos($tag,$sep);
            while( $n !== FALSE) {
                $m = strrpos($tag,$tsep);
                $str = ($m === FALSE || $m === $n) ? mb_strcut($tag,$n+1) : mb_strcut($tag,$n+1,$m-$n-1);
                $tag = mb_strcut($tag,0,$n);
                if(!empty($str)) {
                    $attrList[$key] = (array_key_exists($key,$attrList)) ? "{$str} ".$attrList[$key] : $str;
                }
                $n = strrpos($tag,$sep);
            }
		}
        if(empty($tag)) $tag = 'div';
        return array($tag,$attrList);
    }
//==============================================================================
// Analyzed Section, and Dispatch Command method
    private function subsec_separate($section,$attrList,$vars) {
        $subsec = [];
        if(is_scalar($section)) {
            $innerText = array_to_text($this->expand_Strings($section,$vars));
        } else {
            $innerText = '';
            if(!empty($section)) {
                foreach($section as $token => $sec) {
                    if(is_numeric($token)) {
                        if(is_scalar($sec)) {
                            if(is_tag_identifier($sec)===2) {   // command-token @Template, etc...
                                set_array_key_unique($subsec,$sec,[]);
                            } else {
                                // separate attribute
                                $p = '/^([a-zA-Z]+[^\\\]):["\']?(.+)["\']?/';
                                if(preg_match($p,$sec,$m) === 1) {
                                    $attrList[$m[1]] = $m[2];
                                } else $innerText .= $sec;
                            }
                        } else $subsec[] = $sec;
                    } else {
                        $token = $this->expand_Strings(tag_body_name($token),$vars);
                        if (ctype_alpha($token)) {      // attr-name
                            if(!empty($sec)) $attrList[$token] = $sec;
                        } else {
                            set_array_key_unique($subsec,$token,$sec);
                        }
                    }
                }
            }
        }
        $innerText = preg_replace('/\\\\(.)/','\\1',$innerText);    // escape-char to original-char
        $innerText = $this->expand_Strings($innerText,$vars);
        return [$attrList,$innerText,$subsec];
    }
    // *************************************************************************
    // SECTION-TAG PROCESSIOG
    //==========================================================================
    // Convert ATTRIBUTE-LIST ARRAY to tag attribute strings
    private function gen_Attrs($attrs,$vars) {
        $attr = "";
        if(!empty($attrs)) {
            ksort($attrs);
            foreach($attrs as $name => $val) {
                $str = (is_array($val)) ? implode("\n",$val) :$val;
                $str = $this->expand_Strings($str,$vars);
                if(!empty($str)) $attr .= (is_numeric($name)) ? " {$str}" : " {$name}=\"{$str}\"";
            }
        }
        return $attr;
    }
    //==========================================================================
    // DIRECT HTML TAG
    //  <html>, <h1> => innerText or [ innerText ]
    private function sec_html($tag,$attrs,$sec,$vars) {
        list($attrs,$innerText,$subsec) = $this->subsec_separate($sec,$attrs,$vars);
        $tag = trim($tag,'<>');
        $attr = $this->gen_Attrs($attrs,$vars);
        echo (empty($innerText)) ? "<{$tag}{$attr}>\n" : "<{$tag}{$attr}>{$innerText}</{$tag}>\n" ;
    }
    //==========================================================================
    // HTML Comment TAG
    // *Comment, * => [ array-text ]
    private function sec_comment($tag,$attrs,$sec,$vars) {
        $txt = array_to_text($sec,'');
        echo "<!-- {$tag}{$txt} -->\n";
    }
    //==========================================================================
    // IMPORT external TEMPLATE, or INLINE SECTION
    // @Template, @Template => [ argument => value ... ]
    private function sec_import($tag,$attrs,$sec,$vars) {
        if(!empty($sec)) {      // set import argument
            foreach($sec as $key => $subsec) {
                if(mb_substr($key,0,1)==='$') $key = mb_substr($key,1);     // allow $var = value
                $vars[$key] = $this->expand_SectionVar($subsec,$vars,TRUE); // EXPAND CHILD
            }
        }
        $is_inline = ($tag[0] === '.');
        if($is_inline) $tag = substr($tag,1);
        if($is_inline && array_key_exists($tag,$this->inlineSection)) {
            $this->sectionAnalyze($this->inlineSection[$tag],$vars);
        } else {
            $this->ViewTemplate($tag,$vars);
        }
    }
    //==========================================================================
    //  single tag for attribute only (for <meta>)
    //  -tag => [ common-attr => value [ additional-attr => value ... ] ]
    private function sec_singletag($tag,$attrs,$sec,$vars) {
        list($attrs,$innerText,$subsec) = $this->subsec_separate($sec,$attrs,$vars);
        if(!empty($subsec)) {  // have repeat-section
            foreach($subsec as $vv) {
                list($at,$txt,$sub) = $this->subsec_separate($vv,$attrs,$vars);
                $atr = $this->gen_Attrs($at,$vars);
                echo "<{$tag}{$atr}>\n";
            }
        } else {
            $attr = $this->gen_Attrs($attrs,$vars);
            echo "<{$tag}{$attr}>\n";
        }
    }
    //==========================================================================
    // ALink Hyperlink
    //  %link => [ A-Text => URL ... ], %A-Text => URL
    private function sec_link($tag,$attrs,$sec,$vars) {
        $sec = $this->expand_SectionVar($sec,$vars,TRUE);
        $cls = (isset($attrs['class'])) ? $attrs['class'] : false;
        if($tag === 'link') {
            if(is_array($sec)) {
                foreach($sec as $kk => $vv) $this->Helper->ALink($vv,$kk,$cls);
            } else echo "{$tag} bad argument.\n";
        } else if(is_scalar($sec)) {
            $this->Helper->ALink($sec,$tag,$cls);
        } else echo "tag '{$tag}' not for feature.\n";
    }
    //==========================================================================
    // CALL Helper-Method
    //  &Helper-Method, &Helper-Method => [ argument => value ... ] in Helper refer $arg['argument']
    private function sec_helper($tag,$attrs,$sec,$vars) {
        // EXPAND CHILD for Helper-Method
        $sec = $this->expand_SectionVar($sec,$vars,TRUE);
        if(method_exists($this->Helper,$tag)) {
            $this->Helper->$tag($sec);
        } else if(method_exists('App',$tag)) {
            App::$tag($sec);
        } else {
            echo "Helper Method:'{$tag}' not found. Please Create this method.\n";
        }
    }
    //==============================================================================
    // TAG section direct OUTPUT for +jquery,+script,*style
    private function directOutput($beg_tag, $end_tag,$sec,$vars) {
        $txt = $this->expand_Strings(((is_array($sec)) ? array_to_text($sec) : $sec),$vars);
        echo "{$beg_tag}\n{$txt}\n{$end_tag}\n";
    }
    //--------------------------------------------------------------------------
    // cmd_xxxx method
    //--------------------------------------------------------------------------
    //  iinclude external file, for CSS/JS/...
    //  +include => [ inlclude-filename ... ]
    private function cmd_include($tag,$attrs,$sec,$vars) {
        $wsec = $this->expand_SectionVar($sec,$vars,TRUE);   // EXPAND CHILD
        App::WebInclude($wsec);
    }
    //--------------------------------------------------------------------------
    //  SET GLOBAL VARIABLE
    //  +setvar => [ varname => value ... ]
    private function cmd_setvar($tag,$attrs,$sec,$vars) {
        foreach($sec as $key => $sec) {
            if(mb_substr($key,0,1)==='$') $key = mb_substr($key,1);             // allow '$' prefix
            $this->env_vars[$key] = $this->expand_SectionVar($sec,$vars,TRUE);   // EXPAND CHILD
        }
    }
    //--------------------------------------------------------------------------
    //  output JQuery function
    //  +jquery => value (allow array)
    private function cmd_jquery($tag,$attrs,$sec,$vars) {
        $this->directOutput("<script type='text/javascript'>\n$(function() {", "});\n</script>",$sec,$vars);
    }
    //--------------------------------------------------------------------------
    //  javascript output
    //  +script => value (allow array)
    private function cmd_script($tag,$attrs,$sec,$vars) {
        $this->directOutput("<script type='text/javascript'>", "</script>",$sec,$vars);
    }
    //--------------------------------------------------------------------------
    //  output STYLE tag
    //  +style =>  value (allow array)
    private function cmd_style($tag,$attrs,$sec,$vars) {
        $this->directOutput('<style type="text/css">', "</style>",$sec,$vars);
    }
    //--------------------------------------------------------------------------
    //  output IMAGE-TAG
    // +img => URL , +img => [ attribule => value URL ]
    private function cmd_image($tag,$attrs,$sec,$vars) {
        list($attrs,$src,$subsec) = $this->subsec_separate($sec,$attrs,$vars);
        $attr = $this->gen_Attrs($attrs,$vars);
        $src = make_hyperlink($src,$this->ModuleName);
        echo "<img src='{$src}'{$attr} />";
    }
    //--------------------------------------------------------------------------
    //  echo string
    //  +echo => value, echo => [ value-list ]
    private function cmd_echo($tag,$attrs,$sec,$vars) {
        $this->directOutput('', '',$sec,$vars);
    }
    //--------------------------------------------------------------------------
    //  PHP eval for DEBUG, Danger Section!
    //  +php => php-command
    private function cmd_php($tag,$attrs,$sec,$vars) {
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
    //  Define INLINE Section, for use after import Template
    //  +inline.SecName => value  ( use import for @.SecName )
    private function cmd_inline($tag,$attrs,$sec,$vars) {
        $name = $attrs['class'];
        $this->inlineSection[$name] = $sec;
    }
    //--------------------------------------------------------------------------
    // MARKDOWN OUTPUT
    //  +markdown.classname => markdown-text
    private function cmd_markdown($tag,$attrs,$sec,$vars) {
        $atext = array_to_text($sec,"\n",FALSE);   // array to Text convert
        if(is_array($sec)) $atext = "\n{$atext}\n\n";
        $cls = (isset($attrs['class'])) ? $attrs['class'] : '';
        // pre-expand for checkbox and radio/select markdown
        $atext = preg_replace_callback('/(\[[^\]]*?\]\{(?:\$\{[^\}]+?\}|[^\}])+?\}|\^\[[^\]]*?\][%@:]\{(?:\$\{[^\}]+?\}|[^\}])+?\})/',
            function($m) use(&$vars) {
                list($pat,$var) = $m;
                $var = preg_replace_callback('/(\$\{[^\}]+?\})/',
                    function($mm) use(&$vars) {
                        $vv = $mm[1];
                        $this->expand_Walk($vv,0,$vars);
                        if(is_array($vv)) $vv = array_key_value($vv);
                        return $vv;
                    },$var);
                return $var;
            },$atext);
        $mtext = pseudo_markdown( $atext,$cls);
        $mtext = $this->expand_Strings($mtext,$vars);
        echo $mtext;
    }
    //--------------------------------------------------------------------------
    // repeat Model property data, default is 'Records', otherwise 'name' attribute
    //  +recordset => [ section ], +recoedset[propname] => [ section ]
    private function cmd_recordset($tag,$attrs,$sec,$vars) {
        $save_data = $this->Model->RecData;         // backup RecData
        $props = 'Records';
        if(isset($attrs['name'])) {
            $nm = $attrs['name'];
            if($nm[0]===':') $nm = mb_substr($nm,1);     // allow old-style begin ':' char
            if(isset($this->Model->$nm)) $props = $nm;
        }
        foreach($this->Model->$props as $records) {
            $this->Model->RecData = $records;    // replace RecData
            $this->sectionAnalyze($sec,$vars);
        }
        $this->Model->RecData = $save_data;         // restore RecData
    }
    //--------------------------------------------------------------------------
    //  ul/ol List OUTPUT
    // +ul => [ attr => value
    //   { li.class#id => } [   ]
    // ]
    private function cmd_list($tag,$attrs,$sec,$vars) {
        list($attrs,$text,$subsec) = $this->subsec_separate($sec,$attrs,$vars);
debug_log(-899,['SEC'=>$sec,'SUB'=>$subsec,'ATTR'=>$attrs,'TXT'=>$text]);
        $attr = $this->gen_Attrs($attrs,$vars);
        echo "<{$tag}{$attr}>\n";
        foreach($subsec as $li_token => $li_sec) {
            list($s_tag,$s_attrs) = $this->tag_Separate($li_token,$vars);
            list($s_attrs,$s_text,$s_sec) = $this->subsec_separate($li_sec,$s_attrs,$vars);
            $attr = $this->gen_Attrs($s_attrs,$vars);
            if(!empty($s_sec)) {  // list have a subsection ?
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
    // +dl => [ attr => value
    //    [ DT-Text
    //      { DD-ATTR => } [ SECTION ]
    //    ]
    //     ...
    // ]
    private function cmd_dl($tag,$attrs,$sec,$vars) {
        list($attrs,$text,$subsec) = $this->subsec_separate($sec,$attrs,$vars);
        $attr = $this->gen_Attrs($attrs,$vars);
        echo "<{$tag}{$attr}>\n";
        foreach($subsec as $dt_token => $dt_sec) {
            list($dt_tag,$dt_attrs) = $this->tag_Separate($dt_token,$vars);
            list($dt_attrs,$dt_text,$dd_sec) = $this->subsec_separate($dt_sec,$dt_attrs,$vars);
            $attr = $this->gen_Attrs($dt_attrs,$vars);
            echo "<dt{$attr}>{$dt_text}</dt>\n";
            if(!empty($dd_sec)) {  // dd tag have a subsection ?
                foreach($dd_sec as $dd_token => $dd_sub) {
                    list($dd_tag,$dd_attrs) = $this->tag_Separate($dd_token,$vars);
                    list($dd_attrs,$dd_text,$dd_child) = $this->subsec_separate($dd_sub,$dd_attrs,$vars);
                    $dd_attr = $this->gen_Attrs($dd_attrs,$vars);
                    echo "<dd{$dd_attr}>{$dd_text}\n";
                    $this->sectionAnalyze($dd_child,$vars);
                    echo "</dd>\n";
                }
            } else echo "<dd></dd>\n";
        }
        echo "</{$tag}>\n";
    }
    //--------------------------------------------------------------------------
    //  +floatwin = floatWindow + dl
    //  +floatwin.class#id => [
    //      value => "BUTTIONS"
    //      dt-Title-Text
    //      token => [ DD-Section ]
    // ]
    private function cmd_floatwin($tag,$attrs,$sec,$vars) {
        list($attrs,$text,$sec) = $this->subsec_separate($sec,$attrs,$vars);
        $mycls = (isset($attrs['class']))? $attrs['class'] :'';
        $attrs['class'] = rtrim("floatWindow {$mycls}");
        $attr = $this->gen_Attrs($attrs,$vars);
        echo "<div{$attr}>\n";
        // pick-up #init section
        foreach($sec as $key => $val) {
            if(strpos($key,'#init')!==FALSE) {
                unset($sec[$key]);
                $this->sectionAnalyze([$key => $val],$vars);
                break;
            }
        }
        echo "<dl><dt>{$text}</dt>\n";
        echo "<dd>\n";
        $this->sectionAnalyze($sec,$vars);
        echo "</dd></dl></div>\n";
    }
    //--------------------------------------------------------------------------
    //  select OUTPUT
    // +select => [ selected_key = > [
    //      option_text => value
    //      ...
    //  ] ]
    private function cmd_select($tag,$attrs,$sec,$vars) {
        if(!is_array($sec)) return;     // not allow scalar value
        $attr = $this->gen_Attrs($attrs,$vars);
        echo "<{$tag}{$attr}>\n";
        list($opt_key, $opt_val) = array_first_item($sec);
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
    //--------------------------------------------------------------------------
    // +tabset.classname => [
    //      Menu1.selected => [ Contents1 ]
    //      Menu2 => [ Contents2 ] ...
    //  ]
    private function cmd_tabset($tag,$attrs,$sec,$vars) {
        if(!is_array($sec)) return;     // not allow scalar value

        $mycls = (isset($attrs['class']))? $attrs['class'] :'';
        $attrs['class'] = rtrim("tabControll {$mycls}");
        $attr = $this->gen_Attrs($attrs,$vars);
        echo "<div{$attr}>\n";
        // create tabset
        echo "<div class='tabPanel'><ul class='tabmenu'>\n";
        $tabs = array_keys($sec);
        foreach($tabs as $key_val) {
            list($tag,$attrs) = $this->tag_Separate($key_val,$vars);
            $attr = $this->gen_Attrs($attrs,$vars);
            echo "<li{$attr}>{$tag}</li>\n";
        }
        echo "</ul></div>\n";
        // create tab-contents block
        echo "<ul class='tabcontents'>\n";
        foreach($sec as $key => $val) {
            list($tag,$attrs) = $this->tag_Separate($key,$vars);
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
    //--------------------------------------------------------------------------
    //  TABLE OUTPUT
    // +table => [ attr => value
    //    [  th=>[ TH-CELL ] .td_attr=>[ TD-CELL ]  [ TD-CELL ] ]
    // ]
    private function cmd_table($tag,$attrs,$sec,$vars) {
        if(!is_array($sec)) return;     // not allow scalar value
        list($attrs,$text,$sec) = $this->subsec_separate($sec,$attrs,$vars);
        $attr = $this->gen_Attrs($attrs,$vars);
        echo "<TABLE{$attr}>\n";
        foreach($sec as $key => $val) {        // tr loop
            if(!is_numeric($key)) {
                list($key,$attrs) = $this->tag_Separate($key,$vars);
                $tr_attr = $this->gen_Attrs($attrs,$vars);
                echo "<TR{$tr_attr}>";
            } else echo "<TR>";
            if(is_array($val)) {
                foreach($val as $td_key => $td_val) {         // th,td loop
                    list($tag,$attrs) = $this->tag_Separate($td_key,$vars);
                    list($attrs,$innerText,$sec) = $this->subsec_separate($td_val,$attrs,$vars);
                    $td_attr = $this->gen_Attrs($attrs,$vars);
                    if(is_numeric($tag) || $tag === 'div') $tag='td';     // omitted TAG is DIV tag setting
                    echo "<{$tag}{$td_attr}>{$innerText}";
                    $this->sectionAnalyze($sec,$vars);
                    echo "</{$tag}>";
                }
            }
            echo "</TR>\n";
        }
        echo "</TABLE>";
    }
    //--------------------------------------------------------------------------
    //  INPUT TEXT OUTPUT
    // +textbox[name]:size => [  attribute => value value    ]
    private function cmd_textbox($tag,$attrs,$sec,$vars) {
        list($attrs,$innerText,$sec) = $this->subsec_separate($sec,$attrs,$vars);
        if(!empty($innerText)) $attrs['value'] = $innerText;
        $attr = $this->gen_Attrs($attrs,$vars);
        echo "<INPUT TYPE='text'{$attr}'>\n";
    }
    //--------------------------------------------------------------------------
    //  INPUT RADIO OUTPUT
    // +radio[name] => [  select_option_value = > [
    //      option_text => option_value
    //      ...
    //  ] ]
    private function cmd_radio($tag,$attrs,$sec,$vars) {
        if(!is_array($sec)) return;     // not allow scalar value
        $attr = $this->gen_Attrs($attrs,$vars);
        $tags = "<INPUT TYPE='radio'{$attr}";
        $sec = $this->expand_SectionVar($sec,$vars,TRUE);   // EXPAND ALL-CHILD
        list($opt_key, $opt_val) = array_first_item($sec);
        $sel_item = (is_numeric($opt_key)) ? '' : $opt_key;
        if(is_array($opt_val)) {
            $opt_val = array_flat_reduce($opt_val);
            foreach($opt_val as $opt => $val) {
                $sel = ($val == $sel_item) ? ' checked':'';
                echo "{$tags} value='{$val}'{$sel}>{$opt}\n";
            }
        } else echo "{$tags} value='{$opt_val}'>{$opt_val}\n";
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
    private function cmd_checkbox($tag,$attrs,$sec,$vars) {
        if(!is_array($sec)) return;     // not allow scalar value
        $sec = $this->expand_SectionVar($sec,$vars,TRUE);   // EXPAND ALL-CHILD
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
        $tags = "<INPUT TYPE='checkbox'{$attr}";
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
