#!/bin/bash -eux
exec > /proc/1/fd/1 2>/proc/1/fd/2
find /var/lib/php5/sessions/ -type f -amin +44640 -print0 | xargs -rt -0 rm
