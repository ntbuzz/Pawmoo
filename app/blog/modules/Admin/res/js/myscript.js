/*
ブログスクリプト
*/

$("#acMenu dt").on("click", function() {
    $(this).next().slideToggle('fast');
    $(this).toggleClass("active");//追加部分
});

$('.global a').attr("target", "_blank");

$("#admin_login").on("click", function () {
//    alert("LOGIN");
    // フォームにパラメータをセットし、完了時の処理関数を登録する
    $("#login_dialog").floatWindow("", null, function (e) {
        var url = location.href;
        var url2 = location.pathname.method_path(e["userid"]);
        $.post(url, e, function (data) { //リクエストが成功した際に実行する関数
//            alert(data);
            location.href = url2;
//            location.reload();
        }).fail(function() {
            alert( "error:"+url );
        });
        return false;
    });
    return false;
});
