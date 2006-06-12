
// -------------------------------------------------------------

function popWin(url, width, height, options)
{
	var w = (width) ? width : 400;
	var h = (height) ? height : 400;

	var t = (screen.height) ? (screen.height - h) / 2 : 0;
	var l =  (screen.width) ? (screen.width - w) / 2 : 0;

	var opt = (options) ? options : 'toolbar = no, location = no, directories = no, '+
		'status = yes, menubar = no, scrollbars = yes, copyhistory = no, resizable = yes';

	var popped = window.open(url, 'popupwindow', 
		'top = '+t+', left = '+l+', width = '+w+', height = '+h+',' + opt);

	popped.focus();
}

// -------------------------------------------------------------

function verify(msg)
{
	return confirm(msg);
}

// -------------------------------------------------------------

function toggleDisplay(id)
{
	if (!document.getElementById)
	{
		return false;
	}

	var obj = document.getElementById(id);

	if (obj.style.display == '' || obj.style.display == 'none')
	{
		obj.style.display = 'block';
	}

	else
	{
		obj.style.display = 'none';
	}
}

// -------------------------------------------------------------

function selectall()
{
	var cnt = 0;
	var elem = window.document.longform.elements;

	cnt = elem.length;

	for (var i = 0; i < cnt; i++)
	{
		elem[i].checked = true;
	}
}
	
function deselectall()
{
	var cnt = 0;
	var elem = window.document.longform.elements;

	cnt = elem.length;

	for (var i = 0; i < cnt; i++)
	{
		elem[i].checked = false;
	}
}

function selectrange()
{
	var inrange = false;
	var cnt = 0;
	var elem = window.document.longform.elements;

	cnt = elem.length;

	for (var i=0; i < cnt; i++)
	{
		if (elem[i].type == 'checkbox')
		{
			if (elem[i].checked == true)
			{
				if (!inrange)
				{
					inrange = true;
				}

				else
				{
					inrange = false;
				}
			}

			if (inrange)
			{
				elem[i].checked = true;
			}
		}
	}
}

// -------------------------------------------------------------

function cleanSelects()
{
	var withsel = document.getElementById('withselected');

	if (withsel.options[withsel.selectedIndex].value != '')
	{
		return (withsel.selectedIndex = 0);
	}
}

// -------------------------------------------------------------
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
