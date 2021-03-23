//===============================================
// アップロード処理のプログレスバーチェイン
<<<<<<< HEAD
function ProgressBar(child, f, callback) {
=======
// FormData(fmd) の中に複数のfileが定義可能
function ProgressBar(child, fmd, callback) {
>>>>>>> dev/master
    var self = this;
    self.finishCallback = callback;
    self.child_link = child;
    self.Aborted = false;
<<<<<<< HEAD
    self.Form = new FormData();
    self.Form.append('file', f);
=======
>>>>>>> dev/master
    self.jqxhr = null;
    self.progress_Bar = $('<div class="progress-Bar"></div>');
    self.progressPanel = $('<div class="progress-panel"></div>').appendTo(self.progress_Bar);
    self.Cancel = $('<div class="cancel-button" title="${#core.CancelTitle}">x</div>').appendTo(self.progress_Bar);
    self.FileName = $('<span class="filename left"></span>').appendTo(self.progressPanel);
    self.FileSize = $('<span class="filesize right"></span>').appendTo(self.progressPanel);
    self.gainBar = $('<div class="progress-gain"></div>').appendTo(self.progressPanel);
    self.Cancel.click(function () {
<<<<<<< HEAD
        if (confirm(f.name + "${#core.Confirm}")) {
=======
        if (confirm(fmd.get('name') + "${#core.Confirm}")) {
>>>>>>> dev/master
            self.Abort(false);
        }
    });
    // ファイル情報を表示
<<<<<<< HEAD
    if (f.size>0) {
        for (i = 0, sz = f.size; sz > 1024; i += 3, sz /= 1024) ;
=======
    sz = fmd.get('size');
    if (sz>0) {
        for (i = 0; sz > 1024; i += 3, sz /= 1024) ;
>>>>>>> dev/master
        szStr = sz.toFixed(2) +" B  KB MB GB TB PB".substr(i,3);
        if (i >= 9 && sz > 1.0) {
            szStr = "Size Over > 1.0 GB";
            self.Aborted = true;
        }
    } else {
        szStr = "Size ZERO or Directory";
        self.Aborted = true;
    }
    self.FileSize.html(szStr);
<<<<<<< HEAD
    self.FileName.html(f.name);
=======
    self.FileName.html(fmd.get('name'));
>>>>>>> dev/master
    // 完了処理
    self.Finished = function (aborted) {
        self.Cancel.css('display','none');
        var cls = (aborted) ? 'error' : 'complete';
        self.progressPanel.addClass(cls);
        self.finishCallback(aborted);
    }
    // 送信を中止
    self.Abort = function (propagate) {
        self.Aborted = true;
        if(self.jqxhr != null) self.jqxhr.abort();
        if (propagate && self.child_link != null) self.child_link.Abort(true);
    }
    self.AjaxStart = function (url) {
<<<<<<< HEAD
=======
        self.progress_Bar.css('display', 'flex');
>>>>>>> dev/master
        if (self.child_link != null) self.child_link.AjaxStart(url);
        if (self.Aborted) {
            self.Finished(true);    // ERROR または ABORT
            return;
        }
        self.jqxhr = $.ajax({
            url: url,
            type: 'POST',
            async: true,
            contentType: false,
            processData: false,
            cache: false,
<<<<<<< HEAD
            data: self.Form,
=======
            data: fmd,
>>>>>>> dev/master
            xhr: function () {
                var xhrobj = $.ajaxSettings.xhr();
                if (xhrobj.upload) {
                    xhrobj.upload.addEventListener('progress', function (e) {
                        var percent = parseInt(e.loaded / e.total * 100);
                        self.gainBar.width(percent+'%').html(percent+'%');
<<<<<<< HEAD
                    });
=======
                    },false);
>>>>>>> dev/master
                }
                return xhrobj;
            },
            success: function (data) {
<<<<<<< HEAD
//                alert(data);
=======
                alert("Respons:"+url+"\n"+data);
>>>>>>> dev/master
                self.Finished(false);
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                if(!self.Aborted) {
                    alert(
                        'XMLHttpRequest:' + XMLHttpRequest.status + "\n" +
                        'textStatus:' + textStatus + "\n" +
                        'errorThrown:' + errorThrown.message
                    );
                }
                self.Finished(true);    // ERROR または ABORT
            },
        });
    }
<<<<<<< HEAD
};
// マルチファイルアップロード
function UploadFiles(files) {
    var self = this;
    self.finishCallback = undefined;
=======
    return self;
};
// マルチファイルアップロード
function UploadFiles(files,url, callback) {
    var self = this;
    self.finishCallback = callback;
>>>>>>> dev/master
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
    // ダイアログボックス
    var dialog = $('<div class="progress-dialog"></div>').appendTo(bk_panel);
    // メッセージ表示
    var msg = $('<span></span>').appendTo(dialog);
    // 進捗表示用のボックス
    var obj = $('<div class="progress-box"></div>').appendTo(dialog);
    // 残りメッセージをプロセスバーの後ろに表示
    var rest = $('<span class="message" id="upfiles"></span>').appendTo(dialog);
    // 中止ボタンを追加するためのバー
    var button_bar = $('<div class="buttonBar"></div>');
    var cancel_close = $('<span class="button">${#core.Abort}</span>').appendTo(button_bar);
    cancel_close.off().click(function () { self.topBar.Abort(true);});
    dialog.append(button_bar);
    // プロセスバーのファイルリストを作成
    self.topBar = null;
    for (var i = 0; i < files.length; ++i) {
<<<<<<< HEAD
        var next = new ProgressBar(self.topBar,files[i],function (aborted) {
=======
        var form = new FormData();
        form.append('name', files[i].name);
        form.append('file', files[i]);
        form.append('size', files[i].size);
        var next = new ProgressBar(self.topBar,form,function (aborted) {
>>>>>>> dev/master
            // 中止または完了時の処理
            if(upload.result(aborted) <= 0) self.CloseWait();;
            self.RestMessage(upload.rest);
        });
        obj.append(next.progress_Bar);
        self.topBar = next;
    }
    $('body').append(bk_panel);
<<<<<<< HEAD
    // メソッド定義
    self.RestMessage = function (n) {
        rest.text("${#core.RestFiiles}" + n);
    }
=======
    if (self.topBar === null) {
        self.CloseWait();
        alert("FILES EMPTY!!");
        return;
    }
    // メッセージ表示
    self.RestMessage = function (n) {
        rest.text("${#core.RestFiiles}" + n);
    }
    // ダイアログを閉じる
    self.CloseDialog = function () {
        bk_panel.fadeOut("fast");
        bk_panel.remove();
    }
>>>>>>> dev/master
    self.CloseWait = function () {
        if (self.finishCallback != undefined) self.finishCallback(upload);
        if (!upload.abort) {
            self.CloseDialog();  // ABORTせずに完了したら即ダイアログを閉じる
        } else {
            msg.text('${#core.AbortDone}'); // ABORTしていたら確認用に閉じるボタン表示
            cancel_close.text('${#core.Close}').off().click(function () {
                self.CloseDialog();
            });
        }
    }
<<<<<<< HEAD
    // ダイアログを閉じる
    self.CloseDialog = function () {
        bk_panel.fadeOut("fast");
        bk_panel.remove();
    }
    // アップロード実行
    self.Execute = function (url, callback) {
        if (self.topBar === null) {
            self.CloseWait();
            alert("FILES EMPTY!!");
            return;
        }
        msg.text('${#core.Uploading}');
        self.finishCallback = callback;
        bk_panel.fadeIn('fast');
        self.RestMessage(upload.rest);
        self.topBar.AjaxStart(url);
    }
}
/* dropfile使い方案
    $(セレクタ).drop_files(uploadURL,callback_func(res){
            res.abort
            res.complete
            res.total
    });
=======
    // アップロード実行
    msg.text('${#core.Uploading}');
    bk_panel.fadeIn('fast');
    self.RestMessage(upload.rest);
    self.topBar.AjaxStart(url);
}
/* ===============================================
    ファイルを一つずつアップロードする
>>>>>>> dev/master
*/
(function ($) {
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
<<<<<<< HEAD
            obj = new UploadFiles(files);
            obj.Execute(uploadURL, callback);
=======
            UploadFiles(files,uploadURL, callback);
>>>>>>> dev/master
        },
    });
};
})(jQuery);
