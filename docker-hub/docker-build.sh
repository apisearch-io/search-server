#!/bin/bash

rm -Rf vendor
cps install --no-dev --ignore-platform-reqs

rm -Rf Tests
rm -Rf Plugin/*/Tests
rm -Rf vendor/*/*/*.md
rm -Rf vendor/*/*/composer.json
rm -Rf vendor/*/*/composer.lock
rm -Rf vendor/*/*/LICENSE
rm -Rf vendor/*/*/LICENSE.txt
rm -Rf vendor/*/*/LICENSE.md
rm -Rf vendor/*/*/CHANGELOG
rm -Rf vendor/*/*/CHANGELOG.md
rm -Rf vendor/*/*/docker-compose.yml
rm -Rf vendor/*/*/Dockerfile
rm -Rf vendor/*/*/examples
rm -Rf vendor/*/*/tests
rm -Rf vendor/*/*/Tests
rm -Rf vendor/*/*/doc
rm -Rf vendor/*/*/docs

rm -Rf vendor/*/*/README.md
rm -Rf vendor/*/*/.github
rm -Rf vendor/*/*/.editorconfig
rm -Rf vendor/*/*/phpcs.xml
rm -Rf vendor/*/*/phpmd.xml
rm -Rf vendor/*/*/.php_cs
rm -Rf vendor/*/*/.php_cs.cache
rm -Rf vendor/*/*/.formatter.yml

rm -Rf vendor/*/*/psalm.xml
rm -Rf vendor/*/*/psalm-autoload.php
rm -Rf vendor/*/*/build-phar.sh
rm -Rf vendor/*/*/phpstan.neon
rm -Rf vendor/*/*/Makefile

rm -Rf vendor/*/*/phpunit.xml
rm -Rf vendor/*/*/phpunit.xml.dist
rm -Rf vendor/*/*/phpunit.result.cache

HASH=$(git rev-parse --short HEAD)
IMAGE_NAME=apisearchio/search-server:commit-$HASH
docker build -t "$IMAGE_NAME" .
docker push $IMAGE_NAME

git checkout -- Tests
git checkout -- Plugin
git reset --hard HEAD

cps install --dev --ignore-platform-reqs
