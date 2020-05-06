// スタイルシートとJavascriptのテンプレート
@comment => off
Stylesheet => [
    // モジュールスタイルのテンプレート
    '@charset' => UTF-8 // この コメント が出たらNG
    '*モジュール固有のテンプレート'
    common => [
        import => [
            split-pane.css      // 3ペイン分割
            sitemap.css         // ツリーメニュー
        ]
        section => common       // 上位のセクションを呼出す
    ]
]
Javascript => [
    '*モジュール固有のテンプレート'
    common => [
        jquery => [
            table-sort.js?theme=blue    // テーマは blue
            myscript.js         // クリックイベント処理を組込む
        ]
        import => popup-menu.js
        section => frames       // 上位のセクションを呼出す
    ]
]
