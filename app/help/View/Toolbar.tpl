// ツールバー
.font20#toolbar => [
	.rightitem => [
		<input type="text" class="font16" id="c00" size="20" name="cc">
		"span.font24#popup-dialog" => [ ≡ ]
	]
	+ul#menu => [
		#li => [ %${#Toolbar.home} => "/index" ]		 //  ハイパーリンク
		.li => [ %${#Toolbar.ファイル} => "#"
			+ul.sub => [
				li => [ %${#Toolbar.ファイル.新規} => "#" ]
				li => [ %${#Toolbar.ファイル.開く} => "#" ]
				li => [ %${#Toolbar.ファイル.終了} => "#" ]
			]
		]
		li => [ %${#Toolbar.ドキュメント} => "#"
			ul.sub => [
				li => [ %${#Toolbar.ドキュメント.パート追加} => "#" ]
				li => [ %${#Toolbar.ドキュメント.チャプター追加} => "#" ]
			]
		]
		li => [ %${#Toolbar.管理ページ} => "#"
			"ul.sub global" => [
				li => [ %${#Toolbar.管理ページ.トップ} => ":" ]
				li => [ %${#Toolbar.管理ページ.PHP情報} => ":phpinfo.php" ]
				li => [ %${#Toolbar.管理ページ.SQLite} => ":SQLiteManager" ]
				li => [ %${#Toolbar.管理ページ.PostgreSQL} => ":phpPgAdmin" ]
			]
		]
		li => [ %${#Toolbar.ヘルプ} => "#"
			"ul.sub" => [
				li => [ %${#Toolbar.ヘルプ.バージョン} => "#" ]
				li => [ %${#Toolbar.ヘルプ.問合わせ} => "#" ]
			]
		]
	]
]
