#!/bin/bash
set -ex

[ $(curl --write-out %{http_code} --silent --output /dev/null -H "Host: elife-2.0-website.dev" localhost) == 200 ]
