<?php

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
 * AssetBase
 *
 * Extended by Pages, Forms, Styles…
 *
 * @since   4.7.0
 * @package Skin
 * @see     SkinBase, SkinInterface
 */

namespace Textpattern\Skin {

    abstract class AssetBase extends SkinBase implements SkinInterface
    {
        /**
         * The asset related table.
         *
         * @var string
         */

        protected static $table;

        /**
         * The asset related default templates
         * to use on skin creation.
         *
         * @var array
         */

        protected static $essential;

        /**
         * The asset related skin subdirectory.
         *
         * @var string
         */

        protected static $dir;

        /**
         * The max depth (or number of nested subdirectories)
         * used to store the asset related templates
         * in the above directory.
         *
         * @var string
         */

        protected static $depth = 0;

        /**
         * The valid asset related files extension.
         *
         * @var string
         */

        protected static $extension = 'txp';

        /**
         * The asset related template names to work with
         * (all templates by default).
         *
         * @var array
         */

        protected $templates = array();


        /**
         * {@inheritdoc}
         */

        public function __construct($skin = null, $infos = null, $templates = null)
        {
            parent::__construct($skin, $infos);

            if ($templates) {
                $this->templates = is_array($templates) ? $templates : array($templates);
            }
        }

        public static function getEssential()
        {
            return static::$essential;
        }

        /**
         * {@inheritdoc}
         */

        public function create()
        {
            if ($this->skinIsInstalled()) {
                $templates = $this->templates ? $this->templates : static::$essential;
                $sql_values = $this->getCreationSQLValues($templates);

                $callback_extra = array(
                    'skin'      => strtolower(sanitizeForUrl($this->skin)),
                    'templates' => $templates,
                );

                callback_event(static::$dir, 'create', 0, $callback_extra);

                if ($this->insertTemplates(static::$columns, $sql_values)) {
                    callback_event(static::$dir, 'created', 0, $callback_extra);
                } else {
                    callback_event(static::$dir, 'creation_failed', 0, $callback_extra);

                    throw new \Exception($this->getFailureMessage('creation', $this->templates));
                }
            } else {
                throw new \Exception('unknown_skin');
            }
        }

        /**
         * Gets an array of SQL VALUES sorted as the asset $columns property.
         *
         * @param  array $templates An array of templates names to create.
         * @return array SQL VALUES
         */

        abstract protected function getCreationSQLValues($templates);

        /**
         * {@inheritdoc}
         */
        public function edit()
        {
            $callback_extra = array(
                'skin'      => $this->skin,
                'templates' => $this->templates,
            );

            callback_event(static::$dir, 'import', 0, $callback_extra);

            $updated = (bool) safe_update(
                static::$table,
                "skin = '".doSlash(strtolower(sanitizeForUrl($this->infos['new_name'])))."'",
                "skin = '".doSlash($this->skin)."'"
            );

            if ($updated) {
                callback_event(static::$dir, 'edited', 0, $callback_extra);

                return;
            }

            callback_event(static::$dir, 'edit_failed', 0, $callback_extra);

            throw new \Exception($this->getFailureMessage('edit', $failed));
        }

        /**
         * {@inheritdoc}
         */

        public function import($clean = true)
        {
            if ($this->skinIsInstalled()) {
                $was_locked = $this->locked;

                if ($this->isReadable(static::$dir) && $this->lockSkin()) {
                    $files = $this->getRecDirIterator();
                    $passed = $failed = $sql_values = array();

                    foreach ($files as $file) {
                        $name = $file->getTemplateName();

                        if (!in_array($name, $passed)) {
                            $passed[] = $name;
                            $sql_values[] = $this->getImportSQLValue($file);
                        } else {
                            $failed[] = $name; // Duplicated form.
                        }
                    }

                    $was_locked ?: $this->unlockSkin();

                    $callback_extra = array(
                        'skin'      => $this->skin,
                        'templates' => $passed,
                    );

                    callback_event(static::$dir, 'import', 0, $callback_extra);

                    if ($sql_values) {
                        if ($this->insertTemplates(static::$columns, $sql_values, true)) {
                            callback_event(static::$dir, 'imported', 0, $callback_extra);

                            $clean ? $this->dropRemovedFiles($passed) : '';

                            if ($failed) {
                                throw new \Exception(
                                    $this->getFailureMessage(
                                        'import',
                                        $failed,
                                        'skin_step_failed_for_duplicated_templates'
                                    )
                                );
                            }
                        } else {
                            callback_event(static::$dir, 'import_failed', 0, $callback_extra);

                            throw new \Exception(
                                $this->getFailureMessage('import', $passed)
                            );
                        }
                    }
                }
            } else {
                throw new \Exception('unknown_skin');
            }
        }

        /**
         * {@inheritdoc}
         */

        public function getRecDirIterator()
        {
            if ($this->templates) {
                $templates = '('.implode('|', $this->templates).')';
            } else {
                $templates = '[a-z][a-z0-9_\-\.]{0,63}';
            }

            if (static::$extension === 'txp') {
                $extension = '(txp|html)';
            } else {
                $extension = static::$extension;
            }

            return new RecIteratorIterator(
                new RecRegexIterator(
                    new RecDirIterator($this->getPath(static::$dir)),
                    '/^'.$templates.'\.'.$extension.'$/i'
                ),
                static::$depth
            );
        }

        /**
         * Gets an SQL VALUE sorted as the asset $columns property.
         *
         * @param  RecDirIterator $file.
         * @return string SQL VALUE (a VALUES item).
         */

        abstract protected function getImportSQLValue(RecDirIterator $file);

        /**
         * {@inheritdoc}
         */

        public function insertTemplates($fields, $values, $update = false)
        {
            if ($update) {
                $updates = array();

                foreach ($fields as $field) {
                    if ($field !== 'name' && $field !== 'name') {
                        $updates[] = $field.'=VALUES('.$field.')';
                    }
                }

                $update = 'ON DUPLICATE KEY UPDATE '.implode(', ', $updates);
            }

            return (bool) safe_query(
                sprintf(
                    'INSERT INTO '.safe_pfx(static::$table).' (%s) VALUES %s %s',
                    implode(', ', $fields),
                    implode(', ', $values),
                    $update ? $update : ''
                )
            );
        }

        /**
         * {@inheritdoc}
         */

        public function dropRemovedFiles($not)
        {
            $where = "skin = '".doSlash($this->skin)."'";

            if ($not) {
                $where .= " AND name NOT IN ('".implode("', '", array_map('doSlash', $not))."')";
            }

            if ($drop = (bool) safe_delete(static::$table, $where)) {
                return $drop;
            }

            throw new \Exception('unable_to_delete_obsolete_skin_templates');
        }

        /**
         * {@inheritdoc}
         */

        public function update($clean = true)
        {
            return $this->import($clean);
        }

        /**
         * {@inheritdoc}
         */

        public function duplicate($as)
        {
            if ($this->skinIsInstalled(true)) {
                if ($rows = $this->getTemplateRows()) {
                    $templates = $sql_values = array();

                    foreach ($rows as $row) {
                        $templates[] = $row['name'];
                        $row['skin'] = strtolower(sanitizeForUrl($this->copy));
                        isset($sql_fields) ?: $sql_fields = array_keys($row);
                        $sql_values[] = "('".implode("', '", array_map('doSlash', $row))."')";
                    }

                    $callback_extra = array(
                        'skin'      => $this->skin,
                        'templates' => $templates,
                    );

                    callback_event(static::$dir, 'duplicate', 0, $templates);

                    if ($sql_fields && $sql_values) {
                        if ($this->insertTemplates($sql_fields, $sql_values)) {
                            callback_event(static::$dir, 'duplicated', 0, $callback_extra);
                        } else {
                            callback_event(static::$dir, 'duplication_failed', 0, $callback_extra);

                            throw new \Exception(
                                $this->getFailureMessage('duplication', $templates)
                            );
                        }
                    }
                }
            } else {
                throw new \Exception('unknown_skin');
            }
        }

        /**
         * {@inheritdoc}
         */

        public function getTemplateRows()
        {
            return safe_rows('*', static::$table, $this->getWhereClause());
        }

        /**
         * {@inheritdoc}
         */

        public function getWhereClause()
        {
            $where = "skin = '".doSlash($this->skin)."'";

            if ($this->templates) {
                $where .= ' AND name in ("'.implode('", "', array_map('doSlash', $this->templates)).'")';
            }

            return $where;
        }

        /**
         * {@inheritdoc}
         */

        public function export($clean = true, $as = null)
        {
            if ($rows = $this->getTemplateRows()) {
                $was_locked = $this->locked;

                if ($this->lockSkin() && ($this->isWritable(static::$dir) || $this->mkDir(static::$dir))) {
                    $callback_extra = array(
                        'skin'      => $this->skin,
                        'templates' => $this->templates,
                    );

                    callback_event(static::$dir, 'export', 0, $callback_extra);

                    $passed = $failed = array();

                    foreach ($rows as $row) {
                        if ($this->exportTemplate($row)) {
                            $passed[] = $row['name'];
                        } else {
                            $failed[] = $row['name'];
                        }
                    }

                    $was_locked ?: $this->unlockSkin();

                    if ($passed) {
                        $callback_extra['templates'] = $passed;

                        callback_event(static::$dir, 'exported', 0, $callback_extra);

                        $clean ? $this->unlinkRemovedRows($passed) : '';
                    }

                    if ($failed) {
                        $callback_extra['templates'] = $failed;

                        callback_event(static::$dir, 'export_failed', 0, $callback_extra);

                        throw new \Exception(
                            $this->getFailureMessage('export', $failed)
                        );
                    }
                }
            }
        }

        /**
         * {@inheritdoc}
         */

        abstract public function exportTemplate($row);

        /**
         * {@inheritdoc}
         */

        public function unlinkRemovedRows($not)
        {
            $files = $this->getRecDirIterator();

            foreach ($files as $file) {
                $name = $file->getTemplateName();

                if (!$not || ($not && !in_array($name, $not))) {
                    unlink($file->getPathname());
                }
            }
        }

        /**
         * {@inheritdoc}
         */

        public function delete()
        {
            if ($rows = $this->getTemplateRows()) {
                $templates = array();

                foreach ($rows as $row) {
                    $templates[] = $row['name'];
                }

                $callback_extra = array(
                    'skin'      => $this->skin,
                    'templates' => $templates,
                );

                callback_event(static::$dir, 'delete', 0, $callback_extra);

                if ($this->deleteTemplates()) {
                    callback_event(static::$dir, 'deleted', 0, $callback_extra);
                } else {
                    callback_event(static::$dir, 'deletion_failed', 0, $callback_extra);

                    throw new \Exception($this->getFailureMessage('deletion', $templates));
                }
            }
        }

        /**
         * {@inheritdoc}
         */

        public function deleteTemplates()
        {
            return (bool) safe_delete(doSlash(static::$table), $this->getWhereClause());
        }

        /**
         * {@inheritdoc}
         */

        private function getFailureMessage(
            $process,
            $templates,
            $message = 'skin_step_failed_for_templates'
        ) {
            return gtxt(
                $message,
                array(
                    '{skin}'      => $this->skin,
                    '{asset}'     => static::$dir,
                    '{step}'      => $process,
                    '{templates}' => implode(', ', $templates),
                )
            );
        }
    }
}
