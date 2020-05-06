// スタイルシートとJavascriptのテンプレート
@comment => off
Stylesheet => [
    // モジュールスタイルのテンプレート
    '@charset' => UTF-8 // この コメント が出たらNG
    '*モジュール固有のテンプレート'
    common => [
        import => [
            libstyle.css
            split-pane.css      // 3ペイン分割
            sitemap.css         // ツリーメニュー
            table-sort.css
            tabmenu.css
            toolbar.css
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
