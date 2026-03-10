#!/bin/bash
set -ex

local_hostname=$(hostname)
hostname=${1:-$local_hostname}

echo "Ping"
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/ping") == 200 ]

echo "Homepage"
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://$hostname") == 200 ]

echo "APIs"
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/annual-reports") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/blog-articles") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/collections") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/community") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/covers") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/events") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/interviews") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/job-adverts") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/labs-posts") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/people") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/people?type=leadership") == 200 ] # Deprecated
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/people?type\[\]=leadership") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/podcast-episodes") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/press-packages") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/promotional-collections") == 200 ]
[ $(curl --write-out %{http_code} --silent --output /dev/null "https://${hostname}/subjects") == 200 ]

echo "Redis"
php -r '$redis = new \Redis(); $redis->connect($argv[1], 6379);' "$hostname"
