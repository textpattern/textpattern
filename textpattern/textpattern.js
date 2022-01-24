/* eslint-env jquery */

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2022 The Textpattern Development Team
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

'use strict';

/**
 * Collection of client-side tools.
 */

textpattern.version = '4.8.8';

/**
 * Ascertain the page direction (LTR or RTL) as a variable.
 */

var langdir = document.documentElement.dir,
    dir = langdir === 'rtl' ? 'left' : 'right';

/**
 * Checks if HTTP cookies are enabled.
 *
 * @return {boolean}
 */

function checkCookies() {
    cookieEnabled = navigator.cookieEnabled && (document.cookie.indexOf('txp_test_cookie') >= 0 || document.cookie.indexOf('txp_login') >= 0);

    if (!cookieEnabled) {
        textpattern.Console.addMessage([textpattern.gTxt('cookies_must_be_enabled'), 1]);
    } else {
        document.cookie = 'txp_test_cookie=; Max-Age=0; SameSite=Lax';
    }
}

/**
 * Basic confirmation for potentially powerful choices (like deletion,
 * for example).
 *
 * @param  {string}  msg The message
 * @return {boolean} TRUE if user confirmed the action
 */

function verify(msg) {
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
        'checkbox': 'input[name="selected[]"][type=checkbox]',
        'row': 'tbody td',
        'highlighted': 'tr',
        'filteredClass': 'filtered',
        'selectedClass': 'selected',
        'actions': 'select[name=edit_method]',
        'submitButton': '.multi-edit input[type=submit]',
        'selectAll': 'input[name=select_all][type=checkbox]',
        'rowClick': true,
        'altClick': true,
        'confirmation': textpattern.gTxt('are_you_sure')
    };

    if ($.type(method) !== 'string') {
        opt = method;
        method = null;
    } else {
        args = opt;
    }

    this.each(function() {
        var $this = $(this),
            form = {},
            methods = {},
            lib = {};

        if ($this.data('_txpMultiEdit')) {
            form = $this.data('_txpMultiEdit');
            opt = $.extend(form.opt, opt);
        } else {
            opt = $.extend(defaults, opt);
            form.editMethod = $this.find(opt.actions);
            form.lastCheck = null;
            form.opt = opt;
            form.selectAll = $this.find(opt.selectAll);
            form.button = $this.find(opt.submitButton);
        }

        form.boxes = $this.find(opt.checkbox);

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
                'html': null
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

        methods.select = function(options) {
            var settings = $.extend({
                'index': null,
                'range': null,
                'value': null,
                'checked': true
            }, options);
            var obj = form.boxes; //$this.find(opt.checkbox);

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
            lib.highlight();

            return methods;
        };

        /**
         * Highlights selected rows.
         *
         * @return {object} lib
         */

        lib.highlight = function () {
            var checked = form.boxes.filter(':checked'),
                count = checked.length,
                option = form.editMethod.find('[value=""]');
            checked.closest(opt.highlighted).addClass(opt.selectedClass);
            form.boxes.filter(':not(:checked)').closest(opt.highlighted).removeClass(opt.selectedClass);

            option.gTxt('with_selected_option', {
                '{count}': count
            });
            form.selectAll.prop('checked', count && count === form.boxes.length).change();
            form.editMethod.prop('disabled', !count);

            if (!count) {
                form.editMethod.val('').change();
            }

            return lib;
        };

        /**
         * Extends click region to whole row.
         *
         * @return {object} lib
         */

        lib.extendedClick = function () {
            var selector = opt.rowClick ? opt.row : opt.checkbox;

            $this.on('click', selector, function (e) {
                var self = ($(e.target).is(opt.checkbox) || $(this).is(opt.checkbox));

                if (!self && (e.target != this || $(this).is('a, :input') || $(e.target).is('a, :input'))) {
                    return;
                }

                if (!self && opt.altClick && !e.altKey && !e.ctrlKey) {
                    return;
                }

                var box = $(this).closest(opt.highlighted).find(opt.checkbox);

                if (box.length < 1) {
                    return;
                }

                var checked = box.prop('checked');

                if (self) {
                    checked = !checked;
                }

                if (e.shiftKey && form.lastCheck) {
                    var boxes = form.boxes;
                    var start = boxes.index(box);
                    var end = boxes.index(form.lastCheck);

                    methods.select({
                        'range': [Math.min(start, end), Math.max(start, end) + 1],
                        'checked': !checked
                    });
                } else if (!self) {
                    box.prop('checked', !checked).change();
                }

                form.lastCheck = box;
            });

            return lib;
        };

        /**
         * Tracks row checks.
         *
         * @return {object} lib
         */

        lib.checked = function () {
            $this.on('change', opt.checkbox, function (e) {
                var box = $(this);

                if (box.prop('checked')) {
                    if (-1 == $.inArray(box.val(), textpattern.Relay.data.selected)) {
                        textpattern.Relay.data.selected.push(box.val());
                    }
                } else {
                    textpattern.Relay.data.selected = $.grep(textpattern.Relay.data.selected, function(value) {
                        return value != box.val();
                    });
                }

                if (typeof(e.originalEvent) != 'undefined') {
                    lib.highlight();
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
                            'html': option.eq(0).contents(),
                            'value': $(this).val()
                        });
                    }
                });

                multiOptions.remove();
            })();

            form.selectAll.on('change', function (e) {
                if (typeof (e.originalEvent) != 'undefined') {
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

function setCookie(name, value, days) {
    var expires = '';

    if (days) {
        var date = new Date();

        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = '; expires=' + date.toGMTString();
    }

    document.cookie = name + '=' + value + expires + '; path=/; SameSite=Lax';
}

/**
 * Gets a HTTP cookie's value.
 *
 * @param  {string} name The name
 * @return {string} The cookie
 */

function getCookie(name) {
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

function deleteCookie(name) {
    setCookie(name, '', -1);
}

/**
 * Toggles column's visibility and saves the state.
 *
 * @param  {string}  sel The column selector object
 * @return {boolean} Returns FALSE
 * @since  4.7.0
 */

function toggleColumn(sel, $sel, vis) {
    //$sel = $(sel);
    if ($sel.length) {
        $sel.toggle(!!vis);

        // Send state of toggle pane to localStorage.
        var data = new Object;

        data[textpattern.event] = {
            'columns': {}
        };

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

function toggleDisplay(id) {
    var obj = $('#' + id);

    if (obj.length) {
        obj.toggle();

        // Send state of toggle pane to localStorage.
        var pane = $(this).data('txp-pane') || obj.attr('id');
        var data = new Object;

        data[textpattern.event] = {
            'panes': {}
        };
        data[textpattern.event]['panes'][pane] = obj.is(':visible') ? true : null;
        textpattern.storage.update(data);
    }

    return false;
}

/**
 * Direct show/hide referred #segment; decorate parent lever.
 */

function toggleDisplayHref() {
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

function setClassDisplay(className, show) {
    $('.' + className).toggle(show);
}

/**
 * Toggles panel's visibility and saves the state to a HTTP cookie.
 *
 * @param {string} classname The HTML class
 */

function toggleClassRemember(className) {
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

function setClassRemember(className, force) {
    if (typeof (force) != 'undefined') {
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
 * @see    https://api.jquery.com/jQuery.post/
 */

function sendAsyncEvent(data, fn, format) {
    var formdata = false;

    if ($.type(data) === 'string' && data.length > 0) {
        // Got serialized data.
        data = data + '&app_mode=async&_txp_token=' + textpattern._txp_token;
    } else if (data instanceof FormData) {
        formdata = true;
        data.append('app_mode', 'async');
        data.append('_txp_token', textpattern._txp_token);
    } else {
        data.app_mode = 'async';
        data._txp_token = textpattern._txp_token;
    }

    format = format || 'xml';
    return formdata ? $.ajax({
        type: 'POST',
        url: 'index.php',
        data: data,
        success: fn,
        dataType: format,
        processData: false,
        contentType: false
    }) : $.post('index.php', data, fn, format);
}

/**
 * A pub/sub hub for client-side events.
 *
 * @since 4.5.0
 */

textpattern.Relay = {
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
        clearTimeout(textpattern.Relay.timeouts[event]);
        timeout = !timeout ? 0 : parseInt(timeout, 10);

        if (!timeout || isNaN(timeout)) {
            return $(this).trigger(event, data);
        }

        textpattern.Relay.timeouts[event] = setTimeout($.proxy(function() {
            return textpattern.Relay.callback(event, data);
        }, this), parseInt(timeout, 10));
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
    data: {
        selected: []
    }
};

/**
 * Textpattern localStorage.
 *
 * @since 4.6.0
 */

textpattern.storage = {
    /**
     * Textpattern localStorage data.
     */

    data: (!navigator.cookieEnabled || !window.localStorage ? null : JSON.parse(window.localStorage.getItem('textpattern.' + textpattern._txp_uid))) || {},

    /**
     * Updates data.
     *
     * @param   data The message
     * @example
     * textpattern.update({prefs: 'site'});
     */

    update: function (data) {
        $.extend(true, textpattern.storage.data, data);
        textpattern.storage.clean(textpattern.storage.data);

        if (navigator.cookieEnabled && window.localStorage) {
            window.localStorage.setItem('textpattern.' + textpattern._txp_uid, JSON.stringify(textpattern.storage.data));
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

textpattern.Console = {
    /**
     * Stores an array of invoked messages.
     */

    history: [],

    /**
     * Stores an array of messages to announce.
     */

    messages: {},
    queue: {},

    /**
     * Clear.
     *
     * @param  {string} The event
     * @return textpattern.Console
     */

    clear: function (event, reset) {
        event = event || textpattern.event;
        textpattern.Console.messages[event] = [];

        if (!!reset) {
            textpattern.Console.queue[event] = false;
        }

        return this;
    },

    /**
     * Add message to announce.
     *
     * @param  {string} The event
     * @param  {string} The message
     * @return textpattern.Console
     */

    addMessage: function (message, event) {
        event = event || textpattern.event;

        if (typeof textpattern.Console.messages[event] === 'undefined') {
            textpattern.Console.messages[event] = [];
        }

        textpattern.Console.messages[event].push(message);

        return this;
    },

    /**
     * Announce.
     *
     * @param  {string} The event
     * @return textpattern.Console
     */

    announce: function (event, options) {
        event = event || textpattern.event;

        if (textpattern.Console.queue[event]) {
            return this;
        } else {
            textpattern.Console.queue[event] = true;
        }

        $(function () {
            var c = 0,
                message = [],
                status = 0;

            if (textpattern.Console.messages[event] && textpattern.Console.messages[event].length) {
                var container = textpattern.prefs.message || '{message}';
                textpattern.Console.messages[event].forEach(function (pair) {
                    message.push(textpattern.mustache(container, {
                        status: pair[1] != 1 && pair[1] != 2 ? 'check' : 'alert',
                        message: pair[0]
                    }));
                    c += 2 * (pair[1] == 1) + 1 * (pair[1] == 2);
                });
                status = !c ? 'success' : (c == 2 * textpattern.Console.messages[event].length ? 'error' : 'warning');
            }

            textpattern.Relay.callback('announce', {
                event: event,
                message: message,
                status: status
            });
            textpattern.Console.clear(event, true);
        });

        return this;
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
    $('progress.txp-upload-progress').val(data.loaded / data.total);
}).register('uploadStart', function (event, data) {
    $('progress.txp-upload-progress').val(0).show();
}).register('uploadEnd', function (event, data) {
    $('progress.txp-upload-progress').hide();
}).register('updateList', function (event, data) {
    var list = data.list || '#messagepane, .txp-async-update',
        url = data.url || 'index.php',
        callback = data.callback || function(event) {
            textpattern.Console.announce(event);
        },
        handle = function(html) {
            if (html) {
                var $html = $(html);

                $.each(list.split(','), function(index, value) {
                    $(value).each(function() {
                        var id = this.id;

                        if (id) {
                            $(this).replaceWith($html.find('#' + id)).remove();
                            $('#' + id).trigger('updateList');
                        }
                    });
                });

                $html.remove();
            }

            callback(data.event);
        };

    $(list).addClass('disabled');

    if (typeof data.html == 'undefined') {
        $('<html />').load(url, data.data, function(responseText, textStatus, jqXHR) {
            handle(this);
        });
    } else {
        handle(data.html);
    }
}).register('announce', function(event, data) {
    var container = textpattern.prefs.messagePane || '',
        message = container && data.message.length ? textpattern.mustache(container, {
            message: data.message.join('<br />'),
            status: data.status,
            close: textpattern.gTxt('close')
        }) : '';

    if (message) {
        $('#messagepane').html(message);
    }
});

/**
 * Script routing.
 *
 * @since 4.6.0
 */

textpattern.Route = {
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
                'fn': fn
            });
        });

        return this;
    },

    /**
     * Initializes attached listeners.
     *
     * @param {object} options       Options
     * @param {string} options.event The event
     * @param {string} options.step  The step
     */

    init: function (options) {
        var custom = !!options;
        options = $.extend({
            'event': textpattern.event,
            'step': textpattern.step
        }, options);
        textpattern.Route.attached = textpattern.Route.attached.filter(function(elt) {
            return !!elt;
        });
        textpattern.Route.attached.forEach(function (data, index) {
            if (!custom && data.page === '' || data.page === options.event || data.page === '.' + options.step || data.page === options.event + '.' + options.step) {
                data.fn({
                    'event': options.event,
                    'step': options.step,
                    'route': data.page
                });
                delete (textpattern.Route.attached[index]);
            }
        });

        return this;
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
        success: null,
        error: null
    }, options);

    // Send form data to application, process response as script.
    this.off('submit.txpAsyncForm').on('submit.txpAsyncForm', function (event, extra) {
        event.preventDefault();

        if (typeof extra === 'undefined') {
            extra = new Object;
        }

        var $this = $(this);
        var $inputs = $('input[type="file"]:not([disabled])', $this); // Safari workaround?

        $inputs.each(function (i, input) {
            if (input.files.length > 0) return;
            $(input).prop('disabled', true);
        });

        var form = {
            data: typeof extra.form !== 'undefined' ? extra.form : (typeof window.FormData === 'undefined' ? $this.serialize() : new FormData(this)),
            extra: new Object,
            spinner: typeof extra['_txp_spinner'] !== 'undefined' ? $(extra['_txp_spinner']) : $('<span />').addClass('spinner ui-icon ui-icon-refresh')
        };

        $inputs.prop('disabled', false); // Safari workaround.

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
            if (form.data instanceof FormData) {
                $.each(form.extra, function(key, val) {
                    form.data.append(key, val);
                });
            } else {
                $.each(form.extra, function(key, val) {
                    form.data += '&' + key + '=' + val;
                });
            }
        }

        sendAsyncEvent(form.data, function () {}, options.dataType).done(function (data, textStatus, jqXHR) {
            if (options.success) {
                options.success($this, event, data, textStatus, jqXHR);
            }

            textpattern.Relay.callback('txpAsyncForm.success', {
                'this': $this,
                'event': event,
                'data': data,
                'textStatus': textStatus,
                'jqXHR': jqXHR
            });
        }).fail(function (jqXHR, textStatus, errorThrown) {
            if (options.error) {
                options.error($this, event, jqXHR, $.ajaxSetup(), errorThrown);
            }

            textpattern.Relay.callback('txpAsyncForm.error', {
                'this': $this,
                'event': event,
                'jqXHR': jqXHR,
                'ajaxSettings': $.ajaxSetup(),
                'thrownError': errorThrown
            });
        }).always(function () {
            $this.removeClass('busy');
            form.button.removeAttr('disabled');
            form.spinner.remove();
            $('body').removeClass('busy');
            textpattern.Console.announce();
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

jQuery.fn.txpAsyncHref = function (options, selector) {
    options = $.extend({
        dataType: 'text',
        success: null,
        error: null
    }, options);

    selector = !!selector ? selector : null;

    this.on('click.txpAsyncHref', selector, function (event) {
        event.preventDefault();
        var $this = $(this);
        var url = this.search.replace('?', '') + '&' + $.param({
            value: $this.text()
        });

        // Show feedback while processing.
        $this.addClass('busy');
        $('body').addClass('busy');
        sendAsyncEvent(url, function () {}, options.dataType).done(function (data, textStatus, jqXHR) {
            if (options.dataType === 'text') {
                $this.html(data);
            }

            if (options.success) {
                options.success($this, event, data, textStatus, jqXHR);
            }

            textpattern.Relay.callback('txpAsyncHref.success', {
                'this': $this,
                'event': event,
                'data': data,
                'textStatus': textStatus,
                'jqXHR': jqXHR
            });
        }).fail(function (jqXHR, textStatus, errorThrown) {
            if (options.error) {
                options.error($this, event, jqXHR, $.ajaxSetup(), errorThrown);
            }

            textpattern.Relay.callback('txpAsyncHref.error', {
                'this': $this,
                'event': event,
                'jqXHR': jqXHR,
                'ajaxSettings': $.ajaxSetup(),
                'thrownError': errorThrown
            });
        }).always(function() {
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

function txpAsyncLink(event, txpEvent) {
    event.preventDefault();
    var $this = $(event.target);

    if ($this.attr('href') === undefined) {
        $this = $this.parent();
    }

    var url = $this.attr('href').replace('?', '');

    // Show feedback while processing.
    $this.addClass('busy');
    $('body').addClass('busy');
    sendAsyncEvent(url, function () {}, 'html').done(function (data, textStatus, jqXHR) {
        textpattern.Relay.callback('txpAsyncLink.' + txpEvent + '.success', {
            'this': $this,
            'event': event,
            'data': data,
            'textStatus': textStatus,
            'jqXHR': jqXHR
        });
    }).fail(function (jqXHR, textStatus, errorThrown) {
        textpattern.Relay.callback('txpAsyncLink.' + txpEvent + '.error', {
            'this': $this,
            'event': event,
            'jqXHR': jqXHR,
            'ajaxSettings': $.ajaxSetup(),
            'thrownError': errorThrown
        });
    }).always(function () {
        $this.removeClass('busy');
        $('body').removeClass('busy');
    });

    return this;
}

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
        buttons: [{
            text: textpattern.gTxt('ok'),
            click: function () {
                // callbacks?

                if ($(this).is('form')) {
                    $(this).submit();
                }

                $(this).dialog('close');
            }
        }],
        width: 440
    }, options, $(this).data());

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
    // TODO $.datepicker.regional[ 'en' ];
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
        success: null,
        error: null,
        event: textpattern.event,
        step: 'sortable_save',
        cancel: ':input, button',
        delay: 0,
        distance: 15,
        items: '[data-txp-sortable-id]'
    }, options);
    var methods = {
        /**
         * Sends updated order to the server.
         */

        update: function () {
            var ids = [],
                $this = $(this);

            $this.children('[data-txp-sortable-id]').each(function () {
                ids.push($(this).data('txp-sortable-id'));
            });

            if (ids) {
                sendAsyncEvent({
                    event: options.event,
                    step: options.step,
                    order: ids
                }, function () {}, options.dataType).done(function (data, textStatus, jqXHR) {
                    if (options.success) {
                        options.success.call($this, data, textStatus, jqXHR);
                    }

                    textpattern.Relay.callback('txpSortable.success', {
                        'this': $this,
                        'data': data,
                        'textStatus': textStatus,
                        'jqXHR': jqXHR
                    });
                }).fail(function (jqXHR, textStatus, errorThrown) {
                    if (options.error) {
                        options.error.call($this, jqXHR, $.ajaxSetup(), errorThrown);
                    }

                    textpattern.Relay.callback('txpSortable.error', {
                        'this': $this,
                        'jqXHR': jqXHR,
                        'ajaxSettings': $.ajaxSetup(),
                        'thrownError': errorThrown
                    });
                });
            }
        }
    };

    return this.sortable({
        cancel: options.cancel,
        delay: options.delay,
        distance: options.distance,
        update: methods.update,
        items: options.items
    });
};

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
        $(this).attr('checked', newType === 'text' ? 'checked' : null).prop('checked', newType === 'text');
    }).find('#show_password').prop('checked', false);
};

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
        var html = $('<div>').append(elem.clone()).html();

        // Match existing attributes of type=text or type="text".
        var regex = /type=(\")?([^\"\s]+)(\")?/;

        // If no match, add the type attribute to the end; otherwise, replace it.
        var tmp = $(html.match(regex) == null ? html.replace('>', ' type="' + type + '">') : html.replace(regex, 'type="' + type + '"'));

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
            };
        }(events);

        elem.replaceWith(tmp);

        // Wait a smidge before firing callback.
        setTimeout(cb, 10);

        return tmp;
    }
};

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
 * Decodes a string as HTML.
 *
 * @param  {string} string The string
 * @return {string} Encoded string
 * @since  4.8.0
 */

textpattern.decodeHTML = function (string) {
    let div = document.createElement('template');
    div.innerHTML = string.trim();

    return div.content;
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

textpattern.mustache = function (string, replacements) {
    return string.replace(/\{([^\{\|\}]+)(\|[^\{\}]*)?\}/g, function(match, p1, p2) {
        return typeof replacements[p1] != 'undefined' ? replacements[p1] : (typeof p2 == 'undefined' ? match : p2.replace('|', ''));
    });
};

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
    var match,
        position,
        output = '',
        replacement;

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
        'tags': tags,
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

$(document).keydown (function(e) {
    var key = e.which;

    if (key === 27) {
        $('.close').parent().toggle();
    } else if (key === 19 || (!e.altKey && (e.metaKey || e.ctrlKey) && String.fromCharCode(key).toLowerCase() === 's')) {
        var obj = $('input.publish');

        if (obj.length) {
            e.preventDefault();
            obj.eq(0).closest('form').submit();
        }
    }
});

jQuery.fn.txpMenu = function (button) {
    var menu = this;

    menu.on('click focusin', function (e) {
        e.stopPropagation();
    }).menu({
        select: function(e, ui) {
            menu.menu('focus', null, ui.item);

            if (e.originalEvent.type !== 'click') {
                ui.item.find('input[type="checkbox"]').click();
            }
        }
    }).find('input[type="checkbox"]').keyup(function (e) {
        e.preventDefault();
    });
    !button || button.on('click', function (e) {
        menu.toggle().position({
            my: dir + ' top',
            at: dir + ' bottom',
            of: this
        }).focus().menu('focus', null, menu.find('.ui-menu-item:first'));

        if (menu.is(':visible')) {
            $(document).one('blur click focusin', function (e) {
                menu.hide();
            });
        }

        return false;
    }).on('focusin', function (e) {
        e.stopPropagation();
    });

    return this;
};

/**
 * Search tool.
 *
 * @since 4.6.0
 */

function txp_search() {
    var $ui = $('.txp-search'),
        button = $ui.find('.txp-search-options').button({
            showLabel: false,
            icon: 'ui-icon-triangle-1-s'
        }),
        menu = $ui.find('.txp-dropdown'),
        crit = $ui.find('input[name="crit"]');

    menu.hide().txpMenu(button);

    $ui.find('.txp-search-button').button({
        showLabel: false,
        icon: 'ui-icon-search'
    }).click(function (e) {
        e.stopPropagation();
        e.preventDefault();
        $ui.submit();
    });

    $ui.find('.txp-search-buttons').controlgroup();

    $ui.find('.txp-search-clear').click(function (e) {
        e.preventDefault();
        crit.val('');
        $ui.submit();
    });

    $ui.txpMultiEditForm({
        'checkbox': 'input[name="search_method[]"][type=checkbox]',
        'row': '.txp-dropdown li',
        'highlighted': '.txp-dropdown li',
        'confirmation': false
    });

    $ui.submit(function (e) {
        var empty = crit.val() !== '';

        if (empty) {
            menu.find('input[name="search_method[]"]').each(function () {
                empty = empty && !$(this).is(':checked');
            });
        }

        if (empty) {
            button.click();
            return false;
        }
    });
}

/**
 * Column manipulation tool.
 *
 * @since 4.7.0
 */

var uniqueID = (function () {
    var id = 0;
    return function() {
        return id++;
    };
})(); // Invoke the outer function after defining it.


jQuery.fn.txpColumnize = function () {
    var $table = $(this),
        items = [],
        selectAll = true,
        stored = true,
        $headers = $table.find('thead tr>th');

    $headers.each(function (index) {
        var $this = $(this),
            $title = $this.text().trim(),
            $id = $this.data('col');

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

        var disabled = $this.hasClass('asc') || $this.hasClass('desc');
        var $li = $('<li />').addClass(disabled ? 'ui-state-disabled' : null);
        var $box = $('<input type="checkbox" tabindex="-1" class="checkbox active" data-name="list_options" checked="checked" />')
            .attr('value', $id)
            .attr('data-index', index)
            .prop('disabled', disabled);

        $li.html($('<div role="menuitem" />').append($('<label />').text($title).prepend($box)));

        var $target = $table.find('tr>*:nth-child(' + (index + 1) + ')');
        var me = $li.find('input').on('change', function (ev) {
            toggleColumn($id, $target, $(this).prop('checked'));
        });

        if (stored) {
            try {
                if (textpattern.storage.data[textpattern.event]['columns'][$id] == false) {
                    selectAll = false;
                    $target.hide();
                    me.prop('checked', false);
                }
            } catch (e) {
                stored = false;
            }
        }

        items.push($li);
    });

    if (!items.length) {
        return this;
    }

    var $menu = $('<ul class="txp-dropdown" role="menu" />').hide(),
        $button = $('<a class="txp-list-options-button" href="#" />').text(textpattern.gTxt('list_options')).prepend('<span class="ui-icon ui-icon-gear"></span> ');
    var $li = $('<li class="txp-dropdown-toggle-all" />'),
        $box = $('<input tabindex="-1" class="checkbox active" data-name="select_all" type="checkbox" />').attr('checked', selectAll);

    $li.html($('<div role="menuitem" />').append($('<label />').html(textpattern.gTxt('toggle_all_selected')).prepend($box)));
    $menu.html($li).append(items);

    var $container = $table.closest('.txp-layout-1col');
    var $ui = $container.find('.txp-list-options');
    var $panel = $container.find('.txp-control-panel');

    if (!$ui.length) {
        $ui = $('<div class="txp-list-options"></div>');
    } else {
        $ui.find('a.txp-list-options-button, ul.txp-dropdown').remove();
        $panel = false;
    }

    $ui.append($button).append($menu);
    $menu.txpMenu($button);
    $ui.data('_txpMultiEdit', null).txpMultiEditForm({
        'checkbox': 'input:not(:disabled)[data-name="list_options"][type=checkbox]',
        'selectAll': 'input[data-name="select_all"][type=checkbox]',
        'row': '.txp-dropdown li',
        'highlighted': '.txp-dropdown li',
        'confirmation': false
    });

    if ($panel.length) {
        $panel.after($ui);
    } else if ($panel !== false) {
        $table.closest('form').prepend($ui);
    }

    return this;
};

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
            if ($elm.parent('.txp-summary').hasClass('expanded')) {
                $elm.click();
            }
        } else {
            if (!$elm.parent('.txp-summary').hasClass('expanded')) {
                $elm.click();
            }
        }
    });
}

/**
 * Restore sub-panel twisties to their as-stored state.
 *
 * @return {[type]} [description]
 */

jQuery.fn.restorePanes = function () {
    var $this = $(this),
        stored = true;

    // Initialize dynamic WAI-ARIA attributes.
    $this.find('.txp-summary a').each(function (i, elm) {
        // Get id of toggled <section> region.
        var $elm = $(elm),
            region = this.hash;

        if (region) {
            var $region = $this.find(region);

            region = region.substr(1);

            var pane = $elm.data('txp-pane');

            if (pane === undefined) {
                pane = region;
            }

            if (stored) {
                try {
                    if (textpattern.storage.data[textpattern.event]['panes'][pane] == true) {
                        $elm.parent('.txp-summary').addClass('expanded');
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
};

/**
 * Manage file uploads.
 *
 * @since 4.7.0
 */

jQuery.fn.txpFileupload = function (options) {
    if (!jQuery.fn.fileupload) {
        return this;
    }

    var form = this,
        fileInput = this.find('input[type="file"]'),
        maxChunkSize = Math.min(parseFloat(textpattern.prefs.max_upload_size || 1000000), Number.MAX_SAFE_INTEGER),
        maxFileSize = Math.min(parseFloat(textpattern.prefs.max_file_size || 1000000), Number.MAX_SAFE_INTEGER);

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
        //add: function (e, data) {
        //    form.uploadCount++;
        //    data.submit();
        //},
        progressall: function (e, data) {
            textpattern.Relay.callback('uploadProgress', data);
        },
        start: function (e) {
            textpattern.Relay.callback('uploadStart', e);
        },
        stop: function (e) {
            textpattern.Relay.callback('uploadEnd', e);
        }
    }, options)).off('submit').submit(function (e) {
        e.preventDefault();
        form.uploadCount = 0;
        var files = [];

        for (let file of fileInput.prop('files')) {
            if (file.size > maxFileSize) {
                textpattern.Console.addMessage(['<strong>' + textpattern.encodeHTML(file['name']) + '</strong> - ' + textpattern.gTxt('upload_err_form_size'), 1], 'uploadEnd');
            } else {
                form.uploadCount++;
                file['order'] = form.uploadCount;
                files.push(file);
            }
        }

        if (!files.length) {
            textpattern.Console.announce('uploadEnd');
        } else {
            form.fileupload('add', {
                files: files
            });
        }

        fileInput.val('');
    }).bind('fileuploadsubmit', function (e, data) {
        data.formData = $.merge([{
            'name': 'fileInputOrder',
            'value': data.files[0].order + '/' + form.uploadCount
        }], options.formData);

        $.merge(data.formData, form.serializeArray());

        // Reduce maxChunkSize by extra data size (?)
        var res = typeof data.formData.entries !== 'undefined'
            ? Array.from(data.formData.entries(), function (prop) {
                return prop[1].name.length + prop[1].value.length;
            }).reduce(function (a, b) {
                return a + b + 2;
            }, 0)
            : 256;

        var chunkSize = form.fileupload('option', 'maxChunkSize');

        form.fileupload('option', 'maxChunkSize', Math.min(maxChunkSize - 8 * (res + 255), chunkSize));
    });

    return this;
};

jQuery.fn.txpUploadPreview = function (template) {
    if (!(template = template || textpattern.prefs.uploadPreview)) {
        return this;
    }

    var form = $(this),
        last = form.children(':last-child'),
        maxSize = textpattern.prefs.max_file_size;
    var createObjectURL = (window.URL || window.webkitURL || {}).createObjectURL;

    form.find('input[type="reset"]').on('click', function (e) {
        last.nextAll().remove();
    });

    form.find('input[type="file"]').on('change', function (e) {
        last.nextAll().remove();
        $(this.files).each(function (index) {
            var preview = '',
                mime = this.type.split('/'),
                hash = typeof md5 == 'function' ? md5(this.name) : index,
                status = this.size > maxSize ? 'alert' : '';

            if (createObjectURL) {
                switch (mime[0]) {
                    case 'image':
                        preview = '<img src="' + createObjectURL(this) + '" />';
                        break;
                    // TODO case 'video':?
                    case 'audio':
                        preview = '<' + mime[0] + ' controls src="' + createObjectURL(this) + '" />';
                        break;
                }
            }

            preview = textpattern.mustache(template, $.extend(this, {
                hash: hash,
                preview: preview,
                status: status,
                title: textpattern.encodeHTML(this.name.replace(/\.[^\.]*$/, ''))
            }));

            form.append(preview);
        });
    }).change();

    return this;
};

/**
 * Cookie status.
 *
 * @deprecated in 4.6.0
 */

var cookieEnabled = true;

// Setup panel.
textpattern.Route.add('setup', function () {
    textpattern.passwordMask();
    $('#setup_admin_theme').prop('required', true);
    $('#setup_public_theme').prop('required', true);

    if ($('textarea[name=config]').length) {
        $('.txp-config-download').on('click', function (e) {
            var text = $('textarea[name=config]').val();
            text = 'data:text/plain;charset=utf-8,' + encodeURIComponent(text);
            var el = e.currentTarget;
            el.href = text;
            el.download = 'config.php';
        });
    }
});

// Login panel.
textpattern.Route.add('login', function () {
    // Check cookies.
    cookieEnabled = checkCookies();

    // Focus on either username or password when empty.
    $('#login_form input').filter(function () {
        return !this.value;
    }).first().focus();

    textpattern.passwordMask();
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

    textpattern.Relay.register('article.section_changed', function (event, data) {
        var $overrideForm = $('#override-form');
        var override_sel = $overrideForm.val();

        $overrideForm.empty().append('<option></option>');

        $.each(data.data, function(key, item) {
            var $option = $('<option />');
            $option.text(item).attr('dir', 'auto').prop('selected', item == override_sel);
            $overrideForm.append($option);
        });
    });

    $('#txp-write-sort-group').on('change', '#section', function () {
        if (typeof allForms !== 'undefined') {
            textpattern.Relay.callback('article.section_changed', {
                data: allForms[$(this).find(':selected').data('skin')]
            });
        }
    }).change();

    var status = 'select[name=Status]',
        form = $(status).parents('form');

    $('#article_form').on('change', status, function () {
        let submitButton = form.find('input[type=submit]');

        if (!form.hasClass('published')) {
            if ($(this).val() < 4) {
                submitButton.val(textpattern.gTxt('save'));
            } else {
                submitButton.val(textpattern.gTxt('publish'));
            }
        }
    }).on('submit.txpAsyncForm', function (e) {
        if ($pane.dialog('isOpen') && !$('#live-preview').is(':checked')) {
            $viewMode.click();
        }
    }).on('click', '.txp-clone', function (e) {
        e.preventDefault();
        form.trigger('submit', {
            data: {
                copy: 1,
                publish: 1
            }
        });
    });

    // Switch to Text/HTML/Preview mode.
    var $pane = $('#pane-view').closest('.txp-dialog'),
        $field = '',
        $viewMode = $('#view_modes li.active [data-view-mode]');

    if (!$viewMode.length) {
        $viewMode = $('#view_modes [data-view-mode]').first();
    }

    $pane.dialog({
        dialogClass: 'txp-preview-container',
        buttons: [],
        closeOnEscape: false,
        maxWidth: '100%'
    });

    $pane.on('dialogopen', function (event, ui) {
        $('#live-preview').trigger('change');
    }).on('dialogclose', function (event, ui) {
        $('#body, #excerpt').off('input', txp_article_preview);
    });

    $('#live-preview').on('change', function () {
        if ($(this).is(':checked')) {
            $('#body, #excerpt').on('input', txp_article_preview);
        } else {
            $('#body, #excerpt').off('input', txp_article_preview);
        }
    });

    textpattern.Relay.register('article.preview', function (e) {
        var data = form.serializeArray();

        data.push({
            name: 'app_mode',
            value: 'async'
        },{
            name: '_txp_token',
            value: textpattern._txp_token
        },{
            name: 'preview',
            value: $field
        },{
            name: 'view',
            value: $viewMode.data('view-mode')
        });
        textpattern.Relay.callback('updateList', {
            url: 'index.php #pane-view',
            data: data,
            list: '#pane-view',
            callback: function() {
                $pane.dialog('open');
            }
        });
    });

    $(document).on('click', '[data-view-mode]', function (e) {
        e.preventDefault();
        $viewMode = $(this);
        let $view = $viewMode.data('view-mode');
        $viewMode.closest('ul').children('li').removeClass('active').filter('#tab-' + $view).addClass('active');
        textpattern.Relay.callback('article.preview');
    }).on('click', '[data-preview-link]', function (e) {
        e.preventDefault();
        $field = $(this).data('preview-link');
        $pane.dialog('option', 'title', $(this).text());
        $viewMode.click();
    }).on('updateList', '#pane-view.html', function () {
        Prism.highlightAllUnder(this);
    });

    function txp_article_preview() {
        $field = this.id;
        textpattern.Relay.callback('article.preview', null, 1000);
    }

    // Handle Textfilter options.
    var $listoptions = $('.txp-textfilter-options .jquery-ui-selectmenu');

    $listoptions.on('selectmenuchange', function (e) {
        var me = $('option:selected', this);
        var wrapper = me.closest('.txp-textfilter-options');
        var thisHelp = me.data('help');
        var renderHelp = (typeof thisHelp === 'undefined') ? '' : thisHelp;

        wrapper.find('.textfilter-value').val(me.data('id')).trigger('change');
        wrapper.find('.textfilter-help').html(renderHelp);

        if ($pane.dialog('isOpen')) {
            wrapper.find('[data-preview-link]').click();
        }
    });

    $listoptions.hide().menu();
});

textpattern.Route.add('article.init', function () {
    $('.txp-textfilter-options .jquery-ui-selectmenu').trigger('selectmenuchange');
});

textpattern.Route.add('file, image', function () {
    if (!$('#txp-list-container').length) return;
    textpattern.Relay.register('uploadStart', function (event) {
        textpattern.Relay.data.fileid = [];
    }).register('uploadEnd', function (event) {
        var callback = function () {
            textpattern.Console.clear().announce(event.type);
        };

        $(function () {
            $.merge(textpattern.Relay.data.selected, textpattern.Relay.data.fileid);

            if (textpattern.Relay.data.fileid.length) {
                textpattern.Relay.callback('updateList', {
                    data: $('nav.prev-next form').serializeArray(),
                    list: '#txp-list-container',
                    event: event.type,
                    callback: callback
                });
            } else {
                callback();
            }
        });
    });
    $('form.upload-form.async').txpUploadPreview().txpFileupload({
        formData: [{
            name: 'app_mode',
            value: 'async'
        }]
    });
});

// Uncheck reset on timestamp change.
textpattern.Route.add('article, file', function () {
    $(document).on('change', '.posted input', function (e) {
        $('#publish_now, #reset_time').prop('checked', false);
    });
});

// 'Clone' button on Pages, Forms, Styles panels.
textpattern.Route.add('skin, css, page, form', function () {
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

    $(document).on('click', '.txp-tagbuilder-link', function (ev) {
        txpAsyncLink(ev, 'tag');
    });

    // Set up asynchronous tag builder launcher.
    textpattern.Relay.register('txpAsyncLink.tagbuilder.success', function (event, data) {
        $('#tagbuild_links').dialog('close').html($(data['data'])).dialog('open').restorePanes();
    });

    $(document).on('click', '.txp-tagbuilder-dialog', function (ev) {
        txpAsyncLink(ev, 'tagbuilder');
    });

    $('#tagbuild_links').dialog({
        dialogClass: 'txp-tagbuilder-container',
        autoOpen: false,
        focus: function (ev, ui) {
            $(ev.target).closest('.ui-dialog').focus();
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

// Pophelp.
textpattern.Route.add('', function () {
    textpattern.Relay.register('txpAsyncLink.pophelp.success', function (event, data) {
        $(data.event.target).parent().attr('data-item', encodeURIComponent(data.data));
        $('#pophelp_dialog').dialog('close').html(data.data).dialog('open');
    });
    $('body').on('click', '.pophelp', function (ev) {
        var $pophelp = $('#pophelp_dialog');

        if ($pophelp.length == 0) {
            $pophelp = $('<div id="pophelp_dialog"></div>');
            $('body').append($pophelp);
            $pophelp.dialog({
                classes: {
                    'ui-dialog': 'txp-dialog-container'
                },
                autoOpen: false,
                width: 440,
                title: textpattern.gTxt('help'),
                focus: function (ev, ui) {
                    $(ev.target).closest('.ui-dialog').focus();
                }
            });
        }

        var item = $(ev.target).parent().attr('data-item') || $(ev.target).attr('data-item');

        if (typeof item === 'undefined') {
            txpAsyncLink(ev, 'pophelp');
        } else {
            $pophelp.dialog('close').html(decodeURIComponent(item)).dialog('open');
        }

        return false;
    });
});

// Forms panel.
textpattern.Route.add('form', function () {
    $('#allforms_form').txpMultiEditForm({
        'checkbox': 'input[name="selected_forms[]"][type=checkbox]',
        'row': '.switcher-list li, .form-list-name',
        'highlighted': '.switcher-list li'
    });

    textpattern.Relay.register('txpAsyncForm.success', function () {
        $('#allforms_form').txpMultiEditForm('select', {
            value: textpattern.Relay.data.selected
        });

        $('#allforms_form_sections').restorePanes();
    });
});

// Users panel.
textpattern.Route.add('admin', function () {
    textpattern.passwordMask();
});

// Plugins panel.
textpattern.Route.add('plugin', function () {
    textpattern.Relay.register('txpAsyncHref.success', function (event, data) {
        $(data['this']).closest('tr').toggleClass('active');
    });
});

// Diagnostics panel.
textpattern.Route.add('diag', function () {
    $('#diag_clear_private').change(function () {
        var diag_data = $('#diagnostics-data').val();

        if ($('#diag_clear_private').is(':checked')) {
            var regex = new RegExp($('#diagnostics-data').attr('data-txproot'), 'g');
            diag_data = diag_data.replace(/^===.*\s/gm, '').replace(regex, '__TXP-ROOT');
        } else {
            diag_data = diag_data.replace(/^=== +/gm, '');
        }

        $('#diagnostics-detail').val(diag_data);
    });
    $('#diag_clear_private').change();
});

// Languages panel.
textpattern.Route.add('lang', function () {
    $('.txp-grid-lang').on('click', 'button', function (ev) {
        ev.preventDefault();

        var $me = $(this),
            $form = $me.closest('form');

        $form.find('input[name=step]').val($me.attr('name'));
        $(ev.delegateTarget).addClass('disabled').find('button').attr('disabled', true);
        $form.submit();
    });
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

// Sections panel. Used for edit panel and multi-edit change of page+style.
// TODO: This can probably be cleaned up/optimized.

textpattern.Route.add('section', function () {
    /**
     * Display assets based on the selected theme.
     *
     * @param  string skin The theme name from which to show assets
     */

    function section_theme_show(skin) {
        $('#section_page, #section_css, #multiedit_page, #multiedit_css, #multiedit_dev_page, #multiedit_dev_css').empty();
        var $pageSelect = $('[name=section_page], #multiedit_dev_page');
        var $styleSelect = $('[name=css], #multiedit_dev_css');

        if (skin in skin_page) {
            $pageSelect.append('<option></option>');

            $.each(skin_page[skin], function (key, item) {
                var isSelected = (item == page_sel) ? ' selected' : '';
                $pageSelect.append('<option' + isSelected + '>' + item + '</option>');
            });

            if (page_sel === null) {
                $pageSelect.append('<option selected>*</option>');
            }
        }

        if (skin in skin_style) {
            $styleSelect.append('<option></option>');

            $.each(skin_style[skin], function (key, item) {
                var isSelected = (item == style_sel) ? ' selected' : '';
                $styleSelect.append('<option' + isSelected + '>' + item + '</option>');
            });

            if (style_sel === null) {
                $styleSelect.append('<option selected>*</option>');
            }
        }
    }

    $('main').on('change', '#section_skin, #multiedit_skin, #multiedit_dev_skin', function () {
        section_theme_show($(this).val());
    }).on('change', 'select[name=edit_method]', function () {
        if ($(this).val() === 'changepagestyle') {
            $('#multiedit_skin').change();
        } else if ($(this).val() === 'changepagestyledev') {
            $('#multiedit_dev_skin').change();
        }
    });

    // Invoke the handler now to set things on initial page load.
    $('#section_skin').change();
});

// Plugin help panel.
textpattern.Route.add('plugin.plugin_help', function () {
    var $helpWrap = $(document.body).children('main');
    var $helpTxt = $helpWrap.children('.txp-layout-textbox');
    var $head = $helpTxt.children(':first');
    var $sectHeads = $helpTxt.children('h2');
    var $intro = $head.nextUntil($sectHeads);
    var sectIdPrefix = 'plugin_help_section_';

    if ($head.prop('tagName') != 'H1'
        || $intro.length && !$sectHeads.length
        || !$intro.length && $sectHeads.length < 2
        || $helpTxt.find('h1').length > 1
        || $helpTxt.find('script, style, [style], [id^="' + sectIdPrefix + '"], [id*=" ' + sectIdPrefix + '"], [class^="txp-layout"], [class*=" txp-layout"], [class^="txp-grid"], [class*=" txp-grid"]').length) {
        return;
    }

    $helpTxt.detach();

    var $sects = $();
    var tabs = '';

    if ($intro.length) {
        $intro = $intro.wrapAll('<section class="txp-tabs-vertical-group" id="' + sectIdPrefix + 'intro" aria-labelledby="intro-label" />').parent();
        $sects = $sects.add($intro);
        tabs += '<li><a data-txp-pane="intro" href="#' + sectIdPrefix + 'intro">' + textpattern.gTxt('documentation') + '</a></li>';
    }

    $sectHeads.each(function (i, sectHead) {
        var $sectHead = $(sectHead);
        var $tabHead = $sectHead.clone();

        $tabHead.find('a').each(function (i, anchor) {
            $(anchor).contents().unwrap();
        });

        // Grab the heading, strip out markup, then sanitize.
        var tabTitle = $('<div>').html($tabHead.html()).text();
        var tabName = tabTitle.replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '_').toLowerCase();
        var sectId = sectIdPrefix + tabName;

        $sects = $sects.add($sectHead.nextUntil(sectHead).addBack().wrapAll('<section class="txp-tabs-vertical-group" id="' + sectId + '" aria-labelledby="' + sectId + '-label" />').parent());
        tabs += '<li><a data-txp-pane="' + tabName + '" href="#' + sectId + '">' + tabTitle + '</a></li>';
    });

    $head.addClass('txp-heading').wrap('<div class="txp-layout-1col"></div>');
    $sects.wrapAll('<div class="txp-layout-4col-3span" />');
    $sects.parent().before('<div class="txp-layout-4col-alt"><section class="txp-details" id="all_sections" aria-labelledby="all_sections-label"><h3 id="all_sections-label">' + textpattern.gTxt('plugin_help') + '</h3><div role="group"><ul class="switcher-list">' + tabs + '</ul></div></section></div>');
    $helpTxt.wrap('<div class="txp-layout" />').contents().unwrap().parent().appendTo($helpWrap);
});

// Prefs panel.
textpattern.Route.add('prefs', function () {
    $('#dateformat, #archive_dateformat, #comments_dateformat').on('change', function() {
        $(this).next('input').val($(this).val());
    });
});

// All panels?
textpattern.Route.add('', function () {
    // Pane states
    var hasTabs = $('.txp-layout:has(.switcher-list li a[data-txp-pane])');

    if (hasTabs.length == 0) {
        return;
    }

    var tabs = hasTabs.find('.switcher-list li');
    var $switchers = tabs.children('a[data-txp-pane]');
    var $section = window.location.hash ? hasTabs.find($(window.location.hash).closest('section')) : [];
    var selectedTab = 1;

    if (textpattern.event === 'plugin') {
        var nameParam = new RegExp('[\?&]name=([^&#]*)').exec(window.location.href);
        var dataItem = nameParam[1];
    } else {
        dataItem = textpattern.event;
    }

    tabs.on('click focus', function (ev) {
        var me = $(this).children('a[data-txp-pane]');
        var data = new Object;

        data[dataItem] = {
            'tab': me.data('txp-pane')
        };
        textpattern.storage.update(data);
    });

    hasTabs.find('a:not([data-txp-pane], .pophelp)').click(function () {
        $section = hasTabs.find($(this.hash).closest('section'));

        if ($section.length) {
            selectedTab = $section.index();
            $switchers.eq(selectedTab).click();
        }
    });

    if ($section.length) {
        selectedTab = $section.index();
        $switchers.eq(selectedTab).click();
    } else if (textpattern.storage.data[dataItem] !== undefined && textpattern.storage.data[dataItem]['tab'] !== undefined) {
        $switchers.each(function (i, elm) {
            if ($(elm).data('txp-pane') == textpattern.storage.data[dataItem]['tab']) {
                selectedTab = i;
            }
        });
    } else {
        selectedTab = 0;
    }

    if (typeof selectedTab === 'undefined') {
        selectedTab = 0;
    }

    hasTabs.tabs({
        active: selectedTab
    }).removeClass('ui-widget ui-widget-content ui-corner-all').addClass('ui-tabs-vertical');
    hasTabs.find('.switcher-list').removeClass('ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all');
    tabs.removeClass('ui-state-default ui-corner-top');
    hasTabs.find('.txp-tabs-vertical-group').removeClass('ui-widget-content ui-corner-bottom');
});

// Initialize JavaScript.
$(function () {
    $('body').restorePanes();

    // Collapse/Expand all support.
    $('#supporting_content, #tagbuild_links, #content_switcher').on('click', '.txp-collapse-all', {
        direction: 'collapse'
    }, txp_expand_collapse_all).on('click', '.txp-expand-all', {
        direction: 'expand'
    }, txp_expand_collapse_all);

    // Confirmation dialogs.
    $(document).on('click.txpVerify', 'a[data-verify]', function (e) {
        return verify($(this).data('verify'));
    });

    $(document).on('submit.txpVerify', 'form[data-verify]', function (e) {
        return verify($(this).data('verify'));
    });

    // Disable spellchecking on all elements of class 'code' in capable browsers.
    var c = $('.code')[0];

    if (c && 'spellcheck' in c) {
        $('.code').prop('spellcheck', false);
    }

    // Enable spellcheck for all elements mentioned in textpattern.prefs.do_spellcheck.
    $(textpattern.prefs.do_spellcheck).each(function (i, c) {
        if ('spellcheck' in c) {
            $(c).prop('spellcheck', true);
        }
    });

    // Attach toggle behaviours.
    $(document).on('click', '.txp-summary a[class!=pophelp]', toggleDisplayHref);

    // Establish AJAX timeout from prefs.
    if ($.ajaxSetup().timeout === undefined) {
        $.ajaxSetup({
            timeout: textpattern.ajax_timeout
        });
    }

    // Set up asynchronous forms.
    $('form.async').txpAsyncForm({
        error: function () {
            window.alert(textpattern.gTxt('form_submission_error'));
        }
    });

    // Set up asynchronous links.
    $('body').txpAsyncHref($.extend({
        error: function () {
            window.alert(textpattern.gTxt('form_submission_error'));
        }
    }, $(this).hasClass('script') ? {
        dataType: 'script'
    } : {}), 'a.async');

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
    $('.jquery-ui-selectmenu').selectmenu({
        position: {
            my: dir + ' top',
            at: dir + ' bottom'
        }
    });

    // Button
    $('.jquery-ui-button').button();

    // Button set
    $('.jquery-ui-controlgroup').controlgroup();

    // TODO: end integrate jQuery UI stuff properly ----------------------------

    // Async lists navigation
    $('#txp-list-container').closest('main').on('submit', 'nav.prev-next form', function (e) {
        e.preventDefault();
        textpattern.Relay.callback('updateList', {
            data: $(this).serializeArray()
        });
    }).on('click', '.txp-navigation a', function (e) {
        if ($(this).hasClass('pophelp')) {
            return;
        }

        e.preventDefault();
        textpattern.Relay.callback('updateList', {
            url: $(this).attr('href'),
            data: $('nav.prev-next form').serializeArray()
        });

        scroll(0, 0);
    }).on('click', '.txp-list thead th a', function (e) {
        e.preventDefault();
        textpattern.Relay.callback('updateList', {
            list: '#txp-list-container',
            url: $(this).attr('href'),
            data: $('nav.prev-next form').serializeArray()
        });
    }).on('submit', 'form[name="longform"]', function (e) {
        e.preventDefault();
        textpattern.Relay.callback('updateList', {
            data: $(this).serializeArray()
        });
    }).on('submit', 'form.txp-search', function (e) {
        e.preventDefault();

        if ($(this).find('input[name="crit"]').val()) {
            $(this).find('.txp-search-clear').removeClass('ui-helper-hidden');
        } else {
            $(this).find('.txp-search-clear').addClass('ui-helper-hidden');
        }

        textpattern.Relay.callback('updateList', {
            data: $(this).serializeArray()
        });
    }).on('updateList', '#txp-list-container', function () {
        if ($(this).find('.multi_edit_form').txpMultiEditForm('select', {
            value: textpattern.Relay.data.selected
        }).find('table.txp-list').txpColumnize().length == 0) {
            $(this).closest('.txp-layout-1col').find('.txp-list-options-button').hide();
        }
    });

    // Find and open associated dialogs.
    $(document).on('click.txpDialog', '[data-txp-dialog]', function (e) {
        $($(this).data('txp-dialog')).dialog('open');
        e.preventDefault();
    });

    // Attach multi-edit form.
    $('.multi_edit_form').txpMultiEditForm();
    $('table.txp-list').txpColumnize();
    $('a.txp-logout, .txp-logout a').attr('href', 'index.php?logout=1&lang=' + textpattern.prefs.language_ui + '&_txp_token=' + textpattern._txp_token);

    // Initialize panel specific JavaScript.
    textpattern.Route.init();

    // Trigger post init events.
    textpattern.Route.init({
        'step': 'init'
    });

    // Arm UI.
    $('.not-ready').removeClass('not-ready');
    textpattern.Console.announce();
});
