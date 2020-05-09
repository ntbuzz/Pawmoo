/*
    ポップアップメニューを処理するコールバック関数
*/
var popup_menu_function = {
    "ctxEdit": function (obj) {
        var sectitle = obj.parents('.section').find("h2").text();
        var mytitle = obj.find("h3").text();
        var mytext = obj.find("p").text();
        $("#edit_dialog").floatWindow(function (e) {
            e.find('.section').text(sectitle);
            e.find('input[name="title"]').attr("value",mytitle); // inputタグ
            e.find('.contents').text(mytext);
        });
//        return false;
    },
    "ctxAdd": function (obj) {
        var sectitle = obj.find("h2").text();
        $("#add_dialog").floatWindow(function (e) {
            e.find('.section').text(sectitle);
            e.find('.title').attr("value",''); // inputタグ
            e.find('.contents').text('');
        });
//        return false;
    },
    "ctxDel": function (obj) {
        alert(obj.attr("id") + "/" + obj.attr("class")+"\n"+obj.text());
        alert('ミカン');
        return false;
    },
// セクション編集メニュー
    "ctxSecEdit": function (obj) {
        alert(obj.attr("id") + "/" + obj.attr("class")+"\n"+obj.text());
        alert('りんご');
        return false;
    },
    "ctxSecAdd": function (obj) {
        alert(obj.attr("class")+"\n"+obj.text());
        alert('いちご');
        return false;
    },
    "ctxSecDel": function (obj) {
        alert(obj.attr("id") + "/" + obj.attr("class")+"\n"+obj.text());
        alert('すいか');
        return false;
    },
};
