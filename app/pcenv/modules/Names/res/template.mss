// スタイルシートとJavascriptのテンプレート
@comment => off
Stylesheet => [
    // モジュールスタイルのテンプレート
    '@charset' => UTF-8 // この コメント が出たらNG
    '*モジュール固有のテンプレート'
    common => [
        import => [
            toolbar.css
            split-pane.css      // 3ペイン分割
            sitemap.css         // ツリーメニュー
            table-sort.css
            tabmenu.css
        ]
        section => common       // 上位のセクションを呼出す
    ]
]
Javascript => [
    '*モジュール固有のテンプレート'
    common => [
        jquery => [
            toolbar.js          // ツールバー
            site-menu.js        // サイドツリーメニュー
            split-pane.js      // ウィンドウ分割
            table-sort.js?theme=blue    // テーマは blue
            myscript.js         // クリックイベント処理を組込む
        ]
        import => popup-menu.js
        section => common       // 上位のセクションを呼出す
    ]
]
