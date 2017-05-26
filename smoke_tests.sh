#!/bin/bash
set -ex

local_hostname=$(hostname)
hostname=${1:-$local_hostname}

echo "Ping"
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/ping") == 200 ]

echo "Homepage"
[ $(curl --write-out %{http_code} --silent --output /dev/null "$hostname") == 200 ]

echo "APIs"
[ $(curl --write-out %{http_code} --silent --output /dev/null -H "Accept: application/vnd.elife.blog-article-list+json; version=1" "${hostname}/blog-articles") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null -H "Accept: application/vnd.elife.collection-list+json; version=1" "${hostname}/collections") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null -H "Accept: application/vnd.elife.cover-list+json; version=1" "${hostname}/covers") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null -H "Accept: application/vnd.elife.event-list+json; version=1" "${hostname}/events") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null -H "Accept: application/vnd.elife.interview-list+json; version=1" "${hostname}/interviews") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null -H "Accept: application/vnd.elife.labs-post-list+json; version=1" "${hostname}/labs-posts") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null -H "Accept: application/vnd.elife.person-list+json; version=1" "${hostname}/people") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null -H "Accept: application/vnd.elife.podcast-episode-list+json; version=1" "${hostname}/podcast-episodes") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null -H "Accept: application/vnd.elife.subject-list+json; version=1" "${hostname}/subjects") == 200 ]

echo "Redis"
php -r '$redis = new \Redis(); $redis->connect($argv[1], 6379);' "$hostname"
