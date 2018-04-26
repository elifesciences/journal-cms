#!/usr/bin/env bash
set -e

COMPRESS=""

#########################
# The command line help #
#########################
display_help() {
    echo "Usage: $(basename "$0") [-h] [--gzip]"
    echo
    echo " --gzip Compress the dump using the gzip program which must be in your \$PATH."
    exit 1
}

################################
# Check if parameters options  #
# are given on the commandline #
################################
while true;
do
    case "$1" in
      -h | --help)
          display_help
          exit 0
          ;;
      --gzip)
          COMPRESS="$1 "
          shift 1
          ;;
      *)  # No more options
          break
          ;;
    esac
done

cd "$(dirname $0)/../web"

../vendor/bin/drush sql-dump $COMPRESS--structure-tables-list="user__roles,user__user_picture,users,users_data,users_field_data"
