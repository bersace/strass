#!/bin/bash
#
# Exécute des tests python unittest avec une instance de Strass dédiée en tâche
# de fond.
#
# Un TESTCASE correspond à un type de client, avec son scénario depuis la
# création du site, jusqu'à sa mort naturelle.
#

set -eux
set -o pipefail

export SERVER_LOG=server.log
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

# Si non vide, alors on ne supprime pas les données, on saute les tests déjà
# passés.
REUSE=${REUSE-}

# Si non vide, alors on attends une interraction utilisateur après les tests,
# avant de tuer le serveur PHP et de nettoyer les données. Ça permet de
# naviguer dedans et par exemple rédiger les tests, exporter les données, etc.
# Par défaut, is REUSE est activé, on attends à la fin des tests.
BREAKPOINT=${BREAKPOINT-${REUSE}}

teardown() {
    exit_code=$1

    # Activer les breakpoint uniquement dans un shell interacti.
    if [ -t 1 -a -n "${BREAKPOINT}" ] ; then
        echo "Type ENTER to kill PHP and clean temporary files." >&2
        read || true
    fi

    # Tuer PHP
    kill -TERM ${PHP_PID}
    wait ${PHP_PID} || true

    if [ "$exit_code" != '0' ] ; then
        # En cas d'erreur, afficher tout les logs.
        test -f ${SERVER_LOG} && sed 's,^,PHP: ,g' ${SERVER_LOG} >&2
        test -f ghostdriver.log && sed 's,^,SELENIUM: ,g' ghostdriver.log >&2
    fi

    if [ -z "${REUSE}" ] ; then
        rm -f ${SERVER_LOG} ghostdriver.log
        rm -rf ${STRASS_ROOT}
    fi
}

# Si on a installé notre propre phantomjs, l'injecter dans le PATH.
if [ -d phantomjs/bin ] ; then
    export PATH=$(readlink -f phantomjs/bin):$PATH
fi

if [ -n "${REUSE}" ] ; then
    UNITTEST='strass.tests'
else
    UNITTEST='unittest'
    # Supprimer l'état des tests. Car on va supprimer les données !
    rm -rf .reuse-state ${STRASS_ROOT}
fi

UNITTEST_ARGS="--verbose"
if [ -n "${BREAKPOINT}" ] ; then
    UNITTEST_ARGS="${UNITTEST_ARGS} --failfast"
fi

# On précharge sqlite3 avec faketime pour que l'extension PHP5 Pdo_sqlite aussi
# soit leurée.
libsqlite=$(find /usr/lib -name "*libsqlite3.so")
libfaketime=$(dpkg -L libfaketime| grep libfaketime.so)

# Démarrer le serveur le 9 août 2007 à 9h32m08. Accélérer le temps par 10.
LD_PRELOAD="${libfaketime} ${libsqlite}" FAKETIME="@2007-08-09 09:32:08 x10" \
          maint/scripts/serve.sh &>${SERVER_LOG} &
PHP_PID=$!
echo "PHP PID is ${PHP_PID}" >&2

trap 'teardown $?' EXIT QUIT INT TERM ABRT ALRM HUP CHLD

# On délègue à python3 unittest les tests. Usuel.
python3 -m ${UNITTEST} discover ${UNITTEST_ARGS} tests/func/${TESTCASE}/
