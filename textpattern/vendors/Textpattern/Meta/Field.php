<?php

/*
 * Textpattern Content Management System
 * http://textpattern.com
 *
 * Copyright (C) 2018 The Textpattern Development Team
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
 * A custom field.
 *
 * @since   4.8.0
 * @package CustomField
 */

namespace Textpattern\Meta;

class Field
{
    /**
     * Meta field definition.
     *
     * @var array
     */
    protected $definition = null;

    /**
     * Data type.
     *
     * @var array
     */
    protected $dataType = null;

    /**
     * Options.
     *
     * @var array
     */
    protected $options = array();

    /**
     * Field content.
     *
     * @var array
     */
    protected $content = null;

    /**
     * Default value(s).
     *
     * @var string|array
     */
    protected $default = null;

    /**
     * Meta field columns in the database.
     *
     * @var array
     */
    protected $properties = array();

    /**
     * Crude namespacing to help avoid field collisions with built-in vars.
     *
     * @var string
     */
    protected $labelPfx = 'txpcf_';

    /**
     * Crude namespacing for help / txp_lang strings to void collisions.
     *
     * @var string
     */
    protected $helpPfx = 'txphlp_';

    /**
     * Crude namespacing for inline help / txp_lang strings to void collisions.
     *
     * @var string
     */
    protected $inlineHelpPfx = 'instructions_';

    /**
     * Crude namespacing for option / txp_lang strings to void collisions.
     *
     * @var string
     */
    protected $optionPfx = 'txpopt_';

    /**
     * Return status message holder.
     *
     * @todo Use a dedicated message store / throw exceptions?
     *
     * @var array
     */
    protected $message = array();

    /**
     * Constructor for the field.
     *
     * If passed nothing, an empty field is created ready for population.
     *
     * If passed an id/name, the database is searched for a match and the
     * corresponding record returned if it exists. This facilitates
     * updates to the record.
     *
     * @param mixed $idName The numeric id or array(name, type) of the field to load
     */

    public function __construct($idName = null)
    {
        global $prefs;
        static $out = null;

        $this->properties = array(
            'id',
            'name',
            'content_type',
            'data_type',
            'render',
            'family',
            'textfilter',
            'ordinal',
            'created',
            'modified',
            'expires',
        );

        if ($idName) {
            $this->loadField($idName)
                ->loadOptions()
                ->loadContent();
        }
    }

    /**
     * Set the meta information for the given field from the DB. Chainable.
     *
     * @param mixed $idName The numeric id or array(name, type) of the field to load
     * @param bool  $force  true = always fetch the data from the database, false = use cached value
     */

    public function loadField($idName = null, $force = false)
    {
        if (!$this->definition || $force) {
            if ($idName) {
                if (is_numeric($idName)) {
                    assert_int($idName);
                    $clause = "id = $idName";

                    $this->definition = safe_row(
                        "`" . implode("`,`", $this->properties) . "`",
                        'txp_meta',
                        $clause . " ORDER by ordinal"
                    );
                } elseif (is_array($idName) && count($idName) === 2 && isset($idName['name']) && isset($idName['type'])) {
                    $clause = "name = '" . doSlash($idName['name']) . "' AND content_type = '" . doSlash($idName['type']) . "";

                    $this->definition = safe_row(
                        "`" . implode("`,`", $this->properties) . "`",
                        'txp_meta',
                        $clause . " ORDER by ordinal"
                    );
                } else {
                    foreach ($idName as $key => $value) {
                        if (in_array($key, $this->properties)) {
                            $this->definition[$key] = $value;
                        }
                    }
                }

                $type = \Txp::get('\Textpattern\Meta\DataType')->get();

                // @todo what if the type isn't in the list? Choose a default type? Throw an exception?
                if (isset($type[$this->definition['render']])) {
                    $this->dataType = $type[$this->definition['render']];
                }
            }
        }

        return $this;
    }

    /**
     * Set the content for this field from the DB. Chainable.
     *
     * @param string $ref   Content identifier from which to load the value
     * @param bool   $force true = always fetch the data from the database, false = use cached value
     * @todo txp_section doesn't have an ID field so it can't be referenced yet.
     *       Either add an ID (preferred), or relax the content_id field of meta_value tables to be varchar,
     *       which would imply all refs to meta_id or id be sanitised as they won't be using assert_int().
     */

    public function loadContent($ref = null, $force = false)
    {
        if ($this->content === null || (count($this->content) === 1 && isset($this->content[0]) && $this->content[0] === null) || $force) {
            $fieldCol = $this->getValueField();

            $content = safe_rows(
                'content_id,'. $fieldCol,
                'txp_meta_value_'.$this->definition['data_type'],
                "meta_id = '" . $this->definition['id'] . "' AND (content_id = -1" . ($ref === null ? '' : " OR content_id = '" .$ref. "'") . ")"
            );

            // content_id values with index="-1" contain 'default' entries that need removing.
            foreach ($content as $idx => $row) {
                if ($row['content_id'] == '-1') {
                    $this->default = $row[$fieldCol];
                    unset($content[$idx]);
                    continue;
                }

                $content[$idx]['label'] = gTxt($this->getOptionReference($row[$this->getValueField()]));
            }

            // @Todo What if the value needs to be 0 or empty?
            // @Todo The default value shows up selected (on either new content or those loaded for editing)
            //       BUT it's not actually stored in the DB until the content is saved. So until it's saved
            //       it _looks_ like the record has that CF value, but it actually doesn't. How to get round
            //       this so it's obvious the value is a default and not actually there until it's saved?
            if (!$content) {
                $content = do_list($this->default);
            }

            $this->content = $content;
        }

        return $this;
    }

    /**
     * Retrieve any options for this field from the DB. Chainable.
     *
     * @param bool   $force true = always fetch the data from the database, false = use cached value
     */

    public function loadOptions($force = false)
    {
        if (!$this->options || $force) {
            $hasOptions = ($this->dataType['options']) ? true : false;

            if ($hasOptions) {
                $this->options = safe_rows(
                    'value',
                    'txp_meta_options',
                    "meta_id='" . $this->definition['id'] . "' ORDER BY ordinal"
                );

                // Find the labels and add them to the structure.
                foreach ($this->options as $idx => $row) {
                    $this->options[$idx]['label'] = gTxt($this->getOptionReference($row['value']));
                }
            }
        }

        return $this;
    }

    /**
     * Save the meta information defining this field.
     *
     * @param array $data Name-value tuples for the data to store against each field
     * @return  string Outcome message
     */

    public function save($data = array())
    {
        global $txp_user, $txpnow;

        $table_prefix = 'txp_meta_value_';
        $sqlnow = safe_strftime('%Y-%m-%d %H:%M:%S', $txpnow);
        $data_types = \Txp::get('\Textpattern\Meta\DataType')->get();
        $this->set($data);
        // @todo Possibly validate this.
        $thisLang = get_pref('language_ui', TEXTPATTERN_DEFAULT_LANG);

        extract(doSlash($data));

        // doSlash() done later.
        $help = ps('help');
        $inlineHelp = ps('inline_help');
    /*
        TODO: constraints for data_type, etc
        $constraints = array(
            'category' => new CategoryConstraint($varray['category'], array('type' => 'meta'))
        );
    */
        $constraints = array();

        $created = (empty($created) || $created === '0000-00-00 00:00:00') ? $sqlnow : $created;
        $expires = (empty($expires) || $expires === '0000-00-00 00:00:00') ? "NULL" : "'".$expires."'";
        $data_type = isset($data_types[$render]) ? $data_types[$render] : $data_types['text_input'];

        $has_textfilter = ($textfilter !== '' && $data_type['textfilter']);

        callback_event_ref('meta_ui', 'validate_save', 0, $this->definition, $constraints);
        $validator = new \Textpattern\Validator\Validator($constraints);

        if ($name === '') {
            $name = $labelStr;
        }

        $name = $this->sanitizeName($name);

        if ($validator->validate()) {
            try {
                safe_query('START TRANSACTION');
            
                $table_name = $table_prefix . $data_type['type'];
                $txf = ($has_textfilter ? "$textfilter" : "NULL");

                if ($id) {
                    $ok = safe_update('txp_meta',
                        "name        = '$name',
                        content_type = '$content_type',
                        data_type    = '".$data_type['type']."',
                        render       = '$render',
                        family       = '$family',
                        textfilter   = $txf,
                        ordinal      = '$ordinal',
                        created      = '$created',
                        modified     = '$sqlnow',
                        expires      = $expires",
                        "id = $id"
                    );
                } else {
                    $ok = safe_insert('txp_meta',
                        "name        = '$name',
                        content_type = '$content_type',
                        data_type    = '".$data_type['type']."',
                        render       = '$render',
                        family       = '$family',
                        textfilter   = $txf,
                        ordinal      = '$ordinal',
                        created      = '$created',
                        modified     = '$sqlnow',
                        expires      = $expires"
                    );

                    if ($ok) {
                        $id = $ok;
                    }
                }

                if ($ok) {
                    // Migrate data from one type to another if necessary.
                    // N.B. Data loss may ensue! Caveat utilitor.
                    if ($render_orig !== $render) {
                        if (isset($data_types[$render_orig])) {
                            $data_type_orig = $data_types[$render_orig];
                            $coltype = $data_type['type'];
                            $colsize = $data_type['size'];
                            $colspec = $coltype . ($colsize === null ? '' : '(' . $colsize . ')');

                            $table_name_orig = $table_prefix . $data_type_orig['type'];
                            $has_textfilter_orig = ($data_type_orig['textfilter']);

                            // Create destination table if required.
                            $table_def = "meta_id int(12) NOT NULL DEFAULT 0,
                                content_id int(12) NOT NULL DEFAULT 0,
                                value_id tinyint(4) NOT NULL DEFAULT 0,
                                " . ( $has_textfilter ? 'value_raw ' . $colspec . ' DEFAULT NULL,' : '') . "
                                value " . $colspec . " DEFAULT NULL,
                                UNIQUE KEY meta_content (meta_id,content_id,value_id)";

                            safe_create($table_name, $table_def);

                            $sql = "INSERT IGNORE INTO `" . safe_pfx($table_name) . "`
                                        (meta_id, content_id, value_id, " . ($has_textfilter ? 'value_raw' : 'value') . ")
                                    SELECT meta_id, content_id, value_id, " . ($has_textfilter_orig ? 'value_raw' : 'value') . " 
                                        FROM " . safe_pfx($table_name_orig) . "
                                        WHERE meta_id = '$id';";
                            safe_query($sql);

                            $sql = "DELETE FROM `" . safe_pfx($table_name_orig) . "` WHERE meta_id = '$id';";
                            safe_query($sql);
                        }
                    }

                    // Write default value.
                    // TODO: value_id.
                    safe_delete($table_name, "meta_id='$id' AND content_id='-1' AND value_id='0'");
                    $defaultClause = ($default === '' || $default === '0000-00-00 00:00:00') ? '' : ", value" . ($has_textfilter ? '_raw' : '') . "='$default'";
                    safe_insert($table_name, "meta_id='$id', content_id='-1', value_id='0'".$defaultClause);

                    // Iterate over newly inserted rows and run them through the textfilter if desired.
                    if ($data_type['textfilter']) {
                        $rows = safe_rows('content_id, value_id, value_raw', $table_name, "meta_id = '$id'");

                        foreach ($rows as $row) {
                            $filtered = \Txp::get('Textpattern\Textfilter\Registry')->filter(
                                $textfilter,
                                $row['value_raw'],
                                array(
                                    'field'   => 'value_raw',
                                    'options' => array('lite' => false),
                                    'data'    => $data
                                )
                            );

                            safe_update(
                                $table_name,
                                "value = '" . doSlash($filtered) . "'",
                                "meta_id = '$id'
                                    AND content_id = '" . doSlash($row['content_id']) . "'
                                    AND value_id = '" . doSlash($row['value_id']) . "'"
                            );
                        }
                    }

                    // Write the options.
                    // @Todo What if the keys are altered? Data loss would occur as the named
                    // reference from the value table would break ties with the options table.
                    safe_delete('txp_meta_options', "meta_id = '$id'");
                    $optionList = do_list($options, '\r\n');
                    $insertList = array();
                    $optLabelList = array();

                    foreach ($optionList as $idx => $opt) {
                        if ($opt === '') {
                            continue;
                        }

                        $nv = do_list($opt, '=>');

                        // If just labels given, create appropriate URL-safe keys.
                        if (empty($nv[1])) {
                            $nv[1] = $nv[0];
                        }

                        $nv[0] = $data_type['type'] === 'varchar' ? strtolower(sanitizeForUrl($nv[0])) : $idx;

                        $insertList[] = "('$id', '$nv[0]', '$idx')";
                        $optLabelList[$this->getOptionReference($nv[0])] = $nv[1];
                    }

                    if ($insertList) {
                        $sql = 'INSERT INTO ' . safe_pfx('txp_meta_options') . ' VALUES' . implode(',', $insertList);
                        safe_query($sql);
                    }

                    // Add option labels to Textpack.
                    foreach ($optLabelList as $key => $val) {
                        $done = safe_upsert(
                            'txp_lang',
                            array(
                                'name'  => $key,
                                'data'  => $val,
                                'owner' => 'custom_field',
                            ),
                            array(
                                'name'  => $key,
                                'event' => 'common',
                                'lang'  => $thisLang,
                            )
                        );
                    }

                    // Add label to Textpack, renaming existing entry if $name_orig differs from $name.
                    $orig_label_name = $this->getLabelReference($name_orig);
                    $new_label_name = $this->getLabelReference($name);
                    $done = safe_upsert(
                        'txp_lang',
                        array(
                            'name'  => $new_label_name,
                            'data'  => $labelStr,
                            'owner' => 'custom_field',
                        ),
                        array(
                            'name'  => $orig_label_name,
                            'event' => 'common',
                            'lang'  => $thisLang,
                        )
                    );

                    // Add help to Textpack, renaming existing entry if $name_orig differs from $name.
                    $orig_help_name = $this->getHelpReference($name_orig);
                    $new_help_name = $this->getHelpReference($name);
                    $orig_inline_help_name = $this->getHelpReference($name_orig, 'inline');
                    $new_inline_help_name = $this->getHelpReference($name, 'inline');
                    $done = safe_upsert(
                        'txp_lang',
                        array(
                            'name'  => $new_help_name,
                            'data'  => $help,
                            'owner' => 'custom_field',
                        ),
                        array(
                            'name'  => $orig_help_name,
                            'event' => 'common',
                            'lang'  => $thisLang,
                        )
                    );

                    $done = safe_upsert(
                        'txp_lang',
                        array(
                            'name'  => $new_inline_help_name,
                            'data'  => $inlineHelp,
                            'owner' => 'custom_field',
                        ),
                        array(
                            'name'  => $orig_inline_help_name,
                            'event' => 'common',
                            'lang'  => $thisLang,
                        )
                    );

                    // Let plugins chime in.
                    // Note that this is _before_ the transaction is committed so plugins
                    // have the power to bail out of the entire save process.
                    $payload = compact(
                        'id',
                        'name',
                        'name_orig',
                        'labelStr',
                        'content_type',
                        'render',
                        'render_orig',
                        'options',
                        'default',
                        'family',
                        'textfilter',
                        'ordinal',
                        'created',
                        'modified',
                        'expires'
                    );

                    $ret = callback_event('meta_saved', '', false, $payload);

                    if ($ret !== '') {
                        safe_query('ROLLBACK');
                        $this->message = array(gTxt('meta_save_failed'), E_ERROR);
                    } else {
                        // Update lastmod due to link feeds and commit the transaction.
                        update_lastmod();
                        safe_query('COMMIT');
                        $this->message = gTxt(($id ? 'meta_updated' : 'meta_created'), array('{name}' => doStrip($name)));
                    }
                } else {
                    safe_query('ROLLBACK');
                    $this->message = array(gTxt('meta_save_failed'), E_ERROR);
                }
            } catch (DatabaseException $e) {
                safe_query('ROLLBACK');
                $this->message = array(gTxt('meta_save_failed'), E_ERROR);
            }
        } else {
            $this->message = array(gTxt('meta_save_failed'), E_ERROR);
        }

        return $this->message;
    }

    /**
     * Stash value(s) against this meta field.
     */

    public function store()
    {
        
    }

    /**
     * Set parts of the field meta definition, ready for saving. Chainable.
     *
     * @param array $data Name-value tuples that define or replace existing properties
     * @see save()
     */

    public function set($data = array())
    {
        foreach ($data as $key => $item) {
            if (in_array($key, $this->properties)) {
                $this->definition[$key] = $item;
            }
        }

        return $this;
    }

    /**
     * Set the value(s) for this field, ready for storing. Chainable.
     *
     * @see store()
     */

    public function setValue($data = array())
    {
        foreach ($data as $key => $item) {

        }

        return $this;
    }

    /**
     * Get the contents of the given field(s), or all fields if empty.
     *
     * @param  string|array $field Field name(s): single item, array, or comma-separated list
     * @return array               The requested item(s) with their key as index
     */

    public function get($field = array())
    {
        if (!is_array($field)) {
            $field = do_list($field);
        }

        if (!$field) {
            $field = $this->properties;
        }

        $out = array();

        foreach ($field as $item) {
            switch ($item) {
                case 'options':
                    $out[$item] = $this->options;
                    break;
                case 'default':
                    $out[$item] = $this->default;
                    break;
                case 'content':
                    $out[$item] = $this->content;
                    break;
                default:
                    if (in_array($item, $this->properties)) {
                        $out[$item] = $this->definition[$item];
                    }
                    break;
            }
        }

        return (count($out) === 1) ? reset($out) : $out;
    }

    /**
     * Return the correct in-use 'value' column name for this field.
     *
     * @return string Either 'value' or 'value_raw'
     */

    public function getValueField()
    {
        return ($this->hasTextfilter() ? 'value_raw' : 'value');
    }

    /**
     * Get the content in this field.
     */

    public function getContent()
    {
        return $this->content;
    }

    /**
     * Fetch the label prefix value.
     */

    public function getLabelPrefix()
    {
        return $this->labelPfx;
    }

    /**
     * Fetch the help prefix value.
     *
     * @param  string $type Flavour of pophelp to build (pophelp or inline)
     */

    public function getHelpPrefix($type = 'pophelp')
    {
        return ($type === 'pophelp') ? $this->helpPfx : $this->inlineHelpPfx;
    }

    /**
     * Fetch the option prefix value.
     */

    public function getOptionPrefix()
    {
        return $this->optionPfx;
    }

    /**
     * Fetch a label reference from the given name.
     * 
     * @param  string $name Key name upon which to base the label ref. Assumes doSlash() done
     * @return string       Label reference (txp_lang named key)
     */

    public function getLabelReference($name)
    {
        return $this->getLabelPrefix()
            . doSlash($this->sanitizeName($this->get('content_type')))
            . '_' . $this->sanitizeName($name);
    }

    /**
     * Fetch a help reference from the given name.
     * 
     * @param  string $name Key name upon which to base the help ref. Assumes doSlash() done
     * @param  string $type Flavour of pophelp to build (pophelp or inline)
     * @return string       Help reference (txp_lang named key)
     */

    public function getHelpReference($name, $type = 'pophelp')
    {
        return $this->getHelpPrefix($type)
            . doSlash($this->sanitizeName($this->get('content_type')))
            . '_' . $this->sanitizeName($name);
    }

    /**
     * Fetch an option reference from the given name.
     * 
     * @param  string $name Key name upon which to base the option ref. Assumes doSlash() done
     * @return string       Option reference (txp_lang named key)
     */

    public function getOptionReference($name)
    {
        return $this->getOptionPrefix()
            . doSlash($this->sanitizeName($this->get('content_type')))
            . '_' . $this->sanitizeName($name);
    }

    /**
     * Dumb down the name for URL and string reference purposes.
     * 
     * @param  string $name Key name upon which to operate
     * @return string
     */

    protected function sanitizeName($name)
    {
        return str_replace('-', '_', strtolower(sanitizeForUrl($name)));
    }

    /**
     * Determine if the field has been nominated for textfilter capability.
     *
     * @return bool
     */

    public function hasTextfilter()
    {
        return $this->dataType['textfilter'];
    }

    /**
     * Render a custom field to the screen using an appropriate widget.
     *
     * The rendered widget can be customised via the 'meta_ui > render'
     * callback event.
     * 
     * @param  int          $num     Custom field number
     * @param  string       $type    Data type key
     * @param  string|array $content Content | array of selected content options
     * @param  string       $label   Label identifier
     * @param  string|array $help    Help text item | array(help text item, inline help text)
     * @param  array        $atts    Attribute pairs to assign to wrapper
     * @param  array        $wraptag Tag(s) to wrap the value / label in, or empty to omit
     * @return HTML
     */

    public function render()
    {
        $widget = '';
        $num = $this->get('id');

        $name = 'custom_' . $num;
        $label = $this->get('name');
        $id = 'custom-' . $num;
        $class = 'custom-field ' . $id;
        $fieldCol = $this->getValueField();
        $labelRef = $this->getLabelReference($label);
        $help = $this->getHelpReference($label);
        $inlineHelp = $this->getHelpReference($label, 'inline');
        $type = $this->get('render');
        $options = array();
        $thisContent = array();

        foreach ($this->content as $idx => $row) {
            if (isset($row[$fieldCol])) {
                $thisContent[] = $row[$fieldCol];
            } else {
                $thisContent[] = is_array($row) ? $row[$fieldCol] : $row;
            }
        }

        if (isset($this->dataType['options']) && $this->dataType['options'] === true) {
            $options = safe_rows('value', 'txp_meta_options', "meta_id='" . $num . "' ORDER BY ordinal");
        }

        switch ($type) {
            case 'text_input':
                $widget = fInput('text', $name, implode('', $thisContent), '', '', '', INPUT_REGULAR, '', $id);
                break;
            case 'yesNoRadio':
                $widget = yesnoRadio($name, implode('', $thisContent), 0, $id);
                break;
            case 'onOffRadio':
                $widget = onoffRadio($name, implode('', $thisContent), 0, $id);
                break;
            case 'radioSet':
                $vals = array();

                foreach ($options as $idx => $opt) {
                    $vals[$opt['value']] = gTxt($this->getOptionReference($opt['value']));
                }

                $widget = radioSet($vals, $name, implode('', $thisContent), 0, $id);
                break;
            case 'checkbox':
                // ToDo
                break;
            case 'checkboxSet':
                $out = array();

                foreach ($options as $idx => $opt) {
                    $box_id = $name . '-' . $idx;
                    $out[] = tag(checkbox($name, $opt['value'], (in_array($opt['value'], $thisContent)), '', $box_id, true) . '<label for="' . $box_id . '">' . gTxt($this->getOptionReference($opt['value'])) . '</label>', 'li');
                }

                $widget = tag(implode($out, n), 'ul');
                break;
            case 'text_area':
                $widget = text_area($name, 0, 0, implode('', $thisContent), $id);
                break;
            case 'date':
            case 'time':
                $widget = fInput($type, $name, implode('', $thisContent), '', '', '', INPUT_REGULAR, '', $id);
                break;
            case 'dateTime':
                $widget = fInput('datetime', $name, implode('', $thisContent), '', '', '', INPUT_REGULAR, '', $id);
                break;
            default:
                $widget = callback_event('meta_ui', 'render', 0, compact($num, $id, $name, $type, $labelRef, $options, $help, $thisContent));
                break;
        }

        return inputLabel($id, $widget, $labelRef, array($help, $inlineHelp));
    }
}
