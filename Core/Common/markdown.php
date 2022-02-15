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
	if($md_class !== false) $md_class = get_class_names("easy_markdown.{$md_class}");
	$atext = pseudo_markdown_sub($atext);
    // Returns the escaped character to the character before escaping.
    $p = '/\\\([~\-_<>\^\[\]`*#|\(\.{}])/s';
    $atext = preg_replace_callback($p,function($matches) {return $matches[1];}, $atext);
    return ($md_class === false) ? $atext : "<div{$md_class}>{$atext}</div>\n";
}
//==============================================================================
// MarkDown Sub-Description for recusive-call
function pseudo_markdown_sub($atext) {
    $replace_defs = [
        "/\s\*\*(.+?)\*\*\s/" => '<strong>\\1</strong>',  // BOLD
        "/\s__(.+?)__\s/"     => '<em>\\1</em>',   // BOLD
        "/\s--(.+?)--\s/"   => '<del>\\1</del>', // STRIKEOUT
        "/\s\*(.+?)\*\s/"   => '<span style="font-style:italic;">\\1</span>',             // ITALIC
        "/\s_(.+?)_\s/"     => '<span style="text-decoration:underline;">\\1</span>',     // UNDERLINE
        "/(?: {2}$|　$)/m"   => '<br>',        // newline
    ];
    // The one to be converted with the highest priority.
    // <PRE>,CR-LF, \<TAG>, and <DL> tag
    $atext = preg_replace_callback_array([
        // pre tag with class
        '/(?:^|\n)(```|~~~|\^\^\^)([\w\-]+(?:\.[\w\-]+)*)?(.+?)\n\1/s' => function ($m) {
            $class = [ '```' => 'code','~~~' => 'indent','^^^' => 'indent'];
            $cls = ($m[2]==='')?$class[$m[1]]:get_class_names($m[2],false);
            $txt = ($cls==='code') ? htmlspecialchars($m[3]) : $m[3];
            return "\n<pre class='$cls'>{$txt}</pre>";
        },
        // escape the characters to be excluded, and Windows(CR-LF) style change to UNIX(LF) style.
        '/\s[ \-\=]>\s|\\\[<>]+\s|\\\<[^>\r\n]*?>|\r\n/' => function($m) {
            return str_replace(['\<','\>','<','>',"\r"],['&lt;','&gt;','&lt;','&gt;',''],$m[0]);
        },
        // DL processing
        '/\n(:.*?)\n\n/s' => function($m) {
            $dtdd = array_map(function($v) {
                preg_match('/^(.*?)(?=[ ,\n]+)(.*)$/s',$v,$match);
                $dd = $match[2];
                return "<dt>{$match[1]}</dt><dd>{$dd}</dd>";
            }, array_filter(explode(':',$m[1]),function($v) {return strlen($v)>0;}));
            return "<dl class='dl_list'>".implode("\n",$dtdd)."</dl>";
        }],$atext);
//------------------------------------------------------------------------------
// プロック要素を先に処理する
	$atext = preg_replace_callback_array([
//------- ..class#id(value){TEXT} CLASS/ID/VALUE attributed SPAN/P replacement
        '/(^|\s)\.\.([\w\-]+(?:\.[\w\-]+)*)?(?:#([\w\-]+))?(?:\(([^\)]+)\))?(:)?\{((?:\$\{[^\}\s]+\}|\\\}|.)*?)\}($|\s)/s' => function ($m) {
			list($mm,$pre,$cls,$ids,$val,$tag,$txt,$post) = $m;
            $cls = get_class_names($cls);
            $ids = ($ids==='') ? '' : " id='{$ids}'";
            $val = ($val==='') ? '' : " value='{$val}'";
            $tag = ($tag==='') ? 'span' : 'p';
			if($pre !== "\n") $pre = ' ';				// KEEP NL(\n), for previous line last DBL-SPC to <BR> tag
			if($post !== "\n") $post = '';				// KEEP NL(\n), for next line markdown
			if(strpos($txt ,'\\}')) $txt = str_replace('\\}','}',$txt);
            return "{$pre}<{$tag}{$cls}{$ids}{$val}>{$txt}</{$tag}>{$post}";
		},
//------- ...!{ TEXT }... NL change <br> tag in div-indent class
        '/(?:^|\s)\.\.\.([\w\-]+(?:\.[\w\-]+)*)?(!)?\{\n(.+?)\n\}\.\.\.(?:\n|$)/s' => function ($m) {
			$txt = pseudo_markdown_sub($m[3]);
            if($m[2]==='!') {
                $txt = nl2br($txt);
                // restore HTML-tag(</h1>) after <BR>
                $txt = rtrim(preg_replace('/(<\/h\d>)(?:<br>|<br \/>)\n/i',"\\1\n","{$txt}\n"));
            } else $txt = trim($txt);
            $cls = ($m[1]==='')?'indent':get_class_names($m[1],false);
            return "\n<div class='{$cls}'>{$txt}</div>";
		},
    ],$atext);
    // ul/ol/blockquote processing
    $p = '/(?:^|\n)(([\-\d][\s\.]|>\s)[\s\S]+?)(?:\n((?:\.[\w\-]+)*)\n|$)/s';
    $atext = preg_replace_callback($p,function($matches) {
        $txt = $matches[1];
		$usr_cls = (count($matches)===4) ? $matches[3] : '';
        $user_func = function($text) use(&$usr_cls) {
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
			if(!empty($usr_cls)) $ptagcls = get_class_names($usr_cls,false);
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
                    } else {
						$val = pseudo_markdown_sub($val);
						if($islist) {
                        	$res .= (is_array(next($array)))?"{$spc}<li>{$val}":"{$spc}<li>{$val}</li>";
                    	} else {
	                        $res .= "{$spc}{$val}<br>\n";
						}
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
    $p = '/(?:^|\n)(\|.+?\|)(?:\n((?:\.[\w\-]+)*)\n|$)/s';
    $atext = preg_replace_callback($p,function($matches) {
        // Combine lines that do not end with '|' as multiple lines
        $txt = preg_replace('/([^|])\n+/','\\1<br>', $matches[1]);
		$tbl_class = (isset($matches[2])) ? $matches[2] : '';
        $tbl_class = get_class_names("md_tbl{$tbl_class}");
        $col_row_span = function($key,$str) {
            $span_attr = ['@'=>'colspan','^'=>'rowspan'];
            $bind = '';
            if(!empty($str)) {
                $bval = $span_attr[$key];
                $ll = str_replace($key,'',$str);
                if(empty($ll)) $clen = substr_count($str,$key);
                else $clen = intval($ll);
                if($clen > 0) $bind = " {$bval}='{$clen}'";
            }
            return $bind;
        };
        $arr = array_map(function($str) use(&$col_row_span) {
            $cols = explode("|", trim($str,"|"));
            $ln = '';
            $tags = [ '<' => 'left','>' => 'right','=' => 'center'];
            foreach($cols as $col) {
                // maybe additional calss and colspan/rowspan
                preg_match('/^(:)?([<>=])?(?:(@\d+|@+)|(\^\d+|\^+))*((?:\.[\w\-]+)*)(?:#(\d+%?))?/',$col,$m);
                $style = $attrs = '';
                $tag = 'td';
                switch(count($m)) {
                case 7: $wd = (substr($m[6],-1)==='%') ? $m[6] : "{$m[6]}px";
                        $style .= ($m[6]==='') ? '':"width:{$wd};";
                case 6: $attrs .= get_class_names($m[5]);
                case 5: $attrs .= $col_row_span('^',$m[4]);
                case 4: $attrs .= $col_row_span('@',$m[3]);
                case 3: if(!empty($m[2])) {
                            $ali = $tags[$m[2]];
                            $style .= "text-align:{$ali};";
                        }
                case 2: if(!empty($m[1])) $tag = 'th';
                        $col = mb_substr($col,strlen($m[0]));   // for all case
                        break;
                }
                if(!empty($style)) $style =" style='{$style}'";
				$col = pseudo_markdown_sub($col);
                $ln .= "<{$tag}{$attrs}{$style}>{$col}</{$tag}>";
            }
            return "<tr>{$ln}</tr>";
        },explode("\n", $txt));
        return "<table{$tbl_class}>".implode("\n",$arr)."</table>\n";
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
        '/^(#{1,6})((?:\.[\w\-]+)*) (.+?)$/m' => function($m) {
            $n = strlen($m[1]);
            $cls = get_class_names($m[2]);
            return "<h{$n}{$cls}>{$m[3]}</h{$n}>";
        },
        '/^(?:---|___|\*\*\*)$/m'     => function($m) { return "<hr>"; },
//------- ![alt-text](URL) IMAGE TAG /multi-pattern replace
        '/!\[([^:\]]+)(?::(\d+,\d+))?\]\(([!:])?([^\)]+)\)/' => function ($m) use(&$item_array) {
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
			if(empty($src)) return '';
            return "<img src='{$src}' alt='{$alt}'{$sz} />";
        },
//------- [text](URL) HYPER LINK
//			!(URL)	Target = NEW
//			^(URL)	Target = TOP
//			:(URL)	Target = SELF
//			@(URL)	window.open = @(URL,width,height,scrollbars~
		'/\[([^\]]+)\]([!\^:@])?\(([^\)]+)\)/' => function($m) {
			$txt = $m[1];
			$arr = str_explode([' ',','],$m[3]);		// allow user attribute
			$url = array_shift($arr);
            switch($m[2]) {
            case '!': $arr[] = 'target="_blank"'; break;
            case '^': $arr[] = 'target="_top"'; break;
            case ':': $arr[] = 'target="_self"'; break;
			case '@':
					$nm = array_shift($arr);
					$param = array_items_list(
								array_combine(['width','height','scrollbars'],
									array_alternative($arr,3,[800,800,'yes'])));
					$href = make_hyperlink($url);
					$run = "window.open('{$href}','{$nm}','{$param}')";
					$arr = [ "target=\"_new\" onClick=\"{$run};return false;\"" ];
					$url = '#';
            }
			if(empty($url)) return '';
			$url = make_hyperlink($url);
			$attr = empty($arr) ? '': ' '.implode(' ',$arr);
			return "<a href='{$url}'{$attr}>{$txt}</a>";
		},
//------- [check]{text} CHECKBOX MARK
        '/\[([^\]]*?)\]\{([^\}]*?[^\\\\])\}/' => function ($m) {
            $chk = (is_bool_false($m[1])) ? 'off':'on';
            $chek = "<span class='checkbox-{$chk}'>{$m[2]}</span>";
            return $chek;
        },
//------- FORM parts
//  radio       => ^[name]@{checkitem:item1=val1,item2=val2,...}
//  checkbox    => ^[name]:{item1=val1:checked,item2=val2:checked,...}
//  textarea    => ^[name]!{text-value:row,col}
//  textbox     => ^[name]={text-value:size}
//  passbox     => ^[name]*{text-value:size}
//  select      => ^[name]%{select-val:option1=val1,option2=val2,...}
//  combobox    => ^[name]+{select-val:option1=val1,option2=val2,...:size}
//		Grant class & id name,description between '^' and '[', like ^class#id[name]...
        '/(\s)\^([\w\-\.]+)?(?:#([\w\-]+))?\[([\w\-]+)?\]([@:!=#%+])\{((?:\$\{[^\}\s]+\}|\\\}|.)*?)\}/s' => function ($m) use(&$item_array) {
            $type = [ '@' => 'radio',':' => 'checkbox','=' => 'text','#' => 'password','!' => 'textarea','%' => 'select','+' => 'combo'];
			list($tmp,$spc,$cls,$id,$nm,$kind,$val) = $m;
			if(strpos($val,'\\}')) $val = str_replace('\\}','}',$val);
            $vv = $type[$kind];
			$attr_nm = (empty($nm)) ? '' : " name='{$nm}'";
			if(!empty($cls)) {
				$cls = trim(str_replace('.',' ',$cls));
				$attr_nm = "{$attr_nm} class='{$cls}'";
			}
			if(!empty($id)) $attr_nm = "{$attr_nm} id='{$id}'";
            $attr = " type='{$vv}'{$attr_nm}";
            $tag = "<input{$attr}";
            switch($kind) {
            case '+':   // combobox = select + textbox
                    list($sel_val,$opt_items,$sz) = $item_array(':',$val,3);
					$opt_list = array_associate_convert($opt_items);
					$tag = make_combobox($sel_val,$opt_list,$sz,$nm);
                    break;
            case '%':   // select
                    list($sel_val,$opt_items) = $item_array(':',$val,2);
                    $opt_list = ''; $cnt = 0;
                    foreach(explode(',',$opt_items) as $itemval) {
                        if(!empty($itemval)) {
                            list($opt_text,$opt_val) = $item_array('=',$itemval,2); 
                            if($opt_val===NULL) $opt_val = $cnt++;
                            $sel = ($sel_val === $opt_val) ? ' selected':'';
                            $opt_list .= "<option value='{$opt_val}'{$sel}>{$opt_text}</option>\n";
                        }
                    }
                    $tag = "{$spc}<select{$attr_nm}>{$opt_list}</select>\n";
                    break;
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
                    $tag = "{$spc}<label>{$radio}</label>";
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
                    $tag = "{$spc}<label>{$checkbox}</label>";
                    break;
            case '!':   // text area
                    $vv = explode(':',$val);
                    if(count($vv)===2) {
						list($rows,$cols) = fix_explode(',',$vv[1],2);
						$wd = array_filter(['rows'=>$rows,'cols'=>$cols],'strlen');
						$sz = array_items_list(attr_sz_xchange($wd),' ');
                    } else $sz = '';
					if(!empty($sz)) $sz = " {$sz}";
                    // restore if ...{ TEXT }... mark converted.
                    $txt = rtrim(preg_replace('/(?:<br>|<br \/>)\n/i',"\n","{$vv[0]}\n"));
                    $txt = htmlspecialchars($txt);
                    $tag = "{$spc}<textarea{$attr}{$sz}>{$txt}</textarea>";
                    break;
            case '=':   // text
                    $vv = explode(':',$val);
                    $sz = (empty($vv[1])) ? '' : (' '.array_items_list(attr_sz_xchange(['size'=>$vv[1]]),' '));
                    $tag = "{$spc}{$tag}{$sz} value='{$vv[0]}' />";
                    break;
            case '#':   // password
                    $vv = explode(':',$val);
                    $sz = (empty($vv[1])) ? '' : (' '.array_items_list(attr_sz_xchange(['size'=>$vv[1]]),' '));
                    $tag = "{$spc}{$tag}{$sz} value='{$vv[0]}' />";
                    break;
            }
            return $tag;
        },
	],$atext);
    // replace other PATTERN values
    $replace_keys   = array_keys($replace_defs);
    $replace_values = array_values($replace_defs);
    return preg_replace($replace_keys,$replace_values, $atext);
}
