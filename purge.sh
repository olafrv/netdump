#!/bin/bash
find /var/lib/netdump/{dumps,logs} -type f -mtime +7 -exec rm -v {} \;
find /var/lib/netdump/{dumps,logs} -type d -mtime +7 -exec rmdir --ignore-fail-on-non-empty -v {} \;

