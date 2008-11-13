
// -------------------------------------------------------------
// admin-side "cookies required" warning

function checkCookies()
{
	var date = new Date();

	date.setTime(date.getTime() + (60 * 1000));

	document.cookie = 'testcookie=enabled; expired='+date.toGMTString()+'; path=/';

	cookieEnabled = (document.cookie.length > 2) ? true : false;

	date.setTime(date.getTime() - (60 * 1000));

	document.cookie = 'testcookie=; expires='+date.toGMTString()+'; path=/';

	return cookieEnabled;
}

// -------------------------------------------------------------
// auto-centering popup windows

function popWin(url, width, height, options)
{
	var w = (width) ? width : 400;
	var h = (height) ? height : 400;

	var t = (screen.height) ? (screen.height - h) / 2 : 0;
	var l =	 (screen.width) ? (screen.width - w) / 2 : 0;

	var opt = (options) ? options : 'toolbar = no, location = no, directories = no, '+
		'status = yes, menubar = no, scrollbars = yes, copyhistory = no, resizable = yes';

	var popped = window.open(url, 'popupwindow',
		'top = '+t+', left = '+l+', width = '+w+', height = '+h+',' + opt);

	popped.focus();
}

// -------------------------------------------------------------
// basic confirmation for potentially powerful choice 
// (like deletion, for example)

function verify(msg)
{
	return confirm(msg);
}

// -------------------------------------------------------------
// multi-edit checkbox utils

function selectall()
{
	var elem = window.document.longform.elements;
	var cnt = elem.length;

	for (var i = 0; i < cnt; i++)
	{
		if (elem[i].name == 'selected[]')
		{
			elem[i].checked = true;
		}
	}
}

function deselectall()
{
	var elem = window.document.longform.elements;
	var cnt = elem.length;

	for (var i = 0; i < cnt; i++)
	{
		if (elem[i].name == 'selected[]')
		{
			elem[i].checked = false;
		}
	}
}

function selectrange()
{
	var inrange = false;
	var elem = window.document.longform.elements;
	var cnt = elem.length;
	
	for (var i = 0; i < cnt; i++)
	{
		if (elem[i].name == 'selected[]')
		{
			if (elem[i].checked == true)
			{
				inrange = (!inrange) ? true : false;
			}

			if (inrange)
			{
				elem[i].checked = true;
			}
		}
	}
}

// -------------------------------------------------------------
// ?

function cleanSelects()
{
	var withsel = document.getElementById('withselected');

	if (withsel && withsel.options[withsel.selectedIndex].value != '')
	{
		return (withsel.selectedIndex = 0);
	}
}

// -------------------------------------------------------------
// event handling
// By S.Andrew -- http://www.scottandrew.com/

function addEvent(elm, evType, fn, useCapture)
{
	if (elm.addEventListener)
	{
		elm.addEventListener(evType, fn, useCapture);
		return true;
	}

	else if (elm.attachEvent)
	{
		var r = elm.attachEvent('on' + evType, fn);
		return r;
	}

	else
	{
		elm['on' + evType] = fn;
	}
}

// -------------------------------------------------------------
// cookie handling

function setCookie(name, value, days)
{
	if (days)
	{
		var date = new Date();

		date.setTime(date.getTime() + (days*24*60*60*1000));

		var expires = '; expires=' + date.toGMTString();
	}

	else
	{
		var expires = '';
	}

	document.cookie = name + '=' + value + expires + '; path=/';
}

function getCookie(name)
{
	var nameEQ = name + '=';

	var ca = document.cookie.split(';');

	for (var i = 0; i < ca.length; i++)
	{
		var c = ca[i];

		while (c.charAt(0)==' ')
		{
			c = c.substring(1, c.length);
		}

		if (c.indexOf(nameEQ) == 0)
		{
			return c.substring(nameEQ.length, c.length);
		}
	}

	return null;
}

function deleteCookie(name)
{
	setCookie(name, '', -1);
}

// -------------------------------------------------------------
// @see http://www.snook.ca/archives/javascript/your_favourite_1/
function getElementsByClass(classname, node)
{
	var a = [];
	var re = new RegExp('(^|\\s)' + classname + '(\\s|$)');
	if(node == null) node = document;
	var els = node.getElementsByTagName("*");
	for(var i=0,j=els.length; i<j; i++)
		if(re.test(els[i].className)) a.push(els[i]);
	return a;
}

// -------------------------------------------------------------
// direct show/hide

function toggleDisplay(id)
{
	var obj = $('#' + id);
	if (obj) obj.toggle();
}

// -------------------------------------------------------------
// show/hide matching elements

function setClassDisplay(className, value)
{
	var elements = getElementsByClass(className);
	var is_ie = (navigator.appName == 'Microsoft Internet Explorer');

	for (var i = 0; i < elements.length; i++)
	{
		var tagname = elements[i].nodeName.toLowerCase();
		var type = 'block';

		if (tagname == 'td' || tagname == 'th')
		{
			type = (is_ie ? 'inline' : 'table-cell');
		}

		elements[i].style.display = (value== 1 ? type : 'none');
	}
}

// -------------------------------------------------------------
// toggle show/hide matching elements, and set a cookie to remember

function toggleClassRemember(className)
{
	var v = getCookie('toggle_' + className);
	v = (v == 1 ? 0 : 1);

	setCookie('toggle_' + className, v, 365);

	setClassDisplay(className, v);
	setClassDisplay(className+'_neg', 1-v);
}

// -------------------------------------------------------------
// show/hide matching elements based on cookie value

function setClassRemember(className, force)
{
	if (typeof(force) != 'undefined')
		setCookie('toggle_' + className, force, 365);
	var v = getCookie('toggle_' + className);

	setClassDisplay(className, v);
	setClassDisplay(className+'_neg', 1-v);
}
