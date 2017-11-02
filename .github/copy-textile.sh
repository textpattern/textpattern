#!/bin/sh

URL="https://codeload.github.com";
AUTHOR="textile";
REPO="php-textile";
EXTRACT="textpattern/vendors/Netcarver/Textile";


TAG="master";
if [ ! -z "$1" ]; then
    TAG="$1";
fi

echo "Get repo: $REPO :: $TAG";
echo "-------------------------------------------------------------------------------";
rm -rf $EXTRACT/*.php;
curl $URL/$AUTHOR/$REPO/tar.gz/$TAG | tar xz --strip=4 --directory=$EXTRACT $REPO-$TAG/src/Netcarver/Textile
