/*
    Objectのダンプ方法
        alert(JSON.stringify(sec_obj, null, '\t'));
        alert(objDump(para_obj));
    ポップアップメニューを処理するコールバック関数
*/
var popup_menu_function = {
    "ctxEdit": function (obj) {
        // フォームのデータを生成
        var para_obj = {
            id:         obj.attr("id"),
            section_id: obj.attr('data-parent'),   // チャプターID
            section:    obj.parents('.section').find(".title").text(),
            disp_id:    obj.attr("data-disp"),
            title:      obj.find(".caption").text(),
            contents:   obj.find(".data").html(),
        };
        // フォームにパラメータをセットし、完了時の処理関数を登録する
        $("#edit_dialog").floatWindow(para_obj, function (e) {
            var index = $('.tabmenu .tab li.selected').index();
            e["TabSelect"] = Number(index);
            var url = location.pathname.controller_path("paragraph/update/")+e["id"];
            $.post(url, e,
                function(data){ //リクエストが成功した際に実行する関数
                    location.href = data ;
                })
                .fail(function() {
                    alert( "error:"+url );
                });
            return false;
        });
    },
    "ctxIns": function (obj) {
        // フォームのデータを生成
        var para_obj = {
            section_id: obj.attr('data-parent'),   // チャプターID
            section:    obj.parents('.section').find(".title").text(),
            disp_id:    obj.attr("data-disp") - 1,
            title:      '',
            contents:   '',
        };
        $("#add_dialog").floatWindow(para_obj, function (e) {
            var index = $('.tabmenu .tab li.selected').index();
            e["TabSelect"] = Number(index);
            var url = location.pathname.controller_path("paragraph/add/");
            $.post(url, e,
                function(data){ //リクエストが成功した際に実行する関数
                    location.href = data ;
                })
                .fail(function() {
                    alert( "error:"+url );
                });
            return false;
        });
    },
    "ctxAdd": function (obj) {
        // フォームのデータを生成
        var para_obj = {
            section_id: obj.attr('data-parent'),   // チャプターID
            section:    obj.parents('.section').find(".title").text(),
            disp_id:    0,
            title:      '',
            contents:   '',
        };
        $("#add_dialog").floatWindow(para_obj, function (e) {
            var index = $('.tabmenu .tab li.selected').index();
            e["TabSelect"] = Number(index);
            var url = location.pathname.controller_path("paragraph/add/");
            $.post(url, e,
                function(data){ //リクエストが成功した際に実行する関数
                    location.href = data ;
                })
                .fail(function() {
                    alert( "error:"+url );
                });
            return false;
        });
    },
    "ctxDel": function (obj) {
        var myid = obj.attr("id");
        var url = location.pathname.controller_path("paragraph/delete/" + myid);
        $.post(url,
            function(data){
            //リクエストが成功した際に実行する関数
                location.href = data ;
            })
        .fail(function() {
            alert( "error:"+url );
        });
//        return false;
    },
// セクション編集メニュー
    "ctxSecEdit": function (obj) {
        var sec_obj = {
            id:         obj.attr("id"),
            chapter_id: obj.attr('data-parent'),   // チャプターID
            disp_id:    obj.attr("data-disp"),
            title:      obj.find(".title").text(),
            short_title:obj.attr("value"),
            contents:   obj.find(".description").text(),
        };
        $("#edit_section_dialog").floatWindow(sec_obj, function (e) {
            var index = $('.tabmenu .tab li.selected').index();
            e["TabSelect"] = Number(index);
            var url = location.pathname.controller_path("section/update/") + e["id"];
            $.post(url, e,
                function(data){ //リクエストが成功した際に実行する関数
                    location.href = data ;
                })
                .fail(function() {
                    alert( "error:"+url );
                });
            return false;
        });
    },
    "ctxSecAdd": ".add-section",
    "ctxSecDel": function (obj) {
        var myid = obj.attr("id");
        var url = location.pathname.controller_path("section/delete/" + myid);
        $.post(url,
            function (data) {
                //リクエストが成功した際に実行する関数
                location.href = data;
            })
        .fail(function() {
            alert( "error:"+url );
        });
        return false;
    },
    "ctxCopy1": "#ctxCopy", // クリックオブジェクトの指定
    "ctxCopy2": "#ctxCopy", // クリックオブジェクトの指定
};
