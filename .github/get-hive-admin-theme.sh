#!/bin/sh

URL="https://codeload.github.com";
AUTHOR="philwareham";
REPO="textpattern-hive-admin-theme";
EXTRACT="textpattern/admin-themes";


TAG="master";
if [ ! -z "$1" ]; then
    TAG="$1";
fi

echo "Get repo: $REPO :: $TAG";
echo "-------------------------------------------------------------------------------";
rm -rf $EXTRACT/hive*;
curl $URL/$AUTHOR/$REPO/tar.gz/$TAG | tar xz --strip=2 --directory=$EXTRACT $REPO-$TAG/dist
