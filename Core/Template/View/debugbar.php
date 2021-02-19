<div class="debugBar"></div>
<div class="debugBK"></div>
<script>
// DOMの準備ができたらログを読み込んでデバッグバーに格納する
function LoadDebugBar() {
    var url = "/logs/<?= App::$AppName; ?>/";
    $.get(url)
        .done(function(data) {
            $('.debugBar')
                .html(data)
                .css('display','block');
        })
        .fail(function() {
            alert('ERROR:'+url);
        });
}
 // ページの読み込み完了と同時に実行されるよう指定
//$(document).ready(LoadDebugBar);
window.onload = LoadDebugBar;

</script>
