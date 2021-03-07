lastScrollY = 0;
var graySrc = false;
var InterTime = 1;
var maxWidth = -1;
var minWidth = -128;
var numInter = 8;
var BigInter;
var SmallInter;
var o = null;
var i = 0;
services2 = function(id, _top, _left) {
    var me = id.charAt ? document.getElementById(id) : id, d1 = document.body, d2 = document.documentElement;
    d1.style.height = d2.style.height = '100%'; me.style.top = _top ? _top + 'px' : 0; me.style.left = _left + "px"; 
    me.style.position = 'absolute';
    me.style.display = 'block';
    setInterval(function() { me.style.top = parseInt(me.style.top) + (Math.max(d1.scrollTop, d2.scrollTop) + _top - parseInt(me.style.top)) * 0.1 + 'px'; }, 10 + parseInt(Math.random() * 20));
    return arguments.callee;
};
$(document).ready(function() {
    var html = '';
    html += '<div id="services2" style="z-index:2;display:none;" onmouseover=toBig() onmouseout=toSmall()>';
    html += '    <div class="services">';
    html += '        <div class="con" style="position:relative;">';
    html += '        	<div style="position:relative; top:106px">';
    html += '                	<p><center><a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=2917152020&site=qq&menu=yes"><img border="0" src="http://wpa.qq.com/pa?p=2:2917152020:41" / ></a></center></p>';
    html += '            </div>';
    html += '        </div>';
    html += '    </div>';
    html += '    <a href="http://mxkj.sinaapp.com/themes/Default/images/qrcode.jpg" class="wx" target="_blank">微信二维码</a>';
    html += '    <div class="Obtn"></div>';
    html += '</div>';

    $(document.body).append(html);

    o = document.getElementById("services2");
    i = parseInt(o.style.left);

    services2('services2', 210, -129);
});

function Big() {
    if (parseInt(o.style.left) < maxWidth) {
        i = parseInt(o.style.left);
        i += numInter;
        o.style.left = i + "px";
        if (i == maxWidth)
            clearInterval(BigInter);
    }
    if (!graySrc) {
        $(o).find("img").each(function() {
            $(this).attr("src", $(this).attr("Original"));
        });
        graySrc = true;
    }
}
function toBig() {
    clearInterval(SmallInter);
    clearInterval(BigInter);
    BigInter = setInterval(Big, InterTime);
}
function Small() {
    if (parseInt(o.style.left) > minWidth) {
        i = parseInt(o.style.left);
        i -= numInter;
        o.style.left = i + "px";

        if (i == minWidth)
            clearInterval(SmallInter);
    }
}
function toSmall() {
    clearInterval(SmallInter);
    clearInterval(BigInter);
    SmallInter = setInterval(Small, InterTime);

}