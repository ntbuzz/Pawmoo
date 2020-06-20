
// jquery コマンドでインクルードすること

// ウィンドウリサイズで高さ調整するエレメント
$(".list-view").adjustHeight();

// リストビューはajaxするので個別にスクリプト定義
$("tr.item").dblclick(function(){
    var num =$(this).attr("id");
    var url = location.pathname.method_path("view")+num;
    $('#dialog1 dl dt').text("{$controller$}:"+num+" 登録情報");
//		alert(url);
    // コンテンツのIDを取得
    $("#dialog1").find(".openButton").click();
    $.post(url,
        function(data){
            //リクエストが成功した際に実行する関数
            $('#datalist').html(data);
//			alert("Loaded: " + data);
        })
        .fail(function() {
            alert( "error:"+url );
        });
    return false;
});
