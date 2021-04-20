// アプリ共通ヘッダ
'* ${#HEADER}アプリ共通ヘッダー'

head => [
    title. => [ ${PageTitle} ]         // 呼び出し元で指定された変数の値に置換
    -meta => [
        attribute => attr-value
        [ http-equiv => "Content-Language", content => "ja" ]
        [ http-equiv => "Content-Type",     content => "text/html; charset=UTF-8"  ]
    ]
    +include => [
        /js/jquery-3.2.1.min.js
        /js/jquery-ui-1.12.1/jquery-ui.min.js
        /js/jquery-ui-1.12.1/jquery-ui.min.css
        ${AdditionHeader}
    ]
]

