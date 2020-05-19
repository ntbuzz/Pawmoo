
// jquery コマンドでインクルードすること

// ワンクリックでコンテンツビューに表示する
$("tr.item").click(function(){
    var num =$(this).attr("id");
    var url = location.pathname.module_path("view")+num;
    $.post(url,
        function(data){
            //リクエストが成功した際に実行する関数
            $('#ContentBody').html(data);
            $('.fixedsticky').stickyOn('#bottom-component');
        })
        .fail(function() {
            alert( "error:"+url );
        });
    return false;
});

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
            $('.fixedsticky').stickyOn('.contents-view');
            $('#ContentBody').empty();
            //				alert("Loaded: " + data);
        })
        .fail(function () {
            alert("error:" + url);
        });
    return false;
});
