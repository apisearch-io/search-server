#!/bin/bash

cd /var/www
rm -Rf var/*

php bin/console apisearch-server:generate-crontab --env=prod --no-debug --no-interaction
crond -f
