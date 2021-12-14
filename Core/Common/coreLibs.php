<?php
// coreLibs depend on appLibs functions.
require_once('appLibs.php');
require_once('arrayLibs.php');
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  coreLibs: Common Library for Core/Base Class
 */
//==============================================================================
// Extract the application,controller,method and parameters from REQUEST_URI
function get_routing_path($root) {
    $vv = $_SERVER['REQUEST_URI'];
	list($requrl,$q_str) = fix_explode('?',$vv,2);
    $argv = explode('/', trim($requrl,'/'));
    if($root === $argv[0]) {
        array_shift($argv);         // retrieve application name
        $fwroot = "/{$root}/";      // URI is begin of frameowrkfolder name
    } else $fwroot = "/";           // URI is begin of Application name
    // separate app/cont/method/filters and params
    $args=[];
    for($n=0;$n < count($argv) && !is_numeric($argv[$n]) && strpos($argv[$n],'.') === FALSE;$n++) $args[] = $argv[$n];
    while(count($args) < 3) $args[] = NULL;
    $pp = array_slice($argv,$n);
    list($appname,$controller,$method) = $args;
    $filters = array_splice($args,3);
    $filename = '';
    $params = array_filter($pp,
        function($v) use(&$filename) {
            $ext = extract_extension($v);
            if(in_array($ext,['html','htm','php','xml', 'cgi','js','css','inc'])) {
                $filename = $v;
                return FALSE;
            }
            return TRUE;
        });
    if(!empty($filename)) {
        array_unshift($filters,$method);      // appname will be must not a numeric.
        $method = $filename;
    } else $method = ucfirst(strtolower($method));
    $app_uri = [ $fwroot, "{$fwroot}{$appname}/" ];
    $module = array(
        ucfirst(strtolower($controller)),
        $method,
        $filters,
        array_intval_recursive($params),
    );
    $ret = [$appname,$app_uri,$module];
    return $ret;
}
//==============================================================================
// GET/POST array boolean change
function xchange_Boolean($arr) {
	$data = [];
	$bool_value = [ 'on' => TRUE,'off' => FALSE,'t' => TRUE,'f' => FALSE];
	foreach($arr as $key => $val) {		// GET parameter will be check query
		if(array_key_exists($val,$bool_value)) $val = $bool_value[$key];
		else if(is_numeric($val)) $val = intval($val);
		if(ctype_alnum(str_replace(['-','_'],'', $key))) $data[$key] = $val;
	}
	return $data;
}
//==============================================================================
// Generate URI from array, even when there is an array in the element
function array_to_query($query) {
	if(empty($query)) return '';
	$q_str = '?';
   foreach($query as $key => $val) {
		if($val === TRUE) $val = 't';
		else if($val === FALSE) $val = 'f';
		else $val = urlencode($val);
		$q_str = "{$q_str}{$key}={$val}&";
    }
	$q_str = rtrim($q_str,'&');
	return $q_str;
}
//==============================================================================
// Output 404 ERROR PAGE
// enabled of PHP VARIABLE:
//      $app_name   Applicatiopn Name
//      $app_root   Application Top URI
//      $app_module Controller Name
//      $page_name  Rquest ERROR PAGE
function error_response($error_page,$app_name, $app_uri, $module) {
    list($app_module,$page_name,$page_filter) = array_map(function($a) {
        return (gettype($a) === 'string')?strtolower($a):'';},$module);
    list($sys_root,$app_root) = $app_uri;
    require_once("Core/error/{$error_page}");
	require_once('Core/Template/View/debugbar.php');
//    exit;
}
//==============================================================================
// Output Message Page
// variable in page will be msg_array [ keyname => value, ... ]
//  and root URI information additional
//      $sys_root    System top URI
//      $app_root    Application top URI
function page_response($app_page,$msg_array) {
    $folders = array(App::Get_AppPath("error/"),"Core/error/");
	foreach($msg_array as $nm => $val) $$nm = $val;		// set local variable
    $sys_root = App::Get_SysRoot();
    $app_root = App::Get_AppRoot();
    foreach($folders as $file) {
        $page_file = "{$file}{$app_page}";
        if(file_exists($page_file)) {
            require_once($page_file);
            break;
        }
    }
	require_once('Core/Template/View/debugbar.php');
//    exit;
}
//==============================================================================
// check exist of CONTOLLER folder
function is_extst_module($appname,$modname,$classname) {
    if($modname == NULL) return FALSE;
    $modtop = getcwd() . "/" . "app/{$appname}/modules/{$modname}";
    $reqfile = "{$modtop}/{$modname}{$classname}.php";
    return file_exists($reqfile);
}
//==============================================================================
// get file lists in FOLDER
function get_folder_lists($dirtop) {
    $drc=dir($dirtop);
    $folders = array();
	while(false !== ($fl=$drc->read())) {
        if(! in_array($fl,IgnoreFiles,true)) {
            $path = "{$dirtop}{$fl}";
            if(is_dir($path)) {
                $folders[] = $fl;
            }
        }
    }
    $drc->close();
    return $folders;
}
//==============================================================================
// millisecond, UNIX TIME
function get_UnixTime_MillSecond(){
    $arrTime = explode('.',microtime(true));
    return date('H:i:s', $arrTime[0]) . '.' .$arrTime[1];
}
//==============================================================================
// get PHP extention file list.
function get_php_files($dirtop) {
    $files = array();
    if(file_exists($dirtop)) {
        $drc=dir($dirtop);
        while(false !== ($fl=$drc->read())) {
            if(! in_array($fl,IgnoreFiles,true)) {
                $path = "{$dirtop}{$fl}";
                $ext = substr($fl,strrpos($fl,'.') + 1);
                if(!is_dir($path) && ($ext == 'php')) {
                    $files[] = $path;
                }
            }
        }
        $drc->close();
    }
    return $files;
}
//==============================================================================
// Make the file path end with /
function path_simplify($path) {
    return (mb_substr($path,-1) === '/') ? substr($path,0,strlen($path)-1) : $path;
}
//==============================================================================
// Character code-set change
function SysCharset($str) {
    return (OS_CODEPAGE == 'SJIS') ?
            mb_convert_encoding($str,"UTF-8","sjis-win") : $str;
}
function LocalCharset($str) {
    return (OS_CODEPAGE == 'SJIS') ?
            mb_convert_encoding($str,"sjis-win","UTF-8") : $str;
}
//==============================================================================
// Locale type pickup
function get_locale_lang($lang_str) {
    $arr = array_unique(            // remove duplicate line
        array_filter(           	// empty line delete
            array_map(         		// pickup each element
                function($a) {
                    if(($n=strpos($a,'-')) !== FALSE)       return substr($a,0,$n);     // en-US => en
                    else if(($n=strpos($a,';')) !== FALSE)  return substr($a,0,$n);     // en;q=0.9 => en
                    else return $a;
                },
                explode(',', $lang_str)
            ),
            'strlen'));
    return  array_shift($arr);             // strict回避
}
//==============================================================================
// Removes the character string for duplicate judgment
function tag_body_name($key) {
    $n = strrpos($key,'::#');
    if($n !== FALSE) {
        $dd = substr($key,$n+3);
        if(is_numeric($dd)) $key = substr($key,0,$n);
    }
    return $key;
}
//==============================================================================
// additional label char separate
function tag_label_value($str) {
	$val = explode('.',$str);
	if(count($val) === 2) {
		$str = $val[0];
		$bc = mb_substr($val[1],0,1);
		$ec = mb_substr($val[1],1,1);
		if(empty($ec)) $ec = $bc;
	} else $bc = $ec = '';
	return [$str,$bc,$ec];
}
//==============================================================================
// separate tag convert
function separate_tag_value($val) {
	switch($val) {
	case '-':	return '<br>';
	case '---':	return '<hr>';
	default:	return $val;
	}
}
//==============================================================================
// tag-attr multi-class define
function get_class_names($cls, $with_attr = true) {
	if($cls === '') return '';
	$cls = trim(str_replace('.',' ',$cls));
    return ($with_attr) ? " class='{$cls}'" : $cls;
}
//==============================================================================
// get token type
//  tag-token       1
//  command-token   2
// setvariable      3
//  text            0   digit | alpha-numeric
function is_tag_identifier($str) {
    // token is scalar string
	if(is_scalar($str)) {
		$tokens = [
			'/^[%].*$/' => 2,						// allow empty body/attr(ex. %.attr=>)
			'/^([\*]).+$/' => 2,					// repeat char command available
			'/^([&@\+<\?%\-])(?!\1|$)/' => 2,		// command-token (not repeat char)
			'/^\$\w+$/' => 3,						// variable-token
			'/^\\\d+$/' => 1,						// escape digit
			// dirty pattern for TAG-token tag.class#id:size[name](value)<data-value>{data-element}|style|
			'/^[a-zA-Z_]*(?:[\.#][a-zA-Z_\-\s]*)+(?:\:\d+)?(?:[\[\(\<\{\|].+?[\|\}\>\)\]])*$/' => 1,
		];
		foreach($tokens as $pattern => $ret_val) {
			if(preg_match($pattern,$str)) return $ret_val;
		}
	}
    return 0;
}
//==============================================================================
// '_id' fieldname omitted
function id_relation_name($str) {
	return (substr($str,-3)==='_id') ? substr($str,0,strlen($str)-3) : $str;
}
//==============================================================================
// SQL Compare operator separate
function keystr_opr($str) {
    $opr_set = ['=='=>NULL, '<>'=>NULL, '>='=>NULL, '<='=>NULL, '=>'=>'>=', '=<'=>'<=', '!='=>'<>',
                '='=>NULL, '>'=>NULL, '<'=>NULL, '@'=>NULL, '%'=>NULL ];
    $str = tag_body_name($str);
    foreach([-2,-1] as $nn) {
        $opr = mb_substr($str,$nn);      // last-2char
        if(array_key_exists($opr,$opr_set)) {
            $key = mb_substr($str,0,$nn);    // exclude last 2-char
            if(isset($opr_set[$opr])) $opr = $opr_set[$opr];    // Replace OPR string for SQL
            return array($key,$opr);
        }
    }
    return array($str,'');
}
//==============================================================================
// remove whote-space, newline
function remove_space_comment_str($content) {
	$pat = '[:(){}\[\]<>\=\?;,]';    // remove white-space
	$content = preg_replace("/\\s*({$pat}|\\n|\\s)\\s*|\\s+[+\-]\\s+/sm", '$1$2',
			remove_comment_str($content));		// remove comment
	return $content;
}
//==============================================================================
// remove comment
function remove_comment_str($content) {
	$content = preg_replace('/([\r\n])+/s',"\n",                  // remove empty line
			preg_replace('/\/\*[\s\S]*?\*\/|\s+\/\/.*|^\/\/.*/','',$content));
	return trim($content);
}
//==============================================================================
// re-build condition array flat, possible
function re_build_array($cond) {
	$array_map_shurink = function($opr,$arr) use(&$array_map_shurink) {
		$array_merged = function($opr,&$arr,$val) use(&$array_merged) {
			if(is_array($val)) {
				foreach($val as $kk => $vv) {
					if($opr === $kk) {
						$array_merged($opr,$arr,$vv);
					} else {
						set_array_key_unique($arr,$kk,$vv);
					}
				}
			} else if($val !== '') $arr[] = $val;
		};
		$array_item_shurink = function($opr,$val) use(&$array_map_shurink) {
			return (is_array($val)) ? $array_map_shurink($opr,$val) : $val;
		};
		$AND_OR = [ 'AND' => TRUE, 'OR' => TRUE ];
		$wd = [];
		foreach($arr as $key => $val) {
			$child = $array_item_shurink((is_numeric($key))?$opr:$key,$val);
			if(is_numeric($key) && ($child === [] || $child === NULL)) continue;		// empty condition value
			if(is_numeric($key) || (isset($AND_OR[$key]) && (count($child)===1 || ($opr===$key)))) {
				$array_merged($opr,$wd,$child);
			} else {
				set_array_key_unique($wd,$key,$child);
			}
		}
		return $wd;
	};
	return $array_map_shurink('AND',$cond);
}
//==============================================================================
// UTF-8 CSV miss processing in Windows
// str_csv version
function str_csvget($csv_str) {
	$p = '/(?:^|,)((?:"(?:[^"]|"")*"|[^,]*)*)/u';
	preg_match_all($p,$csv_str,$m);
	$csv = [];
	foreach($m[1] as $item) {
		$wrapstr = mb_substr($item,0,1) . mb_substr($item,-1);     // top-end char pair
		if($wrapstr === '""' || $wrapstr === "''") $item = trim($item,$wrapstr);
		if($item === '' || $item === 'NULL') $item = NULL;
		else $item = str_replace('""','"',$item);		// "" のエスケープ解除
		$csv[] = (is_numeric($item)) ? intval($item) : $item;
	}
	return $csv;
}
//==============================================================================
// UTF-8 CSV miss processing in Windows
// fgets version
function fcsvget($handle) {
	if(($csv = fgets($handle))) {
		while( (mb_substr_count($csv,'"') %2) !== 0) {
			if(($next=fgets($handle))) {
				$csv .= $next;
			} else break;
		}
		return str_csvget(trim($csv));
	}
	return false;
}
//==============================================================================
//  variable format convert
// $[@#]varname | ${[@#]varname} | {$SysVar$} | {%Params%}
function expand_text($class,$str,$recdata,$vars=[],$match_all = false) {
    $expand_Walk = function(&$val, $key, $vars) use(&$recdata,&$class) {
        if($val[0] === '$') {           // top char is variable mark
            $var = mb_substr($val,1);
            $var = trim($var,'{}');                 // triming of delimitter { }
            switch($var[0]) {
			// cannot use in RESOURCE (AppStyle)
            case '@':	// @field-name=compare-value!TRUE-VALUE:FALSE-VALUE#limit-len
				$p = '/(@{1,2})([^=!:#]+)(?:=([^!:#]+))?(?:!([^:#]*))?(?:\:([^#]+))?(?:#(\d+))?/';
                preg_match($p,$var,$m);
                $get_field_data = function($nm) use(&$recdata) {
                    return (mb_substr($nm,0,1)==='@') ? $recdata[mb_substr($nm,1)]:$nm;
                };
                list($all,$raw,$fn) = $m;
				$var = trim(isset($recdata[$fn]) ? $recdata[$fn] : '');     // get FIELD DATA
				switch(count($m)) {
				case 7:		// limitation
					$limit = intval($m[6]);
					if(mb_strlen($var) > $limit) $var = mb_substr($var,0,$limit) . ' ...';
				case 6:
					$c = array_slice($m,3,3);
					if(implode('',$c) !== '') {
	                    list($cmp,$val_true,$val_false) = $c;
						if($val_true === '') $val_true = "@{$fn}";
						if($cmp === '') {	// no-comp will be empty-check
							$an = (is_bool_false($var)) ? $val_false:$val_true;
						} else {
							$an = fnmatch($cmp,$var) ? $val_true : $val_false;       // compare wild-char
						}
						$var = $get_field_data($an);	// get data from alter-name
					}
				}
                if($raw==='@') $var = str_replace("\n",'',text_to_html($var));
                $val = $var;
                break;
            case '#': $var = mb_substr($var,1);     // Language refer
                $allow = false;
                if($var[0]==='@') {                 // AUTO Transfer
                    $var = mb_substr($var,1);
                    $var = 'Transfer.'.trim($recdata[$var]);
                } else if($var[0]==='$') {                 // INDIRECT Transfer from VAR
                    $val = '${'.mb_substr($var,1).'}';
					$var = expand_text($class,$val,$recdata,$vars,true);
                } else {
                    $allow = ($var[0] === '#');         // allow array
                    if($allow) $var = mb_substr($var,1);
                }
				if(isset($class)) $val = $class->_($var,$allow);       // get Language define
                break;
			// cannot use in RESOURCE (AppStyle)
            case '%': if(substr($var,-1) === '%') {     // is parameter number
                    $var = trim($var,'%');
                    if(is_numeric($var)) $val = App::$Params[intval($var)];          // get value from Params[] property
                    else {
                        $n = strpos('abcdefghijklmnopqrstuvwxyz',$var);
                        $val = (isset(App::$Filters[$n])) ? App::$Filters[$n] : '';
                    }
                } else if(isset($vars[$var])) {
					$val = $vars[$var];
				}
                break;
            case '$': if(substr($var,-1) === '$') {
                    $var = trim($var,'$');
					$env = MySession::getEnvValues('sysVAR');
                    $val = $env[$var];          // SysVAR[] property
                }
                break;
			// cannot use in RESOURCE (AppStyle)
            case '?': $var = mb_substr($var,1);     // Query parameter
				$val = App::$Query[$var];          // Query[] property
                break;
			// cannot use in RESOURCE (AppStyle)
            case ':':                                   // Class Property
				if(isset($class)) {
                   	$p = '/(:{1,2})(\w+)(?:\[([\w\.\'"]+)\])?/';
                    preg_match($p,$var,$m);
                    $m[] = NULL;    // add NULL element for list()
                    list($match,$cls,$var,$mem) = $m;
                    $mem = trim($mem,"\"'");        // allow quote char
                    $clsVar = ($cls === '::') ? $class->Helper : $class->Model;
                    if(isset($clsVar->$var)) { // exist Property?
                        $val = array_member_value($clsVar->$var,$mem);
                    } else $val = NULL;
				}
                break;
            case '^':       // both ENV or POST VAR
            case '"':       // POST-VAR
            case "'":       // ENV-VAR
                if(substr($var,-1) === $var[0]) {     // check end-char
                    $tt = $var[0];
                    $var = trim($var,$tt);
					switch($tt) {
					case "'":$val = MySession::getEnvIDs($var);break;
					case '^':$val = MySession::getEnvIDs($var);	// scalar-Get
							 if($val !== '') break;	// empty will be try to POST
					case '"':$val = App::PostElements($var);
					}
                }
                break;
			// cannot use in RESOURCE (AppStyle)
            case '&':       // Helper Method CALL
				if(isset($class)) {
                   	$p = '/&(\w*)(?:\(([^\)]+)\))?/';
                    if(preg_match($p,$var,$m)===1) {
						list($mm,$method,$arg) =array_alternative($m,3);
						if(empty($method)) {
							$method = (defined('HELPER_EXPAND')) ? HELPER_EXPAND : DEFAULT_HELPER_EXPAND;
						}
						if(method_exists($class->Helper,$method)) {
							$arr = explode(',',$arg);
							$arg = (count($arr)===1) ? $arg : $arr;
							$val = $class->Helper->$method($arg);
						} else $val = "NOT-FOUND({$method})";
					}
				}
				break;
            default:
                if(isset($vars[$var])) {            // is LOCAL VAR-SET?
                    $val = $vars[$var];
                } else if(isset($class)) {
					if(isset($class->$var)) $val = $class->$var;	// CLASS-PROPERTY
                }
            }
        }
    };
	if($str === '' || is_numeric($str)) return $str;
	if($match_all) {
		$values = $varList = [$str];
	} else {
		$p = '/\${[^}\s]+?}|\${[#%\'"\$@&:][^}\s]+?}/';       // PARSE variable format
		preg_match_all($p, $str, $m);
		$varList = $m[0]; 
		if($varList === []) return $str;        // not use variable.
		$values = $varList = array_unique($varList);
	}
	array_walk($values, $expand_Walk, $vars);
	$exvar = (is_array($values[0])) ? $values[0]:str_replace($varList,$values,$str);
	return $exvar;
}
//==============================================================================
//  string to associate array convert
//  string is "key=val,key=val,..."
function array_associate_convert($str) {
	$arr = [];
	foreach(explode(',',$str) as $itemval) {
		if($itemval !== '') {
			list($opt_text,$opt_val) = explode('=',$itemval); 
			$arr[$opt_text] = $opt_val;
		}
	}
	return $arr;
}
//==============================================================================
//  make combobox HTML
function make_combobox($sel_item,$opt_list,$size) {
	$sz = int_value($size,12);
	$input_val = $sel_item;
	$tag= "<div class='combobox' style='width:{$sz}em;'>\n<select>\n";
	foreach($opt_list as $opt => $val) {
		$sel = ($val == $sel_item) ? ' selected':'';
		$tag= "{$tag}<OPTION{$sel}>{$opt}</OPTION>\n";
	}
	$sz -= 2;
	$tag = "{$tag}</select>\n<INPUT TYPE='text' value='{$input_val}' />\n</div>\n";
	return $tag;
}
//==============================================================================
//  add classname into attr[class] 
// and removed classname,if instructed.
function attr_addrmclass($attrs,$add,$rm=NULL) {
	$base = (isset($attrs['class'])) ? $attrs['class'] :'';
	if(!empty($rm)) $base = str_replace($rm,'',$base);
    return ltrim("{$base} {$add}");
}
//==============================================================================
//  classname exists check in attr[class] 
function attr_class_exist($attrs,$name,$exist,$none='') {
	$base = (isset($attrs['class'])) ? $attrs['class'] :'';
	return (strpos($base,$name)!==false) ? $exist:$none;
}
//==============================================================================
//  add classname into attr[class] 
// and removed classname,if instructed.
function attr_extract_element(&$attrs,$name,$isempty='',$isexists='%s') {
	$item_name = (isset($attrs[$name])) ? $attrs[$name]:'';
	unset($attrs[$name]);
	if(empty($item_name)) return $isempty;
	return str_replace('%s',$item_name,$isexists);
}
//==============================================================================
//  textbox, textedit size attribute convert
// allow ??px, ??em, ??%
function attr_sz_xchange($attrs) {
	$sz_attrs = [
		'size' => 'width',
		'rows' => 'height',
		'cols' => 'width',
	];
	$style = [];
	foreach($sz_attrs as $attr => $name)  {
		if(isset($attrs[$attr])) {
			$val = $attrs[$attr];
			if(!is_numeric($val)) {
				unset($attrs[$attr]);
				$style[] = "{$name}:{$val}";
			}
		}
	}
	$style_str = implode(';',$style);
	if(!empty($style_str)) $attrs['style'] = "\"{$style_str};\"";
	return $attrs;
}
//==============================================================================
//  Check CLIENT BROWSER PHP version
function client_Browser() {
	$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	$browers = [
		'Internet Explorer' => [ 'msie,trident',false],
		'Edge'				=> [ 'edge,edg' , 	false],
		'Google Chrome'		=> [ 'chrome', 		true],
		'Safari'			=> [ 'safari',		true],
		'FireFox'			=> [ 'firefox',		true],
		'Opera'				=> [ 'opera',		true],
	];
	foreach($browers as $name => $element) {
		$idset = explode(',',$element[0]);
		foreach($idset as $key) {
			if(strpos($agent,$key) !== false) {
				return [$name, $element[1] ];
			}
		}
	}
	return [ 'Unknown',false];
};
