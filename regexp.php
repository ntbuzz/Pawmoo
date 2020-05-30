<?php
require_once('Core/Common/appLibs.php');

$text = <<<'EOS'
変数: ${#varname.単純変数}
変数: {$varname$}単純変数
変数: {%varname%}単純変数
変数: $varname  単純変数
変数: $varname,単純変数
変数: ${#.管理ページ.言語} 日本語名
変数: {$単純変数$},日本語名
==============================-
    スタイルシート
.context-menu {
	display: none;
	position: absolute;
	margin: 0;
	padding: 2px;
	border: 1px solid gray;
	list-style-type: none;
	background: #EEE;
	font-size: 1em;
	box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.6);
    -moz-box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.6);
    -webkit-box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.6);
}
.context-menu > li {
	padding: 5px 20px 5px 10px;
	cursor: default;
}
.context-menu > li:hover {
	background: #06c;
	color: white;
}
.context-menu > li.disable {
	color:silver;
}
.hilight {
	border: 1px dashed blue;
}
.context-menu li.separate {
	color:silver;
	margin:0;
    padding:0;
    height:3px;
    border-top: 1px solid gray;
}
==============================-
    javascript
var selector = $(".popup-baloon");
selector.each(function () {
    var self = $(this); // jQueryオブジェクトを変数に代入しておく
    var ref = self.attr("data-element");  // 紐付けるID
    var act = ref.slice(0, 1);            // 先頭が＠ならmouseover
    if (act == "@") ref = ref.slice(1);
    var ev = (act == '@') ? "mouseover" : "click";
    if (ref != "") {
        var tag = ref.slice(0, 1);
        if (tag == "!") ref = ref.slice(1); // 先頭が！ならアイコン追加しない
        var icon = (tag == "!") ? ref : ref + "-help";
        if ($('#' + icon).length == 0) {
            $('#' + ref).append('<span class="help_icon" id="' + icon + '"></span>')
                .css('padding-right','22px');
            ev = 'mouseover';   // ポップアップイベントが登録されていることがあるので、強制的にマウスオーバーにする
        }
        ref = '#' + icon;
        if (ev == "click") $(ref).css("cursor", "help");
        $(ref).on(ev, function () {
            // バルーンを消すための領域を定義
            $('body').append('<div class="baloon-BK"></div>');
            $('.baloon-BK').fadeIn('fast');
            // バルーンコンテンツの表示位置をリンク先から取得して設定
            var x = $(ref).offset().left + ($(ref).innerWidth()/3);
            var y = $(ref).offset().top  + ($(ref).innerHeight()/2);
            if ((x + self.width()) > $(window).innerWidth()) {
                x = x - self.outerWidth() + $(ref).outerWidth();
                self.addClass("baloon-right");
            } else {
                self.addClass("baloon-left");
            }
            // マウス移動の範囲を配列に記憶する
            var bound = [ x, y, self.outerWidth(), self.outerHeight() ];
            self.css({'left': x + 'px','top': y + 'px'});
            self.fadeIn('fast');
            // バルーン領域以外をクリックしたらバルーンを消して領域を削除
            $('.baloon-BK').off().mousemove(function (e) {
                if (!bound.inBound(5, e.pageX, e.pageY)) {
                    // モーダルコンテンツとオーバーレイをフェードアウト
                    self.fadeOut('fast');
                    $('.baloon-BK').fadeOut('fast',function(){
                        // オーバーレイを削除
                        $('.baloon-BK').remove();
                    });
                }
            });
        });
    };
});
EOS;
$p = <<<'EOS'
/(?:^|[,\s]+)
((?:
"(?:[^"]|(?:\\\\)*\\")+"|
'(?:[^']|(?:\\\\)*\\')+'|
<(?:[^>]|(?:\\\\)*\\>)+>|
\((?:[^\)]+|(?:\\\\)*\\\))+\)|
{(?:[^}]|(?:\\\\)*\\})+}|
~(?:[^~]+|(?:\\\\)*\\~)+~|
(?:\/\/.*)|
[^,\s]+
)*)/x
EOS;

$p = <<<'EOS'
/(
\${[^}]+?}|
{\$[^\$]+?\$}|
{%[^%]+?%}
)/x
EOS;
/*
//$p = '/((?={%).+?\%})|((?={\$).+?\$})|((?=\${).+?})|((?=\$)[^,\s]+?})/s';
$p = '/(\${[^}]+?}|{\$[^\$]+?\$}|{%[^%]+?%})/';
$p = '/(\${[^}]+?}|{\$[^\$]+?\$}|{%[^%]+?%})/'; // 変数リストの配列を取得

echo "Pattern:{$p}\n";
preg_match_all($p,$text,$m);               // 全ての要素をトークン分離する
debug_dump(4,["preg_match" => $m]);
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
*/
$row = ['id' => 1,
       'title' => 'a',
        'contents' => '-BBB',
];

$sql = new SQLTest();

echo $sql->sql_makeWHERE($row)."\n";

class SQLTest {
    private $table = 'SQL';
//===============================================================================
// 配列要素からのWHERE句を作成
function sql_makeWHERE($row) {
    $sql = $this->makeOPR('AND', $row);
    if($sql !== '') $sql = ' WHERE '.$sql;
    return $sql;
}
//===============================================================================
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
