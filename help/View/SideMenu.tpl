
// sitemap クラスの要素
//
div => [
  style => "text-align:center;background-color:lightyellow;"
  -img#biscuit-help => [ src => "/newfw/res/images/biscuit.png" ]
]
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
]

@Sitemenu