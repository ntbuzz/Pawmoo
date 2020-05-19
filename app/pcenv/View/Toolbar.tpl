// ツールバー
.font20#toolbar => [
	.leftitem => [
		"span.font24#popup-left" => [ ■ ] span.help_icon#popup-left-help => []
	]
	.rightitem => [
		<input type="text" class="font16" id="c00" size="20" name="cc">
		"span.font24#popup-dialog" => [ ≡ ]
	]
	+ul#menu => [
		#li => [ %${#Toolbar.home} => "index" ]		 //  ハイパーリンク
		.li => [ %${#Toolbar.ファイル} => "#"
			+ul.sub => [
				li => [ %${#Toolbar.ファイル.新規} => "#" ]
				li => [ %${#Toolbar.ファイル.開く} => "#" ]
				li => [ %${#Toolbar.ファイル.終了} => "#" ]
			]
		]
		li => [ %${#Toolbar.編集} => "#"
			ul.sub => [
				li => [ %${#Toolbar.編集.コピー} => "#" ]
				li => [ %${#Toolbar.編集.貼付け} => "#" ]
				li => [ %${#Toolbar.編集.削除} => "#" ]
				li => [ %${#Toolbar.編集.取消} => "#" ]
				li => [ %${#Toolbar.編集.検索} => "#" ]
			]
		]
		li => [ %${#Toolbar.表示} => "#"
			ul.sub => [
				li => [ %${#Toolbar.home} => "index/page/" ]
				li => [ %${#Toolbar.表示.ホスト} => "hosts/page/" ]
				li => [ %${#Toolbar.表示.名前} => "names/page/" ]
				li => [ %${#Toolbar.表示.OSリスト} => "os/page/" ]
				li => [ %${#Toolbar.表示.ライセンス} => "licenses/page/" ]
				li => [ %${#Toolbar.表示.アプリ} => "apps/page/" ]
				li => [ %${#Toolbar.表示.BIND} => "index/bindlist/100/50" ]
			]
		]
		li => [ %${#Toolbar.管理ページ} => "#"
			"ul.sub global" => [
				li => [ %${#Toolbar.管理ページ.トップ} => ":" ]
				li => [ %${#Toolbar.管理ページ.PHP情報} => ":phpinfo.php" ]
				li => [ %${#Toolbar.管理ページ.SQLite} => ":SQLiteManager" ]
				li => [ %${#Toolbar.管理ページ.PostgreSQL} => ":phppgadmin" ]
			]
		]
		li => [ %${#Toolbar.ヘルプ} => "#"
			"ul.sub" => [
				li => [ %${#Toolbar.ヘルプ.よくある質問} => "#" ]
				li => [ %${#Toolbar.ヘルプ.問合わせ} => "#" ]
			]
		]
	]

]
