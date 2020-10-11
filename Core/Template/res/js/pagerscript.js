/*
ページャーボタンとページサイズセレクターの処理
    jquery => コマンドでインクルードすること
*/
    $(".pager .jump,.move").off().click(function () {
        var pg = $(this).attr("value"); // ページサイズ
        var url = location.pathname.exclude_num_path(pg);
        location.pathname = url;
    });

    $(".pager #pagesize").change(function() {
        var url = location.pathname.exclude_num_path('1/'+$(this).val());
        location.pathname = url;
    });

