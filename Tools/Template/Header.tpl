// アプリ共通ヘッダ
head. => [
    title. => [ ${PageTitle} ]         // 呼び出し元で設定する変数
    -meta => [
        attribute => attr-common-value
        [ http-equiv => "Content-Language" content => "ja" ]
        [ http-equiv => "Content-Type"     content => "text/html; charset=UTF-8"  ]
    ]
    +include => [
        /js/jquery-3.2.1.min.js
        /js/jquery-ui-1.12.1/jquery-ui.min.js
        /js/jquery-ui-1.12.1/jquery-ui.min.css
        ${AdditionHeader}
    ]
]

