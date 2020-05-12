
"*フッターのインクルード"

.info-box#disp_about{about_info} => [ size => "450,220,200,150"
h3 => [ Biscuit(MAP) ]
blockquote => [ ~
Biscuit(MAP) is PHP wild-framwork. :-)
~ ]
pre => [ ~
  
 @copyright  Copyright (c) 2017 - 2020 by nTak
 @license    MIT
 @version 0.10.0, 2020-05-12
 System Require: PHP 5.6 or Higher
 
~ ]
]

// #popup-dialog のマウスオーバーバルーンヘルプ
.popup-baloon{@!biscuit-help} => [
pre => [ ~
PHP簡易フレームワーク<br>
Biscuit(MAP)のドキュメントです
~ ]
]

.popup-baloon{@popup-dialog} => [
pre => [ ~
        さまざまなプロパティ
        動作パラメータの変更
        インフォメーション
        ・・・
        などを表示する
~
]
]
.popup-box#property-dialog{popup-dialog} => [ size => "400,300,200,150"
  .setup => [ 
      +dl => [
        [ "・設定" [ もにゃもにゃ ]  ]
        [ "・情報" [ うにゃうにゃ ]  ]
        [ "・そのた" [ pre => [ ~
        さまざまなプロパティ
        動作パラメータの変更
        インフォメーション
        ~ ]
        ] ]
        [  [ キー名のないDD要素 ] ]
        ]
      p => [ などを表示する ] // attr => value と間違わないよう配列にする
    ]
]
