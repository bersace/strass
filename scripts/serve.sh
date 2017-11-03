#!/bin/bash -eux

cd $(readlink -m $0/../..)

: ${SERVE_PORT=8000}
export STRASS_MODE=devel
exec php -S 0.0.0.0:${SERVE_PORT} \
    -d include_path=${PWD}/include/ \
    -d upload_max_filesize=10M \
    -d xdebug.profiler_output_dir=${PWD} \
    -d xdebug.profiler_enable_trigger=1 \
    index.php
