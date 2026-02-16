#!/bin/sh

URL="https://codeload.github.com";
AUTHOR="philwareham";
REPO="textpattern-hive-admin-theme";
EXTRACT1="textpattern/admin-themes";
EXTRACT2="sites/site1/admin/setup";


TAG="main";
if [ ! -z "$1" ]; then
    TAG="$1";
fi

echo "Get repo: $REPO :: $TAG";
echo "-------------------------------------------------------------------------------";
rm -rf $EXTRACT1/hive*;
rm -f $EXTRACT2/setup-multisite.css;
curl $URL/$AUTHOR/$REPO/tar.gz/$TAG | tar xz --exclude=setup-multisite.css --strip=2 --directory=$EXTRACT1 $REPO-$TAG/dist
curl $URL/$AUTHOR/$REPO/tar.gz/$TAG | tar xz --exclude=hive* --strip=2 --directory=$EXTRACT2 $REPO-$TAG/dist
