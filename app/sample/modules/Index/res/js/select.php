<?php
/*
    このPHPファイルはリソース内で展開されるので、データベースは参照できない
    モジュールクラス内でセッション変数に保存した値を参照できるのでそれを使う
*/
// ContentView.tpl の設定と同じものを定義し、common.js の中に組み込むときは
//  template.mss へ登録し、eContentView.tpl の同じものを削除する
// 
$sel_table = <<<EOS
var objList = {
    'top-select': [
        [  1, "PHPフレームワークなら"   ],   // レベル0
        [  2, "プログラミング言語なら"  ],
        [  3, "ゲームアプリなら", 		],
        [  5, "仕事なら",       		],
    ],
    'sub-select': [
        [ 10, "Pawmooだねδ(^.^;)",  1],   // レベル1
        [ 11, "絶対CakePHP！",      1],
        [ 12, "Laravel知らんの!?",	1],
        [ 13, "Symphony4で決まり",	1],
        [ 14, "Slimだろ",       	1],
        [ 15, "CodeIgnizer使え",   	1],
        [ 16, "FuelPHPでごわす", 	1],
        [ 17, "ObjectPascalは神！", 2],
        [ 18, "Pythonじゃね？",     2],
        [ 19, "PHP忘れんな！",      2],
        [ 20, "C言語はもう古い",  	2],
        [ 21, "黒猫プロジェクト",  	3],
        [ 22, "原神",  				3],
        [ 23, "ストリートBOMⅡ",	3],
        [ 24, "インベーダー",  		3],
        [ 25, "マリオカート",  	3],
        [ 26, "Office365",  	5],
    ],
};
$('#fav-list').ChainSelect(objList,10,function(v,t) {
	alert("「"+t+"」("+v+") を選びました！");
});
EOS;

echo $sel_table;