// コンテキストメニューの表示
// jquery => コマンドでインクルードすること
// グローバルオブジェクト popup_menu_function が定義されているときだけ処理
// popup_menu_function にはコンテキストメニューの処理関数を登録しておく
if (typeof popup_menu_function == "object") {
    var selector = $(".context-menu");
    selector.each(function () {
        var self = $(this); // jQueryオブジェクトを変数に代入しておく
//        var self_id = self.attr("id");
        var ref_id = self.attr("data-element");  // 紐付けるID
        if (ref_id != "") {
            ref = $(ref_id);
            self.find('li').each(function (index) {
                //クリックされたタブのみにクラスselectをつけます。
                var func = $(this).attr('id');
                var func_id = "#" + func;
                // メニュー関数が定義されているものだけ
                if (func) {
                    var func_type = (typeof popup_menu_function[func]);
                    var func_elem = popup_menu_function[func];
                    $(func_id).mousedown(function (e) {
                        self.hide();
                        $(ref_id).removeClass('hilight');  // 全部のクラスを変更
                        if (func_type == "function") {
                            func_elem($(ref_id + ".selected"));
                        } else {
//                            alert(func_elem+" click-action");
                            $(func_elem).click();
                        }
                        return false;   // 親要素に処理させない
                    });
                } else {
                    $(this).addClass('disable');
                }
            });
            ref.bind("contextmenu", function(e){
                // イベント発生位置(クリック位置)を基準にメニューを表示
                self.css({'left': e.pageX + 'px','top': e.pageY + 'px'}).show();
                // 画面クリックでメニュー非表示
                $(document).mousedown(function () {
                    $(ref_id).removeClass('hilight');  // 全部のクラスを変更
                    self.hide();
                    return true;   // 親要素に処理させない
                });
                // ブラウザのコンテキストメニューを起動しない。
                return false;
            }).mousedown(function (e) {
//                alert(ref.attr("id")+":"+e.which);
                // which の値は  1 : 左ボタン、2 : 中央ホイール、3 : 右ボタン
                if (e.which == 3) {
                    $(ref_id).removeClass('selected');  // 全部のクラスを変更
                    $(this).addClass('selected hilight');       // クリックされた要素のみ
                    return false;   // 親要素に処理させない
                }
            });

        };
    });
};
