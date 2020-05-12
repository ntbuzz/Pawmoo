// ツールバー
.font20#toolbar => [
	.rightitem => [
		<input type="text" class="font16" id="c00" size="20" name="cc">
		"span.font24#popup-dialog" => [ ≡ ]
[name] => [ aaaaa ]
	]
	+ul#menu => [
		[ %${#Toolbar.home} => "/index" ]		 //  ハイパーリンク
		.menuitem => [ ${#Toolbar.ドキュメント}
			+ul.sub => [
				[ %${#Toolbar.ドキュメント.パート追加} => "#" ]
				[ %${#Toolbar.ドキュメント.チャプター追加} => "#" ]
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
				.menuitem#about_info => [ ${#Toolbar.ヘルプ.バージョン} ]
				[ %${#Toolbar.ヘルプ.問合わせ} => "#" ]
			]
		]
	]
]
