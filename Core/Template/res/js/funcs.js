// 共通関数
//====================================================
// IE8にはtrim()メソッドが無い！自前で実装
String.prototype.trim2 = function() {
    return this.replace(/^[\s　]+|[\s　]+$/g, '');
};
//====================================================
// customize location object class for this framework
const LOC_FULL      = 3;     // :url     http://host/url
const LOC_SYS       = 2;     // /url     http://host/sysRoot/url
const LOC_APPNEW    = 1;     // ./url    http://host/appRoot/url
const LOC_APPSELF = 0;     // url .url http://host/appRoot/url
// IEのために prototype ベースで実装
var Locations = function (url) {
    if (url == undefined) url = location.href;
    var path = url.replace(/%3F/g, '?').split('?');
    this.url = path[0];
    this.qstr = (path.length == 1) ? '' : path[1];
    this.base = location.origin;
    this.array = this.url.replace(/^[\/]+|[\/]+$/g, '').split('/');
    if (this.array[0] == "http:" || this.array[0] == "https:") {
        this.base = this.array.slice(0, 3).join("/");
        this.array = this.array.slice(3);
        this.url = this.array.join("/") + "/";
    }
    var cont = "${$controller$}".toLowerCase();
    if (this.array[1] != cont) this.array.splice(1, 0, cont);      // controller name compensate
};
Locations.prototype.query = function () { return (this.qstr == "") ? "" : "?" + this.qstr; };
Locations.prototype.last_path = function () { return this.array[this.array.length - 2]; };
Locations.prototype.fw_fullpath = function () {
    this.type = "./:".indexOf(this.url.charAt(0)) + 1;
    var path = (this.type == 0) ? this.url : this.url.slice(1);
    switch (this.type) {
        case 1:
            if (path.charAt(0) == '/') path = path.slice(1);
            else this.type = 0;
        case 0: path = "${$APPROOT$}" + path; break;
        case 2: path = "${$SYSROOT$}" + path; break;
        case 3: path = "/" + path; break;
    }
    return this.base + path;
};
Locations.prototype.set_query = function (q) { this.qstr = q; };
Locations.prototype.trunc_path = function (n, e) {
    for (var i = 0; i < e.length; i++) this.array[n + i] = e[i];
    n = n + e.length;
    return "/" + this.array.slice(0, n).join("/") + this.query();
};
Locations.prototype.href_controller = function (e) { return this.trunc_path(1, e); };
Locations.prototype.href_action = function (e) { return this.trunc_path(2, e); };
Locations.prototype.href_filter = function (e) { return this.trunc_path(3, e); };
Locations.prototype.href_param = function (e) { return this.trunc_path(4, e); };
Locations.prototype.href_number = function (e) {
    var path = this.array;
    for (var n = path.length; (n > 1) && (!isNaN(path[n - 1])); --n);
//  alert(objDump(path) + "\n" + n+"\n"+this.query());
    return this.trunc_path(n, e);
};
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
        self.my_obj.append('<option value="0">${#core.SelectMe}</option>');
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
            if (self.Child_tag !== null) self.Child_tag.defaultList(0, my_val);
            else if (self.callback_func !== null) {
                // 最後のセレクトイベントでコールバック関数を呼ぶ、テキストも渡す
                var my_txt = $(this).children(':selected').text();
                self.callback_func(my_val,my_txt);
            }
        });
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
function DebugSlider() {
    if (typeof LoadDebugBar == "function") LoadDebugBar();
}
