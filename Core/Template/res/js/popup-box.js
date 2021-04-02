//
// 位置固定のポップアップボックスを表示する
// オプション設定のパネル表示などに使用する
// jquery => コマンドでインクルードすること
// ポップアップセレクター
var selector = $(".popup-box");
selector.each(function () {
    var self = $(this); // jQueryオブジェクトを変数に代入しておく
    var ref = "#" + self.attr("data-element");  // 紐付けるID
    var self_id = "#"+self.attr("id");
    var resize_id = self_id+" .resize";
    var message_id= self_id+" .resize_message";
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
        var controlls = ["resize_message:${#core.SizeDisplay}", "close:${#core.Close}", "resize:${#core.Resize}"];
        controlls.forEach(function (value) {
            var cls = value.split(':');
            if (self.find("." + cls[0]).length == 0) {
                var alt = (cls[1] != '') ? '" title="' + cls[1] + '"' : '';
                var tag = '<span class="'+cls[0]+alt+'"></span>';
                self.append(tag);
            }
        });
        $(ref).on('click', function () {
            // バルーンを消すための領域を定義
            $('body').append('<div class="popup-BK"></div>');
            $('.popup-BK').fadeIn('fast');
            // バルーンコンテンツの表示位置をリンク先から取得して設定
            var x = $(ref).offset().left + $(ref).width();
            var y = $(ref).offset().top + self.getPaddingBox().top;
            if ((x + self.outerWidth()) > $(window).innerWidth()) {
                x = $(ref).offset().left - self.outerWidth(true);   // padding+margin込みの幅を差引く
            }
            if ((y + self.outerHeight()) > $(window).innerHeight()) {
                y = $(window).innerHeight() - self.outerHeight();   // padding+margin込みの高さを差引く;
                if (y <= 0) {
                    y = 10;
                    var h = $(window).innerHeight() - 40;   // 上下 20px
                    self.css('height', h+ 'px');
                }
            }
            self.css({'left': x + 'px','top': y + 'px'});
            self.fadeIn('fast');
            // クローズイベントを登録
            $(self_id + " #close, .close, .cancel").click( function() {
                // モーダルコンテンツとオーバーレイをフェードアウト
                self.fadeOut('fast');
                $('.popup-BK').fadeOut('fast',function(){
                    $('.popup-BK').remove();
                });
            });
        });
        // リサイズのドラッグ
        $(resize_id).on('mousedown', function(e) {
            self.data("clickPointX", e.pageX)
                .data("clickPointY", e.pageY);
            $(message_id).fadeIn('fast');
            self.css('user-select', 'none');    // テキスト選択不可
            $(document).mousemove(function (e) {
                var new_width = Math.floor(e.pageX - self.offset().left-12);
                var new_height= Math.floor(e.pageY - self.offset().top-12);
                self.css({
                    width: new_width + "px",
                    height: new_height + "px"
                });
                var txt = new_width + " x " + new_height;
                $(message_id).text(txt);
            }).mouseup(function (e) {
                self.css('user-select', '');    // テキスト選択可能
                $(message_id).fadeOut('fast');
                $(document).unbind("mousemove");
            });
        });     // mousedown()
    };
});

