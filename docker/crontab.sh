#!/bin/bash

rm -Rf /var/www/apisearch/var/cache
php /var/www/apisearch/bin/console cache:warmup --env=prod --no-debug --no-interaction
php /var/www/apisearch/bin/console apisearch-server:generate-crontab --env=prod --no-debug --no-interaction
cron -f
