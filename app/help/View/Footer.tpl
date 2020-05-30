
"*フッターのインクルード"

.info-box#disp_about{about_info} => [ size => "450,220,200,150"
h3 => [ Biscuits(MAP) ]
p => [ ~ Biscuits(MAP) is PHP wild-framwork. :-) ~ ]
  <hr><br>
  pre => [ ~
@@Copyright (c) 2017 - 2020 by nTak
@license    MIT
@version 0.13.0, 2020-05-31
@System Require: PHP 5.6 or Higher
importと間違えないように先頭の＠は二個つける
出力するときには先頭の1個は削除される
 ~  ]
]

// #popup-dialog のマウスオーバーバルーンヘルプ
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
