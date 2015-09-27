#!/bin/bash

set -eu
set -o pipefail

SERVER_LOG=server.log
export SERVE_PORT=9000
export STRASS_TEST_SERVER=http://localhost:${SERVE_PORT}

teardown() {
    exit_code=$1

    kill -TERM ${PHP_PID}
    wait ${PHP_PID} || true

    if [ "$exit_code" != '0' ] ; then
        sed 's,^,PHP: ,g' ${SERVER_LOG} >&2
        sed 's,^,SELENIUM: ,g' ghostdriver.log >&2
    fi
    rm -f ${SERVER_LOG} ghostdriver.log
}

maint/scripts/serve.sh &>${SERVER_LOG} &
PHP_PID=$!
echo "PHP PID is ${PHP_PID}" >&2

trap 'teardown $?' EXIT QUIT INT TERM ABRT ALRM HUP CHLD

python3 -m unittest discover tests/func/
