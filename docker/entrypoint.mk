#!/usr/bin/make -f
# -*- makefile -*-

default:

devserver: fixperms
	sudo -u strass STRASS_ROOT=${STRASS_ROOT} /strass/maint/scripts/serve.sh

fcgi: fixperms
	/usr/sbin/php5-fpm --nodaemonize --fpm-config /etc/php5/fpm/php-fpm.conf

# S'assurer que le volume est accessible à l'utilisateur strass.
fixperms:
	chmod -v 0770 $${STRASS_ROOT}
	chown -v strass: $${STRASS_ROOT} ||:
	chown -vR strass: $${STRASS_ROOT}/data $${STRASS_ROOT}/private ||:

setmaint:
	touch $${STRASS_ROOT}/MAINTENANCE

unsetmaint:
	rm -vf $${STRASS_ROOT}/MAINTENANCE
