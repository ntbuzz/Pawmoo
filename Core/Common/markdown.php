<?php
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

//------------------------------------------------------------------------------
// multi pattern replace
    $atext = preg_replace_callback_array([
//------- HEAD(#) TAG
        '/^(#{1,6})(?:\.(\w+)){0,1} (.+?)$/m' => function($m) {
            $n = strlen($m[1]);
            $cls = ($m[2]==='')?'':" class='{$m[2]}'";
            return "<h{$n}{$cls}>{$m[3]}</h{$n}>\n";
        },
//------- ...{ TEXT }... NL change <br> tag in div-indent class
        '/\.\.\.(?:(\w+)){0,1}\{\n(.+?)\n\}\.\.\.\n/s' => function ($m) {
            $txt = nl2br(trim($m[2]));
            $cls = ($m[1]==='')?'indent':$m[1];
            return "<div class='$cls'>{$txt}</div>";
        },
//------- pre tag with class
        '/(```|~~~|\^\^\^)(?:(\w+)){0,1}(.+?)\n\1/s' => function ($m) {
            $class = [ '```' => 'code','~~~' => 'indent','^^^' => 'indent'];
            $txt = rtrim(str_replace(["<br>\n","<br />\n"],"\n", "{$m[3]}\n"));
            $cls = ($m[2]==='')?$class[$m[1]]:$m[2];
            return "<pre class='$cls'>{$txt}</pre>";
        },
//------- ..class#id{ TEXT } CLASS/ID attributed SPAN/P replacement
        '/\.\.(?:(\w+)){0,1}(?:#(\w+)){0,1}(:){0,1}\{([^\}]*?)\}/' => function ($m) {
            $cls = ($m[1]==='') ? '' : " class='{$m[1]}'";
            $ids = ($m[2]==='') ? '' : " id='{$m[2]}'";
            $tag = ($m[3]==='') ? 'span' : 'p';
            $txt = $m[4];
            return "<{$tag}{$cls}{$ids}>{$txt}</{$tag}>";
        },
//------- ![alt-text](URL) IMAGE TAG /multi-pattern replace
        '/!\[([^:\]]+)(?::(\d+,\d+)){0,1}\]\(([!:]){0,1}([-_.!~*\'()\w;\/?:@&=+\$,%#]+)\)/' => function ($m) {
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
        },
//------- CHECKBOX MARK
        '/\[([^\]]*?)\]\{([^}]+?)\}/' => function ($m) {
            $chek = (in_array(strtolower($m[1]),['','0','f','false']))?'[ ]':'<b>[X]</b>';
            return " {$chek} {$m[2]}";
        },
//------- FORM parts
//  radio       => ^.class#id[name]@{checkitem:item1,item2,item3}
//  checkbox    => ^.class#id[name]:{item1:checked}
//  textarea    => ^.class#id[name]!{text-value:col,row}
//  textbox     => ^.class#id[name]={text-value:size}
        '/\^(?:\.(\w+)){0,1}(?:#(\w+)){0,1}\[(\w+){0,1}\]([@:!=])\{([^}]+?)\}/' => function ($m) {
            $type = [ '@' => 'radio',':' => 'checkbox','=' => 'text','!' => 'textarea'];
            $cls = (empty($m[1])) ? '':" class='{$m[1]}'";
            $ids = (empty($m[2])) ? '':" id='{$m[2]}'";
            $nam = (empty($m[3])) ? '':" name='{$m[3]}'";
            $typ = $type[$m[4]];
            $tag = "<input{$cls}{$ids}{$nam} type='{$typ}'";
            $val = $m[5];
            switch($m[4]) {
            case '@':   // radio
                    $vv = explode(':',$val);
                    $radio = '';
                    if(count($vv)===2) {
                        $items = explode(',',$vv[1]); 
                        foreach($items as $item) {
                            $chk = ($item === $vv[0]) ? ' checked':'';
                            $radio .= "{$tag}{$chk}>{$item} ";
                        }
                    }
                    $tag = $radio;
                    break;
            case ':':   // checkbox
                    $vv = explode(':',$val);
                    $chk = (empty($vv[1])) ? '' : ' checked';
                    $tag .= "{$chk}>{$vv[0]}";
                    break;
            case '!':   // text area
                    $vv = explode(':',$val);
                    if(count($vv)===2) {
                        $size = explode(',',$vv[1]); 
                        $sz = " cols='{$size[0]}' rows='{$size[1]}'";
                    } else $sz = '';
                    $tag = "<textarea{$cls}{$ids}{$nam}{$sz}>{$vv[0]}</textarea>";
                    break;
            case '=':   // text
                    $vv = explode(':',$val);
                    $sz = (empty($vv[1])) ? '' : " size='{$vv[1]}'";
                    $tag .= "{$sz} value='{$vv[0]}'>";
                    break;
            }
            return $tag;
        },
    ],$atext);
/*
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
        '/\.\.(?:(\w+)){0,1}(?:#(\w+)){0,1}(:){0,1}\{([^\}]*?)\}/',
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
*/
    // replace other PATTERN values
    $replace_keys   = array_keys($replace_defs);
    $replace_values = array_values($replace_defs);
    $atext = preg_replace($replace_keys,$replace_values, $atext);
    // Returns the escaped character to the character before escaping.
    $p = '/\\\([~\-_<>\^\[\]`*#|\(\.{}])/s';
    $atext = preg_replace_callback($p,function($matches) {return $matches[1];}, $atext);
    return "<div class='{$md_class}'>{$atext}</div>\n";
}
