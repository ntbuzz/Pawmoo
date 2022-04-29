
// JQueryプラグインで呼び出す
//==============================================================================================
// フローティングウィンドウのフォームのみ
$.fn.innerWindow = function (title,callbackBtn, callback) {
	var self = this;
	var eventButtons = ['.close', '.cancel', '.closeButton', '.execButton'];
	if (typeof callbackBtn === "string" && eventButtons.is_exists(callbackBtn)===false) eventButtons.push(callbackBtn);
	var elements = eventButtons.join(',');
	var callbacks = eventButtons.slice(3).join(',');
	var id = "#" + self.attr("id");
    var val = self.attr("value");
    var buttons = (val) ? val.split(",") : Array();
	var message_id = id + " .fw_resize_message";
	var id_name = function (obj) {
		return obj.prop('tagName')+'.'+obj.prop('class')+'#'+obj.prop('id');
	};
//  ユーザー定義ボタンパーツを追加
	if (buttons.length && self.find(".center").length===0) {
        var buttontag = "<div class='center'>";
        var buttonClass = [ "execButton", "closeButton"];
		$.each(buttons, function (index, val) {
			var label_array = val.split(":");
			var btn_label = label_array[0];
			var action = label_array[1];
			if(action === undefined || action === "") action = buttonClass[index];
			buttontag = buttontag + '<span class="Button ' + action + '">' + btn_label + '</span>';
        });
		buttontag = buttontag + "</div>";
		var button_bar = $(buttontag);
		self.append(button_bar);
		// 高さの調整
		self.find('dd').css('height', 'calc(100% - ' + (button_bar.outerHeight()+11) +'px)');
	};
//	操作ボタンパーツを追加
    var controlls = ["close:${#core.Close}", "fw_resize:${#core.Resize}", "fw_resize_message:${#core.SizeDisplay}"];
    controlls.forEach(function (value) {
		var cls = value.split(':');
		var clsname = "span." + cls[0];
		var btn = self.find(clsname);
        if (btn.length === 0) {
			var alt = (cls[1] != '') ? '" alt="' + cls[1] : '';
            $('<span class="'+cls[0]+alt+'"></span>').appendTo(self);
		};
    });
	self.find('dl dt').text(title);
	// 背景をクリックできなくする
	var bk_panel = $('<div class="floatWin-BK"></div>');
    // イベントボタンリストを登録
	self.off('click').on('click', elements, function (e) {
//		alert(elements+"\nMe:"+$(this).prop('class'));
		e.stopPropagation();
		e.preventDefault();
		// 実行イベントボタンを判定
		if ($(callbacks).is($(this))) {
//			alert(callbacks);
			if (typeof callback === 'function') callback.call($(this));
		};
		self.trigger('close-me');
	});
	// 閉じるためのカスタムイベントを定義する(trigger()で呼び出す)
	self.off('close-me').on('close-me', function (e) {
		self.fadeOut("fast");
		self.find('#init_contents').html('');      // clear contents
		$(document).unbind("mousemove");
		bk_panel.remove();
	});
	// ドロップ属性があればエレメントを初期化する
	var cls = self.attr("class");
	if( cls!==undefined && cls.indexOf("drop") !== -1) {
		self.find("#datalist").empty();
		var initdata = self.find("#init").attr("value");
		self.find("#datalist").append(initdata);
	};
	// サイズ属性があればウィンドウサイズを指定する
	if (self.is('[size]')) {
		var sz = self.attr("size").split(',');
		self.css({
			"width": sz[0] + "px",
			"height": sz[1] + "px",
			"min-width": sz[0] + "px",
			"min-height": sz[1] + "px"
		});
		if (sz.length == 4) {
			self.css({
				"min-width": sz[2] + "px",
				"min-height": sz[3] + "px"
			});
		};
		var x = ($(window).innerWidth() - self.width())/2;  // 中央
		var y = ($(window).innerHeight() - self.height())/4;    // 上部25%の位置
		if (x < 0) {
			x = 5;
			self.width($(window).innerWidth() - 20);
		};
		if (y < 0) {
			y = 5;
			self.height($(window).innerHeight() - 20 );
		};
		self.css({'left': x + 'px','top': y + 'px'});
	};
	$(window).resize( function() {
		self.css( {
			top: $(window).scrollTop() + 100,
			left: ($(window).width() - self.outerWidth()) /2
		});
	});
//	$(window).resize();
    // フォーム内のINPUTでENTERが押下されたときの処理
	self.on('keypress', 'input', function (e) {
		if (e.key === 'Enter') {
			e.stopPropagation();
			e.preventDefault();
//			$('.execButton').click();
			$(callbackBtn).click();
		};
	});
    // タイトルバーのドラッグ
	self.on('mousedown', 'dl dt', function (e) {
        self.data("clickPointX", e.pageX - self.offset().left)
            .data("clickPointY", e.pageY - self.offset().top);
        $(document).mousemove( function(e) {
            self.css({
                top: (e.pageY - self.data("clickPointY")) + "px",
                left: (e.pageX - self.data("clickPointX")) + "px"
            });
        }).mouseup( function(e) {
            $(document).unbind("mousemove");
        });
    });     // mousedown()
    // リサイズのドラッグ
	self.on('mousedown', '.fw_resize', function (e) {
        self.data("clickPointX", e.pageX)
            .data("clickPointY", e.pageY);
        $(message_id).fadeIn('fast');
        self.css('user-select', 'none');    // テキスト選択不可
        $(document).mousemove(function (e) {
            var new_width = Math.floor(e.pageX - self.offset().left + 6);
            var new_height= Math.floor(e.pageY - self.offset().top + 6);
            self.css({
                width: new_width + "px",
                height: new_height + "px"
            });
            var txt = new_width + " x " + new_height;
            $(message_id).text(txt);
        }).mouseup(function (e) {
            $(message_id).fadeOut('fast');
            self.css('user-select', '');    // テキスト選択可能
			$(document).unbind("mousemove").unbind("mouseup");
			self.fitWindow();
        });
    });
	$('body').append(bk_panel);
	bk_panel.fadeIn('fast');
	self.fadeIn("fast");
	return self;
};
//=============================================================================================
// フローティングウィンドウ内に要素が定義済で、要素の値設定を行う場合
$.fn.floatWin = function (setupObj, callback) {
	var self = this;	// Reminder jQuery Self Object
	var setting = {
		Title: '',
		execButton: '.execButton',
		formObj: {},
	};
	if(typeof setupObj === 'string') setting.Title = setupObj;
	else if(typeof setupObj === 'object') $.each(setupObj, function (key, value) { setting[key] = value;});
	// Formparameter setup
	$.each(setting.formObj, function (key, value) {
		if (key.charAt(0) === '#') {
			$(key).html(value);
		} else {
			var target = self.find('[name="' + key + '"]');
			if (target.length) {
				switch (target.prop("tagName")) {
				case 'INPUT':
					if (target.attr("type") == "checkbox" || target.attr("type") == "radio" ) {
						target.prop('checked', (value == 't'));
					} else target.val(value);   // 自ID
					break;
				case 'SELECT':
					target.val(value);   // 自ID
					break;
				case 'TEXTAREA':
					var w = target.attr("cols");
					var h = target.attr("rows");
					target.css({"width": w+"em","height": h+"em"});
				default:
					target.text(value);   // 自ID
				};
			};
		};
	});
	self.innerWindow(setting.Title, setting.execButton, function () {
		var setobj = {};
		self.find("*").each(function () {
			var nm = $(this).attr('name');
			if (nm) {
				var tt = $(this).attr('type');
				if (tt == 'checkbox' || tt == 'radio') {
					if($(this).is(':checked')) setobj[nm] = $(this).val();
				} else {
					setobj[nm] = $(this).val();
				};
			};
		});
		if (typeof callback === "function") callback.call($(this), setobj);
		return false;
	});
	return self;
};
