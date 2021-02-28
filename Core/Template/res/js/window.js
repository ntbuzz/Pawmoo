// ウィンドウ操作関数
//=========================================================
// JQueryプラグインを定義する
(function ($) {
    // 連動セレクトタグ
    $.fn.ChainSelect = function (selObj,val) {
        var id = this.attr('id');
        var sel_chain = new SelectChain(id,selObj);
        sel_chain.Select(val);
    };
    // 指定要素に読み込んだHTMLを書き込む
    $.fn.LoadContents = function (url, callback) {
        if (callback === undefined) callback = null;    // IE11で引数省略の不具合対応
        var self = this; // jQueryオブジェクトを変数に代入しておく
        $('body').css('cursor', 'wait');
        $.post(url,
            function(data){
                //リクエストが成功した際に実行する関数
                self.html(data);
                DebugSlider();
                $('body').css('cursor', 'default');
                if(callback!=null) callback(true);
            })
            .fail(function() {
                DebugSlider();
                $('body').css('cursor', 'default');
                alert( "error:"+url );
                if(callback!=null) callback(false);
            });
    };
    // 指定要素 e のスクロールに追従する
    $.fn.busy_icon = function (disp) {
        var self = this; // jQueryオブジェクトを変数に代入しておく
        self.css('display', (disp) ? 'block' : 'none');
        $('body').css('cursor', (disp) ? 'wait' : 'default');
    };
    // 指定要素 e のスクロールに追従する
    $.fn.stickyOn = function (e) {
        var self = this; // jQueryオブジェクトを変数に代入しておく
        $(e).on("scroll", function () {
            var top = $(e).scrollTop();
            self.css({
                'position': 'relative',
                'top': top + 'px',
                'z-index': 99
            });
        });
    };
    // 親要素の高さに調整する
    $.fn.adjustWindow = function () {
        var self = $(this); // jQueryオブジェクトを変数に代入しておく
        $(window).on("load resize", function () {
            var hsize = self.parent().height();
            var htop = self.offset().top;
            var spc = self.outerHeight() - self.height();
            self.css({
                'width': '100%',
                'height': hsize - htop - spc + "px",
                'overflow-y': "auto"
            });
        });
    };
    // CSS の padding 値を取得する
    $.fn.getPaddingBox = function() {
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
        if (ttl != null && ttl.length) self.find('dt').text(ttl);
        if (typeof obj == 'object') {
            $.each(obj, function (key, value) {
                var target = self.find('[name="' + key + '"]');
                if (target.length) {
                    switch (target.prop("tagName")) {
                    case 'INPUT':
                        if (target.attr("type") == "checkbox" || target.attr("type") == "radio" ) {
                            target.prop('checked', (value == 't'));
                        } else target.val(value);   // 自ID
                        break;
                    case 'SELECT':
                        target.val(value);   // 自ID
                        break;
                    case 'TEXTAREA':
                        var w = target.attr("cols");
                        var h = target.attr("rows");
                        target.css({"width": w+"em","height": h+"em"});
                    default:
                        target.text(value);   // 自ID
                    }
                }
            });
        }
        self.find(".execButton").off().click(function () {
            var setobj = {};
            self.find("*").each(function () {
                var nm = $(this).attr('name');
                if (nm) {
                    var tt = $(this).attr('type');
                    if (tt == 'checkbox' || tt == 'radio') {
                        if($(this).is(':checked')) setobj[nm] = $(this).val();
                    } else {
                        setobj[nm] = $(this).val();
                    }
                }
            });
            callback(setobj);
            return false;
        });
        this.find(".openButton").click();
        return false;
    };
    // Yes/No ダイアログボックスを開く
    $.dialogBox = function (title,msg, callback) {
        var dialog_box = '<div class="dialog-box"><dl class="title"><dt>'+title+'</dt><dd><span class="dialog-msg">'+msg+'</span></dd></dl><div class="buttonList">';
        var controlls = ["okButton:${#core.Yes}", "cancelButton:${#core.No}"];
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
    var selector = $(".stickyBar");
    selector.each(function () {
        var self = $(this); // jQueryオブジェクトを変数に代入しておく
        var stickyWin = self.closest('.fitWindow');
        if (stickyWin.length == 0) stickyWin = self.parent();
        // 親要素 のスクロールに追従する
        stickyWin.on("scroll", function () {
            var top = stickyWin.scrollTop();
            self.css("top", top + "px");
        });
    });
    // ウィンドウ高さ調整
    var selector = $(".fitWindow");
    selector.each(function () {
        $(this).adjustWindow(); // jQueryオブジェクトを変数に代入しておく
    });
    $(window).resize();
    // マークダウン外部リンク
     $('.easy_markdown a[href^=http]:not(:has(img))').addClass("externalLink").attr('target','_blank');

});