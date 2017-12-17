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

    abstract class AssetBase extends SharedBase implements AssetInterface
    {
        /**
         * The asset related directory name.
         *
         * @var string
         * @see        getDir().
         */

        protected static $dir;

        /**
         * The asset related textpack string.
         *
         * @var string
         * @see        getAsset().
         */

        protected static $asset;

        /**
         * The asset table column related to the asset
         * directory subfolder names when applied.
         *
         * @var string
         * @see        getSubdirCol().
         */

        protected static $subdirCol;

        /**
         * The asset table column used to store the templates main contents.
         *
         * @var string
         */

        protected static $contentsCol;

        /**
         * The asset related default templates grouped by types/subfolders.
         * If no defined type apply, just nest the templates array
         * into another one which simulates a abstract group.
         *
         * @var array
         * @see       getContentsCol().
         */

        protected static $essential;

        /**
         * The asset related files extension used for import/export.
         * If set to 'txp', 'html' is also valid on import for now.
         *
         * @var string
         * @see        getExtension().
         */

        protected static $extension = 'txp';

        /**
         * Associative array of skins and their templates grouped by types/subfolders.
         * If no defined type apply, the templates array is just nested
         * into another one which simulates a abstract group.
         *
         * @var array
         * @see       setSkinsTemplates(), getSkinsTemplates().
         */

        protected $skinsTemplates;

        /**
         * {@inheritdoc}
         */

        public function __construct($skins = null, $templates = null)
        {
            $skins ? $this->setSkinsTemplates($skins, $templates) : '';
        }

        /**
         * {@inheritdoc}
         */

        public function setSkinsTemplates($skins, $templates = null)
        {
            is_array($skins) ?: $skins = array($skins);

            $skins = array_map(array($this, 'sanitize'), $skins);

            $this->skinsTemplates = array();

            if ($templates) {
                if (is_string($templates)) {
                    // $templates = 'default';
                    $globalTemplates = array($templates);
                } elseif (is_array($templates)) {
                    if (isset($templates[0])) {
                        if (is_string($templates[0])) {
                            // $templates = array('default', 'error_default');
                            $globalTemplates = $templates;
                        }
                    } else {
                        // $templates = array('misc' => 'a_form');
                        $globalTemplates = $templates;
                    }
                }
            } else {
                $globalTemplates = array(array());
            }

            if (isset($globalTemplates)) {
                $this->skinsTemplates[$skin] = array_fill_keys($skins, $globalTemplates);
            } else {
                $this->skinsTemplates = array_combine($skins, $templates);
            }

            return $this;
        }

        /**
         * {@inheritdoc}
         */

        public function getSkinsTemplates()
        {
            return $this->skinsTemplates;
        }

        /**
         * {@inheritdoc}
         */

        public function getTemplateNames($skin)
        {
            $names = array();

            foreach (self::getSkinsTemplates()[$skin] as $type => $templates) {
                $names = array_merge($names, $templates);
            };

            return $names;
        }

        /**
         * {@inheritdoc}
         */

        public static function getEssentialNames()
        {
            return static::$essential[0];
        }

        /**
         * {@inheritdoc}
         */

        public static function getEssentialTypes($name = null)
        {
            if ($name) {
                foreach (static::$essential as $type => $templates) {
                    if (in_array($name, $templates)) {
                        return $type;
                    }
                }
            } else {
                return array_keys(static::$essential);
            }

            return false;
        }

        /**
         * {@inheritdoc}
         */

        public static function getDir()
        {
            return static::$dir;
        }

        /**
         * {@inheritdoc}
         */

        public static function getSubdirCol()
        {
            return static::$subdirCol;
        }

        /**
         * {@inheritdoc}
         */

        public static function getAsset()
        {
            return static::$asset;
        }

        /**
         * {@inheritdoc}
         */

        public static function getExtension()
        {
            return static::$extension;
        }

        /**
         * {@inheritdoc}
         */

        public static function getContentsCol()
        {
            return static::$contentsCol;
        }

        /**
         * {@inheritdoc}
         */

        public function create()
        {
            callback_event('skin.'.self::getDir().'.create', '', 1, $this->getSkinsTemplates());

            $failed = $unknown = $passed = $sqlValues = array();

            foreach ($this->getSkinsTemplates() as $skin => $typesTemplates) {
                if (!self::isInstalled($skin)) {
                    $failed[$skin] = $unknown[$skin] = '';
                } else {
                    $types = array_keys($typesTemplates);
                    $essentialTypes = self::getEssentialTypes();

                    if ($essentialTypes) {
                        $types = array_unique(
                            array_merge(array_keys($typesTemplates), self::getEssentialTypes())
                        );
                    }

                    foreach ($types as $type) {
                        if (array_key_exists($type, $typesTemplates)) {
                            $templates[$type] = $typesTemplates[$type];
                        } else {
                            $templates[$type] = array();
                        }

                        $essential = static::getEssentialNames(array($type));

                        if ($essential) {
                            $templates[$type] = array_unique(
                                array_merge($essential, $templates[$type])
                            );
                        }

                        if ($templates[$type]) {
                            $passed[$skin] = $templates[$type];
                        } else {
                            $templates[$type];
                            unset($templates[$type]);
                        }
                    }

                    $sqlValues = array_merge(
                        $sqlValues,
                        $this->getCreationSQLValues($skin, $templates)
                    );
                }
            }

            if ($passed) {
                if ($this->insert(self::getTableCols(), $sqlValues)) {
                    $this->setResults(self::getAsset().'_created', $passed, 'success');

                    callback_event('skin.'.self::getDir().'.create', 'success', 0, $passed);
                } else {
                    $failed = array_merge($failed, $passed);
                    $notCreated = $passed;
                    $passed = array();
                }
            }

            if ($failed) {
                if ($unknown) {
                    $this->setResults('skin_unknown', $unknown);
                }

                if ($notCreated) {
                    $this->setResults(self::getAsset().'_creation_failed', $notCreated);
                }

                callback_event('skin.'.self::getDir().'.create', 'failure', 0, $failed);
            }

            callback_event('skin.'.self::getDir().'.create', '', 0, $this->getSkinsTemplates());

            return $this->getSkins($passed);
        }

        /**
         * Gets an array of SQL VALUES sorted as the $tableCols property.
         *
         * @param  array $skin      A skin name.
         * @param  array $templates The skin related template names.
         * @return array            SQL VALUES
         */

        protected static function getCreationSQLValues($skin, $templates)
        {
            $sqlValues = array();

            foreach ($templates as $type => $names) {
                foreach ($names as $name) {
                    $sqlValue = array();

                    foreach (self::getTableCols() as $col) {
                        $sqlValue[] = isset($$col) ? $$col : '';
                    }

                    $sqlValues[] = "('".implode("', '", array_map('doSlash', $sqlValue))."')";
                }
            }

            return $sqlValues;
        }

        /**
         * Changes the templates related skin.
         * Fires after a skin update to keep templates associated with the right skin.
         *
         * @param  array $from The skin (old)names from which templates are adopted.
         *                     The array must be parallel to the $skins array
         *                     passed to the constructor or the setSkinsAssets() method.
         * @return array       Adopted skins.
         */

        public function adopt($from)
        {
            $callbackExtra = array(
                'skins' => $this->getSkinsTemplates(),
                'from'  => $from,
            );

            callback_event('skin.'.self::getDir().'.adopt', '', 1, $callbackExtra);

            if ($this->adoptTemplates($from)) {
                $this->setResults(self::getDir().'_updated', array($from), 'success');

                callback_event('skin.'.self::getDir().'.adopt', 'success', 0, $callbackExtra);

                $passed = $this->getSkinsTemplates();
            } else {
                $this->setResults(self::getDir().'_update_failed', array($from));

                callback_event('skin.'.self::getDir().'.adopt', 'failure', 0, $callbackExtra);

                $passed = array();
            }

            callback_event('skin.'.self::getDir().'.adopt', '', 0, $callbackExtra);

            return $this->getSkins($passed);
        }

        /**
         * Updates the skins templates related 'skin' field in the DB.
         *
         * @param array $from Skins from which you want to adopt the templates.
         *                    The array must be parallel to the $skins array
         *                    passed to the constructor or the setSkinsTemplates() method.
         */

        protected function adoptTemplates($from)
        {
            $cases = '';

            foreach ($this->getSkinsTemplates() as $skin => $templates) {
                $cases .= "WHEN skin = '".doSlash($from[$this->getSkinIndex($skin)])."'";
                $cases .= "THEN '".doSlash($skin)."'";
            }

            return (bool) safe_query(
                'UPDATE '.self::getTable().' '
                .'SET skin = CASE '.$cases.' ELSE skin END '
                ."WHERE skin IN ('".implode("', '", array_map('doSlash', $from))."')"
            );
        }

        /**
         * {@inheritdoc}
         */

        public function import($clean = true, $override = false)
        {
            callback_event('skin.'.self::getDir().'.import', '', 1, $this->getSkinsTemplates());

            $wasLocked = $this->locked;

              $failed
            = $unknown
            = $unreadable
            = $unlockable
            = $duplicated
            = $wrongType
            = $empty
            = $notCleaned
            = $notImported
            = $passed
            = $sqlValues
            = array();

            foreach ($this->getSkinsTemplates() as $skin => $typesTemplates) {
                if (!self::isInstalled($skin)) {
                    $failed[$skin] = $unknown[$skin] = '';
                } elseif (!$wasLocked && !$this->lock($skin)) {
                    $failed[$skin] = $unlockable[$skin] = '';
                } else {
                    if (!self::isReadable($skin.'/'.self::getDir())) {
                        $failed[$skin] = $unreadable[self::getPath($skin.'/'.self::getDir())] = '';
                    }

                    $types = array_keys($typesTemplates);
                    $essentialTypes = array_filter(self::getEssentialTypes());

                    if ($essentialTypes) {
                        if (!array_filter($types)) {
                            $subdirs = array_map('basename', glob(self::getPath($skin.'/'.self::getDir()).'/*'));
                            $types = array_unique(array_merge(array_filter($types), $subdirs));
                        }

                        $types = array_unique(
                            array_merge($types, $essentialTypes)
                        );
                    }

                    foreach ($types as $type) {
                        $passedInType = array();
                        $essential = static::getEssentialNames(array($type));
                        $otherEssential = static::getEssentialNames(
                            array_diff($essentialTypes, array($type))
                        );

                        if (!$unreadable) {
                            $files = self::getRecDirIterator(
                                $skin.'/'.self::getDir().($type ? '/'.$type : ''),
                                $this->getTemplateNames($skin)
                            );

                            foreach ($files as $file) {
                                $name = $file->getTemplateName();
                                if (!array_key_exists($skin, $passed) || !in_array($name, $passed[$skin])) {
                                    if (!$type || !in_array($name, $otherEssential)) {
                                        $passed[$skin][] = $passedInType[] = $name;
                                        $sqlValues[] = self::getImportSQLValue($skin, $file);
                                    } else {
                                        $failed[$skin][] = $wrongType[$skin][] = $name;
                                    }
                                } else {
                                    $failed[$skin][] = $duplicated[$skin][] = $name;
                                }
                            }
                        }

                        $missingTemplates = array_diff($essential, $passedInType);

                        if ($missingTemplates) {
                            $missing = array();

                            foreach ($missingTemplates as $name) {
                                $missing[self::getEssentialTypes($name)][] = $name;
                                $passed[$skin][] = $name;
                            }

                            $sqlValues = array_merge(
                                $sqlValues,
                                $this->getCreationSQLValues($skin, $missing)
                            );
                        }
                    }

                    if (!$passed) {
                        $failed[$skin] = $empty[$skin] = '';
                    }
                }
            }

            if ($sqlValues) {
                if ($this->insert(self::getTableCols(), $sqlValues, $override)) {
                    if ($clean && !self::cleanExtraFiles($passed)) {
                        $failed = array_merge($failed, $passed);
                        $notCleaned = $passed;

                        unset($passed[$skin]);
                    } else {
                        callback_event('skin.'.self::getDir().'.import', 'success', 0, $passed);

                        $this->setResults(self::getAsset().'_imported', $passed, 'success');
                    }
                } else {
                    $failed = array_merge($failed, $passed);
                    $notImported = $passed;
                    $passed = array();
                }
            }

            if ($failed) {
                if ($unknown) {
                    $this->setResults('skin_unknown', $unknown);
                }

                if ($unreadable) {
                    $this->setResults('path_not_readable', $unreadable);
                }

                if ($unlockable) {
                    $this->setResults('skin_locking_failed', $unlockable);
                }

                if ($wrongType) {
                    $this->setResults(self::getAsset().'_type_error', $wrongType);
                }

                if ($duplicated) {
                    $this->setResults('duplicated_'.self::getAsset(), $duplicated);
                }

                if ($empty) {
                    $this->setResults('no_'.self::getAsset().'_found', $empty);
                }

                if ($notImported) {
                    $this->setResults(self::getAsset().'_import_failed', $notImported);
                }

                if ($notCleaned) {
                    $this->setResults(self::getAsset().'_cleaning_failed', $notCleaned);
                }

                callback_event('skin.'.self::getDir().'.import', 'failure', 0, $failed);
            }

            callback_event('skin.'.self::getDir().'.import', '', 0, $this->getSkinsTemplates());

            return $this->getSkins($passed);
        }

        /**
         * Gets files from a defined directory.
         *
         * @param  array  $path      A directory path.
         * @param  array  $templates Template names to filter results;
         * @return object            RecursiveIteratorIterator
         */

        protected static function getRecDirIterator($path, $templates = null)
        {
            if ($templates) {
                $templates = '('.implode('|', $templates).')';
            } else {
                $templates = self::getValidNamePattern();
            }

            $extension = self::getExtension();
            $extension === 'txp' ? $extension = '(txp|html)' : '';

            return new RecIteratorIterator(
                new RecRegexIterator(
                    new RecDirIterator(self::getPath($path)),
                    '#^'.$templates.'\.'.$extension.'$#i'
                ),
                self::getSubdirCol() ? 1 : 0
            );
        }

        /**
         * Gets an SQL VALUES value sorted as the asset $tableCols property.
         *
         * @param  object $file See RecDirIterator.
         * @return string
         */

        protected static function getImportSQLValue($skin, RecDirIterator $file)
        {
            $sqlValue = array();

            foreach (self::getTableCols() as $col) {
                if ($col === 'skin') {
                    $sqlValue[] = $skin;
                } else {
                    if ($col === self::getContentsCol()) {
                        $info = 'content';
                    } elseif ($col === self::getSubdirCol()) {
                        $info = 'dir';
                    } else {
                        $info = $col;
                    }

                    $sqlValue[] = $file->getTemplateInfo($info);
                }
            }

            return "('".implode("', '", array_map('doSlash', $sqlValue))."')";
        }

        /**
         * Drops obsolete template rows.
         *
         * @param  array $not An array of template names to NOT drop;
         * @return bool  false on error.
         */

        public static function cleanExtraFiles($not)
        {
            $where = '';

            foreach ($not as $skin => $templates) {
                $where .= "(skin = '".doSlash($skin)."' AND";
                $where .= " name NOT IN ('".implode("', '", array_map('doSlash', $templates))."'))";
            }

            return safe_delete(self::getTable(), $where);
        }

        /**
         * {@inheritdoc}
         */

        public function duplicate($to)
        {
            callback_event('skin.'.self::getDir().'.duplicate', '', 1, $this->getSkinsTemplates());

            $rows = $this->getRows();

            $passed = $passedTemplates = $sqlValues = $failed = array();

            foreach ($this->getSkinsTemplates() as $skin => $templates) {
                $new = $to[$this->getSkinIndex($skin)];

                if (!self::isInstalled($new)) {
                    $failed[$new] = $unknown[$new] = '';
                } else {
                    $passed[$skin] = array();
                    $passedRows = $rows[$skin];

                    foreach ($passedRows as $row) {
                        $passed[$skin][] = $row['name'];
                        $row['skin'] = self::sanitize($new);
                        $sqlValues[] = "('".implode("', '", array_map('doSlash', $row))."')";
                    }
                }

                $missing = array_diff(static::getEssentialNames(), $passed[$skin]);

                if ($missing) {
                    $sqlValues = array_merge_recursive(
                        $sqlValues,
                        $this->getCreationSQLValues($new, $missing)
                    );
                }
            }

            if ($sqlValues) {
                if ($this->insert(self::getTableCols(), $sqlValues)) {
                    $this->setResults(self::getAsset().'_created', $passed, 'success');

                    callback_event('skin.'.self::getDir().'.duplicate', 'success', 0, $passed);
                }
            } else {
                $failed = array_merge($failed, $passed);
                $dbFailure = $passed;
                $passed = array();
            }

            if ($failed) {
                if ($unknown) {
                    $this->setResults('skin_unknown', $unknown);
                }

                if ($dbFailure) {
                    $this->setResults(self::getAsset().'_creation_failed', $dbFailure);
                }

                callback_event('skin.'.self::getDir().'.duplicate', 'failure', 0, $failed);
            }

            callback_event('skin.'.self::getDir().'.duplicate', '', 0, $this->getSkinsTemplates());

            return $this->getSkins($passed);
        }

        /**
         * Gets skins asset related templates rows.
         *
         * @return array Associative array of skins and their template rows.
         */

        protected function getRows($skins = null)
        {
            $skins === null ? $skins = $this->getSkins() : '';

            $where = $skinIn = array();

            foreach ($skins as $skin) {
                $templates = $this->getTemplateNames($skin);

                if ($templates) {
                    $where[] = "(skin = '".doSlash($skin)."' AND "
                               ."name IN ('".implode("', '", array_map('doSlash', $templates))."'))";
                } else {
                    $skinIn[] = $skin;
                }
            }

            if ($skinIn) {
                $where[] = "(skin IN ('".implode("', '", array_map('doSlash', $skinIn))."'))";
            }

            $rs = safe_rows_start(
                implode(', ', self::getTableCols()),
                self::getTable(),
                implode(' OR ', $where)
            );

            $rows = array();

            if ($rs) {
                while ($row = nextRow($rs)) {
                    $rows[$row['skin']][] = $row;
                }
            }

            return $rows;
        }

        /**
         * {@inheritdoc}
         */

        public function export($clean = true)
        {
            callback_event('skin.'.self::getDir().'.export', '', 1, $this->getSkinsTemplates());

              $failed
            = $notWritable
            = $unlockable
            = $passed
            = array();

            foreach ($this->getSkinsTemplates() as $skin => $templates) {
                $writable = self::isWritable($skin.'/'.self::getDir());
                $new = !$writable && self::mkDir($skin.'/'.self::getDir());

                if (!$new && !$writable) {
                    $failed[$skin] = $notWritable[self::getPath($skin)] = '';
                } elseif (!$this->locked && !$this->lock($skin)) {
                    $failed[$skin] = $unlockable[$skin] = '';
                } else {
                    $passed[$skin] = '';
                }
            }

            $rows = $this->getRows($this->getSkins($passed));

            if ($rows) {
                $skins = $this->getSkins($passed);
                $passed = array();

                foreach ($skins as $skin) {
                    if (!array_key_exists($skin, $rows)) {
                        $failed[$skin] = $unknown[$skin] = '';
                        unset($passed[$skin]);
                    } else {
                        $passedRows = $rows[$skin];
                        $passedTemplates = array();

                        foreach ($passedRows as $row) {
                            if (!preg_match('#^'.self::getValidNamePattern().'$#', $row['name'])) {
                                $failed[$skin][] = $invalid[$skin][] = $row['name'];
                                unset($passed[$skin]);
                            } elseif (!self::exportTemplate($row)) {
                                $failed[$skin][] = $notExported[$skin][] = $row['name'];
                                unset($passed[$skin]);
                            } else {
                                $passed[$skin][] = $row['name'];

                                if (self::getSubdirCol()) {
                                    $passedTemplates[$row[self::getSubdirCol()]][] = $row['name'];
                                } else {
                                    $passedTemplates[] = $row['name'];
                                }
                            }
                        }

                        if (!$new && $clean) {
                            $notUnlinked = $this->cleanExtraRows($skin, $passedTemplates);

                            if ($notUnlinked) {
                                $failed[$skin] = $notUnlinked[$skin] = $notUnlinked;
                                unset($passed[$skin]);
                            }
                        }
                    }
                }

                if ($passed) {
                    $this->setResults(self::getAsset().'_exported', $passed, 'success');

                    callback_event('skin.'.self::getDir().'.export', 'success', 0, $passed);
                }

                if ($failed) {
                    if ($notWritable) {
                        $this->setResults('path_not_writable', $notWritable);
                    }

                    if ($unlockable) {
                        $this->setResults('skin_locking_failed', $unlockable);
                    }

                    if ($unknown) {
                        $this->setResults('no_'.self::getDir().'_found', $unknown);
                    }

                    if ($invalid) {
                        $this->setResults('unsafe_'.self::getAsset().'_name', $invalid);
                    }

                    if ($notUnlinked) {
                        $this->setResults(self::getAsset().'_cleaning_failed', $notUnlinked);
                    }

                    if ($notExported) {
                        $this->setResults(self::getAsset().'_export_failed', $notExported);
                    }

                    callback_event('skin.'.self::getDir().'.export', 'failure', 0, $failed);
                }
            }

            callback_event('skin.'.self::getDir().'.export', '', 0, $this->getSkinsTemplates());

            return $this->getSkins($passed);
        }

        /**
         * Exports a skin template.
         *
         * @param  array $name A template name;
         * @param  array $row  Template row as an associative array.
         * @return bool        false on error.
         */

        protected static function exportTemplate($row)
        {
            extract($row);

            $dirPath = $skin.'/'.self::getDir();

            if (self::getSubdirCol()) {
                $dirPath .= '/'.${self::getSubdirCol()};
                $writable = self::isWritable($dirPath) || self::mkDir($dirPath);
            } else {
                $writable = true;
            }

            if (${self::getContentsCol()}) {
                $content = ${self::getContentsCol()};
            } else {
                $content = '<!-- This template was empty on export. -->';
            }

            if ($writable) {
                return (bool) file_put_contents(
                    self::getPath($dirPath.'/'.$name.'.'.self::getExtension()),
                    $content
                );
            }
        }

        /**
         * Unlinks obsolete template files.
         *
         * @param  array $not An array of template names to NOT unlink;
         * @return array      !Templates for which the unlink process FAILED!;
         */

        protected function cleanExtraRows($skin, $not)
        {
            $files = self::getRecDirIterator($skin.'/'.self::getDir());
            $notRemoved = array();

            foreach ($files as $file) {
                $filenames[] = $name = $file->getTemplateName();

                if (!$not || ($not && !in_array($name, $not))) {
                    unlink($file->getPathname()) ?: $notRemoved[] = $name;
                }
            }

            return $notRemoved;
        }

        /**
         * {@inheritdoc}
         */

        public function delete()
        {
            callback_event('skin.'.self::getDir().'.delete', '', 1, $this->getSkinsTemplates());

            $rows = $this->getRows();

            if ($rows) {
                $passed = array_fill_keys(array_keys($rows), '');

                if ($this->deleteTemplates()) {
                    $this->setResults(self::getAsset().'_deleted', $passed, 'success');

                    callback_event('skin.'.self::getDir().'.delete', 'success', 0, $passed);
                } else {
                    $this->setResults(self::getAsset().'_deletion_failed', $passed);

                    callback_event('skin.'.self::getDir().'.delete', 'failure', 0, $passed);
                }
            } else {
                $passed = array($this->getSkinsTemplates());
            }

            callback_event('skin.'.self::getDir().'.delete', '', 0, $this->getSkinsTemplates());

            return $this->getSkins($passed);
        }

        /**
         * Deletes skin template rows from the DB.
         *
         * @return bool false on error.
         */

        protected function deleteTemplates()
        {
            $where = $skinIn = array();

            foreach ($this->getSkinsTemplates() as $skin => $types) {
                $templates = self::getTemplateNames($skin);

                if ($templates) {
                    $where[] = "(skin = '".doSlash($skin)."' AND "
                               ."name IN ('".implode("', '", array_map('doSlash', $templates))."'))";
                } else {
                    $skinIn[] = $skin;
                }
            }

            if ($skinIn) {
                $where[] = "(skin IN ('".implode("', '", array_map('doSlash', $skinIn))."'))";
            }

            return safe_delete(doSlash(self::getTable()), implode(' OR ', $where));
        }
    }
}
