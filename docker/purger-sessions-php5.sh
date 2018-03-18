#!/bin/bash -eux

find /var/lib/php5/sessions/ -type f -amin +44640 -print0 | xargs -rt -0 rm > /proc/1/fd/1 2>/proc/1/fd/2
