
// sitemap クラスの要素
//
div => [
  style => "text-align:center;background-color:lightyellow;"
  -img#biscuit-help => [ src => "/res/images/biscuit.png" ]
]
.title => [
  span.boldtxt => [ $#Toolbar.フィルタ表示 ]
]
"+ul.filetree#sitemenu" => [
  .open => [
    span.folder => [ ${#Toolbar.テーブル表示} ]
    +ul => [
      .host_help => [ span.file => [ %${#Toolbar.表示.ホスト} => '/hosts/page/1' ] ]
      [ span.file => [ %${#Toolbar.表示.名前} => '/names/page/1' ] ]
      [ span.file => [ %${#Toolbar.表示.OSリスト} => '/os/page/1' ] ]
      [ span.file => [ %${#Toolbar.表示.ライセンス} => '/licenses/page/1' ] ]
      [ span.file => [ %${#Toolbar.表示.アプリ} => '/apps/page/1' ] ]
    ]
  ]
]
@Sitemenu
