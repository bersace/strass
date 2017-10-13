#!/bin/bash -eux

# S'assurer que le volume est accessible à l'utilisateur strass.
chown -R strass: ${STRASS_ROOT}

exec ${@-/usr/sbin/php5-fpm --nodaemonize --fpm-config /etc/php5/fpm/php-fpm.conf}
