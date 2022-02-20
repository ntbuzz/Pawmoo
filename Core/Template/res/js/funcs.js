//====================================================
// customize location object class for this framework
const LOC_FULL    = 3;     // :url     http://host/url
const LOC_SYS     = 2;     // /url     http://host/sysRoot/url
const LOC_APPNEW  = 1;     // ./url    http://host/appRoot/url
const LOC_APPSELF = 0;     // url .url http://host/appRoot/url
//=====================================================
// URIの操作
function PawmooLocations() {
    this.protocol = window.location.protocol;
    this.host = window.location.host;
    this.qstr = window.location.search.replace('?','');
    this.items = window.location.pathname.replace(/^[\/]+|[\/]+$/g, '').split('/');
    this.query_str = function () { return (this.qstr == "") ? "" : "?"+this.qstr.replace(";","%3B"); };
    this.set_query = function (q) { this.qstr = q; };
    this.clear_query = function () { this.qstr = ""; };
    this.last_item = function (n) { return this.items[this.items.length - n]; };
	this.fullpath = function (url) {
		if (url.startOfString(['http://', 'https://', 'ftp://', 'file://'])) {
			this.type = LOC_FULL;
			return url;
		};
        this.type = "./:".indexOf(url.charAt(0)) + 1;
        var path = (this.type == 0) ? url : url.slice(1);
        switch (this.type) {
            case 1:
                if (path.charAt(0) === '/') path = path.slice(1);
                else this.type = 0;
            case 0: path = "${$APPROOT$}" + path; break;
            case 2: path = "${$SYSROOT$}" + path; break;
            case 3: path = "/" + path; break;
		};
		if (path.slice(-1) === "/") path = path.slice(0, -1);
        return  path;
    };
    this.trunc_path = function (n, e, is_num) {
        var path = this.items.slice();
        if (is_num === true) {
            for (var i = path.length; (i > 1) && (!isNaN(path[i - 1])); --i);
            nums = "/" + path.slice(i).join("/");
        } else nums = "";
        for (var i = 0; i < e.length; i++) path[n + i] = e[i];
        n = n + e.length;
        return "/" + path.slice(0, n).join("/") + nums + this.query_str();
    };
    this.cont_path = function (e, isnum) { return this.trunc_path(1, e, isnum); };
    this.act_path = function (e, isnum) { return this.trunc_path(2, e, isnum); };
    this.filter_path = function (e, isnum) { return this.trunc_path(3, e, isnum); };
    this.get_action = function () { return this.items[2]; };
    this.get_filter = function () { return this.items[3]; };
    this.param_path = function (e) {
        var path = this.items;
        for (var n=0; (n < path.length) && (isNaN(path[n])); ++n);
        return this.trunc_path(n,e,false);
	};
	this.Locate = function (url) {
		$.busy_cursor(true);
		location.href = url;
	};
};
//===============================================
// Nested SELECT revised edition
function SelectLink(setupobj, id, first_call, callback) {
	var self = this;
	var self_obj = $('#' + id);
	var my_prop = self_obj.attr('data-value');
	if (my_prop === undefined) my_prop = id;
	var my_obj = setupobj[my_prop];
	var child_id = self_obj.attr('data-element');
	if($('#'+child_id).length === 0) child_id = null;
	var select_me = '<option value="">${#.core.SelectMe}</option>';
	// 末端の子要素オブジェクト
	var child_term = {
		terminate:true,
		selfList: function () {return false;},
		Select: function (val) {return val;},
		hideChildren: function () {return false;},
	};
	var child_obj = (child_id === null) ? child_term : new SelectLink(setupobj, child_id, first_call, callback);
	// ファーストコールフラグを子要素に伝播する
	// self.setFirstCall = function (flag) {
	// 	if (child_obj.terminate === true) first_call = flag;
	// 	else child_obj.setFirstCall(flag);
	// };
	// 子要素を全て隠す
	self.hideChildren = function () {
		child_obj.hideChildren();	// 子要素以下を隠す
		self_obj.hide();			// 自身の要素を隠す
	};
	// 自分の親IDを探す
	self.myParent = function (val) {
		if (val === '') return 0;
		var pid = val;
		$.each(my_obj.sel_list, function (key, value) {
			if (value[0] == val) {
				pid = (value[2] === undefined) ? 0 : value[2];
				return false;	// exit .each()
			};
			return true;
		});
		return pid;
	};
	// 自身のOPTIONタグを生成し、指定値にselect属性を付ける
	self.selfList = function (val, grp) {
		self_obj.empty();
		var opt = 0;var selected = false;
		if (my_obj.select_one) { self_obj.append(select_me); };
		$.each(my_obj.sel_list, function (key, value) {
			if (value[2] === undefined || parseInt(value[2]) === parseInt(grp)) {
				var sel = '';
				if(value[0] == val) {
					selected = true;
					sel = ' selected';
				};
				self_obj.append('<option value="' + value[0] + '"' + sel + '>' + value[1] + '</option>');
				++opt;
			};
		});
		if (setupobj.autohide) {
			if(opt===0) self.hideChildren();
			else {
				self_obj.show();
				if(selected === false) child_obj.hideChildren();
			};
		};
		return (opt > 0);		// SELECT 要素があるかどうかを返す
	};
	self.doCallback = function (val, pid) {
		if (typeof callback === "function") {	// 最終コールバック関数
			var my_txt = self_obj.children(':selected').text();
			callback.call(self, val, my_txt, id, pid);
		};
	};
	// 値の変更
	self.onChange = function (my_val, in_progress) {
		// 自分の親IDを探す
		var pid = self.myParent(my_val);
		// 自分が親になっている子要素を更新
		if (child_obj.selfList(-1, my_val)) {	// 子要素のリストが存在するなら
			if (typeof in_progress === "function") {	// 中間コールバック関数
				in_progress.call(self, my_val, id, pid);
			};
		} else self.doCallback(my_val,pid);
	};
	// 指定値を選択
	self.Select = function (val, in_progress) {
		// 子要素のリストを作成し、その親ID(=自分のselectID)を貰う
		val = child_obj.Select(val, in_progress);
		// 自分の親IDを探す
		var pid = self.myParent(val);
		self.selfList(val, pid);	// 自分と同じ親IDの仲間リストを作成
		if(child_obj.terminate === true && first_call) {
			self.doCallback(val, pid);
			first_call = false;
		};
		self_obj.off().change(function () {
			var my_val = $(this).val();
			self.onChange(my_val, in_progress);
		});
        return pid;
    };
};
//====================================================
// ターゲット位置を元に自身のポジションを決定する
function calcPosition(target, self) {
	var target_left = target.offset().left;
	var target_top = target.offset().top;
	var target_width = target.innerWidth();
	var target_height = target.innerHeight();
	this.scrollPos = function () {
		var x = this.left - $(window).scrollLeft();
		var y = this.top - $(window).scrollTop();
		self.css({'left': x + 'px','top': y + 'px'});
	};
	this.resizeBox = function () {
		var self_width = self.outerWidth();
		var self_height = self.outerHeight();
		var window_right = $(window).innerWidth() + $(window).scrollLeft(); 
		var window_bottom = $(window).innerHeight() + $(window).scrollTop(); 
		this.left = target_left + Math.max(0,target_width - self_width);
		if ((this.left + self_width) > window_right) {
			var vleft = target_left + target_width - self_width;
			if(vleft > $(window).scrollLeft()) this.left = vleft;
		};
		this.top = target_top + target_height + 3;
		if ((this.top + self_height) > window_bottom) {
			var vtop = target_top - self_height;
			if (vtop > $(window).scrollTop()) this.top = vtop;
		};
		this.scrollPos();
	};
	this.resizeBox();
};
//====================================================
// SYNC AJAX
var ajaxLoadSync = function (url, obj, default_data) {
	$.busy_cursor(true);
	$.ajaxSetup({ async: false });
	$.post(url,obj).done(function (data) { //リクエストが成功した際に実行する関数
		default_data = data;
	}).fail(function () {
		alert("error:" + url);
	});
	$.busy_cursor(false);
	return default_data;
};
//====================================================
// create FORM and SUBMIT
var formSubmit = function (obj, url, new_win) {
	$.busy_cursor(true);
	var tag = (new_win === 'new')?'<form method="POST" target="_blank">':'<form method="POST">';
	var form = $(tag);
	$.each(obj, function (key, value) {
		$('<input>').attr({
			'type': 'hidden',
			'name': key,
			'value': value
		}).appendTo(form);
	});
	form.attr('action', url).appendTo('body').submit();
	form.remove();
	$.busy_cursor(false);
};
//====================================================
//  Check CLIENT BROWSER Javascript version
function get_browserInfo() {
	var agent = window.navigator.userAgent.toLowerCase();
	var browsObj = {
		'Internet Explorer': [ 'msie,trident',false],
		'Edge':				 [ 'edge,edg',	true],
		'Google Chrome':	 [ 'chrome',	true],
		'Safari':			 [ 'safari',	false],
		'FireFox':			 [ 'firefox',	false],
		'Opera':			 [ 'opera',		false],
	};
	for(var name in browsObj) {
		var element = browsObj[name];
		var id = element[0].split(",");
		for(var i=0;i<id.length;++i) {
			var v = id[i];
			if(agent.indexOf(v) != -1) {
				return {Name:name, Allow:element[1]};
			};
		};
	};
	return {Name:'Unknown',Allow:false};
};
//====================================================
// for DEBUG dump Object
	var dumpStr = function(obj, rIndent) {
		if (!obj) return '';
		 var result = '', indent = '  ', br = '\n';
		 if (rIndent) indent += rIndent;
		 if (typeof obj === 'object' && !obj.tagName) {
			result += ' {' + br;
			for (var key in obj) {
				result += indent + key + ' = ';
				if (obj[key] instanceof jQuery) {
					result += key +" is jQuery Object" + br;
				} else if (typeof obj[key] === 'function') {
					result += key +" is function()" + br;
				} else if (typeof obj[key] === 'object') {
					if (key === "ownerDocument") result += "own:"+dumpStr(obj[key], indent);
					else if(Array.isArray(obj[key])) result += "["+obj[key].join(",")+"]"+br;
					else result += "Object:" + key + br;
				} else {
					result += obj[key] + br;
				};
			 };
			result += '}' + br;
		} else {
			result = obj;
		};
		 result = String(result);
		 return result;
	};
//====================================================
// dump alert()
var alertDump = function() {
	var str = "";
	$.each(arguments,function (index,argv) {
		if (typeof argv === 'object') str += dumpStr(argv)+"\n";
		else str += argv+"\n";
	});
	alert(str);
};
//====================================================
// dump console()
var consoleDump = function() {
	var str = "";
	$.each(arguments,function (index,argv) {
		if (typeof argv === 'object') str += dumpStr(argv)+"\n";
		else str += argv+"\n";
	});
	console.log(str);
};
//====================================================
function DebugSlider() {
    if (typeof LoadDebugBar == "function") LoadDebugBar();
};
//====================================================
// platform Location Object
var pfLocation = new PawmooLocations();
