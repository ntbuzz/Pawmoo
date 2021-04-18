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
    list($requrl,$q_str) = (mb_strpos($vv,'?')!==FALSE)?explode('?',$vv):[$vv,''];
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
/*
    $params = array_filter($pp,function($v) use(&$filename) {
        if(strpos($v,'.')===FALSE) return TRUE;
        $filename = $v;return FALSE;});
*/
    $params = array_filter($pp,
        function($v) use(&$filename) {
            $ext = extract_extension($v);
            if(in_array($ext,['html','htm','php','cgi','js','css','inc'])) {
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
    $ret = [$appname,$app_uri,$module,$q_str];
    debug_log(FALSE, [
        'Framework Information' => [
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
            break;
        }
    }
    exit;
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
// get token type
//  tag-token       1
//  command-token   2
// setvariable      3
//  text            0   digit | alpha-numeric
function is_tag_identifier($str) {
    // digit or empty string is not token
    if(empty($str) || is_array($str)) return 0;
    if(strpos('*&@+<?%-',$str[0]) !== FALSE)     return 2;       // command-token
    // dirty pattern for TAG-token
    $p = '/^(?:[a-zA-Z_]*)(?:[\.#][a-zA-Z_\-\s]*)+(?:\:\d+)?(?:[\{\(\[].+?[\}\)\]])*$/';
    if(preg_match($p,$str)) return 1;
    if(preg_match('/^\$\w+$/',$str)) return 3;    // variable-token
    return 0;   // text-token
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
	$content = preg_replace("/\\s*({$pat})\\s+|\\s+({$pat})\\s*|(\\s)+/sm", '$1$2$3',
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
