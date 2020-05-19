/*
ページャーボタンとページサイズセレクターの処理
    jquery => コマンドでインクルードすること
*/
    $(".pager .jump,.move").click(function () {
        var pg = $(this).attr("value"); // ページサイズ
        var url = location.pathname.exclude_num_path(pg);
//        alert("JUMP!" + url);
        location.pathname = url;
    });

    $(".pager #pagesize").change(function() {
//        alert("CHANGE!"+$(this).val());
        var url = location.pathname.exclude_num_path('1/'+$(this).val());
        location.pathname = url;
    });

