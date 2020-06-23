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
function trim_delsp($a) {
    return trim(preg_replace('/\s+/', ' ', str_replace('　',' ',$a)));
}
//==============================================================================
function json_escape($a) {
    $vv = str_replace(["\\","/","\r\n", "\r", "\n","\"","\t"],["\\\\","\\/","\\n","\\n","\\n","\\\"","\\t"], $a);
    return $vv;
}
//==============================================================================
// テキストを分割した配列
function text_line_split($del,$txt) {
    $array = array_values(              // これはキーを連番に振りなおしてるだけ
        array_filter(                   // 文字数が0の行を取り除く
            array_map('trim_delsp',     // 各行にtrim()をかける
            explode($del, $txt)         // とりあえず行に分割
            ), 'strlen'));  // array_filter
    return $array;
}
//==============================================================================
// MarkDownもどきのパーサー
// テーブルは処理の都合上、独自書式でサポート
function pseudo_markdown($atext) {
    $replace_defs = [
        "/\[([^\]]+)\]\(([-_.!~*\'()a-z0-9;\/?:\@&=+\$,%#]+)\)/i" => '<a target="_blank" href="\\2">\\1</a>',
        "/^(---|___|\*\*\*)$/m"     => "<hr>",        // 水平線
        "/^#\s(.+?)$/m"     => "<h1>\\1</h1>",        // 見出し1
        "/^##\s(.+?)$/m"    => "<h2>\\1</h2>",        // 見出し2
        "/^###\s(.+?)$/m"   => "<h3>\\1</h3>",        // 見出し3
        "/^####\s(.+?)$/m"  => "<h4>\\1</h4>",        // 見出し4
        "/^#####\s(.+?)$/m" => "<h5>\\1</h5>",        // 見出し5
        "/^######\s(.+?)$/m"=> "<h6>\\1</h6>",        // 見出し6
        "/\*\*(.+?)\*\*/"   => '<strong>\\1</strong>', // 強調
        "/\*(.+?)\*/"   => '<em>\\1</em>',             // 強調
        "/```(?:\r\n|\r|\n)(.+?)```/s"     => '<pre class="code">\\1</pre>',      // code
        "/```(([a-z]+?)(?:\r\n|\r|\n))(.+?)```/s"     => '<pre class="\\2">\\3</pre>',      // code
        "/(\s{2}|　)$/m"     => "<br>",               // 改行
        "/([-=])>/"     => "\\1&gt;",                 // タグ
    ];
    $replace_keys   = array_keys($replace_defs);
    $replace_values = array_values($replace_defs);
// 先に複数行のタグ変換を処理しておく
    // リストと引用を処理を処理する
    $p = '/\n(([\-\d][\s\.]|>\s)[\s\S]+?)((\r\n){2}|\r{2}|\n{2})/s';
    $atext = preg_replace_callback($p,function($maches) {
        $tags = array(
            '- ' => ['ul','ul_list',true],
            '1.' => ['ol','ol_list',true],
            '> ' => ['blockquote','bq_block',false]);
        $txt = $maches[1];
        list($ptag,$ptagcls,$islist) = $tags[mb_substr($txt,0,2)];
        $pcls = "<{$ptag} class='{$ptagcls}'>\n";
        $lvl = 0;
        if($islist) {
            $maptext = "{$pcls}{$txt}\n</{$ptag}>";
            $arr = array_map(function($str) use (&$lvl, &$ptag, &$pcls) {
                for($n=0;ctype_space($str[$n]);++$n) ;
                if(!in_array(mb_substr($str,$n,2), ['- ','1.','> '])) return "{$str}";
                $pretag = ($n < $lvl) ? "</{$ptag}>\n":(($n > $lvl) ? $pcls : '');
                $lvl = $n;
                $ll = ltrim(mb_substr($str,$n+2));
                return "{$pretag}<li>{$ll}</li>";
            },explode("\n", $maptext));         // とりあえず行に分割
        } else {    // blockquote
            $arr = array_map(function($str) use (&$lvl, &$ptag, &$pcls) {
                for($n=0;$str[$n]==='>';++$n) ;
                if($n === 0 && $str[0] !== '>') return "TERM:{$n}:{$str}";
                $ll = ltrim(mb_substr($str,$n));   // 先頭の > を削除
                $pretag = ($n === $lvl) ? '' : (
                          ($n > $lvl) ? str_repeat("{$pcls}", $n - $lvl) :
                          str_repeat("</{$ptag}>\n", $lvl - $n));
                $lvl = $n;
                return "{$pretag}{$ll}<br>";
            },explode("\n", $txt));         // とりあえず行に分割
            // ネスト分を閉じる
            array_push($arr,str_repeat("</{$ptag}>\n", $lvl));
        }
        return implode("\n",$arr);
    }, $atext);
    // テーブルを変換
    $p = '/\n(\|[\s\S]+?\|)((\r\n){2}|\r{2}|\n{2})/s';
    $atext = preg_replace_callback($p,function($maches) {
        $txt = $maches[1];
        $arr = array_map(function($str) {
            $tags = array(':' => ['th','center'],'>' => ['td','right'],'<' => ['td','left']);
            $cols = explode("|", trim($str,"|\r\n"));   // 両側の|を削除して分割
            $ln = "";
            foreach($cols as $col) {
                $is_attr = array_key_exists($col[0],$tags);
                $vars = ($is_attr) ? $tags[$col[0]] : ['td','center'];
                if(($is_attr)) $col = mb_substr($col,1);
                list($tag,$align) = $vars;
                $ln .= "<{$tag} align='{$align}'>{$col}</{$tag}>";
            }
            return "<tr>{$ln}</tr>";
        },explode("\n", $txt));         // とりあえず行に分割
        return "<table class='md_tbl'>".implode("\n",$arr)."</table>\n";
    }, $atext);
    // 残りを一気に置換する
    $atext = preg_replace($replace_keys,$replace_values, $atext);
    return "<div class='easy_markdown'>{$atext}</div>";
}
//==============================================================================
function get_protocol($href) {
    $n = strpos($href,':');
    if($n === FALSE) return NULL;
    return ($n > 3) ? mb_substr($href,0,$n) : NULL;
}
//==============================================================================
// ハイパーリンク生成
//  http〜  直接指定
// :...     /...
// /..      sysRoot/...
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
// デバッグダンプ
function mark_active_words($atext,$word,$class) {
	$ln = array_values(array_map('trim', explode("\n", $atext)));
	$ret = array();
	foreach($ln as $ll) {
    	$ll = preg_replace("/(${word})/i","<span class='{$class}'>\\1</span>", $ll);
		$ret[] = $ll;
	}
	return implode("\n",$ret);
}
