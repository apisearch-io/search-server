# Apisearch - Server

This library is part of the Apisearch project.

[![CircleCI](https://circleci.com/gh/apisearch-io/search-server.svg?style=svg)](https://circleci.com/gh/apisearch-io/search-server)
[![Join the Slack](https://img.shields.io/badge/join%20us-on%20slack-blue.svg)](https://apisearch.slack.com)

Apisearch is an open source search engine fully based on open source third party
technologies. The project provides an *in crescendo* set of language 
integration libraries for her users, as well as some third party projects 
integration bundles, plugins, or javascript widgets.

**Step 1** - First of all, let's use docker to create a clean installation of
the minimum requirements Apisearch server has.

**Using docker-compose** - Clone this repository and use docker-compose

```
git clone git@github.com:apisearch-io/search-server.git
de search-server
docker-compose -f docker-compose/docker-compose-basic.yml up
```

**Using Docker** - Create an elasticsearch and a server containers from the
registry

```
docker run -d \
    --network host \
    -e "ES_JAVA_OPTS=-Xms256m -Xmx256m" \
    -e "discovery.type=single-node" \
    -e "action.auto_create_index=-apisearch*,+*" \
    docker.elastic.co/elasticsearch/elasticsearch:7.9.1
    
docker pull apisearchio/search-server:latest
docker run -d \
    --network host \
    -e "APISEARCH_GOD_TOKEN=0e4d75ba-c640-44c1-a745-06ee51db4e93" \
    -e "APISEARCH_HEALTH_CHECK_TOKEN=6326d504-0a5f-f1ae-7344-8e70b75fcde9" \
    -e "APISEARCH_ENABLED_PLUGINS=elasticsearch" \
    -e "ELASTICSEARCH_HOST=localhost" \
    apisearchio/search-server:latest
```

**Step 2** - Check the Server health

```
curl "http://localhost:8000/health" \
    -H "Apisearch-Token-Id: 6326d504-0a5f-f1ae-7344-8e70b75fcde9"
```

Some first steps for you!

- [Go to DOCS](http://docs.apisearch.io)

or

- [Download and install Apisearch](http://docs.apisearch.io/#download-and-install-apisearch)
- [Create your first application](http://docs.apisearch.io/#create-your-first-application)
- [Import some items](http://docs.apisearch.io/#import-some-items)
- [Create your first search bar](http://docs.apisearch.io/#create-my-first-search-bar)

Take a tour using these links.

- [View a demo](http://apisearch.io)
- [Join us on slack](https://apisearch.slack.com) - or [Get an invitation](https://apisearch-slack.herokuapp.com/)
- [Twitter](https://twitter.com/apisearch_io)

...and remember give us a star on Github! The more stars we have, the further
we'll arrive.
