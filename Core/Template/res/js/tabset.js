
// TabControll Click Event
//$('.tabControll > .tabmenu > li').click(function () {
$(document).on('click','.tabControll>.tabPanel>.tabmenu>li',function () {
//	alert("click!");
    var control = $(this).closest('.tabControll');
    var menu = control.children('.tabPanel').children('.tabmenu').children('li');
    var cont = control.children('.tabcontents').children('li');
	var index = menu.index(this);
	cont.css('display','none');
	cont.eq(index).css('display','block');
	menu.removeClass('selected');
	$(this).addClass('selected');
	control.parents().scrollTop(0);
});
(function ($) {
	// $(固定するウィンドウ).stickyTabMenu(タブセット)
	$.fn.stickyTabMenu = function (e) {
		var self = this; // jQueryオブジェクトを変数に代入しておく
		var stickyTab = $(e).find('.tabPanel');
		self.on("scroll", function () {
            var top = self.scrollTop();
            stickyTab.css("top", top + "px");
		});
	};
})(jQuery);
