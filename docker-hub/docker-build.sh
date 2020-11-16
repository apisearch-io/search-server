#!/bin/bash

rm -Rf vendor
cps install --no-dev

rm -Rf Tests
rm -Rf Plugin/*/Tests
rm -Rf vendor/*/*/*.md
rm -Rf vendor/*/*/composer.json
rm -Rf vendor/*/*/LICENSE
rm -Rf vendor/*/*/CHANGELOG
rm -Rf vendor/*/*/docker-compose.yml
rm -Rf vendor/*/*/Dockerfile

HASH=$(git rev-parse --short HEAD)
IMAGE_NAME=apisearchio/search-server:commit-$HASH
docker build -t "$IMAGE_NAME" .
docker push $IMAGE_NAME

git checkout -- Tests
git checkout -- Plugin
git reset --hard HEAD

cps install --dev