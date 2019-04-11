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

namespace ProseMirror\Textfilter;

class ProseMirror extends \Textpattern\Textfilter\Base implements \Textpattern\Textfilter\TextfilterInterface
{
    private static $init = null, $id = 'prosemirror';

    /**
     * Constructor.
     */

    public function __construct()
    {
        parent::__construct(self::$id, gTxt('ProseMirror'));
        $this->options = array();

        if (!isset(self::$init)) {
            self::$init = true;

            register_callback(function() {
                echo n.'<link rel="stylesheet" href="vendors/ProseMirror/css/editor.css">'.
                script_js("vendors/ProseMirror/js/prosemirror.js", TEXTPATTERN_SCRIPT_URL, array("article")).n;
            }, 'admin_side', 'head_end');

            $id = self::$id;
            $js = <<<EOS
// Kludge to make requiring prosemirror core libraries possible. The
// PM global is defined by http://prosemirror.net/examples/prosemirror.js,
// which bundles all the core libraries.
function require(name) {
    let id = /^prosemirror-(.*)/.exec(name), mod = id && PM[id[1].replace(/-/g, "_")]
    if (!mod) throw new Error(`Library basic isn't loaded`)
    return mod
}

textpattern.Route.add("article", function() {
  if (typeof PM === "undefined") {
    return;
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

  var ProseMirrorEditors = [];

  $(".txp-textarea-options").each(function(i) {
    const textarea = $(this).closest(".txp-form-field-textarea").find("textarea").first(),
        buffer = $("<div />"),
        editor = $("<div />").css({"width":"100%", "min-height":"10rem", "border":"1px solid #ccc", "display":"none"})

    textarea.after(editor)

    $(this).find("input.textfilter-value").change(function() {
        if ($(this).val() == "$id") {
            textarea.hide()
            editor.show()

            ProseMirrorEditors[i] = new EditorView(editor[0], {
              state: EditorState.create({
                doc: DOMParser.fromSchema(mySchema).parse(textpattern.decodeHTML(textarea[0].value)),
                plugins: exampleSetup({schema: mySchema})
              }),
              dispatchTransaction(tr) {
                const { state } = this.state.applyTransaction(tr)

                this.updateState(state)
        
                // Update textarea only if content has changed
                if (tr.docChanged) {
                  //textarea.value = defaultMarkdownSerializer.serialize(tr.doc)
                  textarea.val(buffer.html(DOMSerializer.fromSchema(mySchema).serializeFragment(state.doc)).html());
                }
              }
            })
        }
        else {
          if (typeof ProseMirrorEditors[i] !== "undefined") {
            ProseMirrorEditors[i].destroy()
          }

          editor.hide()
          if ($(this).val() < 4) {textarea.show()}
        }
    })
  })
})
EOS;
  script_js($js, false);
        }
    }
}
