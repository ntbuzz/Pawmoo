// アプリ共通のサイドメニュー
<hr>
.title => [
  span.boldtxt => [ ${#Core.システムメニュー} ]
]
"+ul.filetree#sysmenu" => [
.closed => [
    span.folder => [ ${#Core.WEB開発} ]
    +ul => [
        .closed => [  span.folder => [ ${#Core.SPIDER-MASTER} ]
          +ul => [
              [ span.file => [ %${#Core.SW開発.BiscuitHelp} => "http://spider.starship.net/help/" ] ]
              [ span.file => [ %${#ToCoreolbar.SW開発.PC管理} => "http://spider.starship.net/pcenv/" ] ]
              [ span.file => [ %${#ToCoreolbar.SW開発.ブログ} => "http://spider.starship.net/blog/admin/" ] ]
            ]
        ]
        .closed => [  span.folder => [ ${#Core.VIRGO-BRANCH} ]
          +ul => [
              [ span.file => [ %${#Core.SW開発.BiscuitHelp} => "http://virgo.starship.net/help/" ] ]
              [ span.file => [ %${#ToCoreolbar.SW開発.PC管理} => "http://virgo.starship.net/pcenv/" ] ]
              [ span.file => [ %${#ToCoreolbar.SW開発.ブログ} => "http://virgo.starship.net/blog/admin/" ] ]
            ]
        ]
        [ span.file => [ %${#Core.SW開発.Video管理} => "http://spider.starship.net/mediamgr/" ] ]
      ]
]
.closed => [
    span.folder => [ ${#Core.管理ページ} ]
    +ul.global => [     // 新規タブを開くリンク
      [ span.file => [ %${#Core.管理ページ.トップ} => ":" ] ]
      [ span.file => [ %${#Core.管理ページ.PHP情報} => ":phpinfo.php" ] ]
      [ span.file => [ %${#Core.Redmine} => 'http://spider.starship.net/redmine/' ] ]
      [ span.file => [ %${#Core.Gitbucket} => 'http://spider.starship.net/gitbucket/' ] ]
      [ span.file => [ %${#Core.GitHib} => 'https://github.com/' ] ]
      [ span.file => [ %${#Core.管理ページ.SQLite} => ":SQLiteManager" ] ]
      [ span.file => [ %${#Core.管理ページ.PostgreSQL} => ":phppgadmin" ] ]
      [ span.file => [ %${#Core.管理ページ.MySQL} => ":phpMyAdmin" ] ]
      [ span.file => [ %${#Core.TestLink} => 'http://spider.starship.net/testlink-1.9.20/' ] ]
    ]
  ]
]
+jquery => ~
$("#sysmenu").treeview({
    animated: "fast"
});
~