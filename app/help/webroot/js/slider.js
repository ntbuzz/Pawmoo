
//----------------------------------------------------------------------
// スライダータブインデックス
//  <div id="slideL">
//      <table id=slideBar><tr><td>
//          <span id=slideLB title=ツールバー開閉>▼<span>
//      </td><td> <table class=tab><tr>
//          <td value=val class=select>名前</td>
//      </tr></table></td></tr><table>
//      <div class=content-panel>
//          <ul class=content>
//              <li> aaaaa </li>
//          </ul>
//      </div>
//  </div>
$(function() {
    $("#slideL").mouseleave(
        function() {
            $('#slideL').animate({'whidth':'1.4em'},500);
        }
    ).mouseenter(
        function() {
            $('#slideL').animate({'width':'350px'},500);
        }
    );
    // クリックしたときのファンクション
    $('.tab td').click(function() {
        var txt = $(this).attr('value');
        var index = $('.tab td').index(this);               // index()関数を使いクリックされたタブが何番目かを取得する
        $('.content li').css('display','none');             // コンテンツを一旦すべて非表示にする
        $('.content li').eq(index).css('display','block');  // クリックされたタブのコンテンツだけを表示
        $('.tab td').removeClass('select');                 // select状態を解除
        $(this).addClass('select');                         // クリックされたタブをselectする
        $('#slideLB').text("["+txt.substr(0,1)+"]");        // ラベルを書き換える
    });
});