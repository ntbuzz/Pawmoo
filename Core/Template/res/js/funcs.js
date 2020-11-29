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
const LOC_APPSELF   = 0;     // url .url http://host/appRoot/url
class Locations {
    constructor(url = location.href) {
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
        this.is_app = (this.array[0] == "${$appName$}")
    }
    get query() { return (this.qstr == "") ? "" : "?" + this.qstr; }
    get last_path() { return this.array[this.array.length - 2]; }
    get fw_fullpath() {
        this.type = "./:".indexOf(this.url.charAt(0))+1;
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
    }
    set_query(q) { this.qstr = q; }
    trunc_path(ix, e) {
        var n = (this.is_app) ? ix : ix + 1;
        for (var i = 0; i < e.length; i++) this.array[n + i] = e[i];
        n = n + e.length;
        return "/" + this.array.slice(0, n).join("/")+this.query;
    }
    href_controller(e) { return this.trunc_path(1, e); }
    href_action(e) { return this.trunc_path(2, e); }
    href_filter(e) { return this.trunc_path(3, e); }
    href_param(e) { return this.trunc_path(4, e); }
    href_number(e) {
        var path = this.array;
        for (var n = path.length; (n > 1) && (!isNaN(path[n-1])); --n) ;
        if (!this.is_app) --n;
//        alert(objDump(path) + "\n" + n+"\n"+this.query);
        return this.trunc_path(n, e);
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