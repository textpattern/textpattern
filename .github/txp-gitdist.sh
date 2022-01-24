#!/bin/sh

#/*
# * Textpattern Content Management System
# * https://textpattern.com/
# *
# * Copyright (C) 2022 The Textpattern Development Team
# *
# * This file is part of Textpattern.
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
    echo 1>&2 Usage: npm run txp-gitdist '<version> [dest-dir]';
    echo 1>&2 ' dest-dir defaults to a temporary location';
    exit 127;
fi

if [ $# -eq 2 ]; then
    DESTDIR=$2;
else
    DESTDIR=`mktemp -d "${TMPDIR:-/tmp}/txp.XXXXXXXXX"`;
fi

VER=$1;
OLDDIR=`pwd`;

rm -rf $DESTDIR/textpattern-$VER;
# Export repo to destination -- trailing slash is important!
git checkout-index -a -f --prefix=$DESTDIR/textpattern-$VER/

# Check actual checksums. Add '-badchecksum' suffix, if error
cmp --silent textpattern/checksums.txt $DESTDIR/textpattern-$VER/textpattern/checksums.txt;
if [ $? -eq 0 ] ; then
    SUFFIX="";
else
    echo "BAD CHECKSUM! Commit file 'checksums.txt' before release.";
    SUFFIX="-badchecksum";
fi


cd $DESTDIR
rm -f textpattern-$VER.tar.gz
rm -f textpattern-$VER-badchecksum.tar.gz
rm -f textpattern-$VER.zip
rm -f textpattern-$VER-badchecksum.zip
rm -f textpattern-$VER/composer.json
rm -f textpattern-$VER/package.json
rm -f textpattern-$VER/.gitattributes
rm -f textpattern-$VER/.gitignore
rm -f textpattern-$VER/images/.gitignore
rm -f textpattern-$VER/textpattern/.gitignore
rm -f textpattern-$VER/textpattern/tmp/.gitignore
rm -f textpattern-$VER/phpcs.xml
rm -f textpattern-$VER/.phpstorm.meta.php
rm -f textpattern-$VER/README.md
rm -rf textpattern-$VER/.github

tar cfz textpattern-$VER$SUFFIX.tar.gz textpattern-$VER
shasum -a 256 textpattern-$VER$SUFFIX.tar.gz > textpattern-$VER$SUFFIX.tar.gz.SHA256SUM

zip -q -r textpattern-$VER$SUFFIX.zip textpattern-$VER --exclude textpattern-$VER/sites/\*
shasum -a 256 textpattern-$VER$SUFFIX.zip > textpattern-$VER$SUFFIX.zip.SHA256SUM

cd $OLDDIR
echo Textpattern v$VER$SUFFIX built in $DESTDIR
echo "";
