//
$("#menu li").hover(function () {
    $("ul",this).show();
    },
    function(){
        $("ul",this).hide();
    });
//
$('.global a').attr("target","_blank");

/*
    コンテキストメニューの処理関数
*/
/*
$("#ctxEdit").mousedown(function (e) { 
    alert("コンテキスト:"+$(this).attr("id"));
});

$("#ctxUndo").mousedown(function (e) { 
    alert("コンテキスト:"+$(this).attr("id"));
});
*/
