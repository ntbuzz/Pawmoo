
// TabControll Click Event
$(document).on('click','.tabControll>.tabPanel>.tabmenu>li',function () {
    var control = $(this).closest('.tabControll');
    var menu = control.children('.tabPanel').children('.tabmenu').children('li');
    var cont = control.children('.tabcontents').children('li');
	var index = menu.index(this);
	menu.removeClass('selected');		// TabMenu selected delete
	$(this).addClass('selected');		// switch click TAB selected
	cont.removeClass('selected');		// TabContents selected delete
	cont.eq(index).addClass('selected');	// switch TAB selected Contents
	control.parents().scrollTop(0);
});
(function ($) {
	// $(固定するウィンドウ).stickyTabMenu(タブセット)
	$.fn.stickyTabMenu = function (e) {
		var self = this;
		var stickyTab = $(e).find('.tabPanel');
		self.on("scroll", function () {
            var top = self.scrollTop();
            stickyTab.css("top", top + "px");
		});
	};
})(jQuery);
