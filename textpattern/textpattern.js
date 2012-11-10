/**
 * Collection of client-side tools.
 */

/**
 * Checks if HTTP cookies are enabled.
 *
 * @return {boolean}
 */

function checkCookies()
{
	var date = new Date();

	date.setTime(date.getTime() + (60 * 1000));

	document.cookie = 'testcookie=enabled; expired=' + date.toGMTString() + '; path=/';

	cookieEnabled = (document.cookie.length > 2) ? true : false;

	date.setTime(date.getTime() - (60 * 1000));

	document.cookie = 'testcookie=; expires=' + date.toGMTString() + '; path=/';

	return cookieEnabled;
}

/**
 * Spawns a centred popup window.
 *
 * @param {string}  url     The location
 * @param {integer} width   The width
 * @param {integer} height  The height
 * @param {string}  options A list of options
 */

function popWin(url, width, height, options)
{
	var w = (width) ? width : 400;
	var h = (height) ? height : 400;

	var t = (screen.height) ? (screen.height - h) / 2 : 0;
	var l = (screen.width) ? (screen.width - w) / 2 : 0;

	var opt = (options) ? options : 'toolbar = no, location = no, directories = no, ' +
		'status = yes, menubar = no, scrollbars = yes, copyhistory = no, resizable = yes';

	var popped = window.open(url, 'popupwindow',
		'top = ' + t + ', left = ' + l + ', width = ' + w + ', height = ' + h + ',' + opt);

	popped.focus();
}

/**
 * Legacy multi-edit tool.
 *
 * @param      {object} elm
 * @deprecated in 4.6.0
 */

function poweredit(elm)
{
	var something = elm.options[elm.selectedIndex].value;

	// Add another chunk of HTML
	var pjs = document.getElementById('js');

	if (pjs == null)
	{
		var br = document.createElement('br');
		elm.parentNode.appendChild(br);

		pjs = document.createElement('P');
		pjs.setAttribute('id', 'js');
		elm.parentNode.appendChild(pjs);
	}

	if (pjs.style.display == 'none' || pjs.style.display == '')
	{
		pjs.style.display = 'block';
	}

	if (something != '')
	{
		switch (something)
		{
			default:
				pjs.style.display = 'none';
				break;
		}
	}

	return false;
}

/**
 * Basic confirmation for potentially powerful choices (like deletion, for example).
 *
 * @param  {string}  msg The message
 * @return {boolean} TRUE if user confirmed the action
 */

function verify(msg)
{
	return confirm(msg);
}

/**
 * Selects all multi-edit checkboxes.
 *
 * @deprecated in 4.5.0
 */

function selectall()
{
	$('form[name=longform] input[type=checkbox][name="selected[]"]').prop('checked', true);
}

/**
 * De-selects all multi-edit checkboxes.
 *
 * @deprecated in 4.5.0
 */

function deselectall()
{
	$('form[name=longform] input[type=checkbox][name="selected[]"]').prop('checked', false);
}

/**
 * Selects a range of multi-edit checkboxes.
 *
 * @deprecated in 4.5.0
 */

function selectrange()
{
	var inrange = false;

	$('form[name=longform] input[type=checkbox][name="selected[]"]').each(function ()
	{
		var $this = $(this);

		if ($this.is(':checked'))
		{
			inrange = (!inrange) ? true : false;
		}

		if (inrange)
		{
			$this.prop('checked', true);
		}
	});
}

/**
 * ?
 *
 * @deprecated in 4.5.0
 */

function cleanSelects()
{
	var withsel = document.getElementById('withselected');

	if (withsel && withsel.options[withsel.selectedIndex].value != '')
	{
		return (withsel.selectedIndex = 0);
	}
}

/**
 * Multi-edit functions.
 *
 * @param  {string|object} method Called method, or options
 * @param  {object}        opt    Options if method is a method
 * @return {object}        this
 * @since  4.5.0
 */

jQuery.fn.txpMultiEditForm = function (method, opt)
{
	var args = {};

	var defaults = {
		'checkbox' : 'input[name="selected[]"][type=checkbox]',
		'row' : 'tbody td',
		'highlighted' : 'tr',
		'selectedClass' : 'selected',
		'actions' : 'select[name=edit_method]',
		'submitButton' : '.multi-edit input[type=submit]',
		'selectAll' : 'input[name=select_all][type=checkbox]',
		'rowClick' : true,
		'altClick' : true,
		'confirmation' : textpattern.gTxt('are_you_sure')
	};

	if ($.type(method) !== 'string')
	{
		opt = method;
		method = null;
	}
	else
	{
		args = opt;
	}

	this.closest('form').each(function ()
	{
		var $this = $(this), form = {}, public = {}, private = {};

		if ($this.data('_txpMultiEdit'))
		{
			form = $this.data('_txpMultiEdit');
			opt = $.extend(form.opt, opt);
		}
		else
		{
			opt = $.extend(defaults, opt);
			form.boxes = opt.checkbox;
			form.editMethod = $this.find(opt.actions);
			form.lastCheck = null;
			form.opt = opt;
			form.selectAll = $this.find(opt.selectAll);
			form.button = $this.find(opt.submitButton);
		}

		/**
		 * Registers a multi-edit option.
		 *
		 * @param  {object} options
		 * @param  {string} options.label The option's label
		 * @param  {string} options.value The option's value
		 * @param  {string} options.html  The second step HTML
		 * @return {object} public
		 */

		public.addOption = function (options)
		{
			var settings = $.extend({
				'label' : null,
				'value' : null,
				'html' : null
			}, options);
			
			if (!settings.value)
			{
				return public;
			}

			var option = form.editMethod.find('option').filter(function ()
			{
				return $(this).attr('value') === settings.value;
			});
			
			var exists = (option.length > 0);
			form.editMethod.val('');
			
			if (!exists)
			{
				option = $('<option />');
			}
			
			if (!option.data('method'))
			{
				if (!option.attr('value'))
				{
					option.attr('value', settings.value);
				}
				
				if (!option.text() && settings.label)
				{
					option.text(settings.label);
				}
				
				option.data('method', settings.html);
			}
			
			if (!exists)
			{
				form.editMethod.append(option);
			}
			
			return public;
		};

		/**
		 * Selects rows based on supplied arguments.
		 *
		 * Only one of the filters applies at a time.
		 *
		 * @param  {object}  options
		 * @param  {array}   options.index Indexes to select
		 * @param  {array}   options.range Select index range, takes [min, max]
		 * @param  {array}   options.value Values to select
		 * @param  {boolean} TRUE to check, FALSE to uncheck
		 * @return {object}  public
		 */

		public.select = function (options)
		{
			var settings = $.extend({
				'index' : null,  // Select based on row's index.
				'range' : null,  // Select based on index range.
				'value' : null,  // Select based on values.
				'checked' : true // Check or uncheck.
			}, options);

			var obj = $this.find(form.boxes);

			if (settings.value !== null)
			{
				obj = obj.filter(function ()
				{
					return $.inArray($(this).attr('value'), settings.value) !== -1;
				});
			}

			else if (settings.index !== null)
			{
				obj = obj.filter(function (index)
				{
					return $.inArray(index, settings.index) !== -1;
				});
			}

			else if (settings.range !== null)
			{
				obj = obj.slice(settings.range[0], settings.range[1]);
			}

			obj.prop('checked', settings.checked).change();
			return public;
		};

		/**
		 * Highlights selected rows.
		 *
		 * @return {object} private
		 */

		private.highlight = function ()
		{
			var element = $this.find(form.boxes);
			element.filter(':checked').closest(opt.highlighted).addClass(opt.selectedClass);
			element.filter(':not(:checked)').closest(opt.highlighted).removeClass(opt.selectedClass);
			return private;
		};

		/**
		 * Extends click region to whole row.
		 *
		 * @return {object} private
		 */

		private.extendedClick = function ()
		{
			if (opt.rowClick)
			{
				var selector = opt.row;
			}
			else
			{
				var selector = form.boxes;
			}

			$this.on('click', selector, function (e)
			{

				var self = ($(e.target).is(form.boxes) || $(this).is(form.boxes));

				if (!self && (e.target != this || $(this).is('a, :input') || $(e.target).is('a, :input')))
				{
					return;
				}

				if (!self && opt.altClick && !e.altKey && !e.ctrlKey)
				{
					return;
				}

				var box = $(this).closest(opt.highlighted).find(form.boxes);

				if (box.length < 1)
				{
					return;
				}

				var checked = box.prop('checked');

				if (self)
				{
					checked = !checked;
				}

				if (e.shiftKey && form.lastCheck)
				{
					var boxes = $this.find(form.boxes);
					var start = boxes.index(box);
					var end = boxes.index(form.lastCheck);

					public.select({
						'range' : [Math.min(start, end), Math.max(start, end)+1],
						'checked' : !checked
					});
				}
				else if (!self)
				{
					box.prop('checked', !checked).change();
				}

				if (checked === false)
				{
					form.lastCheck = box;
				}
				else
				{
					form.lastCheck = null;
				}
			});

			return private;
		};

		/**
		 * Tracks row checks.
		 *
		 * @return {object} private
		 */

		private.checked = function ()
		{
			$this.on('change', form.boxes, function (e)
			{
				var box = $(this);
				var boxes = $this.find(form.boxes);

				if (box.prop('checked'))
				{
					$(this).closest(opt.highlighted).addClass(opt.selectedClass);
					$this.find(opt.selectAll).prop('checked', boxes.filter(':checked').length === boxes.length);
				}
				else
				{
					$(this).closest(opt.highlighted).removeClass(opt.selectedClass);
					$this.find(opt.selectAll).prop('checked', false);
				}
			});

			return private;
		};

		/**
		 * Handles edit method selecting.
		 *
		 * @return {object} private
		 */

		private.changeMethod = function ()
		{
			form.button.hide();

			form.editMethod.val('').change(function (e)
			{
				var selected = $(this).find('option:selected');
				$this.find('.multi-step').remove();

				if (selected.length < 1 || selected.val() === '')
				{
					form.button.hide();
					return private;
				}

				if (selected.data('method'))
				{
					$(this).after($('<div />').attr('class', 'multi-step multi-option').html(selected.data('method')));
					form.button.show();
				}
				else 
				{
					form.button.hide();
					$(this).parents('form').submit();
				}
			});

			return private;
		};

		/**
		 * Handles sending.
		 *
		 * @return {object} private
		 */

		private.sendForm = function ()
		{
			$this.submit(function ()
			{
				if (opt.confirmation !== false && verify(opt.confirmation) === false)
				{
					form.editMethod.val('').change();
					return false;
				}
			});

			return private;
		};

		if(!$this.data('_txpMultiEdit'))
		{
			private.highlight().extendedClick().checked().changeMethod().sendForm();

			(function ()
			{
				var multiOptions = $this.find('.multi-option:not(.multi-step)');

				form.editMethod.find('option[value!=""]').each(function ()
				{
					var value = $(this).val();

					var option = multiOptions.filter(function ()
					{
						return $(this).hasClass('multi-option-'+value);
					});

					if (option.length > 0)
					{
						public.addOption({
							'label' : null,
							'html' : option.eq(0).contents(),
							'value' : $(this).val()
						});
					}
				});

				multiOptions.remove();
			})();

			$this.on('change', opt.selectAll, function (e)
			{
				public.select({
					'checked' : $(this).prop('checked')
				});
			});
		}

		if (method && public[method])
		{
			public[method].call($this, args);
		}

		$this.data('_txpMultiEdit', form);
	});

	return this;
};

/**
 * Adds an event handler.
 *
 * See jQuery before trying to use this.
 *
 * @author S.Andrew http://www.scottandrew.com/
 * @param  {object}  elm        The element to attach to
 * @param  {string}  evType     The event
 * @param  {object}  fn         The callback function
 * @param  {boolean} useCapture Initiate capture
 */

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

/**
 * Sets a HTTP cookie.
 *
 * @param {string}  name  The name
 * @param {string}  value The value
 * @param {integer} days  Expires in
 */

function setCookie(name, value, days)
{
	if (days)
	{
		var date = new Date();

		date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));

		var expires = '; expires=' + date.toGMTString();
	}

	else
	{
		var expires = '';
	}

	document.cookie = name + '=' + value + expires + '; path=/';
}

/**
 * Gets a HTTP cookie's value.
 *
 * @param  {string} name The name
 * @return {string} The cookie
 */

function getCookie(name)
{
	var nameEQ = name + '=';

	var ca = document.cookie.split(';');

	for (var i = 0; i < ca.length; i++)
	{
		var c = ca[i];

		while (c.charAt(0) == ' ')
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

/**
 * Deletes a HTTP cookie.
 *
 * @param {string} name The cookie
 */

function deleteCookie(name)
{
	setCookie(name, '', -1);
}

/**
 * Gets element by class.
 *
 * See jQuery before trying to use this.
 *
 * @param  {string} classname The HTML class
 * @param  {object} node      The node, defaults to the document
 * @return {object} Matching nodes
 * @see    http://www.snook.ca/archives/javascript/your_favourite_1/
 */

function getElementsByClass(classname, node)
{
	var a = [];
	var re = new RegExp('(^|\\s)' + classname + '(\\s|$)');
	if (node == null)
	{
		node = document;
	}
	var els = node.getElementsByTagName("*");
	for (var i = 0, j = els.length; i < j; i++)
	{
		if (re.test(els[i].className))
		{
			a.push(els[i]);
		}
	}
	return a;
}

/**
 * Toggles panel's visibility and saves the state to the server.
 *
 * @param  {string}  id The element ID
 * @return {boolean} Returns FALSE
 */

function toggleDisplay(id)
{
	var obj = $('#' + id);
	if (obj.length)
	{
		obj.toggle();

		// Send state of toggle pane to server.
		sendAsyncEvent(
			{
				event: textpattern.event,
				step: 'save_pane_state',
				pane: $(obj).attr('id'),
				visible: ($(obj).is(':visible'))
			}
		);
	}

	return false;
}

/**
 * Direct show/hide referred #segment; decorate parent lever.
 */

function toggleDisplayHref()
{
	var $this = $(this);
	var href = $this.attr('href');
	var lever = $this.parent('.txp-summary');

	if (href)
	{
		toggleDisplay(href.substr(1));
	}

	if (lever.length)
	{
		var vis = $(href).is(':visible');
		lever.toggleClass('expanded', vis);
		$this.attr('aria-pressed', vis.toString());
		$(href).attr('aria-expanded', vis.toString());
	}

	return false;
}

/**
 * Shows/hides matching elements.
 *
 * @param {string}  className Targeted element's class
 * @param {boolean} show      TRUE to display
 */

function setClassDisplay(className, show)
{
	var obj = $('.' + className);
	
	if (show == 1)
	{
		obj.show();
	}
	else
	{
		obj.hide();
	}
}

/**
 * Toggles panel's visibility and saves the state to a HTTP cookie.
 *
 * @param {string} classname The HTML class
 */

function toggleClassRemember(className)
{
	var v = getCookie('toggle_' + className);
	v = (v == 1 ? 0 : 1);

	setCookie('toggle_' + className, v, 365);
	setClassDisplay(className, v);
	setClassDisplay(className + '_neg', 1 - v);
}

/**
 * Toggle visibility of matching elements based on a cookie value.
 *
 * @param {string}  className The HTML class
 * @param {string}  force     The value
 */

function setClassRemember(className, force)
{
	if (typeof(force) != 'undefined')
	{
		setCookie('toggle_' + className, force, 365);
	}
	var v = getCookie('toggle_' + className);
	setClassDisplay(className, v);
	setClassDisplay(className + '_neg', 1 - v);
}

/**
 * Load data from the server using a HTTP POST request.
 *
 * @param  {object} data   POST payload
 * @param  {object} fn     Success handler
 * @param  {string} format Response data format, defaults to 'xml'
 * @return {object} this
 * @see    http://api.jquery.com/jQuery.post/
 */

function sendAsyncEvent (data, fn, format)
{
	if ($.type(data) === 'string' && data.length > 0)
	{
		// Got serialised data.
		data = data + '&app_mode=async&_txp_token=' + textpattern._txp_token;
	}
	else
	{
		data.app_mode = 'async';
		data._txp_token = textpattern._txp_token;
	}
	format = format || 'xml';
	return $.post('index.php', data, fn, format);
}

/**
 * A pub/sub hub for client-side events.
 *
 * @since 4.5.0
 */

textpattern.Relay =
{
	/**
	 * Publishes an event to all registered subscribers.
	 *
	 * @param  {string} event The event
	 * @param  {object} data  The data passed to registered subscribers
	 * @return {object} The Relay object
	 * @example
	 * textpattern.Relay.callback('newEvent', {'name1' : 'value1', 'name2' : 'value2'});
	 */

	callback: function (event, data)
	{
		return $(this).trigger(event, data);
	},

	/**
	 * Subscribes to an event.
	 *
	 * @param  {string} The event
	 * @param  {object} fn  The callback function
	 * @return {object} The Relay object
	 * @example
	 * textpattern.Relay.register('event',
	 * 	function(event, data)
	 * 	{
	 * 		alert(data);
	 * 	}
	 * );
	 */

	register: function (event, fn)
	{
		$(this).on(event, fn);
		return this;
	}
};

/**
 * Logs debugging messages.
 *
 * @since 4.6.0
 */

textpattern.Console =
{
	/**
	 * Stores an array of invoked messages.
	 */

	history : [],

	/**
	 * Logs a message.
	 *
	 * @param message The message
	 * @example
	 * textpattern.Console.log('Some message');
	 */

	log : function (message)
	{
		if (textpattern.production_status !== 'debug')
		{
			return;
		}

		textpattern.Console.history.push(message);

		textpattern.Relay.callback('txpConsoleLog', {
			'message' : message
		});
	}
};

/**
 * Console API module for textpattern.Console.
 *
 * Passes invoked messages to Web/JavaScript Console
 * using console.log().
 *
 * Uses a namespaced 'txpConsoleLog.ConsoleAPI' event.
 */

textpattern.Relay.register('txpConsoleLog.ConsoleAPI', function (event, data)
{
	if ($.type(console) === 'object' && $.type(console.log) === 'function')
	{
		console.log(data.message);
	}
});

/**
 * Script routing.
 *
 * @since 4.6.0
 */

textpattern.Route =
{
	/**
	 * An array of attached listeners.
	 */

	attached : [],

	/**
	 * Attachs a listener.
	 *
	 * @param {string} page The page
	 * @param {object} fn   The callback
	 */

	add : function (page, fn)
	{
		textpattern.Route.attached.push({
			'page' : page,
			'fn'   : fn
		});
	},

	/**
	 * Initialises attached listeners.
	 *
	 * @param {object} options       Options
	 * @param {string} options.event The event
	 * @param {string} options.step  The step
	 */

	init : function (options)
	{
		var options = $.extend({
			'event' : textpattern.event,
			'step'  : textpattern.step
		}, options);

		$.each(textpattern.Route.attached, function(index, data)
		{
			if (data.page === options.event || data.page === options.event + '.' + options.step)
			{
				data.fn(options);
			}
		});
	}
};

/**
 * Sends a form using AJAX and processes the response.
 *
 * @param  {object} options          Options
 * @param  {string} options.dataType The response data type
 * @param  {object} options.success  The success callback
 * @param  {object} options.error    The error callback
 * @return {object} this
 * @since  4.5.0
 */

jQuery.fn.txpAsyncForm = function (options)
{
	options = $.extend({
		dataType : 'script',
		success  : null,
		error    : null
	}, options);

	// Send form data to application, process response as script.
	this.on('submit.txpAsyncForm', function (event)
	{
		event.preventDefault();

		var $this = $(this);
		var form =
		{
			button  : $this.find('input[type="submit"]:focus').eq(0),
			data    : $this.serialize(),
			spinner : $('<span />').addClass('spinner')
		};

		// Show feedback while processing.
		$this.addClass('busy');
		$('body').addClass('busy');

		// WebKit does not set :focus on button-click: use first submit input as a fallback.
		if (!form.button.length)
		{
			form.button = $this.find('input[type="submit"]').eq(0);
		}

		form.button.attr('disabled', true).after(form.spinner);

		if (form.data)
		{
			form.data += '&' + (form.button.attr('name') || '_txp_submit') + '=' + (form.button.val() || '_txp_submit');
		}

		sendAsyncEvent(form.data, function () {}, options.dataType)
			.done(function (data, textStatus, jqXHR)
			{
				if (options.success)
				{
					options.success($this, event, data, textStatus, jqXHR);
				}

				textpattern.Relay.callback('txpAsyncForm.success', {
					'this'       : $this,
					'event'      : event,
					'data'       : data,
					'textStatus' : textStatus,
					'jqXHR'      : jqXHR
				});
			})
			.fail(function (jqXHR, textStatus, errorThrown)
			{
				if (options.error)
				{
					options.error($this, event, jqXHR, $.ajaxSetup(), errorThrown);
				}

				textpattern.Relay.callback('txpAsyncForm.error', {
					'this'         : $this,
					'event'        : event,
					'jqXHR'        : jqXHR,
					'ajaxSettings' : $.ajaxSetup(),
					'thrownError'  : errorThrown
				});
			})
			.always(function ()
			{
				$this.removeClass('busy');
				form.button.removeAttr('disabled');
				form.spinner.remove();
				$('body').removeClass('busy');
			});
	});

	return this;
};

/**
 * Sends a link using AJAX and processes the plain text response.
 *
 * @param  {object} options          Options
 * @param  {object} options.success  The success callback
 * @param  {object} options.error    The error callback
 * @return {object} this
 * @since  4.5.0
 */

jQuery.fn.txpAsyncHref = function (options)
{
	options = $.extend({
		success : null,
		error   : null
	}, options);

	this.on('click.txpAsyncHref', function (event)
	{
		event.preventDefault();
		var $this = $(this);
		var data = this.search.replace('?', '') + '&' + $.param({value : $this.text()});

		// Show feedback while processing.
		$this.addClass('busy');
		$('body').addClass('busy');

		sendAsyncEvent(data, function () {}, 'text')
			.done(function (data, textStatus, jqXHR)
			{
				$this.html(data);

				if (options.success)
				{
					options.success($this, event, data, textStatus, jqXHR);
				}

				textpattern.Relay.callback('txpAsyncHref.success', {
					'this'       : $this,
					'event'      : event,
					'data'       : data,
					'textStatus' : textStatus,
					'jqXHR'      : jqXHR
				});
			})
			.fail(function (jqXHR, textStatus, errorThrown)
			{
				if (options.error)
				{
					options.error($this, event, jqXHR, $.ajaxSetup(), errorThrown);
				}

				textpattern.Relay.callback('txpAsyncHref.error', {
					'this'         : $this,
					'event'        : event,
					'jqXHR'        : jqXHR,
					'ajaxSettings' : $.ajaxSetup(),
					'thrownError'  : errorThrown
				});
			})
			.always(function ()
			{
				$this.removeClass('busy');
				$('body').removeClass('busy');
			});
	});

	return this;
};

/**
 * Returns an i18n string.
 *
 * @param  {string}  i18n   The i18n string
 * @param  {object}  atts   Replacement map
 * @param  {boolean} escape TRUE to escape HTML in atts
 * @return {string}  The string
 * @example
 * textpattern.gTxt('string', {'{name}' : 'example'}, true);
 */

textpattern.gTxt = function (i18n, atts, escape)
{
	var tags = atts || {};
	var string = i18n;
	var name = string.toLowerCase();

	if ($.type(textpattern.textarray[name]) !== 'undefined')
	{
		string = textpattern.textarray[name];
	}

	if (escape !== false)
	{
		string = $('<div/>').text(string).html();

		$.each(tags, function (key, value)
		{
			tags[key] = $('<div/>').text(value).html();
		});
	}

	$.each(tags, function (key, value)
	{
		string = string.replace(key, value);
	});

	return string;
};

/**
 * Replaces HTML contents of each matched with i18n string.
 *
 * This is a jQuery plugin for textpattern.gTxt().
 *
 * @param  {object|string}  options        Options or the i18n string
 * @param  {string}         options.string The i18n string
 * @param  {object}         options.tags   Replacement map
 * @param  {boolean}        options.escape TRUE to escape HTML in tags
 * @param  {object}         tags           Replacement map
 * @param  {boolean}        escape         TRUE to escape HTML in tags
 * @return {object}         this
 * @see    textpattern.gTxt()
 * @example
 * $('p').gTxt('string').class('alert-block warning');
 */

jQuery.fn.gTxt = function (opts, tags, escape)
{
	var options = $.extend({
		'string' : opts,
		'tags' : tags,
		'escape' : escape
	}, opts);

	this.html(textpattern.gTxt(options.string, options.tags, options.escape));
	return this;
};

// ESC button closes alert messages.

$(document).keyup(function (e)
{
	if (e.keyCode == 27)
	{
		$('.close').parent().remove();
	}
});

/**
 * Cookie status.
 *
 * @deprecated in 4.6.0
 */

var cookieEnabled = true;

// Login panel.

textpattern.Route.add('login', function ()
{
	// Check cookies.
	if (!checkCookies())
	{
		cookieEnabled = false;
		$('#txp-main').prepend($('<p class="alert-block warning" />').text(textpattern.gTxt('cookies_must_be_enabled')));
	}

	// Focus on either username or password when empty.
	var has_name = $('#login_name').val().length;
	var password_box = $('#login_password').val();
	var has_password = (password_box) ? password_box.length : 0;

	if (!has_name)
	{
		$('#login_name').focus();
	}
	else if (!has_password)
	{
		$('#login_password').focus();
	}
});

// Import panel.

textpattern.Route.add('import', function ()
{
	var importOptions =
	{
		'mtdb' : '#mtblogid, #databased',
		'wp'   : '#wponly, #databased',
		'b2'   : '#databased'
	};

	$('select[name=import_tool]').change(function ()
	{
		var value = $(this).val();

		$.each(importOptions, function(option, selector)
		{
			$(selector).hide();
		});

		if ($.type(importOptions[value]) === 'string')
		{
			$(importOptions[value]).show();
		}
	});
});

// Write panel.

textpattern.Route.add('article', function ()
{
	// Assume users would not change the timestamp if they wanted to "publish now"/"reset time".
	$(
		'#write-timestamp input.year,' +
		'#write-timestamp input.month,' +
		'#write-timestamp input.day,' +
	 	'#write-timestamp input.hour,' +
	 	'#write-timestamp input.minute,' +
	 	'#write-timestamp input.second'
	 ).change(function ()
	{
		$('#publish_now').prop('checked', false);
		$('#reset_time').prop('checked', false);
	});
});

// Styles panel.

textpattern.Route.add('css', function ()
{
	$('#txp_clone').click(function (e)
	{
		e.preventDefault();
		$(this).closest('form').append('<input type="hidden" name="copy" value="1" />').submit();
	});
});

// Initialise JavaScript.

$(document).ready(function ()
{
	// Disable spellchecking on all elements of class "code" in capable browsers.
	var c = $(".code")[0];
	if (c && "spellcheck" in c)
	{
		$(".code").prop("spellcheck", false);
	}

	// Enable spellcheck for all elements mentioned in textpattern.do_spellcheck.
	c = $(textpattern.do_spellcheck)[0];
	if (c && "spellcheck" in c)
	{
		$(textpattern.do_spellcheck).prop("spellcheck", true);
	}

	// Attach toggle behaviours.
	$(document).on('click', '.txp-summary a[class!=pophelp]', toggleDisplayHref);

	// Attach multi-edit form.
	$('.multi_edit_form').txpMultiEditForm();

	// Establish AJAX timeout from prefs.
	if ($.ajaxSetup().timeout === undefined)
	{
		$.ajaxSetup({timeout : textpattern.ajax_timeout});
	}

	// Set up synchronous links.
	$('form.async').txpAsyncForm({
		error: function ()
		{
			window.alert(textpattern.gTxt('form_submission_error'));
		}
	});

	// Set up synchronous forms.
	$('a.async').txpAsyncHref({
		error: function ()
		{
			window.alert(textpattern.gTxt('form_submission_error'));
		}
	});

	// Close button on the announce pane.
	$(document).on('click', '.close', function (e)
	{
		e.preventDefault();
		$(this).parent().remove();
	});

	// Initialise dynamic WAI-ARIA attributes.
	$('.txp-summary a').each(function (i, elm)
	{
		// Get id of toggled <section> region.
		var region = $(elm).attr('href');
		if (region)
		{
			var $region = $(region), vis = $region.is(':visible').toString();
			$(elm).attr('aria-control', region.substr(1)).attr('aria-pressed', vis);
			$region.attr('aria-expanded', vis);
		}
	});

	// Initialises panel specific JavaScript.
	textpattern.Route.init();

	// Arm UI.
	$('body').removeClass('not-ready');
});
