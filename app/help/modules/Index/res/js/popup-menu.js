/*
    ポップアップメニューを処理するコールバック関数
*/
var popup_menu_function = {
    "ctxEdit": function (obj) {
        var sectitle = obj.parents('.section').find(".title").text();
        var mytitle = obj.find(".caption").text();
        var mytext = obj.find(".data").html();
        var idset = obj.attr("id").split('-');
        var myid = idset[1];
        var mydisp = idset[2];
        var secid = idset[0];
        $("#edit_dialog").floatWindow(function (e) {
            e.find('.section').text(sectitle);
            e.find('.section').attr("id",secid);
            e.find('input[name="dispno"]').attr("value",mydisp); // inputタグ
            e.find('input[name="title"]').attr("value",mytitle); // inputタグ
            e.find('.contents').html(mytext);
            e.find('input[name="id-key"]').attr("value",myid); // inputタグ
        });
//        return false;
    },
    "ctxAdd": function (obj) {
        var sectitle = obj.parents('.section').find(".title").text();
        var secid = obj.parents('li').attr("id");
        $("#add_dialog").floatWindow(function (e) {
            e.find('.section').text(sectitle);
            e.find('.section').attr("id",secid);
            e.find('input[name="dispno"]').attr("value",''); // inputタグ
            e.find('input[name="title"]').attr("value",''); // inputタグ
            e.find('.contents').text('');
        });
//        return false;
    },
    "ctxDel": function (obj) {
        var idset = obj.attr("id").split('-');
        var myid = idset[1];
        var url = location.pathname.controller_path("paragraph/delete/" + myid);
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
