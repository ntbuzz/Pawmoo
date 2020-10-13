//
// バルーンヘルプの表示
// jquery => コマンドでインクルードすること
// ポップアップセレクター
var selector = $(".popup-baloon");
selector.each(function () {
    var self = $(this); // jQueryオブジェクトを変数に代入しておく
    var ref = self.attr("data-element");  // 紐付けるID
    var act = ref.slice(0, 1);            // 先頭が＠ならmouseover
    if (act == "@") ref = ref.slice(1);
    var ev = (act == '@') ? "mouseover" : "click";
    if (ref != "") {
        var tag = ref.slice(0, 1);
        if (tag == "!") ref = ref.slice(1); // 先頭が！ならアイコン追加しない
        var icon = (tag == "!") ? ref : ref + "-help";
        if ($('#' + icon).length == 0) {
            $('#' + ref).append('<span class="help_icon" id="' + icon + '"></span>')
                .css('margin-right','17px');
            ev = 'mouseover';   // ポップアップイベントが登録されていることがあるので、強制的にマウスオーバーにする
        }
        ref = '#' + icon;
        if (ev == "click") $(ref).css("cursor", "help");
        $(ref).on(ev, function () {
            // バルーンを消すための領域を定義
            $('body').append('<div class="baloon-BK"></div>');
            $('.baloon-BK').fadeIn('fast');
            // バルーンコンテンツの表示位置をリンク先から取得して設定
            var x = $(ref).offset().left + ($(ref).innerWidth()/3);
            var y = $(ref).offset().top  + ($(ref).innerHeight()/2);
            if ((x + self.width()) > $(window).innerWidth()) {
                x = x - self.outerWidth() + $(ref).outerWidth();
                self.addClass("baloon-right");
            } else {
//                x = x + ($(ref).outerWidth()/3);
                self.addClass("baloon-left");
            }
            // マウス移動の範囲を配列に記憶する
            var bound = [ x, y, self.outerWidth(), self.outerHeight() ];
            self.css({'left': x + 'px','top': y + 'px'});
            self.fadeIn('fast');
            // バルーン領域以外をクリックしたらバルーンを消して領域を削除
            $('.baloon-BK').off().mousemove(function (e) {
                if (!bound.inBound(5, e.pageX, e.pageY)) {
                    // モーダルコンテンツとオーバーレイをフェードアウト
                    self.fadeOut('fast');
                    $('.baloon-BK').fadeOut('fast',function(){
                        // オーバーレイを削除
                        $('.baloon-BK').remove();
                    });
                }
            });
        });
    };
});
