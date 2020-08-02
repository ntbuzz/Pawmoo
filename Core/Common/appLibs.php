<?php
//==============================================================================
// 拡張子をとりだす
// 返り値は .拡張子
function extract_extension($fn) {
    $nn = strrpos($fn,'.');                     // ファイル名の拡張子を確認
    return ($nn === FALSE) ? '' :               // 拡張子が無いときに備える
            substr($fn,$nn+1);  // 拡張子を分離
}
//==============================================================================
// ファイルパスを分解する
// 返り値は array(ファイル名,拡張子)
function extract_base_name($fn) {
    $nn = strrpos($fn,'.');                     // ファイル名の拡張子を確認
    if($nn === FALSE) $nn = strlen($fn);        // 拡張子が無いときに備える
    $ext = substr($fn,$nn+1);                   // 拡張子を分離
    $fn = substr($fn,0,$nn);                    // ファイル名を切り落とす
    return array($fn,$ext);
}
//==============================================================================
// ファイルパスを分解する
// 返り値は array(パス,ファイル名,拡張子)
function extract_path_file_ext($path) {
    list($path,$fn) = extract_path_filename($path);    // パスとファイル名に分解
    list($fn,$ext) = extract_base_name($fn);    // ファイル名と拡張子に分解
    return array($path,$fn,$ext);
}
//==============================================================================
// ファイルパスを分解する
// 返り値は array(パス,ファイル名)
function extract_path_filename($path) {
    if(mb_substr($path,-1) == '/') {
        $path = substr($path,0,strlen($path)-1);     // 末尾の /  は削除
    }
    $nn = strrpos($path,'/');    // ファイル名を確認
    if($nn === FALSE) {
        $fn = $path;
        $path = '';
    } else {
        $fn = substr($path,$nn+1);     // 拡張子を分離
        $path = substr($path,0,$nn+1);
    }
    if(mb_substr($path,-1) !== '/') $path .= '/';     // パスの最後は /で終わらせる
    return array($path,$fn);
}
//==============================================================================
// 文字列の固定長サイズ
// $w > 0 = 左側から固定長, $w < 0 = 右側から固定長
function str_fixwidth($exp,$pad,$w) {
    if($w < 0) {
        $pp = str_repeat ($pad,-$w);
        return substr("{$pp}{$exp}",$w);
    }
    $pp = str_repeat ($pad,$w);
    return substr("{$exp}{$pp}",0,$w);
}
//==============================================================================
// 移動先のフォルダ存在を確かめてファイル移動
function file_move($src,$dest){
    list($path,$fn) = extract_path_filename($dest);       // 移動先をパスとファイル名に分解
    if(!file_exists($path)) mkdir($path);           // 移動先のフォルダがなければ作成する
    return rename($src,$dest);                      // ファイル移動
}
//==============================================================================
// ファイルサイズ単位
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
// 配列内の文字列チェック
function instr_array($str,$hayz) {
    foreach($hays as $val) {
        if(strpos($str,val) !== false) return TRUE;
    }
    return FALSE;
}
//==============================================================================
// 配列の深さ計算、短さ優先1行バージョン
function array_depth($a, $c = 0) {
    return (is_array($a) && count($a))
          ? max(array_map("array_depth", $a, array_fill(0, count($a), ++$c)))
          : $c;
  }
//==============================================================================
// 配列の結合 array_merge() の代替え、無名要素も上書きする
function array_override($a, $b) {
    foreach($b as $key => $val) {
        $a[$key] = $val;            // 要素を上書きするか追加する
    }
    return $a;          //  結合した配列を返す
  }
//==============================================================================
// テキストの改行を変換
function text_to_html($atext) {
    return nl2br(htmlspecialchars($atext));
}
//==============================================================================
// HTMLタグの出力
function echo_safe($atext,$is_safe = TRUE) {
    echo ($is_safe) ? htmlspecialchars($atext) : $atext;
}
//==============================================================================
function json_escape($a) {
    $vv = str_replace(["\\","/","\r\n", "\r", "\n","\"","\t"],["\\\\","\\/","\\n","\\n","\\n","\\\"","\\t"], $a);
    return $vv;
}
//==============================================================================
// テキストを分割した配列
function text_line_split($del,$txt,$trim = FALSE) {
    $array = array_values(              // これはキーを連番に振りなおしてるだけ
            array_filter(                   // 文字数が0の行を取り除く
                array_map(function($a) {return trim(preg_replace('/\s+/', ' ', str_replace('　',' ',$a)));},
                    explode($del, $txt)         // とりあえず行に分割
            ), ($trim) ? 'strlen' : function($a) { return TRUE;}
        ));
    return $array;
}
//==============================================================================
// 配列要素を改行テキストに変換
function array_to_text($array,$sep = "\n") {
    $dump_text = function ($indent, $items)  use (&$dump_text,&$sep)  {
        $txt = ''; $spc = str_repeat(' ', $indent);
        foreach($items as $key => $val) {
            if(is_array($val)) {
                $txt .= $dump_text($indent+2, $val);
            } else {
                $txt .= (is_numeric($key)) ? "{$spc}{$val}{$sep}" : "{$spc}{$key}={$val}{$sep}" ;
            }
        }
        return trim($txt,$sep);
    };
    return (is_array($array)) ? $dump_text(0,$array) : $array;
}
//==============================================================================
// MarkDownもどきのパーサー
// テーブルは処理の都合上、独自書式でサポート
function pseudo_markdown($atext) {
    $replace_defs = [
        '/\n```([a-z]+?)\n(.+?)\n```/s' => "\n<pre class=\"\\1\">\n\\2</pre>\n",    // class name
        '/\n```\n(.+?)\n```/s'          => "\n<pre class=\"code\">\n\\1</pre>\n",   // code
        '/\n~~~\n(.+?)\n~~~/s'          => "\n<pre class=\"indent\">\n\\1</pre>\n",  // indent block
        "/!\[([^\]]+)\]\(([-_.!~*\'()a-z0-9;\/?:\@&=+\$,%#]+)\)/i"   => '<img src="\\2" alt="\\1">',
        "/\[([^\]]+)\]\(([-_.!~*\'()a-z0-9;\/?:\@&=+\$,%#]+)\)/i"    => '<a target="_blank" href="\\2">\\1</a>',
        "/^(---|___|\*\*\*)$/m"     => "<hr>",       // <HR>
        "/^# (.+?)$/m"     => "<h1>\\1</h1>",        // <H1>
        "/^## (.+?)$/m"    => "<h2>\\1</h2>",        // <H2>
        "/^### (.+?)$/m"   => "<h3>\\1</h3>",        // <H3>
        "/^#### (.+?)$/m"  => "<h4>\\1</h4>",        // <H4>
        "/^##### (.+?)$/m" => "<h5>\\1</h5>",        // <H5>
        "/^###### (.+?)$/m"=> "<h6>\\1</h6>",        // <H6>
        "/\s\*\*(\S+?)\*\*\s/" => '<strong>\\1</strong>',  // BOLD
        "/\s__(\S+?)__\s/"     => '<em>\\1</em>',   // BOLD
        "/\s--(\S+?)--\s/"   => '<del>\\1</del>', // STRIKEOUT
        "/\s\*(\S+?)\*\s/"   => '<span style="font-style:italic;">\\1</span>',             // ITALIC
        "/\s_(\S+?)_\s/"     => '<span style="text-decoration:underline;">\\1</span>',     // UNDERLINE
        "/([^ ]) {2}$/m"     => '\\1<br>',        // newline
    ];
    // 先にタグ文字のエスケープとCR-LFをLFのみに置換しておく
    $p = '/\s[ \-\=]>\s|\\\[<>]+\s|\\\<[^>\r\n]*?>|\r\n/';
    $atext = preg_replace_callback($p, function($maches) {
                return str_replace(['\<','\>','<','>',"\r"],['&lt;','&gt;','&lt;','&gt;',''],$maches[0]);}
            ,$atext);
    // リストと引用を処理を処理する
    $p = '/\n(([\-\d][\s\.]|>\s)[\s\S]+?)\n{2}/s';
    $atext = preg_replace_callback($p,function($maches) {
        $txt = $maches[1];
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
                        $ll = ltrim(mb_substr($str,$n+2));
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
                        $res .= "{$spc}{$val}\n";
                    }
                }
                return $res;
            };
            $tag_body = $ul_text($arr[$key],0);
            return "{$ptag_start}{$tag_body}{$ptag_close}\n";
        };
        return $user_func($txt);
    }, $atext);
    // テーブルを変換
    $p = '/\n(\|[\s\S]+?\|)\n{2}/s';
    $atext = preg_replace_callback($p,function($maches) {
        // | で終わらない行は複数行として結合しておく
        $txt = preg_replace('/([^|])\n/','\\1<br>', $maches[1]);
        $arr = array_map(function($str) {
            $cols = explode("|", trim($str,"|\r\n"));   // 両側の|を削除して分割
            $ln = "";
            $tags = [ '<' => 'left','>' => 'right','=' => 'center'];
            foreach($cols as $col) {
                if($col[0]===':') {     // TH cell
                    $col = mb_substr($col,1);
                    $tag = 'th';
                } else $tag = 'td';
                if(array_key_exists($col[0],$tags)) {
                    $ali = $tags[$col[0]];
                    $vars = " style='text-align:{$ali};'";
                    $col = mb_substr($col,1);
                } else $vars = '';
                $ln .= "<{$tag}{$vars}>{$col}</{$tag}>";
            }
            return "<tr>{$ln}</tr>";
        },explode("\n", $txt));         // とりあえず行に分割
        return "<table class='md_tbl'>".implode("\n",$arr)."</table>\n";
    }, $atext);
    // 残りを一気に置換する
    $replace_keys   = array_keys($replace_defs);
    $replace_values = array_values($replace_defs);
    $atext = preg_replace($replace_keys,$replace_values, $atext);
    //エスケープ文字を置換
    $p = '/\\\([~\-_<>\[\]`*#|\(\)])/s';
    $atext = preg_replace_callback($p,function($maches) {return $maches[1];}, $atext);
    return "<div class='easy_markdown'>{$atext}</div>\n";
}
//==============================================================================
// プロトコルで始まっているか確認
function get_protocol($href) {
    $n = strpos($href,':');
    if($n === FALSE) return NULL;
    return ($n > 3) ? mb_substr($href,0,$n) : NULL;
}
//==============================================================================
// ハイパーリンク生成
//  http〜  直接指定
// :...     /...
// /..     sysRoot/...
// ./...    appRoot/modname/...
// ...      appRoot/...
function make_hyperlink($lnk,$modname) {
    if(get_protocol($lnk) === NULL) {
		if($lnk[0] === ':') {
            $lnk[0] = '/';
        } else if($lnk[0] === '/') {
			$lnk = App::Get_SysRoot("{$lnk}");
        } else if(mb_substr($lnk,0,2) === './') {
            $lnk = substr_replace($lnk, strtolower($modname), 0, 1);
            $lnk = App::Get_AppRoot($lnk);
        } else {
            $lnk = App::Get_AppRoot($lnk);
		}
    }
    return $lnk;
}
//==============================================================================
// 単語を span タグでマーキングする
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
//  論理演算用の配列生成
function _opr($OPR,...$items) {
    $arr = [];
    foreach($items as $val) $arr += $val;
    return [$OPR => $arr];
}
//==============================================================================
// OPENSSLを使った暗号化
function passwd_encrypt($str) {
    $method_name = 'AES-256-CBC';
    $key_string = '_minimvc_biscuit';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method_name));
    return openssl_encrypt($str,$method_name,$key_string,0,$iv);
}
