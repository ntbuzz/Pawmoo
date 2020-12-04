<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  coreLibs: Common Library for Core/Base Class
 */
//==============================================================================
// Extract the application,controller,method and parameters from REQUEST_URI
function get_routing_path($root) {
    $vv = $_SERVER['REQUEST_URI'];
    list($requrl,$q_str) = (strpos($vv,'?')!==FALSE)?explode('?',$vv):[$vv,''];
    $argv = explode('/', trim($requrl,'/'));
    if($root === $argv[0]) {
        array_shift($argv);      // retrieve application name
        $fwroot = "/{$root}/";              // URI is begin of frameowrkfolder name
    } else $fwroot = "/";                   // URI is begin of Application name
    // separate app/cont/method/filters and params
    $args=[];
    for($n=0;$n < count($argv) && !is_numeric($argv[$n]) && strpos($argv[$n],'.') === FALSE;$n++) $args[] = $argv[$n];
    while(count($args) < 3) $args[] = NULL;
    $pp = array_slice($argv,$n);

    list($appname,$controller,$method) = $args;
    $filters = array_splice($args,3);
    if(empty($controller)) $controller = $appname; // empty controller will be same of appname
    $filename = '';
    $params = array_filter($pp,function($v) use(&$filename) {
        if(strpos($v,'.')===FALSE) return TRUE;
        $filename = $v;return FALSE;});
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
    $ret = [$appname,$app_uri,$module,$q_str];
    debug_log(-999, [
        'フレームワーク情報' => [
            "SERVER" => $_SERVER['REQUEST_URI'],
            "app_uri"=> $app_uri,
            "appname"=> $appname,
            "Module"=> $module,
            "query"=> $q_str,
        ],
        "RET" => $ret,
    ]);
    return $ret;
}
function get_routing_params($dir) {
    $root = basename(dirname($dir));        // Framework Folder
    $vv = $_SERVER['REQUEST_URI'];
    list($requrl,$q_str) = (strpos($vv,'?')!==FALSE)?explode('?',$vv):[$vv,''];
    $param = trim(urldecode($requrl),'/'); 
    $args = explode('/', $param);
    $appname = array_shift($args);          // first item in PATH
    if($appname === $root) {                // is same of framework folder?
        $appname = array_shift($args);      // retrieve application name
        $fwroot = "/{$root}/";              // URI is begin of frameowrkfolder name
    } else {
        $fwroot = "/";                      // URI is begin of Application name
    }
    if(is_numeric($appname)) {              // is NUMERIC BEGIN?
        array_unshift($args,$appname);      // appname will be must not a numeric.
        $appname = '';
    }
    $app_uri = [ $fwroot, "{$fwroot}{$appname}/" ];
    // extract after Controoler path
    for($n=0;$n < count($args) && !is_numeric($args[$n]) && strpos($args[$n],'.') === FALSE;$n++) ;
    $params = array_intval_recursive(array_slice($args,$n));
    array_splice($args,$n);

    if(count($args) < 2) $args += array_fill(count($args),2-count($args),NULL);
    list($controller,$method) = $args;
    $filters = array_splice($args,2);
    if(empty($controller)) $controller = $appname; // empty controller will be same of appname
    $module = array(
        ucfirst(strtolower($controller)),
        ucfirst(strtolower($method)),
        $filters,
        $params
    );
    $ret = [$appname,$app_uri,$module,$q_str];
    return $ret;
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
    exit;
}
//==============================================================================
// Output Message Page
// enabled of PHP VARIABLE:
//      $app_root       Application Top URI
//      $$page_title    Page Title
//      $msg_title      Message Title
//      $msg_body       Message Body
function page_response($app_page,...$msg_array) {
    $folders = array(App::Get_AppPath("error/"),"Core/error/");
    list($page_title,$msg_title,$msg_body) = $msg_array;
    $sys_root = App::Get_SysRoot();
    $app_root = App::Get_AppRoot();
    foreach($folders as $file) {
        $page_file = "{$file}{$app_page}";
        if(file_exists($page_file)) {
            require_once($page_file);
            exit;
        }
    }
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
        if(! in_array($fl,IgnoreFiles,FALSE)) {
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
            if(! in_array($fl,IgnoreFiles,FALSE)) {
                $path = "{$dirtop}{$fl}";
                $ext = substr($fl,strrpos($fl,'.') + 1);    // 拡張子を確認
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
// convert to num STRING to INTEGER
function array_intval_recursive($arr) {
    if(is_scalar($arr)) return (empty($arr) || is_numeric($arr))?intval($arr):$arr;
    return array_map(function($v) {
        if(is_array($v)) return array_intval_recursive($v);
        return (empty($v) || is_numeric($v))?intval($v):$v;
    },$arr);
}
//==============================================================================
// convert nexting array to flat array
function array_flat_reduce($arr) {
    $wx = [];
    $reduce_array = function ($arr) use(&$reduce_array,&$wx) {
        if(is_array($arr)) {
            foreach($arr as $key => $val) {
                if(is_array($val)) {
                    $reduce_array($val);
                } else if(is_numeric($key)) {
                    $wx[] = $val;
                } else {
                    $wx[$key] = $val;
                }
            }
        } else $wx[] = $arr;
    };
    $reduce_array($arr);
    return $wx;
}
//==============================================================================
// Generate URI from array, even when there is an array in the element
function array_to_URI($arr) {
    $array_builder = function ($lst) {
        $ret = [];
        foreach($lst as $val) {
            $uri = (is_array($val)) ? array_to_URI($val) : strtolower($val);
            if(!empty($uri)) $ret[] = trim($uri,'/');
        }
        return $ret;
    };
    $ret = $array_builder($arr);
    return implode('/',$ret);
}
//==============================================================================
// Concatenate array values by key value
function array_concat_keys($arr,$keys) {
    if(is_scalar($keys)) return $keys;
    $ss = ''; $trim_sep = ' ';
    foreach($keys as $kk => $val) {
        $sep = (is_numeric($kk)) ? ' ' : $kk;
        if(strpos($trim_sep,$sep) === FALSE) $trim_sep .= $sep;
        $ss .= $sep . $arr[$val];
    }
    return trim($ss,$trim_sep);
}
//==============================================================================
// The result of splitting the text and returning an array with whitespace removed
function trim_explode($sep, $str) {
    return array_map(function($v) { return trim($v); },explode($sep,$str));
}
//==============================================================================
// Make the file path end with /
function path_complete($path) {
    if(mb_substr($path,-1) !== '/') $path .= '/';
    return $path;
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
// Removes the character string for duplicate judgment
function tag_body_name($key) {
    $n = strrpos($key,':');
    if($n !== FALSE) {
        $dd = substr($key,$n+1);
        if(is_numeric($dd)) $key = substr($key,0,$n);
    }
    return $key;
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
// Recursive call to array_key_exists
function array_key_exists_recursive($key,$arr) {
    foreach($arr as $kk => $vv) {
        if($kk === $key) return TRUE;
        if(is_array($vv)) {
            if(array_key_exists_recursive($key,$vv)) return TRUE;
        }
    }
    return FALSE;
}
