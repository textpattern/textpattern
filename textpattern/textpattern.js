/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2016 The Textpattern Development Team
 *
 * This file is part of Textpattern.
 *
 * Textpattern is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * Textpattern is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Textpattern. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Collection of client-side tools.
 */

/**
 * Ascertain the page direction (LTR or RTL) as a variable.
 */

var langdir = document.documentElement.dir;

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

    if (pjs == null) {
        var br = document.createElement('br');
        elm.parentNode.appendChild(br);

        pjs = document.createElement('P');
        pjs.setAttribute('id', 'js');
        elm.parentNode.appendChild(pjs);
    }

    if (pjs.style.display == 'none' || pjs.style.display == '') {
        pjs.style.display = 'block';
    }

    if (something != '') {
        switch (something) {
            default:
                pjs.style.display = 'none';
                break;
        }
    }

    return false;
}

/**
 * Basic confirmation for potentially powerful choices (like deletion,
 * for example).
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

        if ($this.is(':checked')) {
            inrange = (!inrange) ? true : false;
        }

        if (inrange) {
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

    if (withsel && withsel.options[withsel.selectedIndex].value != '') {
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
        'checkbox'      : 'input[name="selected[]"][type=checkbox]',
        'row'           : 'tbody td',
        'highlighted'   : 'tr',
        'selectedClass' : 'selected',
        'actions'       : 'select[name=edit_method]',
        'submitButton'  : '.multi-edit input[type=submit]',
        'selectAll'     : 'input[name=select_all][type=checkbox]',
        'rowClick'      : true,
        'altClick'      : true,
        'confirmation'  : textpattern.gTxt('are_you_sure')
    };

    if ($.type(method) !== 'string') {
        opt = method;
        method = null;
    } else {
        args = opt;
    }

    this.closest('form').each(function ()
    {
        var $this = $(this), form = {}, methods = {}, lib = {};

        if ($this.data('_txpMultiEdit')) {
            form = $this.data('_txpMultiEdit');
            opt = $.extend(form.opt, opt);
        } else {
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
         * @return {object} methods
         */

        methods.addOption = function (options)
        {
            var settings = $.extend({
                'label' : null,
                'value' : null,
                'html'  : null
            }, options);

            if (!settings.value) {
                return methods;
            }

            var option = form.editMethod.find('option').filter(function ()
            {
                return $(this).val() === settings.value;
            });

            var exists = (option.length > 0);
            form.editMethod.val('');

            if (!exists) {
                option = $('<option />');
            }

            if (!option.data('_txpMultiMethod')) {
                if (!option.val()) {
                    option.val(settings.value);
                }

                if (!option.text() && settings.label) {
                    option.text(settings.label);
                }

                option.data('_txpMultiMethod', settings.html);
            }

            if (!exists) {
                form.editMethod.append(option);
            }

            return methods;
        };

        /**
         * Selects rows based on supplied arguments.
         *
         * Only one of the filters applies at a time.
         *
         * @param  {object}  options
         * @param  {array}   options.index   Indexes to select
         * @param  {array}   options.range   Select index range, takes [min, max]
         * @param  {array}   options.value   Values to select
         * @param  {boolean} options.checked TRUE to check, FALSE to uncheck
         * @return {object}  methods
         */

        methods.select = function (options)
        {
            var settings = $.extend({
                'index'   : null,
                'range'   : null,
                'value'   : null,
                'checked' : true
            }, options);

            var obj = $this.find(form.boxes);

            if (settings.value !== null) {
                obj = obj.filter(function ()
                {
                    return $.inArray($(this).val(), settings.value) !== -1;
                });
            } else if (settings.index !== null) {
                obj = obj.filter(function (index)
                {
                    return $.inArray(index, settings.index) !== -1;
                });
            } else if (settings.range !== null) {
                obj = obj.slice(settings.range[0], settings.range[1]);
            }

            obj.prop('checked', settings.checked).change();

            return methods;
        };

        /**
         * Highlights selected rows.
         *
         * @return {object} lib
         */

        lib.highlight = function ()
        {
            var element = $this.find(form.boxes);
            element.filter(':checked').closest(opt.highlighted).addClass(opt.selectedClass);
            element.filter(':not(:checked)').closest(opt.highlighted).removeClass(opt.selectedClass);
            return lib;
        };

        /**
         * Extends click region to whole row.
         *
         * @return {object} lib
         */

        lib.extendedClick = function ()
        {
            if (opt.rowClick) {
                var selector = opt.row;
            } else {
                var selector = form.boxes;
            }

            $this.on('click', selector, function (e)
            {
                var self = ($(e.target).is(form.boxes) || $(this).is(form.boxes));

                if (!self && (e.target != this || $(this).is('a, :input') || $(e.target).is('a, :input'))) {
                    return;
                }

                if (!self && opt.altClick && !e.altKey && !e.ctrlKey) {
                    return;
                }

                var box = $(this).closest(opt.highlighted).find(form.boxes);

                if (box.length < 1) {
                    return;
                }

                var checked = box.prop('checked');

                if (self) {
                    checked = !checked;
                }

                if (e.shiftKey && form.lastCheck) {
                    var boxes = $this.find(form.boxes);
                    var start = boxes.index(box);
                    var end = boxes.index(form.lastCheck);

                    methods.select({
                        'range'   : [Math.min(start, end), Math.max(start, end) + 1],
                        'checked' : !checked
                    });
                } else if (!self) {
                    box.prop('checked', !checked).change();
                }

                if (checked === false) {
                    form.lastCheck = box;
                } else {
                    form.lastCheck = null;
                }
            });

            return lib;
        };

        /**
         * Tracks row checks.
         *
         * @return {object} lib
         */

        lib.checked = function ()
        {
            $this.on('change', form.boxes, function (e)
            {
                var box = $(this);
                var boxes = $this.find(form.boxes);

                if (box.prop('checked')) {
                    $(this).closest(opt.highlighted).addClass(opt.selectedClass);
                    $this.find(opt.selectAll).prop('checked', boxes.filter(':checked').length === boxes.length);
                } else {
                    $(this).closest(opt.highlighted).removeClass(opt.selectedClass);
                    $this.find(opt.selectAll).prop('checked', false);
                }
            });

            return lib;
        };

        /**
         * Handles edit method selecting.
         *
         * @return {object} lib
         */

        lib.changeMethod = function ()
        {
            form.button.hide();

            form.editMethod.val('').change(function (e)
            {
                var selected = $(this).find('option:selected');
                $this.find('.multi-step').remove();

                if (selected.length < 1 || selected.val() === '') {
                    form.button.hide();
                    return lib;
                }

                if (selected.data('_txpMultiMethod')) {
                    $(this).after($('<div />').attr('class', 'multi-step multi-option').html(selected.data('_txpMultiMethod')));
                    form.button.show();
                } else {
                    form.button.hide();
                    $(this).parents('form').submit();
                }
            });

            return lib;
        };

        /**
         * Handles sending.
         *
         * @return {object} lib
         */

        lib.sendForm = function ()
        {
            $this.submit(function ()
            {
                if (opt.confirmation !== false && verify(opt.confirmation) === false) {
                    form.editMethod.val('').change();

                    return false;
                }
            });

            return lib;
        };

        if (!$this.data('_txpMultiEdit')) {
            lib.highlight().extendedClick().checked().changeMethod().sendForm();

            (function ()
            {
                var multiOptions = $this.find('.multi-option:not(.multi-step)');

                form.editMethod.find('option[value!=""]').each(function ()
                {
                    var value = $(this).val();

                    var option = multiOptions.filter(function ()
                    {
                        return $(this).data('multi-option') === value;
                    });

                    if (option.length > 0) {
                        methods.addOption({
                            'label' : null,
                            'html'  : option.eq(0).contents(),
                            'value' : $(this).val()
                        });
                    }
                });

                multiOptions.remove();
            })();

            $this.on('change', opt.selectAll, function (e)
            {
                methods.select({
                    'checked' : $(this).prop('checked')
                });
            });
        }

        if (method && methods[method]) {
            methods[method].call($this, args);
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
 * @param {object}  elm        The element to attach to
 * @param {string}  evType     The event
 * @param {object}  fn         The callback function
 * @param {boolean} useCapture Initiate capture
 */

function addEvent(elm, evType, fn, useCapture)
{
    if (elm.addEventListener) {
        elm.addEventListener(evType, fn, useCapture);
        return true;
    } else if (elm.attachEvent) {
        var r = elm.attachEvent('on' + evType, fn);
        return r;
    } else {
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
    if (days) {
        var date = new Date();

        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));

        var expires = '; expires=' + date.toGMTString();
    } else {
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

    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];

        while (c.charAt(0) == ' ') {
            c = c.substring(1, c.length);
        }

        if (c.indexOf(nameEQ) == 0) {
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

    if (node == null) {
        node = document;
    }

    var els = node.getElementsByTagName("*");

    for (var i = 0, j = els.length; i < j; i++) {
        if (re.test(els[i].className)) {
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

    if (obj.length) {
        obj.toggle();

        // Send state of toggle pane to localStorage or server.
        if ($(this).data('txp-pane')) {
            var pane = $(this).data('txp-pane');

            if (!window.localStorage && $(this).data('txp-token')) {
                sendAsyncEvent({
                    event   : 'pane',
                    step    : 'visible',
                    pane    : $(this).data('txp-pane'),
                    visible : obj.is(':visible'),
                    origin  : textpattern.event,
                    token   : $(this).data('txp-token')
                });
            }
        } else {
            var pane = obj.attr('id');

            if (!window.localStorage) {
                sendAsyncEvent({
                    event   : textpattern.event,
                    step    : 'save_pane_state',
                    pane    : obj.attr('id'),
                    visible : obj.is(':visible')
                });
            }
        }

        var data = new Object;
        data[pane] = obj.is(':visible');
        textpattern.storage.update(data);
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

    if (href) {
        toggleDisplay.call(this, href.substr(1));
    }

    if (lever.length) {
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
    $('.' + className).toggle(show);
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
    if (typeof(force) != 'undefined') {
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
    var formdata = false;
    if ($.type(data) === 'string' && data.length > 0) {
        // Got serialized data.
        data = data + '&app_mode=async&_txp_token=' + textpattern._txp_token;
    } else if (data instanceof FormData) {
        formdata = true;
        data.append("app_mode", 'async');
        data.append("_txp_token", textpattern._txp_token);
    } else {
        data.app_mode = 'async';
        data._txp_token = textpattern._txp_token;
    }

    format = format || 'xml';

    return formdata ?
        $.ajax({
            type: "POST",
            url: 'index.php',
            data: data,
            success: fn,
            dataType: format,
            processData: false,
            contentType: false
        }) :
        $.post('index.php', data, fn, format);
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
     *     function (event, data)
     *     {
     *         alert(data);
     *     }
     * );
     */

    register: function (event, fn)
    {
        $(this).on(event, fn);
        return this;
    }
};

/**
 * Textpattern localStorage.
 *
 * @since 4.6.0
 */

textpattern.storage =
{
    /**
     * Textpattern localStorage data.
     */

    data : (window.localStorage ? JSON.parse(window.localStorage.getItem("textpattern")) : null) || {},

    /**
     * Updates data.
     *
     * @param   data The message
     * @example
     * textpattern.update({prefs : "site"});
     */

    update : function (data) {

        if (!window.localStorage) {
            return;
        }

        if (data) {
            $.extend(textpattern.storage.data, data);
            window.localStorage.setItem("textpattern", JSON.stringify(textpattern.storage.data));
        }
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
     * @param  message The message
     * @return textpattern.Console
     * @example
     * textpattern.Console.log('Some message');
     */

    log : function (message)
    {
        if (textpattern.production_status === 'debug') {
            textpattern.Console.history.push(message);

            textpattern.Relay.callback('txpConsoleLog', {
                'message' : message
            });
        }

        return this;
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
    if ($.type(console) === 'object' && $.type(console.log) === 'function') {
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
     * Attaches a listener.
     *
     * @param {string} pages The page
     * @param {object} fn    The callback
     */

    add : function (pages, fn)
    {
        $.each(pages.split(','), function (index, page)
        {
            textpattern.Route.attached.push({
                'page' : $.trim(page),
                'fn'   : fn
            });
        });
    },

    /**
     * Initializes attached listeners.
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

        $.each(textpattern.Route.attached, function (index, data)
        {
            if (data.page === '' || data.page === options.event || data.page === options.event + '.' + options.step) {
                data.fn({
                    'event' : options.event,
                    'step'  : options.step,
                    'route' : data.page
                });
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
            data    : ( window.FormData === undefined ? $this.serialize() : new FormData(this) ),
            spinner : $('<span />').addClass('spinner')
        };

        // Show feedback while processing.
        $this.addClass('busy');
        $('body').addClass('busy');

        // WebKit does not set :focus on button-click: use first submit input as a fallback.
        if (!form.button.length) {
            form.button = $this.find('input[type="submit"]').eq(0);
        }

        form.button.attr('disabled', true).after(form.spinner);

        if (form.data)
            if ( form.data instanceof FormData ) {
                form.data.append(form.button.attr('name') || '_txp_submit' , form.button.val() || '_txp_submit');
            } else {
                form.data += '&' + (form.button.attr('name') || '_txp_submit') + '=' + (form.button.val() || '_txp_submit');
            }

        sendAsyncEvent(form.data, function () {}, options.dataType)
            .done(function (data, textStatus, jqXHR)
            {
                if (options.success) {
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
                if (options.error) {
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
 * @param  {string} options.dataType The response data type
 * @param  {object} options.success  The success callback
 * @param  {object} options.error    The error callback
 * @return {object} this
 * @since  4.5.0
 */

jQuery.fn.txpAsyncHref = function (options)
{
    options = $.extend({
        dataType : 'text',
        success  : null,
        error    : null
    }, options);

    this.on('click.txpAsyncHref', function (event)
    {
        event.preventDefault();
        var $this = $(this);
        var url = this.search.replace('?', '') + '&' + $.param({value : $this.text()});

        // Show feedback while processing.
        $this.addClass('busy');
        $('body').addClass('busy');

        sendAsyncEvent(url, function () {}, options.dataType)
            .done(function (data, textStatus, jqXHR)
            {
                if (options.dataType === 'text') {
                    $this.html(data);
                }

                if (options.success) {
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
                if (options.error) {
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
 * Sends a link using AJAX and processes the HTML response.
 *
 * @param  {object} options          Options
 * @param  {string} options.dataType The response data type
 * @param  {object} options.success  The success callback
 * @param  {object} options.error    The error callback
 * @return {object} this
 * @since  4.6.0
 */

function txpAsyncLink(event)
{
    event.preventDefault();
    var $this = $(event.target);
    var url = $this.attr('href').replace('?', '');

    // Show feedback while processing.
    $this.addClass('busy');
    $('body').addClass('busy');

    sendAsyncEvent(url, function () {}, 'html')
        .done(function (data, textStatus, jqXHR)
        {
            textpattern.Relay.callback('txpAsyncLink.success', {
                'this'       : $this,
                'event'      : event,
                'data'       : data,
                'textStatus' : textStatus,
                'jqXHR'      : jqXHR
            });
        })
        .fail(function (jqXHR, textStatus, errorThrown)
        {
            textpattern.Relay.callback('txpAsyncLink.error', {
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

    return this;
};

/**
 * Creates a UI dialog.
 *
 * @param  {object} options Options
 * @return {object} this
 * @since  4.6.0
 */

jQuery.fn.txpDialog = function (options)
{
    options = $.extend({
        autoOpen : false,
        buttons  : [
            {
                text  : textpattern.gTxt('ok'),
                click : function ()
                {
                    // callbacks?

                    if ($(this).is('form')) {
                        $(this).submit();
                    }

                    $(this).dialog('close');
                }
            }
        ]
    }, options);

    this.dialog(options);

    return this;
};

/**
 * Creates a date picker.
 *
 * @param  {object} options Options
 * @return {object} this
 * @since  4.6.0
 */

jQuery.fn.txpDatepicker = function (options)
{
    // TODO $.datepicker.regional[ "en" ];
    // TODO support from RTL languages
    this.datepicker(options);

    return this;
};

/**
 * Creates a sortable element.
 *
 * This method creates a sortable widget, allowing to
 * reorder elements in a list and synchronizes the updated
 * order with the server.
 *
 * @param  {object}  options
 * @param  {string}  options.dataType The response datatype
 * @param  {object}  options.success  The sync success callback
 * @param  {object}  options.error    The sync error callback
 * @param  {string}  options.event    The event
 * @param  {string}  options.step     The step
 * @param  {string}  options.cancel   Prevents sorting if you start on elements matching the selector
 * @param  {integer} options.delay    Sorting delay
 * @param  {integer} options.distance Tolerance, in pixels, for when sorting should start
 * @return this
 * @since  4.6.0
 */

jQuery.fn.txpSortable = function (options)
{
    options = $.extend({
        dataType : 'script',
        success  : null,
        error    : null,
        event    : textpattern.event,
        step     : 'sortable_save',
        cancel   : ':input, button',
        delay    : 0,
        distance : 15,
        items    : '[data-txp-sortable-id]'
    }, options);

    var methods =
    {
        /**
         * Sends updated order to the server.
         */

        update : function ()
        {
            var ids = [], $this = $(this);

            $this.children('[data-txp-sortable-id]').each(function ()
            {
                ids.push($(this).data('txp-sortable-id'));
            });

            if (ids) {
                sendAsyncEvent({
                    event : options.event,
                    step  : options.step,
                    order : ids
                }, function () {}, options.dataType)
                    .done(function (data, textStatus, jqXHR)
                    {
                        if (options.success) {
                            options.success.call($this, data, textStatus, jqXHR);
                        }

                        textpattern.Relay.callback('txpSortable.success', {
                            'this'       : $this,
                            'data'       : data,
                            'textStatus' : textStatus,
                            'jqXHR'      : jqXHR
                        });
                    })
                    .fail(function (jqXHR, textStatus, errorThrown)
                    {
                        if (options.error) {
                            options.error.call($this, jqXHR, $.ajaxSetup(), errorThrown);
                        }

                        textpattern.Relay.callback('txpSortable.error', {
                            'this'         : $this,
                            'jqXHR'        : jqXHR,
                            'ajaxSettings' : $.ajaxSetup(),
                            'thrownError'  : errorThrown
                        });
                    });
            }
        }
    };

    return this.sortable({
        cancel   : options.cancel,
        delay    : options.delay,
        distance : options.distance,
        update   : methods.update,
        items    : options.items
    });
};


/**
 * Password strength meter.
 *
 * @since 4.6.0
 * @param  {object}  options
 * @param  {array}   options.gtxt_prefix  gTxt() string prefix
 * @todo  Pass in name/email via 'options' to be injected in user_inputs[]
 */

textpattern.passwordStrength = function (options)
{
    jQuery('form').on('keyup', 'input.txp-strength-hint', function() {
        var settings = $.extend({
            'gtxt_prefix' : ''
        }, options);

        var me = jQuery(this);
        var pass = me.val();
        var passResult = zxcvbn(pass, user_inputs=[]);
        var strengthMap = {
            "0": {
                "width": "5"
            },
            "1": {
                "width": "28"
            },
            "2": {
                "width": "50"
            },
            "3": {
                "width": "75"
            },
            "4": {
                "width": "100"
            }
        };

        var offset = strengthMap[passResult.score];
        var meter = me.siblings('.strength-meter');
        meter.empty();

        if (pass.length > 0) {
            meter.append('<div class="bar"></div><div class="indicator">' + textpattern.gTxt(settings.gtxt_prefix+'password_strength_'+passResult.score) + '</div>');
        }

        meter
            .find('.bar')
            .attr('class', 'bar password-strength-'+passResult.score)
            .css('width', offset.width+'%');
    });
}

/**
 * Mask/unmask password input field.
 *
 * @since  4.6.0
 */

textpattern.passwordMask = function()
{
    $('form').on('click', '#show_password', function() {
        var inputBox = $(this).closest('form').find('input.txp-maskable');
        var newType = (inputBox.attr('type') === 'password') ? 'text' : 'password';
        textpattern.changeType(inputBox, newType);
    });
}

/**
 * Change the type of an input element.
 *
 * @param  {object} elem The <input/> element
 * @param  {string} type The desired type
 *
 * @see    https://gist.github.com/3559343 for original
 * @since  4.6.0
 */

textpattern.changeType = function(elem, type)
{
    if (elem.prop('type') === type) {
        // Already the correct type.
        return elem;
    }

    try {
        // May fail if browser prevents it.
        return elem.prop('type', type);
    } catch(e) {
        // Create the element by hand.
        // Clone it via a div (jQuery has no html() method for an element).
        var html = $("<div>").append(elem.clone()).html();

        // Match existing attributes of type=text or type="text".
        var regex = /type=(\")?([^\"\s]+)(\")?/;

        // If no match, add the type attribute to the end; otherwise, replace it.
        var tmp = $(html.match(regex) == null ?
            html.replace(">", ' type="' + type + '">') :
            html.replace(regex, 'type="' + type + '"'));

        // Copy data from old element.
        tmp.data('type', elem.data('type'));
        var events = elem.data('events');
        var cb = function(events) {
            return function() {
                // Re-bind all prior events.
                for(var idx in events) {
                    var ydx = events[idx];

                    for(var jdx in ydx) {
                        tmp.bind(idx, ydx[jdx].handler);
                    }
                }
            }
        }(events);

        elem.replaceWith(tmp);

        // Wait a smidge before firing callback.
        setTimeout(cb, 10);

        return tmp;
    }
}

/**
 * Encodes a string for a use in HTML.
 *
 * @param  {string} string The string
 * @return {string} Encoded string
 * @since  4.6.0
 */

textpattern.encodeHTML = function (string)
{
    return $('<div/>').text(string).html();
};

/**
 * Translates given substrings.
 *
 * @param  {string} string       The string being translated
 * @param  {object} replacements Translated substrings
 * @return string   Translated string
 * @since  4.6.0
 * @example
 * textpattern.tr('hello world, and bye!', {'hello' : 'bye', 'bye' : 'hello'});
 */

textpattern.tr = function (string, replacements)
{
    var match, position, output = '', replacement;

    for (position = 0; position < string.length; position++) {
        match = false;

        $.each(replacements, function (from, to)
        {
            if (string.substr(position, from.length) === from) {
                match = true;
                replacement = to;
                position = (position + from.length) - 1;

                return;
            }
        });

        if (match) {
            output += replacement;
        } else {
            output += string.charAt(position);
        }
    }

    return output;
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

    if ($.type(textpattern.textarray[name]) !== 'undefined') {
        string = textpattern.textarray[name];
    }

    if (escape !== false) {
        string = textpattern.encodeHTML(string);

        $.each(tags, function (key, value)
        {
            tags[key] = textpattern.encodeHTML(value);
        });
    }

    string = textpattern.tr(string, tags);

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
        'tags'   : tags,
        'escape' : escape
    }, opts);

    this.html(textpattern.gTxt(options.string, options.tags, options.escape));

    return this;
};

/**
 * ESC button closes alert messages.
 *
 * @since 4.5.0
 */

$(document).keyup(function (e)
{
    if (e.keyCode == 27) {
        $('.close').parent().remove();
    }
});

/**
 * Search tool.
 *
 * @since 4.6.0
 */

function txp_search()
{
    var $ui = $('.txp-search');

    $ui.find('.txp-search-button').button({
        showLabel: false,
        icon: 'ui-icon-search'
    }).click(function ()
    {
        $ui.submit();
    });

    $ui.find('.txp-search-options').button({
        showLabel: false,
        icon: 'ui-icon-triangle-1-s'
    }).on('click', function (e)
    {
        if (langdir === 'rtl') {
            var menu = $ui.find('.txp-dropdown').toggle().position(
            {
                my: "left top",
                at: "left bottom",
                of: this
            });
        } else {
            var menu = $ui.find('.txp-dropdown').toggle().position(
            {
                my: "right top",
                at: "right bottom",
                of: this
            });
        };

        $(document).one('click blur', function ()
        {
            menu.hide();
        });

        return false;
    });

    $ui.find('.txp-search-buttons').controlgroup();
    $ui.find('.txp-dropdown').hide().menu().click(function (e) {
        e.stopPropagation();
    });

    $ui.txpMultiEditForm({
        'checkbox'    : 'input[name="search_method[]"][type=checkbox]',
        'row'         : '.txp-dropdown li',
        'highlighted' : '.txp-dropdown li',
        'confirmation': false
    });
}

/**
 * Set expanded/collapsed nature of all twisty boxes in a panel.
 *
 * The direction can either be 'expand' or 'collapse', passed
 * in as an argument to the handler.
 *
 * @param  {event} ev Event that triggered the function
 * @since  4.6.0
 */

function txp_expand_collapse_all(ev) {
    ev.preventDefault();

    var direction = ev.data.direction,
        container = ev.data.container || (ev.delegateTarget == ev.target ? 'body' : ev.delegateTarget);

    $(container).find('.txp-summary a').each(function (i, elm) {
        var $elm = $(elm);

        if (direction === 'collapse') {
            if ($elm.parent(".txp-summary").hasClass("expanded")) {
                $elm.click();
            }
        } else {
            if (!$elm.parent(".txp-summary").hasClass("expanded")) {
                $elm.click();
            }
        }
    });
}

/**
 * Restore sub-panel twistys to their as-stored state.
 *
 * @return {[type]} [description]
 */
jQuery.fn.restorePanes = function ()
{
    // Initialize dynamic WAI-ARIA attributes.
    $(this).find('.txp-summary a').each(function (i, elm)
    {
        // Get id of toggled <section> region.
        var $elm = $(elm), region = $elm.attr('href');

        if (region) {

            var $region = $(region);
            region = region.substr(1);

            var pane = $elm.data("txp-pane");

            if (pane === undefined) {
                pane = region;
            }

            if (textpattern.storage.data[pane] !== undefined) {
                if (textpattern.storage.data[pane]) {
                    $elm.parent(".txp-summary").addClass("expanded");
                    $region.show();
                } else {
                    $elm.parent(".txp-summary").removeClass("expanded");
                    $region.hide();
                }
            }

            var vis = $region.is(':visible').toString();
            $elm.attr('aria-controls', region).attr('aria-pressed', vis);
            $region.attr('aria-expanded', vis);
        }
    });
}

/**
 * Cookie status.
 *
 * @deprecated in 4.6.0
 */

var cookieEnabled = true;

// Setup panel.

textpattern.Route.add('setup', function ()
{
    textpattern.passwordMask();
    textpattern.passwordStrength({
        'gtxt_prefix' : 'setup_'
    });
});

// Login panel.

textpattern.Route.add('login', function ()
{
    // Check cookies.
    if (!checkCookies()) {
        cookieEnabled = false;
        $('main').prepend($('<p class="alert-block warning" />').text(textpattern.gTxt('cookies_must_be_enabled')));
    }

    // Focus on either username or password when empty.
    $('#login_form input').each(function() {
        if (this.value === '') {
            this.focus();
            return false;
        }
    });

    textpattern.passwordMask();
    textpattern.passwordStrength();
});

// Write panel.

textpattern.Route.add('article', function ()
{
    // Assume users would not change the timestamp if they wanted to
    // 'publish now'/'reset time'.
    $(document).on('change',
        '#write-timestamp input.year,' +
        '#write-timestamp input.month,' +
        '#write-timestamp input.day,' +
        '#write-timestamp input.hour,' +
        '#write-timestamp input.minute,' +
        '#write-timestamp input.second',
        function ()
        {
            $('#publish_now').prop('checked', false);
            $('#reset_time').prop('checked', false);
        }
    );

    var status = $('select[name=Status]'), form = status.parents('form'), submitButton = form.find('input[type=submit]');

    status.change(function ()
    {
        if (!form.hasClass('published')) {
            if ($(this).val() < 4) {
                submitButton.val(textpattern.gTxt('save'));
            } else {
                submitButton.val(textpattern.gTxt('publish'));
            }
        }
    });

    $('.txp-actions').on('click', '.txp-clone', function (e)
    {
        e.preventDefault();
        form.append('<input type="hidden" name="copy" value="1" />'+
            '<input type="hidden" name="publish" value="1" />');
        form.off('submit.txpAsyncForm').trigger('submit');
    });

    // Switch to Text/HTML/Preview mode.
    $(document).on('click',
        '[data-view-mode]',
        function (e)
        {
            e.preventDefault();
            $('input[name="view"]').val($(this).data('view-mode'));
            document.article_form.submit();
        }
    );
});

// Uncheck reset on timestamp change.

textpattern.Route.add('article, file', function ()
{
    $(document).on('change', '.posted input', function (e)
    {
        $('#publish_now, #reset_time').prop('checked', false);
    });
});

// 'Clone' button on Pages, Forms, Styles panels.

textpattern.Route.add('css, page, form', function ()
{
    $('.txp-clone').click(function (e)
    {
        e.preventDefault();
        var target = $(this).data('form');
        if (target) {
            $('#'+target).append('<input type="hidden" name="copy" value="1" />');
            $('.txp-save input').click();
        }
    });
});

// Tagbuilder.

textpattern.Route.add('page, form, file, image', function ()
{
    // Set up asynchronous tag builder links.
    textpattern.Relay.register('txpAsyncLink.success', function (event, data)
    {
        $('#tagbuild_links').dialog('close').html($(data['data'])).dialog('open').restorePanes();
        $('#txp-tagbuilder-output').select();
    });

    textpattern.Relay.register('txpAsyncForm.success', function (event, data)
    {
        $('#tagbuild_links').html($(data['data']));
        $('#txp-tagbuilder-output').select();
    });

    $('#tagbuild_links, .files_detail, .images_detail').on('click', '.txp-tagbuilder-link', function(ev) {
        txpAsyncLink(ev);
    });

    $('#tagbuild_links').dialog({
        dialogClass: 'txp-tagbuilder-container',
        autoOpen: false,
        focus: function(ev, ui) {
            $(ev.target).closest('.ui-dialog').focus();
        }
    });

    $('.txp-tagbuilder-dialog').on('click', function(ev) {
        ev.preventDefault();
        if ($("#tagbuild_links").dialog('isOpen')) {
            $("#tagbuild_links").dialog('close');
        } else {
            $("#tagbuild_links").dialog('open');
        }
    });

    // Set up delegated asynchronous tagbuilder form submission.
    $('#tagbuild_links').on('click', 'form.asynchtml input[type="submit"]', function(ev) {
        $(this).closest('form.asynchtml').txpAsyncForm({
            dataType: 'html',
            error: function ()
            {
                window.alert(textpattern.gTxt('form_submission_error'));
            },
            success: function()
            {
            }
        });
    });
});

// Forms panel.

textpattern.Route.add('form', function ()
{
    $('#allforms_form').txpMultiEditForm({
        'checkbox'    : 'input[name="selected_forms[]"][type=checkbox]',
        'row'         : '.switcher-list li, .form-list-name',
        'highlighted' : '.switcher-list li'
    });
});

// Admin panel.

textpattern.Route.add('admin', function ()
{
    textpattern.passwordMask();
    textpattern.passwordStrength();
});

// Plugins panel.

textpattern.Route.add('plugin', function ()
{
    textpattern.Relay.register('txpAsyncHref.success', function (event, data)
    {
        $(data['this']).closest('tr').toggleClass('active');
    });
});

// All panels?

textpattern.Route.add('', function ()
{
    // Collapse/Expand all support.
    $('#supporting_content, #tagbuild_links, #content_switcher').on('click', '.txp-collapse-all', {direction: 'collapse'}, txp_expand_collapse_all)
        .on('click', '.txp-expand-all', {direction: 'expand'}, txp_expand_collapse_all);

    // Pane states
    var prefsGroup = $('form:has(.switcher-list li a[data-txp-pane])');

    if (prefsGroup.length == 0) {
        return;
    }

    var prefTabs = prefsGroup.find('.switcher-list li');
    var $switchers = prefTabs.children('a[data-txp-pane]');
    var $section = window.location.hash ? prefsGroup.find($(window.location.hash).closest('section')) : [];

    if ($section.length) {
        selectedTab = $section.index();
    }
    else if (textpattern.storage.data[textpattern.event] !== undefined) {
        $switchers.each(function (i, elm) {
            if ($(elm).data('txp-pane') == textpattern.storage.data[textpattern.event]) {
                selectedTab = i;
                $(elm).parent().addClass('ui-tabs-active ui-state-active');
            } else {
                $(elm).parent().removeClass('ui-tabs-active ui-state-active');
            }
        });
    }

    if (selectedTab === undefined) {
        selectedTab = 0;
    }

    prefsGroup.tabs({active: selectedTab}).removeClass('ui-widget ui-widget-content ui-corner-all').addClass('ui-tabs-vertical');
    prefsGroup.find('.switcher-list').removeClass('ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all');
    prefTabs.removeClass('ui-state-default ui-corner-top');
    prefsGroup.find('.txp-prefs-group').removeClass('ui-widget-content ui-corner-bottom');

    prefTabs.on('click focus', function(ev)
    {
        var me = $(this).children('a[data-txp-pane]');

        if (!window.localStorage) sendAsyncEvent({
            event  : 'pane',
            step   : 'tabVisible',
            pane   : me.data('txp-pane'),
            origin : textpattern.event,
            token  : me.data('txp-token')
        });

        var data = new Object;
        data[textpattern.event] = me.data('txp-pane');
        textpattern.storage.update(data);
    });
});

// Initialize JavaScript.

$(document).ready(function ()
{
    // Confirmation dialogs.
    $(document).on('click.txpVerify', 'a[data-verify]', function (e)
    {
        return verify($(this).data('verify'));
    });

    $(document).on('submit.txpVerify', 'form[data-verify]', function (e)
    {
        return verify($(this).data('verify'));
    });

    // Disable spellchecking on all elements of class "code" in capable browsers.
    var c = $(".code")[0];

    if (c && "spellcheck" in c) {
        $(".code").prop("spellcheck", false);
    }

    // Enable spellcheck for all elements mentioned in textpattern.do_spellcheck.
    c = $(textpattern.do_spellcheck)[0];

    if (c && "spellcheck" in c) {
        $(textpattern.do_spellcheck).prop("spellcheck", true);
    }

    // Attach toggle behaviours.
    $(document).on('click', '.txp-summary a[class!=pophelp]', toggleDisplayHref);

    // Attach multi-edit form.
    $('.multi_edit_form').txpMultiEditForm();

    // Establish AJAX timeout from prefs.
    if ($.ajaxSetup().timeout === undefined) {
        $.ajaxSetup({timeout : textpattern.ajax_timeout});
    }

    // Set up asynchronous forms.
    $('form.async').txpAsyncForm({
        error: function ()
        {
            window.alert(textpattern.gTxt('form_submission_error'));
        }
    });

    // Set up asynchronous links.
    $('a.async:not(.script)').txpAsyncHref({
        error: function ()
        {
            window.alert(textpattern.gTxt('form_submission_error'));
        }
    });

    $('a.async.script').txpAsyncHref({
        dataType : 'script',
        error    : function ()
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

    $('body').restorePanes();

    // Hide popup elements.
    $('.txp-dropdown').hide();

    // Event handling and automation.
    $(document).on('change.txpAutoSubmit', 'form [data-submit-on="change"]', function (e)
    {
        $(this).parents('form').submit();
    });

    // Polyfills.
    // Add support for form attribute in submit buttons.
    if ($('html').hasClass('no-formattribute')) {
        $('.txp-save input[form]').click(function(e) {
            var targetForm = $(this).attr('form');
            $('form[id='+targetForm+']').submit();
        });
    }

    // Establish UI defaults.
    $('.txp-dialog').txpDialog();
    $('.txp-dialog.modal').dialog('option', 'modal', true);
    $('.txp-datepicker').txpDatepicker();
    $('.txp-sortable').txpSortable();



    // TODO: integrate jQuery UI stuff properly --------------------------------


    // Selectmenu
    $('.jquery-ui-selectmenu').selectmenu();

    // Button
    $('.jquery-ui-button').button();

    // Button set
    $('.jquery-ui-controlgroup').controlgroup();


    // TODO: end integrate jQuery UI stuff properly ----------------------------



    // Find and open associated dialogs.
    $(document).on('click.txpDialog', '[data-txp-dialog]', function (e)
    {
        $($(this).data('txp-dialog')).dialog('open');
        e.preventDefault();
    });

    // Initialize panel specific JavaScript.
    textpattern.Route.init();

    // Arm UI.
    $('body').removeClass('not-ready');
});
