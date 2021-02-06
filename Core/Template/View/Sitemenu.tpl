// アプリ共通のサイドメニュー
<hr>
.title => [
  span.boldtxt => [ ${#core.システムメニュー} ]
]
"+ul.filetree#sysmenu" => [
.closed => [
    span.folder => [ ${#core.WEB開発} ]
    +ul => [
        .closed => [  span.folder => [ ${#core.SPIDER-MASTER} ]
          +ul => [
              [ span.file => [ %${#core.SW開発.PawmooHelp} => "http://spider.starship.net/help/" ] ]
              [ span.file => [ %${#core.SW開発.PC管理} => "http://spider.starship.net/pcenv/" ] ]
              [ span.file => [ %${#core.SW開発.ブログ} => "http://spider.starship.net/blog/admin/" ] ]
            ]
        ]
        .closed => [  span.folder => [ ${#core.VIRGO-BRANCH} ]
          +ul => [
              [ span.file => [ %${#core.SW開発.PawmooHelp} => "http://virgo.starship.net/help/" ] ]
              [ span.file => [ %${#core.SW開発.PC管理} => "http://virgo.starship.net/pcenv/" ] ]
              [ span.file => [ %${#core.SW開発.ブログ} => "http://virgo.starship.net/blog/admin/" ] ]
            ]
        ]
        [ span.file => [ %${#core.SW開発.Video管理} => "http://spider.starship.net/mediamgr/" ] ]
      ]
]
.closed => [
    span.folder => [ ${#core.管理ページ} ]
    +ul.global => [     // 新規タブを開くリンク
      [ span.file => [ %${#core.管理ページ.トップ} => ":" ] ]
      [ span.file => [ %${#core.管理ページ.PHP情報} => ":phpinfo.php" ] ]
      [ span.file => [ %${#core.Redmine} => 'http://spider.starship.net/redmine/' ] ]
      [ span.file => [ %${#core.Gitbucket} => 'http://spider.starship.net/gitbucket/' ] ]
      [ span.file => [ %${#core.GitHib} => 'https://github.com/' ] ]
      [ span.file => [ %${#core.管理ページ.SQLite} => ":SQLiteManager" ] ]
      [ span.file => [ %${#core.管理ページ.PostgreSQL} => ":phppgadmin" ] ]
      [ span.file => [ %${#core.管理ページ.MySQL} => ":phpMyAdmin" ] ]
      [ span.file => [ %${#core.TestLink} => 'http://spider.starship.net/testlink-1.9.20/' ] ]
    ]
  ]
]
+jquery => ~
$("#sysmenu").treeview({
    animated: "fast"
});
~