// スタイルシートとJavascriptのテンプレート
@comment => off
//@compact => on
Stylesheet => [
    // モジュールスタイルのテンプレート
    '*モジュール固有のテンプレート'
    common => [
        +section => ^common       // 上位のセクションを呼出す
    ]
]
Javascript => [
    '*モジュール固有のテンプレート'
    common => [
        +section => ^common       // 上位のセクションを呼出す
    ]
]
