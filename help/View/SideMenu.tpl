
// sitemap クラスの要素
//
.title => [
  span.boldtxt => [ $#Toolbar.トピックス ]
]
"+ul.filetree#sitemenu" => [
  .open => [
    span.folder => [ ${#Toolbar.はじめに} ]
    +ul => [
      [ span.file => [ %${#Toolbar.はじめに.Biscuitとは} => "#" ] ]
      [ span.file => [ %${#Toolbar.はじめに.概要} => "#" ] ]
      [ span.file => [ %${#Toolbar.はじめに.詳細} => "#" ] ]
    ]
  ]
  .closed => [
    span.folder => [ ${#Toolbar.管理ページ} ]
    +ul.global => [
      [ span.file => [ %${#Toolbar.管理ページ.トップ} => ":" ] ]
      [ span.file => [ %${#Toolbar.管理ページ.PHP情報} => ":phpinfo.php" ] ]
    ]
  ]
  .closed => [
    span.folder => [ ${#Toolbar.SW開発} ]
    +ul.global => [
      .closed => [
          span.folder => [ ${#Toolbar.PHPフレームワーク} ]
          +ul.global => [
            [ span.file => [ %${#Toolbar.SW開発.Biscuit} => ":biscuit/" ] ]
            [ span.file => [ %${#Toolbar.SW開発.PC管理} => ":pcenv/" ] ]
            [ span.file => [ %${#Toolbar.SW開発.Video管理} => ":mediamgr/" ] ]
          ]
      ]
      [ span.file => [ %${#Toolbar.管理ページ.SQLite} => ":SQLiteManager" ] ]
      [ span.file => [ %${#Toolbar.管理ページ.PostgreSQL} => ":phppgadmin" ] ]
    ]
  ]
]
