//====================================================
// normarl javascript functions

// common prototype define (Mostly fo IE-11)
//====================================================
// triming space
String.prototype.trim2 = function() {
    return this.replace(/^[\s　]+|[\s　]+$/g, '');
};
//====================================================
// check cannot use URI-charactor
String.prototype.is_invalid_name = function () {
    return (this.match(/^.*[\+%#].*?$/));
};
//====================================================
// start of strings in array values
String.prototype.startOfString = function (arr) {
	var exists = false;
	var base = this;
	arr.forEach(function (val) {
		if (base.substr(0,val.length) === val) {
			exists = true;
			return false;	// break forEach
		};
		return true;	// continue next
	});
	return exists;
};
//====================================================
// element search: IE-11 is not have includes() method.
Array.prototype.is_exists = function (v) {
	var exists = false;
	this.forEach(function (val) {
		if (val === v) {
			exists = true;
			return false;	// break forEach
		};
		return true;	// continue next
	});
	return exists;
};
//====================================================
// array merge: exclude duplicate element
Array.prototype.mymerged = function (b) {
	var new_array =  this.slice();	// 配列コピー
	b.forEach(function (val) {
		if (val !== "" & new_array.is_exists(val) == false) {
			new_array.push(val);
		};
	});
	return new_array;
};
//====================================================
// customize location object class for this framework
const LOC_FULL      = 3;     // :url     http://host/url
const LOC_SYS       = 2;     // /url     http://host/sysRoot/url
const LOC_APPNEW    = 1;     // ./url    http://host/appRoot/url
const LOC_APPSELF = 0;     // url .url http://host/appRoot/url
//=====================================================
// URIの操作
function PawmooLocations() {
//    var cont = "${$controller$}";
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
    this.getfilter = function () { return this.items[2]; };
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
	var callback_call = first_call;
	var self_obj = $('#' + id);
	var my_prop = self_obj.attr('data-value');
	if (my_prop === undefined) my_prop = id;
	var my_obj = setupobj[my_prop];
	var child_id = self_obj.attr('data-element');
	if($('#'+child_id).length === 0) child_id = null;
	var select_me = '<option value="0">${#.core.SelectMe}</option>';
	var child_obj = (child_id === null) ? null : new SelectLink(setupobj, child_id, first_call, callback);
	self.selfList = function (val, grp) {
		self_obj.empty();
		var opt = 0;
		if (my_obj.select_one) { self_obj.append(select_me); ++opt; };
		$.each(my_obj.sel_list, function (key, value) {
			if (value[2] === undefined || parseInt(value[2]) === parseInt(grp)) {
				var sel = (value[0] === val) ? ' selected' : '';
				self_obj.append('<option value="' + value[0] + '"' + sel + '>' + value[1] + '</option>');
				++opt;
			};
		});
		if (opt === 0) self_obj.append(select_me);
	};
    self.defaultList = function(val,grp) {
		self.selfList(val, grp);
		child_grp = self_obj.find('option:selected').val();
        if(child_obj !== null) child_obj.defaultList(0,child_grp);
	};
	// suppress change() event 
	self.SetSelect = function (my_val) {
		if (child_obj !== null) child_obj.defaultList(0, my_val);
		 else if (typeof callback === 'function') {
			var my_txt = self_obj.children(':selected').text();
			if (callback_call) callback.call(this, my_val, my_txt, id);
			callback_call = true;
		};
	};
	self.Select = function (val,in_progress) {
        if(child_obj !== null) val = child_obj.Select(val);
		var grp = val;
		$.each(my_obj.sel_list, function (key, value) {
			if (value[0] === val) {
				grp = (value[2] === undefined) ? 0 : value[2];
				return false;	// exit .each()
			};
			return true;
		});
        self.selfList(val, grp);
		self_obj.off().change(function () {
			var my_val = $(this).val();
			if (child_obj !== null && typeof in_progress === "function") {
				in_progress.call(this, my_val, id);
			};
			self.SetSelect(my_val);
		});
		if (child_obj === null) self.SetSelect(val);
        return grp;
    };
};
//====================================================
// create FORM and SUBMIT
var formSubmit = function (obj, url) {
	$.busy_cursor(true);
	var form = $('<form method="POST">');
	$.each(obj, function (key, value) {
		$('<input>').attr({
			'type': 'hidden',
			'name': key,
			'value': value
		}).appendTo(form);
	});
	form.attr('action', url).appendTo('body').submit();
};
//====================================================
// for DEBUG dump Object
var objDump = function(obj, rIndent) {
    if (!obj) return '';
     var result = '', indent = '  ', br = '\n';
     if (rIndent) indent += rIndent;
     if (typeof obj === 'object' && !obj.tagName) {
        result += '[ Object ] ->' + br;
        for (var key in obj) {
			result += indent + key + ' = ';
			if (obj[key] instanceof jQuery) {
                result += key +" is jQuery Object" + br;
			} else if (typeof obj[key] === 'function') {
                result += key +" is function()" + br;
            } else if (typeof obj[key] === 'object') {
                result += objDump(obj[key], indent);
            } else {
                result += obj[key] + br;
			};
		 };
    } else {
        result = obj;
	};
     result = String(result);
     return result;
};
//====================================================
function DebugSlider() {
    if (typeof LoadDebugBar == "function") LoadDebugBar();
};
//====================================================
// platform Location Object
var pfLocation = new PawmooLocations();
