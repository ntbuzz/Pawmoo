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
        $.getJSON(url, function (para_obj) {
            // フォームにパラメータをセットし、完了時の処理関数を登録する
            $("#edit_dialog").floatWindow(para_obj, function (e) {
                e["TabSelect"] = $('.tabmenu .tab li.selected').index();
                var url = location.pathname.controller_path("paragraph/update")+e["id"];
                $.post(url, e,
                    function(data){ //リクエストが成功した際に実行する関数
                        location.href = data ;
                    })
                    .fail(function() {
                        alert( "error:"+url );
                    });
                return false;
            });
        }).fail(function (jqXHR, textStatus, errorThrown) {
//            console.log("jqXHR:"+jqXHR.status);
//            console.log("status:"+textStatus);
//            console.log("error:"+errorThrown);
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
            e["TabSelect"] = $('.tabmenu .tab li.selected').index();
            var url = location.pathname.controller_path("paragraph/add");
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
    "ctxAdd": function (obj) {  // セクションブロックから呼び出される
        // フォームのデータを生成
        var para_obj = {
            section_id: obj.attr('id'),   // チャプターID
            section:    obj.find(".title").text(),
            disp_id:    0,
            title:      '',
            contents:   '',
        };
        $("#add_dialog").floatWindow(para_obj, function (e) {
            e["TabSelect"] = $('.tabmenu .tab li.selected').index();
            var url = location.pathname.controller_path("paragraph/add");
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
        var url = location.pathname.controller_path("paragraph/delete") + myid;
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
        // JSONメソッドを使ってレコードデータを入手する
        var url = location.origin + location.pathname.controller_path("section/json") + obj.attr("id");
        $.getJSON(url, function (sec_obj) {
            // フォームにパラメータをセットし、完了時の処理関数を登録する
            $("#edit_section_dialog").floatWindow(sec_obj, function (e) {
                e["TabSelect"] = $('.tabmenu .tab li.selected').index();
                var url = location.pathname.controller_path("section/update") + e["id"];
                $.post(url, e,
                    function(data){ //リクエストが成功した際に実行する関数
                        location.href = data ;
                    })
                    .fail(function() {
                        alert( "error:"+url );
                    });
                return false;
            });
        }).fail(function (jqXHR, textStatus, errorThrown) {
//            console.log("jqXHR:"+jqXHR.status);
//            console.log("status:"+textStatus);
//            console.log("error:"+errorThrown);
        });
    },
    "ctxSecAdd": ".add-section",
    "ctxSecDel": function (obj) {
        var myid = obj.attr("id");
        var url = location.pathname.controller_path("section/delete") + myid;
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
