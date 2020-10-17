// 共通関数
//====================================================
// IE8にはtrim()メソッドが無い！自前で実装
String.prototype.trim2 = function() {
    return this.replace(/^[\s　]+|[\s　]+$/g, '');
};
//====================================================
// URLを分割
String.prototype.query_split = function (ix) {
    var path = this.replace(/%3F/g, '?').split('?');
    var url = path[0];
    var query = (path.lenght > 1) ? "?"+path[1] :'';
    var url_arr = url.replace(/^[\/]+|[\/]+$/g, '').split('/');
    if (ix > 0) {
        var n = (url_arr[0] == "${$appName$}") ? ix : ix+1;
        url = '/'+url_arr.slice(0,n).join('/')+'/';
    }
    var setobj = {
        url: url,
        array: url_arr,
        query: query
    };
    return setobj;
};
//====================================================
// コントローラー名までのパスに引数を付加する
String.prototype.controller_path = function (e) {
    var urlobj = this.query_split(1);
    return urlobj['url']+e+'/';
};
//====================================================
// メソッド名までのパスに引数を付加する
String.prototype.method_path = function(e) {
    var urlobj = this.query_split(2);
    return urlobj['url']+e+'/';
};
//====================================================
// フィルタまでのパスに引数を付加する
String.prototype.filter_path = function(e) {
    var urlobj = this.query_split(3);
    return urlobj['url']+e+'/';
};
//====================================================
// パラメータまでのパスに引数を付加する
String.prototype.param_path = function(e) {
    var urlobj = this.query_split(4);
    return urlobj['url']+e+'/';
};
//====================================================
// URLの末尾から数字パラメータを除外して、指定パスを付加する
String.prototype.exclude_num_path = function (e) {
    var urlobj = this.query_split(0);
    var query = urlobj['query'];
    var path = urlobj['array'];
    for (var n = 2; (n < path.length) && isNaN(path[n]); n++) ;    // メソッド位置から数字パラメータの位置まで進める
    return '/'+path.slice(0,n).join('/')+'/'+e+query;
};
Array.prototype.inBound = function (e, x, y) {
    return (x >= (this[0] - e)) && (x <= (this[0] + this[2] + e))
        && (y >= (this[1] - e)) && (y <= (this[1] + this[3]));
};

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