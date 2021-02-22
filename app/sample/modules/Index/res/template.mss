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
        ]
        +section => ^common       // 上位のセクションを呼出す
    ]
]
