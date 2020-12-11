<?php
//==============================================================================
// Replace callback Array , before PHP 7
if (!function_exists('preg_replace_callback_array')) {
    function preg_replace_callback_array(array $arr,$atext) {
        foreach($arr as $pattern => $callback) {
            $atext = preg_replace_callback($pattern, $callback, $atext);
        }
        return $atext;
    }
}
//==============================================================================
// Like a MarkDown Description
// Markdown syntax is the original syntax other than the general one.
function pseudo_markdown($atext, $md_class = '') {
    if(empty($md_class)) $md_class = 'easy_markdown';
    $replace_defs = [
        '/\[([^\]]+)\]\(([-_.!~*\'()\w;\/?:@&=+\$,%#]+)\)/'    => '<a href="\\2">\\1</a>',
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
    // DL processing
    $atext = preg_replace_callback('/\n(:.*?)\n\n/s',function($m) {
        $dtdd = array_map(function($v) {
            preg_match('/^(.*?)(?=[ ,\n]+)(.*)$/s',$v,$match);
            $dd = $match[2];
            return "<dt>{$match[1]}</dt><dd>{$dd}</dd>";
        }, array_filter(explode(':',$m[1]),function($v) {return strlen($v)>0;}));
        return "<dl class='dl_list'>".implode("\n",$dtdd)."</dl>";
    }, $atext);
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

//------------------------------------------------------------------------------
// multi pattern replace
    $item_array = function($delimit,$str,$max=0,$b=[]) {
        $a = explode($delimit,$str);
        $n = count($b);
        if($max === 0) $max = $n;
        else if($n < $max) $b += array_fill($n,$max - $n,NULL);
        else $b = array_slice($b,0,$max);
        foreach($b as $key => $val) {
            if(empty($a[$key])) $a[$key] = $val;
        }
        return $a;
    };
    $atext = preg_replace_callback_array([
//------- HEAD(#) TAG
        '/^(#{1,6})(?:\.(\w+)){0,1} (.+?)$/m' => function($m) {
            $n = strlen($m[1]);
            $cls = ($m[2]==='')?'':" class='{$m[2]}'";
            return "<h{$n}{$cls}>{$m[3]}</h{$n}>";
        },
        '/^(?:---|___|\*\*\*)$/m'     => function($m) { return "<hr>"; },
//------- pre tag with class
        '/(?:^|\n)(```|~~~|\^\^\^)(?:(\w+)){0,1}(.+?)\n\1/s' => function ($m) {
            $class = [ '```' => 'code','~~~' => 'indent','^^^' => 'indent'];
            $txt = $m[3];
            $cls = ($m[2]==='')?$class[$m[1]]:$m[2];
            return "\n<pre class='$cls'>{$txt}</pre>";
        },
//------- ![alt-text](URL) IMAGE TAG /multi-pattern replace
        '/!\[([^:\]]+)(?::(\d+,\d+)){0,1}\]\(([!:]){0,1}([-_.!~*\'()\w;\/?:@&=+\$,%#]+)\)/' => function ($m) use(&$item_array) {
            $alt = $m[1];
            if($m[2]==='') $sz = '';
            else {
                list($wd,$ht) = $item_array(',',$m[2],2);
                $sz = " width='{$wd}' height='{$ht}'";
            }
            switch($m[3]) {
            case '!': $src = App::Get_AppRoot()."images/{$m[4]}"; break;
            case ':': $src = "/images/{$m[4]}";break;
            default: $src = $m[4];
            }
            return "<img src='{$src}' alt='{$alt}'{$sz} />";
        },
//------- ..class#id{ TEXT } CLASS/ID attributed SPAN/P replacement
        '/\s\.\.(?:(\w+)){0,1}(?:#(\w+)){0,1}(:){0,1}\{([^\}]*?[^\\\\]|)\}\s/s' => function ($m) {
            $cls = ($m[1]==='') ? '' : " class='{$m[1]}'";
            $ids = ($m[2]==='') ? '' : " id='{$m[2]}'";
            $tag = ($m[3]==='') ? 'span' : 'p';
            $txt = $m[4];
            return "<{$tag}{$cls}{$ids}>{$txt}</{$tag}>";
        },
//------- ...:{ TEXT }... NL change <br> tag in div-indent class
        '/\s\.\.\.(?:(\w+)){0,1}(!){0,1}\{\n(.+?)\n\}\.\.\.(?:\n|$)/s' => function ($m) {
            if($m[2]==='!') {
                $txt = nl2br($m[3]);
                // restore tag end after NL
                $txt = rtrim(str_replace("><br>\n",">\n",str_replace("<br />","<br>","{$txt}\n")));
            } else $txt = trim($m[3]);
            $cls = ($m[1]==='')?'indent':$m[1];
            return "\n<div class='$cls'>{$txt}</div>";
        },
//------- [check]{text} CHECKBOX MARK
        '/\[([^\]]*?)\]\{([^}]*?[^\\\\]|)\}/' => function ($m) {
            $chek = (is_bool_false($m[1])) ? '[ ]':'<b>[X]</b>';
            return " {$chek} {$m[2]}";
        },
//------- FORM parts
//  radio       => ^[name]@{checkitem:item1=val1,item2=val2,...}
//  checkbox    => ^[name]:{item1=val1:checked,item2=val2:checked,...}
//  textarea    => ^[name]!{text-value:col,row}
//  textbox     => ^[name]={text-value:size}
        '/(\s)\^\[(\w+){0,1}\]([@:!=])\{(.*?[^\\\\]|)\}/s' => function ($m) use(&$item_array) {
            $type = [ '@' => 'radio',':' => 'checkbox','=' => 'text','!' => 'textarea'];
            $spc = $m[1]; $kind = $m[3]; $val = $m[4];
            $vv = $type[$kind];
            $nm = (empty($m[2])) ? '' : " name='{$m[2]}'";
            $attr = " type='{$vv}'{$nm}";
            $tag = "<input{$attr}";
            switch($kind) {
            case '@':   // radio
                    list($check_val,$radio_items) = $item_array(':',$val,2);
                    $radio = ''; $cnt = 0;
                    foreach(explode(',',$radio_items) as $itemval) {
                        if(empty($itemval)) {
                            $radio .= '<br>';
                        } else {
                            list($radio_text,$radio_val) = $item_array('=',$itemval,2); 
                            if($radio_val===NULL) $radio_val = $cnt++;
                            $chk = ($check_val === $radio_val) ? ' checked':'';
                            $radio .= "{$tag}{$chk} value='{$radio_val}'>{$radio_text} ";
                        }
                    }
                    $tag = "{$spc}{$radio}";
                    break;
            case ':':   // checkbox
                    $checkbox = '';
                    foreach(explode(',',$val) as $itemval) {
                        if(empty($itemval)) {
                            $checkbox .= '<br>';
                        } else {
                            list($check_item,$checked) = $item_array(':',$itemval,2); 
                            list($check_text,$check_val) = $item_array('=',$check_item,2); 
                            $chk = (is_bool_false($checked)) ? '' : ' checked';
                            $checkbox .= "{$tag}{$chk} value='{$check_val}'>{$check_text} ";
                        }
                    }
                    $tag = "{$spc}{$checkbox}";
                    break;
            case '!':   // text area
                    $vv = explode(':',$val);
                    if(count($vv)===2) {
                        $size = explode(',',$vv[1]); 
                        $sz = " cols='{$size[0]}' rows='{$size[1]}'";
                    } else $sz = '';
                    // restore if ...{ TEXT }... mark converted.
                    $txt = rtrim(str_replace(["<br>\n","<br />\n"],"\n", "{$vv[0]}\n"));
                    $tag = "{$spc}<textarea{$attr}{$sz}>{$txt}</textarea>";
                    break;
            case '=':   // text
                    $vv = explode(':',$val);
                    $sz = (empty($vv[1])) ? '' : " size='{$vv[1]}'";
                    $tag = "{$spc}{$tag}{$sz} value='{$vv[0]}'>";
                    break;
            }
            return $tag;
        },
    ],$atext);
    // replace other PATTERN values
    $replace_keys   = array_keys($replace_defs);
    $replace_values = array_values($replace_defs);
    $atext = preg_replace($replace_keys,$replace_values, $atext);
    // Returns the escaped character to the character before escaping.
    $p = '/\\\([~\-_<>\^\[\]`*#|\(\.{}])/s';
    $atext = preg_replace_callback($p,function($matches) {return $matches[1];}, $atext);
    return "<div class='{$md_class}'>{$atext}</div>\n";
}
