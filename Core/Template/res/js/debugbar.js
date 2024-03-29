/* デバッグバー用のイベント処理 */
$('.debugBar').mouseenter(function() {
    var mode = $('.debugBK').css('display');            // クローズ判定領域が有効になっているか
    if(mode == 'none') {
        $('.debugBK').Visible('block');           // クローズ判定領域を有効
        $('#debugMenu li').removeClass('selected');     // select状態を解除
        $('.dbcontent li').Visible(false);      		// コンテンツを一旦すべて非表示にする
        // クリックしたときのファンクション
        $('#debugMenu li').off().click(function() {
            var sel = $(this).attr('class');
            if(sel == 'selected') {
                $('.dbcontent li').Visible(false);      // コンテンツを非表示にする
                $(this).removeClass('selected');                // select状態を解除
            } else {
                var hh = $(window).height()*0.9;                // ウィンドウの高さから計算
                $('.debug_srcollbox').css('height',hh+'px');    // メッセージ表示ボックスの高さを指定
                var index = $('#debugMenu li').index(this);     // index()関数を使いクリックされたタブが何番目かを取得する
                $('.dbcontent li')
                    .Visible(false)                      // コンテンツを一旦すべて非表示にする
                    .eq(index).Visible('block');          // クリックされたタブのコンテンツだけを表示
                $('#debugMenu li').removeClass('selected');     // select状態を解除
                $(this).addClass('selected');                   // クリックされたタブをselectする
            };
        });
        $('.debugBar').animate({'width':'900px'},500);
    };
    // ツールバーの外側をクリック
    $('.debugBK, .closeButton').off().click(function(){
        $('#debugMenu li').removeClass('selected');             // select状態を解除
		$('.dbcontent li').Visible(false);
        $('.debugBar').animate({'width':'1.0em'},180);
        $('.debugBK').Visible(false);                    // クローズ判定領域を非表示
	});
});
