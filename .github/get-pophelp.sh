#!/bin/sh

URL="https://codeload.github.com";
AUTHOR="textpattern";
REPO="pophelp";
EXTRACT="textpattern/lang";


TAG="main";
if [ ! -z "$1" ]; then
    TAG="$1";
fi

echo "Get repo: $REPO :: $TAG";
echo "-------------------------------------------------------------------------------";
rm -rf $EXTRACT/*pophelp.xml;
curl $URL/$AUTHOR/$REPO/tar.gz/$TAG | tar xz --strip=2 --directory=$EXTRACT $REPO-$TAG/help
