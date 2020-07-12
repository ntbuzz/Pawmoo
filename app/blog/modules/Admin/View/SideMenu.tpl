// アプリ共通のサイドメニュー
<hr>

span.button#admin_login => [ Login ]		// ボタン表示

+dl => [ id => acMenu
    [ ${#Core.WEB開発}
        [ 
          +ul.global => [
            [ span.file => [ %${#Core.SW開発.BiscuitHelp} => "http://spider.starship.net/help/" ] ]
            [ span.file => [ %${#ToCoreolbar.SW開発.PC管理} => "http://spider.starship.net/pcenv/" ] ]
          ]
        ]
    ]
    [   "データベース操作"
        [
            +ul.global => [     // 新規タブを開くリンク
                [ span.file => [ %${#Core.管理ページ.SQLite} => ":SQLiteManager" ] ]
                [ span.file => [ %${#Core.管理ページ.PostgreSQL} => ":phppgadmin" ] ]
                [ span.file => [ %${#Core.管理ページ.MySQL} => ":phpMyAdmin" ] ]
            ]
        ]
    ]
    [   ${#Core.管理ページ}
        [
            +ul.global => [     // 新規タブを開くリンク
                [ span.file => [ %${#Core.管理ページ.トップ} => ":" ] ]
                [ span.file => [ %${#Core.管理ページ.PHP情報} => ":phpinfo.php" ] ]
                [ span.file => [ %${#Core.Redmine} => 'http://spider.starship.net/redmine/' ] ]
                [ span.file => [ %${#Core.Gitbucket} => 'http://spider.starship.net/gitbucket/' ] ]
                [ span.file => [ %${#Core.GitHib} => 'https://github.com/' ] ]
                [ span.file => [ %${#Core.管理ページ.MySQL} => ":phpMyAdmin" ] ]
                [ span.file => [ %${#Core.TestLink} => 'http://spider.starship.net/testlink-1.9.20/' ] ]
            ]
        ]
    ]
]