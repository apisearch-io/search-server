#!/bin/bash

cd /var/www
rm -Rf var/*

php bin/console cache:warmup --env=prod --no-debug --no-interaction
php bin/console event-bus:infra:create --exchange=events --exchange=tokens_update --env=prod --no-debug --no-interaction --force

sh $1
