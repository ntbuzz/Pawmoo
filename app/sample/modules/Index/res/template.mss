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
            blogscript.js
            tabset.js
            test.php
        ]
        +section => ^common       // 上位のセクションを呼出す
    ]
]
