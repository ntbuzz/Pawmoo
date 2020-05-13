//
//==============================================================================
//   セクションを追加するダイアログ表示＆書き込み処理
$(".add-section").click(function (e) {
    if (ChapterData.length == 0) {
        alert("チャプターが選択されていません！");
        return false;
    }
    var obj = $('.tabmenu .tab');
    var sec_obj = {
        chapter_id: ChapterData.id,
        chapter:    ChapterData.title,
        disp_id:    0,
        title:      '',
        short_title:'',
        contents:   '',
    };
    $("#edit_section_dialog").floatWindow(sec_obj, function (e) {
        e["TabSelect"] = $('.tabmenu .tab li.selected').index();
        var url = location.pathname.controller_path("section/add");
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
//******************************************************************************
//  ツールバーメニューのクリックアクション
//==============================================================================
//   パートレコード編集ダイアログ表示＆書き込み処理
$("#part_edit").click(function () {
    if (PartData.length == 0) {
        alert("パートが選択されていません！");
        return false;
    }
    $("#edit_part_dialog").floatWindow(PartData, function (e) {
        e["TabSelect"] = $('.tabmenu .tab li.selected').index();
        var url = location.pathname.controller_path("part/update") + e["id"];
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
        e["TabSelect"] = $('.tabmenu .tab li.selected').index();
        var url = location.pathname.controller_path("part/add");
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
//   パートレコードを削除
$("#part_del").click(function () {
    if (PartData.length == 0) {
        alert("パートが選択されていません！");
        return false;
    }
    if (confirm(PartData.title + ' を削除しますか？')) {
        var id = PartData.id;
        var url = location.pathname.controller_path("part/delete") + id;
        $.post(url, function (data) { //リクエストが成功した際に実行する関数
            location.href = data;
        })
            .fail(function () {
                alert("error:" + url);
            });
    }
    return false;
});
//==============================================================================
//   セクションを追加するダイアログ表示＆書き込み処理
$("#chap_edit").click(function () {
    if (ChapterData.length == 0) {
        alert("チャプターが選択されていません！");
        return false;
    }
    $("#edit_chapter_dialog").floatWindow(ChapterData, function (e) {
        e["TabSelect"] = $('.tabmenu .tab li.selected').index();
        var url = location.pathname.controller_path("chapter/update") + e["id"];
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
    if (PartData.length == 0) {
        alert("追加するパートが選択されていません！");
        return false;
    }
    var chap_obj = {
        part_id:    PartData.id,
        disp_id:    0,
        title:      '',
        contents:   '',
    };
    $("#add_chapter_dialog").floatWindow(chap_obj, function (e) {
        e["TabSelect"] = $('.tabmenu .tab li.selected').index();
        var url = location.pathname.controller_path("chapter/add");
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
//   チャプターレコードを削除
$("#chap_del").click(function () {
    if (ChapterData.length == 0) {
        alert("チャプターが選択されていません！");
        return false;
    }
    if(confirm(ChapterData.title+' を削除しますか？')){
        /*　OKの時の処理 */
        var id = ChapterData.id;
        var url = location.pathname.controller_path("chapter/delete")+id;
        $.post(url, function (data) { //リクエストが成功した際に実行する関数
            alert(data);
            location.href = data;
        })
        .fail(function () {
            alert("error:" + url);
        });
    }
    return false;
});
//==============================================================================
//   テキストをクリップボードへコピー
$("#ctxCopy").click(function () {
//    alert('COPY');
    document.execCommand('copy');
    return false;
});

