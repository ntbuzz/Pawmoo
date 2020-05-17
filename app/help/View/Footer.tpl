
"*フッターのインクルード"
// #popup-dialog のマウスオーバーバルーンヘルプ
.popup-baloon{@find_word} => [
  pre => [ 検索 ]
]


.info-box#disp_about{about_info} => [ size => "450,220,200,150"
h3 => [ Biscuit(MAP) ]
p => [ ~ Biscuit(MAP) is PHP wild-framwork. :-) ~ ]
  <hr><br>
  pre => [ ~
  @copyright  Copyright (c) 2017 - 2020 by nTak
  @license    MIT
  @version 0.10.0, 2020-05-15
  System Require: PHP 5.6 or Higher
  ~ ]
]

// #popup-dialog のマウスオーバーバルーンヘルプ
.popup-baloon{@!biscuit-help} => [
  pre => [ ${#.ABOUT} ]
]

.popup-baloon{@popup-dialog} => [
pre => [ ${#.SETUP} ]
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
