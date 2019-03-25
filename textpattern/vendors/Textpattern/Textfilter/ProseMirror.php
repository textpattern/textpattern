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

class ProseMirror extends Base implements TextfilterInterface
{
    private static $init = null, $id = 'pmeditor';

    /**
     * Constructor.
     */

    public function __construct()
    {
        parent::__construct(self::$id, gTxt('ProseMirror'));
        $this->options = array();

        if (!isset(self::$init)) {
            self::$init = true;

            $js = <<<EOS
// Kludge to make requiring prosemirror core libraries possible. The
// PM global is defined by http://prosemirror.net/examples/prosemirror.js,
// which bundles all the core libraries.
function require(name) {
    let id = /^prosemirror-(.*)/.exec(name), mod = id && PM[id[1].replace(/-/g, "_")]
    if (!mod) throw new Error(`Library basic isn't loaded`)
    return mod
}

const {EditorState} = require("prosemirror-state")
const {EditorView} = require("prosemirror-view")
const {Schema, DOMParser, DOMSerializer} = require("prosemirror-model")
const {schema} = require("prosemirror-schema-basic")
//const {schema, defaultMarkdownParser, defaultMarkdownSerializer} = require("prosemirror-markdown")
const {addListNodes} = require("prosemirror-schema-list")
const {exampleSetup} = require("prosemirror-example-setup")

// Mix the nodes from prosemirror-schema-list into the basic schema to
// create a schema with list support.
const mySchema = new Schema({
    nodes: addListNodes(schema.spec.nodes, "paragraph block*", "block"),
    marks: schema.spec.marks
})
            
textpattern.Route.add("article", function() {
  if (typeof PM === "undefined") {
    return;
  }

  var ProseMirrorEditors = [];

  $(".txp-textarea-options").each(function(i) {
    let textarea = $(this).closest(".txp-form-field-textarea").find("textarea").first(), id = textarea.attr("id")

    $(this).find("input.textfilter-value").change(function() {
        if ($(this).val() == 'pmeditor') {
            if (typeof ProseMirrorEditors[i] === "undefined") {
                textarea.after($("<div />").attr("id", "prosemirror-editor-"+id).css({"width":"100%", "min-height":"10rem", "border":"1px solid #ccc", "display":"none"}))
            }

            textarea.hide()
            $("#prosemirror-editor-"+id).show()

            ProseMirrorEditors[i] = new EditorView(document.getElementById("prosemirror-editor-"+id), {
              state: EditorState.create({
                doc: DOMParser.fromSchema(mySchema).parse(textpattern.decodeHTML(document.getElementById(id).value)),
                plugins: exampleSetup({schema: mySchema})
              }),
              dispatchTransaction(tr) {
                const { state } = this.state.applyTransaction(tr)
                const area = document.getElementById(id)
                const div = $("<div />")

                this.updateState(state)
        
                // Update textarea only if content has changed
                if (tr.docChanged) {
                  //area.value = defaultMarkdownSerializer.serialize(tr.doc)
                  $(area).text(div.html(DOMSerializer.fromSchema(mySchema).serializeFragment(state.doc.content)).html());
                }
              }
            })
        }
        else {
          if (typeof ProseMirrorEditors[i] !== "undefined") {
            ProseMirrorEditors[i].destroy()
          }

          $("#prosemirror-editor-"+id).hide()
          if ($(this).val() < 4) {textarea.show()}
        }

    }).change()
  })
})
EOS;
  script_js($js, false);
        }
    }
}
