/*
ページャーボタンとページサイズセレクターの処理
    jquery => コマンドでインクルードすること
*/
    $(".pager .jump,.move").off().click(function () {
        var pg = $(this).attr("value"); // ページサイズ
        location.href = pfLocation.param_path([pg]);
    });

    $(".pager #pagesize").change(function() {
        location.href = pfLocation.param_path([1,$(this).val()]);
    });

