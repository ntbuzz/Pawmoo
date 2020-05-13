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
    };
    // 親要素の幅に調整する
    $.fn.adjustWidth = function () {
        var self = this; // jQueryオブジェクトを変数に代入しておく
        $(window).on("load resize",function () {
            wsize = self.parent().innerWidth();
            wleft = self.parent().offset().left;
            self.css("width", wsize - wleft + "px");
        });
    };
    $.fn.paddingWidth = function() {
        var widths = {
            top    : 0,
            bottom : 0,
            left   : 0,
            right: 0,
        };
        if ($(this).length > 0) {
            $.each($(this), function() {
                widths = {
                    top    : parseInt($(this).css('padding-top'), 10),
                    bottom : parseInt($(this).css('padding-bottom'), 10),
                    left   : parseInt($(this).css('padding-left'), 10),
                    right  : parseInt($(this).css('padding-right'), 10)
                };
            });
        }
        return widths;
    };
    // フローティングウィンドウを開く、execButtonがクリックされたらコールバックする
    $.fn.floatWindow = function (obj, callback) {
        var self = this;
        $.each(obj, function(key, value) {
            var target = self.find('[name="' + key + '"]');
            if (target.length) {
                if (target.prop("tagName") == "INPUT" || target.prop("tagName") == "SELECT") target.val(value);   // 自ID
                else target.text(value);   // 自ID
            }
 
        })
        self.find(".execButton").click(function () {
            var setobj = {};
            self.find("*").each(function () {
                var nm = $(this).attr('name');
                if (nm) {
                    setobj[nm] = $(this).val();
                }
            });
            callback(setobj);
        });
        this.find(".openButton").click();
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