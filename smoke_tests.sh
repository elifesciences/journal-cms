#!/bin/bash
set -ex

local_hostname=$(hostname)
hostname=${1:-$local_hostname}

echo "Ping"
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/ping") == 200 ]

echo "Homepage"
[ $(curl --write-out %{http_code} --silent --output /dev/null "$hostname") == 200 ]

echo "APIs"
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/annual-reports") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/blog-articles") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/collections") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/community") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/covers") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/events") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/interviews") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/job-adverts") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/labs-posts") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/people") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/people?type=leadership") == 200 ] # Deprecated
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/people?type\[\]=leadership") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/podcast-episodes") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/press-packages") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/promotional-collections") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "${hostname}/subjects") == 200 ]

echo "Redis"
php -r '$redis = new \Redis(); $redis->connect($argv[1], 6379);' "$hostname"
