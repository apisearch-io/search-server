#!/bin/bash

cd /var/www
rm -Rf var/*

php bin/console event-bus:infra:create --exchange=events --env=prod --no-debug --no-interaction --force

sh $1
