#!/bin/bash
set -ex

[ $(curl --write-out %{http_code} --silent --output /dev/null $(hostname)) == 200 ]
for api in labs-experiment subject podcast-episode blog-article event interview collection cover; do
    [ $(curl --write-out %{http_code} --silent --output /dev/null -H "Accept: application/vnd.elife.$api-list+json; version=1" $(hostname)/"${api}s") == 200 ]
done

[ $(curl --write-out %{http_code} --silent --output /dev/null -H "Accept: application/vnd.elife.person-list+json; version=1" $(hostname)/people) == 200 ]
