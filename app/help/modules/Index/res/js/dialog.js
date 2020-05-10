//
$("#update_paragraph").click(function () {
    var form = $(this).parents('.dialog-form');
    var idkey = form.find('input:hidden[name="id-key"]').val(); // inputタグ
    var disp = form.find('input[name="dispno"]').val(); // inputタグ
    var title = form.find('input[name="title"]').val(); // inputタグ
    var contents = form.find('.contents').val();        // textarea
    var secid = form.find('.section').attr("id");
    var index = $('.tabmenu .tab li.selected').index();
    var url = location.pathname.controller_path("paragraph/update/" + idkey);
//    alert(secid+"\n"+url);
    $.post(url, {
        "id"        : Number(idkey),
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
    var disp = form.find('input[name="dispno"]').val(); // inputタグ
    var title = form.find('input[name="title"]').val(); // inputタグ
    var contents = form.find('.contents').val();        // textarea
    var secid = form.find('.section').attr("id");
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
