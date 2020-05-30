//
//==============================================================================
//   テキストをクリップボードへコピー
$("#ctxCopy").click(function () {
//    alert('COPY');
    document.execCommand('copy');
    return false;
});
//==============================================================================
//   テキスト形式に変換
$('#text_downld').click(function () {
    var url = location.pathname.controller_path("index/download");
    location.href = url;
/*
    $.post(url, function (data) { //リクエストが成功した際に実行する関数
        alert(data);
    }).fail(function () {
        alert("error:" + url);
    });
*/
    return false;
});
