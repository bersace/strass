#!/bin/bash -eux

# S'assurer que le volume est accessible à l'utilisateur strass.
chmod 0770 ${STRASS_ROOT}
chown -R strass: ${STRASS_ROOT}/data ${STRASS_ROOT}/private ||:

exec ${@-/usr/sbin/php5-fpm --nodaemonize --fpm-config /etc/php5/fpm/php-fpm.conf}
