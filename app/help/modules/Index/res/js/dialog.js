//
$("#update_paragraph").click(function () {
    var form = $(this).parents('.dialog-form');
    var myid  = form.attr("id");
    var secid = form.attr("data-parent");
    var disp  = form.find('input[name="dispno"]').val(); // inputタグ
    var title = form.find('input[name="title"]').val(); // inputタグ
    var contents = form.find('.contents').val();        // textarea
    var index = $('.tabmenu .tab li.selected').index();
    var url = location.pathname.controller_path("paragraph/update/" + myid);
//    alert(secid+"\n"+url);
    $.post(url, {
        "id"        : Number(myid),
        "disp_id"   : Number(disp),
        "section_id": Number(secid),
        "title"     : title,
        "contents"  : contents,
        "TabSelect" : Number(index),
        },
        function(data){
            //リクエストが成功した際に実行する関数
//            alert(data);
            location.href = data ;
        })
        .fail(function() {
            alert( "error:"+url );
        });
    return false;
});

//
$("#new_paragraph").click(function () {
    var form = $(this).parents('.dialog-form');
    var secid = form.attr("data-parent");
    var disp  = form.find('input[name="dispno"]').val(); // inputタグ
    var title = form.find('input[name="title"]').val(); // inputタグ
    var contents = form.find('.contents').val();        // textarea
    var index = $('.tabmenu .tab li.selected').index();
    var url = location.pathname.controller_path("paragraph/add");
//    alert(secid+"\n"+url);
    $.post(url, {
        "disp_id"   : Number(disp),
        "section_id": Number(secid),
        "title"     : title,
        "contents"  : contents,
        "TabSelect" : Number(index),
        },
        function(data){
            //リクエストが成功した際に実行する関数
//            alert(data);
            location.href = data ;
        })
        .fail(function() {
            alert( "error:"+url );
        });
    return false;
});
/*
    セクションデータの操作
*/
$("#update_section").click(function () {
    var form = $(this).parents('.dialog-form');
    var myid = form.attr("id");                     // Section-ID
    var pid = form.attr("data-parent");             // Chapter-ID
    var disp = form.find('input[name="dispno"]').val(); // 表示順
    var title = form.find('input[name="title"]').val(); // セクションタイトル
    var short_title = form.find('input[name="short_title"]').val(); // タブ表示名
    var contents = form.find('.contents').val();        // textarea
    var index = $('.tabmenu .tab li.selected').index();
    var url = location.pathname.controller_path("section/update/" + myid);
//    alert(secid+"\n"+url);
    $.post(url, {
        "id"        : Number(myid),
        "disp_id"   : Number(disp),
        "chapter_id": Number(pid),
        "title"     : title,
        "short_title": short_title,
        "contents"  : contents,
        "TabSelect" : Number(index),
        },
        function(data){
            //リクエストが成功した際に実行する関数
//            alert(data);
            location.href = data ;
        })
        .fail(function() {
            alert( "error:"+url );
        });
    return false;
});
$("#new_section").click(function () {
    var form = $(this).parents('.dialog-form');
    var pid = form.attr("data-parent");              // Chapter-ID
    var disp = form.find('input[name="dispno"]').val(); // 表示順
    var title = form.find('input[name="title"]').val(); // セクションタイトル
    var short_title = form.find('input[name="short_title"]').val(); // タブ表示名
    var contents = form.find('.contents').val();        // textarea
    var index = $('.tabmenu .tab li.selected').index();
    var url = location.pathname.controller_path("section/add");
//    alert(pid+"\n"+url);
    $.post(url, {
        "disp_id"   : Number(disp),
        "chapter_id": Number(pid),
        "title"     : title,
        "short_title": short_title,
        "contents"  : contents,
        "TabSelect" : Number(index),
        },
        function(data){
            //リクエストが成功した際に実行する関数
//            alert(data);
            location.href = data ;
        })
        .fail(function() {
            alert( "error:"+url );
        });
    return false;
});

$(".add-section").click(function () {
    var pid = $('.tabmenu .tab').attr('data-parent');   // チャプターID
    $("#add_section_dialog").floatWindow(function (e) {
        e.find('.dialog-form').attr("data-parent", pid);
        e.find('input[name="dispno"]').attr("value", ''); // 表示順
        e.find('input[name="title"]').attr("value", ''); // セクションタイトル
        e.find('input[name="short_title"]').attr("value", ''); // タブ名
        e.find('.contents').text('');                   // セクション概要
    });
    return false;
});
