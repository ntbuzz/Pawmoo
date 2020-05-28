// ツールバー
.font20#toolbar => [
	.rightitem => [
		.finder => [
			<input type="text" class="font16" id="c00" size="20" name="cc">
			"span.font24#find_word" => [ +img => [ attr => value  "images/find_icon.png" ] ]
		]
		"span.font24#popup-dialog" => [ ≡ ]
	]
	+ul#menu => [
		[ %${#Toolbar.home} => "#" ]		 //  ハイパーリンク
		.menuitem => [ ${#Toolbar.ドキュメント}
			+ul.sub => [
				.subitem#part_edit => [ ${#Toolbar.ドキュメント.パート編集} ]
				.subitem#part_add => [ ${#Toolbar.ドキュメント.パート追加} ]
				.subitem#part_del => [ ${#Toolbar.ドキュメント.パート削除} ]
				.subitem#chap_edit => [ ${#Toolbar.ドキュメント.チャプター編集} ]
				.subitem#chap_add => [ ${#Toolbar.ドキュメント.チャプター追加} ]
				.subitem#chap_del => [ ${#Toolbar.ドキュメント.チャプター削除} ]
				.subitem#text_downld => [ ${#Toolbar.ドキュメント.テキスト変換} ]
			]
		]
		.menuitem => [ ${#Toolbar.管理ページ}
			"+ul.sub global" => [
				[ %${#Toolbar.管理ページ.トップ} => ":" ]
				[ %${#Toolbar.管理ページ.PHP情報} => ":phpinfo.php" ]
				[ %${#Toolbar.管理ページ.SQLite} => ":SQLiteManager" ]
				[ %${#Toolbar.管理ページ.PostgreSQL} => ":phpPgAdmin" ]
			]
		]
		.menuitem => [ ${#Toolbar.ヘルプ}
			"+ul.sub" => [
				.subitem#about_info => [ ${#Toolbar.ヘルプ.バージョン} ]
				[ %${#Toolbar.ヘルプ.問合わせ} => "#" ]
			]
		]
	]
]
