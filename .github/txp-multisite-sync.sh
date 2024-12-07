#!/bin/sh

#/*
# * Textpattern Content Management System
# * https://textpattern.com/
# *
# * Copyright (C) 2020 The Textpattern Development Team
# *
# * Textpattern is free software; you can redistribute it and/or
# * modify it under the terms of the GNU General Public License
# * as published by the Free Software Foundation, version 2.
# *
# * Textpattern is distributed in the hope that it will be useful,
# * but WITHOUT ANY WARRANTY; without even the implied warranty of
# * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# * GNU General Public License for more details.
# *
# * You should have received a copy of the GNU General Public License
# * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
# */

# Multisite public root
cp -f ../.htaccess ../sites/site1/public/.htaccess
cp -f ../css.php ../sites/site1/public/css.php
cp -f ../index.php ../sites/site1/public/index.php

# Multisite files
cp -f ../files/.htaccess ../sites/site1/public/files/.htaccess

# Multisite themes
cp -f ../themes/.htaccess ../sites/site1/public/themes/.htaccess
