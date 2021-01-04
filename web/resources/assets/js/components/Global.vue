<script>

function setCookie(name,value,field,expire)
{ 
    var Days = 30; 
    if (expire != null) Days = expire;
    var exp = new Date(); 
    exp.setTime(exp.getTime() + Days*24*60*60*1000); 
    if (field == "page")
        document.cookie = name + "=" + escape (value) + ";expires=" + exp.toGMTString();
    else
        document.cookie = name + "=" + escape (value) + ";expires=" + exp.toGMTString() + ";path=/";
    
};
 
//读取cookies 
function getCookie(name) 
{ 
    var arr,reg = new RegExp("(^| )"+name+"=([^;]*)(;|$)");
    if (arr = document.cookie.match(reg))
        return unescape(arr[2]); 
    else 
        return null; 
}; 
 
//删除cookies 
function delCookie(name,field) 
{ 
    var exp = new Date(); 
    exp.setTime(exp.getTime() - 1); 
    var cval = getCookie(name); 
    if (cval != null)
        if (field == "page")
            document.cookie = name + "=" + cval + ";expires=" + exp.toGMTString(); 
        else
            document.cookie = name + "=" + cval + ";expires=" + exp.toGMTString() + ";path=/";
};

// time是当时区为timezone时显示的时间。返回浏览器当时的时间（HH:MM）
function GetLocalTime(time, timezone){
	if (timezone > 13 || timezone < -11)
		return null;
	if (!time.match(/^[0-9]{1,2}:[0-9]{1,2}$/))
		return null;

	var dtime = time.split(":");
	var hour = parseInt(dtime[0]);
	var minute = parseInt(dtime[1]);
	var d = new Date();
	var localtimezone = d.getTimezoneOffset() / (-60);
	d.setHours(hour);
	d.setMinutes(minute);
	d = new Date(d.getTime() + (localtimezone - timezone) * 3600 * 1000);
	var h = d.getHours();
	var m = d.getMinutes();
	if (h < 10) h = "0" + h;
	if (m < 10) m = "0" + m;
	return h + ":" + m;
};

//获取时区
function getTimeZone() {
	return new Date().getTimezoneOffset() / -60;
}

//按时区，把一个unix时间戳按日期规整化
function get_local_time(time, date) {
    
    if (/</.test(time))
        time = time.replace(/<[^>]*>/g, "");
    
    var date_unix = null;
    if (date != null && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
        var arr = date.split("-");
        var y = parseInt(arr[0]);
        var m = parseInt(arr[1]) - 1;
        var d = parseInt(arr[2]);
        date_unix = (new Date(y, m, d)).getTime() / 1000;
    }
    
    if (/^\d{10}$/.test(time)) {
        var dt = new Date(time * 1000);
        var h = dt.getHours();
        var m = dt.getMinutes();
        if (h < 10) h = '0' + h;
        if (m < 10) m = '0' + m;
        var itvl = date_unix ? (time - date_unix >= 86400 ? '<sup>+1</sup>' : (time < date_unix ? '<sup>-1</sup>' : "")) : '';
        return h + ':' + m + itvl;
    } else {
        var tz = time.split('\|');
        if (tz.length >= 2) {
            var hour = tz[0];
            var timezone = tz[1];
            var text = tz[2] == undefined ? "" : tz[2];
            return text + GetLocalTime(hour, timezone);
        }
    }
    return time;
};

function timeFormat(time, fmStr) {
	const weekCN = '一二三四五六日';
	const weekEN = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

	let year = time.getFullYear();
	let month = time.getMonth() + 1;
	let day = time.getDate();
	let hours = time.getHours();
	let minutes = time.getMinutes();
	let seconds = time.getSeconds();
	let milliSeconds = time.getMilliseconds();
	let week = time.getDay();

	month = month >= 10 ? month : ('0' + month);
	day = day >= 10 ? day : ('0' + day);
	hours = hours >= 10 ? hours : ('0' + hours);
	minutes = minutes >= 10 ? minutes : ('0' + minutes);
	seconds = seconds >= 10 ? seconds : ('0' + seconds);

	if (fmStr.indexOf('yyyy') !== -1) {
		fmStr = fmStr.replace('yyyy', year);
	} else {
		fmStr = fmStr.replace('yy', (year + '').slice(2));
	}
	fmStr = fmStr.replace('mm', month);
	fmStr = fmStr.replace('dd', day);
	fmStr = fmStr.replace('hh', hours);
	fmStr = fmStr.replace('MM', minutes);
	fmStr = fmStr.replace('ss', seconds);
	fmStr = fmStr.replace('SSS', milliSeconds);
	fmStr = fmStr.replace('W', weekCN[week - 1]);
	fmStr = fmStr.replace('ww', weekEN[week - 1]);
	fmStr = fmStr.replace('w', week);

	return fmStr;
};

//lang = getCookie('lang');
lang = window.location.pathname.replace("/", "").substr(0,2);
if (!lang || ' zh en ja es fr ru ko ro th it de cs ar bn el fi he hi hu id nl no pl pt sk sv ta tr '.indexOf(' ' + lang + ' ') == 1) lang = 'en';

export default {
	loading_icon: '/images/tips/loading-wedges.svg',
	lang,
	browser_date: timeFormat(new Date(), 'yyyy-mm-dd'),
	get_local_time,
	timeFormat,
};

</script>
