//
// 位置固定のポップアップボックスを表示する
// オプション設定のパネル表示などに使用する
// jquery => コマンドでインクルードすること
// ポップアップセレクター
var selector = $(".info-box");
selector.each(function () {
    var self = $(this); // jQueryオブジェクトを変数に代入しておく
    var ref = "#" + self.attr("data-element");  // 紐付けるID
    var self_id = "#"+self.attr("id");
    if (ref != "#" && self_id != "#") {
        $(ref).css("cursor", "pointer");
        // サイズ属性があればウィンドウサイズを指定する
        if (self.is('[size]')) {
            var sz = self.attr("size").split(',');
            self.css({
                "width": sz[0] + "px",
                "height": sz[1] + "px",
            });
            if (sz.length == 4) {
                self.css({
                    "min-width": sz[2] + "px",
                    "min-height": sz[3] + "px"
                });
            };
        };
        $(ref).on('click', function () {
            // バルーンを消すための領域を定義
            $('body').append('<div class="popup-BK"></div>');
            $('.popup-BK').fadeIn('fast');
            // バルーンコンテンツの表示位置をリンク先から取得して設定
            var x = ($(window).innerWidth() - self.width())/2;  // 中央
            var y = ($(window).innerHeight() - self.height())/4;    // 上部25%の位置
            if (x < 0) {
                x = 5;
                self.width($(window).innerWidth() - 20);
            }
            if (y < 0) {
                y = 5;
                self.width($(window).innerHeight() - 20 );
            }
            self.css({'left': x + 'px','top': y + 'px'});
            self.fadeIn('fast');
            // クローズイベントを登録
            $(".popup-BK").click( function() {
                // モーダルコンテンツとオーバーレイをフェードアウト
                self.fadeOut('fast');
                $('.popup-BK').fadeOut('fast',function(){
                    $('.popup-BK').remove();
                });
            });
        });
    };
});

