<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2019 The Textpattern Development Team
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
 * Plain-text filter.
 *
 * @since   4.7.4
 * @package Textfilter
 */

namespace Textpattern\Textfilter;

class Medium extends Base implements TextfilterInterface
{
    private static $init = null, $id = 4;

    /**
     * Constructor.
     */

    public function __construct()
    {
        parent::__construct(self::$id, gTxt('MediumEditor'));
        $this->options = array();

        if (!isset(self::$init)) {
            self::$init = true;

            script_js('textpattern.Route.add("article", function() {
                if (typeof MediumEditor === "undefined") {
                    return;
                }

                $(".txp-textarea-options").each(function(i) {
                    var container = $(this).closest(".txp-form-field-textarea");
                    var mEditor = new MediumEditor(container.find("textarea"), textpattern.medium);
                    $(this).find("input.textfilter-value").change(function() {
                        if ($(this).val() == "'.self::$id.'") {
                            mEditor.setup();
                            container.find(".medium-editor-element").show();
                        }
                        else mEditor.destroy();
                    }).change();
                });
            });', false);
        }
    }
}
