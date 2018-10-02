#!/bin/bash

php /var/www/apisearch/bin/console apisearch-server:server-configuration --env=prod
php /var/www/apisearch/bin/server 0.0.0.0:8200 --api --debug
