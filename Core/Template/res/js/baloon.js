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
            var target = {
                top: parseInt($(ref).offset().top, 10),
                left: parseInt($(ref).offset().left, 10),
                width: parseInt($(ref).outerWidth(), 10),
                height: parseInt($(ref).outerHeight(), 10)
            };
            var Balloon = {
                top: target.top + (target.height/2),
                left: target.left + (target.width/2),
                width: parseInt(self.outerWidth(true), 10),
                height: parseInt(self.outerHeight(true), 10),
                inBound: function (x, y) {
                        return (x >= this.left) && (x <= (this.left + this.width))
                                && (y >= this.top) && (y <= (this.top + this.height));
                },
                expand: function (e) {
                    this.left = Math.min(this.left, e.left);
                    this.top = Math.min(this.top, e.top);
                    this.width = Math.max(this.left+this.width, e.left+e.width)-this.left;
                    this.height = Math.max(this.top+this.height, e.top+e.height)-this.top;
                },
                outRangeX: function () {
                    if ((this.left + this.width) <= $(window).width()) return false;
                    this.left = target.left - this.width + (target.width/2);
                    return true;
                },
                outRangeY: function () {
                    if ((this.top + this.height) <= $(window).height()) return false;
                    this.top = target.top - this.height - (target.height/2);
                    return true;
                }
            };
            var cls = 'popup-baloon baloon-';
            cls = cls + ((Balloon.outRangeY()) ? 'bottom-' : 'top-');
            cls = cls + ((Balloon.outRangeX()) ? 'right' : 'left');
            self.attr('class', cls);
            self.css({'left': Balloon.left + 'px','top': Balloon.top + 'px'});
            self.fadeIn('fast');
            Balloon.expand(target);
            $('.baloon-BK').off().mousemove(function (e) {
                if (!Balloon.inBound(e.clientX, e.clientY)) {
                    self.fadeOut('fast');
                    $('.baloon-BK').fadeOut('fast',function(){
                        $('.baloon-BK').remove();
                    });
                }
            });
        });
    };
});
