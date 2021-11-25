//====================================================
// common prototype define (Mostly fo IE-11)
//====================================================
// file-size convert for unit size
Number.prototype.size_unit = function () {
	var sz = this;
	for (var i = 0; sz > 1000; i += 3, sz /= 1000) ;
	szStr = sz.toFixed(2) + " B  KB MB GB TB PB".substr(i, 3);
	return szStr;
};
//====================================================
// Number overflow check by limit-string
Number.prototype.valueOver = function (szstr) {
	var unit_base = {
		'B':	1,
		'KB':	1000,
		'MB':	1000*1000,
		'GB':	1000*1000*1000,
		'TB':	1000*1000*1000*1000,
		'PB':	1000*1000*1000*1000*1000,
	};
	var splitStr = szstr.split(/(\d+(?:\.\d+)*)/).filter(function (i) { return i.length; }).map(function (v) { return v.trim(); });
	var ret = splitStr[0];
	var unit = unit_base[splitStr[1]];
	var limit = parseFloat(ret)*unit; 
	return this > limit;
};
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
// check EXECUTE file
String.prototype.is_executable = function () {
	var ext = this.ext_type();
	return (ext == 'exe');
	// var executable = ['exe', 'com', 'dll', 'ocx','vbs', 'vbe', 'bat', 'cmd','run', 'js', 'jse', 'wsf', 'wsh', 'msc', 'jar', 'hta', 'msi','scr','lnk','url','iqy','cpl'];
	// var ext = this.split('.').pop();
	// return executable.is_exists(ext);
};
//====================================================
// convert extention type
String.prototype.ext_type = function () {
	var ext = {
		psd: 'psd',
		ai: 'ai',
		indd:'indd',
		xdw: 'xdw',
		iso: ['iso','cdd'],
		doc: ['docx', 'doc'],
		xls: ['xls', 'xlsx', 'xlsm', 'xla'],
		htm: ['htm', 'html','xml'],
		txt: ['txt', 'rtf','csv'],
		pdf: ['pdfx', 'pdf'],
		ps:  ['ps', 'eps','pm','xps'],
		ppt: ['pptx', 'ppt'],
		img: ['gif', 'png', 'jpeg', 'jpg', 'bmp', 'dib','tif','tiff'],
		mov: ['mpg', 'mpeg', 'mp4', 'avi', 'mov', 'rm', 'divx','flv'],
		zip: ['zip', 'lzh', '7z', 'rar','gz','tgz','tar'],
		exe: ['exe', 'com', 'dll', 'ocx','vbs', 'vbe', 'bat', 'cmd','run', 'js', 'jse', 'wsf', 'wsh', 'msc', 'jar', 'hta', 'msi','scr','lnk','url','iqy','cpl'],
	};
	for (var k in ext) {
		var hit = (Array.isArray(ext[k])) ? (ext[k].is_exists(this)) : (ext[k] == this);
		if  (hit) return k;
	};
	return 'file';
};
//====================================================
// start of strings in array values
String.prototype.startOfString = function (arr) {
	var exists = false;
	var base = this;
	arr.forEach(function (val) {
		if (base.substr(0,val.length) == val) {
			exists = true;
			return false;	// break forEach
		};
		return true;	// continue next
	});
	return exists;
};
//====================================================
// is exists WORD in string
String.prototype.existsWord = function (str) {
	var wd_arr = this.split(' ');	// separate space
	return wd_arr.is_exists(str);
};
//====================================================
// multi string compare of indexOf()
String.prototype.includeOf = function (reg) {
	var wd_arr = reg.split('|');	// separate stroke
	var cmp_str = this;
	var exists = false;
	wd_arr.forEach(function (val) {
		if(cmp_str.indexOf(val) !== -1) {
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
		if (val == v) {
			exists = true;
			return false;	// break forEach
		};
		return true;	// continue next
	});
	return exists;
};
//====================================================
// delete element by value, don't duplicate
Array.prototype.delete_exists = function (val) {
	var index = this.indexOf(val);
	if(index != -1) {
		this.splice(index, 1);
		return true;
	};
	return false;
};
//====================================================
// pickup uniq element
Array.prototype.uniq = function () {
	return this.filter(function (x, i, self) { return (self.indexOf(x) === i); });
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
