/*
    Objectのダンプ方法
        alert(JSON.stringify(sec_obj, null, '\t'));
        alert(objDump(para_obj));
    ポップアップメニューを処理するコールバック関数
*/
var popup_menu_function = {
    "ctxEdit": function (obj) {
        // JSONメソッドを使ってレコードデータを入手する
        var url = location.origin + location.pathname.controller_path("paragraph/json") + obj.attr("id");
//        alert(url);
        $.getJSON(url, function (para_obj) {
            // フォームにパラメータをセットし、完了時の処理関数を登録する
            $("#paragraph_dialog").floatWindow("段落を編集",para_obj, function (e) {
                e["TabSelect"] = $('.tabmenu .tab li.selected').index();
                var url = location.pathname.controller_path("paragraph/update") + e["id"];
//                alert("段落編集\n"+url+"\n"+objDump(e));
                $.post(url, e, function (data) { //リクエストが成功した際に実行する関数
//                    alert(data);
                    location.href = data ;
                }).fail(function() {
                    alert( "error:"+url );
                });
                return false;
            });
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.log("jqXHR:"+jqXHR.status);
            console.log("status:"+textStatus);
            console.log("error:"+errorThrown);
        });
    },
    "ctxIns": function (obj) {
        var para_obj = {        // フォームのデータを生成
            section_id: obj.attr('data-parent'),   // チャプターID
            section:    obj.parents('.section').find(".title").text(),
            disp_id:    obj.attr("data-disp") - 1,
            title:      '',
            contents:   '',
        };
        $("#paragraph_dialog").floatWindow("段落を挿入",para_obj, function (e) {
            e["TabSelect"] = $('.tabmenu .tab li.selected').index();
            var url = location.pathname.controller_path("paragraph/add");
            $.post(url, e,function(data){ //リクエストが成功した際に実行する関数
                location.href = data ;
            }).fail(function() {
                alert( "error:"+url );
            });
            return false;
        });
    },
    "ctxAdd":"#add-paragraph",
    "ctxDel": function (obj) {
        var myid = obj.attr("id");
        var url = location.pathname.controller_path("paragraph/delete") + myid;
        var e = { TabSelect: $('.tabmenu .tab li.selected').index() };  // タブ選択用
        $.post(url, e, function (data) {           //リクエストが成功した際に実行する関数
            location.href = data ;
        }).fail(function() {
            alert( "error:"+url );
        });
    },
    "ctxClear": function (obj) {
        var sec_id = obj.attr("id");
        var sec_ttl = obj.attr("value");
        if (confirm(sec_ttl + ' の段落をクリアしますか？')) {
            var url = location.pathname.controller_path("paragraph/clear") + sec_id;
            var e = { TabSelect: $('.tabmenu .tab li.selected').index() };  // タブ選択用
            $.post(url, e, function (data) {           //リクエストが成功した際に実行する関数
                location.href = data ;
            }).fail(function() {
                alert( "error:"+url );
            });
        }
    },
// セクション編集メニュー
    "ctxSecEdit":"#edit-section",
    "ctxSecAdd": ".add-section",
    "add_section":".add-section",
    "ctxSecDel": "#delete-section",
    "ctxCopy1": "#ctxCopy", // クリックオブジェクトの指定
    "ctxCopy2": "#ctxCopy", // クリックオブジェクトの指定
};
