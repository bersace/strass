#!/usr/bin/make -rf
# -*- makefile -*-

ENTRYPOINT=$(lastword $(MAKEFILE_LIST)) --no-print-directory
STRASSDO=sudo -u strass STRASS_ROOT=${STRASS_ROOT}

default:

devserver: fixperms statics
	$(STRASSDO) maint/scripts/serve.sh

fcgi: fixperms statics
	/usr/sbin/php5-fpm --nodaemonize --fpm-config /etc/php5/fpm/php-fpm.conf

# S'assurer que le volume est accessible à l'utilisateur strass.
fixperms:
	chmod -v 0770 $${STRASS_ROOT}
	chown -v strass: $${STRASS_ROOT} ||:
	chown -vR strass: $${STRASS_ROOT}/data $${STRASS_ROOT}/private ||:

migrate: fixperms
	$(STRASSDO) maint/scripts/$@
	$(ENTRYPOINT) statics

setmaint:
	touch $${STRASS_ROOT}/MAINTENANCE

# Générer les pages statiques 500.html et maintenance.html avec le script adhoc.
$(STRASS_ROOT)/%.html:
	$(STRASSDO) maint/scripts/$* > $@

statics: $(STRASS_ROOT)/500.html $(STRASS_ROOT)/maintenance.html

unsetmaint:
	rm -vf $${STRASS_ROOT}/MAINTENANCE
