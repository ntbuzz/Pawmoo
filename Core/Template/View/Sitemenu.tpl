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
              [ span.file => [ %FW版help => "http://spider.starship.net/biscuit/help/" ] ]
              [ span.file => [ %${#ToCoreolbar.SW開発.PC管理} => "http://spider.starship.net/pcenv/" ] ]
              [ span.file => [ %FW版PC管理 => "http://spider.starship.net/biscuit/pcenv/" ] ]
            ]
        ]
        .closed => [  span.folder => [ ${#Core.VIRGO-BRANCH} ]
          +ul => [
              [ span.file => [ %${#Core.SW開発.BiscuitHelp} => "http://virgo.starship.net/help/" ] ]
              [ span.file => [ %FW版help => "http://virgo.starship.net/biscuit/help/" ] ]
              [ span.file => [ %${#ToCoreolbar.SW開発.PC管理} => "http://virgo.starship.net/pcenv/" ] ]
              [ span.file => [ %FW版PC管理 => "http://virgo.starship.net/biscuit/pcenv/" ] ]
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
      [ span.file => [ %${#Core.Redmine} => 'http://spider/redmine/' ] ]
      [ span.file => [ %${#Core.Gitbucket} => 'http://spider/gitbucket/' ] ]
      [ span.file => [ %${#Core.管理ページ.SQLite} => ":SQLiteManager" ] ]
      [ span.file => [ %${#Core.管理ページ.PostgreSQL} => ":phppgadmin" ] ]
    ]
  ]
]
+jquery => ~
$("#sysmenu").treeview({
    animated: "fast"
});
~