// 共通関数
//====================================================
// IE8にはtrim()メソッドが無い！自前で実装
String.prototype.trim2 = function() {
    return this.replace(/^[\s　]+|[\s　]+$/g, '');
};
//====================================================
// コントローラー名までのパスに引数を付加する
String.prototype.controller_path = function(e) {
    var path = this.replace(/^[\/]+|[\/]+$/g, '').split('/');
    var n = (path[0] == "${$appName$}") ? 1 : 2;
    return '/'+path.slice(0,n).join('/')+'/'+e+'/';                   // 0 〜 n までの要素を / で結合し、指定パスを付加
};
//====================================================
// メソッド名までのパスに引数を付加する
String.prototype.method_path = function(e) {
    var path = this.replace(/^[\/]+|[\/]+$/g, '').split('/');
    var n = (path[0] == "${$appName$}") ? 2 : 3;
    return '/'+path.slice(0,n).join('/')+'/'+e+'/';                   // 0 〜 n までの要素を / で結合し、指定パスを付加
};
//====================================================
// フィルタまでのパスに引数を付加する
String.prototype.filter_path = function(e) {
    var path = this.replace(/^[\/]+|[\/]+$/g, '').split('/');
    var n = (path[0] == "${$appName$}") ? 3 : 4;
    return '/'+path.slice(0,n).join('/')+'/'+e+'/';                   // 0 〜 n までの要素を / で結合し、指定パスを付加
};
//====================================================
// パラメータまでのパスに引数を付加する
String.prototype.param_path = function(e) {
    var path = this.replace(/^[\/]+|[\/]+$/g, '').split('/');
    var n = (path[0] == "${$appName$}") ? 4 : 5;
    return '/'+path.slice(0,n).join('/')+'/'+e+'/';                   // 0 〜 n までの要素を / で結合し、指定パスを付加
};
//====================================================
// URLの末尾から数字パラメータを除外して、指定パスを付加する
String.prototype.exclude_num_path = function(e) {
    var path = this.replace(/^[\/]+|[\/]+$/g, '').split('/');
    for (var n = 2; (n < path.length) && isNaN(path[n]); n++) ;    // メソッド位置から数字パラメータの位置まで進める
//    alert(n+"\n"+path[n]);
    return '/'+path.slice(0,n).join('/')+'/'+e;                   // 0 〜 n までの要素を / で結合し、指定パスを付加
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