/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2017 The Textpattern Development Team
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
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
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
 * Multi-edit functions.
 *
 * @param  {string|object} method Called method, or options
 * @param  {object}        opt    Options if method is a method
 * @return {object}        this
 * @since  4.5.0
 */

jQuery.fn.txpMultiEditForm = function (method, opt) {
    var args = {};

    var defaults = {
        'checkbox'     : 'input[name="selected[]"][type=checkbox]',
        'row'          : 'tbody td',
        'highlighted'  : 'tr',
        'filteredClass': 'filtered',
        'selectedClass': 'selected',
        'actions'      : 'select[name=edit_method]',
        'submitButton' : '.multi-edit input[type=submit]',
        'selectAll'    : 'input[name=select_all][type=checkbox]',
        'rowClick'     : true,
        'altClick'     : true,
        'confirmation' : textpattern.gTxt('are_you_sure')
    };

    if ($.type(method) !== 'string') {
        opt = method;
        method = null;
    } else {
        args = opt;
    }

    this./*closest('form').*/each(function () {
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

        methods.addOption = function (options) {
            var settings = $.extend({
                'label': null,
                'value': null,
                'html' : null
            }, options);

            if (!settings.value) {
                return methods;
            }

            var option = form.editMethod.find('option').filter(function () {
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

        methods.select = function (options) {
            var settings = $.extend({
                'index'  : null,
                'range'  : null,
                'value'  : null,
                'checked': true
            }, options);

            var obj = $this.find(form.boxes);

            if (settings.value !== null) {
                obj = obj.filter(function () {
                    return $.inArray($(this).val(), settings.value) !== -1;
                });
            } else if (settings.index !== null) {
                obj = obj.filter(function (index) {
                    return $.inArray(index, settings.index) !== -1;
                });
            } else if (settings.range !== null) {
                obj = obj.slice(settings.range[0], settings.range[1]);
            }

            obj.filter(settings.checked ? ':not(:checked)' : ':checked').prop('checked', settings.checked).change();

            return methods;
        };

        /**
         * Highlights selected rows.
         *
         * @return {object} lib
         */

        lib.highlight = function () {
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

        lib.extendedClick = function () {
            if (opt.rowClick) {
                var selector = opt.row;
            } else {
                var selector = form.boxes;
            }

            $this.on('click', selector, function (e) {
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
                        'range'  : [Math.min(start, end), Math.max(start, end) + 1],
                        'checked': !checked
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

        lib.checked = function () {
            $this.on('change', form.boxes, function (e) {
                var box = $(this);
                var boxes = $this.find(form.boxes);

                if (box.prop('checked')) {
                    $(this).closest(opt.highlighted).addClass(opt.selectedClass);

                    if (-1 == $.inArray(box.val(), textpattern.Relay.data.selected)) { 
                        textpattern.Relay.data.selected.push(box.val())
                    }
                } else {
                    $(this).closest(opt.highlighted).removeClass(opt.selectedClass);

                    textpattern.Relay.data.selected = $.grep(textpattern.Relay.data.selected, function(value) {
                        return value != box.val();
                    });
                }

                if (typeof(e.originalEvent) != 'undefined') {
                    form.selectAll.prop('checked', box.prop('checked') && boxes.filter(':checked').length === boxes.length).change();
                }
            });

            return lib;
        };

        /**
         * Handles edit method selecting.
         *
         * @return {object} lib
         */

        lib.changeMethod = function () {
            form.button.hide();

            form.editMethod.val('').change(function (e) {
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

        lib.sendForm = function () {
            $this.submit(function () {
                if (opt.confirmation !== false && verify(opt.confirmation) === false) {
                    form.editMethod.val('').change();

                    return false;
                }
            });

            return lib;
        };

        if (!$this.data('_txpMultiEdit')) {
            lib.highlight().extendedClick().checked().changeMethod().sendForm();

            (function () {
                var multiOptions = $this.find('.multi-option:not(.multi-step)');

                form.editMethod.find('option[value!=""]').each(function () {
                    var value = $(this).val();

                    var option = multiOptions.filter(function () {
                        return $(this).data('multi-option') === value;
                    });

                    if (option.length > 0) {
                        methods.addOption({
                            'label': null,
                            'html' : option.eq(0).contents(),
                            'value': $(this).val()
                        });
                    }
                });

                multiOptions.remove();
            })();

            form.selectAll.on('change', function (e) {
                if (typeof(e.originalEvent) != 'undefined') {
                    methods.select({
                        'checked': $(this).prop('checked')
                    });
                }

                $this.toggleClass(opt.filteredClass, !$(this).prop('checked'));
            }).change();
        }

        if (method && methods[method]) {
            methods[method].call($this, args);
        }

        $this.data('_txpMultiEdit', form);
    });

    return this;
};

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
 * Toggles column's visibility and saves the state.
 *
 * @param  {string}  sel The column selector object
 * @return {boolean} Returns FALSE
 * @since  4.7.0
 */

function toggleColumn(sel, $sel, vis)
{
//    $sel = $(sel);
    if ($sel.length) {
        if (!!vis) {
            $sel.show();
        } else {
            $sel.hide();
        }

        // Send state of toggle pane to localStorage.
        var data = new Object;

        data[textpattern.event] = {'columns':{}};
        data[textpattern.event]['columns'][sel] = !!vis ? null : false;
        textpattern.storage.update(data);
    }

    return false;
}

/**
 * Toggles panel's visibility and saves the state.
 *
 * @param  {string}  id The element ID
 * @return {boolean} Returns FALSE
 */

function toggleDisplay(id)
{
    var obj = $('#' + id);

    if (obj.length) {
        obj.toggle();

        // Send state of toggle pane to localStorage.
        var pane = $(this).data('txp-pane') || obj.attr('id');
        var data = new Object;

        data[textpattern.event] = {'panes':{}};
        data[textpattern.event]['panes'][pane] = obj.is(':visible') ? true : null;
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

function sendAsyncEvent(data, fn, format)
{
    var formdata = false;
    format = format.split('.')
    $.merge(format, ['async'])

    if ($.type(data) === 'string' && data.length > 0) {
        // Got serialized data.
        data = data + '&app_mode='+format[1]+'&_txp_token=' + textpattern._txp_token;
    } else if (data instanceof FormData) {
        formdata = true;
        data.append("app_mode", format[1]);
        data.append("_txp_token", textpattern._txp_token);
    } else {
        data.app_mode = format[1];
        data._txp_token = textpattern._txp_token;
    }

    format = format[0] || 'xml';

    return formdata ?
        $.ajax({
            type: "POST",
            url: 'index.php',
            data: data,
            success: fn,
            dataType: format,
            processData: false,
            contentType: false,
            xhr: function () {
                var xhr = $.ajaxSettings.xhr();
                // For uploads
                xhr.upload.onprogress = function (e) {
                    if (e.lengthComputable) {
                        textpattern.Relay.callback('uploadProgress', e)
                    }
                };
                xhr.upload.onloadstart = function (e) {
                    textpattern.Relay.callback('uploadStart', e)
                };
                xhr.upload.onloadend = function (e) {
                    textpattern.Relay.callback('uploadEnd', e)
                };

                return xhr;
            }
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
     * textpattern.Relay.callback('newEvent', {'name1': 'value1', 'name2': 'value2'});
     */

    callback: function (event, data, timeout) {
        clearTimeout(textpattern.Relay.timeouts[event])

        timeout = !timeout ? 0 : parseInt(timeout, 10) 
        if (!timeout || isNaN(timeout)) {
            return $(this).trigger(event, data)
        }

        textpattern.Relay.timeouts[event] = setTimeout(
            $.proxy(function() {
                return textpattern.Relay.callback(event, data)
            }, this),
            parseInt(timeout, 10)
        )
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

    register: function (event, fn) {
        if (fn) {
            $(this).on(event, fn);
        } else {
            $(this).off(event);
        }

        return this;
    },

    timeouts: {},
    data: {selected: []}
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

    data: (typeof(Storage) === 'undefined' ? null : JSON.parse(window.localStorage.getItem("textpattern." + textpattern._txp_uid))) || {},

    /**
     * Updates data.
     *
     * @param   data The message
     * @example
     * textpattern.update({prefs: "site"});
     */

    update: function (data) {
        $.extend(true, textpattern.storage.data, data);
        textpattern.storage.clean(textpattern.storage.data);

        if (typeof(Storage) !== 'undefined') {
            window.localStorage.setItem("textpattern." + textpattern._txp_uid, JSON.stringify(textpattern.storage.data));
        }
    },

    clean: function (obj) {
        Object.keys(obj).forEach(function (key) {
            if (obj[key] && typeof obj[key] === 'object') {
                textpattern.storage.clean(obj[key]);
            } else if (obj[key] === null) {
                delete obj[key];
            }
        });
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

    history: [],

    /**
     * Stores an array of messages to announce.
     */

    messages: {},

    /**
     * Add message to announce.
     *
     * @param  {string} The event
     * @param  {string} The message
     * @return textpattern.Console
     */

    addMessage: function (message, event) {
        event = event || textpattern.event

        if (typeof textpattern.Console.messages[event] === 'undefined') {
            textpattern.Console.messages[event] = []
        }

        textpattern.Console.messages[event].push(message)

        return this
    },

    /**
     * Announce.
     *
     * @param  {string} The event
     * @return textpattern.Console
     */

    announce: function (event) {
        $(document).ready(function() {
            var c = 0, message = []
            event = event || textpattern.event

            if (!textpattern.Console.messages[event] || !textpattern.Console.messages[event].length) {
                return this
            }

            textpattern.Console.messages[event].forEach (function(pair) {
                var iconClass = 'ui-icon ui-icon-'+(pair[1] != 1 && pair[1] != 2 ? 'check' : 'alert')
                message.push('<span class="'+iconClass+'"></span> '+pair[0])
                c += 2*(pair[1] == 1) + 1*(pair[1] == 2)
            })

            var status = !c ? 'success' : (c == 2*textpattern.Console.messages[event].length ? 'error' : 'warning')
            textpattern.Console.messages[event] = []
            textpattern.Relay.callback('announce', {message: message, status: status})
        })

        return this
    },

    /**
     * Logs a message.
     *
     * @param  message The message
     * @return textpattern.Console
     * @example
     * textpattern.Console.log('Some message');
     */

    log: function (message) {
        if (textpattern.prefs.production_status === 'debug') {
            textpattern.Console.history.push(message);

            textpattern.Relay.callback('txpConsoleLog', {
                'message': message
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

textpattern.Relay.register('txpConsoleLog.ConsoleAPI', function (event, data) {
    if ($.type(console) === 'object' && $.type(console.log) === 'function') {
        console.log(data.message);
    }
}).register('uploadProgress', function (event, data) {
    $('progress.upload-progress').val(data.loaded / data.total)
}).register('uploadStart', function (event, data) {
    $('progress.upload-progress').val(0).show()
}).register('uploadEnd', function (event, data) {
    $('progress.upload-progress').hide()
}).register('updateList', function (event, data) {
    var list = data.list || '#txp-list-container, #messagepane',
        url = data.url || 'index.php',
        callback = data.callback || function(event) {},
        handle = function(html) {
            if (html) {
                $html = $(html)
                $.each(list.split(','), function(index, value) {
                    $(value).replaceWith($html.find(value)).remove()
                    $(value).trigger('updateList')
                })

                $html.remove()
            }

            callback(data.event)
        }
 
    $(list).addClass('disabled')
    
    if (typeof data.html == 'undefined') {
        $('<html />').load(url, data.data, function(responseText, textStatus, jqXHR) {
            handle(this)
        })
    } else {
        handle(data.html)
    }
}).register('announce', function(event, data) {
    if (data.message.length) {
        container = textpattern.prefs.messagePane || '<span class="messageflash {status}" role="alert" aria-live="assertive">{messages}<a class="close" role="button" title="{close}" aria-label="{close}" href="#close">&#215;</a></span>'
        message = textpattern.mustache(container, {messages: data.message.join('<br />'), status: data.status, close: textpattern.gTxt('close')})
        $('#messagepane').html(message)
    }
    else $('#messagepane').empty()
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

    attached: [],

    /**
     * Attaches a listener.
     *
     * @param {string} pages The page
     * @param {object} fn    The callback
     */

    add: function (pages, fn) {
        $.each(pages.split(','), function (index, page) {
            textpattern.Route.attached.push({
                'page': $.trim(page),
                'fn'  : fn
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

    init: function (options) {
        var options = $.extend({
            'event': textpattern.event,
            'step' : textpattern.step
        }, options);

        $.each(textpattern.Route.attached, function (index, data) {
            if (data.page === '' || data.page === options.event || data.page === '.' + options.step || data.page === options.event + '.' + options.step) {
                data.fn({
                    'event': options.event,
                    'step' : options.step,
                    'route': data.page
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

jQuery.fn.txpAsyncForm = function (options) {
    options = $.extend({
        dataType: 'script',
        success : null,
        error   : null
    }, options);

    // Send form data to application, process response as script.
    this.off('submit.txpAsyncForm').on('submit.txpAsyncForm', function (event, extra) {
        event.preventDefault();

        if (typeof extra === 'undefined') extra = new Object;

        var $this = $(this);
        var form =
        {
            data   : ( typeof window.FormData === 'undefined' ? $this.serialize() : new FormData(this) ),
            extra  : new Object,
            spinner: typeof extra['_txp_spinner'] !== 'undefined' ? $(extra['_txp_spinner']) : $('<span />').addClass('spinner ui-icon ui-icon-refresh')
        };

        if (typeof extra['_txp_submit'] !== 'undefined') {
            form.button = $this.find(extra['_txp_submit']).eq(0);
        } else {
            form.button = $this.find('input[type="submit"]:focus').eq(0);

            // WebKit does not set :focus on button-click: use first submit input as a fallback.
            if (!form.button.length) {
                form.button = $this.find('input[type="submit"]').eq(0);
            }
        }

        form.extra[form.button.attr('name') || '_txp_submit'] = form.button.val() || '_txp_submit';
        $.extend(true, form.extra, options.data, extra.data);
        // Show feedback while processing.
        form.button.attr('disabled', true).after(form.spinner.val(0));
        $this.addClass('busy');
        $('body').addClass('busy');

        if (form.data) {
            if ( form.data instanceof FormData ) {
                $.each(form.extra, function(key, val) {
                    form.data.append(key, val);
                });
            } else {
                $.each(form.extra, function(key, val) {
                    form.data += '&'+key+'='+val;
                });
            }
        }

        sendAsyncEvent(form.data, function () {}, options.dataType)
            .done(function (data, textStatus, jqXHR) {
                if (options.success) {
                    options.success($this, event, data, textStatus, jqXHR);
                }

                textpattern.Relay.callback('txpAsyncForm.success', {
                    'this'      : $this,
                    'event'     : event,
                    'data'      : data,
                    'textStatus': textStatus,
                    'jqXHR'     : jqXHR
                });
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                if (options.error) {
                    options.error($this, event, jqXHR, $.ajaxSetup(), errorThrown);
                }

                textpattern.Relay.callback('txpAsyncForm.error', {
                    'this'        : $this,
                    'event'       : event,
                    'jqXHR'       : jqXHR,
                    'ajaxSettings': $.ajaxSetup(),
                    'thrownError' : errorThrown
                });
            })
            .always(function () {
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

jQuery.fn.txpAsyncHref = function (options) {
    options = $.extend({
        dataType: 'text',
        success : null,
        error   : null
    }, options);

    this.on('click.txpAsyncHref', function (event) {
        event.preventDefault();
        var $this = $(this);
        var url = this.search.replace('?', '') + '&' + $.param({value: $this.text()});

        // Show feedback while processing.
        $this.addClass('busy');
        $('body').addClass('busy');

        sendAsyncEvent(url, function () {}, options.dataType)
            .done(function (data, textStatus, jqXHR) {
                if (options.dataType === 'text') {
                    $this.html(data);
                }

                if (options.success) {
                    options.success($this, event, data, textStatus, jqXHR);
                }

                textpattern.Relay.callback('txpAsyncHref.success', {
                    'this'      : $this,
                    'event'     : event,
                    'data'      : data,
                    'textStatus': textStatus,
                    'jqXHR'     : jqXHR
                });
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                if (options.error) {
                    options.error($this, event, jqXHR, $.ajaxSetup(), errorThrown);
                }

                textpattern.Relay.callback('txpAsyncHref.error', {
                    'this'        : $this,
                    'event'       : event,
                    'jqXHR'       : jqXHR,
                    'ajaxSettings': $.ajaxSetup(),
                    'thrownError' : errorThrown
                });
            })
            .always(function () {
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

function txpAsyncLink(event, txpEvent)
{
    event.preventDefault();
    var $this = $(event.target);
    if ($this.attr('href') === undefined) {
        $this = $this.parent();
    }
    var url = $this.attr('href').replace('?', '');

    // Show feedback while processing.
    $this.addClass('busy');
    $('body').addClass('busy');

    sendAsyncEvent(url, function () {}, 'html')
        .done(function (data, textStatus, jqXHR) {
            textpattern.Relay.callback('txpAsyncLink.'+txpEvent+'.success', {
                'this'      : $this,
                'event'     : event,
                'data'      : data,
                'textStatus': textStatus,
                'jqXHR'     : jqXHR
            });
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
            textpattern.Relay.callback('txpAsyncLink.'+txpEvent+'.error', {
                'this'        : $this,
                'event'       : event,
                'jqXHR'       : jqXHR,
                'ajaxSettings': $.ajaxSetup(),
                'thrownError' : errorThrown
            });
        })
        .always(function () {
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

jQuery.fn.txpDialog = function (options) {
    options = $.extend({
        autoOpen: false,
        buttons : [
            {
                text : textpattern.gTxt('ok'),
                click: function () {
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

jQuery.fn.txpDatepicker = function (options) {
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

jQuery.fn.txpSortable = function (options) {
    options = $.extend({
        dataType: 'script',
        success : null,
        error   : null,
        event   : textpattern.event,
        step    : 'sortable_save',
        cancel  : ':input, button',
        delay   : 0,
        distance: 15,
        items   : '[data-txp-sortable-id]'
    }, options);

    var methods =
    {
        /**
         * Sends updated order to the server.
         */

        update: function () {
            var ids = [], $this = $(this);

            $this.children('[data-txp-sortable-id]').each(function () {
                ids.push($(this).data('txp-sortable-id'));
            });

            if (ids) {
                sendAsyncEvent({
                    event: options.event,
                    step : options.step,
                    order: ids
                }, function () {}, options.dataType)
                    .done(function (data, textStatus, jqXHR) {
                        if (options.success) {
                            options.success.call($this, data, textStatus, jqXHR);
                        }

                        textpattern.Relay.callback('txpSortable.success', {
                            'this'      : $this,
                            'data'      : data,
                            'textStatus': textStatus,
                            'jqXHR'     : jqXHR
                        });
                    })
                    .fail(function (jqXHR, textStatus, errorThrown) {
                        if (options.error) {
                            options.error.call($this, jqXHR, $.ajaxSetup(), errorThrown);
                        }

                        textpattern.Relay.callback('txpSortable.error', {
                            'this'        : $this,
                            'jqXHR'       : jqXHR,
                            'ajaxSettings': $.ajaxSetup(),
                            'thrownError' : errorThrown
                        });
                    });
            }
        }
    };

    return this.sortable({
        cancel  : options.cancel,
        delay   : options.delay,
        distance: options.distance,
        update  : methods.update,
        items   : options.items
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

textpattern.passwordStrength = function (options) {
    jQuery('form').on('keyup', 'input.txp-strength-hint', function () {
        var settings = $.extend({
            'gtxt_prefix': ''
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
            meter.append('<div class="bar"></div><div class="indicator">' + textpattern.gTxt(settings.gtxt_prefix + 'password_strength_' + passResult.score) + '</div>');
        }

        meter
            .find('.bar')
            .attr('class', 'bar password-strength-' + passResult.score)
            .css('width', offset.width+'%');
    });
}

/**
 * Mask/unmask password input field.
 *
 * @since  4.6.0
 */

textpattern.passwordMask = function () {
    $('form').on('click', '#show_password', function () {
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

textpattern.changeType = function (elem, type) {
    if (elem.prop('type') === type) {
        // Already the correct type.
        return elem;
    }

    try {
        // May fail if browser prevents it.
        return elem.prop('type', type);
    } catch (e) {
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
        var cb = function (events) {
            return function () {
                // Re-bind all prior events.
                for (var idx in events) {
                    var ydx = events[idx];

                    for (var jdx in ydx) {
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

textpattern.encodeHTML = function (string) {
    return $('<div/>').text(string).html();
};

/**
 * Translates given substrings.
 *
 * @param  {string} string        The mustached string
 * @param  {object} replacements  Translated substrings
 * @return string   Translated string
 * @since  4.7.0
 * @example
 * textpattern.mustache('{hello} world, and {bye|thanks}!', {hello: 'bye'});
 */

textpattern.mustache = function(string, replacements)
{
    return string.replace(/\{([^\{\|\}]+)(\|[^\{\}]*)?\}/g, function(match, p1, p2) {
        return typeof replacements[p1] != 'undefined' ? replacements[p1] : (typeof p2 == 'undefined' ? match : p2.replace('|', ''))
    })
}

/**
 * Translates given substrings.
 *
 * @param  {string} string       The string being translated
 * @param  {object} replacements Translated substrings
 * @return string   Translated string
 * @since  4.6.0
 * @example
 * textpattern.tr('hello world, and bye!', {'hello': 'bye', 'bye': 'hello'});
 */

textpattern.tr = function (string, replacements) {
    var match, position, output = '', replacement;

    for (position = 0; position < string.length; position++) {
        match = false;

        $.each(replacements, function (from, to) {
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
 * textpattern.gTxt('string', {'{name}': 'example'}, true);
 */

textpattern.gTxt = function (i18n, atts, escape) {
    var tags = atts || {};
    var string = i18n;
    var name = string.toLowerCase();

    if ($.type(textpattern.textarray[name]) !== 'undefined') {
        string = textpattern.textarray[name];
    }

    if (escape !== false) {
        string = textpattern.encodeHTML(string);

        $.each(tags, function (key, value) {
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

jQuery.fn.gTxt = function (opts, tags, escape) {
    var options = $.extend({
        'string': opts,
        'tags'  : tags,
        'escape': escape
    }, opts);

    this.html(textpattern.gTxt(options.string, options.tags, options.escape));

    return this;
};

/**
 * ESC button closes alert messages.
 * CTRL+S triggers Save buttons click.
 *
 * @since 4.7.0
 */

$(document).keydown(function (e) {
    var key = e.which;

    if (key === 27) {
        $('.close').parent().toggle();
    } else if (key === 19 || ((e.metaKey || e.ctrlKey) && String.fromCharCode(key).toLowerCase() === 's'))
    {
        var obj = $('input.publish');

        if (obj.length)
        {
            e.preventDefault();
            obj.eq(0).click();
        }
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
    }).click(function (e) {
        e.preventDefault()
        $ui.submit()
    });

    $ui.find('.txp-search-options').button({
        showLabel: false,
        icon: 'ui-icon-triangle-1-s'
    }).on('click', function (e) {
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

        $(document).one('click blur', function () {
            menu.hide();
        });

        return false;
    });

    $ui.find('.txp-search-buttons').controlgroup();
    $ui.find('.txp-dropdown').hide().menu().click(function (e) {
        e.stopPropagation();
    });

    $ui.find('.txp-search-clear').click(function(e) {
        e.preventDefault()
        $ui.find('input[name="crit"]').val('')
        $ui.submit()
    })

    $ui.txpMultiEditForm({
        'checkbox'   : 'input[name="search_method[]"][type=checkbox]',
        'row'        : '.txp-dropdown li',
        'highlighted': '.txp-dropdown li',
        'confirmation': false
    });
}
/**
 * Column manipulation tool.
 *
 * @since 4.7.0
 */

var uniqueID = (function() {
   var id = 0
   return function() { return id++ }
})(); // Invoke the outer function after defining it.

jQuery.fn.txpColumnize = function ()
{
    var $table = $(this), items = [], selectAll = true, stored = true,
        $headers = $table.find('thead tr>th'), tabind = uniqueID();

    if ($table.closest('form').find('.txp-list-options').length) return this

    $headers.each(function (index) {
        var $this = $(this), $title = $this.text().trim(), $id = $this.data('col');

        if (!$title) {
            return;
        }

        if ($id == undefined) {
            if ($id = this.className.match(/\btxp-list-col-([\w\-]+)\b/)) {
                $id = $id[1];
            } else {
                return;
            }
        }

        var disabled = $this.hasClass('asc') || $this.hasClass('desc') ? ' disabled="disabled"' : '';
        var $li = $('<li><div role="menuitem"><input class="checkbox active" id="opt-col-' + index + '-' + tabind + '" data-name="list_options" checked="checked" value="' + $id + '" data-index="' + index + '" type="checkbox"' + disabled + '><label for="opt-col-' + index + '-' + tabind + '">' + $title + '</label></div></li>');
        var $target = $table.find('tr>*:nth-child(' + (index + 1) + ')');
        var me = $li.find('#opt-col-' + index + '-' + tabind).on('change', function (ev) {
            toggleColumn($id, $target, $(this).prop('checked'));
        });

        if (stored) {
            try {
                if (textpattern.storage.data[textpattern.event]['columns'][$id] == false) {
                    selectAll = false;
                    $target.hide();
                    me.prop('checked', false)
                }
            } catch (e) {
                stored = false;
            }
        }

        items.push($li);
    });

    if (!items.length) {
        return this
    }

    var $ui = $('<div class="txp-list-options"><a class="txp-list-options-button" href="#"><span class="ui-icon ui-icon-gear"></span> ' + textpattern.gTxt('list_options') + '</a></div>');
    var $menu = $('<ul class="txp-dropdown" role="menu" />');

    $menu.html($('<li><div role="menuitem"><input class="checkbox active" id="opt-col-all' + tabind + '" data-name="select_all" type="checkbox"' + (selectAll ? 'checked="checked"' : '') + '><label for="opt-col-all' + tabind + '">' + textpattern.gTxt('toggle_all_selected') + '</label></div></li>')).append(items);

    $ui.append($menu);

    $ui.find('.txp-list-options-button').on('click', function (e) {
        var dir = (langdir == 'rtl' ? 'left' : 'right');
        var menu = $ui.find('.txp-dropdown').toggle().position(
        {
            my: dir+" top",
            at: dir+" bottom",
            of: this
        });

        $(document).one('click blur', function () {
            menu.hide();
        });

        return false;
    });

    $ui.find('.txp-dropdown').hide().menu().click(function (e) {
        e.stopPropagation();
    });

    $ui.txpMultiEditForm({
        'checkbox'   : 'input:not(:disabled)[data-name="list_options"][type=checkbox]',
        'selectAll'  : 'input[data-name="select_all"][type=checkbox]',
        'row'        : '.txp-dropdown li',
        'highlighted': '.txp-dropdown li',
        'confirmation': false
    });

    $(this).closest('form').prepend($ui);
    return this
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

function txp_expand_collapse_all(ev)
{
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

jQuery.fn.restorePanes = function () {
    var $this = $(this), stored = true;
    // Initialize dynamic WAI-ARIA attributes.
    $this.find('.txp-summary a').each(function (i, elm) {
        // Get id of toggled <section> region.
        var $elm = $(elm), region = this.hash;

        if (region) {
            var $region = $this.find(region);
            region = region.substr(1);

            var pane = $elm.data("txp-pane");

            if (pane === undefined) {
                pane = region;
            }

            if (stored) {
                try {
                    if (textpattern.storage.data[textpattern.event]['panes'][pane] == true) {
                        $elm.parent(".txp-summary").addClass("expanded");
                        $region.show();
                    }
                } catch (e) {
                    stored = false;
                }
            }

            var vis = $region.is(':visible').toString();
            $elm.attr('aria-controls', region).attr('aria-pressed', vis);
            $region.attr('aria-expanded', vis);
        }
    });

    return $this;
}

/**
 * Manage file uploads.
 *
 * @since 4.7.0
 */
jQuery.fn.txpFileupload = function (options) {
    if (!jQuery.fn.fileupload) return this

    var form = this, fileInput = this.find('input[type="file"]'),
        maxChunkSize = textpattern.prefs.max_upload_size || 1000000,
        maxFileSize = textpattern.prefs.max_file_size || 1000000

    form.fileupload($.extend({
        paramName: fileInput.attr('name'),
        dataType: 'script',
        maxFileSize: maxFileSize,
        maxChunkSize: maxChunkSize,
        singleFileUploads: true,
        formData: null,
        fileInput: null,
        dropZone: null,
        replaceFileInput: false,
        add: function (e, data) {
            var file = data.files[0], uploadErrors = [];
/*            var acceptFileTypes = /^image\/(gif|jpe?g|png)$/i;
            if(data.files[0]['type'] && !acceptFileTypes.test(data.files[0]['type'])) {
                uploadErrors.push('Not an accepted file type');
            }*/
            if(file['size'] && file['size'] > maxFileSize) {
                uploadErrors.push('Filesize is too big')
                textpattern.Console.addMessage(['<strong>'+file['name']+'</strong> - '+textpattern.gTxt('upload_err_form_size'), 1], 'uploadEnd')
            }

            if(!uploadErrors.length) {
                data.submit()
            }
        },/*
        done: function (e, data) {
            console.log(data)
        },*/
        progressall: function (e, data) {
            textpattern.Relay.callback('uploadProgress', data)
        },
        start: function (e) {
            textpattern.Relay.callback('uploadStart', e)
        },
        stop: function (e) {
            textpattern.Relay.callback('uploadEnd', e)
        }
    }, options)).off('submit').submit(function (e) {
        e.preventDefault()
        
        form.fileupload('add', {
            files: fileInput.prop('files')
        })
    }).bind('fileuploadsubmit', function (e, data) {
        var formData = options.formData || []
        $.merge(formData, form.serializeArray())
        data.formData = formData;
    });
/*
    fileInput.on('change', function(e) {
        var singleFileUploads = false

        $(this.files).each(function () {
            if (this.size > maxChunkSize) {
                singleFileUploads = true
            }
        })

        form.fileupload('option', 'singleFileUploads', singleFileUploads)
    })
*/
    return this
}

jQuery.fn.txpUploadPreview = function(template) {
    if (!(template = template || textpattern.prefs.uploadPreview)) {
        return this
    }

    var form = $(this), last = form.children(':last-child'), maxSize = textpattern.prefs.max_file_size
    var createObjectURL = (window.URL || window.webkitURL || {}).createObjectURL

    form.find('input[type="reset"]').on('click', function (e) {
        last.nextAll().remove()
    })

    form.find('input[type="file"]').on('change', function (e) {
        last.nextAll().remove()
        
        $(this.files).each(function (index) {
            var preview = '', mime = this.type.split('/'), hash = typeof(md5) == 'function' ? md5(this.name) : index, status = this.size > maxSize ? 'alert' : '';

            if (createObjectURL) {
                switch (mime[0]) {
                    case 'image':
                          preview = '<img src="' + createObjectURL(this) + '" />'
                        break
                    case 'audio':
                    case 'video':
                          preview = '<'+mime[0]+' controls src="' + createObjectURL(this) + '" />'
                        break
                }
            }

            preview = textpattern.mustache(template, $.extend(this, {hash: hash, preview: preview, status: status, title: this.name.replace(/\.[^\.]*$/, '')}))
            form.append(preview)
        });
    }).change()

    return this
}


/**
 * Cookie status.
 *
 * @deprecated in 4.6.0
 */

var cookieEnabled = true;

// Setup panel.

textpattern.Route.add('setup', function () {
    textpattern.passwordMask();
    textpattern.passwordStrength({
        'gtxt_prefix': 'setup_'
    });
    $('#setup_admin_theme').prop('required',true);
    $('#setup_public_theme').prop('required',true);
});

// Login panel.

textpattern.Route.add('login', function () {
    // Check cookies.
    if (!checkCookies()) {
        cookieEnabled = false;
        $('main').prepend($('<p class="alert-block warning" />').text(textpattern.gTxt('cookies_must_be_enabled')));
    }

    // Focus on either username or password when empty.
    $('#login_form input').filter(function(){
        return !this.value;
    }).first().focus();

    textpattern.passwordMask();
    textpattern.passwordStrength();
});

// Write panel.

textpattern.Route.add('article', function () {
    // Assume users would not change the timestamp if they wanted to
    // 'publish now'/'reset time'.
    $(document).on('change',
        '#write-timestamp input.year,' +
        '#write-timestamp input.month,' +
        '#write-timestamp input.day,' +
        '#write-timestamp input.hour,' +
        '#write-timestamp input.minute,' +
        '#write-timestamp input.second',
        function () {
            $('#publish_now').prop('checked', false);
            $('#reset_time').prop('checked', false);
        }
    );

    var status = $('select[name=Status]'), form = status.parents('form'), submitButton = form.find('input[type=submit]');

    status.change(function () {
        if (!form.hasClass('published')) {
            if ($(this).val() < 4) {
                submitButton.val(textpattern.gTxt('save'));
            } else {
                submitButton.val(textpattern.gTxt('publish'));
            }
        }
    });

    $('#article_form').on('click', '.txp-clone', function (e) {
        e.preventDefault();
        form.trigger('submit', {data: {copy:1, publish:1}});
    });

    // Switch to Text/HTML/Preview mode.
    $(document).on('click',
        '[data-view-mode]',
        function (e) {
            e.preventDefault();
            $('input[name="view"]').val($(this).data('view-mode'));
            document.article_form.submit();
        }
    );

    // Handle Textfilter options.
    var $listoptions = $('.txp-textfilter-options .jquery-ui-selectmenu');

    $listoptions.on('selectmenuchange', function (e) {
        var me = $("option:selected", this)

        var wrapper = me.closest('.txp-textfilter-options');
        var thisHelp = me.data('help');
        var renderHelp = (typeof thisHelp === 'undefined') ? '' : thisHelp;

        wrapper.find('.textfilter-value').val(me.data('id'));
        wrapper.find('.textfilter-help').html(renderHelp);
    });

    $listoptions.hide().menu();
});

// TEST FILEUPLOAD ONLY!!
textpattern.Route.add('list, file, image', function () {
    if (!$('#txp-list-container').length) return

    textpattern.Relay.register('uploadStart', function(event) {
        textpattern.Relay.data.fileid = []
    }).register('uploadEnd', function(event) {
        var callback = function() {
            textpattern.Console.announce(event.type)
        }

        $(document).ready(function() {
            $.merge(textpattern.Relay.data.selected, textpattern.Relay.data.fileid)

            if (textpattern.Relay.data.fileid.length) {
                textpattern.Relay.callback('updateList', {
                    data: $('nav.prev-next form').serializeArray(),
                    list: '#txp-list-container',
                    event: event.type,
                    callback: callback
                })
            } else {
                callback()
            }
        })
    })

    $('form.upload-form.async').txpUploadPreview()
        .txpFileupload({formData: [{name: "app_mode", value: "async"}]})
})
// ENDTEST FILEUPLOAD

// Uncheck reset on timestamp change.

textpattern.Route.add('article, file', function () {
    $(document).on('change', '.posted input', function (e) {
        $('#publish_now, #reset_time').prop('checked', false);
    })
});

// 'Clone' button on Pages, Forms, Styles panels.

textpattern.Route.add('css, page, form', function () {
    $('.txp-clone').click(function (e) {
        e.preventDefault();
        var target = $(this).data('form');
        if (target) {
            var $target = $('#' + target);
            $target.append('<input type="hidden" name="copy" value="1" />');
            $target.off('submit.txpAsyncForm').trigger('submit');
        }
    });
});

// Tagbuilder.

textpattern.Route.add('page, form, file, image', function () {
    // Set up asynchronous tag builder links.
    textpattern.Relay.register('txpAsyncLink.tag.success', function (event, data) {
        $('#tagbuild_links').dialog('close').html($(data['data'])).dialog('open').restorePanes();
        $('#txp-tagbuilder-output').select();
    });

    $('#tagbuild_links, .txp-list-col-tag-build').on('click', '.txp-tagbuilder-link', function (ev) {
        txpAsyncLink(ev, 'tag');
    });

    $('#tagbuild_links').dialog({
        dialogClass: 'txp-tagbuilder-container',
        autoOpen: false,
        focus: function (ev, ui) {
            $(ev.target).closest('.ui-dialog').focus();
        }
    });

    $('.txp-tagbuilder-dialog').on('click', function (ev) {
        ev.preventDefault();
        if ($("#tagbuild_links").dialog('isOpen')) {
            $("#tagbuild_links").dialog('close');
        } else {
            $("#tagbuild_links").dialog('open');
        }
    });

    // Set up delegated asynchronous tagbuilder form submission.
    $('#tagbuild_links').on('click', 'form.asynchtml input[type="submit"]', function (ev) {
        $(this).closest('form.asynchtml').txpAsyncForm({
            dataType: 'html',
            error: function () {
                window.alert(textpattern.gTxt('form_submission_error'));
            },
            success: function ($this, event, data) {
                $('#tagbuild_links').html(data);
                $('#txp-tagbuilder-output').select();
            }
        });
    });
});

// popHelp.

textpattern.Route.add('', function () {
    if ( $('.pophelp' ).length ) {
        textpattern.Relay.register('txpAsyncLink.pophelp.success', function (event, data) {
            $(data.event.target).parent().attr("data-item", encodeURIComponent(data.data) );
            $('#pophelp_dialog').dialog('close').html(data.data).dialog('open').restorePanes();
        });

        $('.pophelp').on('click', function (ev) {
            var item = $(ev.target).parent().attr('data-item');
            if (item === undefined ) {
                txpAsyncLink(ev, 'pophelp');
            } else {
                $('#pophelp_dialog').dialog('close').html(decodeURIComponent(item)).dialog('open').restorePanes();
            }
            return false;
        });

        $('body').append('<div id="pophelp_dialog"></div>');
        $('#pophelp_dialog').dialog({
            dialogClass: 'txp-tagbuilder-container',    // FIXME: UI, need pophelp-class
            autoOpen: false,
            title: textpattern.gTxt('help'),
            focus: function (ev, ui) {
                $(ev.target).closest('.ui-dialog').focus();
            }
        });
    }
});

// Sections panel. Used for edit panel and multiedit change of page+style.
// This can probably be cleaned up / optimised.

textpattern.Route.add('section', function ()
{
    /**
     * Show/hide assets base on the selected theme.
     *
     * @param  string skin The theme name from which to show assets
     */
    function section_theme_hide(skin) {
        $('#section_page, #section_css, #multiedit_page, #multiedit_css').each(function() {
            var $options = $(this).find('option'),
                $selected = $options.filter(':selected'),
                $current = $options.filter('[data-skin="'+skin+'"]');

            $options.hide().filter('[data-skin="'+$selected.data('skin')+'"]').removeAttr("selected");
            $selected.attr('selected', 'selected');
            $selected = $current.filter('[selected]');

            if (!$selected.length) {
                $selected = $current.first();
            }

            $selected.prop('selected', true).attr('selected', 'selected');
            $current.show()
        });
    }

    $('#section_details, .multi_edit_form').on('change', '#section_skin, #multiedit_skin', function() {
        section_theme_hide($(this).val());
    });

    // Invoke the handler now to set things on initial page load.
    $('#section_skin').change();

    $('select[name=edit_method]').change(function() {
        if ($(this).val() === 'changepagestyle') {
            var theSkin = $('#multiedit_skin').val();
            section_theme_hide(theSkin);
        }
    });
});

// Forms panel.

textpattern.Route.add('form', function () {
    $('#allforms_form').txpMultiEditForm({
        'checkbox'   : 'input[name="selected_forms[]"][type=checkbox]',
        'row'        : '.switcher-list li, .form-list-name',
        'highlighted': '.switcher-list li'
    });

    textpattern.Relay.register('txpAsyncForm.success', function () {
        $('#allforms_form_sections').restorePanes();
    });
});

// Admin panel.

textpattern.Route.add('admin', function () {
    textpattern.passwordMask();
    textpattern.passwordStrength();
});

// Plugins panel.

textpattern.Route.add('plugin', function () {
    textpattern.Relay.register('txpAsyncHref.success', function (event, data) {
        $(data['this']).closest('tr').toggleClass('active');
    });
});

// Diag panel.

textpattern.Route.add('diag', function () {
    $('#diag_clear_private').change(function () {
        var diag_data = $('#diagnostics-data').val();
        if ($('#diag_clear_private').is(":checked")) {
            var regex = new RegExp($('#diagnostics-data').attr("data-txproot"), "g");
            diag_data = diag_data.replace(/^===.*\s/gm, '').replace(regex, '__TXP-ROOT');
        } else {
            diag_data = diag_data.replace(/^=== +/gm, '');
        }
        $('#diagnostics-detail').val(diag_data);
    });
    $('#diag_clear_private').change();
});

// Images edit panel.

textpattern.Route.add('image', function () {
    $('.thumbnail-swap-size').button({
        showLabel: false,
        icon: 'ui-icon-transfer-e-w'
    }).on('click', function (ev) {
        var $w = $('#width');
        var $h = $('#height');
        var width = $w.val();
        var height = $h.val();
        $w.val(height);
        $h.val(width);
    });
});

// All panels?

textpattern.Route.add('', function () {
    // Pane states
    var prefsGroup = $('form:has(.switcher-list li a[data-txp-pane])');

    if (prefsGroup.length == 0) {
        return;
    }

    var prefTabs = prefsGroup.find('.switcher-list li');
    var $switchers = prefTabs.children('a[data-txp-pane]');
    var $section = window.location.hash ? prefsGroup.find($(window.location.hash).closest('section')) : [];

    prefTabs.on('click focus', function (ev) {
        var me = $(this).children('a[data-txp-pane]');
        var data = new Object;

        data[textpattern.event] = {'tab':me.data('txp-pane')};
        textpattern.storage.update(data);
    });

    if ($section.length) {
        selectedTab = $section.index();
        $switchers.eq(selectedTab).click();
    } else if (textpattern.storage.data[textpattern.event] !== undefined && textpattern.storage.data[textpattern.event]['tab'] !== undefined) {
        $switchers.each(function (i, elm) {
            if ($(elm).data('txp-pane') == textpattern.storage.data[textpattern.event]['tab']) {
                selectedTab = i;
            }
        });
    }

    if (typeof selectedTab === 'undefined') {
        selectedTab = 0;
    }

    prefsGroup.tabs({active: selectedTab}).removeClass('ui-widget ui-widget-content ui-corner-all').addClass('ui-tabs-vertical');
    prefsGroup.find('.switcher-list').removeClass('ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all');
    prefTabs.removeClass('ui-state-default ui-corner-top');
    prefsGroup.find('.txp-prefs-group').removeClass('ui-widget-content ui-corner-bottom');
});

// Initialize JavaScript.

$(document).ready(function () {
    $('body').restorePanes();

    // Collapse/Expand all support.
    $('#supporting_content, #tagbuild_links, #content_switcher').on('click', '.txp-collapse-all', {direction: 'collapse'}, txp_expand_collapse_all)
        .on('click', '.txp-expand-all', {direction: 'expand'}, txp_expand_collapse_all);

    // Confirmation dialogs.
    $(document).on('click.txpVerify', 'a[data-verify]', function (e) {
        return verify($(this).data('verify'));
    });

    $(document).on('submit.txpVerify', 'form[data-verify]', function (e) {
        return verify($(this).data('verify'));
    });

    // Disable spellchecking on all elements of class "code" in capable browsers.
    var c = $(".code")[0];

    if (c && "spellcheck" in c) {
        $(".code").prop("spellcheck", false);
    }

    // Enable spellcheck for all elements mentioned in textpattern.prefs.do_spellcheck.
    $(textpattern.prefs.do_spellcheck).each(function(i, c) {
    if ("spellcheck" in c) {
        $(c).prop("spellcheck", true);
    }})

    // Attach toggle behaviours.
    $(document).on('click', '.txp-summary a[class!=pophelp]', toggleDisplayHref);

    // Establish AJAX timeout from prefs.
    if ($.ajaxSetup().timeout === undefined) {
        $.ajaxSetup({timeout: textpattern.ajax_timeout});
    }

    // Set up asynchronous forms.
    $('form.async').txpAsyncForm({
        error: function () {
            window.alert(textpattern.gTxt('form_submission_error'));
        }
    });

    // Set up asynchronous links.
    $('a.async:not(.script)').txpAsyncHref({
        error: function () {
            window.alert(textpattern.gTxt('form_submission_error'));
        }
    });

    $('a.async.script').txpAsyncHref({
        dataType: 'script',
        error   : function () {
            window.alert(textpattern.gTxt('form_submission_error'));
        }
    });

    // Close button on the announce pane.
    $(document).on('click', '.close', function (e) {
        e.preventDefault();
        $(this).parent().remove();
    });

    // Event handling and automation.
    $(document).on('change.txpAutoSubmit', 'form [data-submit-on="change"]', function (e) {
        $(this).parents('form').submit();
    });

    // Polyfills.
    // Add support for form attribute in submit buttons.
    if ($('html').hasClass('no-formattribute')) {
        $('.txp-save input[form]').click(function (e) {
            var targetForm = $(this).attr('form');
            $('form[id=' + targetForm + ']').submit();
        });
    }

    // Establish UI defaults.
    $('.txp-dropdown').hide();
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

    // Async lists navigation
    $('#txp-list-container').closest('main').on('submit', 'nav.prev-next form', function(e) {
        e.preventDefault();
        textpattern.Relay.callback('updateList', {data: $(this).serializeArray()})
    }).on('click', '.txp-navigation a', function(e) {
        e.preventDefault();
        textpattern.Relay.callback('updateList', {url: $(this).attr('href'), data: $('nav.prev-next form').serializeArray()})
    }).on('click', '.txp-list thead th a', function(e) {
        e.preventDefault();
        textpattern.Relay.callback('updateList', {list: '#txp-list-container', url: $(this).attr('href'), data: $('nav.prev-next form').serializeArray()})
    }).on('submit', 'form[name="longform"]', function(e) {
        e.preventDefault();
        textpattern.Relay.callback('updateList', {data: $(this).serializeArray()})
    }).on('submit', 'form.txp-search', function(e) {
        e.preventDefault()
        if ($(this).find('input[name="crit"]').val()) $(this).find('.txp-search-clear').show()
        else $(this).find('.txp-search-clear').hide()
        textpattern.Relay.callback('updateList', {data: $(this).serializeArray()})
    }).on('updateList', '#txp-list-container', function() {
        $(this).find('.multi_edit_form').txpMultiEditForm('select', {value: textpattern.Relay.data.selected}).find('table.txp-list').txpColumnize()
    })


    // Find and open associated dialogs.
    $(document).on('click.txpDialog', '[data-txp-dialog]', function (e) {
        $($(this).data('txp-dialog')).dialog('open');
        e.preventDefault();
    });

    // Attach multi-edit form.
    $('.multi_edit_form').txpMultiEditForm()
    $('table.txp-list').txpColumnize()

    // Initialize panel specific JavaScript.
    textpattern.Route.init();

    // Arm UI.
    $('.not-ready').removeClass('not-ready');
});
