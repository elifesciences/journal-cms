#!/bin/bash
set -e
# Usage: TMP=FOLDER scripts/download-backup.sh DELAY_IN_DAYS
# Example: TMP=/ext/tmp scripts/download-backup.sh 1

# arguments and environment variables to influence behavior
delay="${1:-1}" # default to 1 day back
TMP="${TMP:-/ext/tmp/}"

backupLocation='s3://elife-app-backups/journal-cms/'
selectedMonth="$(date -d "-$delay days" +%Y%m)"
searchPrefix="${selectedMonth}/$(date -d "-$delay days" +%Y%m%d)"
downloadPrefix="${backupLocation}${selectedMonth}/"
echo "Looking for backups into ${backupLocation}${searchPrefix}"

databaseArchive="$(aws s3 ls "${backupLocation}${searchPrefix}" | grep prod | grep elife_2_0 | awk '{print $4}')"
filesArchive="$(aws s3 ls "${backupLocation}${searchPrefix}" | grep prod | grep archive | awk '{print $4}')"
echo "Found ${databaseArchive}, $filesArchive}"

aws s3 cp "${downloadPrefix}${databaseArchive}" "$TMP"
aws s3 cp "${downloadPrefix}${filesArchive}" "$TMP"
