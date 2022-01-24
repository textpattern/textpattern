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
 * Skin Interface
 *
 * Implemented by Skin.
 *
 * @since   4.7.0
 * @package Skin
 */

namespace Textpattern\Skin;

interface SkinInterface
{
    /**
     * $dirPath property setter.
     *
     * @param  string $path Custom skin directory path.
     *                      Builds the path from the 'path_to_site' + 'skin_dir'
     *                      if null.
     * @return string $this->dirPath
     */

    public function setDirPath($path = null);

    /**
     * $assets property setter.
     *
     * @param  array $pages  Page names to work with;
     * @param  array $forms  Form names to work with;
     * @param  array $styles CSS names to work with.
     * @return object $this   The current class object (chainable).
     */

    public function setAssets($pages = null, $forms = null, $styles = null);

    /**
     * $infos and $name properties setter.
     *
     * @param  string $name        Skin name;
     * @param  string $title       Skin title;
     * @param  string $version     Skin version;
     * @param  string $description Skin description;
     * @param  string $author      Skin author;
     * @param  string $author_uri  Skin author URL;
     * @return object $this        The current class object (chainable).
     */

    public function setInfos(
        $name,
        $title = null,
        $version = null,
        $description = null,
        $author = null,
        $author_uri = null
    );

    /**
     * Get a $dir property value related subdirectory path.
     *
     * @param  string $name Directory(/skin) name (default: $this->getName()).
     * @return string       The Path
     */

    public function getSubdirPath($name = null);

    /**
     * Update the txp_section table.
     *
     * @param  string $set   The SET clause (default: "skin = '".doSlash($this->getName())."'")
     * @param  string $where The WHERE clause (default: "skin = '".doSlash($this->getBase())."'")
     * @return bool          FALSE on error.
     */

    public function updateSections($set = null, $where = null);

    /**
     * $uploaded property getter.
     *
     * @param  bool $expanded Set it to false to get a simple associative
     *                        array of skin names and their titles.
     * @return array
     */

    public function getUploaded($expanded = true);

    /**
     * Create/CreateFrom a single skin (and its related assets)
     * Merges results in the related property.
     *
     * @return object $this The current object (chainable).
     */

    public function create();

    /**
     * Update a single skin (and its related dependencies)
     * Merges results in the related property.
     *
     * @return object $this The current object (chainable).
     */

    public function update();

    /**
     * Duplicate multiple skins (and their related $assets)
     * Merges results in the related property.
     *
     * @return object $this The current object (chainable).
     */

    public function duplicate();

    /**
     * Delete multiple skins (and their related $assets + directories if empty)
     * Merges results in the related property.
     *
     * @return object $this The current object (chainable).
     */

    public function delete($sync = false);

    /**
     * Control the admin tab.
     */

    public function admin();

    /**
     * Render (echo) the $step related admin tab.
     *
     * @param string $step
     */

    public function render($step);
}
