<div class="debugBar"></div>
<div class="debugBK"></div>
<script>
// DOMの準備ができたらログを読み込んでデバッグバーに格納する
function LoadDebugBar() {
    var url = "/logs/<?= sysLog::getLogURI(); ?>/";
    $('.debugBar').Visible(false);
    $.get(url)
        .done(function(data) {
            $('.debugBar')
                .html(data)
                .Visible('block');
        })
        .fail(function() {
            alert('Log Get-ERROR:'+url);
        });
}
// ページの読み込み完了と同時に実行されるよう指定
//window.onload = LoadDebugBar;
$(window).on('load', LoadDebugBar); // 他に onload を使っている場合はこちら

</script>
