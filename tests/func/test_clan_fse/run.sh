#!/bin/bash

set -eux

export TESTCASE=$(basename $(dirname $0))
exec tests/func/test-runner.sh
