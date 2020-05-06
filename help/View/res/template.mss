// アプリ共通
@compact => off        // コンパクト出力
@comment => off
Stylesheet => [
    @charset => UTF-8 // この コメント が出たらNG
    common => [
        '*アプリ共通のスタイルテンプレート'
        '* commonスタイル'
        import => [
            split-pane.css      // 3ペイン分割
            sitemap.css         // ツリーメニュー
            table-sort.css
            tabmenu.css
            toolbar.css
        ]
        section => common       // 上位のセクションを呼出す
    ]
]

Javascript => [
    common => [
        '*アプリ共通のスクリプトテンプレート'
        jquery => [
            toolbar.js
            site-menu.js        // サイドツリーメニュー
            split-pane.js      // ウィンドウ分割
        ]
        section => ^common       // 上位のセクションを呼出す
    ]
]
