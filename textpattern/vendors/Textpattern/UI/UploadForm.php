<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2020 The Textpattern Development Team
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
 * A file upload &lt;form /&gt; element.
 *
 * @since   4.9.0
 * @package UI
 */

namespace Textpattern\UI;

class UploadForm extends Form
{
    /**
     * Attach the form to this Txp event.
     *
     * @var string
     */

    protected $event = null;

    /**
     * Attach the form to this Txp step.
     *
     * @var string
     */

    protected $step = null;

    /**
     * Whether the upload form accepts multiple entries.
     *
     * @var boolean
     */

    protected $multiple = false;

    /**
     * Label to attach to the upload form.
     *
     * @var string
     */

    protected $label = null;

    /**
     * The help topic associated with this field.
     *
     * @var string
     */

    protected $help = null;

    /**
     * The inline help topic associated with this field.
     *
     * @var string
     */

    protected $inlineHelp = null;

    /**
     * Tags in which to wrap the file upload content and its label, respectively.
     *
     * @var array
     */

    protected $wrapTags = array('div', 'div');

    /**
     * Maximum accepted file size, in bytes.
     *
     * @var int
     */

    protected $maxFileSize = null;

    /**
     * Content to append to the file input field.
     *
     * @var int
     */

    protected $postInput = null;

    /**
     * Resource ID (file, image, etc) to which this form is associated.
     *
     * @var int
     */

    protected $resourceId = '';

    /**
     * Construct a single form container tag.
     *
     * @param string $event  The Textpattern panel (event) to which the control is going to post
     * @param string $step   The Textpattern action (step) to which the control is going to post
     * @param string $label  The label to display alongside the upload field
     */

    public function __construct($event, $step, $label = '')
    {
        parent::__construct();

        if ($this->multiple = (bool) preg_match('/^.+\[\]$/', $step)) {
            $step = substr($step, 0, -2);
        }

        $this->event = $event;
        $this->step = $step;
        $this->label = $label;
        $this->maxFileSize = get_pref('file_max_upload_size');
        $this->postInput = new \Textpattern\UI\TagCollection();

        parent::__construct();
    }

    /**
     * Add one or more elements to append to the file input field. Chainable.
     *
     * @param mixed   $item The pre-built UI element or collection
     * @param string  $key  The optional unique key to associate with the tag
     */

    public function append($item, $key = 'postinput')
    {
        if ($item instanceof \Textpattern\UI\TagCollection) {
            foreach ($item as $idx => $element) {
                $this->postInput->add($element, $key.$idx);
            }

            // Original object is not needed any more as it's been merged in this object.
            $item = null;
        } else {
            $this->postInput->add($item, $key);
        }

        return $this;
    }

    /**
     * Set the associated help topic for this input field. Chainable.
     *
     * @param string|array $topic The main help topic or help + inline topics as an array
     */

    public function setHelp($topic)
    {
        if (!is_array($topic)) {
            $topic = array($topic);
        }

        if (empty($topic)) {
            $topic = array(
                0 => '',
                1 => '',
            );
        }

        $this->inlineHelp = (empty($topic[1])) ? '' : $topic[1];
        $this->help = $topic[0];

        return $this;
    }

    /**
     * Set the associated wraptags for this field/label. Chainable.
     *
     * @param string|array $wraptags The wrapper tag(s) to use.
     */

    public function setWrap($wraptags)
    {
        if (!is_array($wraptags)) {
            $wraptags = array($wraptags, $wraptags);
        }

        $this->wrapTags = $wraptags;

        return $this;
    }

    /**
     * Define the form's maximum acceptable file size. Chainable.
     *
     * @param int $size The max permitted upload size, in bytes
     */

    public function setMaxFileSize($size)
    {
        $this->maxFileSize = (int)$size;

        return $this;
    }

    /**
     * Indicate to which object (file ID, image ID, etc) this form refers. Chainable.
     *
     * @param int $id The ID of the resource being edited
     */

    public function setResourceId($id)
    {
        $this->resourceId = (int)$id;

        return $this;
    }

    /**
     * Add the elements as content and draw them.
     *
     * @param  string $flavour To affect the flavour of tag returned - complete, self-closing, open, close, content
     * @return string HTML
     */

    public function render($flavour = 'complete')
    {
        global $event;

        $name = 'thefile'.($this->multiple ? '[]' : '');
        $class[] = 'upload-form';
        $class[] = $this->getAtt('class');
        $className = implode(' ', $class);

        $this->setAtt('id', $this->getAtt('id', $this->event.'-upload'));

        if (empty($this->wrapTags[0]) && empty($this->wrapTags[1])) {
            $wraptagClass = 'inline-file-uploader';
        } else {
            $wraptagClass = 'txp-form-field file-uploader';
        }

        $this->setAtts(array(
            'class'   => $className,
            'enctype' => 'multipart/form-data',
        ));

        $this->setAction("index.php?event={$this->event}&step={$this->step}");

        $progressBar = new \Textpattern\UI\Tag('progress');
        $progressBar
            ->setContent('')
            ->setAtts(array(
                'class' => 'txp-upload-progress ui-helper-hidden',
            ));

        // Build the form content.
        $input = new \Textpattern\UI\Input($name, 'file');
        $input->setBool('required')
            ->setAtts(array(
                'id'       => $this->getAtt('id'),
                'multiple' => $this->multiple,
                'accept'   => $this->getAtt('accept'),
            ));

        $resetButton = new \Textpattern\UI\Input('', 'reset', gTxt('reset'));
        $submitButton = new \Textpattern\UI\Input('', 'submit', gTxt('upload'));

        $buttons = new \Textpattern\UI\Tag('span');
        $buttons->setContent(n.$resetButton.$submitButton.n)
            ->setAtt('class', 'inline-file-uploader-actions');

        $formContent = new \Textpattern\UI\InputLabel($this->getAtt('id'), $input, $this->label);
        $formContent
            ->add($this->postInput)
            ->add($buttons)
            ->setHelp(array($this->help, $this->inlineHelp))
            ->setAtt('class', $wraptagClass)
            ->setWrap($this->wrapTags);

        $this->add($formContent)
            ->add($progressBar);

        $this->add(new \Textpattern\UI\AdminAction($this->event, $this->step, true));
        $this->add(new \Textpattern\UI\Input('id', 'hidden', $this->resourceId));

        if ($this->maxFileSize) {
            $this->add(new \Textpattern\UI\Input('MAX_FILE_SIZE', 'hidden', $this->maxFileSize));
        }

        $arguments = array(
            'name'          => $this->key,
            'input'         => $this->tags,
            'postinput'     => $this->postInput,
            'label'         => $this->label,
            'help'          => array($this->help, $this->inlineHelp),
            'atts'          => $this->atts,
            'multiple'      => $this->multiple,
            'max_file_size' => $this->maxFileSize,
            'wraptag_val'   => $this->wrapTags,
        );

        return pluggable_ui($event.'_ui', 'upload_form', parent::render($flavour), $arguments);
    }
}
