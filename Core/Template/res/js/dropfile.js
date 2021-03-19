//===============================================
// アップロード処理のプログレスバーチェイン
function ProgressBar(chain, f, callback) {
    var self = this;
    self.finishCallback = callback;
    self.abort_chain = chain;
    self.Aborted = false;
    self.Form = new FormData();
    self.Form.append('file', f);
    self.jqxhr = null;
    self.progress_Bar = $('<div class="progress-Bar"></div>');
    self.progressPanel = $('<div class="progress-panel"></div>').appendTo(self.progress_Bar);
    self.Cancel = $('<div class="cancel-button" title="${#core.CancelTitle}">X</div>').appendTo(self.progress_Bar);
    self.FileName = $('<span class="filename left"></span>').appendTo(self.progressPanel);
    self.FileSize = $('<span class="filesize right"></span>').appendTo(self.progressPanel);
    self.gainBar = $('<div class="progress-gain"></div>').appendTo(self.progressPanel);
    self.Cancel.click(function () {
        if (confirm(f.name + "${#core.Confirm}")) {
            self.Aborted = true;
            if(self.jqxhr != null) self.jqxhr.abort();
        }
    });
    // ファイル上表を表示
    self.FileName.html(f.name);
    if (f.size>0) {
        for (i = 0, sz = f.size; sz > 1024; i += 3, sz /= 1024) ;
        var szStr = sz.toFixed(2) +" B  KB MB GB TB PB".substr(i,3);
        self.FileSize.html(szStr);
        if (i >= 9 && sz > 1.0) {
            self.progressPanel.html("'"+f.name + "' is Size Over > 1.0 GB");
            self.Aborted = true;
        }
    } else {
        self.progressPanel.html("'"+f.name + "' is ZERO File or Directory.");
        self.Aborted = true;
    }
    // 完了処理
    self.Finished = function (aborted) {
        self.Cancel.css('display','none');
        var cls = (aborted) ? 'error' : 'complete';
        self.progressPanel.addClass(cls);
        self.finishCallback(aborted);
    }
    self.Abort = function () {
        self.Aborted = true;
        if(self.jqxhr != null) self.jqxhr.abort();
        if (self.abort_chain != null) self.abort_chain.Abort();
    }
    self.AjaxStart = function (url) {
        if (self.abort_chain != null) self.abort_chain.AjaxStart(url);
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
            data: self.Form,
            xhr: function () {
                var xhrobj = $.ajaxSettings.xhr();
                if (xhrobj.upload) {
                    xhrobj.upload.addEventListener('progress', function (e) {
                        var percent = parseInt(e.loaded/e.total*100);
                        self.gainBar.width(percent+'%').html(percent+'%');
                    });
                }
                return xhrobj;
            },
            success: function (data) {
//                alert(data);
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
};
// マルチファイルアップロード
function UploadFiles(files) {
    var self = this;
    var upload = {      // calback_func に渡すオブジェクト
        abort: false,
        complete: 0,
        total: files.length,
        result: function (ab) {
            this.abort = this.abort || ab;
            if(!ab) ++this.complete;
        },
        aborted: function () {
            return (this.total - this.complete);
        },
    };
    self.RestCount = upload.total;
    self.finishCallback = undefined;
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
    cancel_close.off().click(function () { self.topBar.Abort();});
    dialog.append(button_bar);
    // プロセスバーのファイルリストを作成
    self.topBar = null;
    for (var i = 0; i < files.length; ++i) {
        var next = new ProgressBar(self.topBar,files[i],function (aborted) {
            // 中止または完了時の処理
            --self.RestCount;
            self.RestMessage();
            upload.result(aborted);
            if (self.RestCount <= 0) self.CloseWait();
        });
        obj.append(next.progress_Bar);
        self.topBar = next;
    }
    $('body').append(bk_panel);
    // メソッド定義
    self.RestMessage = function () {
        rest.text("${#core.RestFiiles}" + self.RestCount);
    }
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
    // ダイアログを閉じる
    self.CloseDialog = function () {
        bk_panel.fadeOut("fast");
        bk_panel.remove();
    }
    // アップロード実行
    self.Execute = function (url, callback) {
        msg.text('${#core.Uploading}');
        self.finishCallback = callback;
        bk_panel.fadeIn('fast');
        self.RestMessage();
        self.topBar.AjaxStart(url);
    }
}
/* dropfile使い方案
    $(セレクタ).drop_files(uploadURL,callback_func(res){
            res.abort
            res.complete
            res.total
        LoadDebugBar();
    });
*/
(function ($) {
$.fn.dropfiles = function (uploadURL, callback) {
    var self = this;
    self.on({
        'dragenter': function (e) {
            e.stopPropagation();
            e.preventDefault();
            self.css('border', '2px solid #0B85A1');
        },
        'dragleave': function (e) {
            e.stopPropagation();
            e.preventDefault();
            self.css('border', '2px solid black');
        },
        'dragover': function (e) {
            e.stopPropagation();
            e.preventDefault();
        },
        'drop': function (e) {
            e.stopPropagation();
            e.preventDefault();
            var files = e.originalEvent.dataTransfer.files;
            obj = new UploadFiles(files);
            obj.Execute(uploadURL, callback);
        },
    });
};
})(jQuery);
