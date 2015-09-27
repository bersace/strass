#!/bin/bash

set -eux

: ${SERVE_PORT=8000}
export STRASS_MODE=devel
exec php -S localhost:${SERVE_PORT} \
	-d include_path=${PWD}/include/ \
	-d xdebug.profiler_output_dir=${PWD} \
	-d xdebug.profiler_enable_trigger=1 \
	devel.php
