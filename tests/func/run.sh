#!/bin/bash
#
# Boucle sur les dossier test_* pour les lancer indépendament. Exécute tout les
# tests et aggrège le code d'erreur.
#

# Le pipefail est important car on passe les tests à travers sed pour préfixer
# les log.
set -uo pipefail

exit_code=0

for fullpath in $(ls -d tests/func/test_*) ; do
    entry=$(basename $fullpath)
    # Préfixer la sortie avec le nom du scénario.
    TESTCASE=$entry tests/func/test-runner.sh &>/dev/stdout| sed "s,^,${entry}: ," >&2
    # On ne s'arrête pas au premier test, mais on fait un OU sur le statut. Un
    # test en échec, et c'est la batterie qui échoue.
    exit_code=$(($exit_code | $?))
done

exit $exit_code;
