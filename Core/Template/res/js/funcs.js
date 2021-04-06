// 共通関数
//====================================================
// IE8にはtrim()メソッドが無い！自前で実装
String.prototype.trim2 = function() {
    return this.replace(/^[\s　]+|[\s　]+$/g, '');
};
//====================================================
// ファイル名にURL-NGの文字があるか
String.prototype.is_invalid_name = function () {
    return (this.match(/^.*[\+%#].*?$/));
};
//====================================================
// 配列要素のマージ
Array.prototype.mymerged = function (b) {
	var new_array =  this.slice();	// 配列コピー
	b.forEach(function (val) {
		if (val !== "" & new_array.includes(val) == false) {
			new_array.push(val);
		}
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
    this.protocol = location.protocol;
    this.host = location.host;
    this.qstr = location.search.replace('?','');
    this.items = location.pathname.replace(/^[\/]+|[\/]+$/g, '').split('/');
    var cont = "${$controller$}";
    if (this.items[1] !== cont) this.items.splice(1, 0, cont);      // controller name compensate
//    alert("Pawmoo:" + cont+"\n"+objDump(this.items));
    //
    this.query_str = function () { return (this.qstr == "") ? "" : "?"+this.qstr.replace(";","%3B"); };
    this.set_query = function (q) { this.qstr = q; };
    this.last_item = function (n) { return this.items[this.items.length - n]; };
    this.fullpath = function (url) {
        this.type = "./:".indexOf(url.charAt(0)) + 1;
        var path = (this.type == 0) ? url : url.slice(1);
        switch (this.type) {
            case 1:
                if (path.charAt(0) === '/') path = path.slice(1);
                else this.type = 0;
            case 0: path = "${$APPROOT$}" + path; break;
            case 2: path = "${$SYSROOT$}" + path; break;
            case 3: path = "/" + path; break;
        }
        return  path+"/";
    };
    this.trunc_path = function (n, e, is_num) {
        var path = this.items;
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
    this.param_path = function (e) {
        var path = this.items;
        for (var n=0; (n < path.length) && (isNaN(path[n])); ++n);
        return this.trunc_path(n,e,false);
    };
}
//===============================================
// ネスティッド SELECT
// IEのために class でなく prototype ベースで実装
function SelectChain(mytag,selObj,callback) {
    var myobj = $('#'+mytag);
    var ref = myobj.attr('class');
    if (ref === undefined) ref = mytag;
    var sub = myobj.attr('data-element');
    this.my_obj = myobj;
    this.select_tag = (ref in selObj) ? selObj[ref] :[];  // array-list
    this.tag_id = ref;
    this.tag_avtive = false;
    this.Child_tag = (sub === undefined) ? null : new SelectChain(sub, selObj, callback);
    if (this.Child_tag === null) {
        this.callback_func = (callback === undefined) ? null : callback;
    } else this.callback_func = null;   // セレクト中途ではコールバックしない
};
SelectChain.prototype = {
    // Make Self OPTION List
    selfList: function (val,grp) {
        var self = this;
        self.my_obj.empty();
        self.my_obj.append('<option value="0">${#.core.SelectMe}</option>');
        for (var i = 0; i < self.select_tag.length; i++) {
            var value = self.select_tag[i];
            if(value[2] == grp) {
                var sel = (value[0] === val) ? ' selected' : '';
                self.my_obj.append('<option value="' + value[0] + '"' + sel+'>' + value[1] + '</option>');
            }
        }
    },
    // Recursive OPTION List
    defaultList: function(val,grp) {
        var self = this;
        self.selfList(val, grp);
        if(self.Child_tag !== null) self.Child_tag.defaultList(0,val);
    },
    // Display List Group
    myGroup: function (val) {
        var self = this;
        for (var i = 0; i < self.select_tag.length; i++) {
            var value = self.select_tag[i];
            if (val === value[0]) return value[2];
        }
        return 0;
    },
    // Selected List & OnChange Event set
    Select: function (val) {
        var self = this;
        if(self.Child_tag !== null) val = self.Child_tag.Select(val);
        var grp = self.myGroup(val);
        self.selfList(val, grp);
        self.my_obj.off().change(function() {
            var my_val = $(this).val();
            self.SelectValue = my_val;
            self.tag_avtive = true;
            if (self.Child_tag !== null) self.Child_tag.defaultList(0, my_val);
            else if (self.callback_func !== null) {
                // 最後のセレクトイベントでコールバック関数を呼ぶ、テキストも渡す
                var my_txt = $(this).children(':selected').text();
                self.callback_func(my_val,my_txt);
            }
        });
        if (self.Child_tag === null && self.tag_avtive) self.my_obj.change();
        return grp;
    }
};
//====================================================
// for DEBUG dump Object
var objDump = function(obj, rIndent) {
    if (!obj) return '';
     var result = '', indent = '\t', br = '\n';
     if (rIndent) indent += rIndent;
     if (typeof obj === 'object' && !obj.tagName) {
        result += '[ Object ] ->' + br;
        for (var key in obj) {
            result += indent + key + ' = ';
            if (typeof obj[key] === 'object') {
                result += objDump(obj[key], indent);
            } else {
                result += obj[key];
            }
            result += br;
        }
    } else {
        result = obj;
    }
     result = String(result);
     return result;
};
//====================================================
function DebugSlider() {
    if (typeof LoadDebugBar == "function") LoadDebugBar();
}
//====================================================
// platform Location Object
var pfLocation = new PawmooLocations();
