//
// フローティングウィンドウ
// jquery => コマンドでインクルードすること
// セレクター
var selector = $(".floatWindow");
selector.each(function () {
    var id = "#" + $(this).attr("id");
    var self = $(id);
    var message_id= id+" .resize_message";
    // 中のブロック高さを調整
    self.resize(function () {
        hsize = self.innerHeight() - 50;
        $(id + ' dd').css("height", hsize + "px");
    });
/*  パーツを追加
        span.openButton => []					// ウィンドウを開く隠しボタン
        span.close => [ alt => 閉じる ]			// 閉じるボタン
        span.resize => []
*/
    var controlls = ["openButton:", "close:閉じる", "resize:", "resize_message:サイズ表示"];
    controlls.forEach(function (value) {
        var cls = value.split(':');
        if (self.find("." + cls[0]).length == 0) {
            var alt = (cls[1] != '') ? ' alt="' + cls[1] + '"' : '';
            var tag = '<span class="'+cls[0]+alt+'"></span>';
            self.append(tag);
        }
    });
    // クリックイベント登録
    self.find(".openButton").click( function() {
            // alert("click=" + click);
        selector.fadeOut("fast");   // 全てのウィンドウを消す
        // クローズイベントを登録
        $(id + " #close, .close, .cancel").click( function() {
            self.fadeOut("fast");
            $(document).unbind("mousemove");
            $("body").find(".floatBack").remove();
        });
        // ドロップ属性があればエレメントを初期化する
        if( self.attr("class").indexOf("drop") !== -1) {
            self.find("#datalist").empty();
            var initdata = self.find("#init").attr("value");
            self.find("#datalist").append(initdata);
        };
        $("body").append("<div class='floatBack'></div>");
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
        self.fadeIn("fast");
        $(window).resize( function() {
            self.css( {
                top: $(window).scrollTop() + 100,
                left: ($(window).width() - self.outerWidth()) /2
            });
        });
        $(window).resize();
//            self.resize();
        return false;
    });
    // タイトルバーのドラッグ
    $(id+" dl dt").mousedown( function(e) {
        self.data("clickPointX", e.pageX - self.offset().left)
            .data("clickPointY", e.pageY - self.offset().top);
        $(document).mousemove( function(e) {
            self.css({
                top: (e.pageY - self.data("clickPointY")) + "px",
                left: (e.pageX - self.data("clickPointX")) + "px"
            });
        }).mouseup( function(e) {
            $(document).unbind("mousemove");
        });
    });     // mousedown()
    // リサイズのドラッグ
    $(id+" .resize").mousedown( function(e) {
        self.data("clickPointX", e.pageX)
            .data("clickPointY", e.pageY);
        $(message_id).fadeIn('fast');
        self.css('user-select', 'none');    // テキスト選択不可
        $(document).mousemove( function(e) {
            self.css({
                width: (e.pageX - self.offset().left + 6) + "px",
                height: (e.pageY - self.offset().top + 6) + "px"
            });
            var txt = self.width() + " x " + self.height();
            $(message_id).text(txt);
        }).mouseup(function (e) {
            $(message_id).fadeOut('fast');
            self.css('user-select', '');    // テキスト選択可能
            $(document).unbind("mousemove");
        });
    });     // mousedown()
});
