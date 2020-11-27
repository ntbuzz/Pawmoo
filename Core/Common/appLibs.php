<?php
//==============================================================================
// ARRAY first key , before PHP 7.3
if (!function_exists('array_key_first')) {
    function array_key_first(array $arr) {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}
//==============================================================================
// Divid by INTEGER, before PHP 7.0
if (!function_exists('intdiv')) {
    function intdiv($var,$base) {
        return ($var - ($var % $base)) / $base;
    }
}
//==============================================================================
// first key-value pair for associative arrays
function array_first_item($arr) {
    if(!empty($arr)) {
        foreach($arr as $key => $val) {
            return [$key,$val];
        }
    }
    return ['',''];
}
//==============================================================================
//  To compensate array, fixed count
function array_alternative($a,$max = 0, $b = []) {
    $n = count($b);
    if($max === 0) $max = $n;
    else if($n < $max) $b += array_fill($n,$max - $n,NULL);
    else $b = array_slice($b,0,$max);
    foreach($b as $key => $val) {
        if(empty($a[$key])) $a[$key] = $val;
    }
    return $a;
}
//==============================================================================
// get file extention, return string is ".ext"
function extract_extension($fn) {
    $nn = strrpos($fn,'.');
    return ($nn === FALSE) ? '' :
            substr($fn,$nn+1);
}
//==============================================================================
// extract file name, returned file array [ filename, extention ]
function extract_base_name($fn) {
    $nn = strrpos($fn,'.');
    if($nn === FALSE) $nn = strlen($fn);
    $ext = substr($fn,$nn+1);
    $fn = substr($fn,0,$nn);
    return array($fn,$ext);
}
//==============================================================================
// extract file path, returned path array [ path, filename, extention ]
function extract_path_file_ext($path) {
    list($path,$fn) = extract_path_filename($path);
    list($fn,$ext) = extract_base_name($fn);
    return array($path,$fn,$ext);
}
//==============================================================================
// extract file path, returned path array [ path, filename ]
function extract_path_filename($path) {
    if(mb_substr($path,-1) == '/') {
        $path = substr($path,0,strlen($path)-1);
    }
    $nn = strrpos($path,'/');
    if($nn === FALSE) {
        $fn = $path;
        $path = '';
    } else {
        $fn = substr($path,$nn+1);
        $path = substr($path,0,$nn+1);
    }
    if(mb_substr($path,-1) !== '/') $path .= '/';
    return array($path,$fn);
}
//==============================================================================
// string fixed lenght 
// $w > 0 = left fixed, $w < 0 = right fixed
function str_fixwidth($exp,$pad,$w) {
    if($w < 0) {
        $pp = str_repeat ($pad,-$w);
        return substr("{$pp}{$exp}",$w);
    }
    $pp = str_repeat ($pad,$w);
    return substr("{$exp}{$pp}",0,$w);
}
//==============================================================================
// Check the existence of the destination folder and move the file
function file_move($src,$dest){
    list($path,$fn) = extract_path_filename($dest);
    if(!file_exists($path)) mkdir($path);
    return rename($src,$dest);
}
//==============================================================================
// convert file size string
function byte_format($size){
	$units = array(' KB', ' MB', ' GB', ' TB', ' PB');
	$digits = ($size == 0) ? 0 : floor( log($size, 1000) );
	$over = false;
	$max_digit = count($units) -1 ;
	if($digits == 0){
		$num = $size;
	} else if(!isset($units[$digits])) {
		$num = $size / (pow(1000, $max_digit));
		$over = true;
	} else {
		$num = $size / (pow(1000, $digits));
	}
	$num = round($num,1);
	return ($over) ? $num . $units[$max_digit] : $num . $units[$digits];
}

//==============================================================================
// strpos for array version
function instr_array($str,$hayz) {
    foreach($hays as $val) {
        if(strpos($str,val) !== false) return TRUE;
    }
    return FALSE;
}
//==============================================================================
// Array depth calculation, short priority 1-row version
function array_depth($a, $c = 0) {
    return (is_array($a) && count($a))
          ? max(array_map("array_depth", $a, array_fill(0, count($a), ++$c)))
          : $c;
  }
//==============================================================================
// alternative array_merge(), Overwrite existing index elements
function array_override($a, $b) {
    foreach($b as $key => $val) $a[$key] = $val;
    return $a;
  }
//==============================================================================
// Convert text to HTML
function text_to_html($atext) {
    return nl2br(htmlspecialchars($atext));
}
//==============================================================================
// HTML tag output, by special char will be escaped.
function echo_safe($atext,$is_safe = TRUE) {
    echo ($is_safe) ? htmlspecialchars($atext) : $atext;
}
//==============================================================================
// escape char for JSON value
function json_escape($a) {
    $vv = str_replace(["\\","/","\r\n", "\r", "\n","\"","\t"],["\\\\","\\/","\\n","\\n","\\n","\\\"","\\t"], $a);
    return $vv;
}
//==============================================================================
// escape for controll char
function control_escape($a) {
    if($a === TRUE) return 'TRUE';
    if($a === FALSE) return 'FALSE';
    return str_replace(["\r\n", "\r", "\n","\t"],["\\r\\n","\\r","\\n","\\t"], $a);
}
//==============================================================================
// text line split by NL char, and trim-space each line
function text_line_split($del,$txt,$trim = FALSE) {
    $array = array_values(
            array_filter(
                array_map(function($a) {return trim(preg_replace('/\s+/', ' ', str_replace('　',' ',$a)));},
                    explode($del, $txt)
            ), ($trim) ? 'strlen' : function($a) { return TRUE;}
        ));
    return $array;
}
//==============================================================================
// array value concatinate to TEXT
function array_to_text($array,$sep = "\n", $in_key = TRUE) {
    $dump_text = function ($indent, $items)  use (&$dump_text,&$sep,&$in_key)  {
        $txt = ''; $spc = str_repeat(' ', $indent);
        foreach($items as $key => $val) {
            if(is_array($val)) {
                $txt .= $dump_text($indent+2, $val);
            } else if(is_numeric($key) || $in_key === FALSE) {
                $txt .= "{$spc}{$val}{$sep}";
            } else {
                $txt .= "{$spc}{$key}={$val}{$sep}" ;
            }
        }
        return trim($txt,$sep);
    };
    return (is_array($array)) ? $dump_text(0,$array) : $array;
}
//==============================================================================
// Like a MarkDown Description
// Markdown syntax is the original syntax other than the general one.
function pseudo_markdown($atext, $md_class = '') {
    if(empty($md_class)) $md_class = 'easy_markdown';
    $replace_defs = [
        '/\[([^\]]+)\]\(([-_.!~*\'()\w;\/?:@&=+\$,%#]+)\)/'    => '<a href="\\2">\\1</a>',
        "/^(---|___|\*\*\*)$/m"     => "<hr>",       // <HR>
        "/\s\*\*(.+?)\*\*\s/" => '<strong>\\1</strong>',  // BOLD
        "/\s__(.+?)__\s/"     => '<em>\\1</em>',   // BOLD
        "/\s--(.+?)--\s/"   => '<del>\\1</del>', // STRIKEOUT
        "/\s\*(.+?)\*\s/"   => '<span style="font-style:italic;">\\1</span>',             // ITALIC
        "/\s_(.+?)_\s/"     => '<span style="text-decoration:underline;">\\1</span>',     // UNDERLINE
        "/(?: {2}$|　$)/m"   => '<br>',        // newline
    ];
    // escape the characters to be excluded, and Windows(CR-LF) style change to UNIX(LF) style.
    $p = '/\s[ \-\=]>\s|\\\[<>]+\s|\\\<[^>\r\n]*?>|\r\n/';
    $atext = preg_replace_callback($p, function($matches) {
                return str_replace(['\<','\>','<','>',"\r"],['&lt;','&gt;','&lt;','&gt;',''],$matches[0]);}
            ,$atext);
    // ul/ol/blockquote processing
    $p = '/\n(([\-\d][\s\.]|>\s)[\s\S]+?)\n{2}/s';
    $atext = preg_replace_callback($p,function($matches) {
        $txt = $matches[1];
        $user_func = function($text) {
            $tags = array(
                '- ' => ['ul','ul_list',true],
                '1.' => ['ol','ol_list',true],
                '> ' => ['blockquote','bq_block',false]);
            $call_func = function($arr) use(&$tags) {
                $key_str = mb_substr($arr[0],0,2);
                $islist = $tags[$key_str][2];
                $app = 0;
                $make_array = function($array,$lvl) use(&$make_array,&$app,&$key_str,&$islist) {
                    $result = [];
                    while(isset($array[$app])) {
                        $str = $array[$app];
                        for($n = 0; ($islist)?ctype_space($str[$n]):($str[$n+1]==='>'); ++$n) ;
                        if(mb_substr($str,$n,2) !== $key_str) break;
                        $ll = mb_substr($str,$n+2);
                        if($n === $lvl) {
                            $result[] = $ll;
                            $app++;
                        } else if($n > $lvl) {
                            $result[] = $make_array($array,$lvl+1);
                        } else break;
                    }
                    return $result;
                };
                return [ $key_str => $make_array($arr,0)];
            };
            $arr = $call_func(explode("\n", $text));			// Make Level array
            $key = array_key_first($arr);
            list($ptag,$ptagcls,$islist) = $tags[$key]; 
            $ptag_start = (empty($ptagcls)) ? "<{$ptag}>" : "<{$ptag} class='{$ptagcls}'>";
            $ptag_close = "</{$ptag}>";
            $ul_text = function($array,$n) use(&$ul_text,&$ptag,&$ptag_start,&$ptag_close,$islist) {
                $spc = '';//str_repeat(' ',$n);
                $res = "";
                foreach($array as $n => $val) {
                    if(is_array($val)) {
                        $low = $ul_text($val,$n+1);
                        $res .= "{$spc}<{$ptag}>{$low}{$spc}{$ptag_close}";
                        if($n > 0 && $islist)	$res .= "</li>";
                    } else if($islist) {
                        $res .= (is_array(next($array)))?"{$spc}<li>{$val}":"{$spc}<li>{$val}</li>";
                    } else {
                        $res .= "{$spc}{$val}<br>\n";
                    }
                }
                return $res;
            };
            $tag_body = $ul_text($arr[$key],0);
            return "{$ptag_start}{$tag_body}{$ptag_close}\n";
        };
        return $user_func($txt);
    }, $atext);
    // TABLE processing
    $p = '/\n(\|[\s\S]+?\|)\n(?:(?:\.(\w+)){0,1}\n|$)/s';
    $atext = preg_replace_callback($p,function($matches) {
        // Combine lines that do not end with '|' as multiple lines
        $txt = preg_replace('/([^|])\n+/','\\1<br>', $matches[1]);
        $tbl_class = (empty($matches[2])) ? '':" {$matches[2]}";
        $arr = array_map(function($str) {
            $cols = explode("|", trim($str,"|"));
            $ln = "";
            $tags = [ '<' => 'left','>' => 'right','=' => 'center'];
            foreach($cols as $col) {
                if($col[0]===':') {     // TH cell
                    $col = mb_substr($col,1);
                    $tag = 'th';
                } else $tag = 'td';
                if(array_key_exists($col[0],$tags)) {
                    $ali = $tags[$col[0]];
                    $style = "text-align:{$ali};";
                    $col = mb_substr($col,1);
                } else $style = '';
                // maybe additional calss and colspan/rowspan
                preg_match('/^([@\^]+){0,1}(?:\.(\w+)){0,1}(?:#(\d+)){0,1}/',$col,$m);
                $bind = $cls = '';
                switch(count($m)) {
                case 4: $style .= ($m[3]==='') ? '':"width:{$m[3]}px;";
                case 3: $cls  = ($m[2]==='') ? '': " class='{$m[2]}'";
                case 2: $len = strlen($m[1]);
                        if($len === 0) $bind = '';
                        else {
                            $binds = [];
                            foreach(['@'=>'colspan','^'=>'rowspan'] as $bkey => $bval) {
                                $clen = substr_count($m[1],$bkey);
                                if($clen !== 0) $binds[] = " {$bval}='{$clen}'";
                            }
                            $bind = implode($binds);
                        }
                        $col = mb_substr($col,strlen($m[0]));
                        break;
                }
                $vars = (empty($style)) ? '' : " style='{$style}'";
                $ln .= "<{$tag}{$cls}{$bind}{$vars}>{$col}</{$tag}>";
            }
            return "<tr>{$ln}</tr>";
        },explode("\n", $txt));
        return "<table class='md_tbl{$tbl_class}'>".implode("\n",$arr)."</table>\n";
    }, $atext);
    //---------------------------------------------------------------------------
    // HEAD(#) TAG
    $atext = preg_replace_callback(
        "/^(#{1,6})(?:\.(\w+)){0,1} (.+?)$/m",
         function ($m) {
            $n = strlen($m[1]);
            $cls = ($m[2]==='')?'':" class='{$m[2]}'";
            return "<h{$n}{$cls}>{$m[3]}</h{$n}>\n";
        },$atext);
    //---------------------------------------------------------------------------
    // NL change <br> tag in DIV indent class
    $atext = preg_replace_callback(
            '/\.\.\.(?:(\w+)){0,1}\{(.+?)\n\}\.\.\./s',
             function ($m) {
                $txt = nl2br($m[2]);
                $cls = ($m[1]==='')?'indent':$m[1];
                return "<div class='$cls'>{$txt}</div>";
            },$atext);
    // pre tag with class
    $atext = preg_replace_callback(
            '/\n(```|~~~|\^\^\^)(?:(\w+)){0,1}\n(.+?)\n\1/s',
             function ($m) {
                $class = [ '```' => 'code','~~~' => 'indent','^^^' => 'indent'];
                $txt = $m[3];
                $cls = ($m[2]==='')?$class[$m[1]]:$m[2];
                return "<pre class='$cls'>{$txt}</pre>";
            },$atext);
    // CLASS/ID attributed SPAN/P replacement
    $atext = preg_replace_callback(
        '/\.\.(?:(\w+)){0,1}(?:#(\w+)){0,1}(:){0,1}\[([^\]]*?)\]/',
        function ($m) {
            $cls = ($m[1]==='') ? '' : " class='{$m[1]}'";
            $ids = ($m[2]==='') ? '' : " id='{$m[2]}'";
            $tag = ($m[3]==='') ? 'span' : 'p';
            $txt = $m[4];
            return "<{$tag}{$cls}{$ids}>{$txt}</{$tag}>";
        },$atext);
    // IMAGE TAG /multi-pattern replace
    $atext = preg_replace_callback(
        '/!\[([^:\]]+)(?::(\d+,\d+)){0,1}\]\(([!:]){0,1}([-_.!~*\'()\w;\/?:@&=+\$,%#]+)\)/',
        function ($m) {
            $alt = $m[1];
            if($m[2]==='') $sz = '';
            else {
                $wh = explode(',',$m[2]);
                $sz = " width='{$wh[0]}' height='{$wh[1]}'";
            }
            switch($m[3]) {
            case '!': $src = App::Get_AppRoot()."images/{$m[4]}"; break;
            case ':': $src = "/images/{$m[4]}";break;
            default: $src = $m[4];
            }
            return "<img src='{$src}' alt='{$alt}'{$sz} />";
        },$atext);
    // CHECKBOX MARK
    $atext = preg_replace_callback(
        '/\[([^\]]*?)\]\{([^}]+?)\}/',
            function ($m) {
            $chek = (in_array(strtolower($m[1]),['','0','f','false']))?'[ ]':'<b>[X]</b>';
            return " {$chek} {$m[2]}";
        },$atext);
    // replace other PATTERN values
    $replace_keys   = array_keys($replace_defs);
    $replace_values = array_values($replace_defs);
    $atext = preg_replace($replace_keys,$replace_values, $atext);
    // Returns the escaped character to the character before escaping.
    $p = '/\\\([~\-_<>\^\[\]`*#|\(\.{}])/s';
    $atext = preg_replace_callback($p,function($matches) {return $matches[1];}, $atext);
    return "<div class='{$md_class}'>{$atext}</div>\n";
}
//==============================================================================
// check for protocol
function get_protocol($href) {
    $n = strpos($href,':');
    if($n === FALSE) return NULL;
    return ($n > 3) ? mb_substr($href,0,$n) : NULL;
}
//==============================================================================
// Generate HYPER_LINK string
//  http〜  Direct URL
// :...     /...
// /..     sysRoot/...
// ./...    appRoot/modname/...
// ...      appRoot/...
// !!...    http://SERVER/...
// !:...    https://SERVER/...
function make_hyperlink($lnk,$modname) {
    if(get_protocol($lnk) === NULL) {
		if($lnk[0] === ':') {
            $lnk[0] = '/';
        } else if($lnk[0] === '/') {
			$lnk = App::Get_SysRoot("{$lnk}");
        } else {
            $prf = mb_substr($lnk,0,2);
            $ref = mb_substr($lnk,2);
            if($lnk[0] === '!') {
                $protocols = [ '!!' => 'https://', '!:' => ' http://' ];
                if(array_key_exists($prf,$protocols)) {
                    $lnk = $protocols[$prf] . App::$SysVAR['SERVER'] . $ref;
                } else $lnk = $ref;
            } else if($prf === './') {
                $lnk = substr_replace($lnk, strtolower($modname), 0, 1);
                $lnk = App::Get_AppRoot($lnk);
            } else  $lnk = App::Get_AppRoot($lnk);
		}
    }
    return $lnk;
}
//==============================================================================
// MARKING WORD by SPAN CLASS
function mark_active_words($atext,$word,$class) {
	$ln = array_values(array_map('trim', explode("\n", $atext)));
	$ret = array();
	foreach($ln as $ll) {
    	$ll = preg_replace("/(${word})/i","<span class='{$class}'>\\1</span>", $ll);
		$ret[] = $ll;
	}
	return implode("\n",$ret);
}
//==============================================================================
// password encryption
function passwd_encrypt($str) {
    $method_name = 'AES-256-CBC';
    $key_string = '_minimvc_waffle_map';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method_name));
    return openssl_encrypt($str,$method_name,$key_string,0,$iv);
}
