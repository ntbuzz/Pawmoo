// スタイルシートとJavascriptのテンプレート
@comment => off
@compact => off
// モジュールスタイルのテンプレート
Stylesheet => [
    common => [
        +import => [
            blogstyle.css
            tabmenu.css
            tabset.css
            test.php
        ]
        +section => ^common       // 上位のセクションを呼出す
    ]
]
// モジュールスクリプトのテンプレート
Javascript => [
    common => [
        +jquery => [
//          select.php            // SELECTテーブルをPHPで生成する
            blogscript.js
            tabset.js
        ]
        +section => ^common       // 上位のセクションを呼出す
    ]
]
