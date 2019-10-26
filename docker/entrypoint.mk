#!/usr/bin/make -rf
# -*- makefile -*-

ENTRYPOINT=$(lastword $(MAKEFILE_LIST)) --no-print-directory
STRASSDO=sudo -Eu strass
export LC_ALL=fr_FR.UTF-8
export STRASS_ROOT?=/strass/htdocs

default:

cron: sessionclean
	set -e; while sleep 1800 ; do $(ENTRYPOINT) $^ ; done

devperms:
	chgrp -R $$(stat -c %g index.php) .
	chmod -R g+rw $${STRASS_ROOT} static/
	find $${STRASS_ROOT} static/ -type d -exec chmod g+x {} ';'

devserver: fixperms statics
	$(STRASSDO) scripts/serve.sh

fcgi: fixperms statics
	/usr/sbin/php-fpm5.6 --nodaemonize --force-stderr --fpm-config /etc/php5/fpm/php-fpm.conf

# S'assurer que le volume est accessible à l'utilisateur strass.
fixperms:
	chown -vR strass $${STRASS_ROOT}
	chmod 0733 /var/lib/php5/sessions
	find $${STRASS_ROOT} -type d -exec chmod u+rwx {} ';'
	find $${STRASS_ROOT} -type f -exec chmod u+rw {} ';'
	if test -d $${STRASS_ROOT}/private ; then chmod -vR o-rwx $${STRASS_ROOT}/private ; fi

migrate: fixperms
	$(STRASSDO) scripts/$@
	$(ENTRYPOINT) statics

restore: fixperms
	rsync --verbose --archive --delete $${STRASS_ROOT}/snapshot/data $${STRASS_ROOT}/snapshot/*.html $${STRASS_ROOT}/snapshot/private $${STRASS_ROOT}/

sessionclean:
	@date
	/usr/lib/php/$@

setmaint:
	touch $${STRASS_ROOT}/MAINTENANCE

# Générer les pages statiques 500.html et maintenance.html avec le script adhoc.
$(STRASS_ROOT)/%.html: FORCE
	if test -f ${STRASS_ROOT}/private/strass.sqlite ; then $(STRASSDO) scripts/$* > $@ ; fi

# Pour sécuriser les migrations des données en prod, on créer un instantannée
# des données. L'instantannée est optimisé pour data/ avec des liens physiques.
# Souvent, une migration va renommer des fichiers, c'est pas la peine de
# recopier la données (surtout les images, les PDF, etc.). Parcontre, dans
# private/ on a la base de données SQLite et la configuration. Ça, on le copie
# réellement car la migration va très certainement éditer le contenu du fichier.
# Enfin, on utilise rsync pour nettoyer les fichiers plutôt que de repartir d'un
# dossier vide.
snapshot: fixperms
	$(STRASSDO) mkdir -vp $${STRASS_ROOT}/snapshot $${STRASS_ROOT}/data
	cp --verbose --archive --force --update --link $${STRASS_ROOT}/data $${STRASS_ROOT}/*.html $${STRASS_ROOT}/snapshot/
	cp --verbose --archive --force --update $${STRASS_ROOT}/private $${STRASS_ROOT}/snapshot/
	rsync --verbose --archive --delete $${STRASS_ROOT}/data $${STRASS_ROOT}/snapshot/
	rm -rf $${STRASS_ROOT}/snapshot/private/cache/*

statics: $(STRASS_ROOT)/500.html $(STRASS_ROOT)/maintenance.html

styles:
	$(MAKE) -C static/styles/strass build
	$(MAKE) -C static/styles/joubert build
	$(MAKE) -C static/styles/modele build

unsetmaint:
	rm -vf $${STRASS_ROOT}/MAINTENANCE

wait:
	tail -f /dev/null

FORCE:
