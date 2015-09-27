#!/bin/bash
#
# Boucle sur les dossier test_* pour les lancer indépendament. Exécute tout les
# tests et aggrège le code d'erreur.
#

set -uo pipefail

exit_code=0

for fullpath in $(ls -d tests/func/test_*) ; do
    entry=$(basename $fullpath)
    TESTCASE=$entry tests/func/test-runner.sh &>/dev/stdout| sed "s,^,${entry}: ," >&2
    exit_code=$(($exit_code | $?))
done

exit $exit_code;
