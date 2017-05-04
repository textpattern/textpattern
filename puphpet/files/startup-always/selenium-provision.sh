#!/usr/bin/env bash
set -e
if [ -e /.s-provisioned ]; then
    echo 'Selenium provisioning already ran.'
else
    cd /usr/local
    sudo wget -O selenium-provision.sh https://gist.githubusercontent.com/natenolting/10bb838d32a2078d56a4/raw/1c80d3525a4841f0498f8b127d8befa8dc8034ff/provision-selenium.sh
    sudo sh selenium-provision.sh
    sudo touch /.s-provisioned
fi