// アプリ共通ヘッダ
<!DOCTYPE html>
<html>
'*アプリ共通ヘッダー'
// 必要なパラメータが設定されてこなかった時に備える
+setvar => [
	OptionHeader => ''
]

head => [
    title => [ $PageTitle ]         // 呼び出し元で指定された変数の値に置換
    -meta => [
        attribute => attr-common-value
        [ http-equiv => "Content-Language", content => "ja" ]
        [ http-equiv => "Content-Type",     content => "text/html; charset=UTF-8"  ]
    ]
    +include => [
        images/favicon.ico
        /js/jquery-3.2.1.min.js
        /js/jquery-ui-1.12.1/jquery-ui.min.js
        /js/jquery-ui-1.12.1/jquery-ui.min.css
//        /js/vue.min.js
        /js/split-pane/split-pane.css
        /js/split-pane/split-pane.js
        /js/treeview/jquery.treeview.css
        /js/treeview/jquery.treeview.js
        /js/tablesorter/css/theme.default.css
        /js/tablesorter/css/theme.blue.css
        /js/tablesorter/css/theme.green.css
        /js/tablesorter/js/jquery.tablesorter.js
        /js/tablesorter/js/jquery.tablesorter.widgets.js
        ${AdditionHeader}
    ]
    {$OptionHeader}
]

