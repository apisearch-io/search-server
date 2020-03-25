#!/bin/bash

cd /var/www
rm -Rf var/*

php bin/console cache:warmup --env=prod --no-debug --no-interaction
php bin/console apisearch-server:server-configuration --env=prod --no-debug --no-interaction
php vendor/bin/server run 0.0.0.0:8000 --exchange=events --exchange=tokens_update --env=prod --no-interaction
