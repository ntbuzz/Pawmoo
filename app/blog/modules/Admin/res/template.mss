// スタイルシートとJavascriptのテンプレート
@comment => on
@compact => off
Stylesheet => [
    // モジュールスタイルのテンプレート
    '*モジュール固有のテンプレート'
    common => [
        +import => [
            mystyle.css
        ]
        +section => ^common       // 上位のセクションを呼出す
    ]
]
Javascript => [
    '*モジュール固有のテンプレート'
    common => [
        +jquery => [
            myscript.js
        ]
        +section => ^common       // 上位のセクションを呼出す
    ]
]
