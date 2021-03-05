//  セクション・レイアウト定義
@Header => [
	 PageTitle => ${#TITLE}
	 AdditionHeader => [
		 ./css/common.css
		 ./js/common.js
	]
]
-body =>  [ bgcolor => "white" ]       // HTMLタグ出力
@BlogHeader
.appWindow => [    // タグ名省略は DIVタグセクション
	.blogBody.fitWindow => [
		.leftBlock.stickyBar => [
			@Calendar
		]
		.blogMain => [
			+echo => "${:row_number}/${:record_max}"
			.blog_item => [ id => ${@@id}
				&BlogHeading
				.blog_body => [
					&BlogBody
				]
			]
			<hr>
    		span.rightitem => [ %▲TOP => './' ]
		]
		.rightBlock => [
			@SideMenu
		]
	]
	p#move-top => ▲
]
// select.php リソースを使うときはここのスクリプトを削除する
+jquery => ~
var objList = {
    'top-select': [
        [  1, "フレームワークなら"      ],   // レベル0
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
~

// #popup-dialog のマウスオーバーバルーンヘルプ
.popup-baloon{@popup} => [
	ポップアプバルーンです。
]

@Footer
