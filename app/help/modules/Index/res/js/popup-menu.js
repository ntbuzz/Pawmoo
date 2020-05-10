/*
    ポップアップメニューを処理するコールバック関数
*/
var popup_menu_function = {
    "ctxEdit": function (obj) {
        var sectitle = obj.parents('.section').find(".title").text();
        var mytitle = obj.find(".caption").text();
        var mytext = obj.find(".data").html();
        var myid = obj.attr("id");
        var mydisp = obj.attr("data-disp")
        var secid = obj.attr("data-parent")
        $("#edit_dialog").floatWindow(function (e) {
            e.find('.dialog-form').attr("id", myid);
            e.find('.dialog-form').attr("data-parent", secid);
            e.find('.section').text(sectitle);
            e.find('input[name="dispno"]').attr("value",mydisp); // inputタグ
            e.find('input[name="title"]').attr("value",mytitle); // inputタグ
            e.find('.contents').html(mytext);
        });
//        return false;
    },
    "ctxIns": function (obj) {
        var sectitle = obj.parents('.section').find(".title").text();
        var mydisp = obj.attr("data-disp")
        var secid = obj.attr("data-parent")
//        alert(secid);
        $("#add_dialog").floatWindow(function (e) {
            e.find('.dialog-form').attr("data-parent", secid);
            e.find('.section').text(sectitle);
            e.find('input[name="dispno"]').attr("value",mydisp-1); // inputタグ
            e.find('input[name="title"]').attr("value",''); // inputタグ
            e.find('.contents').text('');
        });
//        return false;
    },
    "ctxAdd": function (obj) {
        var sectitle = obj.find(".title").text();
        var secid = obj.attr("id");
//        alert(secid);
        $("#add_dialog").floatWindow(function (e) {
            e.find('.dialog-form').attr("data-parent", secid);
            e.find('.section').text(sectitle);
            e.find('input[name="dispno"]').attr("value",''); // inputタグ
            e.find('input[name="title"]').attr("value",''); // inputタグ
            e.find('.contents').text('');
        });
//        return false;
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
        var mytitle = obj.find(".title").text();
        var mytext = obj.find(".description").html();
        var myid = obj.attr("id");
        var mydisp = obj.attr("data-disp")
        var pid = $('.tabmenu .tab').attr('data-parent');   // チャプターID
        var short_title = $('.tabmenu .tab li.selected').text();   // タブ表示名
        $("#edit_section_dialog").floatWindow(function (e) {
            e.find('.dialog-form').attr("id", myid);
            e.find('.dialog-form').attr("data-parent", pid);
            e.find('input[name="dispno"]').attr("value",mydisp); // 表示順
            e.find('input[name="title"]').attr("value",mytitle); // セクションタイトル
            e.find('input[name="short_title"]').attr("value",short_title); // タブ名
            e.find('.contents').html(mytext);                   // セクション概要
        });
//        return false;
    },
    "ctxSecAdd": function (obj) {
/*
        var pid = $('.tabmenu .tab').attr('data-parent');   // チャプターID
        $("#add_section_dialog").floatWindow(function (e) {
            e.find('.dialog-form').attr("id", pid);
            e.find('input[name="dispno"]').attr("value",''); // 表示順
            e.find('input[name="title"]').attr("value",''); // セクションタイトル
            e.find('input[name="short_title"]').attr("value",''); // タブ名
            e.find('.contents').text('');                   // セクション概要
        });
*/      $(".add-section").click();
        return false;
    },
    "ctxSecDel": function (obj) {
        var myid = obj.attr("id");
        var url = location.pathname.controller_path("section/delete/" + myid);
        $.post(url,
            function(data){
            //リクエストが成功した際に実行する関数
                location.href = data ;
            })
        .fail(function() {
            alert( "error:"+url );
        });
        return false;
    },
};
