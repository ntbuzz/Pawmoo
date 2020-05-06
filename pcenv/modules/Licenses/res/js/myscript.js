
// jquery コマンドでインクルードすること

// ダブルクリックでフロートウィンドウを表示する場合
$("tr.item").dblclick(function () {
    var num = $(this).attr("id");
    var url = location.pathname.module_path("view") + num;
    $('#dialog1 dl dt').text("{$controller$}:" + num + " のデータ操作");
    //		alert(url);
    // コンテンツのIDを取得
    $("#dialog1").find(".openButton").click();
    $.post(url,
        function (data) {
            //リクエストが成功した際に実行する関数
            $('#datalist').html(data);
            //				alert("Loaded: " + data);
        })
        .fail(function () {
            alert("error:" + url);
        });
    return false;
});
