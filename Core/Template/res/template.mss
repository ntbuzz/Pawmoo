// stylesheeet/javascript のテンプレート
// 最上位キーはファイルタイプ(css/js)を示すキーワード
// URLから必要なファイルタイプの要素を抜き出して解析する
@message => yes     // デバッグ用コメント出力
// =====================================================
// stylesheet テンプレートセクション
Stylesheet => [
    common => [
        // ページャー用ジャンプボタン
        +import => [
            libstyle.css
            markdown.css
            pagerstyle.css
            floatwin.css
            baloon.css
            context.css
        ]
        +section => @debugbar        // DEBUGGERフラグがONの時のみ
    ]
    debugbar => [
        +import => debugbar.css
    ]
]
// =====================================================
// javascript テンプレートセクション
Javascript => [
    common => [
        +jquery => [
            pagerscript.js        // ページャー用スクリプト
            floatwin.js             // フロートウィンドウ操作
            baloon.js               // バルーンヘルプ
            info-box.js             // 移動・リサイズ不可のポップアップBOX
            popup-box.js            // ポップアップBOX(移動不可)
            context.js
        ]
        +import => [
            funcs.js                // 共通関数
            window.js               // ウィンドウ操作関数
        ]
        +section => @debugbar        // DEBUGGERフラグがONの時のみ
    ]
    debugbar => [
        +jquery => debugbar.js
    ]
]
