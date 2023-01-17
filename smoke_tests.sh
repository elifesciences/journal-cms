#!/bin/bash
set -e

# I think this is failing because we're not running in the same container as the app?
local_hostname="localhost"

hostname=${1:-$local_hostname}
web_port=${2:-80}

redis_host=${3:-$hostname}
redis_port=${4:-6379}

function ensure {
    label="$1"
    path="$2"
    url="http://$hostname:$web_port$path"
    echo "$label $url"
    [ $(curl "$url" \
        --retry 3 \
        --retry-delay 1 \
        --retry-connrefused \
        --write-out "%{http_code}" \
        --silent \
        --output /dev/null) == 200 ]
}

ensure "Homepage" "/"
ensure "Ping" "/ping"
ensure "API" "/annual-reports"
ensure "API" "/blog-articles"
ensure "API" "/collections"
ensure "API" "/community"
ensure "API" "/covers"
ensure "API" "/events"
ensure "API" "/interviews"
ensure "API" "/job-adverts"
ensure "API" "/labs-posts"
ensure "API" "/people"
ensure "API" "/people?type=leadership"
ensure "API" "/people?type\[\]=leadership" # Deprecated
ensure "API" "/podcast-episodes"
ensure "API" "/press-packages"
ensure "API" "/promotional-collections"
ensure "API" "/subjects"

echo "Redis"
php -r '$redis = new \Redis(); $redis->connect($argv[1], (int) $argv[2]);' "$redis_host" "$redis_port"
