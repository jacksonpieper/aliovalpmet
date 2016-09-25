#!/bin/bash

[[ ! -e /.dockerenv ]] && [[ ! -e /.dockerinit ]] && exit 0

set -xe

apt-get update -yqq
apt-get install git -yqq
apt-get install zlib1g-dev -yqq

/usr/bin/env php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
/usr/bin/env php composer-setup.php --install-dir=/usr/local/bin --filename=composer --version=1.2.1
/usr/bin/env php -r "unlink('composer-setup.php');"

docker-php-ext-install zip
