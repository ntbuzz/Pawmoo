<?php
require_once('Core/Common/coreLibs.php');
require_once('Core/Common/appLibs.php');

function __expr($OPR,$items) {
    $arr = [];
    foreach ($items as $val) $arr += $val;
    return [$OPR => $arr];
}
function _AND_(...$items) { return __expr('AND',$items); };
function _OR_(...$items) { return __expr('OR',$items); };
function _NOT_(...$items) { return __expr('NOT',$items); };

$row = _AND_(
        _OR_(['name1=' => 'val1'], ['name2<' => 'val2'], ['name3!=' => 'val3']),
        _AND_(['name_a=' => 'val_a', 'name_b>' => 'val_b','name_x' => 'val_x'], 
            _NOT_([ 'name_z<>' => 'val_z' ]))
        );
$row = [
    "1行目",
    [
        "ネスト1-1行目",
        [
            "ネスト2-1行目",
            "ネスト2-1行目",
        ],
        "ネスト1-2行目",
    ],
    "2行目",
];
//==============================================================================
// 排列要素を改行テキストに変換
$dump_text = function ($indent, $items)  use (&$dump_text)  {
    $txt = ''; $spc = str_repeat(' ', $indent);
    foreach($items as $key => $val) {
        $txt .= (is_array($val)) ? $dump_text($indent+2, $val) : "{$spc}{$val}\n";
    }
    return $txt;
};
debug_dump(5,[
    "配列" => $row,
    "implode" => implode("\n",$row),
    "変換" => $dump_text(0,$row),
    "ライブラリ" => array_to_text($row),
    ]);

/*
$dump_object = function ($opr,$items)  use (&$dump_object)  {
    $opc = '';
    foreach($items as $key => $val) {
        if(is_array($val)) {
            $opp = $dump_object($key,$val);
        } else {
            $opp = "({$key}={$val})";
        }
        $opc = (empty($opc)) ? $opp : "{$opc} {$opr} {$opp}";
    }
    if(count($items)===1) $opc = "{$opr} {$opc}";
    return "( {$opc} )";
};
*/
$dump_object = function ($opr,$items)  use (&$dump_object)  {
    $opc = '';
    foreach($items as $key => $val) {
        if(is_array($val)) {
            $opp = $dump_object($key,$val);
        } else {
            // キー名の最後に関係演算子
            list($key,$op) = keystr_opr($key);
            if(empty($op)) {
                $op = (gettype($val) === 'string') ? ' LIKE ' : '=';
            }
            if($val[0] == '-') {
                $val = mb_substr($val,1);
                $op = ' NOT LIKE ';
            }
            if(strpos($op,'LIKE') !== false) $val = "%{$val}%";
//            $opp = "({$this->table}.\"{$key}\"{$op}'{$val}')";
            $opp = "(TABLE.\"{$key}\"{$op}'{$val}')";
        }
        $opc = (empty($opc)) ? $opp : "{$opc} {$opr} {$opp}";
    }
    if(count($items)===1) $opc = "{$opr} {$opc}";
    return "( {$opc} )";
};

debug_dump(1,[ "論理式" => $row]);
echo $dump_object('',$row);
echo "\n";
exit;

$p = '/\n(\|[\s\S]+?\|)((\r\n){2}|\r{2}|\n{2})/s';
$atext = preg_replace_callback($p,function($maches) {
    $txt = $maches[1];
    $arr = array_map(function($str) {
        $tags = array(
            ':' => ['th','center'],
            '>' => ['td','right'],
            '<' => ['td','center']);
        $cols = explode("|", trim($str,"|\r\n"));   // 両側の|を削除して分割
        $ln = "";
        foreach($cols as $col) {
            if(array_key_exists($col[0],$tags)) {
                list($tag,$align) = $tags[$col[0]];
                $col = mb_substr($col,1);
            } else {
                $tag = 'td';
                $align = 'left';
            }
            $ln .= "<{$tag} align='{$align}'>{$col}</{$tag}>";
        }
        return "<tr>{$ln}</tr>";
    },explode("\n", $txt));         // とりあえず行に分割
    return "<table class='md_tbl'>".implode("\n",$arr)."</table>\n";
}, $text);
debug_dump(5,["INPUT" => $text,"MARKDOWN" => $atext]);

$p = '/\n(([\-\d][\s\.]|>\s)[\s\S]+?)((\r\n){2}|\r{2}|\n{2})/s';
$txt = preg_replace_callback($p,function($maches) {
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
}, $text);

debug_dump(5,["INPUT" => $text,"MARKDOWN" => $txt]);
exit;
preg_match_all($p,$atext,$m);               // 全ての要素をトークン分離する

$txt = pseudo_markdown($text);
//echo "INPUT ============\n{$text}";
//echo str_repeat("=", 50)."\n";
//echo "OUTPUT =====\n{$txt}";
debug_dump(5,["INPUT" => $text,"MARKDOWN" => $txt]);
exit;
/*
$family = function ($children) use (&$family, &$contents) {
    if (count($children) == 0) {
        return;
    } else {
        foreach ($children as $child) {
            // 前処理
            $nextChildren = get_children(array(
                'post_parent' => $child->ID,
                'post_type' => 'page',
            ));
            $family($nextChildren);
            // 後処理
        }
    }
};
$family($firstChildren);
*/
//$p = '/((?={%).+?\%})|((?={\$).+?\$})|((?=\${).+?})|((?=\$)[^,\s]+?})/s';
//$p = '/(\${[^}]+?}|{\$[^\$]+?\$}|{%[^%]+?%})/';
//$p = '/(\${[^}]+?}|{\$[^\$]+?\$}|{%[^%]+?%})/'; // 変数リストの配列を取得
$list_text = function($text,$pp) {
    $ln = explode("\n", $text);	// とりあえず行に分割
    $lc = 0;
    $list_array = function ($parent,$level) use (&$list_array, &$ln, &$lc) {
        $arr = "<{$parent}>\n";
        while(!empty($ll = $ln[$lc++])) {
            for($n=0;$ll[$n] === ' '||$ll[$n] === "\t";++$n) ;
            if($n === $level) {
                $ll = trim($ll);
                if($ll[0] !== '-' && mb_substr($ll,0,2) !== '1.') return "{$arr}</{$parent}>\n";
                $ll = trim(mb_substr($ll,2));
                $arr = "{$arr}<li>{$ll}</li>\n";
            } else if($n < $level) {
                --$lc;
                return "{$arr}</{$parent}>\n";
            } else {
                --$lc;
                $arr = "{$arr}" . $list_array($parent,$level+1);
            }
        }
        return "{$arr}</{$parent}>\n";
    };
    $quote_array = function ($level) use (&$quote_array, &$ln, &$lc) {
        $arr = "<blockquote>\n";
        while(!empty($ll = $ln[$lc++])) {
            $ll = ltrim(mb_substr($ll,$level));   // 先頭の > を削除
            if($ll[0] === '>' && $ll[1] === ' ') {
                --$lc;
                $arr = "{$arr}" . $quote_array($level+2);
            } else {
                $arr = "{$arr}{$ll}\n";
            }
        }
        return "{$arr}</blockquote>\n";
    };
    return ($pp==='blockquote') ? $quote_array(1) : $list_array($pp,0);
};
$p = '/\n(([\-\d][\s\.]|>\s)[\s\S]+?)((\r\n){2}|\r{2}|\n{2})/s';
echo "Pattern:{$p}\n";
preg_match_all($p,$text,$m);               // 全ての要素をトークン分離する
$token = $m[1];
debug_dump(4,["preg_match" => $token]);
foreach($token as $atext) {
//    echo "TEXT({$atext})\n";
    $tag = ($atext[0] === '>') ? 'blockquote' : (($atext[0] === '-') ? 'ul' : 'ol');
    $list_item = $list_text($atext,$tag);
    $text = str_replace($atext,$list_item,$text);
}
debug_dump(5,["TEXT" => $text]);
exit;

$lines = array_values(              // これはキーを連番に振りなおしてるだけ
    array_filter(                   // 文字数が0の行を取り除く
        array_map('trim',     // 各行にtrim()をかける
        explode("\n", $text)         // とりあえず行に分割
        ), 'strlen'));  // array_filter
foreach($lines as $line) {
    echo "LINE:{$line}\n";
    preg_match_all($p,$line,$m);               // 全ての要素をトークン分離する
    debug_dump(4,["preg_match" => $m]);
}

$row = ['id' => 1,
       'title' => 'a',
        'contents' => '-BBB',
];

$sql = new SQLTest();

echo $sql->sql_makeWHERE($row)."\n";

class SQLTest {
    private $table = 'SQL';
//==============================================================================
// 配列要素からのWHERE句を作成
function sql_makeWHERE($row) {
    $sql = $this->makeOPR('AND', $row);
    if(!empty($sql)) $sql = ' WHERE '.$sql;
    return $sql;
}
//==============================================================================
// 配列要素からのSQL生成
private function makeOPR($opr,$row) {
    $OP_REV = [ 'AND' => 'OR', 'OR' => 'AND'];
    $sql = '';
    $opcode = '';
    foreach($row as $key => $val) {
        if(is_array($val)) {
            $sub_sql = $this->makeOPR($OP_REV[$opr],$val);
            $sql .= "{$opcode}({$sub_sql})";
        } else {
            for($n=0;strpos('=<>',$val[$n]) !== false;++$n);
            if($n > 0) {
                $op = mb_substr($val,0,$n);
                $val = mb_substr($val,$n);
            } else {
                $op = (gettype($val) === 'string') ? ' LIKE ' : '=';
            }
            if($val[0] == '-') {
                $val = mb_substr($val,1);
                $op = ' NOT LIKE ';
            }
            if(strpos($op,'LIKE') !== false) $val = "%{$val}%";
            $sql .= "{$opcode}({$this->table}.\"{$key}\"{$op}'{$val}')";
        }
        $opcode = " {$opr} ";
    }
    return $sql;
}
}
