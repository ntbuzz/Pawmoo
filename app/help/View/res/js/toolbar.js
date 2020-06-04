//
$("#menu li").hover(function () {
    $("ul",this).show();
    },
    function(){
        $("ul",this).hide();
    });
//
$('.global a').attr("target","_blank");

/*
    コンテキストメニューの処理関数
*/
/*
$("#ctxEdit").mousedown(function (e) { 
    alert("コンテキスト:"+$(this).attr("id"));
});

$("#ctxUndo").mousedown(function (e) { 
    alert("コンテキスト:"+$(this).attr("id"));
});
*/
// テキスト検索
$("#find_word").off().click(function () {
    var key = $('input[name=q]').val();
    var e = {q: key};   // オブジェクトで送信
    var url = location.pathname.controller_path("help/find");
//    alert(url);
    $.post(url, e, function (data) { //リクエストが成功した際に実行する関数
//        alert(data);
        $("#ContentBody").html(data);
    }).fail(function() {
        alert( "error:"+url );
    });
    return false;
});
