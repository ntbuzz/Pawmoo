// アプリ共通
Stylesheet => [
    @charset => UTF-8 // この コメント が出たらNG
    common => [
        '*アプリ共通のcommonテンプレート'
        +section => common       // 上位のセクションを呼出す
    ]
]

Javascript => [
    common => [
        '*アプリ共通のcommonテンプレート'
        +section => ^common       // 上位のセクションを呼出す
    ]
]
