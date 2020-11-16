/*
ページャーボタンとページサイズセレクターの処理
    jquery => コマンドでインクルードすること
*/
    $(".pager .jump,.move").off().click(function () {
        var pg = $(this).attr("value"); // ページサイズ
        var loc = new Locations();
        location.href = loc.href_number([pg]);
    });

    $(".pager #pagesize").change(function() {
        var loc = new Locations();
        location.href = loc.href_number([1,$(this).val()]);
    });

