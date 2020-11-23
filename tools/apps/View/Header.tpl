// アプリ共通ヘッダ
<!DOCTYPE html>
<html>
'*${#HEADER}アプリ共通ヘッダー'

head => [
    title => [ ${PageTitle} ]         // 呼び出し元で指定された変数の値に置換
    -meta => [
        attribute => attr-value
        [ http-equiv => "Content-Language", content => "ja" ]
        [ http-equiv => "Content-Type",     content => "text/html; charset=UTF-8"  ]
    ]
    +include => [
        ${AdditionHeader}
    ]
]

