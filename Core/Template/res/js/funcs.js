// 共通関数
//====================================================
// IE8にはtrim()メソッドが無い！自前で実装
String.prototype.trim2 = function() {
    return this.replace(/^[\s　]+|[\s　]+$/g, '');
};
//====================================================
// URIのトップフォルダを返す
String.prototype.module_path = function(e) {
    var path = this.replace(/^[\/]+|[\/]+$/g, '').split('/');
    var n = (path[0] == "{$appName$}") ? 2 : 3;
    return '/'+path.slice(0,n).join('/')+'/'+e+'/';                   // 0 〜 n までの要素を / で結合し、指定パスを付加
//    return '/'+path[0]+'/'+path[1]+'/'+path[2]+'/'+e+'/';
};
//====================================================
// URLの末尾にある数字パラメータを除外して、指定パスを付加する
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

