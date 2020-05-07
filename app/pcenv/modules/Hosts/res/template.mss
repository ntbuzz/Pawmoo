// スタイルシートとJavascriptのテンプレート
@comment => off      // コメント行の削除

Javascript => [
    '*モジュール固有のテンプレート'
    common => [
        jquery => [
            toolbar.js
            site-menu.js
            split-pane.js      // 3ペイン分割
            table-sort.js?theme=blue    // テーマは blue
            myscript.js         // クリックイベント処理を組込む
        ]
        import => popup-menu.js
        section => ^common       // 上位のセクションを呼出す
    ]
]
