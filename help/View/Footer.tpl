
// #popup-dialog のマウスオーバーバルーンヘルプ
.popup-baloon{@!biscuit-help} => [
~
PHP簡易フレームワーク・Biscuitのドキュメントです
~
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
         "・設定" => [
              もにゃもにゃ
            ]
         "・情報" => [
              うにゃうにゃ
            ]
         "・そのた" => [
            pre => [ ~ さまざまなプロパティ
        動作パラメータの変更
        インフォメーション
             ~ ]
            ]
          キー名のない要素
        ]
      などを表示する
    ]
]
