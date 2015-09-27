#!/bin/bash
#
# Exécute des tests python unittest avec une instance de Strass dédiée en tâche
# de fond.
#
# Un TESTCASE correspond à un type de client, avec son scénario depuis la
# création du site, jusqu'à sa mort naturelle.
#

set -eu
set -o pipefail

SERVER_LOG=server.log
# On utilise pas le port 8000, comme ça on peut avoir un strass de dév qui
# tourne, et lancer les tests.
export SERVE_PORT=9000
export STRASS_TEST_SERVER=http://localhost:${SERVE_PORT}
# On isole chaque *scénario* : sa base, sa conf, ses photos, etc.
export STRASS_ROOT=tests/func/${TESTCASE}/htdocs/

# Dossier où vont être stocké les capture d'écrans, en cas d'erreur
export STRASS_TEST_REPORTS=${STRASS_TEST_REPORTS-.}

# Plutôt que d'installer en mode développement, on injecte directement les libs
# de test dans le PATH python.
export PYTHONPATH=${PYTHONPATH:-.}:tests/func/site-packages/

teardown() {
    exit_code=$1

    # Tuer PHP
    kill -TERM ${PHP_PID}
    wait ${PHP_PID} || true

    if [ "$exit_code" != '0' ] ; then
        # En cas d'erreur, afficher tout les logs.
        sed 's,^,PHP: ,g' ${SERVER_LOG} >&2
        sed 's,^,SELENIUM: ,g' ghostdriver.log >&2
    fi

    # Nettoyer systématiquement.
    rm -f ${SERVER_LOG} ghostdriver.log
    rm -rf ${STRASS_ROOT}
}

maint/scripts/serve.sh &>${SERVER_LOG} &
PHP_PID=$!
echo "PHP PID is ${PHP_PID}" >&2

trap 'teardown $?' EXIT QUIT INT TERM ABRT ALRM HUP CHLD

# On délègue à python3 unittest les tests. Usuel.
python3 -m unittest discover tests/func/${TESTCASE}/
