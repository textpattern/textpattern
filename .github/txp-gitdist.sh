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

if [ $# -lt 1 ]; then
    echo 1>&2 Usage: $0 '<version> [dest-dir] [repo-dir]'
    echo 1>&2 ' dest-dir defaults to a temporary location, repo-dir to the current directory.';
    exit 127
fi

VER=$1
DESTDIR=`mktemp -d "${TMPDIR:-/tmp}/txp.XXXXXXXXX"`
OLDDIR=`pwd`
REPODIR=$OLDDIR

if [ $# -eq 2 ]; then
    DESTDIR=$2
fi

if [ $# -eq 3 ]; then
    DESTDIR=$2
    REPODIR=$3
fi

cd $REPODIR

# Export repo to destination -- trailing slash is important!
git checkout-index -a -f --prefix=$DESTDIR/textpattern-$VER/

cd $DESTDIR

# Tidy and remove development helper files.
rm textpattern-$VER.tar.gz
rm textpattern-$VER.zip
rm textpattern-$VER.tar.gz.SHA256SUM
rm textpattern-$VER.zip.SHA256SUM
rm textpattern-$VER/.gitattributes
rm textpattern-$VER/.phpstorm.meta.php
rm textpattern-$VER/CODE_OF_CONDUCT.md
rm textpattern-$VER/composer.json
rm textpattern-$VER/composer.lock
rm textpattern-$VER/CONTRIBUTING.md
rm textpattern-$VER/package.json
rm textpattern-$VER/phpcs.xml
rm textpattern-$VER/README.md
rm textpattern-$VER/SECURITY.md
rm -rf textpattern-$VER/.github
find . -name '.gitignore' -type f -delete
find . -name '.DS_Store' -type f -delete

# Tidy and remove vendor furniture.
rm textpattern-$VER/textpattern/vendors/phpmailer/phpmailer/.editorconfig
rm textpattern-$VER/textpattern/vendors/phpmailer/phpmailer/COMMITMENT
rm textpattern-$VER/textpattern/vendors/phpmailer/phpmailer/README.md
rm textpattern-$VER/textpattern/vendors/phpmailer/phpmailer/SECURITY.md
rm textpattern-$VER/textpattern/vendors/phpmailer/phpmailer/VERSION
rm textpattern-$VER/textpattern/vendors/phpmailer/phpmailer/composer.json

# Build .tar.gz.
echo -e "\n"
echo '== Building textpattern-'$VER'.tar.gz in '$DESTDIR'.'
tar cf - -C $DESTDIR textpattern-$VER | gzip -c9 -q > textpattern-$VER.tar.gz \
&& echo ' - Built textpattern-'$VER'.tar.gz ('$(wc -c textpattern-$VER.tar.gz | awk '{print $1}' | xargs -I {} echo "scale=4; {}/1024^2" | bc | xargs printf "%.2f")'MB).'

# Build .zip.
echo -e "\n"
echo '== Building textpattern-'$VER'.zip in '$DESTDIR'.'
zip --symlinks -r -q -9 textpattern-$VER.zip textpattern-$VER --exclude textpattern-$VER/sites/\* \
&& echo ' - Built textpattern-'$VER'.zip ('$(wc -c textpattern-$VER.zip | awk '{print $1}' | xargs -I {} echo "scale=4; {}/1024^2" | bc | xargs printf "%.2f")'MB).'

# Tests and checksums for .tar.gz.
echo -e "\n"
echo '== Testing textpattern-'$VER'.tar.gz integrity...'
if gzip -t textpattern-$VER.tar.gz 2>&1 | sed 's/^/   /'; then
    echo ' - textpattern-'$VER'.tar.gz passed `gzip -t` integrity test.' \
    && echo ' - Calculating textpattern-'$VER'.tar.gz SHA256 checksum...' \
    && shasum -a 256 textpattern-$VER.tar.gz > textpattern-$VER.tar.gz.SHA256SUM \
    && echo '   '$(cat textpattern-$VER.tar.gz.SHA256SUM | cut -c1-64) \
    && echo ' - Checking textpattern-'$VER'.tar.gz checksum...' \
    && shasum -a 256 -c textpattern-$VER.tar.gz.SHA256SUM 2>&1 | sed 's/^/   /'
else 
    echo ' - textpattern-$VER.tar.gz failed `gzip -t` integrity test.'
fi

# Tests and checksums for .zip.
echo -e "\n"
echo '== Testing textpattern-'$VER'.zip integrity...'
if unzip -q -t textpattern-$VER.zip 2>&1 | sed 's/^/   /'; then
    echo ' - textpattern-'$VER'.zip passed `unzip -t` integrity test.' \
    && echo ' - Calculating textpattern-'$VER'.zip SHA256 checksum...' \
    && shasum -a 256 textpattern-$VER.zip > textpattern-$VER.zip.SHA256SUM \
    && echo '   '$(cat textpattern-$VER.zip.SHA256SUM | cut -c1-64) \
    && echo ' - Checking textpattern-'$VER'.zip checksum...' \
    && shasum -a 256 -c textpattern-$VER.zip.SHA256SUM 2>&1 | sed 's/^/   /'
else 
    echo ' - textpattern-$VER.zip failed `unzip -t` integrity test.'
fi

cd $OLDDIR

echo -e "\n"
echo '== Textpattern v'$VER' built in '$DESTDIR
