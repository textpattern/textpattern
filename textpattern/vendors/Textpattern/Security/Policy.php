<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2026 The Textpattern Development Team
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
 * Basic CSP options.
 *
 * <code>
 * \Txp::get('\Textpattern\Security\Policy')->getHashes();
 * </code>
 *
 * @since   4.9.2
 * @package Security.
 */

namespace Textpattern\Security;

use \Txp;

class Policy implements \Textpattern\Container\ReusableInterface
{
    private $algos = array('sha256', 'sha384', 'sha512');
    private $hashes = array('script-src-attr' => array(), 'style-src-attr' => array());

    /**
     * Register a CSP hash.
     *
     * @return \Textpattern\Security\Policy
     */

    public function addHash($code, $rule = 'script-src-attr', $algo = 'sha256')
    {
        if (isset($this->hashes[$rule]) and $code = (string)$code) {
            in_array($algo, $this->algos) or $algo = 'sha256';
            $hash = $algo.'-'.base64_encode(hash($algo, $code, true));
            $this->hashes[$rule][$code] = $hash;
        }

        return $this;
    }

    /**
     * Get registered hashes.
     *
     * @return Array
     */

    public function getHashes($rule = '')
    {
        return isset($this->hashes[$rule]) ? quote_list($this->hashes[$rule], ' ') : $this->hashes;
    }
}
