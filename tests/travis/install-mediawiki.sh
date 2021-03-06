#!/bin/bash
set -ex

cd ..

## Use sha (master@5cc1f1d) to download a particular commit to avoid breakages
## introduced by MediaWiki core
if [[ "$MW" == *@* ]]
then
  arrMw=(${MW//@/ })
  MW=${arrMw[0]}
  SOURCE=${arrMw[1]}
else
 MW=$MW
 SOURCE=$MW
fi

wget https://github.com/wikimedia/mediawiki/archive/$SOURCE.tar.gz -O $MW.tar.gz

tar -zxf $MW.tar.gz
mv mediawiki-* mw

cd mw

## MW 1.25+ requires Psr\Logger
if [ -f composer.json ]
then
  composer self-update
  composer install --prefer-source
fi

if [ "$DB" == "postgres" ]
then
  # See #458
  sudo /etc/init.d/postgresql stop
  sudo /etc/init.d/postgresql start

  psql -c 'create database its_a_mw;' -U postgres
  php maintenance/install.php --dbtype $DB --dbuser postgres --dbname its_a_mw --pass nyan TravisWiki admin --scriptpath /TravisWiki
else
  mysql -e 'create database its_a_mw;'
  php maintenance/install.php --dbtype $DB --dbuser root --dbname its_a_mw --dbpath $(pwd) --pass nyan TravisWiki admin --scriptpath /TravisWiki
fi
