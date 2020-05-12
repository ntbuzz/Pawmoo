//
//==============================================================================
//   セクションを追加するダイアログ表示＆書き込み処理
$(".add-section").click(function (e) {
    var obj = $('.tabmenu .tab');
    var sec_obj = {
        chapter_id: obj.attr('data-parent'),   // チャプターID
        disp_id:    0,
        title:      '',
        short_title:'',
        contents:   '',
    };
    $("#edit_section_dialog").floatWindow(sec_obj, function (e) {
        var index = $('.tabmenu .tab li.selected').index();
        e["TabSelect"] = Number(index);
        var url = location.pathname.controller_path("section/add/");
        $.post(url, e,
            function (data) { //リクエストが成功した際に実行する関数
                location.href = data;
            })
            .fail(function () {
                alert("error:" + url);
            });
        return false;
    });
});
//==============================================================================
//   パートレコード編集ダイアログ表示＆書き込み処理
$("#part_edit").click(function () {
    $("#edit_part_dialog").floatWindow(PartData, function (e) {
        var index = $('.tabmenu .tab li.selected').index();
        e["TabSelect"] = Number(index);
        var url = location.pathname.controller_path("part/update/") + e["id"];
        $.post(url, e,
            function (data) { //リクエストが成功した際に実行する関数
                location.href = data;
            })
            .fail(function () {
                alert("error:" + url);
            });
        return false;
    });
});
//==============================================================================
//   パートレコードを追加するダイアログ表示＆書き込み処理
$("#part_add").click(function () {
    var part_obj = {
        disp_id:    0,
        title:      '',
        contents:   '',
    };
    $("#add_part_dialog").floatWindow(part_obj, function (e) {
        var index = $('.tabmenu .tab li.selected').index();
        e["TabSelect"] = Number(index);
        var url = location.pathname.controller_path("part/add/");
        $.post(url, e,
            function (data) { //リクエストが成功した際に実行する関数
                location.href = data;
            })
            .fail(function () {
                alert("error:" + url);
            });
        return false;
    });
});
//==============================================================================
//   セクションを追加するダイアログ表示＆書き込み処理
$("#chap_edit").click(function () {
    $("#edit_chapter_dialog").floatWindow(ChapterData,function (e) {
        var index = $('.tabmenu .tab li.selected').index();
        e["TabSelect"] = Number(index);
        var url = location.pathname.controller_path("part/update/") + e["id"];
        $.post(url, e,
            function (data) { //リクエストが成功した際に実行する関数
                location.href = data;
            })
            .fail(function () {
                alert("error:" + url);
            });
        return false;
    });
});
//==============================================================================
//   チャプターレコードを追加するダイアログ表示＆書き込み処理
$("#chap_add").click(function () {
    var chap_obj = {
        part_id:    ChapterData.part_id,
        disp_id:    0,
        title:      '',
        contents:   '',
    };
    $("#add_chapter_dialog").floatWindow(chap_obj, function (e) {
        var index = $('.tabmenu .tab li.selected').index();
        e["TabSelect"] = Number(index);
        var url = location.pathname.controller_path("part/update/") + e["id"];
        $.post(url, e,
            function (data) { //リクエストが成功した際に実行する関数
                location.href = data;
            })
            .fail(function () {
                alert("error:" + url);
            });
        return false;
    });
    return false;
});
//==============================================================================
//   チャプターレコードを追加するダイアログ表示＆書き込み処理
$("#chap_del").click(function () {
    var target = $(".tabmenu .tab");
    var id = target.attr("data-parent");
    var url = location.pathname.method_path("chapter/delete")+id;
    alert(url + "\n will be DELETE!");
    return false;
});
$("#ctxCopy").click(function () {
//    alert('COPY');
    document.execCommand('copy');
    return false;
});

