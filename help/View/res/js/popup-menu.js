/*
    ポップアップメニューを処理するコールバック関数
*/
var popup_menu_function = {
    "ctxEdit": function (obj) {
        var sel = obj.find(".selected").attr("id");
        if (sel) {
            alert('みかん：'+sel);
        } else {
            alert('アイテムを選んでください'+obj.attr("id"));
        }
    },
    "ctxUndo": function (obj) {
        alert('ミカン');
    },
};
