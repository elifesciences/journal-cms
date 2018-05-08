#!/usr/bin/env bash
set -e

DEFAULT_STRUCTURED_TABLES_LIST=" --structure-tables-list=user__roles,user__user_picture,users,users_data,users_field_data"

if [[ ${*} =~ \-\-structure\-tables\-list ]];
then
    DEFAULT_STRUCTURED_TABLES_LIST=""
fi

cd "$(dirname $0)/../web"

../vendor/bin/drush sql-dump $* $DEFAULT_STRUCTURED_TABLES_LIST
