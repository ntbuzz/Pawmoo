//===============================================
// アップロード処理のプログレスバーチェイン
// FormData(fmd) の中に複数のfileが定義可能
// name,size 引数は IE11 対策
function ProgressBar(child, fmd, name,size,callback_func) {
    var self = this;
    self.child_link = child;
    self.Aborted = false;
    self.jqxhr = null;
    self.progress_Bar = $('<div class="progress-Bar"></div>');
    self.progressPanel = $('<div class="progress-panel"></div>').appendTo(self.progress_Bar);
    self.Cancel = $('<div class="cancel-button" title="${#.core.CancelTitle}">x</div>').appendTo(self.progress_Bar);
    self.FileName = $('<span class="filename left"></span>').appendTo(self.progressPanel);
    self.FileSize = $('<span class="filesize right"></span>').appendTo(self.progressPanel);
    self.gainBar = $('<div class="progress-gain"></div>').appendTo(self.progressPanel);
    self.Cancel.click(function () {
        if (confirm("${#.core.Confirm}".replace('%s',name))) { // fmd.get('name')))) {
            self.Abort(false);
        };
    });
    // ファイル情報を表示
    sz = size;  // fmd.get('size');
    if (sz>0) {
        for (i = 0; sz > 1024; i += 3, sz /= 1024) ;
        szStr = sz.toFixed(2) +" B  KB MB GB TB PB".substr(i,3);
        if (i >= 9 && sz > 1.0) {
            szStr = "Size Over > 1.0 GB";
            self.Aborted = true;
        };
    } else {
        szStr = "Size ZERO or Directory";
        self.Aborted = true;
    };
    self.FileSize.html(szStr);
    self.FileName.html(name);//fmd.get('name'));
    // 完了処理
    self.Finished = function (aborted) {
        self.Cancel.Visible(false);
        var cls = (aborted) ? 'error' : 'complete';
        self.progressPanel.addClass(cls);
        callback_func(aborted);
    };
    // 送信を中止
    self.Abort = function (propagate) {
        self.Aborted = true;
        if(self.jqxhr != null) self.jqxhr.abort();
        if (propagate && self.child_link != null) self.child_link.Abort(true);
    };
    self.AjaxStart = function (url) {
        self.progress_Bar.Visible('flex');
        if (self.child_link != null) self.child_link.AjaxStart(url);
        if (self.Aborted) {
            self.Finished(true);    // ERROR または ABORT
            return;
        };
        self.jqxhr = $.ajax({
            url: url,
            type: 'POST',
            async: true,
            contentType: false,
            processData: false,
            cache: false,
            data: fmd,
            xhr: function () {
                var xhrobj = $.ajaxSettings.xhr();
                if (xhrobj.upload) {
                    xhrobj.upload.addEventListener('progress', function (e) {
                        var percent = parseInt(e.loaded / e.total * 100);
                        self.gainBar.width(percent+'%').html(percent+'%');
                    },false);
                };
                return xhrobj;
            },
            success: function (data) {
//                alert("Respons:"+url+"\n"+data);
                self.Finished(false);
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                if(!self.Aborted) {
                    alert(
                        'XMLHttpRequest:' + XMLHttpRequest.status + "\n" +
                        'textStatus:' + textStatus + "\n" +
                        'errorThrown:' + errorThrown.message
                    );
                };
                self.Finished(true);    // ERROR または ABORT
            },
        });
    };
    return self;
};
//=============================================================================================
// マルチファイルアップロード
// 単独ファイルを複数同時にアップロード
function UploadFiles(files,url, callback_func) {
    var self = this;
    var topBar = null;
    var upload = {      // calback_func に渡すオブジェクト
        abort: false,
        complete: 0,
        total: files.length,
        rest:  files.length,
        result: function (ab) {
            this.abort = this.abort || ab;
            if (!ab) ++this.complete;
            return --this.rest;
        },
        aborted: function () {
            return (this.total - this.complete);
        },
    };
    // ダイアログ以外をクリックさせないため壁をつくる
    var bk_panel = $('<div class="progress-BK"></div>');
    var dialog = $('<div class="progress-dialog"></div>').appendTo(bk_panel);
    var upload_msg = $('<span></span>').appendTo(dialog);
    var obj = $('<div class="progress-box"></div>').appendTo(dialog);
    var rest = $('<span class="message" id="upfiles"></span>').appendTo(dialog);
    // 中止ボタンを追加するためのバー
    var button_bar = $('<div class="buttonBar"></div>');
    var cancel_close = $('<span class="button">${#.core.ABORT}</span>').appendTo(button_bar);
    cancel_close.off().click(function () {
        topBar.Abort(true);
    });
    dialog.append(button_bar);
    // プロセスバーのファイルリストを作成
    topBar = null;
    for (var i = 0; i < files.length; ++i) {
        var form = new FormData();
        form.append('name', files[i].name);
        form.append('file', files[i]);
        form.append('size', files[i].size);
        var next = new ProgressBar(topBar,form,files[i].name,files[i].size,function (aborted) {
            // 中止または完了時の処理
            if(upload.result(aborted) <= 0) self.CloseWait();;
            self.RestMessage(upload.rest);
        });
        obj.append(next.progress_Bar);
        topBar = next;
    };
    $('body').append(bk_panel);
    if (topBar === null) {
        self.CloseWait();
        alert("FILES EMPTY!!");
        return;
    };
    // メッセージ表示
    self.RestMessage = function (n) {
        rest.text("${#.core.RestFiiles}" + n);
    };
    // ダイアログを閉じる
    self.CloseDialog = function () {
        bk_panel.fadeOut("fast");
        bk_panel.remove();
    };
    self.CloseWait = function () {
        $.busy_cursor(false);
        if (callback_func != undefined) callback_func(upload);
        if (!upload.abort) {
            self.CloseDialog();  // ABORTせずに完了したら即ダイアログを閉じる
        } else {
            upload_msg.text('${#.core.AbortDone}'); // ABORTしていたら確認用に閉じるボタン表示
            cancel_close.text('${#.core.Close}').off().click(function () {
                self.CloseDialog();
            });
        };
    };
    // アップロード実行
    upload_msg.text('${#.core.Uploading}');
    bk_panel.fadeIn('fast');
    self.RestMessage(upload.rest);
    $.busy_cursor(true);
    topBar.AjaxStart(url);
};
//=============================================================================================
// マルチペアファイルアップロード
// ペアのファイルを個数制限付きでアップロード
function PairUploadDialog(files,url,callback_func) {
    var self = this;
    var upload = {      // calback_func に渡すオブジェクト
        abort: false,
        complete: 0,
        total: files.length,
        rest:  files.length,
        result: function (ab) {
            this.abort = this.abort || ab;
            if (!ab) ++this.complete;
            return --this.rest;
        },
        aborted: function () {
            return (this.total - this.complete);
        },
    };
    // ダイアログ以外をクリックさせないため壁をつくる
    var bk_panel = $('<div class="progress-BK"></div>');
    var dialog = $('<div class="progress-dialog"></div>').appendTo(bk_panel);
    var upload_msg = $('<span></span>').appendTo(dialog);
    // セカンドファイル名をセット
    self.SecondFileName = function (obj, file, fname) {
        fmsg = (file === undefined) ? "${#.core.ENTER}" :file.name;
        if (file !== undefined && fmsg.is_invalid_name()) alert("${#.core.BADFILE}");
        ttl = fname.split(".").reverse().slice(1).reverse().join(".");
        var cell = obj.find('td.second');
        if (file === undefined) cell.addClass('error');
        else cell.removeClass('error');
        cell.html(fmsg);
        obj.find('input[name=title]').val(ttl);
    };
    // プロセスバーのリスト生成
    for (var i = 0; i < files.length; ++i) {
        var f = files[i];
        var labels = "file_select_" + i;
        var panel = $('<div class="files-pair" id="' + i + '"></div>').appendTo(dialog);;
        var list = "${#.core.FILES}" + (i + 1) + "\
        <table><tr>\
        <th>${#.core.FIRSTFILE}</th><td class='first'>"+ f.name +"</td>\
        <th class='labels'><label for='"+labels+"'>${#.core.SECONDFILE}<input type='file' id='"+labels+"' name='second_file'></label></th>\
        <td class='second'></td></tr>\
        <tr class='title_bar'><th>${#.core.UPTITLE}</th><td colspan=3>\
        <input type='text' name='title' value=''></td>\
        </tr></table>";
        panel.append(list);
        self.SecondFileName(panel, undefined, f.name);
    };
    // アップロードするファイルを選択
    dialog.find('input[type=file]').change(function() {
        var file = $(this).prop('files')[0];
        var panel = $(this).closest('.files-pair');
        i = panel.attr('id');
        ref = (file === undefined) ? files[i] : file;
        self.SecondFileName(panel, file, ref.name);
    });
    // 残りメッセージをプロセスバーの後ろに表示
    var rest = $('<span class="message" id="upfiles"></span>').appendTo(dialog);
    // 中止ボタンを追加するためのバー
    var button_bar = $('<div class="buttonBar"></div>');
    var send_abort = $('<span class="button">${#.core.SEND}</span>').appendTo(button_bar);
    var cancel_close = $('<span class="button">${#.core.UNDO}</span>').appendTo(button_bar);
    cancel_close.off().click(function () {
        bk_panel.fadeOut("fast");
        bk_panel.remove();
    });
    dialog.append(button_bar);
    // 送信実行
    send_abort.off().click(function () {
        var secondf_set = true;
        $('.files-pair').each(function () {
            var num = $(this).attr('id');
            var ttl = $(this).find('input[name="title"]').val();
            var ff2 = $(this).find('input[type=file]').prop('files')[0];
            if (ff2 === undefined || ttl === "") {
                msg = (ff2 === undefined) ? "${#.core.NO_SECOND}":"${#.core.NO_TITLE}";
                alert(msg.replace('%s',files[num].name));
                secondf_set = false;
                return false;
            };
            return true;
        });
        if (!secondf_set) return false;
        // ２回目のループはチェック済の状態
        topBar = null;
        $('.files-pair').each(function () {
            var num = $(this).attr('id');
            var ttl = $(this).find('input[name="title"]').val();
            var ff2 = $(this).find('input[type=file]').prop('files')[0];
            var obj = new FormData();
            obj.append('num', num);     // 送信番号 0〜
            obj.append('name', ttl);    // タイトル
            obj.append('first', files[num]);    // ドロップファイル
            obj.append('second',ff2);           // 2nd ファイル
            obj.append('size', files[num].size + ff2.size);
            var next = new ProgressBar(topBar,obj,ttl,files[num].size + ff2.size,function (aborted) {
                if(upload.result(aborted) <= 0) self.CloseWait();;
                self.RestMessage(upload.rest);
            });
            $(this).append(next.progress_Bar);
            topBar = next;
            $(this).find('.title_bar').Visible(false);
            return true;
        });
        if (topBar === null) {
            self.CloseWait();
            alert("FILES EMPTY!!");
            return false;
        };
        upload_msg.text('${#.core.Uploading}');
        self.RestMessage(upload.rest);
        dialog.find('label').each(function () {
            $(this).Visible(false); // 押せないように消す
            $(this).closest('th').html("${#.core.SECONDFILE}");
        });
        cancel_close.Visible(false);   // 押せないように消す
        $(this).text('${#.core.ABORT}');     // 送信→中止ボタンに切替
        $(this).off().click(function () {
            self.topBar.Abort(true);
            $(this).Visible(false); // 押せないように消す
        });
        $.busy_cursor(true);
        topBar.AjaxStart(url);
    });
    // 残りファイル数表示
    self.RestMessage = function (n) {
        rest.text("${#.core.RestFiiles}" + n);
    };
    self.CloseWait = function () {
        $.busy_cursor(false);
        if (callback_func != undefined) callback_func(upload);
        if (!upload.abort) {
            cancel_close.click();
        } else {
            upload_msg.text('${#.core.AbortDone}'); // ABORTしていたらメッセージを表示
            send_abort.Visible(false);        // 送信/中止ボタンは押せないように消す
            cancel_close.text('${#.core.Close}');      // 閉じるに変更
            cancel_close.Visible('inline');     // 押せるように再表示
        };
    };
    $('body').append(bk_panel);
    bk_panel.fadeIn('fast');
};
//----------------------------------
function isFileCharsetOK(files) {
    for (var i = 0; i < files.length; ++i) {
        fname = files[i].name;
        // URL禁則文字のチェック
        if (fname.is_invalid_name()) {
            if (confirm("${#.core.BADFILE}")) {
                return false;
            };
        };
    };
    return true;
};
(function ($) {
/* ===============================================
    ファイルを一つずつアップロードする
*/
$.fn.dropfiles = function (uploadURL, callback) {
    var self = this;
    self.on({
        'dragenter': function (e) {
            e.stopPropagation();
            e.preventDefault();
            self.addClass('drag-over');
        },
        'dragleave': function (e) {
            e.stopPropagation();
            e.preventDefault();
            self.removeClass('drag-over');
        },
        'dragover': function (e) {
            e.stopPropagation();
            e.preventDefault();
        },
        'drop': function (e) {
            e.stopPropagation();
            e.preventDefault();
            self.removeClass('drag-over');
            var files = e.originalEvent.dataTransfer.files;
            if(isFileCharsetOK(files)) UploadFiles(files,uploadURL, callback);
        },
    });
};
/* ===============================================
    ファイルを２つで一回のアップロードを実行する
*/
$.fn.dropfiles2 = function (maxfiles,url, callback_func) {
    var self = this;
    self.on({
        'dragenter': function (e) {
            e.stopPropagation();
            e.preventDefault();
            self.addClass('drag-over');
        },
        'dragleave': function (e) {
            e.stopPropagation();
            e.preventDefault();
            self.removeClass('drag-over');
        },
        'dragover': function (e) {
            e.stopPropagation();
            e.preventDefault();
        },
        'drop': function (e) {
            e.stopPropagation();
            e.preventDefault();
            self.removeClass('drag-over');
            var files = e.originalEvent.dataTransfer.files;
            if (maxfiles>0  && files.length > maxfiles) {
                alert("${#.core.MAXFILE}".replace('%d',maxfiles));
                return false;
            };
            if(isFileCharsetOK(files)) PairUploadDialog(files, url,callback_func);
        },
    });
};
})(jQuery);
