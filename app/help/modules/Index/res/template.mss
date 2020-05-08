// スタイルシートとJavascriptのテンプレート
@comment => off
Stylesheet => [
    // モジュールスタイルのテンプレート
    '@charset' => UTF-8 // この コメント が出たらNG
    '*モジュール固有のテンプレート'
    common => [
        import => [
            toolbar.css
            sitemap.css         // ツリーメニュー
            split-pane.css      // 3ペイン分割
            tabmenu.css
            table-sort.css
            dialog-form.css
        ]
        section => ^common       // 上位のセクションを呼出す
    ]
]
Javascript => [
    '*モジュール固有のテンプレート'
    common => [
        jquery => [
            toolbar.js
            site-menu.js
            split-pane.js      // 3ペイン分割
            table-sort.js?theme=green    // テーマは blue
        ]
        import => popup-menu.js
        section => ^common       // 上位のセクションを呼出す
    ]
]
