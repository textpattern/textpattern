<?php

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

/**
 * Admin-side search method.
 *
 * A container for packaging search criteria into a cohesive set of SQL
 * statements suitable for the WHERE clause of a query.
 *
 * Supports aliasing of criteria so many user input values equates to a single
 * value to be compared in the DB (e.g. 'true', 'yes', or '1' can all be used to
 * check for a '1' in a boolean field).
 *
 * @since   4.6.0
 * @package Search
 */

namespace Textpattern\Search;

class Method
{
    /**
     * The method's unique reference -- usually the form-submitted variable name.
     *
     * @var string
     */

    protected $id;

    /**
     * The database column names against which a search should be performed.
     *
     * @var array
     */

    protected $columns;

    /**
     * The method's human friendly name.
     *
     * @var string
     */

    protected $label;

    /**
     * The method's data type.
     *
     * Determines if the method is compared as string, integer, etc.
     *
     * @var string
     */

    protected $type;

    /**
     * The method's options.
     *
     * @var array
     */

    protected $options;

    /**
     * Criteria aliases: values that are equivalent to the actual DB value.
     *
     * @var array
     */

    protected $aliases;

    /**
     * General constructor for search methods.
     *
     * @param string $key  The unique reference to this Method
     * @param array  $atts Attributes such as the DB column(s) to which the method applies, its label or data type
     */

    public function __construct($key, $atts)
    {
        $this->id = $key;
        $options = array();

        foreach ($atts as $attribute => $value) {
            switch ($attribute) {
                case 'column':
                    $this->columns = (array) $value;
                    break;
                case 'label':
                    $this->label = (string) $value;
                    break;
                case 'type':
                    $this->type = (string) $value;
                    break;
                case 'options':
                    $options = (array) $value;
                    break;
            }
        }

        $this->setOptions($options);
    }

    /**
     * Sets method's options.
     *
     * Valid options are:
     *  -> always_like: treat the criteria as SQL LIKE, regardless if it is "in quotes".
     *  -> can_list: the criteria can be a comma-separated list of values to retrieve.
     *  -> case_sensitive: the criteria is case sensitive.
     *
     * @param array $options Array of options
     */

    public function setOptions($options = array())
    {
        $this->options = lAtts(array(
            'always_like'    => false,
            'can_list'       => false,
            'case_sensitive' => false,
        ), (array) $options);
    }

    /**
     * Gets methods options.
     *
     * @param array $options Array of options
     */

    public function getOptions($keys = array())
    {
        $out = array();

        if (empty($keys)) {
            $keys = array_keys($this->options);
        }

        foreach ((array) $keys as $key) {
            if (isset($this->options[$key])) {
                $out[] = $this->options[$key];
            }
        }

        return $out;
    }

    /**
     * Sets method aliases.
     *
     * An alias' key is the literal value that is passed to the database as search criteria
     * if the user-supplied criteria matches any of the aliases. Useful for computed,
     * define()d or boolean fields. For example, allowing anyone to use the (localised)
     * words 'draft', 'hidden' or 'live' in a search box as aliases for the numeric
     * values 1, 2, and 4 respectively.
     *
     * @param string $column  DB column to which the aliases should be applied.
     * @param string $key     DB value that is returned if the criteria matches.
     * @param array  $aliases Criteria values that are equivalent to the key.
     */

    public function setAlias($column, $key, $aliases)
    {
        $this->aliases[$column][$key] = $aliases;
    }

    /**
     * Searches aliases for a partial match.
     *
     * @param string $needle   The thing to look for
     * @param array  $haystack The place to look in
     * @param string $type     $needle's type, which affects the way the alias is interpreted
     */

    private function findAlias($needle, $haystack, $type = '')
    {
        foreach ($haystack as $key => $value) {
            if ($type === 'numeric') {
                if ($value == $needle) {
                    return $key;
                }
            } else {
                if (strpos($value, $needle) !== false) {
                    return $key;
                }
            }
        }

        return false;
    }

    /**
     * Gets an item from the object.
     *
     * @param string $item The thing to retrieve
     */

    public function getInfo($item)
    {
        $out = null;

        switch ($item) {
            case 'column':
                $out = $this->columns;
                break;
            case 'label':
                $out = $this->label;
                break;
            case 'type':
                $out = $this->type;
                break;
        }

        return $out;
    }

    /**
     * Gets an SQL clause for this method based on the criteria.
     *
     * @todo Case-sensitive searches
     *
     * @param string $search_term The value to search for
     * @param int    $verbatim    Whether the search term is in quotes (1) or not (0)
     */

    public function getCriteria($search_term, $verbatim)
    {
        $clause = array();
        $numRE = "/([><!=]*)(([-+]?\d*\.?\d+)(?:[eE]([-+]?\d+))?)/";

        foreach ($this->columns as $column) {
            if (isset($this->aliases[$column])) {
                foreach ($this->aliases[$column] as $dbval => $aliases) {
                    $terms = do_list($search_term);

                    foreach ($terms as $idx => $term) {
                        if ($this->findAlias($term, $aliases, $this->type) !== false) {
                            $terms[$idx] = $dbval;
                        }
                    }

                    $search_term = join(',', $terms);
                }
            }

            if ($this->options['case_sensitive']) {
                $column = 'BINARY '.$column;
            }

            if ($this->options['can_list']) {
                preg_match($numRE, $search_term, $matches);

                if (!empty($matches[1]) && strpos($search_term, ',') === false) {
                    $operator = $matches[1];
                    $value = $matches[2];
                } else {
                    $operator = ' in ';
                    $value = '('.join(',', quote_list(do_list($search_term))).')';
                }

            } elseif ($this->type === 'boolean') {
                $clause[] = "convert(".$column.", char) = '".$search_term."'";
                continue;
            } elseif ($verbatim && !$this->options['always_like']) {
                $operator = ' = ';
                $value = doQuote($search_term);
            } elseif ($this->type === 'find_in_set') {
                $clause[] = "find_in_set('".$search_term."', ".$column." )";
                continue;
            } elseif ($this->type === 'numeric') {
                preg_match($numRE, $search_term, $matches);

                $comparator = !empty($matches[1]) ? $matches[1] : '=';

                // Use coalesce() to guard against nulls when using LEFT JOIN.
                if (isset($matches[2])) {
                    if (is_numeric($matches[2])) {
                        $clause[] = "coalesce(".$column.", 0) ".$comparator." '".$matches[2]."'";
                    } else {
                        $clause[] = "coalesce(convert(".$column.", char), 0) ".$comparator." '".$matches[2]."'";
                    }
                }

                continue;
            } else {
                $operator = ' like ';
                $value = doQuote("%$search_term%");
            }

            $clause[] = join($operator, array($column, $value));
        }

        return $clause;
    }
}
