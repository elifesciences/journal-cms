#!/bin/bash
# runs journal-cms with a local nginx server available at localhost:8080
set -e
docker-compose up \
    --remove-orphans
