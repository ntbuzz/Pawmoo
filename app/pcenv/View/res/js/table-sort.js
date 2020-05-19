// jquery コマンドでインポートする
// クエリ文字列で theme 変数を設定して呼び出す
//  アプリケーション用なのでコアからは呼び出さない
// .list-view クラスもパラメータ化する
//
$("#myTable").tablesorter({
    theme: '{$theme$}',
    widthFixed: true,
    headerTemplate: '{content} {icon}', // Add icon for jui theme; new in v2.7!
    widgets: ['zebra', 'stickyHeaders', 'filter'],
    widgetOptions: {
        filter_external: '.search',
        filter_defaultFilter: { 1: '~{query}' },
        filter_columnFilters: true,
        filter_placeholder: { search: 'Search...' },
        filter_saveFilters: true,
        filter_reset: '.reset',
        filter_hideFilters: true,
        stickyHeaders_attachTo: '.list-view' // or $('.wrapper')
    }
});
$("#myTable tr.item").click(function () {
    // 全ての項目から Selected を削除
    $("#myTable tr.item").removeClass("selected");
    // クリックされたアイテムだけに Selected を付加
    $(this).addClass('selected');
    return false;
});
