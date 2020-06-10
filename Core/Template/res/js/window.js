// ウィンドウ操作関数
//=========================================================
// JQueryプラグインを定義する
(function ($) {
    var wsz = {
        theme: 'white',
        adjustClass: '',
        Width: function() {
            wsize = this.parent().innerWidth();
            wleft = this.parent().offset().left;
            this.css("width", wsize - wleft + "px");
        }
    };
    // 指定要素 e のスクロールに追従する
    $.fn.stickyOn = function (e) {
        var self = this; // jQueryオブジェクトを変数に代入しておく
        $(e).on("scroll", function () {
            var top = $(e).scrollTop();
            self.css("position", "relative");
            self.css("top", top + "px");
//            alert(e+":"+top);
        });
    };
    // 親要素の高さに調整する
    $.fn.adjustHeight = function () {
        var self = $(this); // jQueryオブジェクトを変数に代入しておく
        $(window).on("load resize",function () {
            hsize = self.parent().innerHeight();
            htop = self.offset().top;
            self.css("height", hsize - htop + "px");
        });
        $(window).resize();
    };
    // 親要素の幅に調整する
    $.fn.adjustWidth = function () {
        var self = this; // jQueryオブジェクトを変数に代入しておく
        $(window).on("load resize",function () {
            wsize = self.parent().innerWidth();
            wleft = self.parent().offset().left;
            self.css("width", wsize - wleft + "px");
        });
        $(window).resize();
    };
    $.fn.paddingWidth = function() {
        var widths = {
            top    : 0,
            bottom : 0,
            left   : 0,
            right: 0,
        };
        if ($(this).length > 0) {
            $.each($(this), function() {
                widths = {
                    top    : parseInt($(this).css('padding-top'), 10),
                    bottom : parseInt($(this).css('padding-bottom'), 10),
                    left   : parseInt($(this).css('padding-left'), 10),
                    right  : parseInt($(this).css('padding-right'), 10)
                };
            });
        }
        return widths;
    };
    // フローティングウィンドウを開く、execButtonがクリックされたらコールバックする
    $.fn.floatWindow = function (ttl, obj, callback) {
        var self = this;
        if (ttl.length) self.find('dt').text(ttl);
        $.each(obj, function (key, value) {
            var target = self.find('[name="' + key + '"]');
            if (target.length) {
                if (target.prop("tagName") == "INPUT" || target.prop("tagName") == "SELECT") target.val(value);   // 自ID
                else {
                    if (target.prop("tagName") == "TEXTAREA") {     // 初期表示サイズを固定する
                        var w = target.attr("cols");
                        var h = target.attr("rows");
                        target.css({"width": w+"em","height": h+"em"});
                    }
                    target.text(value);   // 自ID
                }
            }
        });
        self.find(".execButton").off().click(function () {
//            alert($(this).attr('class'));
            var setobj = {};
            self.find("*").each(function () {
                var nm = $(this).attr('name');
                if (nm) setobj[nm] = $(this).val();
            });
//            alert("call" + callback);
            callback(setobj);
            return false;
        });
        this.find(".openButton").click();
        return false;
    };
    // Yes/No ダイアログボックスを開く
    $.dialogBox = function (title,msg, callback) {
        var dialog_box = '<div class="dialog-box"><dl class="title"><dt>'+title+'</dt><dd><span class="dialog-msg">'+msg+'</span></dd></dl><div class="buttonList">';
        var controlls = ["okButton:${#core.Yes}", "cancelButton:${#core.Cancel}"];
        controlls.forEach(function (value) {
            var cls = value.split(':');
            dialog_box = dialog_box + '<span class="'+cls[0]+'">'+cls[1]+'</span>';
        });
        dialog_box = dialog_box + "</div></div>";
        $('body').append(dialog_box);
        // ボタン以外をクリックできないようにする
        $('body').append('<div class="popup-BK"></div>');
        $('.popup-BK').fadeIn('fast');
        var dialog = $('.dialog-box');
        // バルーンコンテンツの表示位置をリンク先から取得して設定
        var x = ($(window).innerWidth() - dialog.width())/2;  // 中央
        var y = ($(window).innerHeight() - dialog.height())/3;    // 上部33%の位置
        if (x < 0) {
            x = 5;
            dialog.width($(window).innerWidth() - 20);
        }
        if (y < 0) {
            y = 5;
            dialog.width($(window).innerHeight() - 20 );
        }
        dialog.css({'left': x + 'px','top': y + 'px'});
        dialog.fadeIn('fast');
        // クローズイベントを登録
        dialog.find(".okButton").off().click(function () {
            dialog.fadeOut('fast');
            $('.popup-BK').remove();
            $(".dialog-box").remove();
            callback(true);
        });
        dialog.find(".cancelButton").off().click(function () {
            dialog.fadeOut('fast');
            $('.popup-BK').remove();
            $(".dialog-box").remove();
            callback(false);
        });
    };
})(jQuery);
// ***************************************************************************
// セレクタを使う
$(function () {
    // スティッキー動作設定
    var selector = $(".fixedsticky");
    selector.each(function () {
        var self = $(this); // jQueryオブジェクトを変数に代入しておく
        var ref = self.attr("data-element");  // 紐付けるID

        if (ref != "") {
            // 指定要素 e のスクロールに追従する
            $(ref).on("scroll", function () {
                var top = $(ref).scrollTop();
                self.css("top", top + "px");
            });
        }
    });

});