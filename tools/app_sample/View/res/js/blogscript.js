/*
ブログスクリプト
*/
//スクロールが100に達したらボタン表示
var moveButton = $('#move-top');
moveButton.hide();
$(".fitWindow").scroll(function () {
    if ($(this).scrollTop() > 100) {
        moveButton.off()
            .on('click',function () {
                $(".fitWindow").animate({
                    scrollTop: 0
                }, 100);
                return false;
            })
            .fadeIn();
    } else {
        moveButton.off().fadeOut();
    }
});
