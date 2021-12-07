#!/bin/bash
set -ex

local_hostname=$(hostname)
hostname=${1:-$local_hostname}

function ensure {
    path="$1"
    url="$hostname$path"
    [ $(curl "$url" \
        --retry 3 \
        --retry-delay 1 \
        --retry-connrefused \
        --write-out "%{http_code}" \
        --silent \
        --output /dev/null) == 200 ]
}

echo "Ping"
ensure "/ping"

echo "Homepage"
ensure "/"

echo "APIs"
ensure "/annual-reports"
ensure "/blog-articles"
ensure "/collections"
ensure "/community"
ensure "/covers"
ensure "/events"
ensure "/interviews"
ensure "/job-adverts"
ensure "/labs-posts"
ensure "/people"
ensure "/people?type=leadership"
ensure "/people?type\[\]=leadership" # Deprecated
ensure "/podcast-episodes"
ensure "/press-packages"
ensure "/promotional-collections"
ensure "/subjects"

echo "Redis"
php -r '$redis = new \Redis(); $redis->connect($argv[1], 6379);' "$hostname"
